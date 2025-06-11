<?php

namespace App\Enums;

enum ProductStatusEnum: string
{
    //
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public static function getValues(): array
    {
        return [
            self::Draft,
            self::Published,
            self::Archived,
        ];
    }

    public static function labels(): array
    {
        return [
            self::Draft->value => __('Draft'),
            self::Published->value => __('Published'),
            self::Archived->value => __('Archived'),
        ];
    }

    public static function colors(): array
    {
        return [
            'gray' => self::Draft->value,
            'success' => self::Published->value,
            'danger' => self::Archived->value,
        ];
    }
}
