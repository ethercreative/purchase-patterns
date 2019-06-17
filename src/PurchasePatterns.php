<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns;

use Craft;
use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Dashboard;
use craft\web\twig\variables\CraftVariable;
use ether\purchasePatterns\jobs\PopulateDataJob;
use ether\purchasePatterns\widgets\BoughtTogether;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\db\Exception;


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

		// Events
		// ---------------------------------------------------------------------

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

		// Hooks
		// ---------------------------------------------------------------------

		Craft::$app->getView()->hook(
			'cp.commerce.product.edit.details',
			[$this, 'hookProductEditDetails']
		);

	}

	// Services
	// =========================================================================

	/**
	 * @return Service
	 * @throws InvalidConfigException
	 */
	public function getService (): Service
	{
		/** @var Service $service */
		$service = $this->get('service');
		return $service;
	}

	// Events
	// =========================================================================

	protected function afterInstall ()
	{
		parent::afterInstall();

		Craft::$app->getQueue()->push(new PopulateDataJob());
	}

	/**
	 * @param Event $event
	 *
	 * @throws InvalidConfigException
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
	 * @throws InvalidConfigException
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
	}

	// Hooks
	// =========================================================================

	/**
	 * @param array $context
	 *
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @throws InvalidConfigException
	 * @throws Exception
	 */
	public function hookProductEditDetails (array &$context)
	{
		$purchasedWith = $this->getService()->getBoughtTogetherMeta(
			$context['product']
		);

		return Craft::$app->getView()->renderTemplate(
			'purchase-patterns/_product/edit',
			array_merge($context, compact('purchasedWith'))
		);
	}

}