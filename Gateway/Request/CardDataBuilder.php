<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class CardDataBuilder
 */
class CardDataBuilder implements BuilderInterface
{
    /**
     * The card security code or CVV
     * <br/>
     * <strong>CONDITIONAL</strong>
     */
    const CARD_CVV = 'card.cvv';

    /**
     * The expiry month of the card
     * <br/>
     * <strong>REQUIRED</strong>
     */
    const CARD_EXPIRY_MONTH = 'card.expiryMonth';

    /**
     * The expiry year of the card
     * <br/>
     * <strong>REQUIRED</strong>
     */
    const CARD_EXPIRY_YEAR = 'card.expiryYear';

    /**
     * Card holder name
     * <br/>
     * <strong>OPTIONAL</strong>
     */
    const CARD_HOLDER = 'card.holder';

    /**
     * The PAN or account number of the card
     * <br/>
     * <strong>REQUIRED</strong>
     */
    const CARD_NUMBER = 'card.number';

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject): array
    {
        $this->subjectReader->debug("buildSubject data", $buildSubject);

        $params = [];

        $this->subjectReader->debug("Result", $params);

        return $params;
    }
}
