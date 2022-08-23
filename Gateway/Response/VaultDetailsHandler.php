<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

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
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class VaultDetailsHandler
 */
class VaultDetailsHandler implements HandlerInterface
{
    const REGISTRATION_ID = 'registrationId';

    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var CustomerTokenManagement
     */
    private $customerTokenManagement;

    /**
     * Constructor
     *
     * @param Config                                $config
     * @param CustomerTokenManagement               $customerTokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param PaymentTokenFactoryInterface          $paymentTokenFactory
     * @param Serializer                            $serializer
     * @param SubjectReader                         $subjectReader
     */
    public function __construct(
        Config $config,
        CustomerTokenManagement $customerTokenManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Serializer $serializer,
        SubjectReader $subjectReader
    ) {
        $this->config = $config;
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
        $paymentDataObject = $this->subjectReader->readPayment($handlingSubject);

        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $paymentToken = $this->getVaultPaymentToken($response);
        if (null !== $paymentToken) {
            $additionalInformation = $payment->getAdditionalInformation();
            if (!array_key_exists(VaultConfigProvider::IS_ACTIVE_CODE, $additionalInformation)) {
                $additionalInformation[VaultConfigProvider::IS_ACTIVE_CODE] = true;
                $payment->setAdditionalInformation($additionalInformation);
            }
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity
     *
     * @param array $response
     * @return PaymentTokenInterface|null
     * @throws \Exception
     */
    protected function getVaultPaymentToken(array $response)
    {
        $registrationId = $this->subjectReader->readResponse($response, self::REGISTRATION_ID) ?? '';

        if (empty($registrationId) || $this->isTokenExists($registrationId)) {
            return null;
        }

        $cardDetails = $this->subjectReader->readResponse($response, CardDetailsHandler::CARD_NAMESPACE) ?? [];
        // If we have no 3d secure verification ID it is not 3d secure
        $data3DSecure = $this->subjectReader->readResponse($response, ThreeDSecureHandler::THREE_D_SECURE_NAMESPACE);
        $is3DSecure = isset($data3DSecure[ThreeDSecureHandler::THREE_D_SECURE_VERIFICATION_ID]);

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        $paymentToken
            ->setGatewayToken($registrationId)
            ->setExpiresAt($this->getExpirationDate($cardDetails));

        $jsonDetails = $this->convertDetailsToJSON([
            'type' => $this->subjectReader->readResponse($response, PaymentDetailsHandler::BASIC_PAYMENT_BRAND) ?? '',
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
     * Format card expiration date
     *
     * @param array $cardDetails
     * @return string
     * @throws \Exception
     */
    private function getExpirationDate(array $cardDetails)
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
     * Convert payment token details to JSON
     *
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
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
}
