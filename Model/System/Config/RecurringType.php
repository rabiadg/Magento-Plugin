<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class RecurringType
 * @package TotalProcessing\Opp\Model\System\Config
 */
class RecurringType implements OptionSourceInterface
{
    const INITIAL = 'INITIAL';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::INITIAL,
                'label' => __('Initial'),
            ],
        ];
    }
}
