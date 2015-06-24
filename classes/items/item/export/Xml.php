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

class ShopgateItemsItemExportXml extends Shopgate_Model_Catalog_Product
{
	public function setUid()
	{
		parent::setUid($this->item['id_product']);
	}
	
	/**
	 * set last update
	 */
	public function setLastUpdate()
	{
		parent::setLastUpdate($this->item['date_upd'].' '.date('T'));
	}
	
	/**
	 * set name
	 */
	public function setName()
	{
		parent::setName($this->item['name']);
	}
	
	/**
	 * set tax percent
	 */
	public function setTaxPercent()
	{
		parent::setTaxPercent($this->getAdditionalInfo('tax_percent'));
	}
	
	/**
	 * set tax class
	 */
	public function setTaxClass()
	{
		parent::setTaxClass($this->getAdditionalInfo('tax_class'));
	}
	
	/**
	 * set currency
	 */
	public function setCurrency()
	{
		parent::setCurrency($this->getAdditionalInfo('currency'));
	}
	
	/**
	 * set description
	 */
	public function setDescription()
	{
		parent::setDescription($this->getAdditionalInfo('description'));
	}
	
	public function setPrice()
	{
		parent::setPrice($this->getAdditionalInfo('price'));
	}
	
	/**
	 * set weight unit
	 */
	public function setWeightUnit()
	{
		parent::setWeightUnit(Tools::strtolower(Configuration::get('PS_WEIGHT_UNIT')));
	}
	
	/**
	 * set weight
	 */
	public function setWeight()
	{
		parent::setWeight($this->item['weight']);
	}
	
	/**
	 * set images
	 */
	public function setImages()
	{
		parent::setImages($this->getAdditionalInfo('images'));
	}
	
	/**
	 * set categories
	 *
	 */
	public function setCategoryPaths()
	{
		parent::setCategoryPaths($this->getAdditionalInfo('categories'));
	}
	
	/**
	 * set the product deep link
	 */
	public function setDeepLink()
	{
		parent::setDeeplink($this->getAdditionalInfo('deeplink'));
	}
	
	/**
	 * set shipping
	 */
	public function setShipping()
	{
		parent::setShipping($this->getAdditionalInfo('shipping'));
	}
	
	/**
	 * add manufacturer
	 */
	public function setManufacturer()
	{
		parent::setManufacturer($this->getAdditionalInfo('manufacturer'));
	}
	
	/**
	 * add properties
	 */
	public function setProperties()
	{
		parent::setProperties($this->getAdditionalInfo('properties'));
	}
	
	/**
	 * add visibility
	 */
	public function setVisibility()
	{
		parent::setVisibility($this->getAdditionalInfo('visibility'));
	}
	
	/**
	 * stock
	 */
	public function setStock()
	{
		parent::setStock($this->getAdditionalInfo('stock'));
	}
	
	/**
	 * add identifiers
	 */
	public function setIdentifiers()
	{
		parent::setIdentifiers($this->getAdditionalInfo('identifiers'));
	}
	
	/**
	 * add tags
	 */
	public function setTags()
	{
		parent::setTags($this->getAdditionalInfo('tags'));
	}
	
	/**
	 * add promotion sort order
	 */
	public function setPromotionSortOrder()
	{
		$this->getAdditionalInfo('promotion') ? parent::setPromotionSortOrder(1) : false;
	}
	
	/**
	 * add internal order info
	 */
	public function setInternalOrderInfo()
	{
	}
	
	/**
	 * add relations
	 */
	public function setRelations()
	{
	}
	
	/**
	 * add age rating
	 */
	public function setAgeRating()
	{
	}
	
	/**
	 * add attributes
	 */
	public function setAttributeGroups()
	{
		parent::setAttributeGroups($this->getAdditionalInfo('attribute_groups'));
	}
	
	/**
	 * add inputs
	 */
	public function setInputs()
	{
		parent::setInputs($this->getAdditionalInfo('inputs'));
	}
	
	/**
	 * set children
	 */
	public function setChildren()
	{
		parent::setChildren($this->getAdditionalInfo('children'));
	}
	
	/**
	 * @param $key
	 * @return mixed
	 */
	protected function getAdditionalInfo($key)
	{
		return array_key_exists(
			$key, $this->item['_additional_info'])
			? $this->item['_additional_info'][$key]
			: false;
	}
}
