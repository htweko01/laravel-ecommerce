<?php

namespace App\Filament\Resources\ProductResource\Pages;

use Closure;
use Filament\Actions;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProductResource;
use App\Models\ProductVariantAttribute;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationServiceProvider;

use function PHPUnit\Framework\isEmpty;

class ProductVariant extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected static ?string $title = 'Product Variants';


    public function form(Form $form): Form
    {
        $components = [];


        foreach ($this->record->attributes as $attribute) {
            $components[] = Select::make($attribute->name)
                ->options(AttributeValue::where('attribute_id', $attribute->id)
                    ->pluck('value', 'id'))
                ->label("Product $attribute->name" . 's')
                ->multiple()
                ->required(false)
                ->dehydrated(true);
        }

        return $form->schema([
            ...$components,
            Section::make('Product Variant')
                ->columns(2)
                ->schema([
                    Select::make('action')
                        ->label('Select an Action')
                        ->options([
                            'generate' => 'Generate Variants from Attributes',
                            'delete' => 'Delete All Variants',
                        ])->suffixAction(
                            Action::make('go')
                                ->icon('heroicon-o-arrow-right')
                                ->action(function (callable $get, callable $set) {
                                    if ($get('action') === 'generate') {
                                        $this->generateVariants($get, $set);
                                    } elseif ($get('action') === 'delete') {
                                        // TODO: Implement delete logic
                                        $this->data = [
                                            'variants' => [],
                                        ];
                                    }
                                })
                        )
                        ->dehydrated(true),
                    Repeater::make('variants')
                        // ->relationship('variants')
                        ->schema([
                            ...$this->getProductAttributeComponents(),
                            TextInput::make('sku')
                                ->label('SKU')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('price')
                                ->label('Price')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(1000000)
                                ->default($this->record->price)
                                ->placeholder('0.00'),
                            TextInput::make('stock')
                                ->label('Stock')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(1000000)
                                ->placeholder('0'),
                        ])->columns(2)
                        ->columnSpan(2)
                ]),
        ]);
    }

    private function generateVariants($get, $set)
    {
        $variants = [];

        foreach ($this->record->attributes as $attribute) {
            if (empty($get($attribute->name))) {
                return;
            }
        }

        foreach ($this->record->attributes as $attribute) {
            $attributes[$attribute->name] = $get($attribute->name);
        }

        $keys = array_keys($attributes);
        $values = array_values($attributes);

        // Generate all combinations using recursive function
        $combinations = $this->cartesianProduct($values);

        // Map each combination to attribute names
        $variants = [];

        foreach ($combinations as $combo) {
            $variantAttributes = [];

            foreach ($combo as $index => $value) {
                $variantAttributes["attributes_" . $keys[$index]] = $value;
            }
            $variants[] = [
                ...$variantAttributes,
                'sku' => '',
                'price' => $this->record->price,
            ];
        }

        $this->data = [
            'variants' => $variants,
        ];
        // $set('variants', $variants);
    }

    // Recursive Cartesian product generator
    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $property) {
            $tmp = [];

            foreach ($result as $product) {
                foreach ($property as $item) {
                    $tmp[] = array_merge($product, [$item]);
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    private function getProductAttributeComponents()
    {
        $comps = [];
        foreach ($this->record->attributes as $index => $attribute) {
            $comps[] = Select::make("attributes_$attribute->name")
                ->options(AttributeValue::where('attribute_id', $attribute->id)
                    ->pluck('value', 'id'))
                ->label("$attribute->name")
                ->columnSpan(1)
                ->required();
        }
        return $comps;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->variants()->delete();
        foreach ($this->data['variants'] as $key => $variant) {
            $attributeValues = [];
            foreach ($variant as $k => $v) {
                if (Str::startsWith($k, 'attributes_')) {
                    $attributeValues[] = $v;
                    unset($this->data['variants'][$key][$k]);
                }
            }
            $variantModel = $record->variants()->create($this->data['variants'][$key]);
            $variantModel->attributeValues()->sync($attributeValues);
        }
        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // dd("mutateFormDataBeforeFill", $data);
        $data['variants'] = $this->record->variants->map(function ($variant) {
            $attributes = [];
            foreach ($variant->attributeValues as $value) {
                $attributes["attributes_" . $value->attribute->name] = $value->id;
            }
            return array_merge($attributes, [
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock' => $variant->stock,
            ]);
        })->toArray();
        return $data;
    }

    // protected function beforeValidate(): void
    // {
    //     // dd("beforeValidate", $this->data);
    //     // dd("beforeValid", $this->record->variants);
    //     foreach ($this->data['variants'] as $key => $variant) {
    //         foreach ($variant as $k => $v) {
    //             if (empty($v)) {
    //                 Notification::make()
    //                     ->title('Validation Error')
    //                     ->body("Please fill in all fileds for variant.")
    //                     ->danger()
    //                     ->send();
    //                 throw ValidationException::withMessages([
    //                     "variants.$key.$k" => "The $k field is required for variant $key.",
    //                 ]);
    //             }
    //         }
    //     }
    // }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create-variant', [
            'record' => $this->record,
        ]);
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
