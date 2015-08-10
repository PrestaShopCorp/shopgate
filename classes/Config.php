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

class ShopgateConfigPrestashop extends ShopgateConfig
{
	/**
	 * default plugin name
	 */
	const DEFAULT_PLUGIN_NAME = 'prestashop';

	/**
	 * default config identifier
	 */
	const DEFAULT_CONFIG_NAME = 'SHOPGATE_CONFIG';

	/**
	 * init defaults
	 */
	protected function initDefaults()
	{
		$this->plugin_name = self::DEFAULT_PLUGIN_NAME;
		$this->enable_ping = 1;

		$this->supported_fields_check_cart =
			array(
				'external_coupons',
				'shipping_methods',
				'items'
		);

		/**
		 * get
		 */
		$this->enable_ping = 1;
		$this->enable_add_order = 1;
		$this->enable_update_order = 1;
		$this->enable_check_cart = 1;
		$this->enable_check_stock = 1;
		$this->enable_get_orders = 1;
		$this->enable_get_debug_info = 1;
		$this->enable_redeem_coupons = 1;
		$this->enable_get_reviews = 1;
		$this->enable_get_items = 1;
		$this->enable_get_categories = 1;
		$this->enable_get_settings = 1;
		$this->enable_register_customer = 1;
		$this->enable_get_customer = 1;
		$this->enable_add_order = 1;
		$this->enable_get_log_file = 1;
		$this->enable_cron = 1;
		$this->enable_clear_log_file = 1;
		$this->enable_clear_cache = 1;
		$this->enable_get_settings = 1;

		/**
		 * set
		 */
		$this->enable_set_settings = 1;

		/**
		 * misc
		 */
		$this->enable_mobile_website = 1;
	}

	/**
	 * create defaults / load from config
	 *
	 * @return bool|void
	 */
	public function startup()
	{
		if (!Configuration::get(self::DEFAULT_CONFIG_NAME))
			$this->cleanupConfig();

		$this->setFromConfig();

		return true;
	}

	/**
	 * set by key
	 *
	 * @param $key
	 * @param $value
	 */
	public function setByKey($key, $value)
	{
		$this->$key = $value;
	}

	/**
	 * store config
	 */
	public function store()
	{
		$vars = array();
		foreach (get_object_vars($this) as $key => $value)
			$vars[$key] = $value;

		Configuration::updateValue(self::DEFAULT_CONFIG_NAME, base64_encode(serialize($vars)));
	}

	/**
	 * set from config
	 */
	protected function setFromConfig()
	{
		$storedConfig = unserialize(base64_decode(Configuration::get(self::DEFAULT_CONFIG_NAME)));

		if (is_array($storedConfig))
			foreach ($storedConfig as $key => $value)
				$this->$key = $value;
	}

	/**
	 * init folders
	 */
	public function initFolders()
	{
		$result = array();

		/**
		 * tmp folder
		 */
		$this->createFolder($this->getPathByShopNumber($this->getShopNumber()));
		$result['export_folder_path'] = $this->getPathByShopNumber($this->getShopNumber());

		/**
		 * logs
		 */
		$this->createFolder($this->getPathByShopNumber($this->getShopNumber(), 'logs'));
		$result['log_folder_path'] = $this->getPathByShopNumber($this->getShopNumber(), 'logs');

		/**
		 * cache
		 */
		$this->createFolder($this->getPathByShopNumber($this->getShopNumber(), 'cache'));
		$result['cache_folder_path'] = $this->getPathByShopNumber($this->getShopNumber(), 'cache');

		return $result;
	}

	/**
	 * create folder by path
	 *
	 * @param	  $path
	 * @param int $mode
	 * @param bool $recursive
	 *
	 * @throws ShopgateLibraryException
	 */
	protected function createFolder($path, $mode = 0777, $recursive = true)
	{
		if (!is_dir($path))
			try {
				mkdir($path, $mode, $recursive);
			} catch (ShopgateLibraryException $e) {
				throw new ShopgateLibraryException(
					ShopgateLibraryException::CONFIG_READ_WRITE_ERROR,
					sprintf('The folder "%s" could not be created.', $path)
				);
			}
	}

	/**
	 * create empty config
	 */
	public function cleanupConfig()
	{
		$this->initDefaults();
		$this->store();
	}

	/**
	 * returns the path by shop number and type
	 *
	 * @param int $shopNumber
	 * @param mixed $type
	 *
	 * @return string
	 */
	public function getPathByShopNumber($shopNumber, $type = false)
	{
		$tempFolder = sprintf(
			SHOPGATE_BASE_DIR.DS.'%s'.DS.$shopNumber,
			'temp'
		);

		switch ($type)
		{
			case 'logs' :
				$tempFolder = sprintf(
					'%s/%s',
					$tempFolder,
					'logs'
				);
				break;
			case 'cache' :
				$tempFolder = sprintf(
					'%s/%s',
					$tempFolder,
					'cache'
				);
				break;
		}

		return $tempFolder;
	}
}
