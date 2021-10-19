<?php

namespace Wunderman\CustomerImport\Console\Command;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Console\Cli;
use Magento\Framework\Filesystem;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Wunderman\CustomerImport\Model\Customer;

class CreateCustomers extends Command
{
    const PROFILE_NAME = 'profile_name';
    const FILE_SOURCE = 'file_source';

    private $filesystem;
    private $customer;
    private $state;
    private $logger;

    /**
     * @param Filesystem $filesystem
     * @param Customer $customer
     * @param State $state
     * @param LoggerInterface $logger
     */
    public function __construct(
        Filesystem $filesystem,
        Customer $customer,
        State $state,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->customer = $customer;
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     *  {@inheritdoc}
     */
    public function configure(): void
    {
        $arguments = [
            new InputArgument(
                self::PROFILE_NAME,
                InputArgument::REQUIRED,
                'Please provide the profile name'
            ),
            new InputArgument(
                self::FILE_SOURCE,
                InputArgument::REQUIRED,
                'Please provide the file url"'
            )
        ];

        $this->setName('customer:import')
                ->setDescription('Wunderman Magento Technical Excercise')
                ->setDefinition($arguments);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        try {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $profileName = $input->getArgument(self::PROFILE_NAME);
            $file_source = $input->getArgument(self::FILE_SOURCE);
            $fixture = $mediaDir->getAbsolutePath() . $file_source;

            switch ($profileName) {
                case 'sample-csv':
                    $this->customer->install($fixture, $output);
                    break;
                case 'sample-json':
                default:
                    throw new Exception('Selected Profile is not supported, supported profiles [sample-csv]');
            }
            return Cli::RETURN_SUCCESS;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $this->logger->error('Customers Bulk import error: '.$msg, ['exception' => $e]);
            $output->writeln("<error>$msg</error>", OutputInterface::OUTPUT_NORMAL);
            return Cli::RETURN_FAILURE;
        }
    }
}