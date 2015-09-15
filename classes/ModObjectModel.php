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

abstract class ShopgateModObjectModel extends ObjectModel
{

    /** List of field types */
    const TYPE_INT             = 1;
    const TYPE_BOOL         = 2;
    const TYPE_STRING         = 3;
    const TYPE_FLOAT         = 4;
    const TYPE_DATE         = 5;
    const TYPE_HTML         = 6;
    const TYPE_NOTHING         = 7;
    const TYPE_SQL             = 8;

    /** List of data to format */
    const FORMAT_COMMON     = 1;
    const FORMAT_LANG         = 2;
    const FORMAT_SHOP         = 3;

    /** List of association types */
    const HAS_ONE             = 1;
    const HAS_MANY             = 2;

    /** @var string SQL Table name */
    protected $table         = 'shopgate_order';

    /** @var string SQL Table identifier */
    protected $identifier     = 'id_shopgate_order';

    public $total_shipping;

    public static function updateShippingPrice($price)
    {
        $sql = 'UPDATE '._DB_PREFIX_.'delivery AS d SET price="'.(float)$price.'" WHERE d.id_carrier = '.(int)Configuration::get('SG_CARRIER_ID');
        return Db::getInstance()->execute($sql);
    }
}
