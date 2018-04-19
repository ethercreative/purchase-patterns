<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns\widgets;

use craft\base\Widget;
use ether\purchasePatterns\PurchasePatterns;


/**
 * Class Widget
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
		return \Craft::$app->getUser()->checkPermission('commerce-manageProducts');
	}

	public static function displayName (): string
	{
		return \Craft::t(
			'purchase-patterns',
			'Products Bought Together'
		);
	}

	public static function iconPath (): string
	{
		return \Craft::getAlias('@ether/purchasePatterns/resources/widget-icon.svg');
	}

	/**
	 * @return false|string
	 * @throws \Twig_Error_Loader
	 * @throws \yii\base\Exception
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\db\Exception
	 */
	public function getBodyHtml ()
	{
		$combinations =
			PurchasePatterns::getInstance()->getService()->getTopBoughtTogether();

		return \Craft::$app->getView()->renderTemplate(
			'purchase-patterns/_widgets/boughtTogether',
			compact('combinations')
		);
	}

}