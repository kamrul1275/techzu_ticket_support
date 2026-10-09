<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketImport extends Model
{
    protected $fillable = [
        'uploaded_by',
        'original_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'success_rows',
        'failed_rows',
        'error_file_path',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}