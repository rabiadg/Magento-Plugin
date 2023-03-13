<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class CustomerDataBuilder
 */
class CustomerDataBuilder implements BuilderInterface
{
    /**
     * Customer forename
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const GIVEN_NAME = 'customer.givenName';

    /**
     * Customer middle name
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const MIDDLE_NAME = 'customer.middleName';

    /**
     * Customer surname
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const SURNAME = 'customer.surname';

    /**
     * Customer phone
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const PHONE = 'customer.phone';

    /**
     * Customer email
     */
    const EMAIL = 'customer.email';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * CustomerDataBuilder Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject data", $buildSubject);
        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();

        $billingAddress = $order->getBillingAddress();

        $params = [
            self::GIVEN_NAME => $billingAddress->getFirstname(),
            self::SURNAME => $billingAddress->getLastname(),
            self::PHONE => $billingAddress->getTelephone(),
            self::EMAIL => $billingAddress->getEmail(),
        ];

        $this->subjectReader->debug("Result", $params);
        return array_filter($params, function ($param) {
            return $param !== null;
        });
    }
}
