<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests;

use Essa\APIToolKit\Generator\ColumnDefinition;
use Essa\APIToolKit\Generator\SchemaDefinition;
use Essa\APIToolKit\Generator\SchemaParsers\MigrationContentParser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class SchemaRegressionTest extends TestCase
{
    #[Test]
    public function quotedSimpleEnumValuesSurviveActualGenerationAndMigration(): void
    {
        self::assertSame(0, Artisan::call('api:generate', ['model' => 'CommentState','schema' => "status:enum('draft',\"done\")",'--all' => true]), Artisan::output());
        $migration = require glob(database_path('migrations/*_create_comment_states_table.php'))[0];
        $migration->up();
        DB::table('comment_states')->insert(['status' => 'done']);
        self::assertSame('done', DB::table('comment_states')->value('status'));
    }

    #[Test]
    public function enumValuesThatCannotBeSafelyEmbeddedInBlueprintDdlFailBeforeWriting(): void
    {
        self::assertSame(1, Artisan::call('api:generate', ['model' => 'InvalidState','schema' => "status:enum(don't,done)",'--all' => true]));
        self::assertFileDoesNotExist(app_path('Models/InvalidState.php'));
        self::assertSame([], glob(database_path('migrations/*_create_invalid_states_table.php')));
    }
    #[Test]
    public function enumValuesDoNotLoseTrailingCharacters(): void
    {
        self::assertSame(['draft','done'], ColumnDefinition::createFromDefinitionString('status:enum(draft,done)')->getEnumValues());
    }

    #[Test]
    public function parameterizedColumnsProduceValidMigrationPhp(): void
    {
        $source = (new MigrationContentParser(SchemaDefinition::createFromSchemaString('title:string(80)|amount:decimal(10,2)')))->parse();
        self::assertStringContainsString("\$table->string('title', 80)", $source);
        self::assertStringContainsString("\$table->decimal('amount', 10, 2)", $source);
        self::assertNotEmpty(token_get_all('<?php ' . $source, TOKEN_PARSE));
    }

    #[Test]
    public function nullableForeignColumnsActuallyAcceptNullInTheGeneratedMigration(): void
    {
        self::assertSame(0, Artisan::call('api:generate', ['model' => 'TestChild','schema' => 'test_model_id:foreignId:nullable','--migration' => true]), Artisan::output());
        $migration = require glob(database_path('migrations/*_create_test_children_table.php'))[0];
        $migration->up();
        DB::table('test_children')->insert(['test_model_id' => null]);
        self::assertSame(1, DB::table('test_children')->count());
    }
}
