<?php

namespace App\Filament\Resources;

use App\Enums\ListingType;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FR-112 — category configuration without a deploy.
 *
 * This screen is the whole architectural bet made operable: requirements,
 * fields, rules and price caps are all edited here. Onboarding a new
 * regulated category on a Friday afternoon should never need a developer.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Category')->schema([
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'slug')
                    ->searchable()
                    ->helperText('Requirements and rules cascade down to children.'),
                Forms\Components\KeyValue::make('name')
                    ->label('Name by locale (ar / fr / en)')
                    ->columnSpanFull(),
                Forms\Components\Select::make('listing_type')
                    ->options(collect(ListingType::cases())
                        ->mapWithKeys(fn ($t) => [$t->value => ucfirst($t->value)])->all())
                    ->required(),
                Forms\Components\TextInput::make('path')
                    ->required()
                    ->helperText('Materialised path, e.g. intrants/semences/cereales'),
                Forms\Components\Toggle::make('is_leaf')->default(true),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\TextInput::make('display_order')->numeric()->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Required credentials')
                ->description('A seller who cannot prove these may not list here.')
                ->schema([
                    Forms\Components\Repeater::make('requirements')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('credential_type_code')
                                ->relationship('credentialType', 'code')
                                ->required(),
                            Forms\Components\Toggle::make('is_mandatory')->default(true),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state) => $state['credential_type_code'] ?? null),
                ]),

            Forms\Components\Section::make('Listing fields')
                ->description('Rendered dynamically on the listing form and validated on save.')
                ->schema([
                    Forms\Components\Repeater::make('fields')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('key')->required(),
                            Forms\Components\KeyValue::make('label')->label('Label by locale'),
                            Forms\Components\Select::make('data_type')
                                ->options([
                                    'string' => 'Text',
                                    'text' => 'Long text',
                                    'number' => 'Number',
                                    'integer' => 'Integer',
                                    'boolean' => 'Yes / no',
                                    'date' => 'Date',
                                    'select' => 'Select',
                                    'multiselect' => 'Multi-select',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('unit'),
                            Forms\Components\Toggle::make('required'),
                            Forms\Components\Toggle::make('filterable'),
                            Forms\Components\KeyValue::make('options')->label('Options (select only)'),
                            Forms\Components\TextInput::make('display_order')->numeric()->default(0),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state) => $state['key'] ?? null),
                ])->collapsed(),

            Forms\Components\Section::make('Handling rules')->schema([
                Forms\Components\Group::make()->relationship('rule')->schema([
                    Forms\Components\Toggle::make('is_fragile'),
                    Forms\Components\Toggle::make('is_perishable'),
                    Forms\Components\Toggle::make('is_live')->label('Live plants'),
                    Forms\Components\Toggle::make('is_heavy'),
                    Forms\Components\Toggle::make('needs_cold_chain'),
                    Forms\Components\Toggle::make('allows_group_booking'),
                    Forms\Components\Toggle::make('requires_lot_number')
                        ->label('Requires lot number')
                        ->helperText('Switch this on for anything edible. It is what makes a recall targeted.'),
                    Forms\Components\TextInput::make('max_delivery_days')
                        ->numeric()
                        ->helperText('Perishables are hidden from zones slower than this.'),
                    Forms\Components\TextInput::make('min_lead_time_days')->numeric()->default(0),
                ])->columns(3),
            ])->collapsed(),

            Forms\Components\Section::make('Price ceilings')
                ->description('State-fixed prices. Listings above the ceiling are rejected at save.')
                ->schema([
                    Forms\Components\Repeater::make('priceCaps')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('season_label')->required(),
                            Forms\Components\TextInput::make('unit')->required(),
                            Forms\Components\TextInput::make('max_price')
                                ->numeric()
                                ->required()
                                ->helperText('Millimes. 170 TND/quintal = 170000'),
                            Forms\Components\TextInput::make('contract_surcharge_pct')->numeric()->default(0),
                            Forms\Components\DatePicker::make('effective_from')->required(),
                            Forms\Components\DatePicker::make('effective_to'),
                            Forms\Components\TextInput::make('source_ref')
                                ->label('Source')
                                ->helperText('Link to the ministry publication — shown publicly.'),
                        ])
                        ->columns(2),
                ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('path')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (Category $r) => $r->name()),
                Tables\Columns\TextColumn::make('listing_type')->badge(),
                Tables\Columns\TextColumn::make('requirements_count')
                    ->counts('requirements')
                    ->label('Credentials'),
                Tables\Columns\TextColumn::make('fields_count')->counts('fields')->label('Fields'),
                Tables\Columns\TextColumn::make('listings_count')->counts('listings')->label('Listings'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('path')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
