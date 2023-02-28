<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class MerchantTransactionIdType
 * @package TotalProcessing\Opp\Model\System\Config
 */
class MerchantTransactionIdType implements OptionSourceInterface
{
    const INCREMENT_ID = 'increment_id';
    const ORDER_ID = 'entity_id';
    const UUID = 'uuid';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::INCREMENT_ID,
                'label' => __('Increment ID')
            ],
            [
                'value' => self::UUID,
                'label' => __('UUID (universally unique identifier)')
            ]
        ];
    }
}
