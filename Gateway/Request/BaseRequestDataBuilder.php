<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class BaseRequestDataBuilder
 */
abstract class BaseRequestDataBuilder implements BuilderInterface
{
    const REQUEST_DATA_HEADERS = 'headers';
    const REQUEST_DATA_NAMESPACE = 'requestData';
    const REQUEST_DATA_METHOD = 'method';
    const REQUEST_DATA_RAW_BODY = 'rawBody';
    const REQUEST_DATA_URL = 'url';
    const REQUEST_ENCODE = 'encode';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ResourceInterface
     */
    protected $moduleResource;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * Constructor
     *
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->moduleResource = $moduleResource;
        $this->productMetadata = $productMetadata;
        $this->subjectReader = $subjectReader;
    }

    protected function getVersion(): string
    {
        return "Magento v{$this->productMetadata->getVersion()}/"
            . "Module TotalProcessing OPP v"
            . $this->moduleResource->getDataVersion("TotalProcessing_Opp");
    }
}
