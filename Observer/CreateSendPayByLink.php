<?php

namespace TotalProcessing\TPCARDS\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CreateSendPayByLink
 *
 * @package TotalProcessing\TPCARDS\Observer
 */
class CreateSendPayByLink implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transaction;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    /**
     * @var \Magento\Shipping\Model\ShipmentNotifier
     */
    protected $shipmentNotifier;

    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    protected $helper;

    /**
     * @var \TotalProcessing\TPCARDS\Model\API\SQLDirectConnectSingleId
     */
    protected $sqlConnectOne;

    /**
     * CreateSendPayByLink constructor.
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\DB\TransactionFactory $transaction
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier
     * @param \TotalProcessing\TPCARDS\Helper\Data $helper
     * @param \TotalProcessing\TPCARDS\Model\API\SQLDirectConnectSingleId $sqlConnectOne
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\DB\TransactionFactory $transaction,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \TotalProcessing\TPCARDS\Model\API\SQLDirectConnectSingleId $sqlConnectOne
    ) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->messageManager = $messageManager;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->helper = $helper;
        $this->sqlConnectOne = $sqlConnectOne;
    }

    /**
     * @param Observer $observer
     * @return null|void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment()->getMethodInstance();
        // Check code payment method
        if ($payment->getCode() == 'totalprocessing_tpcards') {

            if($this->helper->getConfigData('tc_paybylink') === '1'){

                $this->displayNotified($order, $payment);
                //go
                $this->helper->logDebug(__('execute CreateSendPayByLink => ' . $order->getId()) );

                $payload = $this->getTpCheckoutPayload($order);
                
                $jsonPayload = json_encode($payload);

                $this->helper->logDebug(__('getTpCheckoutPayload => ' . $jsonPayload) );

                $responseData = $this->postCreatePayByLink($jsonPayload);

                $this->helper->logDebug(__('execute postCreatePayByLink => ' . $responseData) );

            }
        }
    }

    /**
     * @param $order
     * @param $payment
     * return
     */
    private function displayNotified($order, $payment)
    {

        $order->addStatusHistoryComment('TPCARDS: ' . ' Initialising CreateSendPayByLink class methods', false);
        $order->save();
        return null;

    }
    /**
     * @param $order
     * return
     */
    private function getTpCheckoutPayload($order)
    {
        $paymentType = "PA";
        $skuTargets = trim($this->helper->getConfigData('sku_target'));
        $skuTargets = explode(',',$skuTargets);
        $enforceRg = false;
        $oItems = [];

        if($this->helper->getConfigData('environment') === 'sandbox'){
            $entityId = trim($this->helper->getConfigData('entityId_test'));
        } else {
            $entityId = trim($this->helper->getConfigData('entityId'));
        }

        $customerId = $order->getCustomerId();
        if(empty($customerId)){
            $customerId = 'guest';
        }
        
        $payload['entityId'] = $entityId;
        $payload['amount'] = number_format($order->getBaseGrandTotal(),2,".","");
        $payload['currency'] = $order->getBaseCurrencyCode();
        $payload['paymentType'] = $paymentType;
        $payload['merchantTransactionId'] = $order->getRealOrderId();
        $payload['merchantInvoiceId'] = $order->getId();
        $payload['customer.email'] = $order->getCustomerEmail();
        $payload['customer.merchantCustomerId'] = $customerId;
        $payload['customer.givenName'] = $order->getCustomerFirstname();
        $payload['customer.surname'] = $order->getCustomerLastname();
        $payload['threeDSecure.amount'] = number_format($order->getBaseGrandTotal(),2,".","");
        $payload['threeDSecure.currency'] = $order->getBaseCurrencyCode();
        $payload['threeDSecure.challengeIndicator'] = "02";
        $payload['customParameters[SHOPPER_plugin_installed]'] = "Magento v2 TPCARDS";
        $payload['customParameters[SHOPPER_returnurl]'] = $this->helper->getFrameDomainUrl();
        $payload['customParameters[SHOPPER_order_id]'] = $order->getId();
        //$payload['merchant.country'] = "GB";

        if($order->getBillingAddress()){
            $billingAddress = $order->getBillingAddress();
            if($billingAddress->getTelephone()){
                $payload['customer.phone'] = $billingAddress->getTelephone();
                $payload['customer.mobile'] = $billingAddress->getTelephone();
            }
            $payload['billing.street1'] = $billingAddress->getStreetLine(1);
            $payload['billing.street2'] = $billingAddress->getStreetLine(2);
            $payload['billing.city'] = $billingAddress->getCity();
            $payload['billing.state'] = in_array($billingAddress->getCountryId(), ['US', 'CA']) ? $billingAddress->getRegionCode() : '';
            $payload['billing.postcode'] = $billingAddress->getPostcode();
            $payload['billing.country'] = $billingAddress->getCountryId();

            if($payload['billing.country'] === 'US'){
                if(!in_array($payload['billing.state'],["AL","AK","AZ","AR","CA","CO","CT","DE","DC","FL","GA","HI","ID","IL","IN","IA","KS","KY","LA","ME","MD","MA","MI","MN","MS","MO","MT","NE","NV","NH","NJ","NM","NY","NC","ND","OH","OK","OR","PA","RI","SC","SD","TN","TX","UT","VT","VA","WA","WV","WI","WY"])){
                    $payload['billing.state'] = '';
                }
            } else {
                $payload['billing.state'] = '';
            }
        }
        
        if ($order->getShippingAddress()) {
            $shippingAddress = $order->getShippingAddress();
            $payload['shipping.street1'] = $shippingAddress->getStreetLine(1);
            $payload['shipping.street2'] = $shippingAddress->getStreetLine(2);
            $payload['shipping.city'] = $shippingAddress->getCity();
            $payload['shipping.state'] = in_array($shippingAddress->getCountryId(), ['US', 'CA']) ? $shippingAddress->getRegionCode() : '';
            $payload['shipping.postcode'] = $shippingAddress->getPostcode();
            $payload['shipping.country'] = $shippingAddress->getCountryId();

            if($payload['shipping.country'] === 'US'){
                if(!in_array($payload['shipping.state'],["AL","AK","AZ","AR","CA","CO","CT","DE","DC","FL","GA","HI","ID","IL","IN","IA","KS","KY","LA","ME","MD","MA","MI","MN","MS","MO","MT","NE","NV","NH","NJ","NM","NY","NC","ND","OH","OK","OR","PA","RI","SC","SD","TN","TX","UT","VT","VA","WA","WV","WI","WY"])){
                    $payload['shipping.state'] = '';
                }
            } else {
                $payload['shipping.state'] = '';
            }
        }
        
        if($order->getAllItems()){
            $orderItems = $order->getAllItems();
            $oiCount = 0;
            foreach ($orderItems as $item) {
                if(in_array($item->getSku(),$skuTargets)){
                    $enforceRg = true;
                } else {
                    continue;
                }
                $oItems[$oiCount]['nme'] = $item->getName();
                $oItems[$oiCount]['sku'] = $item->getSku();
                $oItems[$oiCount]['qty'] = $item->getQtyOrdered();
                $oItems[$oiCount]['amt'] = $item->getPrice();
                $oiCount++;
            }
        }

        if($enforceRg === true){
            $payload['customParameters[SHOPPER_tpJson]'] = json_encode($oItems);
            $payload['createRegistration'] = 'true';
            $payload['recurringType'] = 'INITIAL';
        }
        
        foreach($payload as $k => $v){
            if( empty($v) || $v == '' ){
                unset($payload[$k]);
            }
        }
        
        return $payload;
        
    }

    /**
     * @param $payload
     * return
     */
    public function postCreatePayByLink($payload){
        $url = "https://oppwa.totalprocessing.com/paybylink/";
        if($this->helper->getConfigData('environment') === 'sandbox'){
            $url .= "?test=1";
            $accessToken = trim($this->helper->getConfigData('accessToken_test'));
        } else {
            $accessToken = trim($this->helper->getConfigData('accessToken'));
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Bearer '.$accessToken));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if(curl_errno($ch)) {
            $responseData = curl_error($ch);
        }
        curl_close($ch);
        return $responseData;
    }

}