<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\Filters\DTO\FiltersDTO;
use Essa\APIToolKit\Tests\Mocks\Models\TestModel;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

class UpgradeSafetyTest extends TestCase
{
    #[Test]
    public function fullGenerationReportsItsActualOutcome(): void
    {
        $status = \Illuminate\Support\Facades\Artisan::call('api:generate', ['model' => 'BlogPost', 'schema' => 'title:string', '--all' => true]);
        self::assertSame(0, $status, \Illuminate\Support\Facades\Artisan::output());
        self::assertFileExists(app_path('Models/BlogPost.php'));
    }

    #[Test]
    public function existingSourceIsPreservedUnlessForceIsExplicit(): void
    {
        $artisan = \Illuminate\Support\Facades\Artisan::class;
        self::assertSame(0, $artisan::call('api:generate', ['model' => 'OwnedPost']));
        $model = app_path('Models/OwnedPost.php');
        file_put_contents($model, '<?php // application customization');
        self::assertSame(1, $artisan::call('api:generate', ['model' => 'OwnedPost','--all' => true]));
        self::assertSame('<?php // application customization', file_get_contents($model));
        self::assertFileDoesNotExist(database_path('factories/OwnedPostFactory.php'));
    }

    #[Test]
    public function forceRegenerationReplacesOneRouteBlockAndReusesTheExistingMigration(): void
    {
        $artisan = \Illuminate\Support\Facades\Artisan::class;
        $arguments = ['model' => 'RepeatPost','schema' => 'title:string','--all' => true];
        self::assertSame(0, $artisan::call('api:generate', $arguments), $artisan::output());
        config()->set('api-tool-kit.groups_files_paths.default.migration.file_name', '2099_01_01_000000_create_{TableName}_table.php');
        self::assertSame(0, $artisan::call('api:generate', $arguments + ['--force' => true]), $artisan::output());
        self::assertCount(1, glob(database_path('migrations/*_create_repeat_posts_table.php')));
        $routes = file_get_contents(config('api-tool-kit.groups_files_paths.default.routes.folder_path') . '/api.php');
        self::assertSame(1, substr_count($routes, '// <api-tool-kit:RepeatPost>'));
        self::assertStringContainsString("'/repeat-posts'", $routes);
    }

    #[Test]
    public function aWriteFailureRestoresExistingFilesAndRemovesPartialOutputs(): void
    {
        $files = new class () extends \Illuminate\Filesystem\Filesystem {
            public bool $failController = false;
            public function replace($path, $content, $mode = null): void
            {
                if ($this->failController && str_ends_with($path, 'RollbackPostController.php')) {
                    $this->failController = false;
                    throw new \RuntimeException('Simulated write failure.');
                }
                parent::replace($path, $content, $mode);
            }
        };
        $this->app->instance(\Illuminate\Filesystem\Filesystem::class, $files);
        $artisan = \Illuminate\Support\Facades\Artisan::class;
        self::assertSame(0, $artisan::call('api:generate', ['model' => 'RollbackPost']));
        $path = app_path('Models/RollbackPost.php');
        file_put_contents($path, '<?php // custom existing source');
        $files->failController = true;
        self::assertSame(1, $artisan::call('api:generate', ['model' => 'RollbackPost','--all' => true,'--force' => true]));
        self::assertSame('<?php // custom existing source', file_get_contents($path));
        self::assertFileDoesNotExist(database_path('factories/RollbackPostFactory.php'));
        self::assertFileDoesNotExist(database_path('seeders/RollbackPostSeeder.php'));
    }

    #[Test]
    public function rollbackContinuesAfterOneRestorationFailsAndReportsTheUnrestoredFile(): void
    {
        $files = new class () extends \Illuminate\Filesystem\Filesystem {
            public bool $failGeneration = false;
            private bool $restoring = false;

            public function replace($path, $content, $mode = null): void
            {
                if ($this->failGeneration && str_ends_with($path, 'PartialRollbackController.php')) {
                    $this->restoring = true;
                    throw new \RuntimeException('Simulated controller write failure.');
                }
                if ($this->restoring && str_ends_with($path, 'PartialRollback.php')) {
                    throw new \RuntimeException('Simulated model restoration failure.');
                }
                parent::replace($path, $content, $mode);
            }
        };
        $this->app->instance(\Illuminate\Filesystem\Filesystem::class, $files);
        $artisan = \Illuminate\Support\Facades\Artisan::class;
        self::assertSame(0, $artisan::call('api:generate', ['model' => 'PartialRollback']));
        $files->failGeneration = true;
        self::assertSame(1, $artisan::call('api:generate', ['model' => 'PartialRollback', '--all' => true, '--force' => true]));
        $output = $artisan::output();
        self::assertStringContainsString('Simulated controller write failure.', $output);
        self::assertStringContainsString('Rollback failed for', $output);
        self::assertStringContainsString('PartialRollback.php', $output);
        self::assertFileDoesNotExist(database_path('factories/PartialRollbackFactory.php'));
        self::assertFileDoesNotExist(database_path('seeders/PartialRollbackSeeder.php'));
    }

    #[Test]
    public function customGroupsUseTheirConfiguredModelAndFactoryNamespaces(): void
    {
        $paths = config('api-tool-kit.groups_files_paths.default');
        $paths['model']['folder_path'] = app_path('Domain/Shop/Models');
        $paths['model']['namespace'] = 'App\\Domain\\Shop\\Models';
        $paths['factory']['folder_path'] = database_path('factories/Shop');
        $paths['factory']['namespace'] = 'Database\\Factories\\Shop';
        config()->set('api-tool-kit.groups_files_paths.shop', $paths);
        config()->set('api-tool-kit.groups_url_prefixes.shop', '/api/shop');
        $status = \Illuminate\Support\Facades\Artisan::call('api:generate', ['model' => 'DomainPurchase','schema' => 'account_id:foreignId','--factory' => true,'--group' => 'shop']);
        self::assertSame(0, $status, \Illuminate\Support\Facades\Artisan::output());
        $model = file_get_contents(app_path('Domain/Shop/Models/DomainPurchase.php'));
        $factory = file_get_contents(database_path('factories/Shop/DomainPurchaseFactory.php'));
        self::assertStringContainsString('\\App\\Domain\\Shop\\Models\\Account::class', $model);
        self::assertStringContainsString('Database\\Factories\\Shop\\DomainPurchaseFactory', $model);
        self::assertStringContainsString('\\App\\Domain\\Shop\\Models\\Account::class', $factory);
        self::assertNotEmpty(token_get_all($model, TOKEN_PARSE));
        self::assertNotEmpty(token_get_all($factory, TOKEN_PARSE));
    }
    #[Test]
    public function emptyFiltersAreSafeOnMaintainedPhp(): void
    {
        $dto = FiltersDTO::buildFromRequest(new Request());
        self::assertSame([], $dto->getIncludes());
        self::assertNull((new FiltersDTO())->getFilter('missing'));
        self::assertSame(0, TestModel::useFilters(null, $dto)->count());
    }

    #[Test]
    public function requestKeysCannotInvokeFilterInfrastructureMethods(): void
    {
        TestModel::factory()->create();
        $dto = new FiltersDTO(filters: ['setBuilder' => 'bad input', 'getBuilder' => 'bad input']);
        self::assertSame(1, TestModel::useFilters(null, $dto)->count());
    }

    #[Test]
    public function malformedFilterArraysReturnValidationErrors(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        FiltersDTO::buildFromRequest(new Request(['sorts' => ['name']]));
    }

    #[Test]
    public function invalidPageSizesCannotReachSqlArithmetic(): void
    {
        TestModel::factory()->create();
        foreach (['0', '-5', 'bad', ['bad']] as $value) {
            $this->app->instance('request', new Request(['per_page' => $value]));
            self::assertSame(20, TestModel::dynamicPaginate()->perPage());
        }
    }
}
