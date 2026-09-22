import { useState } from 'react';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

const IMAGE_ACCEPT =
    'image/png,image/jpeg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg';

type CmsImageFieldProps = {
    label: string;
    hint?: string;
    path: string | null | undefined;
    url: string | null | undefined;
    value: File | null;
    onChange: (file: File | null) => void;
    onRemove: () => void;
    error?: string;
};

function toExisting(
    path: string | null | undefined,
    url: string | null | undefined,
): ExistingFile[] {
    if (!path || !url) {
        return [];
    }

    return [
        {
            id: path,
            path,
            url,
            mime_type: 'image/png',
            name: path.split('/').pop() ?? 'image',
        },
    ];
}

export function CmsImageField({
    label,
    hint = 'PNG, JPG, WEBP up to 5MB',
    path,
    url,
    value,
    onChange,
    onRemove,
    error,
}: CmsImageFieldProps) {
    const [existing, setExisting] = useState<ExistingFile[]>(() =>
        toExisting(path, url),
    );

    return (
        <div className="flex flex-col gap-1.5">
            <Label>{label}</Label>
            <FileUpload
                value={value}
                onChange={(files) => {
                    const file = Array.isArray(files) ? (files[0] ?? null) : files;
                    onChange(file);
                    if (file) {
                        setExisting([]);
                    }
                }}
                existingFiles={existing}
                onRemoveExisting={() => {
                    setExisting([]);
                    onRemove();
                }}
                accept={IMAGE_ACCEPT}
                maxSize={5 * 1024 * 1024}
                maxFiles={1}
                multiple={false}
                lightbox
                compactCards
                placeholder="Drop or click to upload"
                hint={hint}
                error={error}
                classNames={{
                    wrapper: 'flex min-h-36 w-full flex-col',
                    dropzone:
                        'flex min-h-36 w-full flex-1 border-dashed border-border/60 bg-muted/30 px-3 py-4',
                }}
            />
            <InputError message={error} />
        </div>
    );
}
