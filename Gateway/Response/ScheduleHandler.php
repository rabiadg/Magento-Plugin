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
 * Class ScheduleHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class ScheduleHandler implements HandlerInterface
{
    const CLIENT_ID = 'clientId';
    const FREQUENCY = 'frequency';

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param Serializer $serializer
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Serializer $serializer,
        SubjectReader $subjectReader
    ) {
        $this->serializer = $serializer;
        $this->subjectReader = $subjectReader;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!$this->subjectReader->readSkipErrors($handlingSubject)) {
            $paymentDataObject = $this->subjectReader->readPayment($handlingSubject);

            $payment = $paymentDataObject->getPayment();
            ContextHelper::assertOrderPayment($payment);

            $payment->setAdditionalInformation(
                self::CLIENT_ID,
                $this->subjectReader->readResponse($response, self::CLIENT_ID)
            );

            $payment->setAdditionalInformation(
                $this->subjectReader->readResponse($response, self::FREQUENCY),
                $this->serializer->serialize($response)
            );
        }
    }
}
