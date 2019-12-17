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
use ether\purchasePatterns\elements\db\ProductQueryExtended;
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
		$db = Craft::$app->getDb();

		$productIds = [];
		$productQtys = [];
		$variantIds = [];

		foreach ($order->getLineItems() as $item)
		{
			/** @var Variant $variant */
			$variant = $item->getPurchasable();

			if (!$variant || !($variant instanceof Variant))
				continue;

			$productId = $variant->productId;
			if (!in_array($productId, $productIds))
				$productIds[] = $productId;

			$variantId = $variant->id;
			if (!in_array($variantId, $variantIds))
				$variantIds[] = $variantId;

			if (!in_array($productId, $productQtys))
				$productQtys[$productId] = 0;
			$productQtys[$productId] += $item->qty;
		}

		sort($productIds);

		try {
			foreach ($productIds as $idA) {
				$qty = $productQtys[$idA];
				$db->createCommand()->upsert(
					'{{%purchase_counts}}', [
						'product_id'  => $idA,
						'order_count' => 1,
						'qty_count'   => $qty,
					], [
						'order_count' => new Expression(
							'{{%purchase_counts}}.order_count + 1'
						),
						'qty_count'   => new Expression(
							'{{%purchase_counts}}.qty_count + ' . $qty
						),
					],
					[], false
				)->execute();

				foreach ($productIds as $idB) {
					if ($idA >= $idB)
						continue;

					$db->createCommand()->upsert(
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
	 * @param ProductQuery|null $paddingQuery
     * @param array             $filters
	 *
	 * @return ProductQueryExtended
	 * @throws Exception
	 */
	public function getRelatedToProductCriteria (Product $product, $limit = 8, ProductQuery $paddingQuery = null, array $filters = [])
	{
		$id = $product->id;

		$query = <<<SQL
SELECT product_a, product_b
FROM {{%purchase_patterns}}
WHERE (product_a = $id OR product_b = $id)
ORDER BY purchase_count DESC
SQL;

		$results = Craft::$app->db->createCommand($query)->queryAll();
		$productIds = [];

		foreach ($results as $result)
			$productIds[] =
				$result['product_a'] === $id
					? $result['product_b']
					: $result['product_a'];

        if (!empty($filters)) {
            $query = Product::find()
                ->id($productIds)
                ->limit($limit);

            foreach ($filters as $prop => $val) {
                $query->$prop($val);
            }

            $productIds = $query->ids();
        }

		if (count($productIds) < $limit && $paddingQuery) {
            $this->_mergeIds($paddingQuery, $productIds);
			$paddingLimit = $limit - count($productIds);
			$paddingIds = $paddingQuery->limit($paddingLimit)->ids();
			$productIds = array_merge($productIds, $paddingIds);
		} else {
            $productIds = array_slice($productIds, 0, $limit);
        }

		return $this->_getQuery()->id($productIds);
	}

	/**
	 * Finds related products for the given order
	 *
	 * @param Order             $order
	 * @param int               $limit
	 * @param ProductQuery|null $paddingQuery
     * @param array             $filters
	 *
	 * @return ProductQueryExtended
	 * @throws Exception
	 */
	public function getRelatedToOrderCriteria (Order $order, $limit = 8, ProductQuery $paddingQuery = null, array $filters = [])
	{
		$orderProductIds = [];
		foreach ($order->lineItems as $item)
			/** @var $variant Variant */
			if (($variant = $item->purchasable) && $variant instanceof Variant)
				$orderProductIds[] = $variant->product->id;


		if (empty($orderProductIds)) {
            $results = [];
        } else {
            $idString = '(' . implode(',', $orderProductIds) . ')';

    		$query = <<<SQL
SELECT product_a, product_b
FROM {{%purchase_patterns}}
WHERE (product_a IN $idString OR product_b in $idString)
ORDER BY purchase_count DESC
SQL;

    		$results = Craft::$app->db->createCommand($query)->queryAll();
        }

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

        if (!empty($filters)) {
            $query = Product::find()
                ->id($productIds)
                ->limit($limit);

            foreach ($filters as $prop => $val) {
                $query->$prop($val);
            }

            $productIds = $query->ids();
        }

		if (count($productIds) < $limit && $paddingQuery) {
            $this->_mergeIds($paddingQuery, $productIds);
			$paddingLimit = $limit - count($productIds);
			$paddingIds = $paddingQuery->limit($paddingLimit)->ids();
			$productIds = array_merge($productIds, $paddingIds);
		} else {
            $productIds = array_slice($productIds, 0, $limit);
        }

		return $this->_getQuery()->id($productIds);
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

		$products = Product::find()->id($productIds)->anyStatus()->all();
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
	 * Gets the products bought with the given product and returns the name,
	 * cp edit url, and order count
	 *
	 * @param Product $product
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getBoughtTogetherMeta (Product $product)
	{
		$query = <<<SQL
SELECT *
FROM {{%purchase_patterns}}
WHERE (product_a = {$product->id} OR product_b = {$product->id})
ORDER BY purchase_count DESC
LIMIT 10
SQL;

		$results = Craft::$app->getDb()->createCommand($query)->queryAll();
		$productIds = [];
		$countByProductId = [];

		foreach ($results as $result)
		{
			$id = $result['product_a'] === $product->id
				? $result['product_b']
				: $result['product_a'];

			if ($id === $product->id)
				continue;

			$productIds[] = $id;

			if (!array_key_exists($id, $countByProductId))
				$countByProductId[$id] = 0;

			$countByProductId[$id] += $result['purchase_count'];
		}

		$products = Product::find()->id($productIds)->anyStatus()->fixedOrder(true)->all();

		return array_map(function (Product $product) use ($countByProductId) {
			return [
				'title' => $product->title,
				'cpEditUrl' => $product->getCpEditUrl(),
				'count' => $countByProductId[$product->id],
			];
		}, $products);
	}

	/**
	 * @return ProductQueryExtended
	 */
	private function _getQuery ()
	{
		return new ProductQueryExtended(Product::class);
	}

    /**
     * @param ProductQuery $paddingQuery
     * @param array        $productIds
     *
     * @return void
     */
    private function _mergeIds(ProductQuery $paddingQuery, array $productIds)
    {
        switch (gettype($paddingQuery->id)) {
            case 'integer':
                if (in_array($paddingQuery->id, $productIds)) {
                    $newIds = false;
                }
                break;
            case 'string':
                if (strtolower(substr($paddingQuery->id, 0, 3)) === 'not') {
                    $newIds = explode(' ', $paddingQuery->id);
                    $newIds = array_merge($newIds, $productIds);
                } elseif (in_array($paddingQuery->id, $productIds)) {
                    $newIds = false;
                }
                break;
            case 'array':
                if (strtolower($paddingQuery->id[0]) === 'not') {
                    $newIds = array_merge($paddingQuery->id, $productIds);
                } else {
                    $newIds = array_diff($paddingQuery->id, $productIds);
                }
                break;
            case 'NULL':
                $newIds = array_merge(['not'], $productIds);
                break;
        }
        $paddingQuery->id($newIds);
    }

}
