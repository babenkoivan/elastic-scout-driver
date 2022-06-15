<?php declare(strict_types=1);

namespace Elastic\ScoutDriver;

use Elastic\ScoutDriver\Factories\DocumentFactory;
use Elastic\ScoutDriver\Factories\DocumentFactoryInterface;
use Elastic\ScoutDriver\Factories\ModelFactory;
use Elastic\ScoutDriver\Factories\ModelFactoryInterface;
use Elastic\ScoutDriver\Factories\SearchParametersFactory;
use Elastic\ScoutDriver\Factories\SearchParametersFactoryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as AbstractServiceProvider;
use Laravel\Scout\EngineManager;

final class ServiceProvider extends AbstractServiceProvider
{
    private string $configPath;

    private array $weakBindings = [
        ModelFactoryInterface::class => ModelFactory::class,
        DocumentFactoryInterface::class => DocumentFactory::class,
        SearchParametersFactoryInterface::class => SearchParametersFactory::class,
    ];

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->configPath = dirname(__DIR__) . '/config/elastic.scout_driver.php';
    }

    /**
     * @return void
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

        resolve(EngineManager::class)->extend('elastic', static fn () => resolve(Engine::class));
    }
}
