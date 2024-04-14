<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StackResource\Pages;
use App\Filament\Resources\StackResource\RelationManagers;
use App\Models\Stack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StackResource extends Resource
{
    protected static ?string $model = Stack::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('env')
                    ->required(),
                Forms\Components\TextInput::make('env_key')
                    ->required(),
                Forms\Components\TextInput::make('bucket')
                    ->required(),
                Forms\Components\TextInput::make('region')
                    ->required(),
                Forms\Components\TextInput::make('account')
                    ->required(),
                Forms\Components\TextInput::make('function_name_artisan')
                    ->required(),
                Forms\Components\TextInput::make('function_name_web')
                    ->required(),
                Forms\Components\TextInput::make('function_name_worker')
                    ->required(),
                Forms\Components\TextInput::make('distribution_url')
                    ->required(),
                Forms\Components\TextInput::make('queue_name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('env')
                    ->searchable(),
                Tables\Columns\TextColumn::make('env_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bucket')
                    ->searchable(),
                Tables\Columns\TextColumn::make('region')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('function_name_artisan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('function_name_web')
                    ->searchable(),
                Tables\Columns\TextColumn::make('function_name_worker')
                    ->searchable(),
                Tables\Columns\TextColumn::make('distribution_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('queue_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStacks::route('/'),
            'create' => Pages\CreateStack::route('/create'),
            'edit' => Pages\EditStack::route('/{record}/edit'),
        ];
    }
}
