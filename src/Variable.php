<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns;

use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use yii\base\InvalidConfigException;
use yii\db\Exception;


/**
 * Class Variable
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns
 * @since   1.0.0
 */
class Variable
{

	/**
	 * Finds any relayed products for the given product
	 *
	 * @param Product|Order     $target
	 * @param int               $limit
	 * @param ProductQuery|null $paddingQuery
	 *
	 * @return ProductQuery
	 * @throws InvalidConfigException
	 * @throws Exception
	 */
	public function related ($target, $limit = 8, ProductQuery $paddingQuery = null)
	{
		$service = PurchasePatterns::getInstance()->getService();

		if ($target instanceof Product)
		{
			return $service->getRelatedToProductCriteria(
				$target,
				$limit,
				$paddingQuery
			);
		}

		if ($target instanceof Order)
		{
			return $service->getRelatedToOrderCriteria(
				$target,
				$limit,
				$paddingQuery
			);
		}

		throw new InvalidConfigException(
			"The target passed to craft.purchasePatterns.related is not a valid order or product"
		);
	}

}