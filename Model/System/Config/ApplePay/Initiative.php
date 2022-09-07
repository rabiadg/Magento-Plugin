<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config\ApplePay;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Initiative
 * @package TotalProcessing\Opp\Model\System\Config\ApplePay
 */
class Initiative implements OptionSourceInterface
{
    const MESSAGING = 'messaging';
    const WEB = 'web';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::MESSAGING,
                'label' => __('Messaging'),
            ],
            [
                'value' => self::WEB,
                'label' => __('Web'),
            ],
        ];
    }
}
