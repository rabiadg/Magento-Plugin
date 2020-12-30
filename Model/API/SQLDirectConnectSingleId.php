<?php

namespace TotalProcessing\TPCARDS\Model\API;
use Magento\Framework\Config\ConfigOptionsListConstants;

//implements \Magento\Framework\Setup\InstallSchemaInterface

class SQLDirectConnectSingleId {

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig  $deploymentConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Get mysql installation table prefix 
     */
    public function getTablePrefix()
    {
        return $this->deploymentConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX
        );
    }

    public function drawQuotedTransaction2($id,$enforceRg='false')
    {
        $id = (int)$id;
        if( $id < 1 || is_numeric($id) !== true){
            return false;
        }
        $dbPrefix = $this->getTablePrefix();
        $query="SELECT
        JSON_OBJECT(
            'merchantTransactionId',qu.reserved_order_id,
            'merchantInvoiceId',qu.entity_id,
            'paymentType','PA',
            'amount',REPLACE(FORMAT(qu.grand_total, 2),\",\",\"\"),
            'currency',qu.quote_currency_code,
            'card.holder',TRIM(CONCAT_WS(\" \",IFNULL(COALESCE(a1.prefix,a2.prefix,qu.customer_prefix),\"\"),IFNULL(COALESCE(a1.firstname,a2.firstname,qu.customer_firstname),\"\"),IFNULL(COALESCE(a1.lastname,a2.lastname,qu.customer_lastname),\"\"))),
            'customer.companyName',COALESCE(a1.company,IF(a2.same_as_billing=1,a2.company,NULL)),
            'customer.merchantCustomerId',IFNULL(qu.customer_id,'guest'),
            'customer.givenName',IFNULL(qu.customer_firstname ,IFNULL(a1.firstname , a2.firstname)),
            'customer.surname',IFNULL(qu.customer_lastname ,IFNULL(a1.lastname , a2.lastname)),
            'customer.mobile',COALESCE(a1.telephone,a2.telephone),
            'customer.email',COALESCE(qu.customer_email,a1.email,a2.email),
            'customer.ip',qu.remote_ip,
            'billing.street1',SUBSTRING_INDEX(SUBSTRING_INDEX(a1.street, \"\n\" , 1), \"\n\", -1),
            'billing.street2',IF( SUBSTRING_INDEX(SUBSTRING_INDEX(a1.street, \"\n\" , 1), \"\n\", -1) = SUBSTRING_INDEX(SUBSTRING_INDEX(a1.street, \"\n\" , 2), \"\n\", -1),NULL,SUBSTRING_INDEX(SUBSTRING_INDEX(a1.street, \"\n\" , 2), \"\n\", -1)),
            'billing.city',a1.city,
            'billing.state',IF(a1.country_id IN (\"US\",\"CA\"), a1.region, NULL),
            'billing.postcode',UPPER(a1.postcode),
            'billing.country',a1.country_id,
            'shipping.street1',IF(a2.same_as_billing=1,NULL,SUBSTRING_INDEX(SUBSTRING_INDEX(a2.street, \"\n\" , 1), \"\n\", -1)),
            'shipping.street2',IF( IF(a2.same_as_billing=1,NULL,SUBSTRING_INDEX(SUBSTRING_INDEX(a2.street, \"\n\" , 1), \"\n\", -1)) = IF(a2.same_as_billing=1,NULL,SUBSTRING_INDEX(SUBSTRING_INDEX(a2.street, \"\n\" , 2), \"\n\", -1)) ,NULL,IF(a2.same_as_billing=1,NULL,SUBSTRING_INDEX(SUBSTRING_INDEX(a2.street, \"\n\" , 2), \"\n\", -1))),
            'shipping.city',IF(a2.same_as_billing=1,NULL,a2.city),
            'shipping.state',IF(a2.same_as_billing=1,NULL,IF(a2.country_id IN (\"US\",\"CA\"), a2.region, NULL)),
            'shipping.postcode',IF(a2.same_as_billing=1,NULL,UPPER(a2.postcode)),
            'shipping.country',IF(a2.same_as_billing=1,NULL,a2.country_id),
            'customParameters[SHOPPER_plugin_installed]','Magento v2 TPCARDS',
            'customParameters[SHOPPER_quote_id]',qu.entity_id,
            'createRegistration','".$enforceRg."'
        ) payload
        FROM ".$dbPrefix."quote qu
        LEFT JOIN ".$dbPrefix."quote_address a1 ON a1.quote_id = qu.entity_id 
            AND a1.address_type = \"billing\"
        LEFT JOIN ".$dbPrefix."quote_address a2 ON a2.quote_id = qu.entity_id 
            AND a2.address_type = \"shipping\" AND a2.same_as_billing=0
        WHERE qu.is_active = 1
        AND qu.entity_id = $id LIMIT 1;";
        $result = $this->resourceConnection->getConnection()->fetchOne($query);
        return $result;
    }

    public function fetchQuoteRowData($id){

        $id = (int)$id;
        if( $id < 1 || is_numeric($id) !== true){
            return false;
        }

        $dbPrefix = $this->getTablePrefix();

        $query="SELECT
        JSON_OBJECT(
            'reserved_order_id',qu.reserved_order_id,
            'quote_id',qu.entity_id,
            'grand_total',ROUND(qu.grand_total,2),
            'currency',qu.quote_currency_code,
            'customer_id',IFNULL(qu.customer_id,'guest'),
            'customer_email',COALESCE(qu.customer_email,a1.email,a2.email),
            'cart_id',''
        ) quoteRow
        FROM ".$dbPrefix."quote qu
        LEFT JOIN ".$dbPrefix."quote_address a1 ON a1.quote_id = qu.entity_id 
            AND a1.address_type = \"billing\"
        LEFT JOIN ".$dbPrefix."quote_address a2 ON a2.quote_id = qu.entity_id 
            AND a2.address_type = \"shipping\" AND a2.same_as_billing=0
        WHERE qu.entity_id = $id LIMIT 1;";

        $result = $this->resourceConnection->getConnection()->fetchOne($query);

        return $result;
    }

    public function updateReservedOrderId($id,$reservedOrderId){
        
        $id = (int)$id;
        if( $id < 1 || is_numeric($id) !== true){
            return false;
        }

        $dbPrefix = $this->getTablePrefix();

        $query = "UPDATE ".$dbPrefix."quote SET reserved_order_id='".$reservedOrderId."' WHERE entity_id=".$id.";";
        
        $connection = $this->resourceConnection->getConnection();
        
        $connection->query($query);

        return true;
    }

}