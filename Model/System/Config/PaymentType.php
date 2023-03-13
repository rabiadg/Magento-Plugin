<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PaymentType
 */
class PaymentType implements OptionSourceInterface
{
    const DEBIT = 'DB';
    const CAPTURE = 'CP';
    const CREDIT = 'CD';
    const PRE_AUTHORIZATION = 'PA';
    const REFUND = 'RF';
    const REVERSAL = 'RV';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::DEBIT,
                'label' => __('Debit'),
            ],
            [
                'value' => self::CAPTURE,
                'label' => __('Capture')
            ],
            [
                'value' => self::CREDIT,
                'label' => __('Credit')
            ],
            [
                'value' => self::PRE_AUTHORIZATION,
                'label' => __('Pre-authorization')
            ],
            [
                'value' => self::REFUND,
                'label' => __('Refund')
            ],
            [
                'value' => self::REVERSAL,
                'label' => __('Reversal')
            ],
        ];
    }
}
