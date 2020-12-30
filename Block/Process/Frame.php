<?php

namespace TotalProcessing\TPCARDS\Block\Process;

use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;

class Frame extends \Magento\Payment\Block\Form
{
    protected $_checkoutSession;
    protected $_customerSession;
    private $_helper;
    private $_sqlConnectOne;
    protected $_request;
    private $_quoteManagement;
    private $_order;
    private $_transactionBuilder;
    private $_orderSender;
    private $_orderHistoryFactory;
    private $_orderFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \TotalProcessing\TPCARDS\Model\API\SQLDirectConnectSingleId $sqlConnectOne,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_helper = $helper;
        $this->_sqlConnectOne = $sqlConnectOne;
        $this->_request = $request;
        $this->_quoteManagement = $quoteManagement;
        $this->_order = $order;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_orderSender = $orderSender;
        $this->_orderHistoryFactory = $orderHistoryFactory;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    private function genCredentialsArray()
    {
        $array = [];
        if($this->_helper->getConfigData('environment') === 'sandbox'){
            $array['platformBase'] = 'https://test.oppwa.com';
            $array['entityId'] = trim($this->_helper->getConfigData('entityId_test'));
            $array['accessToken'] = trim($this->_helper->getConfigData('accessToken_test'));
        } else {
            $array['platformBase'] = 'https://oppwa.com';
            $array['entityId'] = trim($this->_helper->getConfigData('entityId'));
            $array['accessToken'] = trim($this->_helper->getConfigData('accessToken'));
        }
        foreach($array as $k => $v){
            if(empty($v)){
                unset($array[$k]);
            }
        }
        if( isset($array['platformBase']) === true && isset($array['entityId']) === true && isset($array['accessToken']) === true ){
            return $array;
        }
        return false;
    }

    /**
     * @return array
     */
    private function genReservedOrderId()
    {
        $this->_checkoutSession->getQuote()->reserveOrderId();
        $reservedOrderId = $this->_checkoutSession->getQuote()->getReservedOrderId();
        return $reservedOrderId;
    }

    /**
     * @return array
     */
    public function getUrlReqData()
    {
        $paramData = $this->_request->getParams();
        return $paramData;
    }

    public function fetchRetAllTpCardsSessionData($altData = false){
        
        $storeData = [
            'orderId' => $altData
        ];

        $this->_helper->logDebug( 'storeData0: ' . json_encode($storeData) );

        $storeData['uuid'] = $this->_getCustomerSession()->getTpCardsUuid();
        $this->_helper->logDebug( 'uuid: ' . $storeData['uuid'] );

        $storeData['amount'] = $this->_getCustomerSession()->getTpCardsAmount();
        $this->_helper->logDebug( 'amount: ' . $storeData['amount'] );

        $responseData = $this->_getCustomerSession()->getTpCardsResponseData();
        $this->_helper->logDebug( 'responseData: ' . $responseData );

        $storeData['responseData'] = json_decode($responseData);

        $this->_helper->logDebug( 'storeDataFinal: ' . json_encode($storeData) );

        return $storeData;
    }

    public function processTpCardsOrder($storeData){
        $this->_helper->logDebug( 'triggered: processTpCardsOrder()' );
        //unset session vars...
        //$this->_getCustomerSession()->unsTpCardsUuid();
        //$this->_getCustomerSession()->unsTpCardsAmount();
        //$this->_getCustomerSession()->unsTpCardsResponseData();
        //$this->_helper->logDebug( 'session vars: cleared()' );
        $retArr = $this->placeTpOrderPayment($storeData['orderId'],$storeData['responseData'],$storeData['uuid'],$storeData['amount']);
        $this->_helper->logDebug( 'placeTpOrderPayment: ' . json_encode($retArr) );
        if(is_array($retArr)){
            if(isset($retArr['status'])){
                if($retArr['status'] === true){
                    return $retArr;
                }
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getResultCheckoutDataBlock( $resourcePath ){

        $retArr = [
            'status' => false,
            'code' => '',
            'description' => 'An unknown error occured, please try again.'
        ];

        if($this->_helper->getConfigData('environment') === 'sandbox'){
            $success = '000.100.110';
        } else {
            $success = '000.000.000';
        }

        $credentialsArray = $this->genCredentialsArray();
        if(!$credentialsArray){
            $retArr['description'] = 'Access denied to payment '.$this->_helper->getConfigData('environment').' gateway.';
            return $retArr;
        }

        $curlResource = $credentialsArray['platformBase'];
        $curlResource .= $resourcePath;
        $curlResource .= "?entityId=";
        $curlResource .= $credentialsArray['entityId'];

        $responseData = $this->requestTpTransactionViaModelV2Block('GET',$curlResource,$credentialsArray['accessToken'],[],true);

        if(is_object($responseData)){
            if(isset($responseData->result->code)){
                $retArr['code'] = $responseData->result->code;
                $retArr['description'] = str_replace("'","",$responseData->result->description);
                if($responseData->result->code === $success){
                    $chkData = $this->verifyTransactionStructure( $responseData );
                    //$retArr['chkData'] = $chkData;
                    $dataIntegrity = true;
                    foreach($chkData as $k => $v){
                        if($v === false){
                            $retArr['description'] = $k . ' error, please contact customer services';
                            $dataIntegrity = false;
                            break;
                        }
                    }
                    if($dataIntegrity !== true){
                        //reverse PA
                        $reverseResponse = $this->processBackOfficeTransaction( $credentialsArray, $chkData , 'RV' , true );
                        //$retArr['reverseResponse'] = $reverseResponse;
                        //end reverse
                        return $retArr;
                    }
                    $sqlQuoteJson = $this->_sqlConnectOne->fetchQuoteRowData( $chkData['quote_id'] );
                    //$retArr['sqlQuoteJson'] = $sqlQuoteJson;
                    if($sqlQuoteJson[0] === '{'){
                        $sqlQuoteData = json_decode($sqlQuoteJson,true);
                        if(is_array($sqlQuoteData)){
                            if(count($sqlQuoteData) >= 5){
                                //$retArr['sqlQuoteData'] = $sqlQuoteData;
                                $compareTxQuote = $this->responseQuoteMatcher( $chkData , $sqlQuoteData );
                                if($compareTxQuote !== true){
                                    $retArr['description'] = 'Transaction data:order mismatch error, please contact customer services';
                                    //$retArr['matchingProcess'] = false;
                                    //reverse PA
                                    $reverseResponse = $this->processBackOfficeTransaction( $credentialsArray, $chkData , 'RV' , true );
                                    //$retArr['reverseResponse'] = $reverseResponse;
                                    //end reverse
                                    return $retArr;
                                }
                                //$retArr['matchingProcess'] = true;

                                $captureResponse = $this->processBackOfficeTransaction( $credentialsArray, $chkData , 'CP' , true );
                                
                                $retArr['captureResponse'] = $captureResponse;
                                
                                if(is_object($captureResponse)){
                                    if(isset($captureResponse->result->code)){
                                        if($captureResponse->result->code === $success){
                                            $amount = number_format( $chkData['grand_total'], 2, '.', '');
                                            $uuid = (string)$captureResponse->id;

                                            //store all req data in session
                                            $this->_getCustomerSession()->setTpCardsUuid($uuid);
                                            $this->_getCustomerSession()->setTpCardsAmount($amount);
                                            $this->_getCustomerSession()->setTpCardsResponseData(json_encode($responseData));
                                            
                                            //place order via js
                                            $retArr['redirect'] = false;
                                            $retArr['status'] = true;
                                        } else {
                                            $retArr['code'] = $captureResponse->result->code;
                                            $retArr['description'] = str_replace("'","",$captureResponse->result->description);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $retArr;
    }

    public function reportingEndPointLookupId($env,$id){

        $credentialsArray = [];

        if($env === 'test.oppwa.com'){
            $credentialsArray['platformBase'] = 'https://test.oppwa.com';
            $credentialsArray['entityId'] = trim($this->_helper->getConfigData('entityId_test'));
            $credentialsArray['accessToken'] = trim($this->_helper->getConfigData('accessToken_test'));
        } else {
            $credentialsArray['platformBase'] = 'https://oppwa.com';
            $credentialsArray['entityId'] = trim($this->_helper->getConfigData('entityId'));
            $credentialsArray['accessToken'] = trim($this->_helper->getConfigData('accessToken'));
        }
        
        $url = $credentialsArray['platformBase'] . "/v1/query/" . $id;
        $url .= "?entityId=" . $credentialsArray['entityId'];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization:Bearer ' . $credentialsArray['accessToken']]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        curl_close($ch);
        
        $responseObject = json_decode($responseData);

        if(is_object($responseObject)){
            if(isset($responseObject->result->code)){
                if($env === 'test.oppwa.com'){
                    $success = '000.100.110';
                } else {
                    $success = '000.000.000';
                }
                if((string)$responseObject->result->code === $success){
                    $orderId = (int)$responseObject->customParameters->SHOPPER_order_id;
                    $amount = (float)$responseObject->amount;
                    if((string)$responseObject->paymentType === 'PA'){
                        //capture
                        //prepare $chkData
                        $chkData = [
                            'uuid' => $id,
                            'grand_total' => $amount,
                            'currency' => $responseObject->currency
                        ];
                        $captureResponse = $this->processBackOfficeTransaction( $credentialsArray, $chkData , 'CP' , true );
                        $uuid = $captureResponse->id;
                    } else {
                        $uuid = $id;
                    }
                    //finalise order
                    $final = $this->placeTpOrderPayment($orderId,$responseObject,$uuid,$amount);
                    return $final;
                }
            }
        }
        return false;
    }

    private function placeTpOrderPayment($orderId,$responseData,$uuid,$amount){

        $retArr = ['orderId' => $orderId, 'status' => false];

        if($orderId === false){
            $incrementId = (string)$responseData->merchantTransactionId;

            $this->_helper->logDebug( 'incrementId!! => ' . $incrementId );

            $order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
            $orderId = $order->getId();
            $this->_helper->logDebug( 'done using new!!: orderId => ' . $orderId );
            //return $retArr;
        }

        $this->_helper->logDebug( 'placeOrder: orderId => ' . $orderId );

        $order = $this->_order->load($orderId);
        $increment_id = $order->getRealOrderId();
        $retArr['increment_id'] = $increment_id;

        //check increment_id match merchantTransactionId
        if((string)$responseData->merchantTransactionId !== (string)$increment_id){
            return $retArr;
        }

        $payment = $order->getPayment();
        $payment->setTransactionId($uuid);
        $orginalResponseArray = json_decode(json_encode($responseData),true);
        $this->_helper->setAdditionalInfo($payment, $orginalResponseArray);
        $this->_addTransaction($payment, $order, $uuid, $orginalResponseArray);
        $payment->getMethodInstance()->setIsInitializeNeeded(false);                                            
        $payment->place();

        $this->_invoice($order, $uuid, $amount);
        $order->save();

        if (!$order->getEmailSent()) {
            $this->_orderSender->send($order);
        }
        
        $clientId = $this->parseOrderForTcSchedules($order,$orginalResponseArray);

        $redirectUrl = $this->getUrl('checkout', ['_secure' => true]);
        $redirectUrl .= "onepage/success";
        $retArr['redirect'] = $redirectUrl;
        $retArr['status'] = true;

        return $retArr;
    }

    private function parseOrderForTcSchedules($order,$orginalResponseArray) {
        
        $clientId = false;

        $skuTargetsString = trim($this->_helper->getConfigData('sku_target'));

        $this->_helper->logDebug( 'skuTargetsString: ' . $skuTargetsString  );
        
        $skuTargetsArray = explode(',',$skuTargetsString);
        foreach($skuTargetsArray as $skuIdx => $sku){
            if(trim($sku) === ''){
                unset($skuTargetsArray[$skuIdx]);
            }
        }
        if(count($skuTargetsArray) > 0){
            if($order->getAllItems()){
                $oItems = [];
                $orderItems = $order->getAllItems();
                foreach ($orderItems as $item) {
                    if(in_array($item->getSku(),$skuTargetsArray)){

                        $getQtyOrdered = $item->getQtyOrdered();
                        $this->_helper->logDebug( 'getQtyOrdered: ' . $item->getQtyOrdered()  );
                        $getPrice = $item->getPrice();
                        $this->_helper->logDebug( 'getPrice: ' . $item->getPrice()  );
                        
                        $getQty = (int)$getQtyOrdered;
                        $this->_helper->logDebug( 'getQty: ' . $getQty  );

                        $itemPrice = (float)$getPrice;

                        $this->_helper->logDebug( 'itemPrice: ' . $itemPrice  );

                        $itemTax = (float)$item->getTaxAmount();

                        $this->_helper->logDebug( 'itemTax: ' . $itemTax  );

                        //$item->getTaxPercent()
                        //$item->getTaxAmount()

                        $linePrice = $getQty * ($itemPrice + $itemTax);

                        if($linePrice <= 0){
                            continue;
                        }

                        $skuId = (string)$item->getSku();
                        
                        $oItems[$skuId] = [
                            'amount' => $linePrice,
                            'sku' => $skuId
                        ];
                    }
                }

                $oItemsJson = json_encode($oItems);

                $this->_helper->logDebug( 'oItemsJson: ' . $oItemsJson  );
                

                //scheduleAction...
                if(count($oItems) > 0){

                    if($this->_helper->getConfigData('environment') === 'sandbox'){
                        $merchantUuid = trim($this->_helper->getConfigData('senderId_test'));
                    } else {
                        $merchantUuid = trim($this->_helper->getConfigData('senderId'));
                    }
                    $controlV2ApiUrl = trim($this->_helper->getConfigData('controlv2api_url'));
                    $controlV2ApiAuth = trim($this->_helper->getConfigData('controlv2api_token'));

                    $collectionDate = date('j');
                    if((int)$collectionDate > 28){
                        $collectionDay = 28;
                    } else {
                        $collectionDay = (int)$collectionDate;
                    }
                    $scheduleActions = [
                        'Annual' => [
                            'amount' => 0, 
                            'startDate' => date('Y-m-d', strtotime('+363 day')),
                            'collectionDay' => $collectionDay
                        ],
                        'MonthlyS' => [
                            'amount' => 0, 
                            'startDate' => date('Y-m-d', strtotime('+25 day')),
                            'collectionDay' => $collectionDay
                        ],
                        'Quarterly' => [
                            'amount' => 0, 
                            'startDate' => date('Y-m-d', strtotime('+87 day')),
                            'collectionDay' => $collectionDay
                        ]
                    ];
                    foreach($oItems as $skuParse => $scheduleSkuData){
                        $skuExpl = explode('-',$skuParse);
                        $skuFreqIdent = end($skuExpl);
                        $skuFreqIdent = strtoupper($skuFreqIdent);
                        if($skuFreqIdent === 'YR'){
                            $scheduleActions['Annual']['amount'] += $scheduleSkuData['amount'];
                        } else if($skuFreqIdent === 'QTR') {
                            $scheduleActions['Quarterly']['amount'] += $scheduleSkuData['amount'];
                        } else {
                            $scheduleActions['MonthlyS']['amount'] += $scheduleSkuData['amount'];
                        }
                    }
                    $scheduleActionsJson = json_encode($scheduleActions);
                    $tcScheduleData = json_decode($scheduleActionsJson,true);

                    if($orginalResponseArray['customer']['merchantCustomerId'] === 'guest'){
                        $orginalResponseArray['customer']['merchantCustomerId'] = $orginalResponseArray['merchantTransactionId'];
                    }
                    $tcRegistration = json_encode($orginalResponseArray);
                    foreach($tcScheduleData as $paymentFrequency => $schInfo){
                        if($schInfo['amount'] > 0){
                            $tc_payload = [
                                "merchantUuid" => $merchantUuid,
                                "registration" => $tcRegistration,
                                "firstName" => $orginalResponseArray['customer']['givenName'],
                                "surname" => $orginalResponseArray['customer']['surname'],
                                "mobile" => $orginalResponseArray['customer']['mobile'],
                                "email" => $orginalResponseArray['customer']['email'],
                                "address1" => $orginalResponseArray['billing']['street1'],
                                "postcode" => $orginalResponseArray['billing']['postcode'],
                                "city" => $orginalResponseArray['billing']['city'],
                                "amount" => number_format( $schInfo['amount'] , 2, '.', ''),
                                "currency" => $orginalResponseArray['currency'],
                                "paymentFrequency" => $paymentFrequency,
                                "startDate" => $schInfo['startDate'],
                                "collectionDay" => $schInfo['collectionDay'],
                            ];
                            $tc_payloadJson = json_encode($tc_payload);

                            $this->_helper->logDebug( 'tc_payloadJson: ' . $tc_payloadJson  );

                            //initiate TC curl
                            $clientId = $this->prepareTotalControlClientIdBlock( $controlV2ApiUrl, $controlV2ApiAuth, $tc_payload);
                            //end TC curl
                        }
                    }
                    if($clientId !== false){
                        $this->_paymentIsTotalControl($order, $clientId);
                    }
                }
                //end scheduleAction...
            }
        }
        return $clientId;
    }

    private function prepareTotalControlClientIdBlock( $controlV2ApiUrl, $controlV2ApiAuth, $payload){
        $clientId = false;
        //proceed to POST
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $controlV2ApiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Bearer '.$controlV2ApiAuth));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload, '', '&'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if(curl_errno($ch)) {
            return false;
        }
        curl_close($ch);

        $this->_helper->logDebug( 'TCCURL -> responseData: ' . $responseData  );

        $scheduleObject = json_decode($responseData);
        if(is_object($scheduleObject)){
            if(isset($scheduleObject->clientId)){
                if((int)$scheduleObject->clientId > 0){
                    $clientId = (int)$scheduleObject->clientId;
                }
            }
        }
        return $clientId;
    }

    private function _paymentIsTotalControl($order, $clientId)
    {
        $message = __(
            'Total Control client file available here: <a href="https://control2.totalprocessing.com/client/%1" target="_blank">View Total Control Client %1</a>',
            $clientId
        );
        $this->_addHistoryComment($order, $message);
    }

    /**
     * @desc Add order transaction
     *
     * @param \Magento\Sales\Mode\Order\Payment $payment
     * @param \Magento\Sales\Mode\Order         $order
     * @param string                            $uuid
     * @param array                             $response
     */
    private function _addTransaction($payment, $order, $uuid, $response, $isAutoSettle = true)
    {
        $type = $isAutoSettle
              ? \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE
              : \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
        $transaction = $this->_transactionBuilder
          ->setPayment($payment)
          ->setOrder($order)
          ->setTransactionId($uuid)
          ->setFailSafe(true)
          ->build($type);
        $transaction->setAdditionalInformation(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            $this->_helper->stripFields($response)
        )->setIsClosed(false);

        return $transaction;
    }

    /**
     * @desc Create an invoice
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param string                    $pasref
     * @param string                    $amount
     */
    private function _invoice($order, $uuid, $amount)
    {
        $invoice = $order->prepareInvoice();
        $invoice->getOrder()->setIsInProcess(true);

        // set transaction id so you can do a online refund from credit memo
        $invoice->setTransactionId($uuid);
        $invoice->register()
                ->pay()
                ->save();

        $message = __(
            'Invoiced amount of %1 Transaction ID: %2',
            $order->getBaseCurrency()->formatTxt($amount),
            $uuid
        );
        $this->_addHistoryComment($order, $message);
    }

    /**
     * @desc Add a comment to order history
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param string                    $message
     */
    private function _addHistoryComment($order, $message)
    {
        $history = $this->_orderHistoryFactory->create()
          ->setComment($message)
          ->setEntityName('order')
          ->setOrder($order);

        $history->save();
    }

    /**
     * @return array
     */
    private function processBackOfficeTransaction( $credentialsArray, $chkData , $paymentType , $logging = true ){

        $curlResource = $credentialsArray['platformBase'];
        $curlResource .= "/v1/payments/";
        $curlResource .= $chkData['uuid'];

        $backofficePayload = [
            'entityId' => $credentialsArray['entityId'],
            'paymentType' => $paymentType,
            'amount' => $chkData['grand_total'],
            'currency' => $chkData['currency']
        ];

        if($paymentType === 'RV'){
            unset($backofficePayload['amount']);
            unset($backofficePayload['currency']);
        }

        $backofficeResponse = $this->requestTpTransactionViaModelV2Block('POST',$curlResource,$credentialsArray['accessToken'],$backofficePayload,$logging);

        return $backofficeResponse;

    }


    /**
     * tpcards response matches order data.
     *
     * @param array                      $chkData
     * @param array                      $sqlQuoteData
     *
     * @return bool
     */
    private function responseQuoteMatcher($chkData, $sqlQuoteData) {
        if((string)$chkData['reserved_order_id'] !== (string)$sqlQuoteData['reserved_order_id']){
            return false;        
        }
        if((int)$chkData['quote_id'] !== (int)$sqlQuoteData['quote_id']){
            return false; 
        }
        if((float)$chkData['grand_total'] !== (float)$sqlQuoteData['grand_total']){
            return false; 
        }
        if((string)$chkData['currency'] !== (string)$sqlQuoteData['currency']){
            return false; 
        }
        return true;
    }


    /**
     * @return array
     */
    private function verifyTransactionStructure( $responseObject ){
        $checkArray = [
            'uuid' => false,
            'reserved_order_id' => false,
            'quote_id' => false,
            'grand_total' => false,
            'currency' => false,
            'customer_email' => false,
            'customer_id' => false
        ];
        if(isset($responseObject->{'id'})){
            if(!empty(trim($responseObject->{'id'}))){
                $checkArray['uuid'] = trim($responseObject->{'id'});
            }
        }
        if(isset($responseObject->{'merchantTransactionId'})){
            if(!empty(trim($responseObject->{'merchantTransactionId'}))){
                $checkArray['reserved_order_id'] = trim($responseObject->{'merchantTransactionId'});
            }
        }
        if(isset($responseObject->{'customParameters'}->{'SHOPPER_quote_id'})){
            if(!empty(trim($responseObject->{'customParameters'}->{'SHOPPER_quote_id'}))){
                if((int)trim($responseObject->{'customParameters'}->{'SHOPPER_quote_id'}) > 0){
                    $checkArray['quote_id'] = (int)trim($responseObject->{'customParameters'}->{'SHOPPER_quote_id'});
                }
            }
        }
        if(isset($responseObject->{'amount'})){
            if(!empty(trim($responseObject->{'amount'}))){
                $checkArray['grand_total'] = (float)trim($responseObject->{'amount'});
            }
        }
        if(isset($responseObject->{'currency'})){
            if(!empty(trim($responseObject->{'currency'}))){
                $checkArray['currency'] = trim($responseObject->{'currency'});
            }
        }
        if(isset($responseObject->{'customer'}->{'email'})){
            if(!empty(trim($responseObject->{'customer'}->{'email'}))){
                $checkArray['customer_email'] = trim($responseObject->{'customer'}->{'email'});
            }
        }
        if(isset($responseObject->{'customer'}->{'merchantCustomerId'})){
            if(!empty(trim($responseObject->{'customer'}->{'merchantCustomerId'}))){
                $checkArray['customer_id'] = trim($responseObject->{'customer'}->{'merchantCustomerId'});
            }
        }
        return $checkArray;
    }

    /**
     * @return array
     */
    public function prepareCheckoutDataBlock( $checkoutId = false ){

        $credentialsArray = $this->genCredentialsArray();
        if(!$credentialsArray){
            return false;
        }

        $oiCount = 0;
        $oItems = [];

        $curlResource = $credentialsArray['platformBase'] . '/v1/checkouts';
        
        $frameVars = [
            'iFrameCss' => trim($this->_helper->getConfigData('custom_frame_css')),
            'primaryColor' => trim($this->_helper->getConfigData('primary_color')),
            'paymentBrands' => trim($this->_helper->getConfigData('payment_brands')),
            'checkoutId' => $checkoutId
        ];

        $quoteParam = $this->_getCheckout()->getQuote()->getId();

        $quoteParamTwo = $this->_getCheckout()->getQuoteId();
        
        $this->_helper->logDebug( 'first quoteId: ' . $quoteParam  );
        
        $this->_helper->logDebug( 'real quoteId: ' . $quoteParamTwo  );

        if($checkoutId === false){

            $reservedOrderId = $this->genReservedOrderId();

            $this->_sqlConnectOne->updateReservedOrderId( $quoteParam , $reservedOrderId);

        } else {

            $curlResource .= '/' . $checkoutId;

        }

        $sqlRowData = $this->_sqlConnectOne->drawQuotedTransaction2( $quoteParam );

        $this->_helper->logDebug( $sqlRowData );

        $payload = json_decode($sqlRowData,true);

        if($this->_helper->getConfigData('tc_scheduler') === '1'){

            $skuTargets = trim($this->_helper->getConfigData('sku_target'));
            $skuTargets = explode(',',$skuTargets);

            $this->_helper->logDebug( 'tc_scheduler: is ACTIVE!' );

            $quoteData = $this->_getCheckout()->getQuote();
            $orderItems = $quoteData->getAllVisibleItems();
            foreach ($orderItems as $item) {
                if(!in_array($item->getSku(),$skuTargets)){
                    continue;
                }
                $oItems[$oiCount]['nme'] = (string)$item->getName();
                $oItems[$oiCount]['sku'] = (string)$item->getSku();
                $oItems[$oiCount]['qty'] = (int)$item->getQty();
                $linePrice = $item->getPrice();
                $linePrice = number_format($linePrice,2,".","");
                $itemTax = $item->getTaxAmount();
                $itemTax = number_format($itemTax,2,".","");
                $lineIncl = ( (float)$linePrice + (float)$itemTax );
                $oItems[$oiCount]['amt'] = (float)$lineIncl;
                $oiCount++;
            }
            $this->_helper->logDebug( 'oItems: ' . json_encode($oItems) );
        }

        if(is_array($payload)){
            //set entityId
            $payload['entityId'] = $credentialsArray['entityId'];
            if($this->_helper->getConfigData('tc_scheduler') === '1'){
                if(count($oItems) > 0){
                    $payload['customParameters[SHOPPER_tpJson]'] = json_encode($oItems);
                    $payload['createRegistration'] = 'true';
                    $payload['recurringType'] = 'INITIAL';
                }
            }
            foreach($payload as $apiParam => $paramData){
                if( empty($paramData) || $paramData === '' || $paramData === null ){
                    unset($payload[$apiParam]);
                }
            }
        }

        $this->_helper->logDebug( 'finalPayload: ' . json_encode($payload) );

        if($checkoutId !== false){

            $responseData = $this->requestTpTransactionViaModelV2Block('POST',$curlResource,$credentialsArray['accessToken'],$payload,true);
            
            return ["responseData" => $responseData];

        }
        
        $arr = [
            'curlResource' => $curlResource,
            'method' => 'POST',
            'accessToken' => $credentialsArray['accessToken'],
            'payload' => $payload,
            'reservedOrderId' => $payload['merchantTransactionId'],
            'quote_id' => $payload['customParameters[SHOPPER_quote_id]']
        ];
        
        //do post...
        $checkoutId = $this->requestTpTransactionViaModelV2Block($arr['method'],$arr['curlResource'],$arr['accessToken'],$payload,true);
        //end post...
        
        if($checkoutId !== false){
            $frameVars['oppwaJs'] = $credentialsArray['platformBase'] . '/v1/paymentWidgets.js?checkoutId=' . $checkoutId;
            $frameVars['checkoutId'] = $checkoutId;
        } else {
            $frameVars['oppwaJs'] = false;
        }

        unset($arr['curlResource']);
        unset($arr['method']);
        unset($arr['accessToken']);

        $ret = array_merge($frameVars,$arr);

        return $ret;
    }

    private function requestTpTransactionViaModelV2Block($requestMethod,$url,$accessToken,$payload,$logging=false) {
    	
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Authorization: Bearer ' . $accessToken
        ]);
    	if($requestMethod !== 'POST'){
    	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
    	} else {
            if(isset($payload['amount'])){
                $payload['amount'] = number_format($payload['amount'],2,".","");
            }
            if(isset($payload['threeDSecure.amount'])){
                $payload['threeDSecure.amount'] = number_format($payload['threeDSecure.amount'],2,".","");
            }
    	    curl_setopt($ch, CURLOPT_POST, true);
    	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload, '', '&'));
    	}
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	
    	$responseJson = curl_exec($ch);
    	
    	if(curl_errno($ch)) {
    	    if($logging){
                $this->_helper->logDebug('curl error:'.curl_error($ch));
    	    }
    		return false;
    	}
    	
    	curl_close($ch);
    	
    	if($logging){
    	    $this->_helper->logDebug($responseJson);
    	}
    	
    	$responseData = json_decode($responseJson);
    	
    	if(is_object($responseData)){
            if($requestMethod === 'GET'){
                return $responseData;
            }
    	    if(isset($responseData->result->code)){
                if(isset($payload['paymentType'])){
                    if($payload['paymentType'] === 'CP' || $payload['paymentType'] === 'RV'){
                        return $responseData;
                    }
                }
    	        if($responseData->result->code === '000.200.100'){
    	            if($logging){
                	    $this->_helper->logDebug($responseData->id);
                	}
    	            return (string)$responseData->id;
                } else if($responseData->result->code === '000.200.101'){
                    return $responseData;
                } else {
    	            if($logging){
                        $this->_helper->logDebug('result->code is not 000.200.100 or 000.200.101');
                    }
    	        }
    	    } else {
    	        if($logging){
                    $this->_helper->logDebug('is not set result->code.');
                }
    	    }
    	} else {
    	    if($logging){
                $this->_helper->logDebug('is not object.');
            }
    	}
    	return false;
    }

    /**
     * Get frontend checkout session object.
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function _getCheckout()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get frontend customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    public function _getCustomerSession() 
    {
        return $this->_customerSession;
    }

}
