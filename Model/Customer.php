<?php

namespace Wunderman\CustomerImport\Model;

use Exception;
use Generator;
use Magento\Customer\Model\Group;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Wunderman\CustomerImport\Model\Import\Customerimport;
use Symfony\Component\Console\Output\OutputInterface;

class Customer
{
    private $file;
    private $storeManagerInterface;
    private $customerimport;
    private $output;
    protected $group;
    private $logger;

    /**
     * @param File $file
     * @param StoreManagerInterface $storeManagerInterface
     * @param Customerimport $customerimport
     * @param Group $group
     * @param LoggerInterface $logger
     */
    public function __construct(
        File $file,
        StoreManagerInterface $storeManagerInterface,
        Customerimport $customerimport,
        Group $group,
        LoggerInterface $logger
    ) {
        $this->file = $file;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->customerimport = $customerimport;
        $this->group = $group;
        $this->logger = $logger;
    }

    /**
     * @param string $fixture
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function install(string $fixture, OutputInterface $output): void
    {
        $this->output = $output;

        $store = $this->storeManagerInterface->getStore();
        $websiteId = (int) $this->storeManagerInterface->getWebsite()->getId();
        $websiteCode = $this->storeManagerInterface->getWebsite()->getCode();
        $storeId = (int) $store->getId();
        $storeCode = $store->getCode();

        $customerGroupId = $this->group->load('General', 'customer_group_code')->getId();

        $header = $this->readCsvHeader($fixture)->current();

        $row = $this->readCsvRows($fixture, $header);
        $row->next();

        while ($row->valid()) {
            $data = $row->current();
            $this->createCustomer($data, $websiteId, $websiteCode, $storeId, $storeCode, $customerGroupId);
            $row->next();
        }
    }

    /**
     * @param string $file
     * @param array $header
     * @return Generator|null
     */
    private function readCsvRows(string $file, array $header): ?Generator
    {
        $handle = fopen($file, 'rb');

        while (!feof($handle)) {
            $data = [];
            $rowData = fgetcsv($handle);
            if ($rowData) {
                foreach ($rowData as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                yield $data;
            }
        }

        fclose($handle);
    }

    /**
     * @param string $file
     * @return Generator|null
     */
    private function readCsvHeader(string $file): ?Generator
    {
        $handle = fopen($file, 'rb');

        while (!feof($handle)) {
            yield fgetcsv($handle);
        }

        fclose($handle);
    }

    /**
     * @param array $data
     * @param int $websiteId
     * @param $websiteCode
     * @param int $storeId
     * @param $storeCode
     * @param int $customerGroupId
     */
    private function createCustomer(array $data, int $websiteId, $websiteCode, int $storeId, $storeCode, int $customerGroupId): void
    {
        try {
            // collect the customer data
            $customerData = [
                'email'         => $data['emailaddress'],
                '_website'      => $websiteCode,
                '_store'        => $storeCode,
                'confirmation'  => null,
                'dob'           => null,
                'firstname'     => $data['fname'],
                'gender'        => null,
                'group_id'      => $customerGroupId,
                'lastname'      => $data['lname'],
                'middlename'    => null,
                'password_hash' => null,
                'prefix'        => null,
                'store_id'      => $storeId,
                'website_id'    => $websiteId,
                'password'      => null
            ];

            // save the customer data
            $this->customerimport->importCustomerData($customerData);
        } catch (Exception $e) {
            $this->logger->error('Customers Bulk import error'.$e->getMessage(), ['exception' => $e]);
            $this->output->writeln(
                '<error>'. $e->getMessage() .'</error>',
                OutputInterface::OUTPUT_NORMAL
            );
        }
    }
}