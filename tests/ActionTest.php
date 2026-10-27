<?php

namespace Essa\APIToolKit\Tests;

use PHPUnit\Framework\Attributes\Test;

class ActionTest extends TestCase
{
    #[Test]
    public function generateActionClass(): void
    {
        $name = 'TestAction';

        $this->artisan('make:action', ['name' => $name])
            ->assertExitCode(0);

        $actionClassPath = app_path("Actions/{$name}.php");

        $this->assertFileExists($actionClassPath);

        $this->assertStringContainsString('namespace App\\Actions;', file_get_contents($actionClassPath));

        $this->assertStringContainsString("class {$name}", file_get_contents($actionClassPath));

        $this->assertStringContainsString('public function execute(array $data)', file_get_contents($actionClassPath));
    }
}
