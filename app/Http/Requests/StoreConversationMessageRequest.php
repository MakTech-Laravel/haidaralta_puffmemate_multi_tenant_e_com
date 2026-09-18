<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class StoreConversationMessageRequest extends FormRequest
{
    public const MAX_ATTACHMENTS = 5;

    public const MAX_ATTACHMENT_KB = 10240;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_ATTACHMENTS],
            'attachments.*' => [
                'file',
                'max:'.self::MAX_ATTACHMENT_KB,
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt',
            ],
        ];
    }

    /**
     * A message needs either text or at least one file.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasBody = trim((string) $this->input('body')) !== '';

            if (! $hasBody && $this->attachments() === []) {
                $validator->errors()->add('body', 'Type a message or attach a file.');
            }
        });
    }

    public function body(): ?string
    {
        $body = trim((string) $this->input('body'));

        return $body === '' ? null : $body;
    }

    /**
     * @return list<UploadedFile>
     */
    public function attachments(): array
    {
        $files = $this->file('attachments', []);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        return array_values($files);
    }
}
