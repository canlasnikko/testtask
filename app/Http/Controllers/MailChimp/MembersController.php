<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * ListsController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Add Members to a List.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        $listId = $request->get('list_id');
        // Retrieve list id and check if it exist or not
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimp List ID [%s] not found', $listId)],
                404
            );
        }

        // Instantiate entity
        $member = new MailChimpMember($request->all());
        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), 
            $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {

            // Subscribe a member to a list
            $response = $this->mailChimp->post(\sprintf('/lists/%s/members', 
                $list->getMailChimpId()), $request->all());
            
            $member->setUniqueEmailId($response->get('unique_email_id'));
            $member->setSubscriberHash($response->get('id'));
            $member->setListSubscriptions(array($listId));
            // Save list into db
            $this->saveEntity($member);
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse(['message' => \sprintf('Member %s successfully added to %s', 
            $member->getMemberId(), $list->getName())]);
    }

    /**
     * Remove a member to a List.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request, string $memberId): JsonResponse
    {
        // Check if request have correct body format
        $deleteLists = $request->get('list_subscriptions');

        if (empty($deleteLists) || !isset($deleteLists)) {
            return $this->errorResponse(
                ['message' => 'Invalid request parameter'],
                404
            );
        }

        // Retrieve member id and check if it exist or not
        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimp Member [%s] not found', $memberId)],
                404
            );
        }

        try {
            $memberList = $member->getListSubscriptions();
            // Iterate to the request list subscriptions to remove
            foreach ($deleteLists as $deleteList) {
                $list = $this->entityManager->getRepository(MailChimpList::class)->find($deleteList);

                if ($list === null) continue;
                // Call MailChimp API to remove member subscription in a list
                $this->mailChimp->delete(\sprintf('lists/%s/members/%s', 
                    $list->getMailChimpId(), $member->getSubscriberHash()));
                // Update member's subscribed list
                if (($key = array_search($deleteList, $memberList)) !== false) {
                    unset($memberList[$key]);
                }
            }

            $member->setListSubscriptions($memberList);
            $this->saveEntity($member);
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse(['message' => \sprintf('Member %s successfully unsubscribed to the requested list.', 
            $memberId)]);
    }

    /**
     * Update member in a list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $memberId): JsonResponse
    {
        // Retrieve member id and check if it exist or not
        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimp Member [%s] not found', $memberId)],
                404
            );
        }

        $validator = $this->getValidationFactory()->make($request->all(), 
            $member->getValidationRuleForUpdateMember());
        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        $list = $this->entityManager->getRepository(MailChimpList::class)->find($request->get('list_subscription'));

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimp List ID [%s] not found', $listId)],
                404
            );
        }

        try {
            $member->fill($request->all());
            // Call MailChimp API to remove member subscription in a list
            $this->mailChimp->put(\sprintf('lists/%s/members/%s', 
                $list->getMailChimpId(), $member->getSubscriberHash()), $member->toMailChimpArray());
            // Update list properties
            $this->saveEntity($member);

        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse(['message' => \sprintf('Member %s successfully updated to the requested list.', 
            $memberId)]);
    }

    /**
     * Show members in a list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId): JsonResponse
    {
        // Retrieve list id and check if it exist or not
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimp List ID [%s] not found', $listId)],
                404
            );
        }

        try {
            // Call MailChimp API to show members subscribed to the list provided
            $response = $this->mailChimp->get(\sprintf('lists/%s/members/', $list->getMailChimpId()));
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($response->get('members'));
    }
}
