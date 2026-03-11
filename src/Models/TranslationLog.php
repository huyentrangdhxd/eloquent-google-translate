<?php

namespace TracyTran\EloquentTranslate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TranslationLog extends Model
{
    const PENDING = 'pending';

    const PROCESSING = 'processing';

    const COMPLETED = 'completed';

    const FAILED = 'failed';

    protected $guarded = [
        'id',
        '_token',
        '_method',
    ];

    protected $casts = [
        'target_locales' => 'array',
        'fields' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getTable()
    {
        return config('eloquent-translate.database_table_log');
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $result): void
    {
        $this->update([
            'status' => self::COMPLETED,
            'result' => $result,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

}
