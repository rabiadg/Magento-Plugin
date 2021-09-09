<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Request;

/**
 * Class CustomParameterDataBuilder
 */
class CustomParameterDataBuilder extends BaseRequestDataBuilder
{
    const ORDER_ID = "SHOPPER_order_id";
    const ORDER_INCREMENT_ID = "SHOPPER_order_increment_id";
    const PLUGIN = "SHOPPER_plugin_installed";
    const PUBLIC_HASH = 'SHOPPER_card_public_hash';
    const QUOTE_ID = "SHOPPER_quote_id";
    const RETURN_URL = "SHOPPER_returnurl";
    const TP_JSON = "SHOPPER_tpJson";

    public function build(array $buildSubject)
    {
        return [];
    }
}
