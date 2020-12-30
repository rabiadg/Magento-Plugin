<?php

namespace TotalProcessing\TPCARDS\Model\ConfigProvider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class TotalProcessingConfigProvider implements ConfigProviderInterface
{
    /**
     * @var PaymentHelper
     */
    private $_paymentHelper;

    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    private $_helper;

    /**
     * @var CheckoutSession
     */
    private $_checkoutSession;

    /**
     * @var StoreManager
     */
    private $_storeManager;

    /**
     * @var string[]
     */
    protected $_methodCodes = [
        'totalprocessing_tpcards',
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    private $methods = [];

    /**
     * TotalProcessingConfigProvider constructor.
     *
     * @param PaymentHelper                   $paymentHelper
     * @param \TotalProcessing\TPCARDS\Helper\Data $helper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        CheckoutSession $checkoutSession,
        StoreManager $storeManager
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_helper = $helper;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;

        foreach ($this->_methodCodes as $code) {
            $this->methods[$code] = $this->_paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * Set configuration for TotalProcessing TPCARDS.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'totalprocessing_tpcards' => [
                ],
            ],
        ];
        foreach ($this->_methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment'][$code]['redirectUrl'] = $this->getMethodRedirectUrl($code);
                $config['payment'][$code]['originDomain'] = $this->getMethodOriginUrl($code);
                $config['payment'][$code]['iframeEnabled'] = '1';
                $config['payment'][$code]['iframeMode'] = 'embedded';
            }
        }
        return $config;
    }

    /**
     * Return redirect URL for method.
     *
     * @param string $code
     *
     * @return mixed
     */
    private function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getCheckoutRedirectUrl();
    }

    /**
     * Return originURL for method.
     *
     * @param string $code
     *
     * @return mixed
     */
    private function getMethodOriginUrl($code)
    {
        return rtrim($this->methods[$code]->getOriginDomainUrl(),"/");
    }

    public function getCheckoutSession() 
    {
        return $this->_checkoutSession;
    }    
}
