<?php

namespace Mailjet\MailjetBundle\DataCollector;

use Mailjet\MailjetBundle\Client\MailjetClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class MailjetDataCollector
 */
class MailjetDataCollector extends DataCollector
{
    /**
     * Mailjet client for common API Call
     * @var MailjetClient
     */
    protected $client;

    /**
     * Mailjet client for transactional email (swiftmailer)
     * @var MailjetClient
     */
    protected $transactionalClient;

    /**
     * @param MailjetClient $client
     * @param MailjetClient $transactionalClient
     */
    public function __construct(MailjetClient $client, MailjetClient $transactionalClient)
    {
        $this->client = $client;
        $this->transactionalClient = $transactionalClient;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request    $request   A Request instance
     * @param Response   $response  A Response instance
     * @param \Exception $exception An Exception instance
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {

        $this->data = $this->client->getCalls();
        $this->data = array_merge($this->data, $this->transactionalClient->getCalls());
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return 'mailjet';
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return call number
     * @method getCallCount
     * @return int
     */
    public function getCallCount()
    {
        return count($this->data);
    }

	/**
	 * Resets this data collector to its initial state.
	 */
	public function reset()
	{
		$this->data = array();
	}
}
