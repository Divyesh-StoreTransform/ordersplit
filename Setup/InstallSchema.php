<?php

namespace Storetransform\OrderSplit\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
		
		  $table = $installer->getConnection()->newTable(
            $installer->getTable('mt_ordersplit_package_rule')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'primary' => true,'identity' => true],
            'id'
        )->addColumn(
            'active',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true, 'default' => '0'],
            'active'
        )->addColumn(
            'priority',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true, 'default' => '0'],
            'priority'
        )->addColumn(
            'shipping_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'shipping_code'
        )->addColumn(
            'package_max_weight',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'package_max_weight'
        )->addColumn(
            'package_max_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'package_max_price'
        )->addColumn(
            'package_max_qty',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'package_max_qty'
        )->addColumn(
            'shipping_price_formula',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'shipping_price_formula'
        )->addColumn(
            'tracking_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'tracking_url'
        )->addColumn(
            'extra_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true,'default' => ''],
            'extra_data'
        );
    
		
        $installer->getConnection()->createTable($table);
		
		 $table = $installer->getConnection()->newTable(
            $installer->getTable('mt_ordersplit_package_rule_product')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'primary' => true,'identity' => true],
            'id'
        )->addColumn(
            'shipping_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'shipping_code'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'product_id'
        )->addColumn(
            'product_max_qty',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'product_max_qty'
        )->addColumn(
            'product_max_weight',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'product_max_weight'
        )->addColumn(
            'product_cant_mix',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true, 'default' => '0'],
            'product_cant_mix'
        )->addColumn(
            'extra_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true,'default' => ''],
            'extra_data'
        );
    
		
        $installer->getConnection()->createTable($table);
		
		
        $installer->getConnection()->createTable($table);
		
		 $table = $installer->getConnection()->newTable(
            $installer->getTable('mt_ordersplit_package_orders')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'primary' => true,'identity' => true],
            'id'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'order_id'
        )->addColumn(
            'mtordersplit_package_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'package_data'
        )->addColumn(
            'mtordersplit_package_shipping',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'mtordersplit_package_shipping'
        )->addColumn(
            'sub_order_ids',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => ''],
            'sub_order_ids'
        )->addColumn(
            'extra_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true,'default' => ''],
            'extra_data'
        );
    
		
        $installer->getConnection()->createTable($table);
		
        $installer->endSetup();
    }
}