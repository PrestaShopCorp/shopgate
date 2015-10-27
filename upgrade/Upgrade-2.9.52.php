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
 *
 * User: awesselburg
 * Date: 22.09.14
 * Time: 10:31
 * E-Mail: awesselburg <wesselburg@me.com>
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param ShopGate $module
 *
 * @return bool
 */
function upgrade_module_2_9_52($module)
{
    $shopgateCarrierId = Configuration::get('SG_CARRIER_ID');

    /**
     * set current shopgate carrier as deleted
     */
    if ($shopgateCarrierId) {
        /** @var CarrierCore $carrier */
        $carrier          = new Carrier($shopgateCarrierId);
        $carrier->deleted = true;
        $carrier->save();
    }

    $shopgateShippingModel = new ShopgateShipping($module);
    $shopgateShippingModel->createShopgateCarrier();

    $module->updateTables();

    return true;
}
