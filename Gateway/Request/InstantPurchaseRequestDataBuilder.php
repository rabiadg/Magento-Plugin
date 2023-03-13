<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Payment\Gateway\Command\CommandException;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Response\ThreeDSecureHandler;
use TotalProcessing\Opp\Gateway\Helper\PaymentTokenProvider;
use TotalProcessing\Opp\Gateway\Response\InstantPurchaseHandler;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Gateway\Traits\RegistrationIdsTrait;

/**
 * Class AuthorizeRequestDataBuilder
 */
class InstantPurchaseRequestDataBuilder extends BaseRequestDataBuilder
{

    const STATUS_PATH = '/v1/registrations/{registrationId}/payments';
    const PARAM_RECURRING_TYPE = "recurringType";
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var PaymentTokenProvider
     */
    protected $tokensProvider;

    /**
     * InstantPurchaseRequestDataBuilder constructor.
     *
     * @param CheckoutSession          $checkoutSession
     * @param Config                   $config
     * @param ProductMetadataInterface $productMetadata
     * @param ResourceInterface        $moduleResource
     * @param SubjectReader            $subjectReader
     * @param PaymentTokenProvider     $tokensProvider
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        ProductMetadataInterface $productMetadata,
        ResourceInterface $moduleResource,
        SubjectReader $subjectReader,
        PaymentTokenProvider $tokensProvider
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->checkoutSession = $checkoutSession;
        $this->tokensProvider = $tokensProvider;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws CommandException|LocalizedException|NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject Data", $buildSubject);

        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $quoteId = $this->checkoutSession->getQuoteId();

        $storeId = $this->checkoutSession->getQuote()->getStoreId();
        $gatewayTokens = $this->tokensProvider->getFilteredTokens();

        $gatewayToken = $gatewayTokens[0] ?? null;
        if (!$gatewayTokens || !$gatewayToken) {
            $this->subjectReader->critical("There is no stored cards from active Brand types!", $gatewayTokens);
            throw new CommandException(__("There is no stored cards from active Brand types!"));
        }

        $this->subjectReader->debug("Gateway Token", ["PublicHash" => $gatewayToken->getPublicHash(), "Details" =>$gatewayToken->getTokenDetails()]);

        $url = rtrim($this->config->getApiUrl($storeId), '/')
            . str_replace('{registrationId}', $gatewayToken->getGatewayToken(), self::STATUS_PATH);

        $result = [
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::POST,
                self::REQUEST_DATA_URL => $url,
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ],
            ],
            "customParameters[" . CustomParameterDataBuilder::ORDER_ID . "]" => $order->getId(),
            "customParameters[" . CustomParameterDataBuilder::ORDER_INCREMENT_ID . "]" => $order->getOrderIncrementId(),
            "customParameters[" . CustomParameterDataBuilder::PLUGIN . "]" => $this->getVersion(),
            "customParameters[" . CustomParameterDataBuilder::PUBLIC_HASH . "]" => $gatewayToken->getPublicHash(),
            "customParameters[" . CustomParameterDataBuilder::QUOTE_ID . "]" => $quoteId,
            "customParameters[" . InstantPurchaseHandler::CUSTOM_PARAM_PUBLIC_HASH . "]" => $gatewayToken->getPublicHash(),
            self::PARAM_RECURRING_TYPE => "REGISTRATION_BASED"
        ];

        $this->subjectReader->debug("Authorize Request Data", $result);

        return $result;
    }
}
