<?php declare(strict_types=1);

namespace ElasticScoutDriver;

use ElasticScoutDriver\Factories\DocumentFactory;
use ElasticScoutDriver\Factories\DocumentFactoryInterface;
use ElasticScoutDriver\Factories\ModelFactory;
use ElasticScoutDriver\Factories\ModelFactoryInterface;
use ElasticScoutDriver\Factories\SearchParametersFactory;
use ElasticScoutDriver\Factories\SearchParametersFactoryInterface;
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
    private $weakBindings = [
        ModelFactoryInterface::class => ModelFactory::class,
        DocumentFactoryInterface::class => DocumentFactory::class,
        SearchParametersFactoryInterface::class => SearchParametersFactory::class,
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

        foreach ($this->weakBindings as $key => $value) {
            $this->app->bindIf($key, $value);
        }
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
