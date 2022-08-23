<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class RiskDataHandler
 */
class RiskDataHandler implements HandlerInterface
{
    const RISK_NAMESPACE = 'risk';

    const RISK_SCORE = 'score';

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

        $riskData = $this->subjectReader->readResponse($response, self::RISK_NAMESPACE);

        if ($riskData) {
            $payment->setAdditionalInformation(
                self::RISK_NAMESPACE . "_" . self::RISK_SCORE,
                $riskData[self::RISK_SCORE]
            );
            $this->subjectReader->debug("Risk Data: ", $riskData);
        } else {
            $this->subjectReader->debug("Risk Data is missing");
        }
    }
}
