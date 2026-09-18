<?php

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case File = 'file';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Image => 'Image',
            self::File => 'File',
            self::System => 'System',
        };
    }

    /**
     * System rows are written by the application, never by a participant.
     */
    public function isSystem(): bool
    {
        return $this === self::System;
    }

    /**
     * Resolve the attachment-backed message type for an uploaded mime type.
     */
    public static function fromMimeType(string $mime): self
    {
        return str_starts_with($mime, 'image/') ? self::Image : self::File;
    }
}
