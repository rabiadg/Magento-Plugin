<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\ErrorMapper;

use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapper;

/**
 * Class VirtualErrorMessageMapper
 * @package TotalProcessing\Opp\Gateway\ErrorMapper
 */
class VirtualErrorMessageMapper extends ErrorMessageMapper
{
    const DEFAULT_ERROR_CODE = '800.100.100';
}
