<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use TotalProcessing\Opp\Gateway\Config\ApplePay\Config;
use TotalProcessing\Opp\Gateway\Request\BaseRequestDataBuilder as DataBuilder;
use TotalProcessing\Opp\Gateway\Request\CustomParameterDataBuilder;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Observer\DataAssignObserver;

/**
 * Class AuthorizeAuthDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request\ApplePay
 */
class AuthorizeAuthDataBuilder extends AuthDataBuilder
{
    const BILLING_COUNTRY = 'billing.country';
    const PAYMENT_TOKEN = 'applePay.paymentToken';
    const SESSION_DECRYPT_PATH = '/decrypt';
    const SHOPPER_ENDPOINT = 'customParameters[SHOPPER_endpoint]';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var ResourceInterface
     */
    protected $moduleResource;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     * @param Serializer $serializer
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader,
        Serializer $serializer
    ) {
        parent::__construct($config, $subjectReader);
        $this->checkoutSession = $checkoutSession;
        $this->moduleResource = $moduleResource;
        $this->productMetadata = $productMetadata;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject Data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $payment = $paymentDataObject->getPayment();
        $billingAddress = $order->getBillingAddress();

        $storeId = $order->getStoreId();
        $quoteId = $this->checkoutSession->getQuoteId();

        $version = "Magento v.{$this->productMetadata->getVersion()} "
            . " / Module TotalProcessing OPP v." . $this->moduleResource->getDataVersion("TotalProcessing_Opp");

        $url = rtrim($this->config->getApiUrl($storeId), '/') . self::SESSION_DECRYPT_PATH;

        $result = [
            self::BILLING_COUNTRY => $billingAddress->getCountryId(),
            self::PAYMENT_TOKEN => $this->serializer->unserialize(
                $payment->getAdditionalInformation(DataAssignObserver::TOKEN)
            ),
            self::SHOPPER_ENDPOINT => $this->config->getShopperEndpoint($storeId),
            DataBuilder::REQUEST_DATA_NAMESPACE => [
                DataBuilder::REQUEST_ENCODE => true,
                DataBuilder::REQUEST_DATA_URL => $url,
                DataBuilder::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ]
            ],
            "customParameters[" . CustomParameterDataBuilder::ORDER_ID . "]" => $order->getId(),
            "customParameters[" . CustomParameterDataBuilder::ORDER_INCREMENT_ID . "]" => $order->getOrderIncrementId(),
            "customParameters[" . CustomParameterDataBuilder::PLUGIN . "]" => $version,
            "customParameters[" . CustomParameterDataBuilder::QUOTE_ID . "]" => $quoteId,
        ];

        $result = array_replace_recursive(parent::build($buildSubject), $result);

        $this->subjectReader->debug("Authorize Request Data", $result);

        return $result;
    }
}
