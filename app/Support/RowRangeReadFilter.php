<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class RowRangeReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
    ) {}

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        // Row 1 = header (always read), then only the requested range
        return $row === 1 || ($row >= $this->startRow && $row <= $this->endRow);
    }
}
