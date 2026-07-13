<?php

    declare(strict_types=1);

    namespace DoctrineMigrations;

    use Doctrin\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;

    /**
     * Creates the api_nonces table backing App\Service\ApiNonceStore —
     * replay protection for inbound signed requests from ARS. Same shape as
     * ARS's own copy of this table (each side verifies inbound requests
     * independently, so each keeps its own nonce history).
     */
    final class Version20260713000001 extends AbstractMigrations {
        public function getDescription(): string {
            return 'Create api_nonces table for ARS <-> HRIS signed-request replay protection';
        }

        public function up(Schema $schema): void {
            $table = $schema->createTable('api_nonces');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('service_id', 'string', ['length' => 50]);
            $table->addColumn('nonce', 'string', ['length' => 64]);
            $table->addColumn('request_ts', 'datetime');
            $table->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['service_id', 'nonce'], 'uq_service_nonce');
            $table->addIndex(['request_ts'], 'idx_nonces_request_ts');
        }

        public function down(Schema $schema): void {
            $schema->dropTable('api_nonces');
        }
    }