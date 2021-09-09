<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Config\ApplePay;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\File as AssetFile;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Config\Config as BaseConfig;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Config\Config as GatewayBaseConfig;

class Config extends BaseConfig
{
    const KEY_ACTIVE = 'active';
    const KEY_API_URL = 'api_url';
    const KEY_BRAND_TYPES = 'allowed_brand_types';
    const KEY_DISPLAY_NAME = 'display_name';
    const KEY_DOMAIN_NAMES = 'domain_names';
    const KEY_ENVIRONMENT = 'environment';
    const KEY_MERCHANT_ID_DOMAIN_ASSOCIATION = 'merchant_id_domain_association';
    const KEY_MERCHANT_IDENTIFIER = 'merchant_identifier';
    const KEY_PARTNER_INTERNAL_MERCHANT_IDENTIFIER = 'partner_internal_merchant_identifier';
    const KEY_REGISTER_URL = 'register_url';
    const KEY_SHOPPER_ENDPOINT = 'shopper_endpoint';

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var GatewayBaseConfig
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor
     *
     * @param AssetRepository $assetRepository
     * @param GatewayBaseConfig $config
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        AssetRepository $assetRepository,
        GatewayBaseConfig $config,
        LoggerInterface $logger,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        $methodCode = null,
        $pathPattern = BaseConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->assetRepository = $assetRepository;
        $this->config = $config;
        $this->logger = $logger;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Create a file asset that's subject of fallback system
     *
     * @param string $fileId
     * @param array $params
     * @return AssetFile
     * @throws LocalizedException
     */
    public function createAsset(string $fileId, array $params = []): AssetFile
    {
        $params = array_merge(['_secure' => $this->request->isSecure()], $params);
        return $this->assetRepository->createAsset($fileId, $params);
    }

    /**
     * Returns active environment access token
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getAccessToken($storeId = null): ?string
    {
        return $this->config->getValue(
            $this->getEnvironment($storeId) . "_" . GatewayBaseConfig::KEY_ACCESS_TOKEN,
            $storeId
        );
    }

    /**
     * Returns active environment API URL
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getApiUrl($storeId = null): ?string
    {
        return rtrim(
            $this->getValue(
                $this->getEnvironment($storeId) . "_" . self::KEY_API_URL,
                $storeId
            ),
            '/'
        );
    }

    /**
     * Returns list of available brand types
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableBrandTypes($storeId = null): array
    {
        $brandTypes = $this->getValue(self::KEY_BRAND_TYPES, $storeId);

        return !empty($brandTypes) ? explode(',', $brandTypes) : [];
    }

    /**
     * Returns complete merchant validation URL
     *
     * @return string|null
     */
    public function getCompleteMerchantValidationUrl(): ?string
    {
        return $this->urlBuilder->getUrl(
            'totalprocessing_opp/applepay/merchantsession/',
            ['_secure' => $this->request->isSecure()]
        );
    }

    /**
     * Returns active environment display name
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDisplayName($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_DISPLAY_NAME,
            $storeId
        );
    }

    /**
     * Returns active environment display name
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDomainNames($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_DOMAIN_NAMES,
            $storeId
        );
    }

    /**
     * Returns active environment entity ID
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getEntityId($storeId = null): ?string
    {
        return $this->config->getValue(
            $this->getEnvironment($storeId) . "_" . GatewayBaseConfig::KEY_ENTITY_ID,
            $storeId
        );
    }

    /**
     * Returns active environment
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getEnvironment($storeId = null): ?string
    {
        return $this->getValue(self::KEY_ENVIRONMENT, $storeId);
    }

    /**
     * Returns merchant identifier
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getMerchantIdentifier($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_MERCHANT_IDENTIFIER,
            $storeId
        );
    }

    /**
     * Returns merchant id domain association
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getMerchantIdDomainAssociation($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_MERCHANT_ID_DOMAIN_ASSOCIATION,
            $storeId
        );
    }

    /**
     * Returns partner internal merchant identifier
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getPartnerInternalMerchantIdentifier($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_PARTNER_INTERNAL_MERCHANT_IDENTIFIER,
            $storeId
        );
    }

    /**
     * Returns Payment Button Text
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getPaymentBtnText($storeId = null): ?string
    {
        return $this->config->getPaymentBtnText($storeId);
    }

    /**
     * Returns Shopper Endpoint
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getShopperEndpoint($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_SHOPPER_ENDPOINT,
            $storeId
        );
    }

    /**
     * Gets Payment configuration status.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null): bool
    {
        return $this->config->isActive($storeId) && $this->getValue(self::KEY_ACTIVE, $storeId);
    }
}
