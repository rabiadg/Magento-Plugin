<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\ViewModel\Checkout\Payment;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Payment\Model\MethodInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Helper\Command as CommandHelper;
use TotalProcessing\Opp\Model\Ui\ConfigProvider;
use TotalProcessing\Opp\Model\System\Config\StyleOptions;

/**
 * Payment frame view model.
 *
 * Class FrameViewModel
 * @package TotalProcessing\Opp\ViewModel\Checkout\Payment
 */
class FrameViewModel extends DataObject implements ArgumentInterface
{
    /**
     * @var CommandHelper
     */
    private $commandHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var PaymentDataHelper
     */
    private $paymentDataHelper;

    /**
     * @var string
     */
    private $checkoutId = '';

    /**
     * @param CommandHelper $commandHelper
     * @param Config $config
     * @param CustomerSession $customerSession
     * @param PaymentDataHelper $paymentDataHelper
     * @param array $data
     */
    public function __construct(
        CommandHelper $commandHelper,
        Config $config,
        CustomerSession $customerSession,
        PaymentDataHelper $paymentDataHelper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->commandHelper = $commandHelper;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->paymentDataHelper = $paymentDataHelper;
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getAvailableBrandTypes($storeId = null): array
    {
        return $this->config->getAvailableBrandTypes($storeId);
    }

    /**
     * Get Available Brand types defined in system/config
     *
     * @param null $storeId
     * @return string
     */
    public function getBrands($storeId = null): string
    {
        if ($availableBrandTypes = $this->getAvailableBrandTypes($storeId)) {
            return implode(" ", $availableBrandTypes);
        }
        return '';
    }

    /**
     * Get Available Brand in format for type detection in js
     *
     * @param null $storeId
     * @return string
     */
    public function getBrandsDetectionString($storeId = null): string
    {
        if ($availableBrandTypes = $this->getAvailableBrandTypes($storeId)) {
            return sprintf('"%s"', implode('","', $availableBrandTypes));
        }
        return '';
    }

    /**
     * Returns total processing checkout id
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws CommandException|\Exception
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
     * @throws NoSuchEntityException
     */
    public function isSchedulerActive(): bool
    {
        return $this->commandHelper->isSchedulerActive();
    }

    /**
     * Returns API URL
     *
     * @param null $storeId
     * @return string|null
     */
    public function getApiUrl($storeId = null): ?string
    {
        return $this->config->getApiUrl($storeId);
    }

    /**
     * Returns payment form locale
     *
     * @param null $storeId
     * @return string|null
     */
    public function getLocale($storeId = null): ?string
    {
        return $this->config->getLocale($storeId);
    }

    /**
     * Returns payment widget URL
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws CommandException
     */
    public function getPaymentWidgetsUrl($storeId = null): string
    {
        return "{$this->getApiUrl($storeId)}/v1/paymentWidgets.js?"
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
     * @param null $storeId
     * @return string
     */
    public function getStyleOptionsCustomIframeCss($storeId = null): string
    {
        return $this->config->getStyleOptionsCustomIframeCss($storeId);
    }

    /**
     * Returns Default css for all form types defined in module configuration
     *
     * @param null $storeId
     * @return string
     */
    public function getStyleOptionsDefault($storeId = null): string
    {
        return $this->config->getStyleOptionsDefault($storeId);
    }

    /**
     * Returns style option which is type of the form
     *
     * @param null $storeId
     * @return string
     */
    public function getStyleOption($storeId = null): string
    {
        return $this->config->getStyleOptions($storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     * @throws LocalizedException
     */
    public function isVaultEnabled($storeId = null): bool
    {
        $vaultPayment = $this->getVaultPayment();
        return $vaultPayment->isActive($storeId) && $this->customerSession->isLoggedIn();
    }

    /**
     * Get configured vault payment for TotalProcessing
     *
     * @return MethodInterface
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
     *
     * @param null $storeId
     * @return string
     */
    public function getPaymentBtnText($storeId = null): string
    {
        return $this->config->getPaymentBtnText($storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getOnReadyCustomScript($storeId = null): string
    {
        return $this->config->getStyleOptionsStyleOptionsCustomIframeJs($storeId);
    }
}
