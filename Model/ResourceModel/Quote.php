<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class Quote
 * @package TotalProcessing\Opp\Model\ResourceModel
 */
class Quote
{
    const COLUMN_PAYMENT_ID = 'tp_payment_id';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string|null $paymentId
     * @param int|null $quoteId
     * @return void
     */
    public function updatePaymentId(string $paymentId = null, int $quoteId = null): void
    {
        if (!$quoteId) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        try {
            $connection->beginTransaction();
            $connection->update(
                $connection->getTableName('quote'),
                [
                    self::COLUMN_PAYMENT_ID => $paymentId
                ],
                $connection->quoteInto(CartInterface::KEY_ENTITY_ID . ' = ?', $quoteId, \Zend_Db::INT_TYPE)
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
        }
    }

    /**
     * @param int|null $quoteId
     * @return string|null
     */
    public function getPaymentId(int $quoteId = null): ?string
    {
        if (!$quoteId) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('quote'))
            ->where(CartInterface::KEY_ENTITY_ID . ' = ?', $quoteId, \Zend_Db::INT_TYPE)
            ->reset(Select::COLUMNS)
            ->columns(self::COLUMN_PAYMENT_ID);
        return (string)$connection->fetchOne($select);
    }
}
