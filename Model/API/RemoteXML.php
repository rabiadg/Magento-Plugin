<?php

namespace TotalProcessing\TPCARDS\Model\API;

use TotalProcessing\TPCARDS\Model\Config\Source\SettleMode;

class RemoteXML implements \TotalProcessing\TPCARDS\API\RemoteXMLInterface
{
    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    private $_helper;

    /**
     * @var \TotalProcessing\TPCARDS\Logger\Logger
     */
    private $_logger;

    /**
     * @var \TotalProcessing\TPCARDS\Model\API\Request\RequestFactory
     */
    private $_requestFactory;

    /**
     * @var \TotalProcessing\TPCARDS\Model\API\Response\ResponseFactory
     */
    private $_responseFactory;

    /**
     * @var \Magento\Sales\Model\Order\Status\HistoryFactory
     */
    private $_orderHistoryFactory;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    private $_transactionRepository;

    /**
     * RemoteXML constructor.
     *
     * @param \TotalProcessing\TPCARDS\Helper\Data                        $helper
     * @param \TotalProcessing\TPCARDS\Logger\Logger                      $logger
     * @param \TotalProcessing\TPCARDS\Model\API\Request\RequestFactory   $requestFactory
     * @param \TotalProcessing\TPCARDS\Model\API\Response\ResponseFactory $responseFactory
     * @param \Magento\Sales\Api\TransactionRepositoryInterface      $transactionRepository
     */
    public function __construct(
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \TotalProcessing\TPCARDS\Logger\Logger $logger,
        \TotalProcessing\TPCARDS\Model\API\Request\RequestFactory $requestFactory,
        \TotalProcessing\TPCARDS\Model\API\Response\ResponseFactory $responseFactory,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
    ) {
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_requestFactory = $requestFactory;
        $this->_responseFactory = $responseFactory;
        $this->_transactionRepository = $transactionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function settle($payment, $amount)
    {
        $additional = $payment->getAdditionalInformation();
        $request = $this->_requestFactory->create()
                    ->setType(Request\Request::TYPE_SETTLE)
                    ->setMerchantId($additional['MERCHANT_ID'])
                    ->setOrderId($additional['ORDER_ID'])
                    ->setPasref($additional['PASREF'])
                    ->setAmount($amount)
                    ->build();

        return $this->_sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function multisettle($payment, $amount)
    {
        $additional = $payment->getAdditionalInformation();
        $request = $this->_requestFactory->create()
                    ->setType(Request\Request::TYPE_MULTISETTLE)
                    ->setMerchantId($additional['MERCHANT_ID'])
                    ->setOrderId($additional['ORDER_ID'])
                    ->setPasref($additional['PASREF'])
                    ->setAccount($additional['ACCOUNT'])
                    ->setAuthCode($additional['AUTHCODE'])
                    ->setAmount($amount)
                    ->build();

        return $this->_sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function rebateOLD($payment, $amount, $comments)
    {
        $refundhash = sha1($this->_helper->getEncryptedConfigData('rebate_secret'));
        $transaction = $this->_getTransaction($payment);
        $additional = $payment->getAdditionalInformation();
        if ($additional['AUTO_SETTLE_FLAG'] == SettleMode::SETTLEMODE_MULTI) {
            $orderId = '_multisettle_'.$additional['ORDER_ID'];
            $rawFields = $transaction->getAdditionalInformation(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            );
            $pasref = $rawFields['PASREF'];
        } else {
            $orderId = $additional['ORDER_ID'];
            $pasref = $additional['PASREF'];
        }
        $request = $this->_requestFactory->create()
                  ->setType(Request\Request::TYPE_REBATE)
                  ->setMerchantId($additional['MERCHANT_ID'])
                  ->setAccount($additional['ACCOUNT'])
                  ->setOrderId($orderId)
                  ->setPasref($pasref)
                  ->setAuthCode($additional['AUTHCODE'])
                  ->setAmount($amount)
                  ->setCurrency($payment->getOrder()->getBaseCurrencyCode())
                  ->setComments($comments)
                  ->setRefundHash($refundhash)
                  ->build();

        return $this->_sendRequest($request);
    }

    public function rebate($payment, $amount, $comments)
    {

        $currency = $payment->getOrder()->getBaseCurrencyCode();
        $uuid = $payment->getAdditionalInformation('id');
        $ndc = $payment->getAdditionalInformation('ndc');

        $this->_helper->logDebug('uuid => ' . $uuid);
        $this->_helper->logDebug('amount => ' . $amount);
        $this->_helper->logDebug('currency => ' . $currency);
        $this->_helper->logDebug('ndc => ' . $ndc);

        if(strpos($ndc,'.uat') !== false){
            $platformBase = 'https://test.oppwa.com';
            $entityId = trim($this->_helper->getConfigData('entityId_test'));
            $accessToken = trim($this->_helper->getConfigData('accessToken_test'));
        } else {
            $platformBase = 'https://oppwa.com';
            $entityId = trim($this->_helper->getConfigData('entityId'));
            $accessToken = trim($this->_helper->getConfigData('accessToken'));
        }

        $curlResource = $platformBase . '/v1/payments/' . $uuid; 

        $headers = [
            'Content-Type:application/x-www-form-urlencoded; charset=UTF-8',
            'Authorization:Bearer ' . $accessToken
        ];

        $payload = [
            "entityId" => $entityId,
            "paymentType" => "RF",
            "currency" => $currency,
            "amount" => number_format($amount,2,".","")
        ];

        $this->_helper->logDebug(__('RF payload: ').print_r($this->_helper->stripTrimFields($payload), true));

        return $this->_sendRequestTP($curlResource,$headers,$payload);
    }

    /**
     * {@inheritdoc}
     */
    public function void($payment, $comments)
    {
        $transaction = $this->_getTransaction($payment);
        $additional = $payment->getAdditionalInformation();
        $orderId = $additional['ORDER_ID'];
        if ($additional['AUTO_SETTLE_FLAG'] == SettleMode::SETTLEMODE_MULTI) {
            $rawFields = $transaction->getAdditionalInformation(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS
            );
            $pasref = $rawFields['PASREF'];
        } else {
            $pasref = $additional['PASREF'];
        }
        $request = $this->_requestFactory->create()
                  ->setType(Request\Request::TYPE_VOID)
                  ->setMerchantId($additional['MERCHANT_ID'])
                  ->setAccount($additional['ACCOUNT'])
                  ->setOrderId($orderId)
                  ->setPasref($pasref)
                  ->setComments($comments)
                  ->build();

        return $this->_sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function payerEdit($merchantId, $account, $payerRef, $customer)
    {
        $request = $this->_requestFactory->create()
                  ->setType(Request\Request::TYPE_PAYER_EDIT)
                  ->setMerchantId($merchantId)
                  ->setAccount($account)
                  ->setOrderId(uniqid())
                  ->setPayerRef($payerRef)
                  ->setPayer($customer)
                  ->build();

        return $this->_sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function releasePayment($payment, $comments)
    {
        $additional = $payment->getAdditionalInformation();
        $request = $this->_requestFactory->create()
                    ->setType(Request\Request::TYPE_RELEASE)
                    ->setComments($comments)
                    ->build();

        return $this->_sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function holdPayment($payment, $comments)
    {
        $additional = $payment->getAdditionalInformation();
        $request = $this->_requestFactory->create()
                    ->setType(Request\Request::TYPE_HOLD)
                    ->setComments($comments)
                    ->build();

        return $this->_sendRequest($request);
    }

    /**
     * @desc Send the request to the remote xml api
     *
     * @param \TotalProcessing\TPCARDS\Model\API\Request\Request $request
     *
     * @return \TotalProcessing\TPCARDS\Model\API\Response\Response
     */
    private function _sendRequest($request)
    {
    	return false;
    }

    private function _sendRequestTP($curlResource,$headers,$payload)
    {
        $this->_helper->logDebug(__('curlResource:') . ' => ' . $curlResource );
        $this->_helper->logDebug(__('headers:').print_r($this->_helper->stripTrimFields($headers), true));
        $this->_helper->logDebug(__('payload:').print_r($this->_helper->stripTrimFields($payload), true));
    	
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $curlResource);
    	curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload, '', '&'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	$responseJson = curl_exec($ch);
    	if(curl_errno($ch)) {
    	    $this->_logger->critical(__('Gateway response curl error.'));
    	    curl_close($ch);
    		return false;
    	}
    	curl_close($ch);
    	$responseData = json_decode($responseJson,true);
    	if(is_array($responseData)){
    	    $this->_helper->logDebug(__('_sendRequest response:').print_r($this->_helper->stripTrimFields($responseData), true));
            return $responseData;
    	}
    	$this->_logger->critical(__('responseData is not an array'));
    	$this->_logger->critical(__( $responseJson ));
    	return false;
    }

    private function _getTransaction($payment)
    {
        $transaction = $this->_transactionRepository->getByTransactionId(
            $payment->getParentTransactionId(),
            $payment->getId(),
            $payment->getOrder()->getId()
        );

        return $transaction;
    }
}
