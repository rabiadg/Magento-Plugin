<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Command\ApplePay;

use Magento\Payment\Gateway\Command\GatewayCommand;

/**
 * Class DebitCommand
 * @package TotalProcessing\Opp\Gateway\Command\ApplePay
 */
class DebitCommand extends GatewayCommand
{
    const COMMAND_CODE = "debit";
}
