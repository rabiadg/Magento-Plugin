<?php

namespace TotalProcessing\TPCARDS\Controller\Process\Result;

class Base extends \Magento\Framework\App\Action\Action
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
     * @var \TotalProcessing\TPCARDS\API\TotalProcessingManagementInterface
     */
    private $_paymentManagement;

    /**
     * Result constructor.
     *
     * @param \Magento\Framework\App\Action\Context                    $context
     * @param \TotalProcessing\TPCARDS\Helper\Data                          $helper
     * @param \Magento\Sales\Model\OrderFactory                        $orderFactory
     * @param \Magento\Framework\Registry                              $coreRegistry
     * @param \TotalProcessing\TPCARDS\Logger\Logger                        $logger
     * @param \TotalProcessing\TPCARDS\API\TotalProcessingManagementInterface $paymentManagement
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $coreRegistry,
        \TotalProcessing\TPCARDS\Logger\Logger $logger,
        \TotalProcessing\TPCARDS\API\TotalProcessingManagementInterface $paymentManagement
    ) {
        $this->_helper = $helper;
        $this->_orderFactory = $orderFactory;
        $this->_url = $context->getUrl();
        $this->coreRegistry = $coreRegistry;
        $this->_logger = $logger;
        $this->_paymentManagement = $paymentManagement;
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
            $params['returnUrl'] = $this->_url->getUrl('checkout/cart');

            if ($response) {
                $result = $this->_handleResponse($response);
                
                $sessionRes = false;
                
                if(is_array($result)){
                    if(isset($result['result'])){
                        if($result['result'] == true){
                            $sessionRes = true;
                        }
                    }
                    if(isset($result['code'])){
                        $params['code'] = $result['code'];
                    }
                    if(isset($result['description'])){
                        $params['description'] = $result['description'];
                    }
                } else if($result === true){
                    $sessionRes = true;
                }
                
                $params['result'] = $sessionRes;
                
                $params['returnUrl'] = $this->_url
                  ->getUrl('totalprocessing_tpcards/process/sessionresult', $this->_buildSessionParams($sessionRes));
                  
                $this->_helper->logDebug(__('totalprocessing_tpcards/process/sessionresult:').print_r($params, true));
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $this->coreRegistry->register(\TotalProcessing\TPCARDS\Block\Process\Result::REGISTRY_KEY, $params);

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
        
        $platformBase = $this->_helper->getTpPlatformBase($response['environment']);
        $headers = $this->_helper->getTpHeaderAuth($response['environment']);
        $entityId = $this->_helper->getTpEntityId($response['environment']);

        $curlResource = $platformBase . $response['resourcePath'] . '?entityId=' . $entityId;
        //here is where we do the GET on resourcePath
        
        $responseData = $this->lookupTpTransactionViaResPath($curlResource,$headers);
        
        $incrementId = false;
        
        if(isset($responseData['merchantTransactionId'])){
            $incrementId = $responseData['merchantTransactionId'];
        }

        if ($incrementId) {
            
            $this->_helper->logDebug(__('incrementId OK => ') . $incrementId );
            
            $order = $this->_getOrder($incrementId);
            if ($order->getId()) {
                // process the response
                
                $this->_helper->logDebug(__('running: this->_paymentManagement->processResponse'));
                
                return $this->_paymentManagement->processResponse($order, $responseData);
                
            } else {
                $this->_logger->critical(__('Gateway response has an invalid order id.'));

                return false;
            }
        } else {
            $this->_logger->critical(__('Gateway response does not have an order id.'));

            return false;
        }
    }
    
    public function lookupTpTransactionViaResPath($curlResource,$headers) {
        
        $this->_helper->logDebug(__('curlResource:') . ' => ' . $curlResource );
        
        $this->_helper->logDebug(__('headers:').print_r($this->_helper->stripTrimFields($headers), true));
    	
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $curlResource);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	
    	$responseJson = curl_exec($ch);
    	
    	if(curl_errno($ch)) {
    	    
    	    $this->_logger->critical(__('Gateway response curl error.'));
    	    
    		return false;
    	}
    	
    	curl_close($ch);
    	
    	$responseData = json_decode($responseJson,true);
    	
    	if(is_array($responseData)){
    	    
    	    $this->_helper->logDebug(__('lookupTpTransactionViaResPath response:').print_r($this->_helper->stripTrimFields($responseData), true));
    	    
            return $responseData;
            
    	}
    	
    	$this->_logger->critical(__('responseData is not an array'));
    	$this->_logger->critical(__( $responseJson ));
    	
    	return false;
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
        if(!isset($response['resourcePath'])){
            return false;
        }
        if(!isset($response['environment'])){
            return false;
        }
        //$this->getConfigData('environment') 
        return true;
    }

    /**
     * Build params for the session redirect.
     *
     * @param bool $result
     *
     * @return array
     */
    private function _buildSessionParams($result)
    {
        $result = ($result) ? '1' : '0';
        // if no order id exists
        if(!$this->_order) {
            return false;
        }
        else {
            $orderid = $this->_order->getIncrementId();
        }

        return ['order_id' => $orderid, 'result' => $result];
    }

    /**
     * Get order based on increment id.
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
