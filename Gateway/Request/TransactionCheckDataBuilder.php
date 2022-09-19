<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Module\ResourceInterface;
use Magento\Quote\Api\Data\CartInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Cart ID related to the current order.
     *
     * @var null
     */
    public static $cartId = null;

    /**
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     * @param ResourceInterface $moduleResource
     * @param ProductMetadataInterface $productMetadata
     * @param SubjectReader $subjectReader
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Config $config,
        ResourceInterface $moduleResource,
        ProductMetadataInterface $productMetadata,
        SubjectReader $subjectReader,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($config, $moduleResource, $productMetadata, $subjectReader);
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Get quote.
     * In some cases (detected in Safari) at one of the processes during placing an order, for some reason(s),
     * the checkout session is interrupted, which causes the loss of the necessary information like merchantTransactionId
     * This causes a 'invalid or missing parameter' gateway error.
     * @see \TotalProcessing\Opp\Gateway\Request\PaymentDataBuilder::MERCHANT_TRANSACTION_ID
     *
     * @return CartInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function getQuote()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getId() && (self::$cartId !== null)) {
            try {
                $quote = $this->cartRepository->get(self::$cartId);
            } catch (NoSuchEntityException $e) {
                // omit exception
            }
        }
        return $quote;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject Data", $buildSubject);

        $quote = $this->getQuote();
        $storeId = $quote->getStoreId();

        $result = [
            AuthenticationDataBuilder::ENTITY_ID => $this->config->getEntityId($storeId),
            PaymentDataBuilder::MERCHANT_TRANSACTION_ID => $quote->getOppMerchantTransactionId(),
            self::REQUEST_DATA_NAMESPACE => [
                self::REQUEST_DATA_METHOD => ZendClient::GET,
                self::REQUEST_DATA_URL =>
                    rtrim($this->config->getApiUrl($storeId), '/') . self::TRANSACTION_PATH,
                self::REQUEST_DATA_HEADERS => [
                    "Authorization" => "Bearer {$this->config->getAccessToken($storeId)}",
                ],
            ],
        ];

        $this->subjectReader->debug("Transfer Check Request Data", $result);

        return $result;
    }
}
