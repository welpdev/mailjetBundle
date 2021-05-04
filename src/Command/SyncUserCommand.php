<?php

namespace Mailjet\MailjetBundle\Command;

use Mailjet\MailjetBundle\Synchronizer\ContactsListSynchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use Mailjet\MailjetBundle\Model\ContactsList;
use Mailjet\MailjetBundle\Provider\ProviderInterface;

/**
 * Class SyncUserCommand
 * Sync users in a mailjet contact list
 *
 */
class SyncUserCommand extends Command
{

    /**
     * @var array
     */
    private $lists;

    /**
     * @var ContactsListSynchronizer
     */
    private $synchronizer;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('mailjet:user-sync')
            ->setDescription('Synchronize users with mailjet contact list')
            ->addOption(
                'follow-sync',
                null,
                InputOption::VALUE_NONE,
                'If you want to follow batches execution'
            );
            // @TODO add params : listId, providerServiceKey
    }

    /**
    * {@inheritDoc}
    */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>%s</info>', $this->getDescription()));

        $this->lists = $this->getContainer()->getParameter('mailjet.lists');
        $this->synchronizer = $this->getContainer()->get('mailjet.service.contacts_list_synchronizer');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->lists as $listId => $listParameters) {
            $provider = $this->getProvider($listParameters['contact_provider']);

            $contactList = new ContactsList($listId, ContactsList::ACTION_ADDFORCE, $provider->getContacts());

            $batchesResult = $this->synchronizer->synchronize($contactList);

            if ($input->getOption('follow-sync')) {
                $batchesError = [];
                $batchesResult = $this->refreshBatchesResult($listId, $batchesResult, $batchesError);
                while (!$this->batchesFinished($batchesResult)) {
                    $batchesResult = $this->refreshBatchesResult($listId, $batchesResult, $batchesError);
                    foreach ($batchesResult as $key => $batch) {
                        $output->writeln($this->displayBatchInfo($batch));
                    }
                    sleep(2);
                    $output->writeln('----------------------------------------');
                }

                $output->writeln(sprintf('<info>There are %d batches in error.</info>', count($batchesError)));
                // Recovering error file
                foreach ($this->displayBatchesErrorFile($batchesError) as $line) {
                    $output->writeln($line);
                }
            }

            $output->writeln(sprintf('<info>OK listId: %s, see logs in Mailjet List</info>', $listId));
        }
    }

    /**
     * Get contact provider
     * @param string $providerServiceKey
     * @return ProviderInterface $provider
     */
    private function getProvider($providerServiceKey)
    {
        try {
            $provider = $this->getContainer()->get($providerServiceKey);
        } catch (ServiceNotFoundException $e) {
            throw new \InvalidArgumentException(sprintf('Provider "%s" should be defined as a service.', $providerServiceKey), $e->getCode(), $e);
        }
        if (!$provider instanceof ProviderInterface) {
            throw new \InvalidArgumentException(sprintf('Provider "%s" should implement Mailjet\MailjetBundle\Provider\ProviderInterface.', $providerServiceKey));
        }
        return $provider;
    }

    /**
     * Refresh all batch from Mailjet API
     * @param string $listId
     * @param array $batchesResult
     * @param &array $batchesError
     * @return array
     */
    private function refreshBatchesResult($listId, $batchesResult, &$batchesError)
    {
        $refreshedBatchsResults = [];
        foreach ($batchesResult as $key => $batch) {
            $jobId = $batch['JobID'];
            $batch = $this->synchronizer->getJob($listId, $jobId);
            // We need to array merge because Mailjet API doesn't send jobid in response.
            if ($batch[0]['Status'] == 'Error') {
                array_push($batchesError, array_merge(['JobID' => $jobId], $batch[0]));
            } else {
                array_push($refreshedBatchsResults, array_merge(['JobID' => $jobId], $batch[0]));
            }
        }
        return $refreshedBatchsResults;
    }

    /**
     * Test if all batches are finished
     * @param array $batchesResult
     * @return bool
     */
    private function batchesFinished($batchesResult)
    {
        $allfinished = true;
        foreach ($batchesResult as $key => $batch) {
            if ($batch['Status'] != 'Completed') {
                $allfinished = false;
            }
        }
        return $allfinished;
    }
    /**
     * Pretty display of batch info
     * @param array $batch
     * @return string
     */
    private function displayBatchInfo($batch)
    {
        if ($batch['Status'] == 'Completed') {
            return sprintf('batch %s is Completed, %d operations %s', $batch['JobID'], $batch['Count'], $batch['Error']);
        } else {
            return sprintf('batch %s, current status %s, %d operations %s', $batch['JobID'], $batch['Status'], $batch['Count'], $batch['Error']);
        }
    }

    /**
     * Print Batches Errors
     * @param  array $batchesError
     * @return array
     */
    private function displayBatchesErrorFile($batchesError)
    {
        $output = [];
        foreach ($batchesError as $key => $batch) {
            $errors = $this->synchronizer->getJobJsonError($batch['JobID']);
            array_push($output, '<error><pre>'.print_r($errors).'</pre></error>');
        }

        return $output;
    }
}
