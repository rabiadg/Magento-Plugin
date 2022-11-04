<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\MethodInterface;

/**
 * Class PaymentAction
 * @package TotalProcessing\Opp\Model\System\Config
 */
class PaymentAction implements OptionSourceInterface
{
    const AUTHORIZE = MethodInterface::ACTION_AUTHORIZE;
    const AUTHORIZE_CAPTURE = MethodInterface::ACTION_AUTHORIZE_CAPTURE;
    const DEBIT = 'debit';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => self::AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ],
            [
                'value' => self::DEBIT,
                'label' => __('Debit'),
            ],
        ];
    }
}
