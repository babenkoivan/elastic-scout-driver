<?php declare(strict_types=1);

use ElasticAdapter\Indices\Mapping;
use ElasticAdapter\Indices\Settings;
use ElasticMigrations\Facades\Index;
use ElasticMigrations\MigrationInterface;

final class CreateClientsIndex implements MigrationInterface
{
    public function up(): void
    {
        Index::create('clients', static function (Mapping $mapping) {
            $mapping->text('name');
            $mapping->text('last_name');
            $mapping->keyword('phone_number');
            $mapping->keyword('email');
        });

        Index::create('clients_sharded', static function (Mapping $mapping, Settings $settings) {
            $mapping->text('name');
            $mapping->text('last_name');
            $mapping->keyword('phone_number');
            $mapping->keyword('email');

            $settings->index([
                'number_of_shards' => 4,
            ]);
        });
    }

    public function down(): void
    {
        Index::dropIfExists('clients');
        Index::dropIfExists('clients_sharded');
    }
}
