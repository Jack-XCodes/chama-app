<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\DocumentType;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Document Management';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        
        return $form
            ->schema([
                Section::make('Document Information')
                    ->schema([
                        Select::make('document_type_id')
                            ->label('Document Type')
                            ->options(function () use ($user) {
                                // Only show document types the user can upload to
                                return DocumentType::all()
                                    ->filter(function ($type) use ($user) {
                                        return $type->canUserUpload($user);
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        FileUpload::make('file_path')
                            ->label('Document File')
                            ->required()
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'text/plain',
                            ])
                            ->maxSize(10240) // 10MB
                            ->directory('documents')
                            ->visibility('private')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;
                                
                                $file = Storage::disk('public')->get($state);
                                $set('file_name', $state);
                                $set('mime_type', Storage::disk('public')->mimeType($state));
                                $set('file_size', Storage::disk('public')->size($state));
                            })
                            ->columnSpanFull(),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->default('published')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('human_file_size')
                    ->label('Size')
                    ->sortable(
                        query: fn (Builder $query, string $direction): Builder => $query->orderBy('file_size', $direction)
                    ),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Upload Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'danger' => 'archived',
                    ]),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->relationship('documentType', 'name'),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Document $record): string => $record->getSecureUrl())
                        ->openUrlInNewTab()
                        ->visible(fn (Document $record): bool => $record->isPreviewable()),

                    Tables\Actions\Action::make('download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (Document $record): string => $record->getSecureUrl())
                        ->visible(fn (Document $record): bool => Auth::user()->can('download', $record)),

                    Tables\Actions\EditAction::make()
                        ->visible(fn (Document $record): bool => Auth::user()->can('update', $record)),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (Document $record): bool => Auth::user()->can('delete', $record)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->can('delete', Document::class)),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
