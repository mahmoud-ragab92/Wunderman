<?php

namespace Wunderman\CustomerImport\Model\Import;

use Exception;
use Magento\CustomerImportExport\Model\Import\Customer;

class CustomerImport extends Customer
{
    /**
     * @param array $rowData
     * @return mixed|null
     * @throws Exception
     */
    public function importCustomerData(array $rowData)
    {
        try {

            // prepare customer data
            $this->prepareCustomerData($rowData);
            $entitiesToCreate = [];
            $entitiesToUpdate = [];
            $entitiesToDelete = [];
            $attributesToSave = [];

            //
            $processedData = $this->_prepareDataForUpdate($rowData);
            $entitiesToCreate = array_merge($entitiesToCreate, $processedData[self::ENTITIES_TO_CREATE_KEY]);
            $entitiesToUpdate = array_merge($entitiesToUpdate, $processedData[self::ENTITIES_TO_UPDATE_KEY]);
            foreach ($processedData[self::ATTRIBUTES_TO_SAVE_KEY] as $tableName => $customerAttributes) {
                if (!isset($attributesToSave[$tableName])) {
                    $attributesToSave[$tableName] = [];
                }
                $attributesToSave[$tableName] = array_diff_key(
                        $attributesToSave[$tableName],
                        $customerAttributes
                    ) + $customerAttributes;
            }

            $this->updateItemsCounterStats($entitiesToCreate, $entitiesToUpdate, $entitiesToDelete);

            /**
             * Save prepared data
             */
            if ($entitiesToCreate || $entitiesToUpdate) {
                $this->_saveCustomerEntities($entitiesToCreate, $entitiesToUpdate);
            }
            if ($attributesToSave) {
                $this->_saveCustomerAttributes($attributesToSave);
            }

            return $entitiesToCreate[0]['entity_id'] ?? $entitiesToUpdate[0]['entity_id'] ?? null;
        } catch (Exception $e) {
            error_log('------->>>> here is the customer import error: '. $e->getMessage());
            throw $e;
        }
    }
}