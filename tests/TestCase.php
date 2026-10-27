<?php

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\APIToolKitServiceProvider;
use Essa\APIToolKit\MacroServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    private ?string $generatorRoot = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->generatorRoot = sys_get_temp_dir() . '/api-toolkit-tests-' . bin2hex(random_bytes(8));
        $originalAppPath = app_path();
        $originalDatabasePath = database_path();
        $originalBasePath = base_path();
        $this->app->getNamespace();
        $this->app->useAppPath($this->generatorRoot . '/app');
        $this->app->useDatabasePath($this->generatorRoot . '/database');
        foreach (config('api-tool-kit.groups_files_paths.default') as $type => $info) {
            $info['folder_path'] = str_replace([$originalAppPath, $originalDatabasePath, $originalBasePath], [app_path(), database_path(), $this->generatorRoot], $info['folder_path']);
            if ($type === 'routes') {
                $info['folder_path'] = $this->generatorRoot . '/routes';
            }
            config()->set('api-tool-kit.groups_files_paths.default.' . $type, $info);
        }

        $this->setUpDatabase($this->app);

        $this->createRoutesFile();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Essa\\APIToolKit\\Tests\\database\\factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            if ($this->generatorRoot !== null && is_dir($this->generatorRoot)) {
                $resolved = realpath($this->generatorRoot);
                $temporary = realpath(sys_get_temp_dir());
                if ($resolved === false || $temporary === false || !str_starts_with($resolved, $temporary . DIRECTORY_SEPARATOR . 'api-toolkit-tests-')) {
                    throw new \RuntimeException('Unsafe generator fixture cleanup path.');
                }
                (new \Illuminate\Filesystem\Filesystem())->deleteDirectory($resolved);
            }
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            APIToolKitServiceProvider::class,
            MacroServiceProvider::class,
        ];
    }

    protected function setUpDatabase(Application $app): void
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table): void {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
            $table->string('code');
            $table->boolean('is_active')->default(true);
        });

        $app['db']->connection()->getSchemaBuilder()->create('sluggable_test_models', function (Blueprint $table): void {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('test_model_id')->nullable();
        });
    }

    protected function normalizeWhitespaceAndNewlines(string $content): string
    {
        $content = preg_replace('/\s+/', ' ', $content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return trim($content);
    }

    private function createRoutesFile(): void
    {
        $filePath = base_path('routes/api.php');

        if (is_dir($filePath)) {
            rmdir($filePath);
        }

        if (! file_exists($filePath)) {
            touch($filePath);
        }
    }
}
