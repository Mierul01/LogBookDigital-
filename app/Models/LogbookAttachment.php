<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A file a student attached to a logbook entry as evidence. */
class LogbookAttachment extends Model
{
    public const MAX_FILES = 5;

    /** Kilobytes, for the validator. */
    public const MAX_KB = 5120;

    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'];

    protected $fillable = ['logbook_id', 'original_name', 'path', 'mime', 'size'];

    protected static function booted(): void
    {
        // Remove the file from disk with its record.
        static::deleted(fn (self $attachment) => Storage::disk('local')->delete($attachment->path));
    }

    public function logbook(): BelongsTo
    {
        return $this->belongsTo(Logbook::class);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1).' MB'
            : max(1, round($this->size / 1024)).' KB';
    }
}
