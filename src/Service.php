<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns;

use craft\base\Component;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use yii\db\Expression;


/**
 * Class Service
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns
 * @since   1.0.0
 */
class Service extends Component
{

	/**
	 * Tallies the products on the given order
	 *
	 * @param Order $order
	 */
	public function tallyProducts (Order $order)
	{
		$productIds = [];
		$variantIds = [];

		foreach ($order->lineItems as $item)
		{
			/** @var Variant $variant */
			$variant = $item->purchasable;

			if (!$variant)
				continue;

			$productId = $variant->productId;
			if (!in_array($productId, $productIds))
				$productIds[] = $productId;

			$variantId = $variant->id;
			if (!in_array($variantId, $variantIds))
				$variantIds[] = $variantId;
		}

		try {
			foreach ($productIds as $idA) {
				foreach ($productIds as $idB) {
					if ($idA === $idB)
						continue;

					\Craft::$app->db->createCommand()->upsert(
						'{{%purchase_patterns}}', [
							'product_a' => $idA,
							'product_b' => $idB,
							'purchase_count' => 0,
						], [
							// FIXME:                       column `purchase_count` is ambiguous
							'purchase_count' => new Expression('purchase_count + 1')
						],
						[], false
					)->execute();
				}
			}
		} catch (\Exception $e) {
			\Craft::error(
				$e->getMessage(),
				'PurchasePatterns'
			);
		}
	}

	/**
	 * Finds related products for the given product
	 *
	 * @param Product $product
	 * @param int     $limit
	 *
	 * @return ProductQuery
	 * @throws \yii\db\Exception
	 */
	public function getRelatedProductsCriteria (Product $product, $limit = 8)
	{
		$id = $product->id;
		$craft = \Craft::$app;

		$query = <<<SQL
SELECT product_a, product_b
FROM {{%purchase_patterns}}
WHERE (product_a = $id OR product_b = $id)
ORDER BY purchase_count
LIMIT $limit
SQL;

		$results = $craft->db->createCommand($query)->queryAll();
		$productIds = [];

		foreach ($results as $result)
			$productIds[] =
				$result['product_a'] === $id
					? $result['product_a']
					: $result['product_b'];

		return Product::find()->id($productIds)->limit($limit);
	}

}