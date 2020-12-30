<?php

namespace TotalProcessing\TPCARDS\Model\Config\Source;

class DMFields implements \Magento\Framework\Option\ArrayInterface
{
    const DM_BILL_STR1 = 'TPCARDS_BILLING_STREET1';
    const DM_BILL_STR2 = 'TPCARDS_BILLING_STREET2';
    const DM_BILL_CITY = 'TPCARDS_BILLING_CITY';
    const DM_BILL_POSTAL = 'TPCARDS_BILLING_POSTALCODE';
    const DM_BILL_STATE = 'TPCARDS_BILLING_STATE';
    const DM_BILL_COUNTRY = 'BILLING_CO';

    const DM_SHIPPING_FIRST = 'TPCARDS_SHIPPING_FIRSTNAME';
    const DM_SHIPPING_LAST = 'TPCARDS_SHIPPING_LASTNAME';
    const DM_SHIPPING_PHONE = 'TPCARDS_SHIPPING_PHONE';
    const DM_SHIPPING_METHOD = 'TPCARDS_SHIPPING_SHIPPINGMETHOD';
    const DM_SHIPPING_STR1 = 'TPCARDS_SHIPPING_STREET1';
    const DM_SHIPPING_STR2 = 'TPCARDS_SHIPPING_STREET2';
    const DM_SHIPPING_CITY = 'TPCARDS_SHIPPING_CITY';
    const DM_SHIPPING_POSTAL = 'TPCARDS_SHIPPING_POSTALCODE';
    const DM_SHIPPING_STATE = 'TPCARDS_SHIPPING_STATE';
    const DM_SHIPPING_COUNTRY = 'SHIPPING_CO';

    const DM_CUSTOMER_ID = 'TPCARDS_CUSTOMER_ID';
    const DM_CUSTOMER_DOB = 'TPCARDS_CUSTOMER_DATEOFBIRTH';
    const DM_CUSTOMER_EMAIL_DOMAIN = 'TPCARDS_CUSTOMER_DOMAINNAME';
    const DM_CUSTOMER_EMAIL = 'TPCARDS_CUSTOMER_EMAIL';
    const DM_CUSTOMER_FIRST = 'TPCARDS_CUSTOMER_FIRSTNAME';
    const DM_CUSTOMER_LAST = 'TPCARDS_CUSTOMER_LASTNAME';
    const DM_CUSTOMER_PHONE = 'TPCARDS_CUSTOMER_PHONENUMBER';

    const DM_PRODUCTS_TOTAL = 'TPCARDS_PRODUCTS_UNITPRICE';

    const DM_FRAUD_HOST = 'TPCARDS_FRAUD_DM_BILLHOSTNAME';
    const DM_FRAUD_COOKIES = 'TPCARDS_FRAUD_DM_BILLHTTPBROWSERCOOKIESACCEPTED';
    const DM_FRAUD_BROWSER = 'TPCARDS_FRAUD_DM_BILLTOHTTPBROWSERTYPE';
    const DM_FRAUD_IP = 'TPCARDS_FRAUD_DM_BILLTOIPNETWORKADDRESS';
    const DM_FRAUD_TENDER = 'TPCARDS_FRAUD_DM_INVOICEHEADERTENDERTYPE';

    /**
     * Possible Decision Manager fields.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
          [
              'value' => self::DM_BILL_STR1,
              'label' => 'TPCARDS_BILLING_STREET1',
          ],
          [
              'value' => self::DM_BILL_STR2,
              'label' => 'TPCARDS_BILLING_STREET2',
          ],
          [
              'value' => self::DM_BILL_CITY,
              'label' => 'TPCARDS_BILLING_CITY',
          ],
          [
              'value' => self::DM_BILL_POSTAL,
              'label' => 'TPCARDS_BILLING_POSTALCODE',
          ],
          [
              'value' => self::DM_BILL_STATE,
              'label' => 'TPCARDS_BILLING_STATE',
          ],
          [
              'value' => self::DM_BILL_COUNTRY,
              'label' => 'BILLING_CO',
          ],
          [
              'value' => self::DM_SHIPPING_FIRST,
              'label' => 'TPCARDS_SHIPPING_FIRSTNAME',
          ],
          [
              'value' => self::DM_SHIPPING_LAST,
              'label' => 'TPCARDS_SHIPPING_LASTNAME',
          ],
          [
              'value' => self::DM_SHIPPING_PHONE,
              'label' => 'TPCARDS_SHIPPING_PHONE',
          ],
          [
              'value' => self::DM_SHIPPING_METHOD,
              'label' => 'TPCARDS_SHIPPING_SHIPPINGMETHOD',
          ],
          [
              'value' => self::DM_SHIPPING_STR1,
              'label' => 'TPCARDS_SHIPPING_STREET1',
          ],
          [
              'value' => self::DM_SHIPPING_STR2,
              'label' => 'TPCARDS_SHIPPING_STREET2',
          ],

          [
              'value' => self::DM_SHIPPING_CITY,
              'label' => 'TPCARDS_SHIPPING_CITY',
          ],
          [
              'value' => self::DM_SHIPPING_POSTAL,
              'label' => 'TPCARDS_SHIPPING_POSTALCODE',
          ],
          [
              'value' => self::DM_SHIPPING_STATE,
              'label' => 'TPCARDS_SHIPPING_STATE',
          ],
          [
              'value' => self::DM_SHIPPING_COUNTRY,
              'label' => 'SHIPPING_CO',
          ],
          [
              'value' => self::DM_CUSTOMER_ID,
              'label' => 'TPCARDS_CUSTOMER_ID',
          ],
          [
              'value' => self::DM_CUSTOMER_DOB,
              'label' => 'TPCARDS_CUSTOMER_DATEOFBIRTH',
          ],
          [
              'value' => self::DM_CUSTOMER_EMAIL_DOMAIN,
              'label' => 'TPCARDS_CUSTOMER_DOMAINNAME',
          ],
          [
              'value' => self::DM_CUSTOMER_EMAIL,
              'label' => 'TPCARDS_CUSTOMER_EMAIL',
          ],
          [
              'value' => self::DM_CUSTOMER_FIRST,
              'label' => 'TPCARDS_CUSTOMER_FIRSTNAME',
          ],
          [
              'value' => self::DM_CUSTOMER_LAST,
              'label' => 'TPCARDS_CUSTOMER_LASTNAME',
          ],
          [
              'value' => self::DM_CUSTOMER_PHONE,
              'label' => 'TPCARDS_CUSTOMER_PHONENUMBER',
          ],
          [
              'value' => self::DM_PRODUCTS_TOTAL,
              'label' => 'TPCARDS_PRODUCTS_UNITPRICE',
          ],
          [
              'value' => self::DM_FRAUD_HOST,
              'label' => 'TPCARDS_FRAUD_DM_BILLHOSTNAME',
          ],
          [
              'value' => self::DM_FRAUD_COOKIES,
              'label' => 'TPCARDS_FRAUD_DM_BILLHTTPBROWSERCOOKIESACCEPTED',
          ],
          [
              'value' => self::DM_FRAUD_BROWSER,
              'label' => 'TPCARDS_FRAUD_DM_BILLTOHTTPBROWSERTYPE',
          ],
          [
              'value' => self::DM_FRAUD_IP,
              'label' => 'TPCARDS_FRAUD_DM_BILLTOIPNETWORKADDRESS',
          ],
          [
              'value' => self::DM_FRAUD_TENDER,
              'label' => 'TPCARDS_FRAUD_DM_INVOICEHEADERTENDERTYPE',
          ],
        ];
    }
}
