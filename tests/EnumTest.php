<?php

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\Tests\Mocks\TestEnum;
use PHPUnit\Framework\Attributes\Test;

class EnumTest extends TestCase
{
    #[Test]
    public function generatesEnumClass(): void
    {
        $name = 'TestEnum';

        $this->artisan('make:enum', ['name' => 'Enums/' . $name, '--string' => true, '--force' => true])
            ->assertExitCode(0);

        $actionClassPath = app_path("Enums/{$name}.php");

        $this->assertFileExists($actionClassPath);

        $this->assertStringContainsString('namespace App\\Enums;', file_get_contents($actionClassPath));

        $this->assertStringContainsString("enum {$name}", file_get_contents($actionClassPath));
    }

    #[Test]
    public function itProvidesEnumFunctionality(): void
    {
        $this->assertEquals(['EXAMPLE' => 'example'], TestEnum::toArray());
        $this->assertEquals(['EXAMPLE'], TestEnum::getConst());
        $this->assertTrue(TestEnum::isValid('example'));
        $this->assertTrue(TestEnum::isValidConst('EXAMPLE'));
        $this->assertEquals('example', TestEnum::getValue('EXAMPLE'));
    }
}
