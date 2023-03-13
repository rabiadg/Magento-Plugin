<?php

namespace TotalProcessing\Opp\Gateway\ErrorMapper;

use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapper;

class VirtualErrorMessageMapper extends ErrorMessageMapper
{
    const DEFAULT_ERROR_CODE = '800.100.100';
}
