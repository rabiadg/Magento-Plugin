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
use TotalProcessing\Opp\Gateway\Helper\MerchantTransactionIdProvider;
use TotalProcessing\Opp\Gateway\Helper\MerchantTransactionIdProviderFactory;

/**
 * Class TransactionCheckDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class TransactionCheckDataBuilder extends BaseRequestDataBuilder
{
    const TRANSACTION_PATH = '/v1/query';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var MerchantTransactionIdProviderFactory
     */
    private $merchantTransactionIdProviderFactory;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     * @param MerchantTransactionIdProviderFactory $merchantTransactionIdProviderFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader,
        MerchantTransactionIdProviderFactory $merchantTransactionIdProviderFactory
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->checkoutSession = $checkoutSession;
        $this->merchantTransactionIdProviderFactory = $merchantTransactionIdProviderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("Transaction Check buildSubject Data", $buildSubject);

        $quote = $this->checkoutSession->getQuote();
        $storeId = $quote->getStoreId();

        /** @var MerchantTransactionIdProvider $merchantTransactionIdProvider */
        $merchantTransactionIdProvider = $this->merchantTransactionIdProviderFactory->create();

        $result = [
            AuthenticationDataBuilder::ENTITY_ID => $this->config->getEntityId($storeId),
            PaymentDataBuilder::MERCHANT_TRANSACTION_ID => $merchantTransactionIdProvider->execute(),
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::GET,
                self::REQUEST_DATA_URL =>
                    rtrim($this->config->getApiUrl($storeId), '/') . self::TRANSACTION_PATH,
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ],
            ],
        ];

        $this->subjectReader->debug("Transaction Check Request Data", $result);

        return $result;
    }
}
