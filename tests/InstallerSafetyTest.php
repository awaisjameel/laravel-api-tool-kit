<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class InstallerSafetyTest extends TestCase
{
    #[Test]
    public function existingWorkflowsAndKnowledgeRemainUntilForceIsExplicit(): void
    {
        $root = base_path('.claude');
        self::assertStringContainsString('testbench-core', base_path());
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root . '/skills/code-review');
        File::ensureDirectoryExists($root . '/knowledge');
        File::put($root . '/skills/code-review/SKILL.md', 'Application-owned workflow');
        File::put($root . '/knowledge/_TEMPLATE.md', 'Application-owned knowledge template');
        try {
            self::assertSame(0, Artisan::call('api-skill:install', ['tool' => 'claude']), Artisan::output());
            self::assertSame('Application-owned workflow', File::get($root . '/skills/code-review/SKILL.md'));
            self::assertSame('Application-owned knowledge template', File::get($root . '/knowledge/_TEMPLATE.md'));
            self::assertSame(0, Artisan::call('api-skill:install', ['tool' => 'claude','--force' => true]), Artisan::output());
            $content = File::get($root . '/skills/code-review/SKILL.md');
            self::assertStringContainsString('Managed by essa/api_tool_kit', $content);
            preg_match('/\A---\R(.*?)\R---/s', $content, $header);
            self::assertIsString(\Symfony\Component\Yaml\Yaml::parse($header[1])['description']);
        } finally {
            File::deleteDirectory($root);
        }
    }

    #[Test]
    public function repeatedMarkerUpdatesAreIdempotentAndPreserveUnrelatedInstructions(): void
    {
        $path = base_path('AGENTS.md');
        $old = File::exists($path) ? File::get($path) : null;
        $folder = base_path('.agent');
        self::assertStringContainsString('testbench-core', base_path());
        try {
            File::put($path, "Application instructions.\n");
            self::assertSame(0, Artisan::call('api-skill:install', ['tool' => 'antigravity','--force' => true]), Artisan::output());
            $first = File::get($path);
            self::assertSame(0, Artisan::call('api-skill:install', ['tool' => 'antigravity','--force' => true]), Artisan::output());
            self::assertSame($first, File::get($path));
            self::assertSame(1, substr_count($first, '<!-- LARAVEL API TOOL KIT START -->'));
            self::assertStringStartsWith('Application instructions.', $first);
        } finally {
            if ($old === null) {
                File::delete($path);
            } else {
                File::put($path, $old);
            }
            File::deleteDirectory($folder);
        }
    }
}
