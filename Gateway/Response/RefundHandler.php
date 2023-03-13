<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Sales\Model\Order\Payment;

/**
 * Class RefundHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class RefundHandler extends VoidHandler
{
    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $payment
     * @return bool
     */
    protected function shouldCloseParentTransaction(Payment $payment)
    {
        return !(bool) $payment->getCreditmemo()->getInvoice()->canRefund();
    }
}
