<?php

namespace App\DataTables;

use App\Enums\ContactInquiryStatus;
use App\Enums\ContactInquiryType;
use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<ContactInquiry>
 */
class ContactInquiryDataTable extends AbstractDataTable
{
    /**
     * @return class-string<ContactInquiry>
     */
    protected function modelClass(): string
    {
        return ContactInquiry::class;
    }

    /**
     * @return Builder<ContactInquiry>
     */
    protected function baseQuery(): Builder
    {
        return ContactInquiry::query();
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::exact('type'),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['full_name', 'email', 'phone'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return ['full_name', 'type', 'status', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'status' => ContactInquiryStatus::options(),
            'type' => ContactInquiryType::options(),
        ];
    }

    /**
     * @param  ContactInquiry  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var ContactInquiry $model */
        return [
            'id' => $model->id,
            'full_name' => $model->full_name,
            'phone' => $model->phone,
            'email' => $model->email,
            'type' => $model->type->value,
            'type_label' => $model->type->label(),
            'status' => $model->status->value,
            'status_label' => $model->status->label(),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
