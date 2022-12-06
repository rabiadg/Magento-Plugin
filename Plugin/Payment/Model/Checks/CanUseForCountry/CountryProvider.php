<?php
/**
 * Copyright © Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TotalProcessing\Opp\Plugin\Payment\Model\Checks\CanUseForCountry;

use Magento\Payment\Model\Checks\CanUseForCountry\CountryProvider as DefaultCountryProvider;
use Magento\Quote\Model\Quote;
use Magento\Directory\Helper\Data as DirectoryHelper;

/**
 * Class CountryProvider
 * @package TotalProcessing\Opp\Plugin\Payment\Model\Checks\CanUseForCountry
 */
class CountryProvider
{
    /**
     * @var DirectoryHelper
     */
    protected $directoryHelper;

    /**
     * @param DirectoryHelper $directoryHelper
     */
    public function __construct(DirectoryHelper $directoryHelper)
    {
        $this->directoryHelper = $directoryHelper;
    }

    /**
     * @param DefaultCountryProvider $subject
     * @param \Closure $proceed
     * @param Quote $quote
     * @return string
     */
    public function aroundGetCountry(
        DefaultCountryProvider $subject,
        \Closure $proceed,
        Quote $quote
    ) {
        $address = $quote->getShippingAddress() ?: $quote->getBillingAddress();
        return (!empty($address) && !empty($address->getCountry()))
            ? $address->getCountry()
            : $this->directoryHelper->getDefaultCountry();
    }
}
