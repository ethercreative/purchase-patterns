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
 * Class OrderHeatmap
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns\widgets
 * @since   1.0.0
 */
class OrderHeatmap extends Widget
{

	// Public Methods
	// =========================================================================

	public static function isSelectable (): bool
	{
		return \Craft::$app->getUser()->checkPermission('commerce-manageOrders');
	}

	public static function displayName (): string
	{
		return \Craft::t(
			'purchase-patterns',
			'Orders Heatmap'
		);
	}

	public static function iconPath (): string
	{
		return \Craft::getAlias('@ether/purchasePatterns/resources/widget-icon.svg');
	}

	/**
	 * @return string
	 */
	public function getBodyHtml ()
	{
		return 'hello';
	}

}