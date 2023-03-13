<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ScheduleType
 * @package TotalProcessing\Opp\Model\System\Config
 */
class ScheduleType implements OptionSourceInterface
{
    const ANNUAL = 'Annual';
    const MONTHLY = 'MonthlyS';
    const QUARTERLY = 'Quarterly';

    const ANNUAL_IDENT = 'YR';
    const QUARTERLY_IDENT = 'QTR';

    const ACTION_AMOUNT = 'amount';
    const ACTION_COLLECTION_DAY = 'collectionDay';
    const ACTION_START_DATE = 'startDate';
    const ACTION_TYPE = 'type';

    /**
     * @var int
     */
    private static $collectionDay;

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ANNUAL,
                'label' => __('Annual'),
            ],
            [
                'value' => self::MONTHLY,
                'label' => __('Monthly')
            ],
            [
                'value' => self::QUARTERLY,
                'label' => __('Quarterly')
            ],
        ];
    }

    /**
     * Returns schedule actions
     *
     * @return array[]
     */
    public function getScheduleActions(): array
    {
        $collectionDay = $this->getCollectionDay();

        $date = new \DateTime();

        return [
            self::ANNUAL => [
                self::ACTION_AMOUNT => 0,
                self::ACTION_COLLECTION_DAY => $collectionDay,
                self::ACTION_START_DATE => (clone $date)->add(new \DateInterval('P363D'))->format('Y-m-d'),
                self::ACTION_TYPE => self::ANNUAL,
            ],
            self::MONTHLY => [
                self::ACTION_AMOUNT => 0,
                self::ACTION_COLLECTION_DAY => $collectionDay,
                self::ACTION_START_DATE => (clone $date)->add(new \DateInterval('P25D'))->format('Y-m-d'),
                self::ACTION_TYPE => self::MONTHLY,
            ],
            self::QUARTERLY => [
                self::ACTION_AMOUNT => 0,
                self::ACTION_COLLECTION_DAY => $collectionDay,
                self::ACTION_START_DATE => (clone $date)->add(new \DateInterval('P87D'))->format('Y-m-d'),
                self::ACTION_TYPE => self::QUARTERLY,
            ]
        ];
    }

    /**
     * Returns collection day
     *
     * @return int
     */
    public function getCollectionDay(): int
    {
        if (!isset(self::$collectionDay)) {
            $collectionDay = (int) date("j");
            if ($collectionDay > 28) {
                $collectionDay = 28;
            }

            self::$collectionDay = $collectionDay;
        }

        return self::$collectionDay;
    }
}
