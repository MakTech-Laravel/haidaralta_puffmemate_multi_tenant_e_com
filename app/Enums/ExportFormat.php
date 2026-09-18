<?php

namespace App\Enums;

use Maatwebsite\Excel\Excel;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public function writerType(): string
    {
        return match ($this) {
            self::Csv => Excel::CSV,
            self::Xlsx => Excel::XLSX,
        };
    }

    public function extension(): string
    {
        return $this->value;
    }
}
