<?php

namespace TotalProcessing\TPCARDS\Model\Order;

use Magento\Sales\Model\Order\Payment as parentPayment;

class Payment extends parentPayment
{
    /**
     * @return $this
     */
    public function hold()
    {
        /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
        $method = $this->getMethodInstance();
        if ($this->getData('method') == 'totalprocessing_tpcards') {
            $method->hold($this);
        }
        return $this;

    }

    /**
     * @return $this
     */
    public function release()
    {
        /** @var \Magento\Payment\Model\Method\AbstractMethod $method */
        $method = $this->getMethodInstance();
        if ($this->getData('method') == 'totalprocessing_tpcards') {
            $method->acceptPayment($this);
        }
        return $this;

    }


}