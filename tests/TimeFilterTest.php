<?php

namespace Essa\APIToolKit\Tests;

use Carbon\Carbon;
use Essa\APIToolKit\Tests\Mocks\Models\TestModel;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

class TimeFilterTest extends TestCase
{
    #[Test]
    public function useTimeFilterToFilterFromTime(): void
    {
        TestModel::factory(5)->create([
            'created_at' => Carbon::parse('2023-08-01 12:00:00'),
        ]);
        TestModel::factory(3)->create([
            'created_at' => Carbon::parse('2023-08-01 14:00:00'),
        ]);

        $this->app->bind('request', fn () => new Request([
            'from_time' => '13:00:00',
        ]));

        $records = TestModel::useFilters()->get();

        $this->assertCount(3, $records);
    }

    #[Test]
    public function useTimeFilterToFilterToTime(): void
    {
        TestModel::factory(5)->create([
            'created_at' => Carbon::parse('2023-08-01 12:00:00'),
        ]);
        TestModel::factory(3)->create([
            'created_at' => Carbon::parse('2023-08-01 14:00:00'),
        ]);

        $this->app->bind('request', fn () => new Request([
            'to_time' => '13:00:00',
        ]));

        $records = TestModel::useFilters()->get();

        $this->assertCount(5, $records);
    }

    #[Test]
    public function useTimeFilterWithBothFromAndToTime(): void
    {
        TestModel::factory(5)->create([
            'created_at' => Carbon::parse('2023-08-01 12:00:00'),
        ]);
        TestModel::factory(3)->create([
            'created_at' => Carbon::parse('2023-08-01 14:00:00'),
        ]);

        $this->app->bind('request', fn () => new Request([
            'from_time' => '13:00:00',
            'to_time' => '15:00:00',
        ]));

        $records = TestModel::useFilters()->get();

        $this->assertCount(3, $records);
    }
}
