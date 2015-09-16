<?php
/**
 * Shopgate GmbH
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file AFL_license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to interfaces@shopgate.com so we can send you a copy immediately.
 *
 * @author    Shopgate GmbH, Schloßstraße 10, 35510 Butzbach <interfaces@shopgate.com>
 * @copyright Shopgate GmbH
 * @license   http://opensource.org/licenses/AFL-3.0 Academic Free License ("AFL"), in the version 3.0
 */

class ShopgateItemsOrder extends ShopgateItemsAbstract
{
    /**
     * @param $plugin
     *
     * @throws ShopgateLibraryException
     */
    public function __construct($plugin)
    {
        parent::__construct($plugin);

        /** @var CartCore $cart */
        $cart = new Cart();
        $cart->id_lang = $this->getPlugin()->getLanguageId();
        $cart->id_currency = $this->getPlugin()->getContext()->currency->id;
        $cart->recyclable = 0;
        $cart->gift = 0;

        $this->getPlugin()->getContext()->cart = $cart;

        /**
         * check / create shopgate carrier
         */
        /** @var CarrierCore $sgCarrier */
        $sgCarrier = new Carrier(Configuration::get('SG_CARRIER_ID'));

        if (!$sgCarrier->id) {
            $shopgateShippingModel = new ShopgateShipping(new ShopGate());
            $shopgateShippingModel->createShopgateCarrier();
        }

        /**
         * check all needed table columns
         */
        $shopGate = new ShopGate();

        if (!$shopGate->updateTables()) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_DATABASE_ERROR, sprintf('Cannot update shopgate_order_table'));
        }
    }

    /**
     * @return Cart|CartCore
     */
    public function getCart()
    {
        return $this->getPlugin()->getContext()->cart;
    }

    /**
     * @param int    $customerId
     * @param int    $limit
     * @param int    $offset
     * @param string $orderDateFrom
     * @param string $sortOrder
     *
     * @return mixed
     */
    public function getCustomerOrders($customerId, $limit = 10, $offset = 0, $orderDateFrom = '', $sortOrder = 'created_desc')
    {
        $orders = Order::getCustomerOrders($customerId);
        $orders = $this->sortCoreOrders($orders, $sortOrder);

        $orderCount = 0;
        $result     = array();

        if ($orderDateFrom != '') {
            $dateTime      = new DateTime($orderDateFrom);
            $orderDateFrom = $dateTime->getTimestamp();
        } else {
            $orderDateFrom = false;
        }

        foreach ($orders as $order) {
            /**
             * handle offset
             */
            if ($orderCount < $offset) {
                $orderCount++;
                continue;
            }

            /**
             * handle date from
             */
            if ($orderDateFrom) {
                $dateTime             = new DateTime($order['date_add']);
                $orderDateFromCompare = $dateTime->getTimestamp();

                if ($orderDateFromCompare < $orderDateFrom) {
                    $orderCount++;
                    continue;
                }
            }

            /**
             * handle limit
             */
            if ($orderCount == $limit) {
                break;
            }

            array_push($result, $order);
            $orderCount++;
        }

        return $result;
    }

    /**
     * @param array  $orders
     * @param string $sort
     *
     * @return mixed
     */
    protected function sortCoreOrders($orders, $sort)
    {
        switch ($sort) {
            case 'created_asc':
                $this->arraySortByColumn($orders, 'date_add', SORT_ASC);
                break;
            case 'created_desc':
                $this->arraySortByColumn($orders, 'date_add', SORT_DESC);
                break;
        }

        return $orders;
    }

    /**
     * @param     $arr
     * @param     $col
     * @param int $dir
     */
    protected function arraySortByColumn(&$arr, $col, $dir = SORT_ASC)
    {
        $sortCol = array();
        foreach ($arr as $key => $row) {
            $sortCol[$key] = $row[$col];
        }
        array_multisort($sortCol, $dir, $arr);
    }
}
