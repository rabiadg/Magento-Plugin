<?php

namespace TotalProcessing\TPCARDS\Controller\Cards\Result;

use TotalProcessing\TPCARDS\Block\Process;

class Base extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    private $_helper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry\Registry
     */
    private $coreRegistry;

    /**
     * @var \TotalProcessing\TPCARDS\Logger\Logger
     */
    private $_logger;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \TotalProcessing\TPCARDS\Helper\Data       $helper
     * @param \Magento\Framework\Registry           $coreRegistry
     * @param \TotalProcessing\TPCARDS\Logger\Logger     $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry,
        \TotalProcessing\TPCARDS\Logger\Logger $logger
    ) {
        $this->_helper = $helper;
        $this->_url = $context->getUrl();
        $this->coreRegistry = $coreRegistry;
        $this->_logger = $logger;

        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $response = $this->getRequest()->getParams();
            //the default
            $params['returnUrl'] = $this->_url->getUrl('/');

            if ($response) {
                $result = $this->_handleResponse($response);
                $params['returnUrl'] = $this->_url->getUrl('totalprocessing_tpcards/cards/success');
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $this->coreRegistry->register(Process\Result::REGISTRY_KEY, $params);

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function _handleResponse($response)
    {
        if (empty($response)) {
            $this->_logger->critical(__('Empty response received from gateway'));

            return false;
        }

        $this->_helper->logDebug(__('Gateway response:').print_r($this->_helper->stripTrimFields($response), true));

        // validate response
        $authStatus = $this->_validateResponse($response);
        if (!$authStatus) {
            $this->_logger->critical(__('Invalid response received from gateway.'));

            return false;
        }
        // happy with the response
        return true;
    }

    /**
     * Validate response using sha1 signature.
     *
     * @param array $response
     *
     * @return bool
     */
    private function _validateResponse($response)
    {
        return true;
    }
}
