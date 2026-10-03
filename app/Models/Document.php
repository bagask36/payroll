<?php

namespace App\Models;

use App\Services\Document\DocumentService;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'documentable_type',
        'documentable_id',
        'document_type',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size',
        'storage_provider',
        'drive_file_id',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Document $doc) {
            if (empty($doc->uuid)) {
                $doc->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Polymorphic owner (Employee, Doctor, etc.).
     *
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * User who uploaded this compliance document.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Generate a signed temporary download URL (default valid for 15 minutes).
     */
    public function getTemporaryDownloadUrl(int $minutes = 15): string
    {
        $expiration = Carbon::now()->addMinutes($minutes);

        return app(DocumentService::class)->getPreviewUrl($this->storage_path, $expiration);
    }

    /**
     * Check if a given user is the document owner.
     */
    public function isOwner(User $user): bool
    {
        if ($this->uploaded_by === $user->id) {
            return true;
        }

        $owner = $this->documentable;
        if ($owner && isset($owner->user_id) && $owner->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
