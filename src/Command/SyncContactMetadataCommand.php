<?php

namespace Welp\MailjetBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use Welp\MailjetBundle\Model\ContactMetadata;
use Welp\MailjetBundle\Provider\ProviderInterface;

/**
 * Class SyncUserCommand
 * Sync users in a mailjet contact list
 *
 */
class SyncContactMetadataCommand extends ContainerAwareCommand
{

    /**
     * @var array
     */
    private $contactMetadata;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('welp:mailjet:contactmetadata-sync')
            ->setDescription('Synchronize ContactMetadata in config with Mailjet');
    }

    /**
    * {@inheritDoc}
    */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>%s</info>', $this->getDescription()));

        $this->contactMetadata = $this->getContainer()->getParameter('welp_mailjet.contact_metadata');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->contactMetadata as $contactMetadata) {

            $metadataObj = new ContactMetadata($contactMetadata['name'], $contactMetadata['datatype']);
            
            try {
                $response = $this->getContainer()->get('welp_mailjet.service.contact_metadata_manager')->create($metadataObj);
                $output->writeln(sprintf('<info>%s:%s added!</info>', $contactMetadata['name'], $contactMetadata['datatype']));
            } catch (\RuntimeException $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }
            
        }
    }
}