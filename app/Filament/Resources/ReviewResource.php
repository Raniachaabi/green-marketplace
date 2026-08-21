<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Listing;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Moderation only — reviews are written by buyers, never created here. */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Trust & Safety';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('rating')->disabled(),
            Forms\Components\Textarea::make('body')->disabled()->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('author'))
            ->columns([
                Tables\Columns\TextColumn::make('author.full_name')->label('Author')->searchable(),
                Tables\Columns\TextColumn::make('target')
                    ->label('Listing')
                    ->getStateUsing(fn (Review $r) => $r->target_type === 'listing'
                        ? (Listing::find($r->target_id)?->name() ?? '—')
                        : $r->target_type),
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                Tables\Columns\TextColumn::make('body')->limit(60)->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5']),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Removes this review from the listing permanently.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
        ];
    }
}
