<?php

namespace TotalProcessing\TPCARDS\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use TotalProcessing\TPCARDS\Model\Config\Source\Environment;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const METHOD_CODE = 'totalprocessing_tpcards';
    const CUSTOMER_ID = 'customer';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $_encryptor;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    private $_country;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    private $_moduleList;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $_quoteRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \TotalProcessing\TPCARDS\Logger\Logger
     */
    private $_realexLogger;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $_productMetadata;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    private $_resourceInterface;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $_resolver;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_session;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context             $context
     * @param \Magento\Framework\Encryption\EncryptorInterface  $encryptor
     * @param \Magento\Directory\Model\Config\Source\Country    $country
     * @param \Magento\Quote\Api\CartRepositoryInterface        $quoteRepository
     * @param \Magento\Framework\Module\ModuleListInterface     $moduleList
     * @param \Magento\Store\Model\StoreManagerInterface        $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface   $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface       $resourceInterface
     * @param \Magento\Framework\Locale\ResolverInterface       $resolver
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session                   $session
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \TotalProcessing\TPCARDS\Logger\Logger $realexLogger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $resourceInterface,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $session
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
        $this->_country = $country;
        $this->_moduleList = $moduleList;
        $this->_quoteRepository = $quoteRepository;
        $this->_storeManager = $storeManager;
        $this->_realexLogger = $realexLogger;
        $this->_productMetadata = $productMetadata;
        $this->_resourceInterface = $resourceInterface;
        $this->_resolver = $resolver;
        $this->_customerRepository = $customerRepository;
        $this->_session = $session;
    }

    public function getFrameDomainUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    /**
     * @desc Sign fields
     *
     * @return string
     */
    public function signFields($fields, $account = null)
    {
        //do we need to use a specific config
        if (!isset($account)) {
            $account = 'shared_secret';
        }
        $secret = $this->getEncryptedConfigData($account);
        $sha1hash = sha1($fields);
        $tmp = "$sha1hash.$secret";

        return sha1($tmp);
    }

    /**
     * @desc Check if configuration is set to sandbox mode
     *
     * @return bool
     */
    public function isSandboxMode()
    {
        return $this->getConfigData('environment') == Environment::ENVIRONMENT_SANDBOX;
    }
    
    /**
     * @desc Get tpcards entityId
     *
     * @return string
     */
    public function getTpEntityId($environment)
    {
        if($environment === 'sandbox'){
            
            return $this->getConfigData('entityId_test');
            
        }

        return $this->getConfigData('entityId');
        
    }
    
    public function getTpHeaderAuth($environment)
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
        ];
        
        if($environment === 'sandbox'){
            $headers[] = 'Authorization: Bearer ' . $this->getConfigData('accessToken_test');
        } else {
            $headers[] = 'Authorization: Bearer ' . $this->getConfigData('accessToken');
        }
        
        return $headers;
    }
    
    public function getTpPlatformBase($environment)
    {
        if($environment === 'sandbox'){
            return 'https://test.oppwa.com';
        }
        return 'https://oppwa.com';
    }
    

    /**
     * @desc Get tpcards form url
     *
     * @return string
     */
    public function getFormUrl()
    {
        if ($this->isSandboxMode()) {
            return $this->getConfigData('sandbox_payment_url');
        }

        return $this->getConfigData('live_payment_url');
    }

    /**
     * @desc Get remote api url
     *
     * @return string
     */
    public function getRemoteApiUrl()
    {
        if ($this->isSandboxMode()) {
            return $this->getConfigData('sandbox_api_url');
        }

        return $this->getConfigData('live_api_url');
    }

    /**
     * @desc Sets all the fields that is posted to TPCARDS for a OTB Card transaction
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOTBFormFields()
    {
        if (!$this->_session->isLoggedIn()) {
            return [];
        }

        $timestamp = strftime('%Y%m%d%H%M%S');
        $merchantId = trim($this->getConfigData('merchant_id'));
        $merchantAccount = trim($this->getConfigData('merchant_account'));
        $fieldOrderId = uniqid().'_'.$timestamp;
        $orderCurrencyCode = $this->_storeManager->getStore()->getBaseCurrency()->getCode();
        $amount = 0;
        $customerId = $this->_session->getCustomer()->getId();
        $settleMode = $this->getConfigData('settle_mode');
        $autoSettle = ($settleMode == \TotalProcessing\TPCARDS\Model\Config\Source\SettleMode::SETTLEMODE_AUTO) ? '1' : '0';
        $cardPaymentText = $this->getConfigData('card_btn_text');
        $realexLang = $this->getConfigData('lang');
        $varRef = self::CUSTOMER_ID.'_'.$customerId;
        $prodId = '';
        $shopperLocale = $this->_resolver->getLocale();
        $otbEnabled = true;
        $iframeEnabled = '1';

        $formFields = [];
        $formFields['MERCHANT_ID'] = $merchantId;
        $formFields['ACCOUNT'] = $merchantAccount;
        $formFields['ORDER_ID'] = $fieldOrderId;
        $formFields['AMOUNT'] = $amount;
        $formFields['CURRENCY'] = $orderCurrencyCode;
        $formFields['TIMESTAMP'] = $timestamp;
        $formFields['AUTO_SETTLE_FLAG'] = $autoSettle;
        $formFields['CUST_NUM'] = $customerId;
        $formFields['VAR_REF'] = $varRef;
        $formFields['PROD_ID'] = $prodId;
        $formFields['TPCARDS_VERSION'] = '2';
        if (isset($realexLang) && !empty($realexLang)) {
            $formFields['TPCARDS_LANG'] = $realexLang;
        }
        if (isset($cardPaymentText) && !empty($cardPaymentText)) {
            $formFields['CARD_PAYMENT_BUTTON'] = $cardPaymentText;
        }
        if (isset($paymentMethods) && !empty($paymentMethods)) {
            $formFields['PM_METHODS'] = $paymentMethods;
        }
        if ($otbEnabled) {
            $formFields['VALIDATE_CARD_ONLY'] = '1';
        }
        $baseUrl = $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);

        if ($iframeEnabled) {
            $formFields['TPCARDS_POST_DIMENSIONS'] = $baseUrl;
        }

        $formFields['MERCHANT_RESPONSE_URL'] = $baseUrl.'totalprocessing_tpcards/cards/result';

        //Load payer ref customer attribute
        $payerAttr = $this->_customerRepository->getById($customerId)
                    ->getCustomAttribute('totalprocessing_tpcards_payerref');
        $payerRef = (isset($payerAttr) && $payerAttr != null) ? $payerAttr->getValue() : '';

        $formFields = $this->setCardStorageFields($formFields, $payerRef);
        $fieldsToSign = "$timestamp.$merchantId.$fieldOrderId.$amount.$orderCurrencyCode.$payerRef.";

        $sha1hash = $this->signFields($fieldsToSign);
        $this->logDebug('Gateway Request:'.print_r($this->stripFields($formFields), true));

        $formFields['SHA1HASH'] = $sha1hash;
        // Sort the array by key using SORT_STRING order
        ksort($formFields, SORT_STRING);

        return $formFields;
    }

    /**
     * Set Card Storage Fields.
     *
     * @param array  $formFields
     * @param string $payerRef
     *
     * @return $array
     */
    public function setCardStorageFields($formFields, $payerRef)
    {
        if (!isset($payerRef) || $payerRef == '') {
            $formFields['CARD_STORAGE_ENABLE'] = '1';
            $formFields['PAYER_EXIST'] = '0';
            $formFields['PAYER_REF'] = '';
            $formFields['PMT_REF'] = '';
        } else {
            $formFields['TPCARDS_SELECT_STORED_CARD'] = $payerRef;
            $formFields['PAYER_EXIST'] = '1';
        }
        $formFields['OFFER_SAVE_CARD'] = $this->getConfigData('card_offer_save');

        return $formFields;
    }

    public function getDMSessionId()
    {
        $sessionId = $this->_session->getData('DM_SessionId');
        if (!isset($sessionId) || empty($sessionId)) {
            $sessionId = uniqid();
            $this->_session->setData('DM_SessionId', $sessionId);
        }

        return $sessionId;
    }

    /**
     * @desc Logs debug information if enabled
     *
     * @param mixed
     */
    public function logDebug($message)
    {
        if ($this->getConfigData('debug_log') == '1') {
            $this->_realexLogger->debug($message);
        }
    }

    /**
     * @desc Cancels the order
     *
     * @param \Magento\Sales\Mode\Order $order
     */
    public function cancelOrder($order)
    {
        $orderStatus = $this->getConfigData('payment_cancelled');
        $order->setActionFlag($orderStatus, true);
        $order->cancel()->save();
    }

    /**
     * @desc Load a quote based on id
     *
     * @param $quoteId
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote($quoteId)
    {
        // get quote from quoteId
        $quote = $this->_quoteRepository->get($quoteId);

        return $quote;
    }

    /**
     * @desc Removes the response fields that we don't want stored
     *
     * @param array $response
     *
     * @return array
     */
    public function stripFields($response)
    {
        if ($this->isSandboxMode()) {
            //return $response;
        }
        $returnedFields = [];
        $excludedFields = [];
        foreach ($response as $key => $field) {
            if(is_array($field)){
                continue;
            }
            if (!in_array(strtoupper($key), $excludedFields)) {
                $returnedFields[$key] = $field;
            }
        }

        return $returnedFields;
    }

    /**
     * @desc Trims the response card digits field to only contain the last 4
     *
     * @param array $response
     *
     * @return array
     */
    public function trimCardDigits($response)
    {
        if (isset($response['CARDDIGITS']) && strlen($response['CARDDIGITS']) > 4) {
            $response['CARDDIGITS'] = substr($response['CARDDIGITS'], -4);
        }
        if (isset($response['SAVED_PMT_DIGITS']) && strlen($response['SAVED_PMT_DIGITS']) > 4) {
            $response['SAVED_PMT_DIGITS'] = substr($response['SAVED_PMT_DIGITS'], -4);
        }

        return $response;
    }

    /**
     * @desc Strips and trims the response and returns a new array of fields
     *
     * @param array $response
     *
     * @return array
     */
    public function stripTrimFields($response)
    {
        $fields = $this->stripFields($response);

        return $this->trimCardDigits($fields);
    }

    /**
     * @desc Strips and trims the xml and returns the new xml
     *
     * @param string $xml
     *
     * @return string
     */
    public function stripXML($xml)
    {
        $patterns = ['/(<sha1hash>).+(<\/sha1hash>)/',
                      '/(<md5hash>).+(<\/md5hash>)/',
                      '/(<refundhash>).+(<\/refundhash>)/', ];

        return preg_replace($patterns, '', $xml);
    }

    /**
     * @desc Converts the magento decimal amount into a int one used by Realex
     *
     * @param float  $amount
     * @param string $currencyCode
     *
     * @return int
     */
    public function amountFromMagento($amount, $currencyCode)
    {
        $minor = $this->_getCurrencyMinorUnit($currencyCode);

        return round($amount * $minor);
    }

    /**
     * @desc Converts the realex int amount into a decimal one used by Realex
     *
     * @param string $amount
     * @param string $currencyCode
     *
     * @return float
     */
    public function amountFromRealex($amount, $currencyCode)
    {
        $minor = $this->_getCurrencyMinorUnit($currencyCode);

        return floatval($amount) / $minor;
    }

    /**
     * @desc Gets the amount of currency minor units. This would be used to divide or
     * multiply with. eg. cents with 2 minor units would mean 10^2 = 100
     *
     * @param string $currencyCode
     *
     * @return int
     */
    private function _getCurrencyMinorUnit($currencyCode)
    {
        if ($this->checkForFirstMinorUnit($currencyCode)) {
            return 1;
        }
        switch ($currencyCode) {
            case 'BHD':
            case 'IQD':
            case 'JOD':
            case 'KWD':
            case 'LYD':
            case 'OMR':
            case 'TND':
                return 1000;
            case 'CLF':
                return 10000;
        }

        return 100;
    }

    private function checkForFirstMinorUnit($currencyCode)
    {
        return in_array($currencyCode, ['BYR', 'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'KMF',
            'KRW', 'PYG', 'RWF', 'UGX', 'UYI', 'VUV', 'VND', 'XAF', 'XOF', 'XPF', ]);
    }

    /**
     * @desc Sets additional information fields on the payment class
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param array                              $response
     */
    public function setAdditionalInfo($payment, $response)
    {
        $fields = [];
        foreach ($response as $respK => $respV){
            if(is_array($respV)){
                foreach($respV as $subK => $subV){
                    if(!is_array($subV)){
                        $fields[$respK.'.'.$subK] = $subV;
                    }
                }
            }
            $fields[$respK] = $respV;
        }
        foreach ($fields as $key => $value) {
            $payment->setAdditionalInformation($key, $value);
        }
    }

    /**
     * @desc Gives back configuration values
     *
     * @param $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->getConfig($field, self::METHOD_CODE, $storeId);
    }

    /**
     * @desc Gives back configuration values as flag
     *
     * @param $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigDataFlag($field, $storeId = null)
    {
        return $this->getConfig($field, self::METHOD_CODE, $storeId, true);
    }

    /**
     * @desc Gives back encrypted configuration values
     *
     * @param $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEncryptedConfigData($field, $storeId = null)
    {
        return $this->_encryptor->decrypt(trim($this->getConfigData($field, $storeId)));
    }

    /**
     * @desc Retrieve information from payment configuration
     *
     * @param $field
     * @param $paymentMethodCode
     * @param $storeId
     * @param bool|false $flag
     *
     * @return bool|mixed
     */
    public function getConfig($field, $paymentMethodCode, $storeId, $flag = false)
    {
        $path = 'payment/'.$paymentMethodCode.'/'.$field;
        if (null === $storeId) {
            $storeId = $this->_storeManager->getStore();
        }

        if (!$flag) {
            return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->scopeConfig->isSetFlag($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        }
    }
}
