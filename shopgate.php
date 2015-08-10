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

if (!defined('_PS_VERSION_'))
	exit;

/**
 * define shopgate version
 */
define('SHOPGATE_PLUGIN_VERSION', '2.9.47');

/**
 * define module dir
 */
define('SHOPGATE_DIR', _PS_MODULE_DIR_.'shopgate/');

/**
 * require classes
 */

/**
 * extend
 */
if (!in_array('BWProduct', get_declared_classes()) && version_compare(_PS_VERSION_, '1.5.2.0', '<'))
	require_once(SHOPGATE_DIR.'extend/Product.php');

/**
 * global
 */
require_once(SHOPGATE_DIR.'vendors/shopgate_library/shopgate.php');
require_once(SHOPGATE_DIR.'classes/Config.php');
require_once(SHOPGATE_DIR.'classes/Settings.php');
require_once(SHOPGATE_DIR.'classes/Plugin.php');
require_once(SHOPGATE_DIR.'classes/Helper.php');
require_once(SHOPGATE_DIR.'classes/Shipping.php');
require_once(SHOPGATE_DIR.'classes/Payment.php');
require_once(SHOPGATE_DIR.'classes/Order.php');

/**
 * abstract
 */
require_once(SHOPGATE_DIR.'classes/items/Abstract.php');

/**
 * review
 */
require_once(SHOPGATE_DIR.'classes/items/review/Review.php');
require_once(SHOPGATE_DIR.'classes/items/review/export/Xml.php');

/**
 * category
 */
require_once(SHOPGATE_DIR.'classes/items/category/Category.php');
require_once(SHOPGATE_DIR.'classes/items/category/export/Xml.php');

/**
 * items
 */
require_once(SHOPGATE_DIR.'classes/items/item/Item.php');
require_once(SHOPGATE_DIR.'classes/items/item/export/Xml.php');

/**
 * customer
 */
require_once(SHOPGATE_DIR.'classes/items/customer/Customer.php');
require_once(SHOPGATE_DIR.'classes/items/customer/export/Json.php');
require_once(SHOPGATE_DIR.'classes/items/customer/import/Json.php');

/**
 * order
 */
require_once(SHOPGATE_DIR.'classes/items/order/Order.php');
require_once(SHOPGATE_DIR.'classes/items/order/input/Json.php');

/**
 * cart
 */
require_once(SHOPGATE_DIR.'classes/items/cart/Cart.php');
require_once(SHOPGATE_DIR.'classes/items/cart/export/Json.php');



/**
 * Class ShopGate
 */
class ShopGate extends PaymentModule
{
	/**
	 * install sql file
	 */
	const INSTALL_SQL_FILE = '/setup/install.sql';

	/** @var  ShopgateShipping */
	protected $shopgateShippingModel;

	/** @var  ShopgatePayment */
	protected $shopgatePaymentModel;

	/** @var  array */
	protected $configurations;

	/**
	 * init settings
	 */
	public function __construct()
	{
		$this->bootstrap = true;

		/**
		 * fill models
		 */
		$this->shopgateShippingModel = new ShopgateShipping($this);
		$this->shopgatePaymentModel = new ShopgatePayment($this);

		$this->name = 'shopgate';
		if (version_compare(_PS_VERSION_, '1.5.0.0', '<'))
			$this->tab = 'market_place';
		else
			$this->tab = 'mobile';

		$this->version = '2.9.47';
		$this->author = 'Shopgate';
		$this->module_key = '';

		parent::__construct();

		$this->displayName = $this->l('Shopgate');
		$this->description = $this->l('Sell your products with your individual app and a website optimized for mobile devices.');
		$this->ps_versions_compliancy = array('min' => '1.4', 'max' => '1.6.99.99');
	}

	/**
	 * install
	 *
	 * @return bool
	 */
	public function install()
	{
		/**
		 * delete shopgate config
		 */
		Configuration::deleteByName(ShopgateConfigPrestashop::DEFAULT_CONFIG_NAME);

		/**
		 * hooks
		 */
		$_registerHooks = array(
			'header',
			'adminOrder',
			'updateOrderStatus',
		);
		
		if (version_compare(_PS_VERSION_, '1.5.0.0', '>='))
			$_registerHooks[] = 'displayMobileHeader';
		
		/**
		 * enable debug
		 */
		ShopgateLogger::getInstance()->enableDebug();

		/**
		 * set default settings
		 */
		$this->configurations = ShopgateSettings::getDefaultSettings();

		/**
		 * check parent install
		 */
		ShopGate::log('INSTALLATION - calling parent::install()', ShopgateLogger::LOGTYPE_DEBUG);
		$result = parent::install();
		if (!$result)
		{
			ShopGate::log('parent::install() failed; return value: '
				.var_export($result, true), ShopgateLogger::LOGTYPE_ERROR);
			return false;
		}

		/**
		 * check installed php extensions
		 */
		$missingExtensions = ShopgateHelper::checkLoadedExtensions(
			array('curl')
		);

		if (count($missingExtensions) > 0)
		{
			foreach ($missingExtensions as $missingExtension)
				ShopGate::log(
					sprintf(
						'Installation failed. %s is not installed or loaded.',
						$missingExtension
					),
				ShopgateLogger::LOGTYPE_ERROR);
			return false;
		}

		/**
		 * register hooks
		 */
		ShopGate::log('INSTALLATION - registering hookpoints', ShopgateLogger::LOGTYPE_DEBUG);
		
		foreach ($_registerHooks as $hook)
		{
			ShopGate::log(
				sprintf('INSTALLATION - registering hookpoint %s', $hook),
				ShopgateLogger::LOGTYPE_DEBUG);

			$result = $this->registerHook($hook);
			if (!$result)
			{
				ShopGate::log(
					sprintf('$this->registerHook("%s") failed; return value: %s', $hook, var_export($result, true)),
					ShopgateLogger::LOGTYPE_ERROR);
				return false;
			}
		}

		/**
		 * install data
		 */
		ShopGate::log('INSTALLATION - fetching database object', ShopgateLogger::LOGTYPE_DEBUG);

		/**
		 * install tables
		 */
		if (!$this->installTables())
			return false;

		/**
		 * update tables
		 */
		if (!$this->updateTables())
			return false;

		/**
		 * install shopgate carrier
		 */
		$this->shopgateShippingModel->createShopgateCarrier();

		/**
		 * order states
		 */
		ShopGate::log('INSTALLATION - adding order states', ShopgateLogger::LOGTYPE_DEBUG);
		$this->addOrderState('PS_OS_SHOPGATE', $this->l('Shipping blocked (Shopgate)'));

		/**
		 * save default configuration
		 */
		ShopGate::log('INSTALLATION - setting config values', ShopgateLogger::LOGTYPE_DEBUG);
		$this->configurations['SG_LANGUAGE_ID'] = Configuration::get('PS_LANG_DEFAULT');

		foreach ($this->configurations as $name => $value)
		{
			if (!Configuration::updateValue($name, $value))
			{
				ShopGate::log(
					sprintf(
						'installation failed: unable to save configuration setting "%s" with value "%s"',
						var_export($name, true),
						var_export($value, true)
					),
					ShopgateLogger::LOGTYPE_ERROR
				);

				return false;
			}
		}

		/** @todo register plugin */

		ShopGate::log('INSTALLATION - installation was successful', ShopgateLogger::LOGTYPE_DEBUG);

		return true;
	}

	/**
	 * @return bool
	 */
	protected function installTables()
	{
		$db = Db::getInstance(true);

		if (!file_exists(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE))
			return (false);
		else if (!$sql = version_compare(_PS_VERSION_, '1.4.0.10', '>=') ? Tools::file_get_contents(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE) : file_get_contents(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE))
			return (false);

		$sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
		$sql = preg_split("/;\s*[\r\n]+/", $sql);

		ShopGate::log('INSTALLATION - install tables', ShopgateLogger::LOGTYPE_DEBUG);

		foreach ($sql as $query)
			if ($query)
				if (!$db::getInstance()->execute(trim($query)))
					return false;

		return true;

	}

	/**
	 * uninstall
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		if (!parent::uninstall())
			return false;

		/**
		 * remove carrier
		 */
		if ($shopgateCarrierId = Db::getInstance()->getValue('SELECT `id_carrier` FROM `'._DB_PREFIX_.'carrier` WHERE `name` = "Shopgate"'))
		{
			Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'delivery` WHERE `id_carrier` = '.$shopgateCarrierId);
			Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'range_price` WHERE `id_carrier` = '.$shopgateCarrierId);
			Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'range_weight` WHERE `id_carrier` = '.$shopgateCarrierId);
			Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'carrier_lang` WHERE `id_carrier` = '.$shopgateCarrierId);
			Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'carrier_zone` WHERE `id_carrier` = '.$shopgateCarrierId);
		}

		/**
		 * delete shopgate config
		 */
		Configuration::deleteByName(ShopgateConfigPrestashop::DEFAULT_CONFIG_NAME);

		return true;
	}

	/**
	 * @return mixed
	 */
	public function getContent()
	{
		include_once dirname(__FILE__).'/backward_compatibility/backward.php';

		/** @var ShopgateConfigPrestashop $shopgateConfig */
		$shopgateConfig = new ShopgateConfigPrestashop();

		/** @var mixed $errorMessage */
		$errorMessage = false;

		/** @var LanguageCore $lang */
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

		/**
		 * prepare carrier list
		 */
		$allCarriers    = (defined('Carrier::ALL_CARRIERS') ? Carrier::ALL_CARRIERS : ShopgateShipping::SG_ALL_CARRIERS);
		$carrierList    = Carrier::getCarriers($lang->id, true, false, false, null, $allCarriers);
		$carrierIdColumn = version_compare(_PS_VERSION_, '1.5.0.1', '>=') ? 'id_reference' : 'id_carrier';
		$nativeCarriers = $carrierList;

		/**
		 * save on submit
		 */
		if (Tools::isSubmit('saveConfigurations'))
		{
			$configs = Tools::getValue('configs', array());

			/**
			 * set and store configs
			 */
			foreach ($configs as $key => $value)
				$shopgateConfig->setByKey($key, $value);

			try
			{
				foreach ($shopgateConfig->initFolders() as $key => $value)
					$shopgateConfig->setByKey($key, $value);
				$shopgateConfig->store();
			} catch(ShopgateLibraryException $e)
			{
				$errorMessage = $e->getAdditionalInformation();
			}

			$settings = array();
			foreach ($carrierList as $carrier)
				$settings['SG_MOBILE_CARRIER'][(int)$carrier[$carrierIdColumn]] = 0;
			foreach (Tools::getValue('settings', array()) as $key => $value)
				if (!empty($value) && is_array($value) && !empty($settings[$key]))
					$settings[$key] = $value + $settings[$key];
				else
					$settings[$key] = $value;

			/**
			 * store settings
			 */
			foreach ($settings as $key => $value)
				if (in_array($key, ShopgateSettings::getSettingKeys()))
				{
					if (is_array($value))
						$value = base64_encode(serialize($value));
					Configuration::updateValue($key, htmlentities($value, ENT_QUOTES));
				}
		}

		$languages = array();
		foreach (Language::getLanguages() as $l)
			$languages[$l['iso_code']] = $l['name'];

		$orderStates = array();
		foreach (OrderState::getOrderStates($lang->id) as $key => $orderState)
			$orderStates[$orderState['id_order_state']] = $orderState['name'];

		$newOrderStateMapping = array();
		foreach ($this->shopgatePaymentModel->getPaymentMethods() as $key => $method)
			$newOrderStateMapping[ShopgateSettings::getOrderStateKey($key)] = $method;

		/**
		 * prepare css
		 */
		if (version_compare(_PS_VERSION_, '1.6', '<'))
			$configCss = 'configurations_without_bs.css';
		else
			$configCss = 'configurations.css';

		$mobileCarrierUse     = unserialize(base64_decode(Configuration::get('SG_MOBILE_CARRIER')));
		$resultNativeCarriers = array();
		foreach ($nativeCarriers as $nativeCarrier)
			if ($nativeCarrier['external_module_name'] != ShopgateShipping::DEFAULT_EXTERNAL_MODULE_CARRIER_NAME)
			{
				$nativeCarrier['identifier'] = $nativeCarrier[$carrierIdColumn];
				if (!is_array($mobileCarrierUse))
					$nativeCarrier['mobile_used'] = 1;
				else
					if (!empty($mobileCarrierUse[$nativeCarrier['identifier']]))
						$nativeCarrier['mobile_used'] = 1;
					else
						$nativeCarrier['mobile_used'] = 0;

				array_push($resultNativeCarriers, $nativeCarrier);
			}


		$shopgateCarrier = new Carrier(Configuration::get('SG_CARRIER_ID'), $lang->id);
		array_push($carrierList, array('name' => $shopgateCarrier->name, 'id_carrier' => $shopgateCarrier->id));

		/**
		 * price types
		 */
		$priceTypes = array(
			Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET   => $this->l('Net'),
			Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS => $this->l('Gross')
		);

		/**
		 * fill smarty params
		 */
		$this->context->smarty->assign('error_message', $errorMessage);
		$this->context->smarty->assign('settings', Configuration::getMultiple(ShopgateSettings::getSettingKeys()));
		$this->context->smarty->assign('configs', $shopgateConfig->toArray());
		$this->context->smarty->assign('mod_dir', $this->_path);
		$this->context->smarty->assign('video_url', ShopgateHelper::getVideoLink($this->context));
		$this->context->smarty->assign('offer_url', ShopgateHelper::getOfferLink($this->context));
		$this->context->smarty->assign('api_url', ShopgateHelper::getApiUrl($this->context));
		$this->context->smarty->assign('currencies', Currency::getCurrencies());
		$this->context->smarty->assign('servers', ShopgateHelper::getEnvironments($this));
		$this->context->smarty->assign('shipping_service_list', $this->shopgateShippingModel->getShippingServiceList());
		$this->context->smarty->assign('product_export_descriptions', ShopgateSettings::getProductExportDescriptionsArray($this));
		$this->context->smarty->assign('languages', $languages);
		$this->context->smarty->assign('carrier_list', $carrierList);
		$this->context->smarty->assign('shippingModel', $this->shopgateShippingModel);
		$this->context->smarty->assign('configCss', $configCss);
		$this->context->smarty->assign('product_export_price_type', $priceTypes);
		$this->context->smarty->assign('native_carriers', $resultNativeCarriers);
		
		return $this->display(__FILE__, 'views/templates/admin/configurations.tpl');
	}

	/**
	 * @param $message
	 * @param string $type
	 */
	public static function log($message, $type = ShopgateLogger::LOGTYPE_ERROR)
	{
		ShopgateLogger::getInstance()->log($message, $type);
	}

	/**
	 * @param $state
	 * @param $name
	 * @return bool
	 */
	private function addOrderState($state, $name)
	{
		$orderState = new OrderState((int)Configuration::get($state));
		if (!Validate::isLoadedObject($orderState))
		{
			//Creating new order state
			$orderState->color       = 'lightblue';
			$orderState->unremovable = 1;
			$orderState->name        = array();
			foreach (Language::getLanguages() as $language)
				$orderState->name[$language['id_lang']] = $name;
			if (!$orderState->add())
				return false;

			if (version_compare(_PS_VERSION_, '1.5.5.0', '>='))
				Tools::copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif');
			else
				copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif');

		}

		return ($this->configurations[$state] = $orderState->id);
	}

	/**
	 * @return mixed|string
	 */
	public function hookHeader()
	{
		return ShopgateHelper::calculateRedirect();
	}

	/**
	 * @return mixed|string
	 */
	public function hookDisplayMobileHeader()
	{
		return ShopgateHelper::calculateRedirect();
	}

	/**
	 * @param $params
	 * @return string
	 */
	public function hookAdminOrder($params)
	{
		include_once dirname(__FILE__).'/backward_compatibility/backward.php';

		$shopgateOrder = ShopgateOrderPrestashop::loadByOrderId($params['id_order']);
		
		if ($shopgateOrder->id)
		{
			/** @var ShopgateOrder $apiOrder */
			$apiOrder 		= unserialize(base64_decode($shopgateOrder->shopgate_order));
			
			if (version_compare(_PS_VERSION_, '1.6.0.0', '<='))
			{
				$oldShopVersion = true;
				$this->context->smarty->assign('image_enabled', $this->getAdminImageUrl('enabled.gif'));
				$this->context->smarty->assign('image_disabled', $this->getAdminImageUrl('disabled.gif'));
			}
			else
				$oldShopVersion = false;

			if (!$apiOrder)
				try
				{
					$shopgateConfig = new ShopgateConfigPrestashop();

					$shopgateBuilder     = new ShopgateBuilder($shopgateConfig);
					$shopgateMerchantApi = $shopgateBuilder->buildMerchantApi();
					$shopgateOrders = $shopgateMerchantApi->getOrders(array('order_numbers[0]'=>$shopgateOrder->order_number, 'with_items' => 1))->getData();

					if (!empty($shopgateOrders[0]))
					{
						$shopgateOrder->updateFromOrder($shopgateOrders[0]);
						$apiOrder = $shopgateOrders[0];
						$shopgateOrder->save();
					}
				} catch(ShopgateMerchantApiException $e)
				{
					/**
					 * can not be empty because of the Prestashop validator
					 */
					unset($e);
				}
			
			
			$this->context->smarty->assign('isShopgateOrder', $shopgateOrder->id ? true : false);
			$this->context->smarty->assign('shopgateOrder', $shopgateOrder);
			$this->context->smarty->assign('apiOrder', $apiOrder);
			$this->context->smarty->assign('modDir', $this->_path);
			$this->context->smarty->assign('paymentModel', $this->shopgatePaymentModel);
			$this->context->smarty->assign('shippingModel', $this->shopgateShippingModel);
			$this->context->smarty->assign('mod_dir', $this->_path);
			$this->context->smarty->assign('old_shop_version', $oldShopVersion);
			
			/**
			 * prepare css / js
			 */
			if (version_compare(_PS_VERSION_, '1.6', '<'))
			{
				$orderCss 	= 'order_without_bs.css';
				$requireJs 	= true;
			}
			else
			{
				$orderCss 	= 'order.css';
				$requireJs 	= false;
			}
			
			$this->context->smarty->assign('requireJs', $requireJs);
			$this->context->smarty->assign('orderCss', $orderCss);
			
			/**
			 * prepare show custom fields panel
			 */
			if ($apiOrder && (count($apiOrder->getCustomFields())
				|| count($apiOrder->getInvoiceAddress()->getCustomFields())
				|| count($apiOrder->getDeliveryAddress()->getCustomFields())))
				$this->context->smarty->assign('showCustomFieldsPanel', true);
			else
				$this->context->smarty->assign('showCustomFieldsPanel', false);
			
			return $this->display(__FILE__, 'views/templates/admin/admin_order.tpl');
		}
	}
	
	/**
	 * Carrie module methods
	 *
	 * @param $params
	 * @param $shipping_cost
	 *
	 * @return float
	 */
	public function getOrderShippingCost($params, $shipping_cost)
	{
		return (float)($this->getOrderShippingCostExternal($params, $shipping_cost) + $shipping_cost);
	}
	
	public function getOrderShippingCostExternal($cart)
	{
		$shopgateOrder = ShopgateOrderPrestashop::loadByCartId($cart->id);
		
		return Validate::isLoadedObject($shopgateOrder) ? $shopgateOrder->shipping_cost : 0;
	}
	
	/**
	 * returns the complete url to an admin image
	 *
	 * @param $imageFileName
	 *
	 * @return string
	 */
	private function getAdminImageUrl($imageFileName)
	{
		$adminImageUrl = (defined('_PS_SHOP_DOMAIN_') ? 'http://'._PS_SHOP_DOMAIN_ : _PS_BASE_URL_)._PS_ADMIN_IMG_.$imageFileName;
		$adminImageLocalPath = _PS_IMG_DIR_.'admin/'.$imageFileName;
		return file_exists($adminImageLocalPath) ? $adminImageUrl : '';
	}


	/**
	 * This method gets called when the order status is changed in the admin area of the Prestashop backend
	 * 
	 * @param $params
	 */
	public function hookUpdateOrderStatus($params)
	{
		$id_order      			= $params['id_order'];
		$newOrderState 			= $params['newOrderStatus'];
		$shopgateOrder 			= ShopgateOrderPrestashop::loadByOrderId($id_order);

		$shopgateConfig 		= new ShopgateConfigPrestashop();

		$shopgateBuilder     	= new ShopgateBuilder($shopgateConfig);
		$shopgateMerchantApi 	= $shopgateBuilder->buildMerchantApi();

		if (!is_object($shopgateOrder) || !$shopgateOrder->id_shopgate_order)
			return;

		try
		{

			$shippedOrderStates = array();
			if (version_compare(_PS_VERSION_, '1.5.0.0', '>='))
			{
				$orderStates = OrderState::getOrderStates($this->context->language->id);
				foreach ($orderStates as $orderState)
					if ($orderState['shipped'])
						$shippedOrderStates[$orderState['id_order_state']] = 1;
			}
			else
			{
				// Default methods for Prestashop version < 1.5.0.0
				$shippedOrderStates[_PS_OS_DELIVERED_] = 1;
				$shippedOrderStates[_PS_OS_SHIPPING_]  = 1;
			}

			if (!empty($shippedOrderStates[$newOrderState->id]))
				$shopgateMerchantApi->setOrderShippingCompleted($shopgateOrder->order_number);

		} catch(ShopgateMerchantApiException $e)
		{
			$msg              = new Message();
			$msg->message     = $this->l('On order state').': '.$orderState->name.' - '.$this->l('Shopgate status was not updated because of following error').': '.$e->getMessage();
			$msg->id_order    = $id_order;
			$msg->id_employee = isset($params['cookie']->id_employee) ? $params['cookie']->id_employee : 0;
			$msg->private     = true;
			$msg->add();
		}
	}

	/**
	 * functionality thats executed after the installTables method and mostly because of database table changes in later versions
	 *
	 * @return bool
	 */
	public function updateTables()
	{
		if (!$this->insertColumnToTable(_DB_PREFIX_.'shopgate_order', 'comments', 'text NULL DEFAULT NULL', 'shipping_cost'))
			return false;

		if (!$this->insertColumnToTable(_DB_PREFIX_.'shopgate_order', 'status', "int(1) NOT NULL DEFAULT '0'", 'comments'))
			return false;

		if (!$this->insertColumnToTable(_DB_PREFIX_.'shopgate_order', 'shopgate_order', 'text NULL DEFAULT NULL', 'status'))
			return false;

		return true;

	}

	/**
	 * Insert a new column to database $tableName in case it is not yet inserted
	 *
	 * @param $tableName - full table name
	 * @param $fieldName - the column to add
	 * @param $insertAfterColumn - the position to insert the column
	 *
	 * @return bool
	 */
	protected function insertColumnToTable($tableName, $fieldName, $columnProperties, $insertAfterColumn)
	{
		$db = Db::getInstance(true);

		$this->log('INSTALLATION - checking for field "'.$fieldName.'" inside table "'.$tableName.'"', ShopgateLogger::LOGTYPE_DEBUG);
		$db->Execute('SHOW COLUMNS FROM `'.$tableName.'` LIKE \''.$fieldName.'\';');
		if (!$db->NumRows())
		{
			$this->log('INSTALLATION - creating field "'.$fieldName.'" inside table "'.$tableName.'"', ShopgateLogger::LOGTYPE_DEBUG);
			if ($db->Execute('ALTER TABLE `'.$tableName.'` ADD `'.$fieldName.'` '.$columnProperties.' AFTER `'.$insertAfterColumn.'`;') === false)
			{
				$this->log('installation failed: unable to add field "'.$fieldName.'" to table "'.$tableName.'". MySQL says: '.var_export($db->getMsgError(), true), ShopgateLogger::LOGTYPE_ERROR);

				return false;
			}
		}

		return true;
	}
}
