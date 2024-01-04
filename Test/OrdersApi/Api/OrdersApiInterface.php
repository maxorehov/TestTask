<?php

namespace Test\OrdersApi\Api;

interface OrdersApiInterface
{

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Sales\Api\Data\OrderSearchResultInterface|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getLastOrders();
}
