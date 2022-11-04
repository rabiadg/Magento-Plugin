<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Module\ResourceInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class PaymentStatusRequestDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class PaymentStatusRequestDataBuilder extends BaseRequestDataBuilder
{
    const STATUS_PATH = '/v1/checkouts/{checkoutId}/payment';

    const CHECKOUT_ID = 'id';
    const RESOURCE_PATH = 'resourcePath';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("PAYMENT STATUS buildSubject", $buildSubject);

        $checkoutId = $buildSubject[self::CHECKOUT_ID] ?? null;
        if (null === $checkoutId) {
            $checkoutId = $this->checkoutSession->getCheckoutId();
        }

        $resourcePath = $buildSubject[self::RESOURCE_PATH] ?? null;
        if (null === $resourcePath) {
            $resourcePath = str_replace('{checkoutId}', $checkoutId, self::STATUS_PATH);
        }

        $storeId = $this->checkoutSession->getQuote()->getStoreId();
        $url = sprintf('%s%s', rtrim($this->config->getApiUrl($storeId), '/'), $resourcePath);

        $result = [
            AuthenticationDataBuilder::ENTITY_ID => $this->config->getEntityId($storeId),
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::GET,
                self::REQUEST_DATA_URL => $url,
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ],
            ]
        ];

        $this->subjectReader->debug("PAYMENT STATUS request", $result);

        return $result;
    }
}
