<?php

namespace Mailjet\MailjetBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use Mailjet\MailjetBundle\Model\ContactMetadata;
use Mailjet\MailjetBundle\Provider\ProviderInterface;

/**
 * Class SyncUserCommand
 * Sync users in a mailjet contact list
 *
 */
class SyncContactMetadataCommand extends Command
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
            ->setName('mailjet:contactmetadata-sync')
            ->setDescription('Synchronize ContactMetadata in config with Mailjet');
    }

    /**
    * {@inheritDoc}
    */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>%s</info>', $this->getDescription()));

        $this->contactMetadata = $this->getContainer()->getParameter('mailjet.contact_metadata');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @TODO create a ContactMetadataSynchronizer
        // @TODO update existing ContactMetadata (in order to not throw error...)
        foreach ($this->contactMetadata as $contactMetadata) {

            $metadataObj = new ContactMetadata($contactMetadata['name'], $contactMetadata['datatype']);

            try {
                $response = $this->getContainer()->get('mailjet.service.contact_metadata_manager')->create($metadataObj);
                $output->writeln(sprintf('<info>%s:%s added!</info>', $contactMetadata['name'], $contactMetadata['datatype']));
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }

        }
    }
}
