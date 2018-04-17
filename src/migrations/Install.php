<?php
/**
 * Purchase Patterns
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\purchasePatterns\migrations;

use craft\db\Migration;


/**
 * Class Install
 *
 * @author  Ether Creative
 * @package ether\purchasePatterns\migrations
 * @since   1.0.0
 */
class Install extends Migration
{

	public function safeUp ()
	{
		$this->_createPatternsTable();

		return true;
	}

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%purchase_patterns}}');

		return true;
	}

	// Tables
	// =========================================================================

	private function _createPatternsTable ()
	{
		// Table

		$this->createTable('{{%purchase_patterns}}', [
			[
				'id'            => $this->primaryKey(),
				'productA'      => $this->integer()->notNull(),
				'productB'      => $this->integer()->notNull(),
				'purchaseCount' => $this->integer()->notNull(),
			]
		]);

		// Indexes

		$this->createIndex(
			null,
			'{{%purchase_patterns}}',
			['productA', 'productB'],
			true
		);
	}

}