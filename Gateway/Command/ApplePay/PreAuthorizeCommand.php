<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Command\ApplePay;

use Magento\Payment\Gateway\Command\GatewayCommand;
use TotalProcessing\Opp\Gateway\Command\PreAuthorizeCommand as BasePreAuthorizeCommand;

/**
 * Class PreAuthorizeCommand
 * @package TotalProcessing\Opp\Gateway\Command\ApplePay
 */
class PreAuthorizeCommand extends GatewayCommand
{
    const COMMAND_CODE = "pre_authorize";
}
