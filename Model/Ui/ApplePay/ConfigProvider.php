<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\Ui\ApplePay;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Asset\Source as AssetSource;
use TotalProcessing\Opp\Gateway\Config\ApplePay\Config;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'totalprocessing_opp_applepay';

    /**
     * @var AssetSource
     */
    protected $assetSource;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $icon = [];

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * Constructor
     *
     * @param Config $config
     * @param SessionManagerInterface $session
     * @param AssetSource $assetSource
     */
    public function __construct(
        Config $config,
        SessionManagerInterface $session,
        AssetSource $assetSource
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->assetSource = $assetSource;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        $storeId = $this->session->getStoreId();
        $isActive = $this->config->isActive($storeId);

        return [
            'payment' => [
                self::CODE => [
                    'availableBrandTypes' => $this->config->getAvailableBrandTypes($storeId),
                    'completeMerchantValidationUrl' => $this->config->getCompleteMerchantValidationUrl(),
                    'displayName' => $this->config->getDisplayName($storeId),
                    'icon' => $this->getIcon(),
                    'isActive' => $isActive,
                    'merchantId' => $this->config->getPartnerInternalMerchantIdentifier($storeId),
                    'paymentBtnText' => $this->config->getPaymentBtnText($storeId),
                ],
            ],
        ];
    }

    /**
     * Get apple pay icon
     *
     * @return array
     * @throws LocalizedException
     */
    public function getIcon(): array
    {
        if (!empty($this->icon)) {
            return $this->icon;
        }

        $asset = $this->config->createAsset('TotalProcessing_Opp::images/other/applepay.png');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesize($asset->getSourceFile());

            $this->icon = [
                'url' => $asset->getUrl(),
                'width' => $width,
                'height' => $height,
            ];
        }

        return $this->icon;
    }
}
