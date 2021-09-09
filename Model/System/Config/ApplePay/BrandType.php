<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\System\Config\ApplePay;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\Config as PaymentConfig;


/**
 * Class BrandType
 */
class BrandType implements OptionSourceInterface
{
    /**
     * Allowed CC types
     *
     * @var array
     */
    protected $allowedTypes = [
        "amex",
        "discover",
        "electron",
        "jcb",
        "maestro",
        "mastercard",
        "visa",
    ];

    protected $map = [
        "AMEX" => 'amex',
        "DISCOVER" => 'discover',
        "MAESTRO" => 'maestro',
        "MASTER" => 'mastercard',
        "VISA" => 'visa',
        "VISAELECTRON" => 'electron',
        "JCB" => 'jcb',
    ];

    /**
     * @var PaymentConfig
     */
    protected $paymentConfig;

    /**
     * Constructor.
     *
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(PaymentConfig $paymentConfig)
    {
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * Return allowed cc types for current method
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * Setter for allowed types
     *
     * @param array $values
     * @return $this
     */
    public function setAllowedTypes(array $values): self
    {
        $this->allowedTypes = $values;
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
            if (array_key_exists($code, $this->map) && in_array($this->map[$code], $allowed)) {
                $options[] = ['value' => $this->map[$code], 'label' => $name];
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
        return $this->paymentConfig->getCcTypes();
    }
}
