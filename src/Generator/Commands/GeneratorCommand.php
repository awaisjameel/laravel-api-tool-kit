<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;
use Essa\APIToolKit\Generator\ApiGenerationCommandInputs;
use Essa\APIToolKit\Generator\Configs\PathConfigHandler;
use Essa\APIToolKit\Generator\Contracts\GeneratorCommandInterface;
use Essa\APIToolKit\Generator\Contracts\HasDynamicContentInterface;
use Essa\APIToolKit\Generator\GeneratedFileInfo;
use Essa\APIToolKit\Generator\Helpers\StubVariablesProvider;
use Illuminate\Filesystem\Filesystem;

abstract class GeneratorCommand implements GeneratorCommandInterface
{
    protected const TAGS = [
        'soft-delete',
        'request',
        'resource',
        'filter',
        'factory',
    ];

    protected GeneratorFilesType $type;

    protected ApiGenerationCommandInputs $apiGenerationCommandInputs;

    public function __construct(protected Filesystem $filesystem)
    {
    }

    public function run(ApiGenerationCommandInputs $apiGenerationCommandInputs): void
    {
        $this->apiGenerationCommandInputs = $apiGenerationCommandInputs;

        $this->generateFiles();
    }

    abstract protected function getStubName(): string;

    protected function modelNamespace(): string
    {
        $namespace = PathConfigHandler::generateFilePathInfo($this->apiGenerationCommandInputs->getPathGroup(), 'model', $this->apiGenerationCommandInputs->getModel())->getNamespace();
        if ($namespace === null || $namespace === '') {
            throw new \InvalidArgumentException('A generated model requires a namespace.');
        }
        return $namespace;
    }

    protected function generateFiles(): void
    {
        if (! file_exists($this->generatedFileInfo()->getFolderPath())) {
            $this->createFolder();
        }

        $this->saveContentToFile();
    }

    protected function generatedFileInfo(): GeneratedFileInfo
    {
        return PathConfigHandler::generateFilePathInfo(
            pathGroupName: $this->apiGenerationCommandInputs->getPathGroup(),
            generatedFileType: $this->type->value,
            modelName: $this->apiGenerationCommandInputs->getModel()
        );
    }

    protected function createFolder(): void
    {
        $this->filesystem
            ->makeDirectory(
                path: $this->generatedFileInfo()->getFolderPath(),
                mode: 0755,
                recursive: true,
                force: true
            );
    }

    protected function saveContentToFile(): void
    {
        $this->filesystem->replace($this->generatedFileInfo()->getFullPath(), $this->parseStub($this->getStubName()));
    }

    protected function parseStub(string $type): string
    {
        $output = $this->replacePatternsInTheStub($type);

        $result = $this->removeTags($output);
        $php = $this->type === GeneratorFilesType::ROUTES ? "<?php\n" . $result : $result;
        if (!str_starts_with(ltrim($php), '<?php') || token_get_all($php, TOKEN_PARSE) === []) {
            throw new \RuntimeException('The generator stub must produce valid PHP.');
        }
        return $result;
    }

    protected function replacePatternsInTheStub(string $type): string
    {
        $replacements = StubVariablesProvider::generate(
            modelName: $this->apiGenerationCommandInputs->getModel(),
            pathGroup: $this->apiGenerationCommandInputs->getPathGroup()
        );

        if ($this instanceof HasDynamicContentInterface) {
            $replacements = array_merge($replacements, $this->getContent());
        }

        return strtr(
            $this->getStubContent($type),
            $replacements
        );
    }

    protected function removeTags(string $content): string
    {
        $processedContent = $content;

        foreach (self::TAGS as $option) {
            $processedContent = $this->extractConditionalBlock(
                $processedContent,
                $this->apiGenerationCommandInputs->isOptionSelected($option),
                $option
            );
        }

        return $processedContent;
    }

    protected function extractConditionalBlock(string $string, bool $condition, string $tag): string
    {
        $pattern = "/@if\('{$tag}'\)(.*?)@endif\('{$tag}'\)/s";

        $result = preg_replace_callback($pattern, function ($matches) use ($condition) {
            $parts = explode('@else', $matches[1], 2);
            return $condition ? trim($parts[0]) : (isset($parts[1]) ? trim($parts[1]) : '');
        }, $string);
        if ($result === null) {
            throw new \RuntimeException('Unable to process generator stub condition.');
        }
        return $result;
    }

    protected function getStubContent(string $stubName): string
    {
        $customStubPath = base_path("stubs/api-tool-kit/{$stubName}.stub");

        if (file_exists($customStubPath)) {
            return $this->filesystem->get($customStubPath);
        }

        return $this->filesystem->get(__DIR__ . "/../../Stubs/{$stubName}.stub");
    }
}
