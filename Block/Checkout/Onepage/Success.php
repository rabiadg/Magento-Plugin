<?php

namespace TotalProcessing\TPCARDS\Block\Checkout\Onepage;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Customer\Model\Session                  $customerSession
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }
    /**
     * Return success message if TP payment.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $customerId = $this->_customerSession->getCustomerId();
        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order) {
            return '';
        }
        if ($order->getId()) {
            if ($order->getPayment()->getMethodInstance()->getCode() == 'totalprocessing_tpcards') {
                $this->addData(
                    [
                        'is_totalprocessing' => true
                    ]
                );
                return parent::_toHtml();
            }
        }
        return '';
    }
}
