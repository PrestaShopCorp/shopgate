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

class ShopgateItemsInputOrderJson extends ShopgateItemsOrder
{
    /**
     * @param ShopgateOrder $order
     *
     * @return array
     * @throws PrestaShopException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order)
    {
        /**
         * check exits shopgate order
         */
        if (ShopgateOrderPrestashop::loadByOrderNumber($order->getOrderNumber())->status == 1) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER, sprintf('external_order_id: %s', $order->getOrderNumber()), true);
        }

        $context = Context::getContext();
        $context->cookie->__set('shopgateOrder', serialize($order));

        $customerModel     = new ShopgateItemsCustomerImportJson($this->getPlugin());
        $paymentModel     = new ShopgatePayment($this->getModule());
        $shippingModel     = new ShopgateShipping($this->getModule());

        /**
         * read / check customer
         */
        if (!$customerId = Customer::customerExists($order->getMail(), true, false)) {
            /**
             * prepare customer
             */
            $shopgateCustomerItem = new ShopgateCustomer();

            $shopgateCustomerItem->setLastName($order->getInvoiceAddress()->getLastName());
            $shopgateCustomerItem->setFirstName($order->getInvoiceAddress()->getFirstName());
            $shopgateCustomerItem->setGender($order->getInvoiceAddress()->getGender());
            $shopgateCustomerItem->setBirthday($order->getInvoiceAddress()->getBirthday());
            $shopgateCustomerItem->setNewsletterSubscription(
                Configuration::get('SG_SUBSCRIBE_NEWSLETTER') ? true : false
            );

            $customerId = $customerModel->registerCustomer(
                $order->getMail(),
                md5(_COOKIE_KEY_.time()),
                $shopgateCustomerItem
            );
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($customerId);

        /**
         * prepare cart
         */
        if (!$order->getDeliveryAddress()->getPhone()) {
            $order->getDeliveryAddress()->setPhone($order->getPhone());
        }
        if (!$order->getInvoiceAddress()->getPhone()) {
            $order->getInvoiceAddress()->setPhone($order->getPhone());
        }
        $this->getCart()->id_address_delivery = $customerModel->createAddress($order->getDeliveryAddress(), $customer);
        $this->getCart()->id_address_invoice = $customerModel->createAddress($order->getInvoiceAddress(), $customer);
        $this->getCart()->id_customer = $customerId;

        // id_guest is a connection to a ps_guest entry which includes screen width etc.
        // is_guest field only exists in Prestashop 1.4.1.0 and higher
        if (version_compare(_PS_VERSION_, '1.4.1.0', '>=')) {
            $this->getCart()->id_guest = $customer->is_guest;
        }

        $this->getCart()->secure_key = $customer->secure_key;
        $this->getCart()->id_carrier = $shippingModel->getCarrierIdByApiOrder($order);

        $shopgateCustomFieldsHelper = new ShopgateCustomFieldsHelper();
        $shopgateCustomFieldsHelper->saveCustomFields($this->getCart(), $order->getCustomFields());

        $this->getCart()->add();

        /**
         * add cart items
         */
        $canCreateOrder = true;
        $errorMessages = array();

        foreach ($order->getItems() as $item) {
            list ($productId, $attributeId) = ShopgateHelper::getProductIdentifiers($item);

            if ($productId == 0) {
                continue;
            }

            $updateCart = $this->getCart()->updateQty(
                $item->getQuantity(),
                $productId,
                $attributeId,
                false,
                'up',
                $this->getCart()->id_address_delivery
            );

            if ($updateCart !== true) {
                $canCreateOrder = false;
                $errorMessages[] = array(
                    'product_id' => $productId,
                    'attribute_id' => $attributeId,
                    'quantity' => $item->getQuantity(),
                    'result' => $updateCart,
                    'reason' => ($updateCart == -1 ? 'minimum quantity not reached' : ''),
                );
            }
        }

        /**
         * coupons
         */
        foreach ($order->getExternalCoupons() as $coupon) {
            /** @var CartRuleCore $cartRule */
            $cartRule = new CartRule(CartRule::getIdByCode($coupon->getCode()));
            if (Validate::isLoadedObject($cartRule)) {
                $this->getCart()->addCartRule($cartRule->id);
                $this->getCart()->save();
            }
        }

        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            /**
             * this field is not available in version 1.4.x.x
             * set delivery option
             */
            $delivery_option = array($this->getCart()->id_address_delivery => $shippingModel->getCarrierIdByApiOrder($order).',');
            $this->getCart()->setDeliveryOption($delivery_option);
            $this->getCart()->save();
        }

        /**
         * store shopgate order
         */
        $shopgateOrderItem = new ShopgateOrderPrestashop();
        $shopgateOrderItem->fillFromOrder(
            $this->getCart(),
            $order,
            $this->getPlugin()->getShopgateConfig()->getShopNumber()
        );

        if (version_compare(_PS_VERSION_, '1.6.0.0', '<')) {
            $shopgateOrderItem->add();
        }

        /**
         * create order
         */
        if ($canCreateOrder) {
            /**
             * get first item from order stats
             */
            $this->getCart()->save();

            $idOrderState = reset($paymentModel->getOrderStateId($order));
            $validateOder = $this->getModule()->validateOrder(
                $this->getCart()->id,
                $idOrderState,
                $this->getCart()->getOrderTotal(true, defined('Cart::BOTH') ? Cart::BOTH : 3),
                $paymentModel->getPaymentTitleByKey($order->getPaymentMethod()),
                null,
                array(),
                null,
                false,
                $this->getCart()->secure_key
            );

            /**
             * update shopgate order
             */
            if ($validateOder) {

                $shopgateOrderItem->id_order = $this->getModule()->currentOrder;
                $shopgateOrderItem->status = 1;
                $shopgateOrderItem->save();

                return array(
                    'external_order_id' => $shopgateOrderItem->id_order,
                    'external_order_number' => $shopgateOrderItem->id_order
                );
            }
        }

        $shopgateOrderItem->delete();
        throw new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            'Unable to create order:'.print_r($errorMessages, true),
            true
        );
    }

    /**
     * @param ShopgateOrder $order
     *
     * @return array
     * @throws ShopgateLibraryException
     */
    public function updateOrder(ShopgateOrder $order)
    {
        $paymentModel = new ShopgatePayment($this->getModule());
        $shopgateOrderItem = ShopgateOrderPrestashop::loadByOrderNumber($order->getOrderNumber());

        if (!Validate::isLoadedObject($shopgateOrderItem)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_ORDER_NOT_FOUND, 'Order not found #'.$order->getOrderNumber(), true);
        }

        /** @var OrderCore $coreOrder */
        $coreOrder = new Order($shopgateOrderItem->id_order);
        $returnValues = array(
            'external_order_id' => $shopgateOrderItem->id_order,
            'external_order_number' => $shopgateOrderItem->id_order
        );
    
        // check if the order is already shipped and stop processing if the order is shipped already
        $stopProcessing = false;
        $currentOrderStateId = $coreOrder->getCurrentState();
        if ($currentOrderStateId) {
            $currentOrderState = new OrderState($currentOrderStateId);
    
            if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')
                && is_object($currentOrderState)
                && property_exists($currentOrderState, 'shipped')
                && $currentOrderState->shipped
            ) {
                $stopProcessing = true;
            } elseif (version_compare(_PS_VERSION_, '1.5.0.0', '<') && in_array($currentOrderState->id, array(_PS_OS_DELIVERED_, _PS_OS_SHIPPING_))) {
                $stopProcessing = true;
            }
            
            if ($stopProcessing) {
                return $returnValues;
            }
        }
        
        /**
         * get order states
         */
        $changedStates = $paymentModel->getOrderStateId($order, false);

        /**
         * apply changed states
         */
        foreach ($changedStates as $changedState) {
            $coreOrder->setCurrentState($changedState);
        }

        $shopgateOrderItem->updateFromOrder($order);
        $shopgateOrderItem->save();
        

        return $returnValues;
    }
}
