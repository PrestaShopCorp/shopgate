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

class ShopgatePluginPrestashop extends ShopgatePlugin
{

    /**
     * @var ShopgateConfigPrestashop;
     */
    protected $config;

    /**
     *
     */
    const PREFIX = 'BD';

    /**
     * Callback function for initialization by plugin implementations.
     *
     * This method gets called on instantiation of a ShopgatePlugin child class and serves as __construct() replacement.
     *
     * Important: Initialize $this->config here if you have your own config class.
     *
     * @see http://wiki.shopgate.com/Shopgate_Library#startup.28.29
     */
    public function startup()
    {
        include_once dirname(__FILE__).'/../backward_compatibility/backward.php';
        $this->config = new ShopgateConfigPrestashop();
    }
    
    public function cron($jobname, $params, &$message, &$errorcount)
    {
        switch ($jobname)
        {
            case 'cancel_orders':
                $this->log("cron executed job '".$jobname."'", ShopgateLogger::LOGTYPE_DEBUG);
                $cancellationStatus = ConfigurationCore::get('SG_CANCELLATION_STATUS');
                
                $select = sprintf(
                    'SELECT '
                    .(version_compare(_PS_VERSION_, '1.5.0', '>=')
                        ? ' o.current_state,  '
                        : ' o.id_order, ').
                    ' so.id_shopgate_order from %sshopgate_order as so
                        JOIN %sorders as o on so.id_order=o.id_order 
                        WHERE so.is_cancellation_sent_to_shopgate = 0',
                    _DB_PREFIX_,
                    _DB_PREFIX_
                );
                $result = Db::getInstance()->ExecuteS($select);
                
                if (empty($result)) {
                    $this->log('no orders to cancel found for shop:'.$this->config->getShopNumber(), ShopgateLogger::LOGTYPE_DEBUG);
                    return;
                }
                
                foreach ($result as $order) {
                    $sgOrder = new ShopgateOrderPrestashop($order['id_shopgate_order']);
                    
                    if (is_string($sgOrder->order_number)) {
                        $sgOrder->order_number = (int)$sgOrder->order_number;
                    }
                    
                    if (version_compare(_PS_VERSION_, '1.5.0', '>=')) {
                        $state = $order['current_state'];
                    } else {
                        $stateObject = OrderHistory::getLastOrderState($order['id_order']);
                        $state = $stateObject->id;
                    }
                    
                    if ($state == $cancellationStatus) {
                        $sgOrder->cancelOrder($message);
                    } else {
                        $sgOrder->cancelOrder($message, true);
                    }
                }
                
                break;
            
            default:
                $this->log("job '".$jobname."' not found", ShopgateLogger::LOGTYPE_ERROR);
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::PLUGIN_CRON_UNSUPPORTED_JOB,
                    'Job name: "'.
                    $jobname.'"',
                    true
                );
                break;
        }
    }

    /**
     * This performs the necessary queries to build a ShopgateCustomer object for the given log in credentials.
     * The method should not abort on soft errors like when the street or phone number of a customer can't be found.
     *
     * @see http://developer.shopgate.com/plugin_api/customers/get_customer
     *
     * @param string $user The user name the customer entered at Shopgate Connect.
     * @param string $pass The password the customer entered at Shopgate Connect.
     * @return ShopgateCustomer A ShopgateCustomer object.
     * @throws ShopgateLibraryException on invalid log in data or hard errors like database failure.
     */
    public function getCustomer($user, $pass)
    {
        $customerModel = new ShopgateItemsCustomerExportJson($this);
        return $customerModel->getCustomer($user, $pass);
    }

    /**
     * This method creates a new user account / user addresses for a customer in the shop system's database
     * The method should not abort on soft errors like when the street or phone number of a customer is not set.
     *
     * @see http://developer.shopgate.com/plugin_api/customers/register_customer
     *
     * @param string $user The user name the customer entered at Shopgate.
     * @param string $pass The password the customer entered at Shopgate.
     * @param ShopgateCustomer A ShopgateCustomer object to be added to the shop system's database.
     * @throws ShopgateLibraryException if an error occures
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        $customerModel = new ShopgateItemsCustomerImportJson($this);
        $customerModel->registerCustomer($user, $pass, $customer);
    }

    /**
     * Performs the necessary queries to add an order to the shop system's database.
     *
     * @see http://developer.shopgate.com/merchant_api/orders/get_orders
     * @see http://developer.shopgate.com/plugin_api/orders/add_order
     *
     * @param ShopgateOrder $order The ShopgateOrder object to be added to the shop system's database.
     * @return array(
     *          <ul>
     *            <li>'external_order_id' => <i>string</i>, # the ID of the order in your shop system's database</li>
     *              <li>'external_order_number' => <i>string</i> # the number of the order in your shop system</li>
     *          </ul>)
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function addOrder(ShopgateOrder $order)
    {
        $orderModel = new ShopgateItemsInputOrderJson($this);
        return $orderModel->addOrder($order);
    }

    /**
     * Performs the necessary queries to update an order in the shop system's database.
     *
     * @see http://developer.shopgate.com/merchant_api/orders/get_orders
     * @see http://developer.shopgate.com/plugin_api/orders/update_order
     *
     * @param ShopgateOrder $order The ShopgateOrder object to be updated in the shop system's database.
     * @return array(
     *          <ul>
     *            <li>'external_order_id' => <i>string</i>, # the ID of the order in your shop system's database</li>
     *              <li>'external_order_number' => <i>string</i> # the number of the order in your shop system</li>
     *          </ul>)
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function updateOrder(ShopgateOrder $order)
    {
        $orderModel = new ShopgateItemsInputOrderJson($this);
        $orderModel->updateOrder($order);
    }

    /**
     * Redeems coupons that are passed along with a ShopgateCart object.
     *
     * @see http://developer.shopgate.com/plugin_api/coupons
     *
     * @param ShopgateCart $cart The ShopgateCart object containing the coupons that should be redeemed.
     * @return array('external_coupons' => ShopgateExternalCoupon[])
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function redeemCoupons(ShopgateCart $cart)
    {
        return array();
    }

    /**
     * Checks the content of a cart to be valid and returns necessary changes if applicable.
     *
     *
     * @see http://developer.shopgate.com/plugin_api/cart
     *
     * @param ShopgateCart $cart The ShopgateCart object to be checked and validated.
     * @return array(
     *          <ul>
     *            <li>'external_coupons' => ShopgateExternalCoupon[], # list of all coupons</li>
     *            <li>'items' => array(...), # list of item changes</li>
     *            <li>'shippings' => array(...), # list of available shipping services for this cart</li>
     *          </ul>)
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function checkCart(ShopgateCart $cart)
    {
        $cartModel = new ShopgateItemsCartExportJson($this);
        return $cartModel->checkCart($cart);
    }

    /**
     * Checks the items array and returns stock quantity for each item.
     *
     *
     * @see http://wiki.shopgate.com/Shopgate_Plugin_API_check_cart#API_Response
     *
     * @param ShopgateCart $cart The ShopgateCart object to be checked and validated.
     *
     * @return array(
     *          'items' => array(...), # list of item changes
     * )
     * @throws ShopgateLibraryException if an error occurs.
     */
    public function checkStock(ShopgateCart $cart)
    {
        $cartModel = new ShopgateItemsCartExportJson($this);
        return $cartModel->checkStock($cart);
    }

    /**
     * Returns an array of certain settings of the shop. (Currently mainly tax settings.)     *
     * @see http://developer.shopgate.com/plugin_api/system_information/get_settings
     *
     * @return array(
     *                    <ul>
     *                        <li>'tax' => Contains the tax settings as follows:
     *                            <ul>
     *                                <li>'tax_classes_products' => A list of product tax class identifiers.</li>
     *                                <li>'tax_classes_customers' => A list of customer tax classes.</li>
     *                                <li>'tax_rates' => A list of tax rates.</li>
     *                                <li>'tax_rules' => A list of tax rule containers.</li>
     *                            </ul>
     *                        </li>
     *                    </ul>)
     * @throws ShopgateLibraryException on invalid log in data or hard errors like database failure.
     */
    public function getSettings()
    {
        return ShopgateSettings::getShopgateSettings($this);
    }

    /**
     * Loads the products of the shop system's database and passes them to the buffer.
     *
     * If $this->splittedExport is set to "true", you MUST regard $this->offset and $this->limit when fetching items from the database.
     *
     * Use ShopgatePlugin::buildDefaultItemRow() to get the correct indices for the field names in a Shopgate items csv and
     * use ShopgatePlugin::addItemRow() to add it to the output buffer.
     *
     * @see http://developer.shopgate.com/file_formats/csv/products
     * @see http://developer.shopgate.com/plugin_api/export/get_items_csv
     *
     * @throws ShopgateLibraryException
     */
    protected function createItemsCsv()
    {
        // TODO: Implement createItemsCsv() method.
    }

    /**
     * Loads the Media file information to the products of the shop system's database and passes them to the buffer.
     *
     * Use ShopgatePlugin::buildDefaultMediaRow() to get the correct indices for the field names in a Shopgate media csv and
     * use ShopgatePlugin::addMediaRow() to add it to the output buffer.
     *
     * @see http://wiki.shopgate.com/CSV_File_Media#Sample_Media_CSV_file
     * @see http://developer.shopgate.com/plugin_api/export/get_media_csv
     *
     * @throws ShopgateLibraryException
     */
    protected function createMediaCsv()
    {
        // TODO: Implement createMediaCsv() method.
    }

    /**
     * Loads the product categories of the shop system's database and passes them to the buffer.
     *
     * Use ShopgatePlugin::buildDefaultCategoryRow() to get the correct indices for the field names in a Shopgate categories csv and
     * use ShopgatePlugin::addCategoryRow() to add it to the output buffer.
     *
     * @see http://developer.shopgate.com/file_formats/csv/categories
     * @see http://developer.shopgate.com/plugin_api/export/get_categories_csv
     *
     * @throws ShopgateLibraryException
     */
    protected function createCategoriesCsv()
    {
        // TODO: Implement createCategoriesCsv() method.
    }

    /**
     * Loads the product reviews of the shop system's database and passes them to the buffer.
     *
     * Use ShopgatePlugin::buildDefaultReviewRow() to get the correct indices for the field names in a Shopgate reviews csv and
     * use ShopgatePlugin::addReviewRow() to add it to the output buffer.
     *
     * @see http://developer.shopgate.com/file_formats/csv/reviews
     * @see http://developer.shopgate.com/plugin_api/export/get_reviews_csv
     *
     * @throws ShopgateLibraryException
     */
    protected function createReviewsCsv()
    {
        // TODO: Implement createReviewsCsv() method.
    }

    /**
     * Exports orders from the shop system's database to Shopgate.
     *
     * @see http://developer.shopgate.com/plugin_api/orders/get_orders
     *
     * @param string $customerToken
     * @param string $customerLanguage
     * @param int $limit
     * @param int $offset
     * @param string $orderDateFrom
     * @param string $sortOrder
     *
     * @return ShopgateExternalOrder[] A list of ShopgateExternalOrder objects
     *
     * @throws ShopgateLibraryException
     */
    public function getOrders($customerToken, $customerLanguage, $limit = 10, $offset = 0, $orderDateFrom = '', $sortOrder = 'created_desc')
    {
        $orderModel = new ShopgateItemsOrderExportJson($this);
        return $orderModel->getOrders($customerToken, $customerLanguage, $limit, $offset, $orderDateFrom, $sortOrder);
    }

    /**
     * Updates and returns synchronization information for the favourite list of a customer.
     *
     * @see http://developer.shopgate.com/plugin_api/customers/sync_favourite_list
     *
     * @param string $customerToken
     * @param ShopgateSyncItem[] $items A list of ShopgateSyncItem objects that need to be synchronized
     *
     * @return ShopgateSyncItem[] The updated list of ShopgateSyncItem objects
     */
    public function syncFavouriteList($customerToken, $items)
    {
        // TODO: Implement syncFavouriteList() method.
    }

    /**
     * Loads the products of the shop system's database and passes them to the buffer.
     *
     * @param int $limit pagination limit; if not null, the number of exported items must be <= $limit
     * @param int $offset pagination; if not null, start the export with the item at position $offset
     * @param string[] $uids a list of item UIDs that should be exported
     *
     * @see http://developer.shopgate.com/plugin_api/export/get_items
     *
     * @throws ShopgateLibraryException
     */
    protected function createItems($limit = null, $offset = null, array $uids = array())
    {
        $itemsModel = new ShopgateItemsItem($this);
        foreach ($itemsModel->getItems($limit, $offset, $uids) as $product) {
            $row = new ShopgateItemsItemExportXml();
            $this->addItemModel($row->setItem($product)->generateData());
        }
    }

    /**
     * Loads the product categories of the shop system's database and passes them to the buffer.
     *
     * @param int $limit pagination limit; if not null, the number of exported categories must be <= $limit
     * @param int $offset pagination; if not null, start the export with the categories at position $offset
     * @param string[] $uids a list of categories UIDs that should be exported
     *
     * @see http://developer.shopgate.com/plugin_api/export/get_categories
     *
     * @throws ShopgateLibraryException
     */
    protected function createCategories($limit = null, $offset = null, array $uids = array())
    {
        $categoryModel = new ShopgateItemsCategory($this);

        foreach ($categoryModel->getItems($limit, $offset) as $category) {
            if (count($uids) > 0 && !in_array($category['id_category'], $uids)) {
                continue;
            }

            $row = new ShopgateItemsCategoryExportXml();
            $this->addCategoryModel($row->setItem($category)->generateData());
        }
    }

    /**
     * Loads the product reviews of the shop system's database and passes them to the buffer.
     *
     * @param int $limit pagination limit; if not null, the number of exported reviews must be <= $limit
     * @param int $offset pagination; if not null, start the export with the reviews at position $offset
     * @param string[] $uids A list of products that should be fetched for the reviews.
     *
     * @see http://developer.shopgate.com/plugin_api/export/get_reviews
     *
     * @throws ShopgateLibraryException
     */
    protected function createReviews($limit = null, $offset = null, array $uids = array())
    {
        /** @var ShopgateItemsReview $reviewModel */
        $reviewModel = new ShopgateItemsReview($this);

        foreach ($reviewModel->getItems($limit, $offset, $uids) as $review) {
            if (count($uids) > 0 && !in_array($review['id_review'], $uids)) {
                continue;
            }

            $row = new ShopgateItemsReviewExportXml();
            $this->addReviewModel($row->setItem($review)->generateData());
        }
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return ShopGate_Config
     */
    public function getShopgateConfig()
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->getContext()->language->id;
    }

    public function createShopInfo()
    {
        $shopInfo = array(
            'category_count' => count(Category::getSimpleCategories($this->getLanguageId())),
            'item_count'     => count(Product::getSimpleProducts($this->getLanguageId())),
        );

        if ($this->config->getEnableGetReviewsCsv()) {
            /**
             * set review_count
             */
            $shopInfo['review_count'] = 0;
        }

        if ($this->config->getEnableGetMediaCsv()) {
            /**
             * media_count
             */
            $shopInfo['media_count'] = array();
        }

        $shopInfo['plugins_installed'] = array();

        foreach (Module::getModulesInstalled() as $module) {
            array_push($shopInfo['plugins_installed'], array(
                'id'      => $module['id_module'],
                'name'    => $module['name'],
                'version' => $module['version'],
                'active'  => $module['active'] ? 1 : 0
            ));
        }

        return $shopInfo;
    }

    /**
     * @return array|mixed[]
     */
    public function createPluginInfo()
    {
        return array(
            'PS Version' => _PS_VERSION_,
            'Plugin'     => 'standard'
        );
    }
}
