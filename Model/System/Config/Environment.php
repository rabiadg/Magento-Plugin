<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Environment
 * @package TotalProcessing\Opp\Model\System\Config
 */
class Environment implements OptionSourceInterface
{
    const ENVIRONMENT_LIVE = 'live';
    const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ENVIRONMENT_SANDBOX,
                'label' => __('Sandbox'),
            ],
            [
                'value' => self::ENVIRONMENT_LIVE,
                'label' => __('Live')
            ]
        ];
    }
}
