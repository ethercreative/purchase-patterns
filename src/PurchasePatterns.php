<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns;

use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Dashboard;
use craft\web\twig\variables\CraftVariable;
use ether\purchasePatterns\widgets\BoughtTogether;
use ether\purchasePatterns\widgets\OrderHeatmap;
use yii\base\Event;


/**
 * Class PurchasePatterns
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

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			[$this, 'onRegisterVariable']
		);

		Event::on(
			Dashboard::class,
			Dashboard::EVENT_REGISTER_WIDGET_TYPES,
			[$this, 'onRegisterWidgets']
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
		/** @var Service $service */
		$service = $this->get('service');
		return $service;
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

	/**
	 * @param Event $event
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	public function onRegisterVariable (Event $event)
	{
		/** @var CraftVariable $variable */
		$variable = $event->sender;
		$variable->set('purchasePatterns', Variable::class);
	}

	/**
	 * @param RegisterComponentTypesEvent $event
	 */
	public function onRegisterWidgets (RegisterComponentTypesEvent $event)
	{
		$event->types[] = BoughtTogether::class;
		$event->types[] = OrderHeatmap::class;
	}

}