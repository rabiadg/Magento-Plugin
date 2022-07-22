<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Command;

use Magento\Payment\Gateway\Command\GatewayCommand;
use TotalProcessing\Opp\Gateway\Response\TransactionCheckHandler;

/**
 * Class AuthorizeCommand
 */
class AuthorizeCommand extends GatewayCommand
{
    /**
     * {@inheritDoc}
     */
    public function execute(array $commandSubject)
    {
        $isPreAuthorized = $commandSubject[TransactionCheckHandler::IS_PRE_AUTHORIZED] ?? false;

        if (!$isPreAuthorized) {
            parent::execute($commandSubject);
        }
    }
}
