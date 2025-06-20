<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DetailLayananResource\Pages;
use App\Filament\Resources\DetailLayananResource\RelationManagers;
use App\Models\DetailLayanan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DetailLayananResource extends Resource
{
    protected static ?string $model = DetailLayanan::class;

    public static function form(Form $form): Form
    {
        // Get layanan_id from the request if available
        $layananId = request()->get('layanan_id');

        return $form
            ->schema([
                Forms\Components\TextInput::make('pekerjaan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('biaya')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\Select::make('layanan_id')
                    ->relationship('layanan', 'nama')
                    ->required()
                    ->default($layananId), // Prefill if available
                Forms\Components\Select::make('montir_id')
                    ->relationship('montir', 'nama')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pekerjaan')->label('Pekerjaan'),
                Tables\Columns\TextColumn::make('biaya')->label('Biaya')->money('IDR', true),
                Tables\Columns\TextColumn::make('layanan')
                    ->label('Layanan')
                    ->formatStateUsing(fn($record) => $record->layanan ? "{$record->layanan->kode} - {$record->layanan->nama}" : '-')
                    ->url(fn($record) => $record->layanan ? route('filament.admin.resources.layanans.edit', ['record' => $record->layanan->id]) : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('montir.nama')->label('Montir'),
            ])
            ->recordUrl(fn($record): ?string => null)
            ->filters([
                Filter::make('layanan_id')
                    ->form([
                        Forms\Components\Select::make('layanan_id')
                            ->relationship('layanan', 'kode')
                            ->searchable()
                            ->preload()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['layanan_id'],
                                fn(Builder $query, $layananId): Builder => $query->where('layanan_id', $layananId),
                            );
                    })
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListDetailLayanans::route('/'),
            // 'create' => Pages\CreateDetailLayanan::route('/create'),
            // 'edit' => Pages\EditDetailLayanan::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // ⬅️ Nonaktifkan pembuatan dari semua tempat
    }

    //* Authorization
    static function can(string $action, ?\Illuminate\Database\Eloquent\Model $record = null): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) return false;

        // Allow all
        return $user && $user->role === 'admin';
    }
}
