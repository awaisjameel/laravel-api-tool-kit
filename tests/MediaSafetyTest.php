<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\MediaHelper;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class MediaSafetyTest extends TestCase
{
    #[Test]
    public function zeroLikeFileNamesRemainExplicitAndFailedWritesNeverReportSuccess(): void
    {
        Storage::fake('public');
        $path = MediaHelper::disk('public')->uploadBase64Image(MediaHelperTest::BASE_64_IMAGE, 'images', '0');
        self::assertSame('/images/0', $path);
        Storage::disk('public')->assertExists($path);
        $adapter = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $adapter->shouldReceive('put')->once()->andReturnFalse();
        Storage::shouldReceive('disk')->with('refusing')->andReturn($adapter);
        $this->expectException(\RuntimeException::class);
        MediaHelper::disk('refusing')->uploadBase64Image(MediaHelperTest::BASE_64_IMAGE, 'images');
    }
    #[Test]
    public function diskSelectionsRemainIsolatedAcrossInterleavedCalls(): void
    {
        Storage::fake('first');
        Storage::fake('second');
        $first = MediaHelper::disk('first');
        $second = MediaHelper::disk('second');
        $path = $first->uploadBase64Image(MediaHelperTest::BASE_64_IMAGE, 'images', 'first.png');
        Storage::disk('first')->assertExists($path);
        Storage::disk('second')->assertMissing($path);
        $second->uploadBase64Image(MediaHelperTest::BASE_64_IMAGE, 'images', 'second.png');
        Storage::disk('second')->assertExists('images/second.png');
    }

    #[Test]
    public function mislabeledNonImagesAreRejectedBeforeStorage(): void
    {
        Storage::fake('public');
        $this->expectException(\InvalidArgumentException::class);
        MediaHelper::disk('public')->uploadBase64Image('data:image/png;base64,' . base64_encode('plain text'), 'images');
    }
}
