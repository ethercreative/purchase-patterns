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
		$this->_createPurchasesTable();

		return true;
	}

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%purchase_patterns}}');
		$this->dropTableIfExists('{{%purchase_counts}}');

		return true;
	}

	// Tables
	// =========================================================================

	private function _createPatternsTable ()
	{
		$this->createTable('{{%purchase_patterns}}', [
			'id'             => $this->primaryKey(),
			'product_a'      => $this->integer()->notNull(),
			'product_b'      => $this->integer()->notNull(),
			'purchase_count' => $this->integer()->notNull(),
		]);

		$this->createIndex(
			null,
			'{{%purchase_patterns}}',
			['product_a', 'product_b'],
			true
		);

		$this->addForeignKey(
			null,
			'{{%purchase_patterns}}',
			'product_a',
			'{{%commerce_products}}',
			'id',
			'CASCADE',
			null
		);

		$this->addForeignKey(
			null,
			'{{%purchase_patterns}}',
			'product_b',
			'{{%commerce_products}}',
			'id',
			'CASCADE',
			null
		);
	}

	private function _createPurchasesTable ()
	{
		$this->createTable('{{%purchase_counts}}', [
			'id'          => $this->primaryKey(),
			'product_id'  => $this->integer()->notNull(),
			'order_count' => $this->integer()->notNull(),
			'qty_count'   => $this->integer()->notNull(),
		]);

		$this->createIndex(
			null,
			'{{%purchase_counts}}',
			'product_id',
			true
		);

		$this->addForeignKey(
			null,
			'{{%purchase_counts}}',
			'product_id',
			'{{%commerce_products}}',
			'id',
			'CASCADE',
			null
		);
	}

}