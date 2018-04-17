<?php
/**
 * Customers Also Bought
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns;

use craft\base\Plugin;
use craft\commerce\elements\Order;
use yii\base\Event;


/**
 * Class CustomersAlsoBought
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns
 * @since   1.0.0
 */
class PurchasePatterns extends Plugin
{

	// Properties
	// =========================================================================

	public $changelogUrl = 'https://raw.githubusercontent.com/ethercreative/purchase-patterns/v1/CHANGELOG.md';
	public $downloadUrl = 'https://github.com/ethercreative/purchase-patterns/archive/v1.zip';

	// Craft
	// =========================================================================

	public function init ()
	{
		parent::init();

		$this->setComponents([
			'service' => Service::class,
		]);

		Event::on(
			Order::class,
			Order::EVENT_AFTER_COMPLETE_ORDER,
			[$this, 'onOrderComplete']
		);
	}

	// Services
	// =========================================================================

	/**
	 * @return Service
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getService (): Service
	{
		return $this->get('service');
	}

	// Events
	// =========================================================================

	/**
	 * @param Event $event
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	public function onOrderComplete (Event $event)
	{
		/** @var Order $order */
		$order = $event->sender;
		$this->getService()->tallyProducts($order);
	}

}