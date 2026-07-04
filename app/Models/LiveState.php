<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveState extends Model
{
    protected $fillable = [
        'mass_id', 'item_index', 'block_index', 'mode', 'paused',
        'preset', 'align', 'badge', 'theme_id', 'quick', 'updated_by',
    ];

    protected $casts = [
        'quick' => 'array',
        'paused' => 'boolean',
    ];

    public function mass(): BelongsTo
    {
        return $this->belongsTo(Mass::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1]);
    }

    /** Tema efektif: override live > tema misa > tema default. */
    public function effectiveTheme(): ?Theme
    {
        return $this->theme
            ?? $this->mass?->theme
            ?? Theme::query()->where('is_default', true)->first()
            ?? Theme::query()->first();
    }

    public function payload(): array
    {
        return [
            'mass_id' => $this->mass_id,
            'item' => (int) $this->item_index,
            'block' => (int) $this->block_index,
            'mode' => $this->mode,
            'paused' => (bool) $this->paused,
            'preset' => $this->preset,
            'align' => $this->align ?: 'center',
            'badge' => $this->badge ?: 'accent',
            'quick' => $this->quick,
            'theme' => $this->effectiveTheme()?->toPayload(),
            'updated_by' => $this->updated_by,
            'v' => $this->updated_at?->getTimestampMs(),
        ];
    }
}
