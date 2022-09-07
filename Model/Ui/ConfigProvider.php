<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Asset\Source as AssetSource;
use TotalProcessing\Opp\Gateway\Config\Config;

/**
 * Class ConfigProvider
 * @package TotalProcessing\Opp\Model\Ui
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'totalprocessing_opp';

    const VAULT_CODE = 'totalprocessing_opp_vault';

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
    private $icons = [];

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
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

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive($storeId),
                    'availableBrandTypes' => $this->config->getAvailableBrandTypes($storeId),
                    'icons' => $this->getIcons(),
                    'locale' => $this->config->getLocale($storeId),
                    'availableCountries' => $this->config->getAvailableCountries($storeId),
                    'months' => $this->config->getCcMonths(),
                    'years' => $this->config->getCcYears(),
                    'cvvImageUrl' => $this->config->getCvvImageUrl(),
                    'vaultCode' => self::VAULT_CODE,
                    'isCardHolderRequired' => $this->config->isCardHolderRequired($storeId),
                    'styleOptions' => $this->config->getStyleOptions($storeId),
                    'styleOptionsCustomIframeCss' => $this->config->getStyleOptionsCustomIframeCss($storeId),
                    'iframeStyles' => $this->config->getIframeStyles($storeId),
                    'source' => $this->config->getSource(),
                    'paymentBtnText' => $this->config->getPaymentBtnText()
                ],
            ],
        ];
    }

    /**
     * Get payment cards icons
     *
     * @return array
     * @throws LocalizedException
     */
    public function getIcons(): array
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $types = $this->config->getAvailableBrandTypeLabelMap();

        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->config->createAsset('TotalProcessing_Opp::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $this->icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height,
                        'title' => __($label),
                    ];
                }
            }
        }

        return $this->icons;
    }
}
