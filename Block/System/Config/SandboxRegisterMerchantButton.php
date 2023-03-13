<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Block\System\Config;

/**
 * Class SandboxRegisterMerchantButton
 */
class SandboxRegisterMerchantButton extends RegisterMerchantButton
{
    /**
     * Returns button id
     *
     * @return string
     */
    public function getButtonId(): string
    {
        return "sandbox_register_merchant_btn";
    }
}
