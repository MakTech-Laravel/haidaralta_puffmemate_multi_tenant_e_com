<?php

namespace App\Http\Requests;

use App\Enums\ExportFormat;
use App\Enums\ExportScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportSpreadsheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', Rule::enum(ExportFormat::class)],
            'scope' => ['required', Rule::enum(ExportScope::class)],
            'filter.from' => ['nullable', 'date'],
            'filter.to' => ['nullable', 'date'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->enum('scope', ExportScope::class) !== ExportScope::Period) {
                    return;
                }

                $from = trim((string) data_get($this->input('filter'), 'from', ''));
                $to = trim((string) data_get($this->input('filter'), 'to', ''));

                if ($from === '' && $to === '') {
                    $validator->errors()->add(
                        'filter.from',
                        'Select a from or to date to export a date range.',
                    );
                }

                if ($from !== '' && $to !== '' && $to < $from) {
                    $validator->errors()->add(
                        'filter.to',
                        'The to date must be on or after the from date.',
                    );
                }
            },
        ];
    }

    public function exportFormat(): ExportFormat
    {
        return $this->enum('format', ExportFormat::class);
    }

    public function exportScope(): ExportScope
    {
        return $this->enum('scope', ExportScope::class);
    }

    public function filename(string $basename): string
    {
        return sprintf(
            '%s-%s-%s.%s',
            $basename,
            $this->exportScope()->value,
            now()->format('Y-m-d'),
            $this->exportFormat()->extension(),
        );
    }
}
