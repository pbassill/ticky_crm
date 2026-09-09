<?php

namespace OCA\TickyCRM\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Creates the ticky_products table – the central product catalogue.
 *
 * Columns:
 *   sku       – unique stock-keeping unit identifier
 *   name      – human-readable product / service name
 *   description – free-text description
 *   price     – decimal unit price (up to 12 digits, 4 decimal places)
 *   currency  – ISO-4217 currency code (default EUR)
 *   unit      – billing unit, e.g. "piece", "hour", "month"
 *   category  – free-text category / grouping
 *   status    – active | inactive | discontinued
 *   created_at / updated_at
 */
class Version0004Date20260909 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $tableName = 'ticky_products';

        if ($schema->hasTable($tableName)) {
            return null;
        }

        $table = $schema->createTable($tableName);

        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull'       => true,
            'unsigned'      => true,
        ]);

        $table->addColumn('uuid', 'string', [
            'notnull' => true,
            'length'  => 32,
        ]);

        $table->addColumn('sku', 'string', [
            'notnull' => true,
            'length'  => 100,
        ]);

        $table->addColumn('name', 'string', [
            'notnull' => true,
            'length'  => 255,
        ]);

        $table->addColumn('description', 'text', [
            'notnull' => false,
            'default' => null,
        ]);

        $table->addColumn('price', 'decimal', [
            'notnull'   => false,
            'default'   => null,
            'precision' => 12,
            'scale'     => 4,
        ]);

        $table->addColumn('currency', 'string', [
            'notnull' => true,
            'length'  => 3,
            'default' => 'EUR',
        ]);

        $table->addColumn('unit', 'string', [
            'notnull' => false,
            'length'  => 50,
            'default' => null,
        ]);

        $table->addColumn('category', 'string', [
            'notnull' => false,
            'length'  => 100,
            'default' => null,
        ]);

        $table->addColumn('status', 'string', [
            'notnull' => true,
            'length'  => 20,
            'default' => 'active',
        ]);

        $table->addColumn('created_at', 'datetime', [
            'notnull' => true,
        ]);

        $table->addColumn('updated_at', 'datetime', [
            'notnull' => true,
        ]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['uuid'],  'ticky_products_uuid_unique');
        $table->addUniqueIndex(['sku'],   'ticky_products_sku_unique');
        $table->addIndex(['status'],      'ticky_products_status_idx');
        $table->addIndex(['category'],    'ticky_products_category_idx');

        return $schema;
    }
}
