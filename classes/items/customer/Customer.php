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

class ShopgateItemsCustomer extends ShopgateItemsAbstract
{
	/**
	 * @var array
	 */
	protected $gender = array(
		ShopgateCustomer::MALE => 1,
		ShopgateCustomer::FEMALE => 2
	);

	/**
	 * @var array
	 */
	public static $validationAddressFields = array(
		'id_customer',
		'alias',
		'company',
		'lastname',
		'firstname',
		'vat_number',
		'address1',
		'address2',
		'postcode',
		'city',
		'other',
		'phone',
		'prone_mobile'
	);

	/**
	 * @param $emil
	 * @param $password
	 * @return int
	 */
	public function getCustomerIdByEmailAndPassword($emil, $password)
	{
		return (int)Db::getInstance()->getValue('
			SELECT `id_customer`
			FROM `'._DB_PREFIX_.'customer`
			WHERE
			`active` AND
			`email` = \''.pSQL($emil).'\' AND
			`passwd` = \''.Tools::encrypt($password).'\' AND
			`deleted` = 0
			AND `is_guest` = 0');
	}

	/**
	 * shopgate / prestashop :-(
	 *
	 * @param $value
	 * @return bool|int|string
	 */
	public function mapGender($value)
	{
		foreach ($this->gender as $key => $val)
			if (ctype_digit($value))
				if ($val == $value)
					return $key;
				else;//no statement
			else
				if ($key == $value)
					return $val;
		
		return false;
	}

	/**
	 * @param AddressCore $addressOne
	 * @param AddressCore $addressTwo
	 * @return bool
	 */
	public static function compareAddresses($addressOne, $addressTwo)
	{
		foreach (ShopgateItemsCustomer::$validationAddressFields as $field)
			if ($addressOne->$field != $addressTwo->$field)
				return false;

		return true;
	}
}
