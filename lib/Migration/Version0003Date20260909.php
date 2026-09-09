<?php

namespace OCA\TickyCRM\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Creates the ticky_client_relations table which stores directed (but
 * logically symmetric) relationships between CRM clients.
 *
 * Supported relation_type values (enforced in the service layer):
 *   partner        – strategic or reseller partner
 *   subsidiary     – client_id is parent, related_client_id is subsidiary
 *   parent         – client_id is subsidiary, related_client_id is parent
 *   reseller        – client_id resells for related_client_id
 *   reseller_client – client_id is a customer of reseller related_client_id
 *   competitor      – competing companies
 *   other           – free-form relationship
 */
class Version0003Date20260909 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $tableName = 'ticky_client_relations';

        if ($schema->hasTable($tableName)) {
            return null;
        }

        $table = $schema->createTable($tableName);

        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
            'notnull' => true,
            'unsigned' => true,
        ]);

        $table->addColumn('client_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'Source client (ticky_clients.id)',
        ]);

        $table->addColumn('related_client_id', 'integer', [
            'notnull' => true,
            'unsigned' => true,
            'comment' => 'Target client (ticky_clients.id)',
        ]);

        $table->addColumn('relation_type', 'string', [
            'notnull' => true,
            'length' => 32,
            'default' => 'other',
        ]);

        $table->addColumn('notes', 'text', [
            'notnull' => false,
            'default' => null,
        ]);

        $table->addColumn('created_at', 'datetime', [
            'notnull' => true,
        ]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['client_id'], 'ticky_rel_client_idx');
        $table->addIndex(['related_client_id'], 'ticky_rel_related_idx');
        $table->addUniqueIndex(['client_id', 'related_client_id'], 'ticky_rel_unique');

        $table->addForeignKeyConstraint(
            $schema->getTable('ticky_clients'),
            ['client_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'ticky_rel_client_fk'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('ticky_clients'),
            ['related_client_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'ticky_rel_related_fk'
        );

        return $schema;
    }
}
