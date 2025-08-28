<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTypeResource\Pages;
use App\Models\DocumentType;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationGroup = 'Document Management';

    public static function form(Form $form): Form
    {
        $roles = Role::all()->pluck('name')->toArray();
        $actions = DocumentType::availableActions();

        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                    ]),

                Section::make('Document Actions & Permissions')
                    ->schema([
                        CheckboxList::make('allowed_actions')
                            ->options($actions)
                            ->columns(2)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Reset role permissions when actions change
                                $set('role_permissions', []);
                            }),

                        Section::make('Role Permissions Matrix')
                            ->schema([
                                Grid::make()
                                    ->schema(array_map(function ($role) use ($actions) {
                                        return CheckboxList::make("role_permissions.{$role}")
                                            ->label(ucfirst($role))
                                            ->options(function (Forms\Get $get) use ($actions) {
                                                // Only show actions that are enabled
                                                $allowedActions = $get('allowed_actions') ?? [];
                                                return array_intersect_key($actions, array_flip($allowedActions));
                                            })
                                            ->columns(2);
                                    }, $roles))
                                    ->columns(2),
                            ])
                            ->collapsible(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                
                Tables\Columns\TagsColumn::make('allowed_actions')
                    ->separator(',')
                    ->badge(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Document Types')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggleActive')
                        ->label('Toggle Active Status')
                        ->icon('heroicon-o-power')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(function ($record) {
                                $record->update(['is_active' => !$record->is_active]);
                            });
                        }),
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
            'index' => Pages\ListDocumentTypes::route('/'),
            'create' => Pages\CreateDocumentType::route('/create'),
            'edit' => Pages\EditDocumentType::route('/{record}/edit'),
        ];
    }
}
