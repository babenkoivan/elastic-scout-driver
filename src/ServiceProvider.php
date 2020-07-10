<?php declare(strict_types=1);

namespace ElasticScoutDriver;

use ElasticScoutDriver\Factories\DocumentFactory;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use ElasticScoutDriver\Factories\ModelFactory;
use ElasticScoutDriver\Factories\ModelFactoryInterface;
use ElasticScoutDriver\Factories\SearchRequestFactory;
use ElasticScoutDriver\Factories\SearchRequestFactoryInterface;
use Illuminate\Support\ServiceProvider as AbstractServiceProvider;
use Laravel\Scout\EngineManager;

final class ServiceProvider extends AbstractServiceProvider
{
    /**
     * @var string
     */
    private $configPath;
    /**
     * @var array
     */
    public $bindings = [
        ModelFactoryInterface::class => ModelFactory::class,
        DocumentFactoryInterface::class => DocumentFactory::class,
        SearchRequestFactoryInterface::class => SearchRequestFactory::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->configPath = dirname(__DIR__) . '/config/elastic.scout_driver.php';
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->configPath,
            basename($this->configPath, '.php')
        );
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->configPath => config_path(basename($this->configPath)),
        ]);

        resolve(EngineManager::class)->extend('elastic', static function () {
            return resolve(Engine::class);
        });
    }
}
