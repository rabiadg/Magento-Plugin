<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Response;

use Magento\Sales\Model\Order\Payment;

/**
 * Class VoidHandler
 * @package TotalProcessing\Opp\Gateway\Response
 */
class VoidHandler extends TransactionIdHandler
{
    /**
     * @param Payment $payment
     * @param array $response
     * @return void
     */
    protected function setTransactionId(Payment $payment, array $response)
    {
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return true;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $payment
     * @return bool
     */
    protected function shouldCloseParentTransaction(Payment $payment)
    {
        return true;
    }
}
