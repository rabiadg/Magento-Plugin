<?php

namespace TotalProcessing\TPCARDS\Model;

use Magento\Sales\Model\Order\Address;
use TotalProcessing\TPCARDS\Model\Config\Source\ChallengePreference;
use TotalProcessing\TPCARDS\Model\Config\Source\DMFields;
use TotalProcessing\TPCARDS\Model\Config\Source\FraudMode;
use TotalProcessing\TPCARDS\Model\Config\Source\SettleMode;
use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{
    const METHOD_CODE = 'totalprocessing_tpcards';
    const NOT_AVAILABLE = 'N/A';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var GUEST_ID , used when order is placed by guests
     */
    const GUEST_ID = 'guest';
    /**
     * @var CUSTOMER_ID , used when order is placed by customers
     */
    const CUSTOMER_ID = 'customer';

    /**
     * @var string
     */
    protected $_infoBlockType = 'TotalProcessing\TPCARDS\Block\Info\Info';

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * @var bool
     */
    protected $_canCaptureOnce = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * @var bool
     */
    protected $_canReviewPayment = true;

    /**
     * @var \TotalProcessing\TPCARDS\Helper\Data
     */
    private $_helper;

    /**
     * @var \TotalProcessing\TPCARDS\API\RemoteXMLInterface
     */
    private $_remoteXml;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $_urlBuilder;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $_resolver;

    /**
     * @var \TotalProcessing\TPCARDS\Logger\Logger
     */
    private $_realexLogger;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadata;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $_resourceInterface;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\App\RequestInterface                      $request
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \TotalProcessing\TPCARDS\Helper\Data                              $helper
     * @param \TotalProcessing\TPCARDS\API\RemoteXMLInterface                   $remoteXml
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface                  $resolver
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \TotalProcessing\TPCARDS\Logger\Logger                            $realexLogger
     * @param \Magento\Framework\App\ProductMetadataInterface              $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface                  $resourceInterface
     * @param \Magento\Checkout\Model\Session                              $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface            $customerRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        \TotalProcessing\TPCARDS\Helper\Data $helper,
        \TotalProcessing\TPCARDS\API\RemoteXMLInterface $remoteXml,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \TotalProcessing\TPCARDS\Logger\Logger $realexLogger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $resourceInterface,
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
        $this->_remoteXml = $remoteXml;
        $this->_storeManager = $storeManager;
        $this->_resolver = $resolver;
        $this->_request = $request;
        $this->_realexLogger = $realexLogger;
        $this->_productMetadata = $productMetadata;
        $this->_resourceInterface = $resourceInterface;
        $this->_session = $session;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /*
         * do not send order confirmation mail after order creation wait for
         * result confirmation from total processing
         */
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $stateObject->setStatus($this->_helper->getConfigData('order_status'));
        $stateObject->setIsNotified(false);
    }

    /**
     * Assign data to info model instance.
     *
     * @param \Magento\Framework\DataObject|mixed $data
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $additionalData = $data->getAdditionalData();
        $infoInstance = $this->getInfoInstance();

        return $this;
    }

    /**
     * Checkout redirect URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl(
            'totalprocessing_tpcards/process/frame/',
            ['_secure' => $this->_getRequest()->isSecure()]
        );
    }

    /**
     * Get getOriginDomainUrl URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    public function getOriginDomainUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Retrieve request object.
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function _getRequest()
    {
        return $this->_request;
    }

    /**
     * Post request to gateway and return response.
     *
     * @param DataObject      $request
     * @param ConfigInterface $config
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        // Do nothing
        $this->_helper->logDebug('Gateway postRequest called');
    }

    /**
     * @desc Get tpcards form url
     *
     * @return string
     */
    public function getFormUrl()
    {
        return $this->_helper->getFormUrl();
    }

    /**
     * @desc Sets all the fields that is posted to TPCARDS
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormFields()
    {
        return [];
    }
    
    
    

    //jeans Model::logger
	
	private function writeLogTpcardsModel($obj){
    	if(is_object($obj) === true || is_array($obj) === true){
    	    $obj = json_encode($obj);
    	}
        $filename = dirname( __FILE__ ).'/'.date('Ymd').'.log';
        $fh = fopen($filename, "a+");
        if(!$fh){
            return false;
        }
        fwrite($fh, date('H:i:s').' => '.$obj."\n");
        fclose($fh);
        return true;
    }
    

	private function getCountryPhoneCodes()
	{
		return [
			'AD' => '376',
			'AE' => '971',
			'AF' => '93',
			'AG' => '1268',
			'AI' => '1264',
			'AL' => '355',
			'AM' => '374',
			'AN' => '599',
			'AO' => '244',
			'AQ' => '672',
			'AR' => '54',
			'AS' => '1684',
			'AT' => '43',
			'AU' => '61',
			'AW' => '297',
			'AZ' => '994',
			'BA' => '387',
			'BB' => '1246',
			'BD' => '880',
			'BE' => '32',
			'BF' => '226',
			'BG' => '359',
			'BH' => '973',
			'BI' => '257',
			'BJ' => '229',
			'BL' => '590',
			'BM' => '1441',
			'BN' => '673',
			'BO' => '591',
			'BR' => '55',
			'BS' => '1242',
			'BT' => '975',
			'BW' => '267',
			'BY' => '375',
			'BZ' => '501',
			'CA' => '1',
			'CC' => '61',
			'CD' => '243',
			'CF' => '236',
			'CG' => '242',
			'CH' => '41',
			'CI' => '225',
			'CK' => '682',
			'CL' => '56',
			'CM' => '237',
			'CN' => '86',
			'CO' => '57',
			'CR' => '506',
			'CU' => '53',
			'CV' => '238',
			'CX' => '61',
			'CY' => '357',
			'CZ' => '420',
			'DE' => '49',
			'DJ' => '253',
			'DK' => '45',
			'DM' => '1767',
			'DO' => '1809',
			'DZ' => '213',
			'EC' => '593',
			'EE' => '372',
			'EG' => '20',
			'ER' => '291',
			'ES' => '34',
			'ET' => '251',
			'FI' => '358',
			'FJ' => '679',
			'FK' => '500',
			'FM' => '691',
			'FO' => '298',
			'FR' => '33',
			'GA' => '241',
			'GB' => '44',
			'GD' => '1473',
			'GE' => '995',
			'GH' => '233',
			'GI' => '350',
			'GL' => '299',
			'GM' => '220',
			'GN' => '224',
			'GQ' => '240',
			'GR' => '30',
			'GT' => '502',
			'GU' => '1671',
			'GW' => '245',
			'GY' => '592',
			'HK' => '852',
			'HN' => '504',
			'HR' => '385',
			'HT' => '509',
			'HU' => '36',
			'ID' => '62',
			'IE' => '353',
			'IL' => '972',
			'IM' => '44',
			'IN' => '91',
			'IQ' => '964',
			'IR' => '98',
			'IS' => '354',
			'IT' => '39',
			'JM' => '1876',
			'JO' => '962',
			'JP' => '81',
			'KE' => '254',
			'KG' => '996',
			'KH' => '855',
			'KI' => '686',
			'KM' => '269',
			'KN' => '1869',
			'KP' => '850',
			'KR' => '82',
			'KW' => '965',
			'KY' => '1345',
			'KZ' => '7',
			'LA' => '856',
			'LB' => '961',
			'LC' => '1758',
			'LI' => '423',
			'LK' => '94',
			'LR' => '231',
			'LS' => '266',
			'LT' => '370',
			'LU' => '352',
			'LV' => '371',
			'LY' => '218',
			'MA' => '212',
			'MC' => '377',
			'MD' => '373',
			'ME' => '382',
			'MF' => '1599',
			'MG' => '261',
			'MH' => '692',
			'MK' => '389',
			'ML' => '223',
			'MM' => '95',
			'MN' => '976',
			'MO' => '853',
			'MP' => '1670',
			'MR' => '222',
			'MS' => '1664',
			'MT' => '356',
			'MU' => '230',
			'MV' => '960',
			'MW' => '265',
			'MX' => '52',
			'MY' => '60',
			'MZ' => '258',
			'NA' => '264',
			'NC' => '687',
			'NE' => '227',
			'NG' => '234',
			'NI' => '505',
			'NL' => '31',
			'NO' => '47',
			'NP' => '977',
			'NR' => '674',
			'NU' => '683',
			'NZ' => '64',
			'OM' => '968',
			'PA' => '507',
			'PE' => '51',
			'PF' => '689',
			'PG' => '675',
			'PH' => '63',
			'PK' => '92',
			'PL' => '48',
			'PM' => '508',
			'PN' => '870',
			'PR' => '1',
			'PT' => '351',
			'PW' => '680',
			'PY' => '595',
			'QA' => '974',
			'RO' => '40',
			'RS' => '381',
			'RU' => '7',
			'RW' => '250',
			'SA' => '966',
			'SB' => '677',
			'SC' => '248',
			'SD' => '249',
			'SE' => '46',
			'SG' => '65',
			'SH' => '290',
			'SI' => '386',
			'SK' => '421',
			'SL' => '232',
			'SM' => '378',
			'SN' => '221',
			'SO' => '252',
			'SR' => '597',
			'ST' => '239',
			'SV' => '503',
			'SY' => '963',
			'SZ' => '268',
			'TC' => '1649',
			'TD' => '235',
			'TG' => '228',
			'TH' => '66',
			'TJ' => '992',
			'TK' => '690',
			'TL' => '670',
			'TM' => '993',
			'TN' => '216',
			'TO' => '676',
			'TR' => '90',
			'TT' => '1868',
			'TV' => '688',
			'TW' => '886',
			'TZ' => '255',
			'UA' => '380',
			'UG' => '256',
			'US' => '1',
			'UY' => '598',
			'UZ' => '998',
			'VA' => '39',
			'VC' => '1784',
			'VE' => '58',
			'VG' => '1284',
			'VI' => '1340',
			'VN' => '84',
			'VU' => '678',
			'WF' => '681',
			'WS' => '685',
			'XK' => '381',
			'YE' => '967',
			'YT' => '262',
			'ZA' => '27',
			'ZM' => '260',
			'ZW' => '263',
		];
	}

	/**
	 * @return array
	 */
	private function getCountryNumericCodes()
	{
		return array(
			'AF' => '004',
			'AX' => '248',
			'AL' => '008',
			'DZ' => '012',
			'AS' => '016',
			'AD' => '020',
			'AO' => '024',
			'AI' => '660',
			'AQ' => '010',
			'AG' => '028',
			'AR' => '032',
			'AM' => '051',
			'AW' => '533',
			'AU' => '036',
			'AT' => '040',
			'AZ' => '031',
			'BS' => '044',
			'BH' => '048',
			'BD' => '050',
			'BB' => '052',
			'BY' => '112',
			'BE' => '056',
			'BZ' => '084',
			'BJ' => '204',
			'BM' => '060',
			'BT' => '064',
			'BO' => '068',
			'BQ' => '535',
			'BA' => '070',
			'BW' => '072',
			'BV' => '074',
			'BR' => '076',
			'IO' => '086',
			'BN' => '096',
			'BG' => '100',
			'BF' => '854',
			'BI' => '108',
			'CV' => '132',
			'KH' => '116',
			'CM' => '120',
			'CA' => '124',
			'KY' => '136',
			'CF' => '140',
			'TD' => '148',
			'CL' => '152',
			'CN' => '156',
			'CX' => '162',
			'CC' => '166',
			'CO' => '170',
			'KM' => '174',
			'CG' => '178',
			'CD' => '180',
			'CK' => '184',
			'CR' => '188',
			'CI' => '384',
			'HR' => '191',
			'CU' => '192',
			'CW' => '531',
			'CY' => '196',
			'CZ' => '203',
			'DK' => '208',
			'DJ' => '262',
			'DM' => '212',
			'DO' => '214',
			'EC' => '218',
			'EG' => '818',
			'SV' => '222',
			'GQ' => '226',
			'ER' => '232',
			'EE' => '233',
			'ET' => '231',
			'SZ' => '748',
			'FK' => '238',
			'FO' => '234',
			'FJ' => '242',
			'FI' => '246',
			'FR' => '250',
			'GF' => '254',
			'PF' => '258',
			'TF' => '260',
			'GA' => '266',
			'GM' => '270',
			'GE' => '268',
			'DE' => '276',
			'GH' => '288',
			'GI' => '292',
			'GR' => '300',
			'GL' => '304',
			'GD' => '308',
			'GP' => '312',
			'GU' => '316',
			'GT' => '320',
			'GG' => '831',
			'GN' => '324',
			'GW' => '624',
			'GY' => '328',
			'HT' => '332',
			'HM' => '334',
			'VA' => '336',
			'HN' => '340',
			'HK' => '344',
			'HU' => '348',
			'IS' => '352',
			'IN' => '356',
			'ID' => '360',
			'IR' => '364',
			'IQ' => '368',
			'IE' => '372',
			'IM' => '833',
			'IL' => '376',
			'IT' => '380',
			'JM' => '388',
			'JP' => '392',
			'JE' => '832',
			'JO' => '400',
			'KZ' => '398',
			'KE' => '404',
			'KI' => '296',
			'KP' => '408',
			'KR' => '410',
			'KW' => '414',
			'KG' => '417',
			'LA' => '418',
			'LV' => '428',
			'LB' => '422',
			'LS' => '426',
			'LR' => '430',
			'LY' => '434',
			'LI' => '438',
			'LT' => '440',
			'LU' => '442',
			'MO' => '446',
			'MK' => '807',
			'MG' => '450',
			'MW' => '454',
			'MY' => '458',
			'MV' => '462',
			'ML' => '466',
			'MT' => '470',
			'MH' => '584',
			'MQ' => '474',
			'MR' => '478',
			'MU' => '480',
			'YT' => '175',
			'MX' => '484',
			'FM' => '583',
			'MD' => '498',
			'MC' => '492',
			'MN' => '496',
			'ME' => '499',
			'MS' => '500',
			'MA' => '504',
			'MZ' => '508',
			'MM' => '104',
			'NA' => '516',
			'NR' => '520',
			'NP' => '524',
			'NL' => '528',
			'NC' => '540',
			'NZ' => '554',
			'NI' => '558',
			'NE' => '562',
			'NG' => '566',
			'NU' => '570',
			'NF' => '574',
			'MP' => '580',
			'NO' => '578',
			'OM' => '512',
			'PK' => '586',
			'PW' => '585',
			'PS' => '275',
			'PA' => '591',
			'PG' => '598',
			'PY' => '600',
			'PE' => '604',
			'PH' => '608',
			'PN' => '612',
			'PL' => '616',
			'PT' => '620',
			'PR' => '630',
			'QA' => '634',
			'RE' => '638',
			'RO' => '642',
			'RU' => '643',
			'RW' => '646',
			'BL' => '652',
			'SH' => '654',
			'KN' => '659',
			'LC' => '662',
			'MF' => '663',
			'PM' => '666',
			'VC' => '670',
			'WS' => '882',
			'SM' => '674',
			'ST' => '678',
			'SA' => '682',
			'SN' => '686',
			'RS' => '688',
			'SC' => '690',
			'SL' => '694',
			'SG' => '702',
			'SX' => '534',
			'SK' => '703',
			'SI' => '705',
			'SB' => '090',
			'SO' => '706',
			'ZA' => '710',
			'GS' => '239',
			'SS' => '728',
			'ES' => '724',
			'LK' => '144',
			'SD' => '729',
			'SR' => '740',
			'SJ' => '744',
			'SE' => '752',
			'CH' => '756',
			'SY' => '760',
			'TW' => '158',
			'TJ' => '762',
			'TZ' => '834',
			'TH' => '764',
			'TL' => '626',
			'TG' => '768',
			'TK' => '772',
			'TO' => '776',
			'TT' => '780',
			'TN' => '788',
			'TR' => '792',
			'TM' => '795',
			'TC' => '796',
			'TV' => '798',
			'UG' => '800',
			'UA' => '804',
			'AE' => '784',
			'GB' => '826',
			'US' => '840',
			'UM' => '581',
			'UY' => '858',
			'UZ' => '860',
			'VU' => '548',
			'VE' => '862',
			'VN' => '704',
			'VG' => '092',
			'VI' => '850',
			'WF' => '876',
			'EH' => '732',
			'YE' => '887',
			'ZM' => '894',
			'ZW' => '716',
		);
	}

	/**
	 * @param $alpha2
	 *
	 * @return mixed|string
	 */
	private function getCountryNumericCode($alpha2)
	{
		$countries = $this->getCountryNumericCodes();

		return isset($countries[$alpha2]) ? $countries[$alpha2] : '';
	}

    /**
     * Set Alternate Payment Method Fields.
     *
     * @param array                                   $formFields
     * @param \Magento\Sales\Model\Order\Address|null $shipping
     *
     * @return $array
     */
    private function setAPMFields($formFields, $shipping)
    {
        return [];
    }

    private function setDMFields($formFields, $order)
    {
        return [];
    }

    private function setDMBilling($formFields, $order, $fields)
    {
        return [];
    }

    private function setDMShipping($formFields, $order, $fields)
    {
        return [];
    }

    private function setDMCustomer($formFields, $order, $fields)
    {
        return [];
    }

    /**
     * Set Card Storage Fields.
     *
     * @param array  $formFields
     * @param string $payerRef
     *
     * @return $array
     */
    private function setCardStorageFields($formFields, $payerRef)
    {
        return $this->_helper->setCardStorageFields($formFields, $payerRef);
    }

    /**
     * Capture.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);
        return $this;
    }

    /**
     * Refund specified amount for payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_helper->logDebug('Refund postRequest called');

        parent::refund($payment, $amount);
        $order = $payment->getOrder();
        $comments = $payment->getCreditMemo()->getComments();
        $grandTotal = $order->getBaseGrandTotal();

        if ($grandTotal == $amount) {
            $response = $this->_remoteXml->rebate($payment, $amount, $comments);
        } else {
            //partial rebate
            $response = $this->_remoteXml->rebate($payment, $amount, $comments);
        }
        if (!isset($response) || !$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action failed'));
        }

        if (!in_array($response['result']['code'],['000.000.000','000.100.110'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The refund action failed. Gateway Response - Error '.$response['result']['code'].': '.
                $response['result']['description'])
            );
        }
        $payment->setTransactionId($response['id'])
                ->setParentTransactionId($payment->getAdditionalInformation('id'))
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, 
                $this->_helper->stripFields($response));
        
        return $this;
    }

    /**
     * Refund specified amount for payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::void($payment);
        return $this;
    }

    /**
     * Accept under review payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::acceptPayment($payment);
        return $this;
    }

    public function hold(\Magento\Payment\Model\InfoInterface $payment)
    {
        $response = $this->_remoteXml->holdPayment($payment, []);
        return $this;
    }
}
