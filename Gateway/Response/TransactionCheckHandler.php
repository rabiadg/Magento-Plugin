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
use Magento\Sales\Api\Data\OrderPaymentInterface;
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

/**
 * Class TransactionCheckHandler
 */
class TransactionCheckHandler implements HandlerInterface
{
    const IS_PRE_AUTHORIZED = "is_pre_authorized";
    const IS_CAPTURED = "is_captured";
    const CAPTURE_DATA = "capture";
    const PAYMENTS = 'payments';

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
     * Constructor
     *
     * @param CheckoutSession $checkoutSession
     * @param CustomerTokenManagement $customerTokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Serializer $serializer
     * @param SubjectReader $subjectReader
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

            $preAuthorizeTransaction = false;
            $captureTransaction = false;

            foreach ($payments as $paymentData) {
                $paymentType = $paymentData[PaymentDetailsHandler::BASIC_PAYMENT_TYPE] ?? '';

                if ($preAuthorizeTransaction && $captureTransaction) {
                    break;
                }

                if ($paymentType == PaymentType::PRE_AUTHORIZATION && !$preAuthorizeTransaction) {
                    $preAuthorizeTransaction = $paymentData;
                } elseif ($paymentType == PaymentType::CAPTURE && !$captureTransaction) {
                    $captureTransaction = $paymentData;
                }
            }

            if ($captureTransaction && !$preAuthorizeTransaction) {
                $preAuthorizeTransaction = $captureTransaction;
            }

            if ($preAuthorizeTransaction) {
                $preAuthorizeTransaction = $this->processPreAuthorizeTransaction($payment, $preAuthorizeTransaction);
            }

            $payment->setAdditionalInformation(TransactionCheckHandler::IS_PRE_AUTHORIZED, $preAuthorizeTransaction);
            $payment->setAdditionalInformation(TransactionCheckHandler::IS_CAPTURED, $captureTransaction);
            $payment->setAdditionalInformation(
                PaymentDataBuilder::MERCHANT_TRANSACTION_ID,
                $quote->getOppMerchantTransactionId()
            );
        }
    }

    /**
     * Convert payment token details to JSON
     *
     * @param array $details
     * @return string
     */
    protected function convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
    }

    /**
     * Format card expiration date
     *
     * @param array $cardDetails
     * @return string
     * @throws \Exception
     */
    protected function getExpirationDate($cardDetails)
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
    protected function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
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
            return $response[self::PAYMENTS];
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
     * Get vault payment token entity
     *
     * @param array $paymentData
     * @return PaymentTokenInterface|null
     * @throws \Exception
     */
    protected function getVaultPaymentToken(array $paymentData)
    {
        $registrationId = $this->subjectReader->readResponse($paymentData, VaultDetailsHandler::REGISTRATION_ID) ?? '';

        if (empty($registrationId) || $this->isTokenExists($registrationId)) {
            return null;
        }

        $cardDetails = $this->subjectReader->readResponse($paymentData, CardDetailsHandler::CARD_NAMESPACE) ?? [];
        // If have no 3d secure verification ID it is not 3d secure

        $data3DSecure = $this->subjectReader->readResponse($paymentData, ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE);
        $is3DSecure = isset($data3DSecure[ThreeDSecureHandler::THREE_D_SECURE_VERIFICATION_ID]);

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        $paymentToken
            ->setGatewayToken($registrationId)
            ->setExpiresAt($this->getExpirationDate($cardDetails));

        $jsonDetails = $this->convertDetailsToJSON([
            'type' => $this->subjectReader->readResponse($paymentData, PaymentDetailsHandler::BASIC_PAYMENT_BRAND) ?? '',
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
     * Handle PA transaction card details
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    protected function handleCardDetails(InfoInterface $payment, array $paymentData): self
    {
        $card = $this->subjectReader->readResponse($paymentData, CardDetailsHandler::CARD_NAMESPACE);

        if ($card) {
            $paymentBrand = $this->subjectReader->readResponse(
                $paymentData,
                PaymentDetailsHandler::BASIC_PAYMENT_BRAND
            );

            $payment->setCcLast4($card[CardDetailsHandler::CARD_LAST4_DIGITS]);
            $payment->setCcExpMonth($card[CardDetailsHandler::CARD_EXP_MONTH]);
            $payment->setCcExpYear($card[CardDetailsHandler::CARD_EXP_YEAR]);
            $payment->setCcType($paymentBrand);

            $payment->setAdditionalInformation(
                CardDetailsHandler::CARD_NUMBER,
                'xxxx-' . $card[CardDetailsHandler::CARD_LAST4_DIGITS]
            );

            $payment->setAdditionalInformation(OrderPaymentInterface::CC_TYPE, $paymentBrand);
        }

        return $this;
    }

    /**
     * Handle PA transaction common payment data
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    protected function handleCommonData(InfoInterface $payment, array $paymentData): self
    {
        $payment->setAdditionalInformation(
            CommonHandler::ID,
            $this->subjectReader->readResponse($paymentData, CommonHandler::ID)
        );

        $payment->setAdditionalInformation(
            CommonHandler::TIMESTAMP,
            $this->subjectReader->readResponse($paymentData, CommonHandler::TIMESTAMP)
        );

        $payment->setAdditionalInformation(PaymentDetailsHandler::BASIC_PAYMENT_TYPE, PaymentType::PRE_AUTHORIZATION);

        $payment->setAdditionalInformation(
            PaymentDetailsHandler::BASIC_PAYMENT_BRAND,
            $this->subjectReader->readResponse($paymentData, PaymentDetailsHandler::BASIC_PAYMENT_BRAND)
        );

        return $this;
    }

    /**
     * Handle PA transaction custom parameters
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    protected function handleCustomParameters(InfoInterface $payment, array $paymentData): self
    {
        $customParameters = $this->subjectReader->readResponse(
            $paymentData,
            CustomParametersHandler::CUSTOM_PARAMETERS_NAMESPACE
        ) ?? [];

        foreach ($customParameters as $name => $value) {
            if (is_scalar($value)) {
                $payment->setAdditionalInformation(
                    CustomParametersHandler::CUSTOM_PARAMETERS_NAMESPACE . "_{$name}",
                    $value
                );
            }
        }

        return $this;
    }

    /**
     * Handle PA transaction risk data
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    protected function handleRiskData(InfoInterface $payment, array $paymentData): self
    {
        $riskData = $this->subjectReader->readResponse($paymentData, RiskDataHandler::RISK_NAMESPACE);

        if ($riskData) {
            $payment->setAdditionalInformation(
                RiskDataHandler::RISK_NAMESPACE . "_" . RiskDataHandler::RISK_SCORE,
                $riskData[RiskDataHandler::RISK_SCORE]
            );

//            if ($riskData[self::RISK_SCORE] < 0) {
//                $payment->setIsFraudDetected(true);
//            }

            $this->subjectReader->debug("Risk Data: ", $riskData);
        } else {
            $this->subjectReader->debug("Risk Data is missing");
        }

        return $this;
    }

    /**
     * Handle PA transaction
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    protected function handleTransaction(InfoInterface $payment, array $paymentData): self
    {
        if (!$payment->getTransactionid()) {
            $transactionId = $this->subjectReader->readResponse($paymentData, TransactionIdHandler::TRANSACTION_ID);
            $payment->setTransactionId($transactionId ?? '');
        }

        $payment->setIsTransactionClosed(false);
        $payment->setShouldCloseParentTransaction(false);

        return $this;
    }

    /**
     * Handle PA 3D secure data
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     */
    protected function handleThreeDSecure(InfoInterface $payment, array $paymentData): self
    {
        $threeDSecure = $this->subjectReader->readResponse($paymentData, ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE);

        if ($threeDSecure) {
            $payment->setAdditionalInformation(
                ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE . "." . ThreeDSecureHandler::THREE_D_SECURE_ECI,
                $threeDSecure[ThreeDSecureHandler::THREE_D_SECURE_ECI] ?? null
            );
            $payment->setAdditionalInformation(
                ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE . "." . ThreeDSecureHandler::THREE_D_SECURE_VERIFICATION_ID,
                $threeDSecure[ThreeDSecureHandler::THREE_D_SECURE_VERIFICATION_ID] ?? null
            );
            $payment->setAdditionalInformation(
                ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE . "." . ThreeDSecureHandler::THREE_D_SECURE_XID,
                $threeDSecure[ThreeDSecureHandler::THREE_D_SECURE_XID] ?? null
            );
            $payment->setAdditionalInformation(
                ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE . "." . ThreeDSecureHandler::THREE_D_SECURE_ENROLLMENT_STATUS,
                $threeDSecure[ThreeDSecureHandler::THREE_D_SECURE_ENROLLMENT_STATUS] ?? null
            );
            $payment->setAdditionalInformation(
                ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE . "." . ThreeDSecureHandler::THREE_D_SECURE_AUTHENTICATION_STATUS,
                $threeDSecure[ThreeDSecureHandler::THREE_D_SECURE_AUTHENTICATION_STATUS] ?? null
            );
        }

        return $this;
    }

    /**
     * Handle PA vault data
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return $this
     * @throws \Exception
     */
    protected function handleVault(InfoInterface $payment, array $paymentData): self
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
     * Returns whether registration id exists
     *
     * @param string $registrationId
     * @return bool
     */
    protected function isTokenExists(string $registrationId): bool
    {
        return (bool) array_filter(
            $this->customerTokenManagement->getCustomerSessionTokens(),
            function ($token) use ($registrationId) {
                return $token->getGatewayToken() === $registrationId;
            }
        );
    }

    /**
     * Process PA transaction from transaction check response
     *
     * @param InfoInterface $payment
     * @param array $paymentData
     * @return array
     * @throws \Exception
     */
    protected function processPreAuthorizeTransaction(InfoInterface $payment, array $paymentData): array
    {
        $this
            ->handleCardDetails($payment, $paymentData)
            ->handleCommonData($payment, $paymentData)
            ->handleCustomParameters($payment, $paymentData)
            ->handleRiskData($payment, $paymentData)
            ->handleThreeDSecure($payment, $paymentData)
            ->handleTransaction($payment, $paymentData)
            ->handleVault($payment, $paymentData);

        return $paymentData;
    }
}
