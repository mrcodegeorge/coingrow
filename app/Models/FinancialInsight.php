<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialInsight extends Model
{
    use HasFactory;

    public const TYPE_SAVINGS = 'savings';
    public const TYPE_SPENDING = 'spending';
    public const TYPE_RUNWAY = 'runway';
    public const TYPE_SPLIT = 'split';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'value',
        'meta',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'meta' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
