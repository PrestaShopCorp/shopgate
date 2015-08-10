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

	const SG_ALL_CARRIERS = 5;

	/** @var ShopGate */
	protected $module;

	/** @var array */
	protected $shipping_service_list;

	/**
	 * @param ShopGate $module
	 */
	public function __construct($module)
	{
		$this->shipping_service_list = array
		(
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
			case self::DEFAULT_PLUGIN_API_KEY :
			if ($order->getShippingInfos() && $order->getShippingInfos()->getName())
					return $order->getShippingInfos()->getName();
				break;
			default :

				/**
				 * use always shopgate carrier if shipping cost uses.
				 */
				if ($order->getShippingInfos()->getAmount() > 0)
					return Configuration::get('SG_CARRIER_ID');

				if ($order->getShippingGroup())
				{
					$carrierMapping = $this->getCarrierMapping();
					if (is_array($carrierMapping))
						foreach ($carrierMapping as $key => $value)
							if ($order->getShippingGroup() == $key)
								return $value;

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

		if (!is_array($carrierMapping))
		{
			$carrierMapping = array();
			foreach ($this->getShippingServiceList() as $key => $value)
				$carrierMapping[$key] = Configuration::get('SG_CARRIER_ID');
		}

		return $carrierMapping;
	}

	/**
	 * @param ShopgateOrder $apiOrder
	 * @return string
	 */
	public function getMappingHtml(ShopgateOrder $apiOrder)
	{
		switch ($apiOrder->getShippingType())
		{
			/**
			 * read system
			 */
			case self::DEFAULT_PLUGIN_API_KEY :
				return $this->_getNameByCarrierId($apiOrder->getShippingInfos()->getName());
			/**
			 * switch from mapping
			 */
			default :
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
		$carrier->name = 'Shopgate';
		$carrier->is_module = 1;
		$carrier->deleted = 0;
		$carrier->shipping_external = 1;
		$carrier->id_tax_rules_group = 0;

		$carrier->external_module_name = self::DEFAULT_EXTERNAL_MODULE_CARRIER_NAME;

		foreach (Language::getLanguages() as $language)
			$carrier->delay[$language['id_lang']] = 'Depends on Shopgate selected carrier';

		$carrier->save();
		Configuration::updateValue('SG_CARRIER_ID', $carrier->id);
	}
}
