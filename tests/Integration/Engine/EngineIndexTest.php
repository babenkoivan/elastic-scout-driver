<?php declare(strict_types=1);

namespace ElasticScoutDriver\Tests\Integration\Engine;

use ElasticAdapter\Indices\IndexManager;
use ElasticScoutDriver\Engine;
use ElasticScoutDriver\Tests\Integration\TestCase;
use InvalidArgumentException;

/**
 * @covers \ElasticScoutDriver\Engine
 */
final class EngineIndexTest extends TestCase
{
    private const INDEX_NAME = 'test';

    /**
     * @var IndexManager
     */
    private $indexManager;
    /**
     * @var Engine
     */
    private $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexManager = resolve(IndexManager::class);
        $this->engine = resolve(Engine::class);
    }

    public function test_index_with_given_name_can_be_created(): void
    {
        $this->engine->createIndex(self::INDEX_NAME);
        $this->assertTrue($this->indexManager->exists(self::INDEX_NAME));
    }

    public function test_index_with_alternative_primary_key_can_not_be_created(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->createIndex(self::INDEX_NAME, ['primaryKey' => 'bar']);
    }

    /**
     * @depends test_index_with_given_name_can_be_created
     */
    public function test_index_can_be_deleted_by_name(): void
    {
        $this->engine->deleteIndex(self::INDEX_NAME);
        $this->assertFalse($this->indexManager->exists(self::INDEX_NAME));
    }
}
