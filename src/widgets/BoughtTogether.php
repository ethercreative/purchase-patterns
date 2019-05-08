<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns\widgets;

use Craft;
use craft\base\Widget;
use ether\purchasePatterns\PurchasePatterns;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\InvalidConfigException;
use yii\db\Exception;


/**
 * Class BoughtTogether
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns\widgets
 * @since   1.0.0
 */
class BoughtTogether extends Widget
{

	// Public Methods
	// =========================================================================

	public static function isSelectable (): bool
	{
		return Craft::$app->getUser()->checkPermission('commerce-manageProducts');
	}

	public static function displayName (): string
	{
		return Craft::t(
			'purchase-patterns',
			'Products Bought Together'
		);
	}

	public static function iconPath (): string
	{
		return Craft::getAlias('@ether/purchasePatterns/resources/widget-icon.svg');
	}

	/**
	 * @return false|string
	 * @throws Exception
	 * @throws InvalidConfigException
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	public function getBodyHtml ()
	{
		$combinations =
			PurchasePatterns::getInstance()->getService()->getTopBoughtTogether();

		return Craft::$app->getView()->renderTemplate(
			'purchase-patterns/_widgets/boughtTogether',
			compact('combinations')
		);
	}

}