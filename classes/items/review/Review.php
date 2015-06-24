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

class ShopgateItemsReview extends ShopgateItemsAbstract
{
	/**
	 * @param null $limit
	 * @param null $offset
	 * @return array
	 */
	public function getItems($limit = null, $offset = null)
	{
		$reviews = array();

		if (ShopgateHelper::checkTable(sprintf('%sproduct_comment', _DB_PREFIX_)))
			$reviews = Db::getInstance()->ExecuteS(
				sprintf(
					'SELECT * FROM %sproduct_comment WHERE validate = 1%s%s',
					_DB_PREFIX_,
					is_int($limit) ? ' LIMIT '.$limit : '',
					is_int($offset) ? ' OFFSET '.$offset : ''
				)
			);

		return $reviews;
	}
}