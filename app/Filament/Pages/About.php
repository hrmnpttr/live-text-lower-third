<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class About extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationLabel = 'Tentang';

    protected static ?string $title = 'Tentang Liturgia Live';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.about';

    public const VERSION = '1.0.0';
}
