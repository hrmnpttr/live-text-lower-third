<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mass extends Model
{
    protected $fillable = ['title', 'celebrated_at', 'priest', 'theme_id', 'is_template', 'notes'];

    protected $casts = [
        'celebrated_at' => 'datetime',
        'is_template' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MassItem::class)->orderBy('sort');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /** Rundown terkompilasi yang dikonsumsi halaman output & kontrol. */
    public function toRundownArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'priest' => $this->priest,
            'celebrated_at' => $this->celebrated_at?->format('Y-m-d H:i'),
            'theme' => $this->theme?->toPayload(),
            'items' => $this->items->map(fn (MassItem $item) => $item->toCompiledArray())->values()->all(),
        ];
    }
}
