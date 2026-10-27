<?php

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\Tests\Mocks\Models\TestModel;
use PHPUnit\Framework\Attributes\Test;

class HasActivationTraitTest extends TestCase
{
    #[Test]
    public function activeScopeReturnsOnlyActiveRecords(): void
    {
        TestModel::factory(3)->create([
            'is_active' => true,
        ]);

        TestModel::factory(7)->create([
            'is_active' => false,
        ]);

        $activeRecords = TestModel::active()->get();

        $this->assertCount(3, $activeRecords);
    }

    #[Test]
    public function toggleActivationChangesStatus(): void
    {
        $model = TestModel::factory()->create([
            'is_active' => true,
        ]);

        $model->toggleActivation();

        $this->assertFalse($model->is_active);

        $model->toggleActivation();

        $this->assertTrue($model->is_active);
    }
}
