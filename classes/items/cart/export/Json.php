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

class ShopgateItemsCartExportJson extends ShopgateItemsCart
{
	/**
	 * default dummy first name
	 */
	const DEFAULT_CUSTOMER_FIRST_NAME = 'shopgate';

	/**
	 * default dummy last name
	 */
	const DEFAULT_CUSTOMER_LAST_NAME = 'shopgate';

	/**
	 * default dummy email
	 */
	const DEFAULT_CUSTOMER_EMAIL = 'example@shopgate.com';

	/**
	 * default dummy password
	 */
	const DEFAULT_CUSTOMER_PASSWD = '123shopgate';

	/**
	 * default dummy alias
	 */
	const DEFAULT_ADDRESS_ALIAS = 'shopgate_check_cart';

	/**
	 * default check stock qty
	 */
	const DEFAULT_QTY_TO_CHECK = 1;

	/**
	 * @var Address
	 */
	protected $_deliveryAddress;

	/**
	 * @var Address
	 */
	protected $_invoiceAddress;

	/**
	 * @var AddressCore
	 */
	protected $_Address;

	/**
	 * @var bool
	 */
	protected $_isDummyCustomer = false;

	/**
	 * @param ShopgateCart $cart
	 * @return array
	 */
	public function checkStock(ShopgateCart $cart)
	{
		$result = array();

		foreach ($cart->getItems() as $item)
		{

			$cartItem = new ShopgateCartItem();
			$cartItem->setItemNumber($item->getItemNumber());

			list ($productId, $attributeId) = ShopgateHelper::getProductIdentifiers($item);
			
			/** @var ProductCore $product */
			if (version_compare(_PS_VERSION_, '1.5.2.0', '<'))
				$product = new BWProduct($productId, true, $this->getPlugin()->getLanguageId());
			else
				$product = new Product($productId, $this->getPlugin()->getLanguageId());

			if (empty($attributeId) && !empty($productId) && $product->hasAttributes())
			{
				array_push($result, $cartItem);
				continue;
			}

			$product->loadStockData();
			/**
			 * validate attributes
			 */
			if ($product->hasAttributes())
			{
				$invalidAttribute = false;
				$message = '';

				if (!$attributeId)
				{
					$cartItem->setError(ShopgateLibraryException::UNKNOWN_ERROR_CODE);
					$cartItem->setErrorText('attributeId required');
					$message = 'attributeId required';
					$invalidAttribute = true;
				}
				else
				{
					$validAttributeId = false;
					
					if (version_compare(_PS_VERSION_, '1.5.0', '<'))
						$attributeIds = BWProduct::getProductAttributesIds($productId);
					else
						$attributeIds = $product->getProductAttributesIds($productId, true);
					
					foreach ($attributeIds as $attribute)
					if ($attributeId == $attribute['id_product_attribute'])
					{
						$validAttributeId = true;
						continue;
					}

					if (!$validAttributeId)
					{
						$invalidAttribute = true;
						$message = 'invalid attributeId';
					}
				}

				if ($invalidAttribute)
				{
					$cartItem->setError(ShopgateLibraryException::UNKNOWN_ERROR_CODE);
					$cartItem->setErrorText($message);
					array_push($result, $cartItem);
					continue;
				}
			}

			if ($product->id)
			{
				
				if (version_compare(_PS_VERSION_, '1.5.0', '<'))
					$quantity = $product->getStockAvailable();//getQuantityAvailableByProduct($productId, $attributeId, $this->getPlugin()->getContext()->shop->id);
				else
					$quantity = StockAvailable::getQuantityAvailableByProduct($productId, $attributeId, $this->getPlugin()->getContext()->shop->id);
				
				$cartItem->setStockQuantity($quantity);
				$cartItem->setIsBuyable(
					$product->available_for_order
					&& ($attributeId ? Attribute::checkAttributeQty(
						$attributeId,
						ShopgateItemsCartExportJson::DEFAULT_QTY_TO_CHECK
					)
						: $product->checkQty(ShopgateItemsCartExportJson::DEFAULT_QTY_TO_CHECK)
					) || Product::isAvailableWhenOutOfStock($product->out_of_stock) ? 1 : 0);
			}
			else
			{
				$cartItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);
				$cartItem->setErrorText(ShopgateLibraryException::getMessageFor($cartItem->getError()));
			}

			array_push($result, $cartItem);
		}

		return $result;
	}

	/**
	 * @param ShopgateCart $cart
	 * @return array
	 */
	public function checkCart(ShopgateCart $cart)
	{
		$this->_createCustomer($cart);

		if ($cart->getDeliveryAddress())
		{
			if (!$cart->getDeliveryAddress()->getPhone())
				$cart->getDeliveryAddress()->setPhone($cart->getPhone());

			$this->_deliveryAddress = $this->_createAddress($cart->getDeliveryAddress());
			$this->_deliveryAddress->id_customer = $this->getPlugin()->getContext()->customer->id;
			$this->_deliveryAddress->save();

			$this->getPlugin()->getContext()->cart->id_address_delivery = $this->_deliveryAddress->id;
			$this->getPlugin()->getContext()->cart->save();
		}

		if ($cart->getInvoiceAddress())
		{
			if (!$cart->getInvoiceAddress()->getPhone())
				$cart->getInvoiceAddress()->setPhone($cart->getPhone());

			$this->_invoiceAddress = $this->_createAddress($cart->getInvoiceAddress());
			$this->_deliveryAddress->id_customer = $this->getPlugin()->getContext()->customer->id;
			$this->_invoiceAddress->save();

			$this->getPlugin()->getContext()->cart->id_address_invoice = $this->_invoiceAddress->id;
			$this->getPlugin()->getContext()->cart->save();
		}

		/**
		 * don't change the direction
		 */
		$result = array(
			'items' => $this->_addItems($cart),
			'external_coupons' => $this->_addCoupons($cart),
			'currency' => $this->_getCurrency(),
			'customer' => $this->_getCustomerGroups($cart),
			'shipping_methods' => $this->_getCarriers(),
			'payment_methods' => array()
		);

		return $result;
	}

	/**
	 * @param ShopgateCart $cart
	 * @return array
	 * @throws PrestaShopException
	 */
	protected function _addCoupons(ShopgateCart $cart)
	{
		$results = array();

		foreach ($cart->getExternalCoupons() as $coupon)
		{

			$result = new ShopgateExternalCoupon();
			$result->setCode($coupon->getCode());
			$result->setCurrency($this->_getCurrency());
			/** @var CartRuleCore $cartRule */
			$cartRule = new CartRule(CartRule::getIdByCode($coupon->getCode()));
			if (Validate::isLoadedObject($cartRule))
			{
				$result->setName($cartRule->getFieldByLang('name'), $this->getPlugin()->getContext()->language->id);
				$result->setDescription($cartRule->getFieldByLang('description', $this->getPlugin()->getContext()->language->id));
				$result->setTaxType(Translate::getAdminTranslation('not_taxable'));
				$result->setAmountGross($cartRule->getContextualValue(true, $this->getPlugin()->getContext()));

				$result->setIsFreeShipping((bool)$cartRule->free_shipping);

				/**
				 * validate coupon
				 */
				if ($validateException = $cartRule->checkValidity($this->getPlugin()->getContext(), false, true))
				{
					$result->setIsValid(false);
					$result->setNotValidMessage($validateException);
				}
				else
				{
					$result->setIsValid(true);
					$this->getPlugin()->getContext()->cart->addCartRule($cartRule->id);
					$this->getPlugin()->getContext()->cart->save();
				}
			}
			else
			{
				$result->setIsValid(false);
				$result->setNotValidMessage(Tools::displayError('This voucher does not exists.'));
			}

			array_push($results, $result);
		}

		return $results;
	}

	/**
	 * @param ShopgateCart $cart
	 * @return ShopgateCartCustomer
	 */
	protected function _getCustomerGroups(ShopgateCart $cart)
	{
		$customer = new ShopgateCartCustomer();

		$group = new ShopgateCartCustomerGroup();
		$group->setId(
			$cart->getExternalCustomerGroupId() ?
				$cart->getExternalCustomerGroupId() :
				Configuration::get('PS_UNIDENTIFIED_GROUP')
		);

		$customer->setCustomerGroups(array($group));

		return $customer;
	}

	/**
	 * @param $cart
	 * @return array
	 */
	protected function _addItems($cart)
	{
		$resultItems = array();

		foreach ($cart->getItems() as $item)
		{

			list($productId, $attributeId) = ShopgateHelper::getProductIdentifiers($item);

			/** @var ProductCore $product */
			$product = new Product($productId);

			$resultItem = new ShopgateCartItem();
			$resultItem->setItemNumber($item->getItemNumber());
			$resultItem->setStockQuantity($product->getQuantity($product->id, $attributeId));

			$resultItem->setUnitAmount($product->getPrice(false, $attributeId));
			$resultItem->setUnitAmountWithTax($product->getPrice(true, $attributeId));

			$resultItem->setOptions($item->getOptions());
			$resultItem->setAttributes($item->getAttributes());
			$resultItem->setInputs($item->getInputs());

			/**
			 * validate product
			 */
			if (!$this->_validateProduct($product, $attributeId))
			{
				$this->_addItemException(
					$resultItem,
					ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND,
					sprintf(
						'ProductId #%s AttributeId #%s',
						$productId,
						$attributeId
					)
				);
				array_push($resultItems, $resultItem);
				continue;
			}

			$addItemResult = $this->getPlugin()->getContext()->cart->updateQty(
				$item->getQuantity(),
				$productId,
				$attributeId,
				false,
				'up',
				($this->_deliveryAddress && $this->_deliveryAddress->id) ? $this->_deliveryAddress->id : 0
			);
			$this->getPlugin()->getContext()->cart->save();
			if ($addItemResult != 1)
			{
				$resultItem->setIsBuyable(false);
				$resultItem->setQtyBuyable($product->getQuantity($productId, $attributeId));

				/**
				 * add error
				 */
				switch ($addItemResult)
				{
					case -1:
						$resultItem->setQtyBuyable(
							$attributeId ?
								(int)Attribute::getAttributeMinimalQty($attributeId) :
								(int)$product->minimal_quantity
						);
						$this->_addItemException(
							$resultItem, ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_UNDER_MINIMUM_QUANTITY);
						break;
					default:
						$this->_addItemException(
							$resultItem,
							ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE);
						break;
				}

			}
			else
			{
				$resultItem->setIsBuyable(true);
				$resultItem->setQtyBuyable((int)$item->getQuantity());
			}

			array_push($resultItems, $resultItem);
		}

		return $resultItems;
	}

	/**
	 * @return mixed
	 */
	protected function _getCurrency()
	{
		return $this->getPlugin()->getContext()->currency->iso_code;
	}

	/**
	 * @param ProductCore $product
	 * @param $attributeId
	 * @return bool
	 */
	protected function _validateProduct(ProductCore $product, $attributeId)
	{
		if (Validate::isLoadedObject($product))
		{
			if ($attributeId)
			{
				if (version_compare(_PS_VERSION_, '1.5.0', '<'))
					$attributeIds = BWProduct::getProductAttributesIds($product->id);
				else
					$attributeIds = $product->getProductAttributesIds($product->id, true);
				
				foreach ($attributeIds as $id)
					if ($id['id_product_attribute'] == $attributeId)
						return true;
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * create carriers
	 *
	 * @return mixed
	 */
	protected function _getCarriers()
	{
		$resultsCarrier = array();

		$mobileCarrierUse = unserialize(base64_decode(Configuration::get('SG_MOBILE_CARRIER')));

		if ($this->_deliveryAddress)
		{
			foreach (Carrier::getCarriersForOrder(Address::getZoneById($this->_deliveryAddress->id), $this->getPlugin()->getContext()->customer->getGroups(), $this->getPlugin()->getContext()->cart) as $carrier)
			{
				/** @var CarrierCore $carrierItem */
				$carrierItem = new Carrier($carrier['id_carrier'], $this->getPlugin()->getContext()->language->id);
				$taxRulesGroup = new TaxRulesGroup($carrierItem->id_tax_rules_group);
				$resultCarrier = new ShopgateShippingMethod();

				/**
				 * check is defined as mobile carrier
				 */
				$idColumn = version_compare(_PS_VERSION_, '1.5.0.1', '>=') ? 'id_reference' : 'id_carrier';
				if (is_array($mobileCarrierUse) && empty($mobileCarrierUse[$carrier[$idColumn]]))
					continue;

				$resultCarrier->setId($carrier['id_carrier']);
				$resultCarrier->setTitle($carrier['name']);
				$resultCarrier->setDescription($carrier['delay']);
				$resultCarrier->setSortOrder($carrier['position']);
				$resultCarrier->setAmount($carrier['price_tax_exc']);
				$resultCarrier->setAmountWithTax($carrier['price']);
				$resultCarrier->setTaxClass($taxRulesGroup->name);
				
				if (version_compare(_PS_VERSION_, '1.5.0', '<'))
					$carrierTax = Tax::getCarrierTaxRate($carrierItem->id, $this->_deliveryAddress->id);
				else
					$carrierTax = $carrierItem->getTaxesRate($this->_deliveryAddress);
				
				$resultCarrier->setTaxPercent($carrierTax);
				$resultCarrier->setInternalShippingInfo(serialize(array('carrierId' => $carrier['id_carrier'])));

				array_push($resultsCarrier, $resultCarrier);
			}
		}

		return $resultsCarrier;
	}

	/**
	 * create dummy customer
	 *
	 * @param ShopgateCart $cart
	 */
	protected function _createCustomer(ShopgateCart $cart)
	{
		/**
		 * prepare customer group
		 */
		if ($cart->getExternalCustomerId())
		{
			/**
			 * load exist customer
			 */
			$this->getPlugin()->getContext()->customer = new Customer($cart->getExternalCustomerId());
			if (!Validate::isLoadedObject($this->getPlugin()->getContext()->customer))
			{
				$this->_addException(
					ShopgateLibraryException::UNKNOWN_ERROR_CODE,
					sprintf(
						'Customer with id #%s not found',
						$cart->getExternalCustomerId()
					)
				);
			}
		}
		else
		{
			/**
			 * create dummy customer
			 */
			$customerGroup = $this->_getCustomerGroups($cart);
			$this->getPlugin()->getContext()->customer = new Customer();
			$this->getPlugin()->getContext()->customer->lastname = self::DEFAULT_CUSTOMER_LAST_NAME;
			$this->getPlugin()->getContext()->customer->firstname = self::DEFAULT_CUSTOMER_FIRST_NAME;
			$this->getPlugin()->getContext()->customer->email = self::DEFAULT_CUSTOMER_EMAIL;
			$this->getPlugin()->getContext()->customer->passwd = self::DEFAULT_CUSTOMER_PASSWD;
			$this->getPlugin()->getContext()->customer->id_default_group =
				current($customerGroup->getCustomerGroups())->getId();

			$this->getPlugin()->getContext()->customer->add();

			$this->_isDummyCustomer = true;
		}

		/**
		 * add customer to cart
		 */
		$this->getPlugin()->getContext()->cart->id_customer = $this->getPlugin()->getContext()->customer->id;

		/**
		 * add carrier id
		 */
		$shippingModel = new ShopgateShipping($this->getModule());

		$tmpOrder = new ShopgateOrder();
		$tmpOrder->setShippingType($cart->getShippingType() ? $cart->getShippingType() : ShopgateShipping::DEFAULT_PLUGIN_API_KEY);
		$tmpOrder->setShippingGroup($cart->getShippingGroup());
		$tmpOrder->setShippingInfos($cart->getShippingInfos());

		/** @var CarrierCore $carrierItem */
		$carrierItem = new Carrier($shippingModel->getCarrierIdByApiOrder($tmpOrder));

		if (!Validate::isLoadedObject($carrierItem))
			$this->_addException(
				ShopgateLibraryException::UNKNOWN_ERROR_CODE,
				sprintf(
					'Invalid carrier ID #%s',
					$shippingModel->getCarrierIdByApiOrder($tmpOrder)
				)
			);

		$this->getPlugin()->getContext()->cart->id_carrier = $carrierItem->id;
		$this->getPlugin()->getContext()->cart->save();
	}

	/**
	 * @param ShopgateAddress $address
	 * @return AddressCore
	 */
	protected function _createAddress(ShopgateAddress $address)
	{
		/** @var AddressCore $resultAddress */
		$resultAddress = new Address();

		$resultAddress->id_country = $this->_getCountryIdByIsoCode($address->getCountry());
		$resultAddress->alias = self::DEFAULT_ADDRESS_ALIAS;
		$resultAddress->firstname = $address->getFirstName();
		$resultAddress->lastname = $address->getLastName();
		$resultAddress->address1 = $address->getStreet1();
		$resultAddress->postcode = $address->getZipcode();
		$resultAddress->city = $address->getCity();
		$resultAddress->country = $address->getCountry();
		$resultAddress->phone = $address->getPhone() ? $address->getPhone() : 1;
		$resultAddress->phone_mobile = $address->getMobile() ? $address->getMobile() : 1;

		/**
		 * check is state iso code available
		 */
		if ($address->getState() != '')
			$resultAddress->id_state = $this->getStateIdByIsoCode($address->getState());

		$resultAddress->company = $address->getCompany();

		return $resultAddress;
	}

	/**
	 * @param $isoCode
	 * @return mixed
	 * @throws ShopgateLibraryException
	 */
	protected function getStateIdByIsoCode($isoCode)
	{
		$stateId = null;
		if ($isoCode)
		{
			$stateParts = explode('-', $isoCode);
			if (is_array($stateParts))
				if (count($stateParts) == 2)
					$stateId = State::getIdByIso(
						$stateParts[1],
						$this->_getCountryIdByIsoCode($stateParts[0])
					);
				else
					$stateId = State::getIdByIso($stateParts[0]);

			if ($stateId)
				return $stateId;
			else
				$this->_addException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, ' invalid or empty iso code #'.$isoCode);
		}
	}

	/**
	 * @param $isoCode
	 * @return mixed
	 * @throws ShopgateLibraryException
	 */
	protected function _getCountryIdByIsoCode($isoCode)
	{
		if ($isoCode && $countryId = Country::getByIso($isoCode))
			return $countryId;
		else
			$this->_addException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, ' invalid or empty iso code #'.$isoCode);
	}

	/**
	 * add exception
	 *
	 * @param int $errorCoded
	 * @param bool $message
	 * @param bool $writeLog
	 *
	 * @throws ShopgateLibraryException
	 */
	protected function _addException($errorCoded = ShopgateLibraryException::UNKNOWN_ERROR_CODE, $message = false, $writeLog = false)
	{
		throw new ShopgateLibraryException($errorCoded, $message, true, $writeLog);
	}

	/**
	 * add a item exception
	 *
	 * @param ShopgateCartItem $item
	 * @param $code
	 * @param mixed $message
	 */
	protected function _addItemException(ShopgateCartItem $item, $code, $message = false)
	{
		$item->setError($code);
		$item->setErrorText(ShopgateLibraryException::getMessageFor($code).($message ? ' - '.$message : ''));
	}

	/**
	 * clear DB
	 */
	public function __destruct()
	{
		/**
		 * delete customer
		 */
		if ($this->getPlugin()->getContext() && $this->_isDummyCustomer)
		{
			$this->getPlugin()->getContext()->customer->delete();
			// "delete" function calls deleteByIdCustomer.
			// In version 1.4.x.x this logic only deletes discounts defined to an user, but not the user itself.
			// It's needed to delete the user entry manually
			if (version_compare(_PS_VERSION_, '1.5.0', '<'))
				Db::getInstance()->Execute(
					'DELETE FROM `'._DB_PREFIX_.'customer` 
				WHERE `id_customer` = '.(int)($this->getPlugin()->getContext()->customer->id));
		}

		/**
		 * delete delivery address
		 */
		if ($this->_deliveryAddress && $this->_deliveryAddress->id)
			$this->_deliveryAddress->delete();

		/**
		 * delete invoice address
		 */
		if ($this->_invoiceAddress && $this->_invoiceAddress->id)
			$this->_invoiceAddress->delete();

		/**
		 * delete cart
		 */
		if ($this->getPlugin()->getContext()->cart->id)
			$this->getPlugin()->getContext()->cart->delete();
	}
}
