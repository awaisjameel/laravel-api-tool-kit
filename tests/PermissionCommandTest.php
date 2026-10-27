<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;

class PermissionCommandTest extends TestCase
{
    #[Test]
    public function generationWorksWithoutAMissingTemplateAndIsIdempotent(): void
    {
        $directory = $this->app->configPath();
        $this->app->useConfigPath(database_path('config'));
        try {
            config()->set('permissions-map', ['other' => ['index' => 'list']]);
            config()->set('api-tool-kit.use_soft_delete', true);
            foreach ([1,2] as $attempt) {
                self::assertSame(0, Artisan::call('generate:permissions', ['model' => 'posts']), Artisan::output());
            }
            $map = require config_path('permissions-map.php');
            self::assertSame(['index' => 'list'], $map['other']);
            self::assertSame('restore', $map['posts']['restore']);
            $csv = file_get_contents(database_path('permissions.csv'));
            self::assertSame(1, substr_count($csv, 'posts.create'));
        } finally {
            $this->app->useConfigPath($directory);
        }
    }
}
