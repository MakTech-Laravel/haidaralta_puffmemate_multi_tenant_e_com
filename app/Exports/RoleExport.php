<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RoleExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, array{id: int, name: string, permissions_count: int, users_count: int, is_super_admin: bool}>  $rows
     */
    public function __construct(
        private Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn (array $row): array => [
            $row['id'],
            $row['name'],
            $row['permissions_count'],
            $row['users_count'],
            $row['is_super_admin'] ? 'yes' : 'no',
        ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['id', 'name', 'permissions_count', 'users_count', 'is_system'];
    }
}
