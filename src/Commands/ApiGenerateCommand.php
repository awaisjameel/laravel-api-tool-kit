<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;
use Essa\APIToolKit\Generator\ApiGenerationCommandInputs;
use Essa\APIToolKit\Generator\Configs\CommandConfigHandler;
use Essa\APIToolKit\Generator\Configs\PathConfigHandler;
use Essa\APIToolKit\Generator\ConsoleTable\GeneratedFilesConsoleTable;
use Essa\APIToolKit\Generator\ConsoleTable\SchemaConsoleTable;
use Essa\APIToolKit\Generator\Contracts\ConsoleTableInterface;
use Essa\APIToolKit\Generator\SchemaDefinition;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ApiGenerateCommand extends Command
{
    protected $name = 'api:generate';

    protected $description = 'This command generate api crud.';

    /** @var list<string> */
    private array $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
    ];

    public function __construct(private readonly Container $container, private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('model');
        $group = $this->option('group');
        $schema = $this->argument('schema');
        if (!is_string($name) || !is_string($group) || ($schema !== null && !is_string($schema))) {
            $this->error('Model, group and schema must be strings.');
            return self::FAILURE;
        }
        if ($this->isReservedName($name)) {
            $this->error('The name "' . $name . '" is reserved by PHP.');

            return self::FAILURE;
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) !== 1) {
            $this->error('The model must be a simple PHP class name.');
            return self::FAILURE;
        }
        $model = ucfirst($name);
        try {
            $tokens = token_get_all('<?php class ' . $model . ' {}', TOKEN_PARSE);
            if ($tokens === []) {
                throw new \ParseError('Empty class definition.');
            }
        } catch (\ParseError) {
            $this->error('The model name is not a valid PHP class name.');
            return self::FAILURE;
        }
        if (! PathConfigHandler::isValidPathGroup($group)) {
            $this->error('The path group you entered is not valid');

            return self::FAILURE;
        }

        $snapshots = [];
        try {
            $apiGenerationCommandInputs = new ApiGenerationCommandInputs(
                model: $model,
                userChoices: $this->getUserChoices(),
                schema: SchemaDefinition::createFromSchemaString($schema),
                pathGroup: $group
            );

            foreach (CommandConfigHandler::getAllCommands() as $type => $class) {
                if (!$apiGenerationCommandInputs->isOptionSelected($type)) {
                    continue;
                }
                $path = PathConfigHandler::generateFilePathInfo($group, $type, $model)->getFullPath();
                if (is_link($path) || (file_exists($path) && (!is_file($path) || ($type !== GeneratorFilesType::ROUTES->value && !$this->option('force'))))) {
                    $this->error('Refusing to overwrite ' . $path . '. Review the destination or use --force for existing files.');
                    return self::FAILURE;
                }
                $snapshots[$path] = is_file($path) ? $this->files->get($path) : null;
            }

            $this->executeCommands($apiGenerationCommandInputs);
        } catch (\Throwable $exception) {
            $rollbackErrors = [];
            foreach ($snapshots as $path => $content) {
                try {
                    if ($content === null) {
                        if ($this->files->exists($path) && !$this->files->delete($path)) {
                            throw new \RuntimeException('Unable to remove the partial output.');
                        }
                    } else {
                        $this->files->replace($path, $content);
                    }
                } catch (\Throwable $rollbackException) {
                    $rollbackErrors[] = 'Rollback failed for ' . $path . ': ' . $rollbackException->getMessage();
                }
            }
            $this->error('API generation failed: ' . $exception->getMessage());
            foreach ($rollbackErrors as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $this->info('Here is your schema : ');

        $this->displayTable(new SchemaConsoleTable(), $apiGenerationCommandInputs);

        $this->info('Generated Files : ');

        $this->displayTable(new GeneratedFilesConsoleTable(), $apiGenerationCommandInputs);

        return self::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model.'],
            ['schema', InputArgument::OPTIONAL, 'The schema.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['all', null, InputOption::VALUE_NONE, 'Generate a migration, seeder, factory, policy, resource controller, and form request classes for the model'],
            ['force', null, InputOption::VALUE_NONE, 'Overwrite selected existing generated files'],
            ['routes', null, InputOption::VALUE_NONE, 'Generate routes for the crud operations'],
            ['soft-delete', null, InputOption::VALUE_NONE, 'Generate soft delete functionality for the model'],
            ['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model'],
            ['factory', 'f', InputOption::VALUE_NONE, 'Create a new factory for the model'],
            ['filter', 'F', InputOption::VALUE_NONE, 'Create a new filter for the model'],
            ['test', 't', InputOption::VALUE_NONE, 'Create new test cases for the model'],
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model'],
            ['seeder', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller for the model'],
            ['request', 'R', InputOption::VALUE_NONE, 'Create new form request classes for the model and use them in the resource controller'],
            ['group', 'g', InputOption::VALUE_OPTIONAL, 'Specify the group for the generated files', PathConfigHandler::getDefaultPathGroup()],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function getUserChoices(): array
    {
        if ($this->option('all')) {
            $this->setDefaultOptions();
        }

        $extraOptions = [
            GeneratorFilesType::UPDATE_REQUEST->value => $this->option('request'),
            GeneratorFilesType::CREATE_REQUEST->value => $this->option('request'),
            'model' => true,
        ];

        $options = [];
        foreach ($this->options() as $key => $value) {
            if (is_bool($value)) {
                $options[$key] = $value;
            }
        }
        return $options + array_map(static fn ($value): bool => $value === true, $extraOptions);
    }

    private function setDefaultOptions(): void
    {
        $defaultOptions = config('api-tool-kit.default_generates');

        foreach ($defaultOptions as $option) {
            $this->input->setOption($option, true);
        }
    }

    private function isReservedName(string $name): bool
    {
        return in_array(mb_strtolower($name), $this->reservedNames);
    }

    private function executeCommands(ApiGenerationCommandInputs $apiGenerationCommandInputs): void
    {
        $apiGeneratorCommands = CommandConfigHandler::getAllCommands();

        foreach ($apiGeneratorCommands as $type => $commandClass) {
            if ($apiGenerationCommandInputs->isOptionSelected($type)) {
                $this->container
                    ->get($commandClass)
                    ->run($apiGenerationCommandInputs);
            }
        }
    }

    private function displayTable(
        ConsoleTableInterface $consoleTable,
        ApiGenerationCommandInputs $apiGenerationCommandInputs
    ): void {
        $output = $consoleTable->generate($apiGenerationCommandInputs);

        $this->table($output->getHeaders(), $output->getTableData());
    }
}
