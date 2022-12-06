<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Setup\Patch\Schema;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\EntityPool;
use Magento\SalesSequence\Model\Config;
use Magento\Store\Model\Store;

/**
 * Class ResolveSalesSequence
 * @package TotalProcessing\Opp\Setup\Patch\Schema
 */
class ResolveSalesSequence implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @var Builder
     */
    private $sequenceBuilder;

    /**
     * @var EntityPool
     */
    private $entityPool;

    /**
     * @var Config
     */
    private $sequenceConfig;

    /**
     * @param SchemaSetupInterface $schemaSetup
     * @param Builder $sequenceBuilder
     * @param EntityPool $entityPool
     * @param Config $sequenceConfig
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        Builder $sequenceBuilder,
        EntityPool $entityPool,
        Config $sequenceConfig
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->sequenceBuilder = $sequenceBuilder;
        $this->entityPool = $entityPool;
        $this->sequenceConfig = $sequenceConfig;
    }

    /**
     * @return void
     * @throws AlreadyExistsException|\Exception
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $setup = $this->schemaSetup;

        $storeSelect = $setup->getConnection()
            ->select()
            ->from($setup->getTable(Store::ENTITY))
            ->where(Store::STORE_ID . ' > 0');

        foreach ($setup->getConnection()->fetchAll($storeSelect) as $store) {
            $storeId = (int)$store[Store::STORE_ID];

            foreach ($this->entityPool->getEntities() as $entityType) {
                $sequenceTableName = $setup->getTable(sprintf('sequence_%s_%d', $entityType, $storeId));

                if (!$setup->getConnection()->isTableExists($sequenceTableName)) {
                    try {
                        $this->sequenceBuilder->setPrefix($storeId)
                            ->setSuffix($this->sequenceConfig->get('suffix'))
                            ->setStartValue($this->sequenceConfig->get('startValue'))
                            ->setStoreId($storeId)
                            ->setStep($this->sequenceConfig->get('step'))
                            ->setWarningValue($this->sequenceConfig->get('warningValue'))
                            ->setMaxValue($this->sequenceConfig->get('maxValue'))
                            ->setEntityType($entityType)
                            ->create();
                    } catch (AlreadyExistsException $e) {
                        // omit exception
                    }
                }
            }
        }

        $this->schemaSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
