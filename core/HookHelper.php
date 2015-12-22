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

class HookHelper
{

    /**
     * @param array   $hookArgs
     * @param Context $context
     *
     * @return array
     */
    public function hook($hookArgs, Context $context = null)
    {
        $paymentMethods = array();
        if (version_compare(_PS_VERSION_, '1.5.0.0', '<')) {
            $paymentMethods = $this->hookExecDisplayPayment14xx($context);
        } else {
            if (version_compare(_PS_VERSION_, '1.6.0.0', '<')) {
                $unformattedPaymentMethods = $this->hookExec15xx('displayPayment', $hookArgs, null, true);
            } else {
                $unformattedPaymentMethods = $this->hookExec16xx('displayPayment', $hookArgs);
            }
            foreach ($unformattedPaymentMethods as $moduleName => $output) {
                if (!empty($output)) {
                    $paymentMethods[] = $moduleName;
                }
            }
        }

        return $paymentMethods;
    }

    /**
     * This method is copied and adapted from Prestashop version: 1.4.2.5
     * 
     * @param Context $context
     *
     * @return array
     */
    public function hookExecDisplayPayment14xx(Context $context)
    {
        $cart   = $context->cart;
        $cookie = $context->cookie;

        $hookArgs       = array('cookie' => $cookie, 'cart' => $cart);
        $id_customer    = (int)($cookie->id_customer);
        $billing        = new Address((int)($cart->id_address_invoice));
        $paymentMethods = array();

        if (method_exists(Module, 'getPaymentModules')) {
            $result = Module::getPaymentModules();
        } else {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS(
                '
                SELECT DISTINCT h.`id_hook`, m.`name`, hm.`position`
                FROM `' . _DB_PREFIX_ . 'module_country` mc
                LEFT JOIN `' . _DB_PREFIX_ . 'module` m ON m.`id_module` = mc.`id_module`
                INNER JOIN `' . _DB_PREFIX_ . 'module_group` mg ON (m.`id_module` = mg.`id_module`)
                INNER JOIN `' . _DB_PREFIX_
                . 'customer_group` cg ON (cg.`id_group` = mg.`id_group` AND cg.`id_customer` = ' . (int)($id_customer) . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_module` = m.`id_module`
                LEFT JOIN `' . _DB_PREFIX_ . 'hook` h ON hm.`id_hook` = h.`id_hook`
                WHERE h.`name` = \'payment\'
                AND mc.id_country = ' . (int)($billing->id_country) . '
                AND m.`active` = 1
                ORDER BY hm.`position`, m.`name` DESC'
            );
        }

        if ($result) {
            foreach ($result as $k => $module) {
                if (($moduleInstance = Module::getInstanceByName($module['name'])) and is_callable(
                    array($moduleInstance, 'hookpayment')
                )) {
                    if (!$moduleInstance->currencies or ($moduleInstance->currencies and sizeof(
                        Currency::checkPaymentCurrencies($moduleInstance->id)
                    ))) {
                        $output = call_user_func(array($moduleInstance, 'hookpayment'), $hookArgs);
                        if (!empty($output)) {
                            $paymentMethods[] = $moduleInstance->name;
                        }
                    }
                }
            }
        }

        return $paymentMethods;
    }

    /**
     * This method is copied and adapted from Prestashop version: 1.5.6.2
     *
     * @param string     $hook_name
     * @param array      $hook_args
     * @param null       $id_module
     * @param bool|false $array_return
     * @param bool|true  $check_exceptions
     *
     * @return array|string
     * @throws PrestaShopException
     */
    public function hookExec15xx(
        $hook_name,
        $hook_args = array(),
        $id_module = null,
        $array_return = false,
        $check_exceptions = true
    ) {
        static $disable_non_native_modules = null;
        if ($disable_non_native_modules === null) {
            $disable_non_native_modules = (bool)Configuration::get('PS_DISABLE_NON_NATIVE_MODULE');
        }

        // Check arguments validity
        if (($id_module && !is_numeric($id_module)) || !Validate::isHookName($hook_name)) {
            throw new PrestaShopException('Invalid id_module or hook_name');
        }

        // If no modules associated to hook_name or recompatible hook name, we stop the function

        if (!$module_list = Hook::getHookModuleExecList($hook_name)) {
            return '';
        }

        // Check if hook exists
        if (!$id_hook = Hook::getIdByName($hook_name)) {
            return false;
        }

        // Store list of executed hooks on this page
        Hook::$executed_hooks[$id_hook] = $hook_name;

        $live_edit = false;
        $context   = Context::getContext();
        if (!isset($hook_args['cookie']) || !$hook_args['cookie']) {
            $hook_args['cookie'] = $context->cookie;
        }
        if (!isset($hook_args['cart']) || !$hook_args['cart']) {
            $hook_args['cart'] = $context->cart;
        }

        $retro_hook_name = Hook::getRetroHookName($hook_name);

        // Look on modules list
        $altern = 0;
        $output = '';

        if ($disable_non_native_modules && !isset(Hook::$native_module)) {
            Hook::$native_module = Module::getNativeModuleList();
        }

        foreach ($module_list as $array) {
            // Check errors
            if ($id_module && $id_module != $array['id_module']) {
                continue;
            }

            if ((bool)$disable_non_native_modules && Hook::$native_module && count(Hook::$native_module)
                && !in_array(
                    $array['module'],
                    self::$native_module
                )
            ) {
                continue;
            }

            if (!($moduleInstance = Module::getInstanceByName($array['module']))) {
                continue;
            }

            // Check permissions
            if ($check_exceptions) {
                $exceptions = $moduleInstance->getExceptions($array['id_hook']);
                $controller = Dispatcher::getInstance()->getController();

                if (in_array($controller, $exceptions)) {
                    continue;
                }

                //retro compat of controller names
                $matching_name = array(
                    'authentication' => 'auth',
                    'compare'        => 'products-comparison',
                );
                if (isset($matching_name[$controller]) && in_array($matching_name[$controller], $exceptions)) {
                    continue;
                }
                if (Validate::isLoadedObject($context->employee)
                    && !$moduleInstance->getPermission(
                        'view',
                        $context->employee
                    )
                ) {
                    continue;
                }
            }

            // Check which / if method is callable
            $hook_callable       = is_callable(array($moduleInstance, 'hook' . $hook_name));
            $hook_retro_callable = is_callable(array($moduleInstance, 'hook' . $retro_hook_name));
            if (($hook_callable || $hook_retro_callable) && Module::preCall($moduleInstance->name)) {
                $hook_args['altern'] = ++$altern;

                // Call hook method
                if ($hook_callable) {
                    $display = $moduleInstance->{'hook' . $hook_name}($hook_args);
                } else {
                    if ($hook_retro_callable) {
                        $display = $moduleInstance->{'hook' . $retro_hook_name}($hook_args);
                    }
                }
                // Live edit
                if (!$array_return && $array['live_edit'] && Tools::isSubmit('live_edit') && Tools::getValue('ad')
                    && Tools::getValue('liveToken') == Tools::getAdminToken(
                        'AdminModulesPositions' . (int)Tab::getIdFromClassName('AdminModulesPositions')
                        . (int)Tools::getValue('id_employee')
                    )
                ) {
                    $live_edit = true;
                    $output .= self::wrapLiveEdit($display, $moduleInstance, $array['id_hook']);
                } else {
                    if ($array_return) {
                        $output[$moduleInstance->name] = $display;
                    } else {
                        $output .= $display;
                    }
                }
            }
        }
        if ($array_return) {
            return $output;
        } else {
            return ($live_edit ? '<script type="text/javascript">hooks_list.push(\'' . $hook_name . '\');</script>
                <div id="' . $hook_name . '" class="dndHook" style="min-height:50px">' : '') . $output . ($live_edit
                ? '</div>' : '');
        }// Return html string
    }

    /**
     * This method is copied and adapted from Prestashop version: 1.6.0.1
     *
     * @param string $hookName
     * @param array  $hookArgs
     *
     * @return array
     */
    public function hookExec16xx($hookName, $hookArgs)
    {
        return Hook::exec($hookName, $hookArgs, null, true);
    }
}
