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
			'id'             => $this->primaryKey(),
			'product_a'      => $this->integer()->notNull(),
			'product_b'      => $this->integer()->notNull(),
			'purchase_count' => $this->integer()->notNull(),
		]);

		// Indexes

		$this->createIndex(
			null,
			'{{%purchase_patterns}}',
			['product_a', 'product_b'],
			true
		);
	}

}