<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns;

use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Product;


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
	 * @param Product           $product
	 * @param int               $limit
	 * @param ProductQuery|null $paddingQuery
	 *
	 * @return \craft\commerce\elements\db\ProductQuery
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\db\Exception
	 */
	public function related (Product $product, $limit = 8, ProductQuery $paddingQuery = null)
	{
		return PurchasePatterns::getInstance()
		                       ->getService()
		                       ->getRelatedProductsCriteria(
		                       	    $product,
		                            $limit,
		                            $paddingQuery
		                       );
	}

}