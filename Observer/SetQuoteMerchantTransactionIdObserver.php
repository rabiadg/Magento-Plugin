<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace TotalProcessing\Opp\Observer;

use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


/**
 * Class SetQuoteMerchantTransactionIdObserver
 */
class SetQuoteMerchantTransactionIdObserver implements ObserverInterface
{
    /**
     * @var IdentityGeneratorInterface
     */
    protected $identityGenerator;

    /**
     * Constructor
     *
     * @param IdentityGeneratorInterface $identityGenerator
     */
    public function __construct(
        IdentityGeneratorInterface $identityGenerator
    ) {
        $this->identityGenerator = $identityGenerator;
    }

    /**
     * Set OPP Merchant Transaction ID
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var $quote \Magento\Quote\Model\Quote */
        $quote = $observer->getEvent()->getQuote();

        if (!$quote->getOppMerchantTransactionId()) {
            $quote->setOppMerchantTransactionId($this->identityGenerator->generateId());
        }
    }
}
