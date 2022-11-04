<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CustomerTokenManagement;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use TotalProcessing\Opp\Gateway\Helper\SuccessCode;
use TotalProcessing\Opp\Gateway\Request\PaymentDataBuilder;
use TotalProcessing\Opp\Gateway\SubjectReader;
use TotalProcessing\Opp\Model\System\Config\PaymentType;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class TransactionCheckHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class TransactionCheckHandler implements HandlerInterface
{
    const PAYMENTS_NAMESPACE = 'payments';
    const CUSTOMER_NAMESPACE = 'customer';
    const BILLING_NAMESPACE = 'billing';
    const RESULT_DETAILS_NAMESPACE = 'resultDetails';

    const IS_PRE_AUTHORIZED = "is_pre_authorized";
    const IS_CAPTURED = "is_captured";

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerTokenManagement
     */
    protected $customerTokenManagement;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var InfoInterface|null
     */
    private $payment = null;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CustomerTokenManagement $customerTokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param SubjectReader $subjectReader
     * @param Serializer $serializer
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerTokenManagement $customerTokenManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        SubjectReader $subjectReader,
        Serializer $serializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerTokenManagement = $customerTokenManagement;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->serializer = $serializer;
        $this->subjectReader = $subjectReader;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payments = $this->getPayments($response);
        if ($payments) {
            $paymentDataObject = $this->subjectReader->readPayment($handlingSubject);

            $payment = $paymentDataObject->getPayment();
            $quote = $this->checkoutSession->getQuote();
            ContextHelper::assertOrderPayment($payment);

            $payment->setAdditionalInformation(
                PaymentDataBuilder::MERCHANT_TRANSACTION_ID,
                $quote->getOppMerchantTransactionId()
            );

            $preAuthorizeTransaction = false;
            $captureTransaction = false;
            $debitTransaction = false;

            foreach ($payments as $transaction) {
                $paymentType = $transaction[PaymentDetailsHandler::BASIC_PAYMENT_TYPE] ?? '';

                if (($preAuthorizeTransaction && $captureTransaction) || ($paymentType == PaymentType::DEBIT)) {
                    if ($paymentType == PaymentType::DEBIT) {
                        // for debit type set last transaction details
                        $debitTransaction = end($payments);
                    }
                    break;
                }

                if ($paymentType == PaymentType::PRE_AUTHORIZATION && !$preAuthorizeTransaction) {
                    $preAuthorizeTransaction = $transaction;
                } elseif ($paymentType == PaymentType::CAPTURE && !$captureTransaction) {
                    $captureTransaction = $transaction;
                }
            }

            if ($captureTransaction && !$preAuthorizeTransaction) {
                $preAuthorizeTransaction = $captureTransaction;
            }

            $paymentData = $debitTransaction ?? $preAuthorizeTransaction;
            if ($paymentData) {
                $preAuthorizeTransaction = $this->handleResponseData($payment, $paymentData, $response);
            }

            if ($debitTransaction) {
                $fields = [
                    TransactionCheckHandler::IS_PRE_AUTHORIZED,
                    TransactionCheckHandler::IS_CAPTURED
                ];
                foreach ($fields as $field) {
                    $payment->setAdditionalInformation($field, $debitTransaction);
                }
            } else {
                $payment->setAdditionalInformation(TransactionCheckHandler::IS_PRE_AUTHORIZED, $preAuthorizeTransaction);
                $payment->setAdditionalInformation(TransactionCheckHandler::IS_CAPTURED, $captureTransaction);
            }
        }
    }

    /**
     * Returns payments from transaction check
     *
     * @param array $response
     * @return array
     */
    private function getPayments(array $response): array
    {
        if ($this->checkRequest($response)) {
            return $response[self::PAYMENTS_NAMESPACE];
        }

        return [];
    }

    /**
     * Is transaction check request successful
     *
     * @param array $response
     * @return bool
     */
    private function checkRequest(array $response): bool
    {
        $status = $response[CommonHandler::RESULT_NAMESPACE][CommonHandler::RESULT_CODE] ?? null;
        if (!$status) {
            return false;
        }

        return in_array($status, SuccessCode::getSuccessfulTransactionCheckCodes());
    }

    /**
     * @param InfoInterface $payment
     * @param $namespaceData
     * @param $parentNamespace
     * @return void
     */
    private function processAdditionalInformation(
        InfoInterface $payment,
        $namespaceData,
        $parentNamespace = null
    ): void {
        foreach ($namespaceData as $namespace => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    continue;
                }

                $this->processAdditionalInformation(
                    $payment,
                    $value,
                    $parentNamespace
                        ? ($parentNamespace . '.' . $namespace)
                        : $namespace
                );
                continue;
            }

            $key = $namespace;
            if ($parentNamespace) {
                $key = $parentNamespace . '.' . $namespace;
            }
            $payment->setAdditionalInformation($key, (string)$value);
        }
    }

    /**
     * Convert payment token details to JSON
     *
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details): string
    {
        $json = $this->serializer->serialize($details);
        return $json ?: '{}';
    }

    /**
     * Format card expiration date
     *
     * @param array $cardDetails
     * @return string
     * @throws \Exception
     */
    private function getExpirationDate($cardDetails): string
    {
        $expDate = new \DateTime(
            ($cardDetails[CardDetailsHandler::CARD_EXP_YEAR] ?? '')
            . '-'
            . ($cardDetails[CardDetailsHandler::CARD_EXP_MONTH] ?? '')
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );

        $expDate->add(new \DateInterval('P1M'));

        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Get payment extension attributes
     *
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * Get vault payment token entity
     *
     * @param array $paymentData
     * @return PaymentTokenInterface|null
     * @throws \Exception
     */
    private function getVaultPaymentToken(array $paymentData)
    {
        $registrationId = $this->subjectReader->readResponse(
            $paymentData, VaultDetailsHandler::REGISTRATION_ID
            ) ?? '';

        if (empty($registrationId) || $this->isTokenExists($registrationId)) {
            return null;
        }

        $cardDetails = $this->subjectReader->readResponse(
            $paymentData, CardDetailsHandler::CARD_NAMESPACE
            ) ?? [];
        // If we have no 3d secure verification ID it is not 3d secure
        $data3DSecure = $this->subjectReader->readResponse(
            $paymentData,
            ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE
        );
        $is3DSecure = isset($data3DSecure[ThreeDSecureHandler::THREE_D_SECURE_VERIFICATION_ID]);

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        $paymentToken
            ->setGatewayToken($registrationId)
            ->setExpiresAt($this->getExpirationDate($cardDetails));

        $jsonDetails = $this->convertDetailsToJSON([
            'type' => $this->subjectReader->readResponse(
                $paymentData, PaymentDetailsHandler::BASIC_PAYMENT_BRAND
                ) ?? '',
            'maskedCC' => $cardDetails[CardDetailsHandler::CARD_LAST4_DIGITS],
            'expirationDate' => ($cardDetails[CardDetailsHandler::CARD_EXP_MONTH] ?? '')
                . "/" . ($cardDetails[CardDetailsHandler::CARD_EXP_YEAR] ?? ''),
            ThreeDSecureHandler::IS_THREE_D_SECURE => $is3DSecure
        ]);

        $this->subjectReader->debug("Json Details", ['Data' => $jsonDetails]);

        $paymentToken->setTokenDetails($jsonDetails);

        return $paymentToken;
    }

    /**
     * Handle transaction common details
     *
     * @param InfoInterface $payment
     * @param array $response
     * @return $this
     */
    private function handleCommonDetails(InfoInterface $payment, array $response): self
    {
        $responseFields = [
            CommonHandler::BUILD_NUMBER,
            CommonHandler::TIMESTAMP,
            CommonHandler::NDC
        ];
        foreach ($responseFields as $field) {
            $payment->setAdditionalInformation($field, $this->subjectReader->readResponse($response, $field));
        }
        $payment->setAdditionalInformation(CommonHandler::RESPONSE, $this->serializer->serialize($response));

        return $this;
    }

    /**
     * Handle transaction customer details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handlePaymentDetails(InfoInterface $payment, array $paymentData): self
    {
        $fields = [
            CommonHandler::ID,
            PaymentDetailsHandler::BASIC_PAYMENT_BRAND,
            PaymentDetailsHandler::BASIC_PAYMENT_TYPE,
            PaymentDetailsHandler::AMOUNT,
            PaymentDetailsHandler::CURRENCY,
            CommonHandler::DESCRIPTOR,
            PaymentDataBuilder::MERCHANT_TRANSACTION_ID
        ];
        foreach ($fields as $field) {
            $payment->setAdditionalInformation($field, $this->subjectReader->readResponse($paymentData, $field));
        }

        return $this;
    }

    /**
     * Handle transaction state details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleTransactionState(InfoInterface $payment, array $paymentData): self
    {
        $result = $this->subjectReader->readResponse($paymentData, CommonHandler::RESULT_NAMESPACE) ?? [];
        $this->processAdditionalInformation($payment, [CommonHandler::RESULT_NAMESPACE => $result]);

        return $this;
    }

    /**
     * Handle transaction result details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleResultDetails(InfoInterface $payment, array $paymentData): self
    {
        $resultDetails = $this->subjectReader->readResponse($paymentData, self::RESULT_DETAILS_NAMESPACE)
            ?? [];
        $this->processAdditionalInformation($payment, [self::RESULT_DETAILS_NAMESPACE => $resultDetails]);

        return $this;
    }

    /**
     * Handle transaction card details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleCardDetails(InfoInterface $payment, array $paymentData): self
    {
        $card = $this->subjectReader->readResponse($paymentData, CardDetailsHandler::CARD_NAMESPACE) ?? [];
        if ($card) {
            $payment->setCcLast4($card[CardDetailsHandler::CARD_LAST4_DIGITS]);
            $payment->setCcExpMonth($card[CardDetailsHandler::CARD_EXP_MONTH]);
            $payment->setCcExpYear($card[CardDetailsHandler::CARD_EXP_YEAR]);
            $payment->setCcType(
                $this->subjectReader->readResponse(
                    $paymentData,
                PaymentDetailsHandler::BASIC_PAYMENT_BRAND
                )
            );
            $payment->setCcOwner($card[CardDetailsHandler::CARD_HOLDER]);

            $this->processAdditionalInformation($payment, [CardDetailsHandler::CARD_NAMESPACE => $card]);
        }

        return $this;
    }

    /**
     * Handle transaction customer details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleCustomerDetails(InfoInterface $payment, array $paymentData): self
    {
        $customerDetails = $this->subjectReader->readResponse($paymentData, self::CUSTOMER_NAMESPACE) ?? [];
        $this->processAdditionalInformation($payment, [self::CUSTOMER_NAMESPACE => $customerDetails]);

        return $this;
    }

    /**
     * Handle transaction billing details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleBillingDetails(InfoInterface $payment, array $paymentData): self
    {
        $billingDetails = $this->subjectReader->readResponse($paymentData, self::BILLING_NAMESPACE) ?? [];
        $this->processAdditionalInformation($payment, [self::BILLING_NAMESPACE => $billingDetails]);

        return $this;
    }

    /**
     * Handle transaction custom details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleCustomDetails(InfoInterface $payment, array $paymentData): self
    {
        $customDetails = $this->subjectReader->readResponse(
            $paymentData,
            CustomParametersHandler::CUSTOM_PARAMETERS_NAMESPACE
        ) ?? [];
        $this->processAdditionalInformation(
            $payment,
            [CustomParametersHandler::CUSTOM_PARAMETERS_NAMESPACE => $customDetails]
        );

        return $this;
    }

    /**
     * Handle transaction risk details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleRiskDetails(InfoInterface $payment, array $paymentData): self
    {
        $riskData = $this->subjectReader->readResponse($paymentData, RiskDataHandler::RISK_NAMESPACE) ?? [];
        $this->processAdditionalInformation($payment, [RiskDataHandler::RISK_NAMESPACE => $riskData]);

        return $this;
    }

    /**
     * Handle transaction details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleTransactionDetails(InfoInterface $payment, array $paymentData): self
    {
        if (!$payment->getTransactionid()) {
            $transactionId = $this->subjectReader->readResponse(
                $paymentData,
                TransactionIdHandler::TRANSACTION_ID
            );
            $payment->setTransactionId($transactionId ?? '');
        }

        $payment->setIsTransactionClosed(false);
        $payment->setShouldCloseParentTransaction(false);

        return $this;
    }

    /**
     * Handle transaction 3D secure details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    private function handleThreeDSecureDetails(InfoInterface $payment, array $paymentData): self
    {
        $threeDSecureDetails = $this->subjectReader->readResponse(
            $paymentData,
            ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE
        ) ?? [];
        $this->processAdditionalInformation(
            $payment,
            [ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE => $threeDSecureDetails]
        );

        return $this;
    }

    /**
     * Handle vault details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     * @throws \Exception
     */
    private function handleVaultDetails(InfoInterface $payment, array $paymentData): self
    {
        $paymentToken = $this->getVaultPaymentToken($paymentData);
        if (null !== $paymentToken) {
            $additionalInformation = $payment->getAdditionalInformation();
            if (!array_key_exists(VaultConfigProvider::IS_ACTIVE_CODE, $additionalInformation)) {
                $additionalInformation[VaultConfigProvider::IS_ACTIVE_CODE] = true;
                $payment->setAdditionalInformation($additionalInformation);
            }
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }

        return $this;
    }

    /**
     * Handle transaction additional info
     *
     * @param InfoInterface $payment
     * @return $this
     * @throws \Exception
     */
    private function handleTransactionAdditionalInfo(InfoInterface $payment): self
    {
        $additionalInformation = $payment->getAdditionalInformation();
        if (empty($payment->getTransactionAdditionalInfo()) && !empty($additionalInformation)) {
            unset($additionalInformation[CommonHandler::RESPONSE]);
            // for backend transaction details grid in case if key 'raw_details_info' was specified
            // @see \Magento\Sales\Block\Adminhtml\Transactions\Detail\Grid::getTransactionAdditionalInfo()
            $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $additionalInformation);
            // for backend transaction details grid without specific key specified
            foreach ($additionalInformation as $field => $value) {
                $payment->setTransactionAdditionalInfo($field, $value);
            };
        }

        return $this;
    }

    /**
     * Returns whether registration id exists
     *
     * @param string $registrationId
     * @return bool
     */
    private function isTokenExists(string $registrationId): bool
    {
        return (bool) array_filter(
            $this->customerTokenManagement->getCustomerSessionTokens(),
            function ($token) use ($registrationId) {
                return $token->getGatewayToken() === $registrationId;
            }
        );
    }

    /**
     * Process transaction from transaction check response
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @param array $response
     * @return array
     * @throws \Exception
     */
    private function handleResponseData(InfoInterface $payment, array $paymentData, array $response): array
    {
        $this->handleCommonDetails($payment, $response)
            ->handlePaymentDetails($payment, $paymentData)
            ->handleTransactionState($payment, $paymentData)
            ->handleResultDetails($payment, $paymentData)
            ->handleCardDetails($payment, $paymentData)
            ->handleCustomerDetails($payment, $paymentData)
            ->handleBillingDetails($payment, $paymentData)
            ->handleCustomDetails($payment, $paymentData)
            ->handleRiskDetails($payment, $paymentData)
            ->handleThreeDSecureDetails($payment, $paymentData)
            ->handleTransactionDetails($payment, $paymentData)
            ->handleVaultDetails($payment, $paymentData)
            ->handleTransactionAdditionalInfo($payment);

        return $paymentData;
    }
}
