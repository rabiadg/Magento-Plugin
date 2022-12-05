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
 * Class CardDataBuilder
 * @package TotalProcessing\Opp\Gateway\Request\ApplePay
 */
class CardDataBuilder extends AbstractDataBuilder
{
    /**
     * Card holder name
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const CARD_HOLDER = 'card.holder';

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
        $this->subjectReader->debug("Card buildSubject Data", $buildSubject);

        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();

        $result = [
            self::CARD_HOLDER => $billingAddress->getName()
        ];

        $this->subjectReader->debug("Card Request Data", $result);

        return $result;
    }
}
