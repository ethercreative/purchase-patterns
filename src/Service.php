<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns;

use Craft;
use craft\base\Component;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use yii\db\Exception;
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

			if (!$variant || !($variant instanceof Variant))
				continue;

			$productId = $variant->productId;
			if (!in_array($productId, $productIds))
				$productIds[] = $productId;

			$variantId = $variant->id;
			if (!in_array($variantId, $variantIds))
				$variantIds[] = $variantId;
		}

		sort($productIds);

		try {
			foreach ($productIds as $idA) {
				foreach ($productIds as $idB) {
					if ($idA >= $idB)
						continue;

					Craft::$app->db->createCommand()->upsert(
						'{{%purchase_patterns}}', [
							'product_a' => $idA,
							'product_b' => $idB,
							'purchase_count' => 1,
						], [
							'purchase_count' => new Expression(
								'{{%purchase_patterns}}.purchase_count + 1'
							),
						],
						[], false
					)->execute();
				}
			}
		} catch (\Exception $e) {
			Craft::error(
				$e->getMessage(),
				'PurchasePatterns'
			);
		}
	}

	/**
	 * Finds related products for the given product
	 *
	 * @param Product           $product
	 * @param int               $limit
	 *
	 * @param ProductQuery|null $paddingQuery
	 *
	 * @return ProductQuery
	 * @throws Exception
	 */
	public function getRelatedToProductCriteria (Product $product, $limit = 8, ProductQuery $paddingQuery = null)
	{
		$id = $product->id;

		$query = <<<SQL
SELECT product_a, product_b
FROM {{%purchase_patterns}}
WHERE (product_a = $id OR product_b = $id)
ORDER BY purchase_count DESC
LIMIT $limit
SQL;

		$results = Craft::$app->db->createCommand($query)->queryAll();
		$productIds = [];

		foreach ($results as $result)
			$productIds[] =
				$result['product_a'] === $id
					? $result['product_b']
					: $result['product_a'];

		if (count($productIds) < $limit && $paddingQuery)
		{
			$paddingLimit = $limit - count($productIds);
			$paddingIds = $paddingQuery->limit($paddingLimit)->ids();
			$productIds = array_merge($productIds, $paddingIds);
		}

		return Product::find()->id($productIds);
	}

	/**
	 * Finds related products for the given order
	 *
	 * @param Order             $order
	 * @param int               $limit
	 * @param ProductQuery|null $paddingQuery
	 *
	 * @return ProductQuery
	 * @throws Exception
	 */
	public function getRelatedToOrderCriteria (Order $order, $limit = 8, ProductQuery $paddingQuery = null)
	{
		$orderProductIds = [];
		foreach ($order->lineItems as $item)
			/** @var $variant Variant */
			if (($variant = $item->purchasable) && $variant instanceof Variant)
				$orderProductIds[] = $variant->product->id;


		if (empty($orderProductIds))
			return Product::find()->limit($limit);

		$idString = '(' . implode(',', $orderProductIds) . ')';

		$query = <<<SQL
SELECT product_a, product_b
FROM {{%purchase_patterns}}
WHERE (product_a IN $idString OR product_b in $idString)
ORDER BY purchase_count DESC
LIMIT $limit
SQL;

		$results = Craft::$app->db->createCommand($query)->queryAll();
		$productIds = [];

		foreach ($results as $result)
		{
			$idA = $result['product_a'];
			$idB = $result['product_b'];

			if (!in_array($idA, $productIds) && !in_array($idA, $orderProductIds))
				$productIds[] = $idA;

			if (!in_array($idB, $productIds) && !in_array($idB, $orderProductIds))
				$productIds[] = $idB;
		}

		if (count($productIds) < $limit && $paddingQuery)
		{
			$paddingLimit = $limit - count($productIds);
			$paddingIds = $paddingQuery->limit($paddingLimit)->ids();
			$productIds = array_merge($productIds, $paddingIds);
		}

		return Product::find()->id($productIds);
	}

	/**
	 * Returns the top 10 product combinations
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getTopBoughtTogether ()
	{
		$query = <<<SQL
SELECT product_a, product_b, purchase_count
FROM {{%purchase_patterns}}
ORDER BY purchase_count DESC
LIMIT 10
SQL;

		$results = Craft::$app->db->createCommand($query)->queryAll();
		$productIds = [];

		foreach ($results as $result)
		{
			$idA = $result['product_a'];
			$idB = $result['product_b'];

			if (!in_array($idA, $productIds))
				$productIds[] = $idA;

			if (!in_array($idB, $productIds))
				$productIds[] = $idB;
		}

		$products = Product::find()->id($productIds)->all();
		$productsById = [];

		foreach ($products as $product)
			$productsById[$product->id] = $product;

		$rows = [];

		foreach ($results as $result)
		{
			$rows[] = [
				'a' => $productsById[$result['product_a']],
				'b' => $productsById[$result['product_b']],
				'count' => $result['purchase_count']
			];
		}

		return $rows;
	}

	/**
	 * @param Product $product
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getBoughtTogetherMeta (Product $product)
	{
		$id = $product->id;

		$query = <<<SQL
SELECT *
FROM {{%purchase_patterns}}
WHERE (product_a = $id OR product_b = $id)
ORDER BY purchase_count DESC
LIMIT 10
SQL;

		$results = Craft::$app->getDb()->createCommand($query)->queryAll();
		$productIds = [];
		$countByProductId = [];

		foreach ($results as $result)
		{
			$id =
				$result['product_a'] === $id
					? $result['product_b']
					: $result['product_a'];
			$productIds[] = $id;
			$countByProductId[$id] = $result['purchase_count'];
		}

		$products = Product::find()->id($productIds)->fixedOrder(true)->all();

		return array_map(function (Product $product) use ($countByProductId) {
			return [
				'title' => $product->title,
				'cpEditUrl' => $product->getCpEditUrl(),
				'count' => $countByProductId[$product->id],
			];
		}, $products);
	}

}