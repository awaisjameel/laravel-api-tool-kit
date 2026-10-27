<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator\Commands;

use Essa\APIToolKit\Enum\GeneratorFilesType;

class RoutesGeneratorCommand extends GeneratorCommand
{
    protected GeneratorFilesType $type = GeneratorFilesType::ROUTES;

    protected function getStubName(): string
    {
        return 'DummyRoutes';
    }

    protected function saveContentToFile(): void
    {
        $path = $this->generatedFileInfo()->getFullPath();
        $existing = is_file($path) ? $this->filesystem->get($path) : '';
        if (trim($existing) === '') {
            $existing = "<?php\n\ndeclare(strict_types=1);\n\nuse Illuminate\\Support\\Facades\\Route;\n";
        }
        $model = $this->apiGenerationCommandInputs->getModel();
        $start = '// <api-tool-kit:' . $model . '>';
        $end = '// </api-tool-kit:' . $model . '>';
        $block = $start . "\n" . $this->parseStub('DummyRoutes') . "\n" . $end;
        $pattern = '~' . preg_quote($start, '~') . '.*?' . preg_quote($end, '~') . '~s';
        $updated = preg_match($pattern, $existing) === 1 ? preg_replace_callback($pattern, static fn (): string => $block, $existing) : $existing . "\n" . $block . "\n";
        if ($updated === null) {
            throw new \RuntimeException('Could not update generated route block.');
        }
        if (token_get_all($updated, TOKEN_PARSE) === []) {
            throw new \RuntimeException('The generated route file is invalid PHP.');
        }
        $this->filesystem->replace($path, $updated);
    }
}
