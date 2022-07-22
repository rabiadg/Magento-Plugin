<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Config;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime as MagentoDateTime;
use Magento\Framework\View\Asset\File as AssetFile;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Config\Config as BaseConfig;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Model\System\Config\BrandType;
use TotalProcessing\Opp\Model\System\Config\Locale;
use TotalProcessing\Opp\Model\System\Config\StyleOptions;

/**
 * Class Config
 */
class Config extends BaseConfig
{
    const YEARS_RANGE = 10;
    const KEY_ACCESS_TOKEN = 'access_token';
    const KEY_ACTIVE = 'active';
    const KEY_API_URL = 'api_url';
    const KEY_BRAND_TYPES = 'allowed_brand_types';
    const KEY_CHECKOUT_ID = 'checkout_id';
    const KEY_COUNTRY_CREDIT_CARD = 'countrycreditcard';
    const KEY_COUNTRIES = 'specificcountry';
    const KEY_COUNTRIES_ALLOW_SPECIFIC = 'allowspecific';
    const KEY_DEFAULT_LOCALE = 'default_locale';
    const KEY_ENVIRONMENT = 'environment';
    const KEY_ENTITY_ID = 'entity_id';
    const KEY_IFRAME_STYLES = 'iframe_style';
    const KEY_PAYMENT_URL = 'payment_url';
    const KEY_PAYMENT_BTN_TEXT = 'payment_btn_text';
    const KEY_REQUIRE_CARDHOLDER = 'require_cardholder';
    const KEY_SCHEDULER = 'scheduler';
    const KEY_SCHEDULER_ACCESS_TOKEN = 'scheduler_access_token';
    const KEY_SCHEDULER_API_URL = 'scheduler_api_url';
    const KEY_SCHEDULER_SKU_TARGET = 'scheduler_sku_target';
    const KEY_SENDER_ID = 'sender_id';
    const KEY_STYLE_OPTIONS = 'style_options';
    const KEY_STYLE_OPTIONS_CUSTOM_IFRAME_CSS = 'style_options_custom_iframe_css';
    const KEY_STYLE_OPTIONS_CUSTOM_IFRAME_JS = 'style_options_custom_iframe_js';
    const KEY_STYLE_OPTIONS_DEFAULT_CSS = 'style_options_default_css';

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * DateTime
     *
     * @var MagentoDateTime
     */
    protected $date;

    /**
     * Locale model
     *
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var BrandType
     */
    private $brandType;

    /**
     * @var Locale
     */
    private $locale;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param AssetRepository $assetRepository
     * @param RequestInterface $request
     * @param ResolverInterface $localeResolver
     * @param UrlInterface $urlBuilder
     * @param MagentoDateTime $date
     * @param BrandType $brandType
     * @param Locale $locale
     * @param LoggerInterface $logger
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AssetRepository $assetRepository,
        RequestInterface $request,
        ResolverInterface $localeResolver,
        UrlInterface $urlBuilder,
        MagentoDateTime $date,
        BrandType $brandType,
        Locale $locale,
        LoggerInterface $logger,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->assetRepository = $assetRepository;
        $this->request = $request;
        $this->localeResolver = $localeResolver;
        $this->urlBuilder = $urlBuilder;
        $this->date = $date;
        $this->brandType = $brandType;
        $this->locale = $locale;
        $this->logger = $logger;
    }

    /**
     * Gets Payment configuration status.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * Gets if cardholder required or not
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCardHolderRequired($storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_REQUIRE_CARDHOLDER, $storeId);
    }

    /**
     * Returns scheduler status
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSchedulerActive($storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_SCHEDULER, $storeId);
    }

    /**
     * Returns active environment schedule access token
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getScheduleAccessToken($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_SCHEDULER_ACCESS_TOKEN,
            $storeId
        );
    }

    /**
     * Returns active environment Schedule API URL
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getScheduleApiUrl($storeId = null): ?string
    {
        return rtrim(
            $this->getValue(
                $this->getEnvironment($storeId) . "_" . self::KEY_SCHEDULER_API_URL,
                $storeId
            ),
            '/'
        );
    }

    /**
     * Returns schedule SKUs list
     *
     * @param int|null $storeId
     * @return array
     */
    public function getScheduleSkus($storeId = null): array
    {
        return array_filter(
            array_map(
                "trim",
                explode(',', (string) $this->getValue(self::KEY_SCHEDULER_SKU_TARGET, $storeId))
            )
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
     * @param null $storeId
     * @return string[]
     */
    public function getAvailableCountries($storeId = null): array
    {
        if (!$this->getValue(self::KEY_COUNTRIES_ALLOW_SPECIFIC, $storeId)) {
            return ["All"];
        }

        $countries = $this->getValue(self::KEY_COUNTRIES, $storeId);

        return !empty($countries) ? explode(',', $countries) : [];
    }

    /**
     * Returns list of available brand types with labels
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableBrandTypeLabelMap($storeId = null): array
    {
        $brandTypes = $this->getAvailableBrandTypes($storeId);

        return array_intersect_key(
            $this->brandType->getBrandTypeLabelMap(),
            array_flip($brandTypes)
        );
    }

    /**
     * Returns payment form locale
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getLocale($storeId = null): ?string
    {
        $locale = $this->locale->getLocale($this->localeResolver->getLocale());

        return $locale ?? $this->getValue(self::KEY_DEFAULT_LOCALE, $storeId);
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
     * Returns active environment access token
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getAccessToken($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_ACCESS_TOKEN,
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
     * Returns active environment entity ID
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getEntityId($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_ENTITY_ID,
            $storeId
        );
    }

    /**
     * Returns active environment payment URL
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getPaymentUrl($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_PAYMENT_URL,
            $storeId
        );
    }

    /**
     * Returns active environment sender ID
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getSenderId($storeId = null): ?string
    {
        return $this->getValue(
            $this->getEnvironment($storeId) . "_" . self::KEY_SENDER_ID,
            $storeId
        );
    }

    /**
     * Returns style options type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getStyleOptions($storeId = null): string
    {
        return $this->getValue(self::KEY_STYLE_OPTIONS, $storeId);
    }

    /**
     * Returns style options custom iframe CSS
     *
     * @param int|null $storeId
     * @return string
     */
    public function getStyleOptionsCustomIframeCss($storeId = null):string
    {
        $value = $this->getValue(self::KEY_STYLE_OPTIONS_CUSTOM_IFRAME_CSS, $storeId) ?? '';
        return preg_replace('/\s+/', ' ', trim($value));
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getStyleOptionsStyleOptionsCustomIframeJs($storeId = null):string
    {
            return $this->getValue(self::KEY_STYLE_OPTIONS_CUSTOM_IFRAME_JS, $storeId) ?? '';
    }
    /**
     * @param null $storeId
     * @return string
     */
    public function getStyleOptionsDefault($storeId = null): string
    {
        return $this->getValue(self::KEY_STYLE_OPTIONS_DEFAULT_CSS, $storeId);
    }

    /**
     * Returns CSS for style attribute of Iframe
     *
     * @param int|null $storeId
     * @return string
     */
    public function getIframeStyles($storeId = null): string
    {
        return $this->getValue(self::KEY_IFRAME_STYLES, $storeId);
    }

    /**
     * Returns Payment Button Text
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getPaymentBtnText($storeId = null): ?string
    {
        return $this->getValue(self::KEY_PAYMENT_BTN_TEXT, $storeId);
    }

    /**
     * Returns source URL
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->urlBuilder->getUrl(
            'totalprocessing_opp/process/frame/',
            ['_secure' => $this->request->isSecure()]
        );
    }

    /**
     * Returns redirect URL
     *
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->urlBuilder->getUrl(
            'totalprocessing_opp/process/status/',
            ['_secure' => $this->request->isSecure()]
        );
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
     * Returns cvv image url
     *
     * @return string|null
     */
    public function getCvvImageUrl(): ?string
    {
        return $this->getViewFileUrl('Magento_Checkout::cvv.png');
    }

    /**
     * Returns view file url
     *
     * @param string $fileId
     * @param array $params
     * @return string|null
     */
    public function getViewFileUrl(string $fileId, array $params = []): ?string
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepository->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ["fileId" => $fileId , "params" => $params]);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }

    /**
     * Returns credit card expiry months list
     *
     * @return array
     */
    public function getCcMonths(): array
    {
        $data = [];

        $months = (new DataBundle())
                ->get(
                    $this->localeResolver->getLocale()
                )['calendar']['gregorian']['monthNames']['format']['wide'] ?? [];

        foreach ($months as $key => $value) {
            $monthNum = ++$key < 10 ? '0' . $key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }
        return $data;
    }

    /**
     * Returns credit card expiry years list
     *
     * @return array
     */
    public function getCcYears(): array
    {
        $years = [];

        $first = (int)$this->date->date('Y');

        for ($index = 0; $index <= self::YEARS_RANGE; $index++) {
            $year = $first + $index;
            $years[$year] = $year;
        }

        return $years;
    }
}
