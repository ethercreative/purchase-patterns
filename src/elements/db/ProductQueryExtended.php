<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\purchasePatterns\elements\db;

use craft\commerce\elements\db\ProductQuery;

/**
 * Class ProductQueryExtended
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns\elements\db
 */
class ProductQueryExtended extends ProductQuery
{

	protected function beforePrepare (): bool
	{
		$this->leftJoin(
			'{{%purchase_counts}} purchase_counts',
			'[[commerce_products.id]] = [[purchase_counts.product_id]]'
		);

		$select = [
			'[[purchase_counts.order_count]] AS orderCount',
			'[[purchase_counts.qty_count]] AS qtyCount',
		];

		$this->query->select($select);
		$this->subQuery->select($select);

		return parent::beforePrepare();
	}

}
