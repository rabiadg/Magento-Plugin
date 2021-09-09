<?php

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

class SettleMode implements OptionSourceInterface
{
    const SETTLEMODE_AUTO = 'auto';
    const SETTLEMODE_DELAYED = 'delayed';
    const SETTLEMODE_MULTI = 'multi';

    /**
     * Possible settle modes.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::SETTLEMODE_AUTO,
                'label' => 'Auto Settle',
            ],
            [
                'value' => self::SETTLEMODE_DELAYED,
                'label' => 'Delayed Settle',
            ],
            [
                'value' => self::SETTLEMODE_MULTI,
                'label' => 'Multi Settle',
            ],
        ];
    }
}
