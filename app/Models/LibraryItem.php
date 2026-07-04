<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryItem extends Model
{
    protected $fillable = ['type', 'code', 'title', 'set_name', 'sections', 'tags'];

    protected $casts = [
        'sections' => 'array',
        'tags' => 'array',
    ];

    public const TYPES = [
        'lagu' => 'Lagu',
        'doa' => 'Doa',
        'mazmur' => 'Mazmur',
        'bacaan' => 'Bacaan',
        'ordinarium' => 'Ordinarium',
        'pengumuman' => 'Pengumuman',
        'lainnya' => 'Lainnya',
    ];

    public function displayTitle(): string
    {
        return trim(($this->code ? $this->code.' — ' : '').$this->title);
    }
}
