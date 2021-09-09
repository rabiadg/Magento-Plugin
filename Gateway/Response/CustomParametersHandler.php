<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class CustomParametersHandler
 */
class CustomParametersHandler implements HandlerInterface
{
    const CUSTOM_PARAMETERS_NAMESPACE = 'customParameters';

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

        $customParameters = $this->subjectReader->readResponse($response, self::CUSTOM_PARAMETERS_NAMESPACE) ?? [];

        foreach ($customParameters as $name => $value) {
            if (is_scalar($value)) {
                $payment->setAdditionalInformation(self::CUSTOM_PARAMETERS_NAMESPACE . "_{$name}", $value);
            }
        }
    }
}
