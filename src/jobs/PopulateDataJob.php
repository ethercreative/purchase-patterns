<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\purchasePatterns\jobs;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;
use ether\purchasePatterns\PurchasePatterns;
use yii\base\InvalidConfigException;
use yii\queue\Queue;

/**
 * Class PopulateDataJob
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns\jobs
 */
class PopulateDataJob extends BaseJob
{

	/**
	 * @param Queue|QueueInterface $queue The queue the job belongs to
	 *
	 * @throws InvalidConfigException
	 */
	public function execute ($queue)
	{
		$completeOrders = Order::find()->isCompleted(true);
		$total = $completeOrders->count();
		$service = PurchasePatterns::getInstance()->getService();
		$loop = 0;

		// TODO: Clear tables if we allow users to run this later, after install

		/** @var Order $order */
		foreach ($completeOrders->each() as $order)
		{
			$this->setProgress($queue, $loop++ / $total);
			$service->tallyProducts($order);
		}
	}

	protected function defaultDescription ()
	{
		return Craft::t('purchase-patterns', 'Collating purchase pattern data');
	}

}