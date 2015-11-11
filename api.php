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

/**
 * default relative path to config
 */
define('DEFAULT_RELATIVE_CONFIG_PATH', '/../../config/config.inc.php');

require_once(dirname($_SERVER['SCRIPT_FILENAME']).'/classes/Helper.php');
require_once(ShopgateHelper::normalizePath(array(dirname($_SERVER['SCRIPT_FILENAME']), DEFAULT_RELATIVE_CONFIG_PATH)));

require_once(dirname($_SERVER['SCRIPT_FILENAME']).'/shopgate.php');

$controller = new FrontController();
$controller->init();

$plugin = new ShopgatePluginPrestashop();
$response = $plugin->handleRequest($_POST);
