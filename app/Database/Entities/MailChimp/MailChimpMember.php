<?php
declare(strict_types=1);

namespace App\Database\Entities\MailChimp;

use Doctrine\ORM\Mapping as ORM;
use EoneoPay\Utils\Str;

/**
 * @ORM\Entity()
 */
class MailChimpMember extends MailChimpEntity
{
    /**
     * @ORM\Id()
     * @ORM\Column(name="id", type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string
     */
    private $memberId;

    /**
     * @ORM\Column(name="email_address", type="string")
     *
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\Column(name="email_type", type="string", nullable=true)
     *
     * @var string
     */
    private $emailType;

    /**
     * @ORM\Column(name="unique_email_id", type="string", nullable=true)
     *
     * @var string
     */
    private $uniqueEmailId;

    /**
     * @ORM\Column(name="subscriber_hash", type="string", nullable=true)
     *
     * @var string
     */
    private $subscriberHash;

    /**
     * @ORM\Column(name="status", type="string", nullable=true)
     *
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="language", type="string", nullable=true)
     *
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(name="locations", type="array")
     *
     * @var array
     */
    private $locations;

    /**
     * @ORM\Column(name="list_subscription", type="string", nullable=true)
     *
     * @var string
     */
    private $listSubscription = [];

    /**
     * Get member id.
     *
     * @return null|string
     */
    public function getMemberId(): string
    {
        return $this->memberId;
    }

    /**
     * Get member list subscriptions.
     *
     * @return null|array
     */
    public function getListSubscription(): string
    {
        return $this->listSubscription;
    }

    /**
     * Get member subscriber hash.
     *
     * @return null|string
     */
    public function getSubscriberHash(): string
    {
        return $this->subscriberHash;
    }

    /**
     * Get validation rules for mailchimp entity.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'email_address' => 'required|email',
            'email_type' => 'nullable|string',
            'status' => 'required|string',
            'language' => 'nullable|string',
            'locations' => 'nullable|array',
            'locations.latitude' => 'nullable|string',
            'locations.longitude' => 'nullable|string'
        ];
    }

    /**
     * Get validation rules for member update.
     *
     * @return array
     */
    public function getValidationRuleForUpdateMember(): array
    {
        return [
            'email_address' => 'required|email',
            'status' => 'required|string'
        ];
    }

    /**
     * Set unique email id.
     *
     * @param string $uniqueEmailId
     *
     * @return MailChimpList
     */
    public function setUniqueEmailId(string $uniqueEmailId): MailChimpMember
    {
        $this->uniqueEmailId = $uniqueEmailId;

        return $this;
    }

    /**
     * Set member's subscriber hash.
     *
     * @param string $subscriberHash
     *
     * @return MailChimpList
     */
    public function setSubscriberHash(string $subscriberHash): MailChimpMember
    {
        $this->subscriberHash = $subscriberHash;

        return $this;
    }

    /**
     * Set email address.
     *
     * @param string $emailAddress
     *
     * @return MailChimpList
     */
    public function setEmailAddress(string $emailAddress): MailChimpMember
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Set member status.
     *
     * @param string $status
     *
     * @return MailChimpList
     */
    public function setStatus(string $status): MailChimpMember
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set language.
     *
     * @param string $language
     *
     * @return MailChimpList
     */
    public function setLanguage(string $language): MailChimpMember
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set email type.
     *
     * @param string $emailType
     *
     * @return MailChimpList
     */
    public function setEmailType(string $emailType): MailChimpMember
    {
        $this->emailType = $emailType;

        return $this;
    }

    /**
     * Set location - latitude and longitude.
     *
     * @param array $locations
     *
     * @return MailChimpMember
     */
    public function setLocations(array $locations): MailChimpMember
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * Set list subscription.
     *
     * @param string $listSubscription
     *
     * @return MailChimpMember
     */
    public function setListSubscription(string $listSubscription): MailChimpMember
    {
        $this->listSubscription = $listSubscription;

        return $this;
    }

    /**
     * Get array representation of entity.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        $str = new Str();

        foreach (\get_object_vars($this) as $property => $value) {
            $array[$str->snake($property)] = $value;
        }

        return $array;
    }
}
