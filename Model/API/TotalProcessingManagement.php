<?php

namespace TotalProcessing\TPCARDS\Model\API;

use TotalProcessing\TPCARDS\Model\Config\Source\SettleMode;

class TotalProcessingManagement implements \TotalProcessing\TPCARDS\API\TotalProcessingManagementInterface
{
    const FRAUD_ACTIVE = 'ACTIVE';
    const FRAUD_HOLD = 'HOLD';
    const FRAUD_BLOCK = 'BLOCK';
    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    private $_helper;

    /**
     * @var \TotalProcessing\TPCARDS\API\RemoteXMLInterface
     */
    private $_remoteXml;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $_transactionBuilder;

    /**
     * @var \TotalProcessing\TPCARDS\Logger\Logger
     */
    private $_logger;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $_orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
    private $_orderHistoryFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * TotalProcessingManagement constructor.
     *
     * @param \TotalProcessing\TPCARDS\Helper\Data                                 $helper
     * @param \TotalProcessing\TPCARDS\API\RemoteXMLInterface                      $remoteXml
     * @param \Magento\Checkout\Model\Session                                 $session
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \TotalProcessing\TPCARDS\Logger\Logger                               $logger
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender             $orderSender
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory                $orderHistoryFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface               $customerRepository
     */
    public function __construct(
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \TotalProcessing\TPCARDS\API\RemoteXMLInterface $remoteXml,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \TotalProcessing\TPCARDS\Logger\Logger $logger,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\AddressFactory $addressFactory
    ) {
        $this->_helper = $helper;
        $this->_session = $session;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_logger = $logger;
        $this->_orderSender = $orderSender;
        $this->_orderHistoryFactory = $orderHistoryFactory;
        $this->_customerRepository = $customerRepository;
        $this->_remoteXml = $remoteXml;
        $this->_address = $addressFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function processResponse($order, $response)
    {
        if(isset($response['result']['description'])){
            $response['result']['description'] = str_replace("'", "", $response['result']['description']);
        }
        
        $payment = $order->getPayment();
        if (!$this->_validateResponseFields($response)) {
            try {
                $this->_helper->setAdditionalInfo($payment, $response);
                $order->save();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
            return ['result' => false , 'code' => $response['result']['code'] , 'description' => $response['result']['description']];
        }

        $uuid = $response['id'];
        $amount = number_format($response['amount'],2,'.','');
        $currency = trim($response['currency']);
        
        //verify order amt match tnx
        if($amount !== number_format($order->getBaseGrandTotal(),2,'.','')){
            $this->_helper->logDebug('amount mismatch! gateway => ' . $amount . ' !== order->getBaseGrandTotal() => ' . number_format($order->getBaseGrandTotal(),2,'.',''));
            return false;
        }
        //verify order curr match tnx
        if($currency !== $order->getBaseCurrencyCode()){
            $this->_helper->logDebug('currency mismatch! gateway => ' . $currency . ' !== order->getBaseCurrencyCode() => ' . $order->getBaseCurrencyCode());
            return false;
        }
        
        $this->_helper->logDebug('gateAmt => ' . $amount );
        $this->_helper->logDebug('gateCur => ' . $currency );

        if($order->getBillingAddress()){

            $newBilling = [
                'givenName' => '',
                'surname' => '',
                'street' => '',
                'city' => '',
                'state' => '',
                'postcode' => '',
                'country' => ''
            ];
            
            //log current order billing address
            $this->_helper->logDebug('$order->getBillingAddress()  => true' );
            $this->_helper->logDebug('$order->getBillingAddress()->getData()  => ' . json_encode($order->getBillingAddress()->getData()) );
            
            $billingAddress = $order->getBillingAddress();

            /*if(isset($response['customer']['givenName'])){
                $newBilling['givenName'] = ucfirst(trim($response['customer']['givenName']));
                $billingAddress->setFirstname($newBilling['givenName']);
            }
            if(isset($response['customer']['surname'])){
                $newBilling['surname'] = ucfirst(trim($response['customer']['surname']));
                $billingAddress->setLastname($newBilling['surname']);
            }*/
            if(isset($response['billing']['street1'])){
                $newBilling['street'] = trim($response['billing']['street1']);
                $billingAddress->setStreet($newBilling['street']);
            }
            if(isset($response['billing']['street2'])){
                $newBilling['street'] .= "\n" . trim($response['billing']['street2']);
                $billingAddress->setStreet($newBilling['street']);
            }
            if(isset($response['billing']['city'])){
                $newBilling['street'] .= "\n" . trim($response['billing']['city']);
                $newBilling['city'] = ucfirst(trim($response['billing']['city']));
                $billingAddress->setCity($newBilling['city']);
            }
            if(isset($response['billing']['postcode'])){
                $newBilling['postcode'] = strtoupper(trim($response['billing']['postcode']));
                $billingAddress->setPostcode($newBilling['postcode']);
            }
            if(isset($response['billing']['country'])){
                $newBilling['country'] = $response['billing']['country'];
                $billingAddress->setCountryId($newBilling['country']);
            }
            if(isset($response['billing']['state'])){
                if($newBilling['country'] === 'US' || $newBilling['country'] === 'CA'){
                    $newBilling['state'] = $response['billing']['state'];
                    $billingAddress->setState($newBilling['state']);
                }
            }

            $this->_helper->logDebug('$order->newBilling()  => ' . json_encode($newBilling) );
            $billingAddress->save();

        } else {

            $this->_helper->logDebug('$order->getBillingAddress()  => false' );

        }

        $settleMode = $this->_helper->getConfigData('settle_mode', $order->getStoreId());
        $isAutoSettle = $settleMode == SettleMode::SETTLEMODE_AUTO;
        //Set information
        $payment->setTransactionId($uuid);
        $this->_helper->setAdditionalInfo($payment, $response);
        $payment->setAdditionalInformation('AUTO_SETTLE_FLAG', $settleMode);

        //Add order Transaction
        $this->_addTransaction($payment, $order, $uuid, $response, $isAutoSettle);
        //place payment
        $payment->getMethodInstance()
                ->setIsInitializeNeeded(false);

        $fraud = false;
        $payment->place();
        if ($fraud) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW)
                  ->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
        } else {
            //Write comment
            $this->_paymentIsAuthorised($order, $uuid, $amount);

            //Should we invoice
            if ($isAutoSettle) {
                $this->_invoice($order, $uuid, $amount);
            } else {
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                      ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            }
        }

        $this->_helper->logDebug('Action =>  checking for customParameters.SHOPPER_tpJson' );

        if(isset($response['customParameters']['SHOPPER_tpJson'])){

            $this->_helper->logDebug('Action =>  FOUND! customParameters.SHOPPER_tpJson' );
            
            if($response['result']['code'] === '000.000.000' || $response['result']['code'] === '000.100.110'){

                $this->_helper->logDebug('code =>  is success ' . $response['result']['code'] );

                $responseArray = $response;

                if($responseArray['customer']['merchantCustomerId'] === 'guest'){

                    $this->_helper->logDebug('customerId =>  is guest ' );

                    $responseArray['customer']['merchantCustomerId'] = $response['merchantTransactionId'];

                }

                $this->_helper->logDebug('Action =>  get environment');

                if($this->_helper->getConfigData('environment') === 'sandbox'){

                    $merchantUuid = trim($this->_helper->getConfigData('senderId_test'));

                } else {

                    $merchantUuid = trim($this->_helper->getConfigData('senderId'));

                }

                $this->_helper->logDebug('merchantUuid =>  ' . $merchantUuid );

                $controlV2ApiUrl = trim($this->_helper->getConfigData('controlv2api_url'));
                $controlV2ApiAuth = trim($this->_helper->getConfigData('controlv2api_token'));
                $responseJson = json_encode($responseArray);
                $tpJsonArray = json_decode($response['customParameters']['SHOPPER_tpJson']);

                $this->_helper->logDebug('run prepareTotalControlClientId(controlV2ApiUrl) =>  ' . $controlV2ApiUrl );
                $this->_helper->logDebug('run prepareTotalControlClientId(controlV2ApiAuth) =>  ' . $controlV2ApiAuth );
                $this->_helper->logDebug('run prepareTotalControlClientId(responseJson) =>  ' . $responseJson );
                $this->_helper->logDebug('run prepareTotalControlClientId(tpJsonArray) =>  ' . $response['customParameters']['SHOPPER_tpJson'] );
                
                $clientId = $this->prepareTotalControlClientId($merchantUuid,$controlV2ApiUrl,$controlV2ApiAuth,$responseJson,$tpJsonArray);
                
                if($clientId !== false){
                    
                    $this->_helper->logDebug('clientId  => ' . $clientId );
                    
                    $this->_paymentIsTotalControl($order, $clientId);
                
                } else {

                    $this->_helper->logDebug('clientId  => false' );

                }

            }

        }

        $order->save();
        
        //Send order email
        if (!$order->getEmailSent() && !$fraud) {
            $this->_orderSender->send($order);
        }

        //Store payer details if applicable
        $customerId = $order->getCustomerId();
        if (!empty($customerId)) {
        
        }
        
        $this->_helper->logDebug('processResponse  => true' );

        return ['result' => true , 'code' => $response['result']['code'] , 'description' => $response['result']['description'] ];
    }

    public function prepareTotalControlClientId($merchantUuid, $controlV2ApiUrl, $controlV2ApiAuth, $responseJson, $tpJsonArray){
        
        $this->_helper->logDebug('executed prepareTotalControlClientId()' );

        $this->_helper->logDebug('controlV2ApiUrl  => ' . $controlV2ApiUrl );
        $this->_helper->logDebug('controlV2ApiAuth  => ' . $controlV2ApiAuth );
        
        $clientId = false;
        $responseObj = json_decode($responseJson);
        //$scheduleAmount = 0;

        $collectionDate = date('j');
        if((int)$collectionDate > 28){
            $collectionDay = 28;
        } else {
            $collectionDay = (int)$collectionDate;
        }

        $this->_helper->logDebug('collectionDay => ' . $collectionDay );

        $initDt = date('Y-m-'.str_pad($collectionDay,2,'0',STR_PAD_LEFT));

        $this->_helper->logDebug('initDt => ' . $initDt );

        $this->_helper->logDebug('responseObj->customer->givenName => ' . $responseObj->customer->givenName );

        foreach($tpJsonArray as $idx => $obj){
            $scheduleQty = (int)$obj->qty;
            $scheduleAmt = (float)$obj->amt;
            //$scheduleAmount = $scheduleAmount + ( $scheduleQty * $scheduleAmt );
            $scheduleAmount = ( $scheduleQty * $scheduleAmt );
            $scheduleAmount = number_format($scheduleAmount,2,".","");

            $this->_helper->logDebug('scheduleAmount => ' . $scheduleAmount );

            //determine frequency type...

            $skuParse = trim((string)$obj->sku);
            $skuExpl = explode('-',$skuParse);
            $skuFreqIdent = end($skuExpl);
            $skuFreqIdent = strtoupper($skuFreqIdent);

            if($skuFreqIdent === 'YR'){

                $paymentFrequency = "Annual";
                $startDate = date('Y-m-d', strtotime('+363 day'));
                //$endDate = date('Y-m-d', strtotime('+1 year'));
                $this->_helper->logDebug('startDate => ' . $startDate );
                //$this->_helper->logDebug('endDate => ' . $endDate );

            } else {

                $paymentFrequency = "MonthlyS";
                $startDate = date('Y-m-d', strtotime('+25 day'));
                //$endDate = date('Y-m-d', strtotime('+338 day'));
                $this->_helper->logDebug('startDate => ' . $startDate );
                //$this->_helper->logDebug('endDate => ' . $endDate );

            }

            //prepare to payload

            $payload = [
                "merchantUuid" => $merchantUuid,
                "registration" => $responseJson,
                "firstName" => $responseObj->customer->givenName,
                "surname" => $responseObj->customer->surname,
                "mobile" => $responseObj->customer->mobile,
                "email" => $responseObj->customer->email,
                "address1" => $responseObj->billing->street1,
                "postcode" => $responseObj->billing->postcode,
                "city" => $responseObj->billing->city,
                "amount" => $scheduleAmount,
                "currency" => $responseObj->currency,
                "paymentFrequency" => $paymentFrequency,
                "startDate" => $startDate,
                //"endDate" => $endDate,
                "collectionDay" => $collectionDay
            ];

            //proceed to POST

            $this->_helper->logDebug(__('Total Control Request: ').print_r($this->_helper->stripTrimFields($payload), true));
            $this->_helper->logDebug('executing TC curl');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $controlV2ApiUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Bearer '.$controlV2ApiAuth));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload, '', '&'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if(curl_errno($ch)) {
                $this->_helper->logDebug('curl_errno => ' . curl_errno($ch));
                return false;
            }
            curl_close($ch);

            $this->_helper->logDebug('TC curl_close');

            $this->_helper->logDebug('scheduleResponse  => ' . $responseData );
            $scheduleObject = json_decode($responseData);
            if(is_object($scheduleObject)){
                if(isset($scheduleObject->clientId)){
                    if((int)$scheduleObject->clientId > 0){
                        $clientId = (int)$scheduleObject->clientId;
                    }
                }
            }
        }
        return $clientId;
    }

    private function checkFraud($response, $payment, $isAutoSettle, $uuid, $amount)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreCart($cartId)
    {
        $session = $this->_session;
        $order = $session->getLastRealOrder();
        if ($order->getId()) {
            // restore the quote
            if ($session->restoreQuote()) {
                $this->_helper->cancelOrder($order);
            }
        }
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
     * @desc Handles the card storage fields
     *
     * @param array  $response
     * @param string $customerId
     */
    private function _handleCardStorage($response, $customerId)
    {
        try {
            $paymentSetup = isset($response['PMT_SETUP']) ? $response['PMT_SETUP'] : false;
            $payerRef = isset($response['SAVED_PAYER_REF']) ? $response['SAVED_PAYER_REF'] : false;
            //Is there a payment setup?
            if ($paymentSetup) {
                $payerSetup = isset($response['PAYER_SETUP']) ? $response['PAYER_SETUP'] : false;
                //Are we setting up a new payer?
                if ($payerSetup == '00') {
                    //Store payer ref against the customer
                    $this->_storeCustomerPayerRef(
                        $response['MERCHANT_ID'],
                        $response['ACCOUNT'],
                        $payerRef,
                        $customerId
                    );
                }
            }

            $cardRef = isset($response['SAVED_PMT_REF']) ? $response['SAVED_PMT_REF'] : false;
            if ($cardRef) {
                //Store card details
                $this->_helper->logDebug('Customer '.$customerId.' added a new card:'.$cardRef);
            }

            $cardsEdited = isset($response['TPCARDS_EDITED_PMT_REF']) ? $response['TPCARDS_EDITED_PMT_REF'] : false;
            $cardsDeleted = isset($response['TPCARDS_DELETED_PMT_REF']) ? $response['TPCARDS_DELETED_PMT_REF'] : false;
            if ($cardsEdited) {
                $this->_manageEditedCards($cardsEdited);
            }
            if ($cardsDeleted) {
                $this->_manageDeletedCards($cardsDeleted);
            }
        } catch (\Exception $e) {
            //card storage exceptions should not stop a transaction
            $this->_logger->critical($e);
        }
    }

    /**
     * @desc Store the payer ref against the customer
     *
     * @param string $merchantId
     * @param string $account
     * @param string $payerRef
     * @param string $customerId
     */
    private function _storeCustomerPayerRef($merchantId, $account, $payerRef, $customerId)
    {
        $this->_helper->logDebug('Storing payer ref:'.$payerRef.' for customer: '.$customerId);

        $customer = $this->_customerRepository->getById($customerId);
        $customer->setCustomAttribute('totalprocessing_tpcards_payerref', $payerRef);
        $this->_customerRepository->save($customer);
        //Update payer in realex
        try {
            $this->_remoteXml->payerEdit($merchantId, $account, $payerRef, $customer);
        } catch (\Exception $e) {
            //Let it fail but still setup the rest of the payment
            $this->_logger->critical($e);
        }
    }

    /**
     * @desc Manage cards that were edited while the user was on tpcards
     *
     * @param string $cards
     */
    private function _manageEditedCards($cards)
    {
        $this->_helper->logDebug('Customer edited the following cards:'.$cards);
    }

    /**
     * @desc Manage cards that were deleted while the user was on tpcards
     *
     * @param string $cards
     */
    private function _manageDeletedCards($cards)
    {
        $this->_helper->logDebug('Customer deleted the following cards:'.$cards);
    }

    /**
     * @desc Called after payment is authorised
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param string                    $pasref
     * @param string                    $amount
     */
    private function _paymentIsAuthorised($order, $uuid, $amount)
    {
        $message = __(
            'Authorised amount of %1 uuid: %2',
            $order->getBaseCurrency()->formatTxt($amount),
            $uuid
        );
        $this->_addHistoryComment($order, $message);
    }

    /**
     * @desc Called after payment is authorised & is TotalControl Client
     *
     * @param \Magento\Sales\Mode\Order $order
     * @param string                    $pasref
     * @param string                    $amount
     */
    private function _paymentIsTotalControl($order, $clientId)
    {
        $message = __(
            'Total Control client file available here: <a href="https://control2.totalprocessing.com/client/%1" target="_blank">View Total Control Client %1</a>',
            $clientId
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
     * @desc Validates the response fields
     *
     * @param array $response
     *
     * @return bool
     */
    private function _validateResponseFields($response)
    {
        if ($response == null ||
           !isset($response['result']) ||
           !isset($response['result']['code']) ||
           !isset($response['id'])) {
               
            $this->_helper->logDebug('_validateResponseFields  => false 1' );
            
            return false;
        }
        
        if(!in_array($response['result']['code'],['000.100.110','000.000.000'])){
            $this->_helper->logDebug('_validateResponseFields  => false 2' );
            return false;
        }

        $this->_helper->logDebug('_validateResponseFields  => true' );

        return true;
    }

    /**
     * @desc Add order transaction
     *
     * @param \Magento\Sales\Mode\Order\Payment $payment
     * @param \Magento\Sales\Mode\Order         $order
     * @param string                            $uuid
     * @param array                             $response
     */
    private function _addTransaction($payment, $order, $uuid, $response, $isAutoSettle)
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
}
