<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\Config as PaymentConfig;

/**
 * Class BrandType
 * @package TotalProcessing\Opp\Model\System\Config
 */
class BrandType implements OptionSourceInterface
{
    /**
     * Allowed CC types
     *
     * @var array
     */
    protected $_allowedTypes = [
        "MASTER",
        "VISA",
        "AMEX"
    ];

    /**
     * @var PaymentConfig
     */
    protected $_paymentConfig;

    /**
     * Constructor.
     *
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(PaymentConfig $paymentConfig)
    {
        $this->_paymentConfig = $paymentConfig;
    }

    /**
     * Return allowed cc types for current method
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return $this->_allowedTypes;
    }

    /**
     * Setter for allowed types
     *
     * @param array $values
     * @return $this
     */
    public function setAllowedTypes(array $values): self
    {
        $this->_allowedTypes = $values;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $allowed = $this->getAllowedTypes();
        $options = [];
        foreach ($this->getBrandTypeLabelMap() as $code => $name) {
            if (in_array($code, $allowed)) {
                $options[] = ['value' => $code, 'label' => $name];
            }
        }

        return $options;
    }

    /**
     * Returns list of credit cards types
     *
     * @return array
     */
    public function getBrandTypeLabelMap(): array
    {
        return $this->_paymentConfig->getCcTypes();
    }
}
