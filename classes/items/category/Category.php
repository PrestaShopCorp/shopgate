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

class ShopgateItemsCategory extends ShopgateItemsAbstract
{
	/**
	 * default pattern category image
	 */
	const PS_CONST_IMAGE_TYPE_CATEGORY_DEFAULT = 'category%sdefault';

	/**
	 * @return array
	 */
	public function getItems()
	{
		$categoryItems = array();
		$result = array();

		$exportRootCategories = Configuration::get('SG_EXPORT_ROOT_CATEGORIES') == 1 ? true : false;

		$rootCategories = array();

		foreach (Category::getSimpleCategories($this->getPlugin()->getLanguageId()) as $category)
		{
			/** @var CategoryCore $categoryInfo */
			$categoryInfo = new Category($category['id_category']);
			$categoryLinkRewrite = $categoryInfo->getLinkRewrite($categoryInfo->id_category, $this->getPlugin()->getLanguageId());
			$isRootCategory = $categoryInfo->is_root_category;

			/**
			 * collect root categories
			 */
			if ($isRootCategory)
				array_push($rootCategories, $categoryInfo->id_category);

			$categoryItem = array();
			$categoryItem['category_number'] = $categoryInfo->id_category;
			$categoryItem['category_name'] = $categoryInfo->getName($this->getPlugin()->getLanguageId());
			$categoryItem['parent_id'] = $isRootCategory && $exportRootCategories ? '' : $categoryInfo->id_parent;
			$categoryItem['is_active'] = $categoryInfo->active;

			$categoryItem['url_deeplink'] =
				$this->getPlugin()->getContext()->link->getCategoryLink(
					$categoryInfo->id_category,
					$categoryLinkRewrite,
					$this->getPlugin()->getLanguageId()
				);

			$categoryImageUrl = $this->getPlugin()->getContext()->link->getCatImageLink(
				$categoryLinkRewrite,
				$categoryInfo->id_category,
				sprintf(self::PS_CONST_IMAGE_TYPE_CATEGORY_DEFAULT, '_')
			);

			$categoryItem['url_image'] = $categoryImageUrl;
			$categoryItem['order_index'] = $categoryInfo->position;

			array_push($categoryItems, $categoryItem);
		}

		$categoryPositionData = array();
		foreach ($categoryItems as $categoryItem)
		{
			$key = $categoryItem['parent_id'] == '' ? 'root' : $categoryItem['parent_id'];
			if (!array_key_exists($key, $categoryPositionData))
				$categoryPositionData[$key] = 0;
			else
				$categoryPositionData[$key]++;
		}

		$categoryNewPositionData = array();
		foreach ($categoryItems as $categoryItem)
		{
			$key = $categoryItem['parent_id'] == '' ? 'root' : $categoryItem['parent_id'];
			if (!array_key_exists($key, $categoryNewPositionData))
				$categoryNewPositionData[$key] = 0;
			else
				$categoryNewPositionData[$key]++;

			$categoryItem['order_index'] = $categoryPositionData[$key] - $categoryNewPositionData[$key];
			array_push($result, $categoryItem);
		}

		return $result;
	}
}