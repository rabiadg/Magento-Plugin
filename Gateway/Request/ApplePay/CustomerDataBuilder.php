<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use TotalProcessing\Opp\Gateway\Config\ApplePay\Config;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class DebitAuthDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request\ApplePay
 */
class CustomerDataBuilder extends AbstractDataBuilder
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
     * Customer city
     */
    const CITY = 'billing.city';

    /**
     * Customer country
     */
    const COUNTRY = 'billing.country';

    /**
     * Customer street 1
     */
    const STREET1 = 'billing.street1';

    /**
     * Customer street 2
     */
    const STREET2 = 'billing.street2';

    /**
     * Customer postcode
     */
    const POSTCODE = 'billing.postcode';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        CheckoutSession $checkoutSession,
        SubjectReader $subjectReader
    ) {
        parent::__construct($config, $subjectReader);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("Customer buildSubject Data", $buildSubject);

        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();

        $result = [
            self::GIVEN_NAME => $billingAddress->getFirstname(),
            self::SURNAME => $billingAddress->getLastname(),
            self::PHONE => $billingAddress->getTelephone(),
            self::EMAIL => $billingAddress->getEmail(),
            self::CITY => $billingAddress->getCity(),
            self::COUNTRY => $billingAddress->getCountryId(),
            self::STREET1 => $billingAddress->getStreetLine(1),
            self::POSTCODE => $billingAddress->getPostCode()
        ];

        $this->subjectReader->debug("Customer Request Data", $result);

        return $result;
    }
}
