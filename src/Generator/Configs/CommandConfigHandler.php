<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Configs;

use Essa\APIToolKit\Generator\Exception\ConfigNotFoundException;
use Illuminate\Support\Facades\Config;

class CommandConfigHandler
{
    /**
     * Get all API generator commands from the configuration.
     *
     * @return array<string, class-string<\Essa\APIToolKit\Generator\Contracts\GeneratorCommandInterface>>
     */
    public static function getAllCommands(): array
    {
        $commands = Config::get('api-tool-kit-internal.api_generator_commands', []);
        if (!is_array($commands)) {
            throw new ConfigNotFoundException('Generator commands must be a map.');
        }
        $validated = [];
        foreach ($commands as $type => $class) {
            if (!is_string($type) || !is_string($class) || !is_a($class, \Essa\APIToolKit\Generator\Contracts\GeneratorCommandInterface::class, true)) {
                throw new ConfigNotFoundException('Invalid generator command configuration.');
            }
            $validated[$type] = $class;
        }
        return $validated;
    }

    /**
     * Get the class name (as a string) of an API generator command.
     *
     * @param string $commandName The name of the API generator command.
     *
     * @return string|null The class name of the API generator command or null if not found.
     * @throws ConfigNotFoundException If the configuration for the command is not found.
     */
    public static function getCommandClass(string $commandName): ?string
    {
        $commandClass = self::getAllCommands()[$commandName] ?? null;

        if (! $commandClass) {
            throw new ConfigNotFoundException("API generator command configuration not found for command: {$commandName}");
        }

        return $commandClass;
    }

    /**
     * Process different API generator commands using a callback function.
     *
     * @param callable $callback The callback function to process each API generator command.
     * @throws ConfigNotFoundException If the configuration for a command is not found.
     */
    public static function iterateOverCommandsFromConfig(callable $callback): void
    {
        $apiGeneratorCommands = self::getAllCommands();

        foreach ($apiGeneratorCommands as $type => $commandClass) {
            call_user_func($callback, $type, $commandClass);
        }
    }
}
