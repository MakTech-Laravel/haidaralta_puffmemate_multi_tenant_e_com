import FileUpload, {
    type ExistingFile,
    type FileUploadProps,
} from '@/components/file-upload';

type FileUploadAvatarProps = Omit<
    FileUploadProps,
    'maxFiles' | 'multiple' | 'variant'
> & {
    existingUrl?: string | null;
    onClearExisting?: () => void;
};

/**
 * Thin avatar wrapper around FileUpload `variant="avatar"`.
 * Prefer using `<FileUpload variant="avatar" />` directly when possible.
 */
export function FileUploadAvatar({
    accept = 'image/png,image/jpeg,image/webp',
    maxSize = 2,
    value,
    onChange,
    existingUrl,
    onClearExisting,
    ...props
}: FileUploadAvatarProps) {
    const selected = Array.isArray(value) ? (value[0] ?? null) : (value ?? null);

    const existingFiles: ExistingFile[] =
        !selected && existingUrl
            ? [
                  {
                      id: 'avatar',
                      path: 'avatar',
                      name: 'Current avatar',
                      url: existingUrl,
                      mime_type: 'image/*',
                  },
              ]
            : [];

    return (
        <FileUpload
            {...props}
            variant="avatar"
            accept={accept}
            maxSize={maxSize}
            maxFiles={1}
            multiple={false}
            value={value}
            onChange={onChange}
            existingFiles={existingFiles}
            onRemoveExisting={
                onClearExisting ? () => onClearExisting() : undefined
            }
        />
    );
}
