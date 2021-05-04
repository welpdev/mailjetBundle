<?php

namespace Mailjet\MailjetBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Mailjet\MailjetBundle\Model\Contact;

class ContactEvent extends Event
{
    const EVENT_SUBSCRIBE = 'mailjet.event.subscribe';
    const EVENT_UNSUBSCRIBE = 'mailjet.event.unsubscribe';
    const EVENT_UPDATE = 'mailjet.event.update';
    const EVENT_DELETE = 'mailjet.event.delete';
    const EVENT_CHANGE_EMAIL = 'mailjet.event.change_email'; # not implemented yet

    protected $listId;
    protected $contact;
    protected $oldEmail;

    public function __construct($listId, Contact $contact, $oldEmail = null)
    {
        $this->listId = $listId;
        $this->contact = $contact;
        $this->oldEmail = $oldEmail;
    }

    public function getListId()
    {
        return $this->listId;
    }

    public function getContact()
    {
        return $this->contact;
    }

    public function getOldEmail()
    {
        return $this->oldEmail;
    }
}
