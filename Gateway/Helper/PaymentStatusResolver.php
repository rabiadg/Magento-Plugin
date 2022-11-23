<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TotalProcessing\Opp\Gateway\Helper;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Psr\Log\LoggerInterface;
use TotalProcessing\Opp\Gateway\Command\PaymentStatusCommand;
use TotalProcessing\Opp\Gateway\Config\Config;
use TotalProcessing\Opp\Model\System\Config\PaymentAction;
use TotalProcessing\Opp\Gateway\Request\PaymentStatusRequestDataBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use TotalProcessing\Opp\Model\Ui\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;

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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param CommandManagerInterface $commandManager
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        CommandManagerInterface $commandManager,
        Config $config,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->commandManager = $commandManager;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isApplicable($storeId = null): bool
    {
        $storeId = $storeId ?? $this->storeManager->getStore()->getId();
        if ($this->config->getPaymentAction($storeId) == PaymentAction::DEBIT) {
            return true;
        }

        return false;
    }


    /**
     * @param $storeId
     * @return void
     * @throws NoSuchEntityException|LocalizedException
     */
    public function resolve($storeId = null): void
    {
        if (!$this->isApplicable($storeId)) {
            return;
        }

        $this->logger->debug(
            "Check payment status before. Payment gateway response params:",
            $this->request->getParams()
        );

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

        $this->logger->debug("Check payment status after.");
    }
}
