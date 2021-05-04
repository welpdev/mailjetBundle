<?php
namespace Mailjet\MailjetBundle\Command;

use Mailjet\MailjetBundle\Manager\EventCallbackUrlManager;
use Mailjet\MailjetBundle\Model\EventCallbackUrl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('mailjet:event-endpoint')
            ->setDescription('Prints URL endpoint that should be configured at mailjet.com website')
            ->addArgument('baseurl', InputArgument::REQUIRED, 'Baseurl with domain to be used in URL, i.e. https://example.com')
            ->addOption(
                'event-type',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'List of eventType: ["sent", "open", "click", "bounce", "blocked", "spam", "unsub"], all by default',
                null
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument('baseurl');
        $uri = $this->getRouter()->generate($this->getRouteName(), array(
            'token' => $this->getToken()
        ));
        $url = sprintf('%s/%s', rtrim($domain, '/'), ltrim($uri, '/'));

        /**
         * @var EventCallbackUrlManager $manager
         */
        $manager = $this->getContainer()->get('mailjet.service.event_callback_manager');

        if ($input->getOption('event-type')) {
            $eventTypes = $input->getOption('event-type');
        } else {
            $eventTypes = ["sent", "open", "click", "bounce", "blocked", "spam", "unsub"];
        }

        foreach ($eventTypes as $eventType) {
            $eventCallBackUrl = new EventCallbackUrl($url, $eventType, true);

            try {
                $manager->get($eventType);
                $output->writeln('update '.$eventType);
                $manager->update($eventType, $eventCallBackUrl);
            } catch (\Exception $e) {
                $output->writeln('create '.$eventType);
                $manager->create($eventCallBackUrl);
            }
        }

        $output->writeln(sprintf('<info>%s callback url has been added to your Mailjet account!</info>', $url));
    }

    /**
     * @return \Symfony\Component\Routing\RouterInterface
     */
    protected function getRouter()
    {
        return $this->getContainer()->get('router');
    }

    /**
     * @return string
     */
    protected function getRouteName()
    {
        return $this->getContainer()->getParameter('mailjet.event_endpoint_route');
    }

    /**
     * @return string
     */
    protected function getToken()
    {
        return $this->getContainer()->getParameter('mailjet.event_endpoint_token');
    }
}
