<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class CommonHandler
 */
class CommonHandler implements HandlerInterface
{
    const BUILD_NUMBER = 'buildNumber';
    const ID = 'id';
    const NDC = 'ndc';
    const RESULT_NAMESPACE = 'result';
    const RESULT_CODE = 'code';
    const RESULT_DESCRIPTION = 'description';
    const TIMESTAMP = 'timestamp';
    const RESPONSE = 'response';

    private $serializer;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param Serializer $serializer
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader, Serializer $serializer)
    {
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

        $payment->setAdditionalInformation(
            self::RESPONSE,
            $this->serializer->serialize($response)
        );

        $payment->setAdditionalInformation(
            self::BUILD_NUMBER,
            $this->subjectReader->readResponse($response, self::BUILD_NUMBER)
        );
        $payment->setAdditionalInformation(
            self::ID,
            $this->subjectReader->readResponse($response, self::ID)
        );
        $payment->setAdditionalInformation(
            self::NDC,
            $this->subjectReader->readResponse($response, self::NDC)
        );
        $payment->setAdditionalInformation(
            self::TIMESTAMP,
            $this->subjectReader->readResponse($response, self::TIMESTAMP)
        );

        $result = $this->subjectReader->readResponse($response, self::RESULT_NAMESPACE);

        if ($result) {
            $payment->setAdditionalInformation(
                self::RESULT_NAMESPACE . "_" . self::RESULT_CODE,
                $result[self::RESULT_CODE]
            );
            $payment->setAdditionalInformation(
                self::RESULT_NAMESPACE . "_" . self::RESULT_DESCRIPTION,
                $result[self::RESULT_DESCRIPTION]
            );
        }
    }
}
