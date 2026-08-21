<?php

namespace App\Filament\Resources;

use App\Enums\GreenScoreCheckType;
use App\Filament\Resources\GreenScoreRuleResource\Pages;
use App\Models\GreenScoreRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FR-Phase2 §11/12 — the Green Score is only trustworthy if it is
 * transparent, so this screen is where every point on the platform is
 * defined. Sellers never touch this: points and active/inactive are
 * admin-only, and every change is audited (see Pages\EditGreenScoreRule).
 */
class GreenScoreRuleResource extends Resource
{
    protected static ?string $model = GreenScoreRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Trust & Safety';

    protected static ?string $navigationLabel = 'Green Score Rules';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rule')->schema([
                Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)->disabled(fn (?GreenScoreRule $record) => $record !== null),
                Forms\Components\KeyValue::make('name')->label('Name by locale (ar / fr / en)')->columnSpanFull(),
                Forms\Components\KeyValue::make('description')->label('Description by locale')->columnSpanFull(),
                Forms\Components\TextInput::make('icon')->label('Icon (emoji)')->maxLength(8),
                Forms\Components\Select::make('category')
                    ->options([
                        'origin' => 'Origin',
                        'certification' => 'Certification',
                        'packaging' => 'Packaging',
                        'producer' => 'Producer',
                        'craftsmanship' => 'Craftsmanship',
                    ])
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('What this checks')
                ->description('Fixed, code-defined checks against real data — never a free-text claim.')
                ->schema([
                    Forms\Components\Select::make('check_type')
                        ->options(collect(GreenScoreCheckType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all())
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('check_value')
                        ->label('Check value (e.g. a green attribute or badge code)')
                        ->visible(fn (Forms\Get $get) => GreenScoreCheckType::tryFrom((string) $get('check_type'))?->needsValue() ?? false),
                ])->columns(2),

            Forms\Components\Section::make('Weight')->schema([
                Forms\Components\TextInput::make('points')->numeric()->required()->minValue(0)->maxValue(100),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\TextInput::make('display_order')->numeric()->default(0),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->getStateUsing(fn (GreenScoreRule $r) => $r->name()),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('check_type')->label('Checks')->getStateUsing(fn (GreenScoreRule $r) => $r->check_type->label().($r->check_value ? " ({$r->check_value})" : '')),
                Tables\Columns\TextColumn::make('points')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('display_order')
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGreenScoreRules::route('/'),
            'create' => Pages\CreateGreenScoreRule::route('/create'),
            'edit' => Pages\EditGreenScoreRule::route('/{record}/edit'),
        ];
    }
}
