<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class StyleOptions
 */
class StyleOptions implements OptionSourceInterface
{
    const STYLE_OPTIONS_CARD = 'card';
    const STYLE_OPTIONS_PLAIN = 'plain';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::STYLE_OPTIONS_CARD,
                'label' => __('Card'),
            ],
            [
                'value' => self::STYLE_OPTIONS_PLAIN,
                'label' => __('Plain')
            ]
        ];
    }
}
