<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Generator;

class TableDate
{
    /**
     * @param list<string> $headers
     * @param list<list<string>> $tableData
     */
    public function __construct(private array $headers, private array $tableData)
    {
    }

    /**
     * @return list<string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return list<list<string>>
     */
    public function getTableData(): array
    {
        return $this->tableData;
    }
}
