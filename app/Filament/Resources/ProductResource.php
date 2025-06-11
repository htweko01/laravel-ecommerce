<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms\Form;
use App\Models\Department;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Enums\ProductStatusEnum;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Actions\DeleteAction;
use App\Filament\Resources\ProductResource\Pages;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\CreateVariant;
use App\Filament\Resources\ProductResource\Pages\ProductVariant;
use App\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Product Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Product Name')
                            ->placeholder('Product Name')
                            ->columnSpan('1')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->placeholder('product-name')
                            ->required()
                            ->unique(Product::class, 'slug', ignoreRecord: true),
                        Select::make('department_id')
                            ->label('Department')
                            ->placeholder('Select Department')
                            ->options(Department::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('category_id', null);
                            }),
                        Select::make('category_id')
                            ->label('Category')
                            ->placeholder('Select Category')
                            ->options(fn(callable $get) => $get('department_id') ? Category::where('department_id', $get('department_id'))->pluck('name', 'id') : collect())
                            ->disabled(fn(callable $get) => !$get('department_id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        RichEditor::make('description')
                            ->label('Description')
                            ->placeholder('Description')
                            ->columnSpan('2')
                            ->required(),
                        TextInput::make('price')
                            ->label('Price')
                            ->placeholder('Price')
                            ->type('number')
                            ->columnSpan('1')
                            ->required(),
                        TextInput::make('stock')
                            ->label('Stock')
                            ->placeholder('Stock')
                            ->type('number')
                            ->columnSpan('1')
                            ->required(),
                        Select::make('status')
                            ->options(ProductStatusEnum::labels())
                            ->default(ProductStatusEnum::Draft)
                            ->required(),
                    ]),
                Section::make('Product Images')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('images')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->collection('images')
                                    ->openable()
                                    ->required()
                                    ->appendFiles()
                                    ->preserveFileNames()
                                    ->panelLayout('grid'),

                            ]),
                    ]),
                Select::make('attributes')
                    ->label('Attributes')
                    ->relationship('attributes', 'name')
                    ->columnSpan('2')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('Image')
                    ->collection('images')
                    ->limit(1)
                    ->conversion('thumb'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->colors(ProductStatusEnum::colors()),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()->iconButton()->tooltip('View'),
                EditAction::make()->iconButton()->tooltip('Edit'),
                DeleteAction::make()->iconButton()->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditProduct::class,
            ProductVariant::class,
        ]);
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'create-variant' => Pages\ProductVariant::route('/{record}/variants'),
        ];
    }
}
