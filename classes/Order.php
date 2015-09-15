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
    include_once(_PS_MODULE_DIR_.'shopgate'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'ModObjectModel.php');
} else {
    include_once(_PS_MODULE_DIR_.'shopgate'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'ModObjectModelDummy.php');
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
            'shopgate_order'     => array('type' => self::TYPE_STRING),
            'id_order'             => array('type' => self::TYPE_INT),
            'tracking_number'     => array('type' => self::TYPE_STRING),
            'shipping_service'     => array('type' => self::TYPE_STRING),
            'shipping_cost'     => array('type' => self::TYPE_FLOAT),
            'shop_number'         => array('type' => self::TYPE_INT),
            'comments'             => array('type' => self::TYPE_STRING),
            'status'             => array('type' => self::TYPE_INT)
        )
    );

    public function getFields()
    {
        if (parent::validateFields()) {
            ShopgateLogger::getInstance()->log('Validation for shopgate database fields invalid', ShopgateLogger::LOGTYPE_ERROR);
        }

        $fields                     = array();
        $fields['id_cart']             = (int)($this->id_cart);
        $fields['id_order']         = (int)($this->id_order);
        $fields['shopgate_order']     = pSQL(base64_encode(serialize($this->shopgate_order)));
        $fields['order_number']     = pSQL($this->order_number);
        $fields['shipping_service'] = pSQL($this->shipping_service);
        $fields['shipping_cost']     = (float)($this->shipping_cost);
        $fields['shop_number']         = pSQL($this->shop_number);
        $fields['comments']         = pSQL($this->comments, true);
        $fields['status']             = (int)($this->status);
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
}
