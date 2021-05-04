<?php

namespace spec\Mailjet\MailjetBundle\Controller;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Mailjet\MailjetBundle\Event\CallbackEvent;


class EventControllerSpec extends ObjectBehavior
{

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    function let(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Mailjet\MailjetBundle\Controller\EventController');
        $this->shouldHaveType('Symfony\Bundle\FrameworkBundle\Controller\AbstractController');

    }

    function it_bad_request_exception_if_token_mismatch(ContainerInterface $container)
    {
        $request = new Request();
        $request->create('/mailjet/mailjet-event/endpoint/12345678', 'POST');
        $request->setMethod('POST');

        $container->getParameter('mailjet.event_endpoint_token')->shouldBeCalled()->willReturn('NOWAY');

        $this->shouldThrow(new BadRequestHttpException('Token mismatch'))
            ->duringIndexAction($request, '12345678');
    }

    function it_bad_request_exception_if_no_data(ContainerInterface $container)
    {
        $request = new Request();
        $request->create('/mailjet/mailjet-event/endpoint/12345678', 'POST');
        $request->setMethod('POST');

        $container->getParameter('mailjet.event_endpoint_token')->shouldBeCalled()->willReturn('12345678');

        $this->shouldThrow(new BadRequestHttpException('Malformatted or missing data'))
            ->duringIndexAction($request, '12345678');
    }

    function it_pass_with_simple_payload(ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $data = '{
           "event": "unsub",
           "time": 1433334941,
           "MessageID": 20547674933128000,
           "email": "admin@local.com",
           "mj_campaign_id": 7276,
           "mj_contact_id": 126,
           "customcampaign": "",
           "CustomID": "helloworld",
           "Payload": "",
           "mj_list_id": 1,
           "ip": "127.0.0.1",
           "geo": "FR",
           "agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36"
       }';
        $request = new Request();
        $request->initialize([], [], [], [], [], [], $data);

        $container->getParameter('mailjet.event_endpoint_token')->shouldBeCalled()->willReturn('12345678');
        $container->get('event_dispatcher')->shouldBeCalled()->willReturn($eventDispatcher);

        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)), CallbackEvent::EVENT_UNSUB)->shouldBeCalled();

        $this->indexAction($request, '12345678');
    }

    function it_pass_with_grouped_payload(ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $data = '[
           {
              "event": "sent",
              "time": 1433333949,
              "MessageID": 19421777835146490,
              "email": "api@mailjet.com",
              "mj_campaign_id": 7257,
              "mj_contact_id": 4,
              "customcampaign": "",
              "mj_message_id": "19421777835146490",
              "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
              "CustomID": "helloworld",
              "Payload": ""
           },
           {
              "event": "sent",
              "time": 1433333949,
              "MessageID": 19421777835146491,
              "email": "api@mailjet.com",
              "mj_campaign_id": 7257,
              "mj_contact_id": 4,
              "customcampaign": "",
              "mj_message_id": "19421777835146491",
              "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
              "CustomID": "helloworld",
              "Payload": ""
           }
        ]';
        $request = new Request();
        $request->initialize([], [], [], [], [], [], $data);

        $container->getParameter('mailjet.event_endpoint_token')->shouldBeCalled()->willReturn('12345678');
        $container->get('event_dispatcher')->shouldBeCalled()->willReturn($eventDispatcher);

        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[0]), CallbackEvent::EVENT_SENT)->shouldBeCalled();
        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[1]),CallbackEvent::EVENT_SENT)->shouldBeCalled();

        $this->indexAction($request, '12345678');
    }

    function it_throw_exception_if_bad_eventtype(ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $data = '{
           "event": "sqdfkjsqdkjqsndkqsj",
           "time": 1433334941,
           "MessageID": 20547674933128000,
           "email": "admin@local.com",
           "mj_campaign_id": 7276,
           "mj_contact_id": 126,
           "customcampaign": "",
           "CustomID": "helloworld",
           "Payload": "",
           "mj_list_id": 1,
           "ip": "127.0.0.1",
           "geo": "FR",
           "agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36"
       }';
        $request = new Request();
        $request->initialize([], [], [], [], [], [], $data);

        $container->getParameter('mailjet.event_endpoint_token')->shouldBeCalled()->willReturn('12345678');
        $container->get('event_dispatcher')->shouldBeCalled()->willReturn($eventDispatcher);

        $this->shouldThrow(new BadRequestHttpException('Type mismatch'))
            ->duringIndexAction($request, '12345678');
    }

    function it_test_all_event_type(ContainerInterface $container, EventDispatcherInterface $eventDispatcher)
    {
        $data = '[
           {
              "event": "sent",
              "time": 1433333949,
              "MessageID": 19421777835146490,
              "email": "api@mailjet.com",
              "mj_campaign_id": 7257,
              "mj_contact_id": 4,
              "customcampaign": "",
              "mj_message_id": "19421777835146490",
              "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
              "CustomID": "helloworld",
              "Payload": ""
           },
           {
              "event": "open",
              "time": 1433333949,
              "MessageID": 19421777835146491,
              "email": "api@mailjet.com",
              "mj_campaign_id": 7257,
              "mj_contact_id": 4,
              "customcampaign": "",
              "mj_message_id": "19421777835146491",
              "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
              "CustomID": "helloworld",
              "Payload": ""
          },
          {
             "event": "click",
             "time": 1433333949,
             "MessageID": 19421777835146491,
             "email": "api@mailjet.com",
             "mj_campaign_id": 7257,
             "mj_contact_id": 4,
             "customcampaign": "",
             "mj_message_id": "19421777835146491",
             "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
             "CustomID": "helloworld",
             "Payload": ""
         },
         {
             "event": "bounce",
             "time": 1433333949,
             "MessageID": 19421777835146491,
             "email": "api@mailjet.com",
             "mj_campaign_id": 7257,
             "mj_contact_id": 4,
             "customcampaign": "",
             "mj_message_id": "19421777835146491",
             "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
             "CustomID": "helloworld",
             "Payload": ""
         },
         {
            "event": "spam",
            "time": 1433333949,
            "MessageID": 19421777835146491,
            "email": "api@mailjet.com",
            "mj_campaign_id": 7257,
            "mj_contact_id": 4,
            "customcampaign": "",
            "mj_message_id": "19421777835146491",
            "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
            "CustomID": "helloworld",
            "Payload": ""
        },
        {
           "event": "blocked",
           "time": 1433333949,
           "MessageID": 19421777835146491,
           "email": "api@mailjet.com",
           "mj_campaign_id": 7257,
           "mj_contact_id": 4,
           "customcampaign": "",
           "mj_message_id": "19421777835146491",
           "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
           "CustomID": "helloworld",
           "Payload": ""
       }, {
           "event": "unsub",
           "time": 1433333949,
           "MessageID": 19421777835146491,
           "email": "api@mailjet.com",
           "mj_campaign_id": 7257,
           "mj_contact_id": 4,
           "customcampaign": "",
           "mj_message_id": "19421777835146491",
           "smtp_reply": "sent (250 2.0.0 OK 1433333948 fa5si855896wjc.199 - gsmtp)",
           "CustomID": "helloworld",
           "Payload": ""
        }
        ]';
        $request = new Request();
        $request->initialize([], [], [], [], [], [], $data);

        $container->getParameter('mailjet.event_endpoint_token')->shouldBeCalled()->willReturn('12345678');
        $container->get('event_dispatcher')->shouldBeCalled()->willReturn($eventDispatcher);

        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[0]), CallbackEvent::EVENT_SENT)->shouldBeCalled();
        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[1]), CallbackEvent::EVENT_OPEN)->shouldBeCalled();
        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[2]), CallbackEvent::EVENT_CLICK)->shouldBeCalled();
        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[3]), CallbackEvent::EVENT_BOUNCE)->shouldBeCalled();
        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[4]), CallbackEvent::EVENT_SPAM)->shouldBeCalled();
        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[5]), CallbackEvent::EVENT_BLOCKED)->shouldBeCalled();
        $eventDispatcher->dispatch(new CallbackEvent(json_decode($data, true)[6]), CallbackEvent::EVENT_UNSUB)->shouldBeCalled();

        $this->indexAction($request, '12345678');
    }
}
