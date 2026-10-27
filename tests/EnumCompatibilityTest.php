<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\Tests\Mocks\Models\TestModel;
use Essa\APIToolKit\Tests\Mocks\NumericUpgradeStatus;
use Essa\APIToolKit\Tests\Mocks\TestEnum;
use PHPUnit\Framework\Attributes\Test;

class EnumCompatibilityTest extends TestCase
{
    #[Test]
    public function nativeIntegerEnumsAreSupportedWithoutCrossTypeCoercion(): void
    {
        self::assertTrue(NumericUpgradeStatus::isValid(1));
        self::assertFalse(NumericUpgradeStatus::isValid('1'));
        self::assertSame(NumericUpgradeStatus::Active, NumericUpgradeStatus::fromValue(1));
        self::assertNull(NumericUpgradeStatus::fromValue('1'));
        self::assertSame(['Active' => 1], NumericUpgradeStatus::toArray());
    }

    #[Test]
    public function forkConstantEnumCastsStillPersistNamesAsValues(): void
    {
        $model = new class () extends TestModel {
            protected $casts = ['name' => TestEnum::class];
        };
        $model->name = 'EXAMPLE';
        $model->setTable('test_models');
        $model->save();
        self::assertSame('example', $model->fresh()->name);
        $this->expectException(\InvalidArgumentException::class);
        $model->name = true;
    }
}
