<?php

namespace TotalProcessing\TPCARDS\Controller\Process;

class SessionResult extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    private $_helper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \TotalProcessing\TPCARDS\Logger\Logger
     */
    private $_logger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \TotalProcessing\TPCARDS\Helper\Data       $helper
     * @param \Magento\Sales\Model\OrderFactory     $orderFactory
     * @param \TotalProcessing\TPCARDS\Logger\Logger     $logger
     * @param \Magento\Checkout\Model\Session       $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \TotalProcessing\TPCARDS\Logger\Logger $logger,
        \Magento\Checkout\Model\Session $session
    ) {
        $this->_helper = $helper;
        $this->_orderFactory = $orderFactory;
        $this->_url = $context->getUrl();
        $this->_logger = $logger;
        $this->_session = $session;
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $response = $this->getRequest()->getParams();
        
        $this->_helper->logDebug(__('/Controller/Process/SessionResult =>').print_r($response, true));
        
        
        if (!$this->_validateResponse($response)) {
            $this->messageManager->addError(
                __('Your payment was unsuccessful. Please try again or use a different card / payment method.'),
                'totalprocessing_messages'
            );
            $this->_redirect('checkout/cart');

            return;
        }
        $result = boolval($response['result']);
        if ($result) {
            $this->_session->getQuote()
                  ->setIsActive(false)
                  ->save();
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->_cancel();
            $this->_session->setData(\TotalProcessing\TPCARDS\Block\Process\Result\Observe::OBSERVE_KEY, '1');
            $this->messageManager->addError(
                __('Your payment was unsuccessful. Please try again or use a different card / payment method.'),
                'totalprocessing_messages'
            );
            $this->_redirect('checkout/cart');
        }
    }

    private function _validateResponse($response)
    {
        if((int)$response['result'] !== 1){
            return false;
        }
        $orderid = $response['order_id'];
        $order = $this->_getOrder($orderid);
        return $order->getId();
    }

    /**
     * Cancel the order and restore the quote.
     */
    private function _cancel()
    {
        // restore the quote
        $this->_session->restoreQuote();
        $this->_helper->cancelOrder($this->_order);
    }

    /**
     * Get order based on increment_id.
     *
     * @param $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    private function _getOrder($incrementId)
    {
        if (!$this->_order) {
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }
        return $this->_order;
    }
}
