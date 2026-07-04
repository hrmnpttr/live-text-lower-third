<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'name', 'color_key', 'accent', 'bg_tint', 'accent_style',
        'watermark', 'logo_path', 'background_path', 'watermark_path', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color_key' => $this->color_key,
            'accent' => $this->accent,
            'bg_tint' => $this->bg_tint,
            'accent_style' => $this->accent_style,
            'logo' => $this->logo_path ? asset('storage/'.$this->logo_path) : null,
            'background' => $this->background_path ? asset('storage/'.$this->background_path) : null,
            'watermark_image' => $this->watermark_path ? asset('storage/'.$this->watermark_path) : null,
        ];
    }
}
