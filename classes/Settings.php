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

class ShopgateSettings
{
	const PRODUCT_EXPORT_DESCRIPTION = 'DESCRIPTION';
	const PRODUCT_EXPORT_SHORT_DESCRIPTION = 'SHORT';
	const PRODUCT_EXPORT_BOTH_DESCRIPTIONS = 'BOTH';

	const DEFAULT_ORDER_NEW_STATE_KEY_PATTERN = 'SG_ONS_%s';

	/**
	 * settings keys
	 *
	 * @return array
	 */
	public static function getSettingKeys()
	{
		return array_keys(ShopgateSettings::getDefaultSettings());
	}

	/**
	 * @return array
	 */
	public static function getDefaultSettings()
	{
		$configuration = array(
			'PS_OS_SHOPGATE' => 0,
			'SG_LANGUAGE_ID' => 0,
			'SG_MIN_QUANTITY_CHECK' => 0,
			'SG_OUT_OF_STOCK_CHECK' => 0,
			'SG_PRODUCT_DESCRIPTION' => self::PRODUCT_EXPORT_DESCRIPTION,
			'SG_SUBSCRIBE_NEWSLETTER' => 0,
			'SG_EXPORT_ROOT_CATEGORIES' => 0,
			'SG_CARRIER_MAPPING' => array(),
			'SHOPGATE_EXPORT_PRICE_TYPE' => Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET
		);

		return $configuration;
	}

	/**
	 * @param $paymentIdentifier
	 * @return string
	 */
	public static function getOrderStateKey($paymentIdentifier)
	{
		return sprintf(self::DEFAULT_ORDER_NEW_STATE_KEY_PATTERN, $paymentIdentifier);
	}

	/**
	 * @param $module
	 * @return array
	 */
	public static function getProductExportDescriptionsArray($module)
	{
		return array(
			self::PRODUCT_EXPORT_DESCRIPTION => $module->l('Description'),
			self::PRODUCT_EXPORT_SHORT_DESCRIPTION => $module->l('Short Description'),
			self::PRODUCT_EXPORT_BOTH_DESCRIPTIONS => $module->l('Short Description + Description')
		);
	}

	/**
	 * @param $module
	 * @return array
	 */
	protected static function getCustomerGroups($module)
	{
		/**
		 * customer groups
		 */
		$customerGroupsItems = Group::getGroups(
			$module->context->language->id,
			$module->context->shop->id ? $module->context->shop->id : false
		);

		$customerGroups = array();

		if (is_array($customerGroupsItems))
			foreach ($customerGroupsItems as $customerGroupsItem)
			{
				$group = array();
				$group['id'] = $customerGroupsItem['id_group'];
				$group['name'] = $customerGroupsItem['name'];
				$group['is_default'] = $group['id'] == (int)Configuration::get('PS_GUEST_GROUP') ? true : false;
				array_push($customerGroups, $group);
			}

		return $customerGroups;
	}

	/**
	 * @param $module
	 * @return array
	 */
	protected static function getProductTaxClasses($module)
	{
		$productTaxClassItems = Tax::getTaxes($module->context->language->id);
		$productTaxClasses = array();

		if (is_array($productTaxClassItems) && Configuration::get('PS_TAX') == 1)
			foreach ($productTaxClassItems as $productTaxClassItem)
			{
				$taxClass = array();
				$taxClass['id'] = $productTaxClassItem['id_tax'];
				$taxClass['key'] = $productTaxClassItem['name'];
				array_push($productTaxClasses, $taxClass);
			}

		return $productTaxClasses;
	}
	
	public static function arrayValueExists($key, $value, $data)
	{
		if (!is_array($data))
			return false;
		
		foreach ($data as $dataItem)
		{
			if ($dataItem[$key] == $value)
				return true;
		}
		return false;
	}
	
	/**
	 * @param $module
	 * @return array
	 */
	protected static function getTaxRates($module)
	{
		$taxRates = array();

		$taxRuleGroups = TaxRulesGroup::getTaxRulesGroups(true);

		foreach ($taxRuleGroups as $taxRuleGroup)
		{
			foreach (TaxRule::getTaxRulesByGroupId($module->context->language->id, $taxRuleGroup['id_tax_rules_group']) as $taxRuleItem)
			{
				
				/** @var TaxRuleCore $taxRuleItem */
				if (version_compare(_PS_VERSION_, '1.4.11.0', '<='))
					$taxId = $taxRuleItem[0][0]['id_tax'];
				else
					$taxId = $taxRuleItem['id_tax_rule'];
				
				$taxRuleItemTmp = new TaxRule($taxId);

				/** @var TaxCore $taxItem */
				$taxItem = new Tax($taxRuleItemTmp->id_tax, $module->context->language->id);
				$country = Country::getIsoById($taxRuleItemTmp->id_country);
				
				if (version_compare(_PS_VERSION_, '1.5.0.1', '<'))
					$state = State::getNameById($taxRuleItemTmp->id_state);
				else
				{
					$stateModel = new State($taxRuleItemTmp->id_state);
					$state = $stateModel->iso_code;
				}
				
				$resultTaxRule = array();
				$resultTaxRule['id'] = $taxRuleItemTmp->id;

				if ($state)
					$resultTaxRule['key'] = $taxRuleGroup['name'].$taxItem->name.'-'.$country.'-'.$state;
				else
					$resultTaxRule['key'] = $taxRuleGroup['name'].$taxItem->name.'-'.$country;

				$resultTaxRule['key'] .= '-'.$taxRuleItemTmp->id;
				
				//Fix for 1.4.x.x the taxes were exported multiple
				if (version_compare(_PS_VERSION_, '1.5.0', '<') 
					&& self::arrayValueExists('key', $resultTaxRule['key'], $taxRates))
						continue;
				
				$resultTaxRule['display_name'] 			= $taxItem->name;
				$resultTaxRule['tax_percent'] 			= $taxItem->rate;
				$resultTaxRule['country'] 				= $country;
				$resultTaxRule['state'] 				= (!empty($state)) ? $country.'-'.$state : null;

				$resultTaxRule['zipcode_type'] 			= 'range';
				$resultTaxRule['zipcode_range_from'] 	= $taxRuleItemTmp->zipcode_from ? $taxRuleItemTmp->zipcode_from : null;
				$resultTaxRule['zipcode_range_to'] 		= $taxRuleItemTmp->zipcode_to ? $taxRuleItemTmp->zipcode_to : null;

				
				if ($taxItem->active && Configuration::get('PS_TAX') == 1)
					array_push($taxRates, $resultTaxRule);
			}
		}

		return $taxRates;
	}

	/**
	 * @param $module
	 * @return mixed
	 */
	protected function getTaxRules($module)
	{
		$taxRules = array();

		$taxRuleGroups = TaxRulesGroup::getTaxRulesGroups(true);

		foreach ($taxRuleGroups as $taxRuleGroup)
		{

			/** @var TaxCore $taxItem */
			$taxItem = ShopgateSettings::getTaxItemByTaxRuleGroupId($taxRuleGroup['id_tax_rules_group']);

			$rule = array(
				'id' => $taxRuleGroup['id_tax_rules_group'],
				'name' => $taxRuleGroup['name'],
				'priority' => 0
			);
			
			$rule['product_tax_classes'] = array(
				array(
					'id' => $taxItem->id,
					'key' => is_array($taxItem->name) ? reset($taxItem->name) : '',
				)
			);

			$rule['customer_tax_classes'] = array(
				array(
					'key' => 'default',
					'is_default' => true
				)
			);

			$rule['tax_rates'] = array();

			foreach (TaxRule::getTaxRulesByGroupId($module->context->language->id, $taxRuleGroup['id_tax_rules_group']) as $taxRuleItem)
			{
				if (version_compare(_PS_VERSION_, '1.4.11.0', '<='))
					$taxId = $taxRuleItem[0][0]['id_tax'];
				else
					$taxId = $taxRuleItem['id_tax_rule'];
				
				/** @var TaxRuleCore $taxRuleItem */
				$taxRuleItem = new TaxRule($taxId);

				/** @var TaxCore $taxItem */
				$taxItem = new Tax($taxRuleItem->id_tax, $module->context->language->id);

				$country = Country::getIsoById($taxRuleItem->id_country);
				$stateModel = new State($taxRuleItem->id_state);
				$state = $stateModel->iso_code;

				$resultTaxRule = array();
				$resultTaxRule['id'] = $taxRuleItem->id;
				if ($state)
					$resultTaxRule['key'] = $taxItem->name.'-'.$country.'-'.$state;
				else
					$resultTaxRule['key'] = $taxItem->name.'-'.$country;
				$resultTaxRule['key'] .= '-'.$taxRuleItem->id;
				
				//Fix for 1.4.x.x the taxes were exported multiple
				if (self::arrayValueExists('key', $resultTaxRule['key'], $rule['tax_rates']))
					continue;
				
				array_push($rule['tax_rates'], $resultTaxRule);
			}

			if ($taxItem->active && Configuration::get('PS_TAX') == 1)
				array_push($taxRules, $rule);
		}

		return $taxRules;
	}

	/**
	 * @param $ruleGroupId
	 *
	 * @return bool|Tax
	 */
	public static function getTaxItemByTaxRuleGroupId($ruleGroupId)
	{
		$select = sprintf(
			'SELECT DISTINCT id_tax from %stax_rule WHERE id_tax_rules_group = %d',
			_DB_PREFIX_,
			$ruleGroupId
		);

		$result = Db::getInstance()->getRow($select);

		if (is_array($result) && isset($result['id_tax']))
			return new Tax($result['id_tax']);


		return false;
	}

	/**
	 * @param ShopgatePluginPrestashop $module
	 * @return array
	 */
	public static function getShopgateSettings($module)
	{
		$result = array();

		/**
		 * customer groups
		 */
		$result['customer_groups'] = ShopgateSettings::getCustomerGroups($module);

		/**
		 * product tax
		 */
		$result['tax']['product_tax_classes'] = ShopgateSettings::getProductTaxClasses($module);

		/**
		 * customer tax classes
		 */
		$result['tax']['customer_tax_classes'] = array(
			array(
				'key' => 'default',
				'is_default' => true
			)
		);

		/**
		 * tax rates
		 */
		$result['tax']['tax_rates'] = ShopgateSettings::getTaxRates($module);

		/**
		 * tax rules
		 */
		$result['tax']['tax_rules'] = ShopgateSettings::getTaxRules($module);

		return $result;
	}
}
