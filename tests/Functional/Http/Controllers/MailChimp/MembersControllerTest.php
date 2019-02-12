<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test application add member to a list subscription
     *
     * @return void
     */
    public function testAddMemberToListSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $list = \json_decode($this->response->content(), true);

        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']), static::$memberData);
        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        self::assertArrayHasKey('member_id', $content);
        self::assertNotNull($content['member_id']);

        $this->createdListsId[] = $list['mail_chimp_id']; // Store List Id for deleting later
    }

    /**
     * Test application returns custom error message when passing wrong list number
     *
     * @return void
     */
    public function testAddMemberValidationFailed(): void
    {
        $this->post(\sprintf('/mailchimp/lists/%s/members', '1234'), static::$memberData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('MailChimp List ID [1234] not found', $content['message']);
    }

    /**
     * Test application for updating member in a list
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $list = \json_decode($this->response->content(), true);

        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']), static::$memberData);
        $content = \json_decode($this->response->getContent(), true);

        $this->put(\sprintf('/mailchimp/lists/%s/members/%s', $list['list_id'], $content['member_id']), static::$updateData);
        $response = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        self::assertEquals(\sprintf('Member %s successfully updated to the requested list.', 
            $content['member_id']), $response['message']);

        $this->createdListsId[] = $list['mail_chimp_id'];
    }

    /**
     * Test application for removing member in a list
     *
     * @return void
     */
    public function testRemoveListSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $list = \json_decode($this->response->content(), true);

        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']), static::$memberData);
        $content = \json_decode($this->response->getContent(), true);

        $this->delete(\sprintf('/mailchimp/lists/%s/members/%s', $list['list_id'], $content['member_id']));
        $response = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        self::assertEquals(\sprintf('Member %s successfully unsubscribed to the requested list.', 
            $content['member_id']), $response['message']);

        $this->createdListsId[] = $list['mail_chimp_id'];
    }

    /**
     * Test application for retrieving the members in a list
     *
     * @return void
     */
    public function testShowMembersSuccessfully(): void
    {
        $this->post('/mailchimp/lists', static::$listData);
        $list = \json_decode($this->response->content(), true);

        $this->post(\sprintf('/mailchimp/lists/%s/members', $list['list_id']), static::$memberData);
        $content = \json_decode($this->response->getContent(), true);

        $this->get(\sprintf('/mailchimp/lists/%s/members', $list['list_id']));
        $response = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();

        $this->createdListsId[] = $list['mail_chimp_id'];
    }
}
