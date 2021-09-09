<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class ThreeDSecureHandler
 */
class ThreeDSecureHandler implements HandlerInterface
{
    const THREE_D_SECURE_NAMESPACE = 'threeDSecure';

    const THREE_D_SECURE_ECI = 'eci';
    const THREE_D_SECURE_VERIFICATION_ID = 'verificationId';
    const THREE_D_SECURE_XID = 'xid';
    const THREE_D_SECURE_ENROLLMENT_STATUS = 'enrollmentStatus';
    const THREE_D_SECURE_AUTHENTICATION_STATUS = 'authenticationStatus';
    const IS_THREE_D_SECURE = "isThreeDSecure";

     /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
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

        $threeDSecure = $this->subjectReader->readResponse($response, self::THREE_D_SECURE_NAMESPACE);

        if ($threeDSecure) {
            $payment->setAdditionalInformation(
                self::THREE_D_SECURE_NAMESPACE . "." . self::THREE_D_SECURE_ECI,
                $threeDSecure[self::THREE_D_SECURE_ECI] ?? null
            );
            $payment->setAdditionalInformation(
                self::THREE_D_SECURE_NAMESPACE . "." . self::THREE_D_SECURE_VERIFICATION_ID,
                $threeDSecure[self::THREE_D_SECURE_VERIFICATION_ID] ?? null
            );
            $payment->setAdditionalInformation(
                self::THREE_D_SECURE_NAMESPACE . "." . self::THREE_D_SECURE_XID,
                $threeDSecure[self::THREE_D_SECURE_XID] ?? null
            );
            $payment->setAdditionalInformation(
                self::THREE_D_SECURE_NAMESPACE . "." . self::THREE_D_SECURE_ENROLLMENT_STATUS,
                $threeDSecure[self::THREE_D_SECURE_ENROLLMENT_STATUS] ?? null
            );
            $payment->setAdditionalInformation(
                self::THREE_D_SECURE_NAMESPACE . "." . self::THREE_D_SECURE_AUTHENTICATION_STATUS,
                $threeDSecure[self::THREE_D_SECURE_AUTHENTICATION_STATUS] ?? null
            );
        }
    }
}
