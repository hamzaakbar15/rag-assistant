<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label('Document')
                    ->disk('local')
                    ->directory('documents')
                    ->acceptedFileTypes(['application/pdf', 'text/plain'])
                    ->required(),
                TextInput::make('title')
                    ->required(),
            ]);
    }
}
