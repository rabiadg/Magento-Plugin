<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Vault\Model\VaultPaymentInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Helper\Command as CommandHelper;
use TotalProcessing\Opp\Model\Ui\ConfigProvider;
use TotalProcessing\Opp\Model\System\Config\StyleOptions;

/**
 * Class Frame
 */
class Frame extends Template
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CommandHelper
     */
    protected $commandHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var string
     */
    protected $checkoutId = '';

    /**
     * @var PaymentDataHelper
     */
    protected $paymentDataHelper;

    /**
     * Frame constructor.
     *
     * @param Template\Context  $context
     * @param CommandHelper     $commandHelper
     * @param Config            $config
     * @param CustomerSession   $customerSession
     * @param PaymentDataHelper $paymentDataHelper
     * @param array             $data
     */
    public function __construct(
        Template\Context $context,
        CommandHelper $commandHelper,
        Config $config,
        CustomerSession $customerSession,
        PaymentDataHelper $paymentDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->commandHelper = $commandHelper;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->paymentDataHelper = $paymentDataHelper;
    }

    /**
     * Get Available Brand types defined in system/config
     *
     * @return string
     */
    public function getBrands(): string
    {
        return implode(" ", $this->config->getAvailableBrandTypes());
    }

    /**
     * Get Available Brand in format for type detection in js
     *
     * @return string
     */
    public function getBrandsDetectionString(): string
    {
        return sprintf('"%s"', implode('","', $this->config->getAvailableBrandTypes()));
    }

    /**
     * Returns total processing checkout id
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function getCheckoutId(): string
    {
        if (empty($this->checkoutId)) {
            $this->checkoutId = $this->commandHelper->getCheckoutId();
        }

        return $this->checkoutId;
    }

    /**
     * Returns if scheduler applicatioble
     *
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isSchedulerActive(): bool
    {
        return $this->commandHelper->isSchedulerActive();
    }

    /**
     * Returns API URL
     *
     * @return string|null
     */
    public function getApiUrl(): ?string
    {
        return $this->config->getApiUrl($this->_session->getStoreId());
    }

    /**
     * Returns payment form locale
     *
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->config->getLocale($this->_session->getStoreId());
    }

    /**
     * Returns payment widget URL
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function getPaymentWidgetsUrl(): string
    {
        return "{$this->getApiUrl()}/v1/paymentWidgets.js?"
            . http_build_query([
                "checkoutId" => $this->getCheckoutId()
            ]);
    }

    /**
     * Returns redirect URL
     *
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->config->getRedirectUrl();
    }

    /**
     * Returns custom css defined in module configuration
     *  check default values in config.xml
     *
     * @return string
     */
    public function getStyleOptionsCustomIframeCss(): string
    {
        return $this->config->getStyleOptionsCustomIframeCss($this->_session->getStoreId());
    }

    /**
     * Returns Default css for all form types defined in module configuration
     *
     * @return string
     */
    public function getStyleOptionsDefault(): string
    {
        return $this->config->getStyleOptionsDefault($this->_session->getStoreId());
    }

    /**
     * Returns style option which is type of the form
     *
     * @return string
     */
    public function getStyleOption(): string
    {
        return $this->config->getStyleOptions($this->_session->getStoreId());
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function isVaultEnabled(): bool
    {
//        return false; // removed while there is a problem with default payments
        $vaultPayment = $this->getVaultPayment();
        return $vaultPayment->isActive($this->_session->getStoreId()) && $this->customerSession->isLoggedIn();
    }

    /**
     * Get configured vault payment for TotalProcessing
     *
     * @return VaultPaymentInterface
     * @throws LocalizedException
     */
    protected function getVaultPayment()
    {
        return $this->paymentDataHelper->getMethodInstance(ConfigProvider::VAULT_CODE);
    }

    /**
     * Returns card style option value
     *
     * @return string
     */
    public function getCardStyleOption(): string
    {
        return StyleOptions::STYLE_OPTIONS_CARD;
    }

    /**
     * Provide btn text from store config
     * @return string
     */
    public function getPaymentBtnText():string
    {
        return $this->config->getPaymentBtnText($this->_session->getStoreId());
    }

    public function getOnReadyCustomScript():string
    {
        return $this->config->getStyleOptionsStyleOptionsCustomIframeJs($this->_session->getStoreId());
    }
}
