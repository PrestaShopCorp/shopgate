<?php
/**
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2013 PrestaShop SA
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class BWProduct extends Product
{

    protected $id_lang;

    public function __construct($id_product = null, $full = false, $id_lang = null)
    {
        $this->id_lang = $id_lang;
        parent::__construct($id_product, $full, $id_lang);
    }

    /**
     * This function is available from version 1.5.0.1
     * @param $id_product
     *
     * @return array
     */
    public static function getAttributesInformationsByProduct($id_product, $lang_id)
    {
        $result = array();
        if (Module::isInstalled('blocklayered')) {
            $nb_custom_values = Db::getInstance()->executeS(
                'SELECT DISTINCT la.`id_attribute`, la.`url_name` as `attribute`
                FROM `'._DB_PREFIX_.'attribute` a
                LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                    ON (a.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                    ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                LEFT JOIN `'._DB_PREFIX_.'layered_indexable_attribute_lang_value` la
                    ON (la.`id_attribute` = a.`id_attribute` AND la.`id_lang` = '.(int)$lang_id.')
                WHERE la.`url_name` IS NOT NULL
                AND pa.`id_product` = '.(int)$id_product
            );

            if (!empty($nb_custom_values)) {
                $tab_id_attribute = array();
                foreach ($nb_custom_values as $attribute) {
                    $tab_id_attribute[] = $attribute['id_attribute'];

                    $group = Db::getInstance()->executeS(
                        'SELECT g.`id_attribute_group`, g.`url_name` as `group`
                        FROM `'._DB_PREFIX_.'layered_indexable_attribute_group_lang_value` g
                        LEFT JOIN `'._DB_PREFIX_.'attribute` a
                            ON (a.`id_attribute_group` = g.`id_attribute_group`)
                        WHERE a.`id_attribute` = '.(int)$attribute['id_attribute'].'
                        AND g.`id_lang` = '.(int)$lang_id.'
                        AND g.`url_name` IS NOT NULL'
                    );
                    if (empty($group)) {
                        $group = Db::getInstance()->executeS(
                            'SELECT g.`id_attribute_group`, g.`name` as `group`
                            FROM `'._DB_PREFIX_.'attribute_group_lang` g
                            LEFT JOIN `'._DB_PREFIX_.'attribute` a
                                ON (a.`id_attribute_group` = g.`id_attribute_group`)
                            WHERE a.`id_attribute` = '.(int)$attribute['id_attribute'].'
                            AND g.`id_lang` = '.(int)$lang_id.'
                            AND g.`name` IS NOT NULL'
                        );
                    }
                    $result[] = array_merge($attribute, $group[0]);
                }
                $values_not_custom = Db::getInstance()->executeS(
                    'SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`
                    FROM `'._DB_PREFIX_.'attribute` a
                    LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                        ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)$lang_id.')
                    LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                        ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)$lang_id.')
                    LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                        ON (a.`id_attribute` = pac.`id_attribute`)
                    LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                        ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                    WHERE pa.`id_product` = '.(int)$id_product.'
                    AND a.`id_attribute` NOT IN('.pSQL(implode(', ', $tab_id_attribute)).')'
                );
                $result = array_merge($values_not_custom, $result);
            } else {
                $result = Db::getInstance()->executeS(
                    'SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`
                    FROM `'._DB_PREFIX_.'attribute` a
                    LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                        ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)$lang_id.')
                    LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                        ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)$lang_id.')
                    LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                        ON (a.`id_attribute` = pac.`id_attribute`)
                    LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                        ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                    WHERE pa.`id_product` = '.(int)$id_product
                );
            }
        } else {
            $result = Db::getInstance()->executeS(
                'SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`
                FROM `'._DB_PREFIX_.'attribute` a
                LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
                    ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)$lang_id.')
                LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
                    ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)$lang_id.')
                LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
                    ON (a.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                    ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                WHERE pa.`id_product` = '.(int)$id_product
            );
        }
        return $result;
    }

    /**
     * Get all product attributes ids
     *
     * @since 1.5.0
     * @param int $id_product the id of the product
     * @return array product attribute id list
     */
    public static function getProductAttributesIds($id_product)
    {
        return Db::getInstance()->executeS(
            'SELECT pa.id_product_attribute
            FROM `'._DB_PREFIX_.'product_attribute` pa
            WHERE pa.`id_product` = '.(int)$id_product
        );
    }

    /**
     * Fill the variables used for stock management
     */
    public function loadStockData()
    {
        // By default, the product quantity correspond to the available quantity to sell in the current shop
        if (Validate::isLoadedObject($this)) {
            $this->quantity = $this->getStockAvailable();
        }
    }
}
