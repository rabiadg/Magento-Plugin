<?php
/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 * @package TotalProcessing_Opp
 */
namespace TotalProcessing\Opp\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;

/**
 * Class MagentoMetadata
 * @package TotalProcessing\Opp\Helper
 */
class MagentoMetadata
{
    const CACHE_KEY_MAGENTO_VERSION = 'total_processing_magento_version';

    /**
     * @var CacheConfig
     */
    private $cacheConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var string
     */
    private $magentoVersion;

    /**
     * MagentoMetadata constructor.
     * @param CacheConfig $cache
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        CacheConfig $cache,
        ProductMetadataInterface $productMetadata
    ) {
        $this->cacheConfig = $cache;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @return bool|string
     */
    public function getVersion()
    {
        if (
            !$this->magentoVersion
            && !($this->magentoVersion = $this->cacheConfig->load(self::CACHE_KEY_MAGENTO_VERSION))
        ) {
            $this->magentoVersion = $this->productMetadata->getVersion();
            $this->cacheConfig->save($this->magentoVersion, self::CACHE_KEY_MAGENTO_VERSION);
        }
        return $this->magentoVersion;
    }

    /**
     * @param $version
     * @param string $operator
     * @return bool|int
     */
    public function versionCompare($version, $operator = '>=')
    {
        return version_compare($this->getVersion(), $version, $operator);
    }
}
