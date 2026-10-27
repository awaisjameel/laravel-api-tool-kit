<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GeneratePermissions extends Command
{
    protected $signature = 'generate:permissions {model}';
    protected $description = 'Generate permission mappings and a deduplicated permissions CSV';

    public function __construct(private readonly Filesystem $filesystem)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $snapshots = [];
        try {
            $this->modelName();
            $this->filesystem->ensureDirectoryExists(config_path());
            $this->filesystem->ensureDirectoryExists(database_path());
            foreach ([config_path('permissions-map.php'),database_path('permissions.csv')] as $path) {
                $snapshots[$path] = is_file($path) ? $this->filesystem->get($path) : null;
            }
            $permissions = ['index' => 'list','store' => 'create','show' => 'view','destroy' => 'delete','update' => 'update'];
            if (config('api-tool-kit.use_soft_delete')) {
                $permissions += ['restore' => 'restore','permanentDelete' => 'force-delete'];
            }
            $this->addPermissionsToConfigFile($permissions);
            $this->AddPermissionsToCsvFile(array_values(array_unique(array_values($permissions))));
        } catch (Throwable $exception) {
            foreach ($snapshots as $path => $content) {
                if ($content === null) {
                    $this->filesystem->delete($path);
                } else {
                    $this->filesystem->replace($path, $content);
                }
            }
            $this->error('Permission generation failed: ' . $exception->getMessage());
            return self::FAILURE;
        }
        $this->info('permissions generated successfully!');
        return self::SUCCESS;
    }

    /** @param array<string, string> $permissions */
    public function addPermissionsToConfigFile(array $permissions): void
    {
        $path = config_path('permissions-map.php');
        $current = is_file($path) ? $this->filesystem->getRequire($path) : config('permissions-map', []);
        if (!is_array($current)) {
            throw new InvalidArgumentException('Permission mappings must be an array.');
        }
        $current[$this->modelName()] = $permissions;
        $this->filesystem->replace($path, "<?php\n\nreturn " . var_export($current, true) . ";\n");
    }

    /** @param list<string> $permissions */
    public function AddPermissionsToCsvFile(array $permissions): void
    {
        $path = database_path('permissions.csv');
        $content = is_file($path) ? $this->filesystem->get($path) : $this->filesystem->get(__DIR__ . '/../permissions.csv');
        $lines = preg_split('/\R/', trim($content));
        if ($lines === false) {
            throw new RuntimeException('Could not parse the permissions CSV.');
        }
        $model = $this->modelName();
        foreach ($permissions as $permission) {
            $value = $model . '.' . $permission;
            if (!in_array($value, $lines, true)) {
                $content = rtrim($content) . "\n" . $value . "\n";
                $lines[] = $value;
            }
        }
        $this->filesystem->replace($path, $content);
    }

    private function modelName(): string
    {
        $model = $this->argument('model');
        if (!is_string($model) || preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/D', $model) !== 1) {
            throw new InvalidArgumentException('A permission group must be a simple name.');
        }
        return $model;
    }
}
