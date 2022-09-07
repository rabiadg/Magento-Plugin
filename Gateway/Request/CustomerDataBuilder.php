<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Request\BuilderInterface;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class CustomerDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request
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
     * @var Session
     */
    private $checkoutSession;

    /**
     * CustomerDataBuilder Constructor
     *
     * @param SubjectReader $subjectReader
     * @param Session $checkoutSession
     */
    public function __construct(SubjectReader $subjectReader, Session $checkoutSession)
    {
        $this->subjectReader = $subjectReader;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("customer data", $buildSubject);

        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();

        $params = [
            self::GIVEN_NAME => $billingAddress->getFirstname(),
            self::SURNAME => $billingAddress->getLastname(),
            self::PHONE => $billingAddress->getTelephone(),
            self::EMAIL => $billingAddress->getEmail(),
            'billing.city' => $billingAddress->getCity(),
            'billing.country' => $billingAddress->getCountryId(),
            'billing.street1' => $billingAddress->getStreetLine(1),
            'billing.postcode' => $billingAddress->getPostCode()
        ];

        $this->subjectReader->debug("Result", $params);
        return array_filter($params, function ($param) {
            return $param !== null;
        });
    }
}
