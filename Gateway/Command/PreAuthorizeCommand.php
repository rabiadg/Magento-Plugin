<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Command;

use Magento\Payment\Gateway\Command\GatewayCommand;

/**
 * Class PreAuthorizeCommand
 * @package TotalProcessing\Opp\Gateway\Command
 */
class PreAuthorizeCommand extends GatewayCommand
{
    const COMMAND_CODE = "pre_authorize";
}
