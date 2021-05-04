<?php
namespace Mailjet\MailjetBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Documentation : https://dev.mailjet.com/email-api/v3/eventcallbackurl/
 */
class CallbackEvent extends Event
{

    const EVENT_SENT = 'mailjet.event.sent';
    const EVENT_OPEN = 'mailjet.event.open';
    const EVENT_CLICK = 'mailjet.event.click';
    const EVENT_BOUNCE = 'mailjet.event.bounce';
    const EVENT_SPAM = 'mailjet.event.spam';
    const EVENT_BLOCKED = 'mailjet.event.blocked';
    const EVENT_UNSUB = 'mailjet.event.unsub';
    const EVENT_TYPOFIX = 'mailjet.event.typofix';
    const EVENT_PARSEAPI = 'mailjet.event.parseapi';
    const EVENT_NEWSENDER = 'mailjet.event.newsender';
    const EVENT_NEWSENDERAUTOVALID = 'mailjet.event.newsenderautovalid';

    /**
     * array of data payload from Mailjet Event
     * @var array
     */
    protected $data;

    /**
     * @param  array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get data payload from Mailjet Event
     * @method getData
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

}
