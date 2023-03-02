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
use TotalProcessing\Opp\Gateway\Response\CommonHandler;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\ResourceModel\Quote as ResourceQuote;

/**
 * Class CancelRequestDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
 */
class CancelRequestDataBuilder extends BaseRequestDataBuilder
{
    const PATH = '/v1/payments/{id}';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

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
        $this->subjectReader->debug("CANCEL request buildSubject", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();

        $paymentId = null;
        if ($quote = $this->checkoutSession->getQuote()) {
            $paymentId = $quote->getData(ResourceQuote::COLUMN_PAYMENT_ID);
        }

        $storeId = $order->getStoreId();
        $url = rtrim($this->config->getApiUrl($storeId), '/')
            . str_replace('{id}', $paymentId, self::PATH);

        $result = [
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::POST,
                self::REQUEST_DATA_URL => $url,
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ],
            ]
        ];

        $this->subjectReader->debug("CANCEL request", $result);

        return $result;
    }
}
