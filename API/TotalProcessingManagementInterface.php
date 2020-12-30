<?php

namespace TotalProcessing\TPCARDS\API;

/**
 * Interface TotalProcessingManagementInterface.
 *
 * @api
 */
interface TotalProcessingManagementInterface
{
    /**
     * Processes the tpcards response from the gateway.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array                      $response
     *
     * @return bool
     */
    public function processResponse($order, $response);

    /**
     * Restore cart.
     *
     * @param string $cartId
     *
     * @return mixed
     */
    public function restoreCart($cartId);
}
