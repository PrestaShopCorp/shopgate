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

if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
    require_once(_PS_MODULE_DIR_.'shopgate'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'ModObjectModel.php');
} else {
    require_once(_PS_MODULE_DIR_.'shopgate'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'ModObjectModelDummy.php');
}

class ShopgateOrderPrestashop extends ShopgateModObjectModel
{

    public $id_shopgate_order;
    public $id_cart;
    public $id_order;
    public $order_number;
    public $tracking_number;
    public $shipping_service = 'OTHER';
    public $shipping_cost;
    public $shop_number;
    public $comments;
    public $status;
    public $shopgate_order;
    public $is_sent_to_shopgate;

    public static $definition = array(
        'table'     => 'shopgate_order',
        'primary'     => 'id_shopgate_order',
        'fields'     => array(
            'order_number'         => array(
                'type' => self::TYPE_INT,
                'validate' => 'isNullOrUnsignedId',
                'copy_post' => false
            ),
            'id_cart'             => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true
            ),
            'shopgate_order'        => array('type' => self::TYPE_STRING),
            'id_order'              => array('type' => self::TYPE_INT),
            'tracking_number'       => array('type' => self::TYPE_STRING),
            'shipping_service'      => array('type' => self::TYPE_STRING),
            'shipping_cost'         => array('type' => self::TYPE_FLOAT),
            'shop_number'           => array('type' => self::TYPE_INT),
            'comments'              => array('type' => self::TYPE_STRING),
            'status'                => array('type' => self::TYPE_INT),
            'is_sent_to_shopgate'   => array('type' => self::TYPE_INT)
        )
    );

    public function getFields()
    {
        if (parent::validateFields()) {
            ShopgateLogger::getInstance()->log('Validation for shopgate database fields invalid', ShopgateLogger::LOGTYPE_ERROR);
        }

        if ($this->shopgate_order instanceof ShopgateOrder) {
            $this->shopgate_order = pSQL(base64_encode(serialize($this->shopgate_order)));
        }
        
        if (is_array($this->comments)) {
            
            if (method_exists("Tools", "jsonEncode")) {
                $encodedData = Tools::jsonEncode($this->comments);
            } else {
                $encodedData = json_encode($this->comments);
            }
            
            $this->comments = pSQL(base64_encode($encodedData));
        } else {
            $this->comments = pSQL($this->comments);
        }
        
        $fields                         = array();
        $fields['id_cart']              = (int)($this->id_cart);
        $fields['id_order']             = (int)($this->id_order);
        $fields['shopgate_order']       = pSQL(base64_encode(serialize($this->shopgate_order)));
        $fields['order_number']         = pSQL($this->order_number);
        $fields['shipping_service']     = pSQL($this->shipping_service);
        $fields['shipping_cost']        = (float)($this->shipping_cost);
        $fields['shop_number']          = pSQL($this->shop_number);
        $fields['comments']             = $this->comments;
        $fields['status']               = (int)($this->status);
        $fields['tracking_number']      = pSQL($this->tracking_number);
        $fields['is_sent_to_shopgate']  = (int)($this->is_sent_to_shopgate);
        return $fields;
    }


    /**
     * @param int $id_cart
     * @return ShopgateOrderPrestashop
     */
    public static function loadByCartId($id_cart = 0)
    {
        return new ShopgateOrderPrestashop(ShopgateOrderPrestashop::getIdShopgateOrderByFilter('id_cart', $id_cart));
    }

    /**
     * @param int $id_order
     * @return ShopgateOrderPrestashop
     */
    public static function loadByOrderId($id_order = 0)
    {
        return new ShopgateOrderPrestashop(ShopgateOrderPrestashop::getIdShopgateOrderByFilter('id_order', $id_order));
    }

    /**
     * @param int $order_number
     * @return ShopgateOrderPrestashop
     */
    public static function loadByOrderNumber($order_number = 0)
    {
        $result = ShopgateOrderPrestashop::getIdShopgateOrderByFilter('order_number', $order_number);
        return new ShopgateOrderPrestashop($result);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    protected static function getIdShopgateOrderByFilter($key, $value)
    {
        if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
            $query = 'SELECT id_shopgate_order
                        FROM '._DB_PREFIX_.'shopgate_order
                        WHERE '.pSQL($key).'='.pSQL($value);
            return Db::getInstance()->getValue($query);
        }

        $query = new DbQuery();
        $query->select(ShopgateOrderPrestashop::$definition['primary']);
        $query->from(ShopgateOrderPrestashop::$definition['table']);
        $query->where(pSQL($key).' = \''.pSQL($value).'\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    /**
     * Returns array of all shopgate orders, where shipping is completed
     * but not transferred to shopgate yet.
     *
     * @param int   $languageId
     *
     * @return array
     */
    public static function getUnsyncedShopgatOrderIds($languageId)
    {
        $shippedOrderStateIds = array();
        $unsyncedOrders       = array();
        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            $orderStates = OrderState::getOrderStates($languageId);
            foreach ($orderStates as $orderState) {
                if ($orderState['shipped']) {
                    $shippedOrderStateIds[] = $orderState['id_order_state'];
                }
            }

            if (!empty($shippedOrderStateIds)) {
                $query = 'SELECT so.id_order, so.id_shopgate_order
                    FROM '._DB_PREFIX_.'shopgate_order AS so
                        INNER JOIN '._DB_PREFIX_.'orders AS o on so.id_order=o.id_order
                    WHERE so.`is_sent_to_shopgate` = 0
                        AND o.`current_state` IN (' . implode(',', $shippedOrderStateIds).')';

                $unsyncedOrders = Db::getInstance()->ExecuteS($query);
            }
        } else {
            // Default methods for Prestashop version < 1.5.0.0
            $shippedOrderStateIds[] = _PS_OS_DELIVERED_;
            $shippedOrderStateIds[] = _PS_OS_SHIPPING_;

            if (!empty($shippedOrderStateIds)) {
                $query = 'SELECT so.id_order, so.id_shopgate_order
                    FROM '._DB_PREFIX_.'shopgate_order AS so
                        INNER JOIN '._DB_PREFIX_.'order_history AS oh on so.id_order=oh.id_order
                    WHERE so.`is_sent_to_shopgate` = 0
                        AND oh.`id_order_state` IN (' . implode(',', $shippedOrderStateIds).')
                        GROUP BY oh.`id_order_state`';

                $unsyncedOrders = Db::getInstance()->ExecuteS($query);
            }
        }

        return $unsyncedOrders;
    }

    /**
     * @param CartCore $cart
     * @param ShopgateOrder $order
     * @param int $shopNumber
     * @return bool
     */
    public function fillFromOrder($cart, $order, $shopNumber)
    {
        $this->id_cart             = $cart->id;
        $this->order_number     = $order->getOrderNumber();
        $this->shipping_cost     = $order->getAmountShipping();
        $this->shipping_service = Configuration::get('SG_SHIPPING_SERVICE');
        $this->shop_number         = $shopNumber;
        $this->shopgate_order     = $order;
    }

    /**
     * @param ShopgateOrder $order
     */
    public function updateFromOrder($order)
    {
        $this->shopgate_order = serialize($order);
    }
    
    /**
     * @param bool $partial
     * @param            $message
     *
     * @return bool
     * @throws \ShopgateLibraryException
     */
    public function cancelOrder(&$message, $partial = false)
    {
        $log                 = ShopgateLogger::getInstance();
        $cancellationItems     = array();
        
        // partial cancellation
        // need to check if items quantity changed
        if (!$partial) {
            $message .= 'order will be cancelled complete: '.$this->order_number."\n";
        } else {
            $sgOrder                     = unserialize(base64_decode($this->shopgate_order));
            $cancellationInformation     = unserialize(base64_decode($this->reported_cancellations));
            $cancellationInformation     = empty($cancellationInformation) ? array() : $cancellationInformation;
            
            if (empty($sgOrder)) {
                // changed log type to debug to prevent the creation
                // of huge log files
                $log->log(
                    "Shopgate order data are unavailable id_order:{$this->id_order}, ".
                    "Shopgate order number: {$this->order_number}",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
                return;
            }
            
            $cancellationItems                 = $this->getCancellationData($sgOrder, $cancellationInformation, $message, $log);
            
            if (empty($cancellationItems) || $cancellationItems == $cancellationInformation) {
                return; // nothing should happen because nothing to do or something went wrong
            }
            
            $cancellationInformation         = array_merge($cancellationInformation, $cancellationItems);
            $this->shopgate_order             = $sgOrder;
            $this->reported_cancellations     = base64_encode(serialize($cancellationInformation));
            $message .= 'order will be cancelled partial: '.$this->order_number."\n";
        }
        
        $items = $this->prepareRequestData($cancellationItems);
        
        // prevent that a cancellation will be sent only one time.
        // this check is needed only for full cancellations
        if ($this->is_cancellation_sent_to_shopgate == 0 && $this->sendCancellationRequest($partial, $items)) {
            if (!$partial) {
                $this->is_cancellation_sent_to_shopgate = 1;
            }
            
            $this->update();
        }
    }
    
    /**
     * put the cancellation item data into the needed format
     *
     * @param $items
     *
     * @return array
     */
    private function prepareRequestData($items)
    {
        $requestItemList = array();
        
        if (empty($items)) {
            return $requestItemList;
        }
        
        foreach ($items as $itemNumber => $item) {
            if ($item['data']['quantity_to_cancel'] > 0) {
                $requestItemList[] = array(
                    'item_number'     => $itemNumber,
                    'quantity'         => $item['data']['quantity_to_cancel'],
                );
            }
        }
        
        return $requestItemList;
    }
    
    /**
     * calculate the the quantity to cancel at shopgate
     *
     * @param \ShopgateOrder     $sgOrder this is the shopgate object which was stored into
     *                                     the database on addOrder request
     * @param array             $cancelledItems
     * @param                  $message
     * @param \ShopgateLogger  $log
     *
     * @return array
     * @throws \ShopgateLibraryException
     */
    private function getCancellationData(ShopgateOrder $sgOrder, $cancelledItems, &$message, ShopgateLogger $log)
    {
        if (version_compare(_PS_VERSION_, '1.5.0', '>=')) {
            $pItems = OrderDetailCore::getList($this->id_order);
        } else {
            $pOrder = new OrderCore($this->id_order);
            $pItems = $pOrder->getProductsDetail();
        }
        
        if (empty($pItems)) {
            $errorMessage = "No products found to shopgate order id_order:{$sgOrder->getOrderNumber()}";
            $message .= $errorMessage;
            // changed log type to debug to prevent the creation
            // of huge log files
            $log->log($errorMessage, ShopgateLogger::LOGTYPE_DEBUG);
            
            return array();
        }
        
        foreach ($sgOrder->getItems() as $sgItem) {
            foreach ($pItems as $pItem) {
                $qty             = null;
                $fromHook         = false;
                $sgItemNumber     = $sgItem->getItemNumber();
                
                // generate the item number as we do it for items with attributes
                if (!empty($pItem['product_attribute_id'])) {
                    $prestaItemNumber = $pItem['product_id'].'-'.$pItem['product_attribute_id'];
                } else {
                    $prestaItemNumber = $pItem['product_id'];
                }
                
                if ($sgItemNumber == $prestaItemNumber) {
                    // There is no opportunity to deliver this information to
                    // Shopgate. We need to add a message to the order.
                    $refundPriceData     = Tools::getValue('partialRefundProduct');
                    $refundQtyData         = Tools::getValue('partialRefundProductQuantity');
                    
                    if (!empty($refundPriceData[$pItem['id_order_detail']])) {
                        $currency         = new CurrencyCore(ConfigurationCore::get("PS_CURRENCY_DEFAULT"));
                        $noteMsg         = "Please note that these information could not be transmitted to Shopgate.\n";
                        $refundPrice     = $refundPriceData[$pItem['id_order_detail']];
                        $refundPriceMsg = "The price of the Product (id:{$pItem['product_id']}) was refunded({$refundPrice}{$currency->sign}).\n";
                        
                        if (empty($this->comments)) {
                            $this->comments = array();
                        }
                        
                        if (is_string($this->comments)) {

                            $data = base64_decode($this->comments);
                            
                            if (method_exists("Tools", "jsonDecode")) {
                                $this->comments = Tools::jsonDecode($data);
                            } else {
                                $this->comments = json_decode($data);
                            }
                            
                        }
                        
                        if (is_array($this->comments)) {
                            $this->comments[] = $noteMsg;
                            $this->comments[] = $refundPriceMsg;
                        }
                    }
                    
                    // if the hook was executed or cancel_order request was sent from
                    // shopgate, we get the right data from version 1.5.0
                    // for lower versions we got two cases:
                    // * cancel order request, we need to get the cancelled quantity
                    //   out of the database (here we need to calculate it as in 1.5.00)
                    // * if the hook was executed from prestashop, we get the actual cancelled
                    //   amount in the $_POST array (here there is no need to calculate the cancelled)
                    //   value, cause we got it at this point
                    if (empty($refundQtyData[$pItem['id_order_detail']]) && Tools::isSubmit('partialRefundProduct')) {
                        continue;
                    } else {
                        $qty = $refundQtyData[$pItem['id_order_detail']];
                    }

                    // try to retrieve an $_POST['cancelQuantity'] array
                    $cancelQuantity = Tools::getValue('cancelQuantity', null);

                    if (version_compare(_PS_VERSION_, '1.5.0', '>=') && empty($qty)) {
                        $qty = $sgItem->getQuantity() - $pItem['product_quantity'];
                    } elseif (!empty($cancelQuantity[$pItem['id_order_detail']]) && empty($qty)) {
                        $qty = $cancelQuantity[$pItem['id_order_detail']];
                        $fromHook = true;
                    }
                    
                    if (empty($qty) && Tools::getValue('action') == "cron") {
                        $qty = $pItem['product_quantity_refunded'];
                    }
                    
                    // nothing to cancel here
                    if (empty($qty) || $qty < 1) {
                        continue;
                    }
                    
                    $cancelledItems[$sgItemNumber]['data']['item_number'] = $pItem['product_id'];
                    
                    // if someone changed the quantity for this item in the past
                    // we stored it in the database
                    if (empty($cancelledItems[$sgItemNumber]['data']['quantity'])) {
                        $oldQty = 0;
                    } else {
                        $oldQty = $cancelledItems[$sgItemNumber]['data']['quantity'];
                    }
                    
                    if (empty($cancelledItems[$sgItemNumber]['data']['quantity'])) {
                        $cancelledItems[$sgItemNumber]['data']['quantity']                 = $qty;
                        $cancelledItems[$sgItemNumber]['data']['quantity_to_cancel']     = $qty;
                    } else {
                        
                        // subtract the old quantity
                        if (version_compare(_PS_VERSION_, '1.5.0', '>=') || !$fromHook) {
                            if (Tools::isSubmit('partialRefundProduct')) {
                                $cancelQuantity = $qty;
                            } else {
                                $cancelQuantity = $qty - $oldQty;
                            }
                        } else {
                            $cancelQuantity = $qty;
                        }
                        
                        if ($cancelQuantity < 0) {
                            $cancelQuantity *= -1;
                        }
                        
                        if ($cancelQuantity > 0) {
                            $cancelledItems[$sgItemNumber]['data']['quantity']                 += $cancelQuantity;
                            $cancelledItems[$sgItemNumber]['data']['quantity_to_cancel']     = $cancelQuantity;
                        } else {
                            $cancelledItems[$sgItemNumber]['data']['quantity_to_cancel'] = 0;
                        }
                    }
                    if ($cancelQuantity > 0) {
                        $message .= "reducing quantity ({$cancelledItems[$sgItemNumber]['data']['quantity_to_cancel']}) for the item {$sgItemNumber} \n";
                    }
                }
            }
        }
        
        return $cancelledItems;
    }
    
    /**
     * @param array $items
     * @param       $partial
     *
     * @return bool
     */
    private function sendCancellationRequest($partial, $items = array())
    {
        
        if (!empty($this->merchantApiCache[$this->shop_number])) {
            $merchantApi = $this->merchantApiCache[$this->shop_number];
        } else {
            $builder      = new ShopgateBuilder(new ShopgateConfigPrestashop());
            $merchantApi = $builder->buildMerchantApi();
            $this->merchantApiCache[$this->shop_number] = $merchantApi;
        }
        
        try {
            if (empty($items) && !$partial) {
                $merchantApi->cancelOrder($this->order_number, true);
                $requestSent = true;
            } elseif (!empty($items) && $partial) {
                $merchantApi->cancelOrder($this->order_number, false, $items);
                $requestSent = true;
            } else {
                $requestSent = false;
            }
        } catch (Exception $e) {
            /*catch the exceptions, do nothing
            to prevent that the following other cancellation request won't
            be requested*/
            $requestSent = false;
        }
        
        return $requestSent;
    }

    /**
     * @param string    $message
     * @param int       $errorcount
     */
    public function setShippingComplete(&$message, &$errorcount)
    {
        $log                    = ShopgateLogger::getInstance();
        $shopgateConfig         = new ShopgateConfigPrestashop();
        $shopgateBuilder        = new ShopgateBuilder($shopgateConfig);
        $shopgateMerchantApi    = $shopgateBuilder->buildMerchantApi();

        try {
            /** @var OrderCore $orderCore */
            $orderCore      = new Order($this->id_order);
            $trackingCode   = $orderCore->shipping_number;
            if (Tools::strlen($trackingCode) > 32) {
                $log->log("TrackingCode '" . $trackingCode . "' is too long", ShopgateLogger::LOGTYPE_DEBUG);
                $trackingCode = '';
            }

            $shopgateMerchantApi->addOrderDeliveryNote(
                $this->order_number,
                ShopgateDeliveryNote::OTHER,
                $trackingCode,
                true
            );
            $message .= "Setting \"shipping complete\" for shopgate-order #{$this->order_number} successfully completed\n";
            $this->is_sent_to_shopgate  = 1;
            $this->tracking_number      = $trackingCode;
            $this->update();
        } catch (ShopgateMerchantApiException $e) {
            if ($e->getCode() == ShopgateMerchantApiException::ORDER_SHIPPING_STATUS_ALREADY_COMPLETED) {
                $log->log(
                    "Order with order-number #{$this->order_number} already marked as complete at shopgate.",
                    ShopgateLogger::LOGTYPE_DEBUG
                );
                $e                = null;

                $this->is_sent_to_shopgate  = 1;
                $this->update();
            }
        } catch (Exception $e) {
            $errorcount++;
            $message .= "Error while setting \"shipping complete\" for shopgate-order #{$this->order_number}\n";
        }
    }
}
