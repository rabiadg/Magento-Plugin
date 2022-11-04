<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Command\PaymentStatusCommand;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Gateway\Request\PaymentStatusRequestDataBuilder;
use Magento\Framework\App\RequestInterface;
use TotalProcessing\Opp\Model\System\Config\PaymentAction;

/**
 * Class PaymentStatusResolver
 * @package TotalProcessing\Opp\Gateway\Helper
 */
class PaymentStatusResolver
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param CommandManagerInterface $commandManager
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        CommandManagerInterface $commandManager,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->commandManager = $commandManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return void
     * @throws CommandException
     * @throws NotFoundException
     */
    public function resolve(): void
    {
        if ($this->config->getPaymentAction() != PaymentAction::DEBIT) {
            // processing only for debit payment action (DB)
            return;
        }

        $this->logger->debug(
            "Check payment status before. Payment gateway response params:",
            $this->request->getParams()
        );

        try {
            $this->logger->debug("Get Command: " . PaymentStatusCommand::COMMAND_CODE);
            $command = $this->commandManager->get(PaymentStatusCommand::COMMAND_CODE);
            if (!$command instanceof CommandInterface) {
                $this->logger->critical(__("Payment Status command should be provided."), []);
                throw new CommandException(__("Payment Status command should be provided."));
            }
            $command->execute([
                PaymentStatusRequestDataBuilder::CHECKOUT_ID =>
                    $this->request->getParam(PaymentStatusRequestDataBuilder::CHECKOUT_ID),
                PaymentStatusRequestDataBuilder::RESOURCE_PATH =>
                    $this->request->getParam(PaymentStatusRequestDataBuilder::RESOURCE_PATH),
            ]);
        } catch (CommandException $e) {
            $this->logger->critical($e->getMessage(), []);
            throw new CommandException(__($e->getMessage()));
        }

        $this->logger->debug("Check payment status after.");
    }
}
