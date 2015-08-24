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

class ShopgateShipping
{
    const DEFAULT_PLUGIN_API_KEY = 'PLUGINAPI';

    const DEFAULT_EXTERNAL_MODULE_CARRIER_NAME = 'shopgate';

    const MODULE_CARRIER_NAME = 'shopgate';

    const SG_ALL_CARRIERS = 5;

    const CARRIER_CODE_ALL = 'All';

    /** @var ShopGate */
    protected $module;

    /** @var array */
    protected $shipping_service_list;

    /**
     * @param ShopGate $module
     */
    public function __construct($module)
    {
        $this->shipping_service_list = array (
            ShopgateDeliveryNote::OTHER => $module->l('Other'),
            ShopgateDeliveryNote::DHL => $module->l('DHL'),
            ShopgateDeliveryNote::DHLEXPRESS => $module->l('DHL Express'),
            ShopgateDeliveryNote::DP => $module->l('Deutsche Post'),
            ShopgateDeliveryNote::DPD => $module->l('DPD'),
            ShopgateDeliveryNote::FEDEX => $module->l('FedEx'),
            ShopgateDeliveryNote::GLS => $module->l('GLS'),
            ShopgateDeliveryNote::HLG => $module->l('Hermes'),
            ShopgateDeliveryNote::TNT => $module->l('TNT'),
            ShopgateDeliveryNote::TOF => $module->l('trans-o-flex'),
            ShopgateDeliveryNote::UPS => $module->l('UPS'),
            'LAPOSTE' => $module->l('LA POSTE'),
        );
    }

    /**
     * returns the shipping service list
     *
     * @return array
     */
    public function getShippingServiceList()
    {
        return $this->shipping_service_list;
    }

    /**
     * @param ShopgateOrder $order
     *
     * @return mixed
     */
    public function getCarrierIdByApiOrder($order)
    {
        switch ($order->getShippingType())
        {
            case self::DEFAULT_PLUGIN_API_KEY:
                if ($order->getShippingInfos() && $order->getShippingInfos()->getName()) {
                    return $order->getShippingInfos()->getName();
                }
                break;
            default:

                /**
                 * use always shopgate carrier if shipping cost uses.
                 */
                if ($order->getShippingInfos()->getAmount() > 0) {
                    return Configuration::get('SG_CARRIER_ID');
                }

                if ($order->getShippingGroup()) {
                    $carrierMapping = $this->getCarrierMapping();
                    if (is_array($carrierMapping)) {
                        foreach ($carrierMapping as $key => $value) {
                            if ($order->getShippingGroup() == $key) {
                                return $value;
                            }
                        }
                    }

                    break;
                }
        }

        return Configuration::get('SG_CARRIER_ID');
    }

    /**
     * @return array|mixed
     */
    public function getCarrierMapping()
    {
        $carrierMapping = unserialize(base64_decode(Configuration::get('SG_CARRIER_MAPPING')));

        if (!is_array($carrierMapping)) {
            $carrierMapping = array();
            foreach ($this->getShippingServiceList() as $key => $value) {
                $carrierMapping[$key] = Configuration::get('SG_CARRIER_ID');
            }
        }

        return $carrierMapping;
    }

    /**
     * @param ShopgateOrder $apiOrder
     * @return string
     */
    public function getMappingHtml(ShopgateOrder $apiOrder)
    {
        switch ($apiOrder->getShippingType()) {
            /**
             * read system
             */
            case self::DEFAULT_PLUGIN_API_KEY:
                return $this->_getNameByCarrierId($apiOrder->getShippingInfos()->getName());
            /**
             * switch from mapping
             */
            default:
                return
                    sprintf(
                        '%s (%s - %s)',
                        $this->_getNameByCarrierId(
                            $this->getCarrierIdByApiOrder($apiOrder)
                        ),
                        $apiOrder->getShippingType(),
                        $apiOrder->getShippingInfos()->getDisplayName()
                    );
        }
    }

    /**
     * @param $carrierId
     * @return string
     */
    protected function _getNameByCarrierId($carrierId)
    {
        /** @var CarrierCore $carrierItem */
        $carrierItem = new Carrier($carrierId);
        return $carrierItem->name;
    }

    /**
     * create shopgate carrier
     */
    public function createShopgateCarrier()
    {
        /** @var CarrierCore $carrier */
        $carrier = new Carrier();
        $carrier->name = self::MODULE_CARRIER_NAME;
        $carrier->is_module = 1;
        $carrier->deleted = 0;
        $carrier->shipping_external = 1;
        $carrier->id_tax_rules_group = 0;

        $carrier->external_module_name = self::DEFAULT_EXTERNAL_MODULE_CARRIER_NAME;

        foreach (Language::getLanguages() as $language) {
            $carrier->delay[$language['id_lang']] = 'Depends on Shopgate selected carrier';
        }

        $carrier->save();
        Configuration::updateValue('SG_CARRIER_ID', $carrier->id);
    }

    /**
     * @param int        $id_lang
     * @param bool|false $active_countries
     * @param bool|false $active_carriers
     * @param null       $contain_states
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getDeliveryCountries($id_lang, $active_countries = false, $active_carriers = false, $contain_states = null)
    {
        if (!Validate::isBool($active_countries) || !Validate::isBool($active_carriers)) {
            die(Tools::displayError());
        }

        $states = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT s.*
            FROM `'._DB_PREFIX_.'state` s
            ORDER BY s.`name` ASC'
        );

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT cl.*,c.*, cl.`name` AS country, zz.`name` AS zone
            FROM `'._DB_PREFIX_.'country` c
            LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (c.`id_country` = cl.`id_country` AND cl.`id_lang` = '.(int)$id_lang.')
            INNER JOIN (`'._DB_PREFIX_.'carrier_zone` cz INNER JOIN `'._DB_PREFIX_.'carrier` cr ON ( cr.id_carrier = cz.id_carrier AND cr.deleted = 0 '.
            ($active_carriers ? 'AND cr.active = 1) ' : ') ').'
            LEFT JOIN `'._DB_PREFIX_.'zone` zz ON cz.id_zone = zz.id_zone) ON zz.`id_zone` = c.`id_zone`
            WHERE 1
            '.($active_countries ? 'AND c.active = 1' : '').'
            '.(!is_null($contain_states) ? 'AND c.`contains_states` = '.(int)$contain_states : '').'
            ORDER BY cl.name ASC'
        );

        $countries = array();
        foreach ($result as &$country) {
            $countries[$country['id_country']] = $country;
        }
        foreach ($states as &$state) {
            if (isset($countries[$state['id_country']])) { /* Does not keep the state if its country has been disabled and not selected */
                if ($state['active'] == 1) {
                    $countries[$state['id_country']]['states'][] = $state;
                }
            }
        }

        return $countries;
    }
}
