<?php declare(strict_types=1);

namespace Elastic\ScoutDriver\Tests\Integration\Engine;

use Elastic\Adapter\Indices\IndexManager;
use Elastic\ScoutDriver\Engine;
use Elastic\ScoutDriver\Tests\Integration\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass(Engine::class)]
final class EngineIndexTest extends TestCase
{
    private const INDEX_NAME = 'test';

    private IndexManager $indexManager;
    private Engine $engine;

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

    #[Depends('test_index_with_given_name_can_be_created')]
    public function test_index_can_be_deleted_by_name(): void
    {
        $this->engine->deleteIndex(self::INDEX_NAME);
        $this->assertFalse($this->indexManager->exists(self::INDEX_NAME));
    }
}
