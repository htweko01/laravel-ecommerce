<?php

namespace App\Filament\Resources\ProductAttributeResource\Pages;

use App\Filament\Resources\ProductAttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductAttribute extends CreateRecord
{
    protected static string $resource = ProductAttributeResource::class;
    protected static ?string $title = 'Create Product Attribute Value';
}
