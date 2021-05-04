<?php
namespace Mailjet\MailjetBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Mailjet\MailjetBundle\Event\CallbackEvent;

/**
 * @Route("/mailjet-event")
 * Best practice
 * We advise you to follow some basic guidelines for implementation and usage.
 *
 * Process the payload received asynchronously : as much as possible, the webhook script should rely on an asynchronous consumer process that will use the data saved by your webhook. You should keep out of your webhook logic all cross matches of the delivered events with other ressources of our API or your internal database. This step will allow your webhook to answer in a timely manner to our calls and avoid it to timeout and being retried by our server.
 * Check regularly your server logs for any errors : all non 200 errors would be retried and could cause an increasing volume of calls to your system.
 * Leverage the transactional message tagging to simplify reconciliation between the events and your own system.
 */
class EventController extends AbstractController
{

    /**
     * Endpoint for the mailjet events (webhooks)
     * https://dev.mailjet.com/guides/#events
     * https://live-event-dashboard-demo.mailjet.com/
     * @Route("/endpoint/{token}", name="mailjet_event_endpoint", methods={"POST"})
     */
    public function indexAction(Request $request, $token)
    {
        // Token validation
        if ($this->getToken() !== $token) {
            throw new BadRequestHttpException('Token mismatch');
        }

        $data = $this->extractData($request);

        if(!$data){
            throw new BadRequestHttpException('Malformatted or missing data');
        }

        if(isset($data['event'])){
            $data = array($data);
        }
        /*
            Please note that the event types in the collection can be mixed.
            We group together all the events of the last second for the same webhook url.
        */
        // NOTE: use a better dispatcher such as rabbitMQ if you have a huge amount of events (sent, open, click can be a lot...)
        $dispatcher = $this->getDispatcher();

        foreach ($data as $key => $callbackData) {
            $type = $callbackData['event'];
            switch ($type) {
                case 'sent':
                    $dispatcher->dispatch(new CallbackEvent($callbackData), CallbackEvent::EVENT_SENT);
                    break;
                case 'open':
                    $dispatcher->dispatch(new CallbackEvent($callbackData), CallbackEvent::EVENT_OPEN);
                    break;
                case 'click':
                    $dispatcher->dispatch(new CallbackEvent($callbackData), CallbackEvent::EVENT_CLICK);
                    break;
                case 'bounce':
                    $dispatcher->dispatch(new CallbackEvent($callbackData), CallbackEvent::EVENT_BOUNCE);
                    break;
                case 'spam':
                    $dispatcher->dispatch(new CallbackEvent($callbackData), CallbackEvent::EVENT_SPAM);
                    break;
                case 'blocked':
                    $dispatcher->dispatch(new CallbackEvent($callbackData), CallbackEvent::EVENT_BLOCKED);
                    break;
                case 'unsub':
                    $dispatcher->dispatch(new CallbackEvent($callbackData), CallbackEvent::EVENT_UNSUB);
                    break;
                default:
                    throw new BadRequestHttpException('Type mismatch');
                    break;
            }
        }

        return $this->prepareResponse(200);
    }

    /**
     * Override this to use another event dispatcher
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        // NOTE: use a better dispatcher such as rabbitMQ if you have a huge amount of events
        return $this->get('event_dispatcher');
    }

    /**

     * @param  Request $request
     * @return array
     */
    private function extractData(Request $request)
    {
        return json_decode($request->getContent(), true);
    }

    /**
     * @param  int $status
     * @return JsonResponse
     */
    private function prepareResponse($status)
    {
        return new JsonResponse(array('success' => true), $status);
    }

    /**
     * @return string
     */
    private function getToken()
    {
        return $this->container->getParameter('mailjet.event_endpoint_token');
    }
}
