<?php

namespace App\Filament\Vendor\Pages;

use App\Domains\Vendor\Models\Restaurant;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterRestaurant extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Создать ресторан';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Название ресторана')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, \Filament\Forms\Set $set) {
                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->label('Уникальный идентификатор (Slug)')
                    ->required()
                    ->unique(Restaurant::class, 'slug')
                    ->maxLength(255)
                    ->readOnly()
                    ->dehydrated()
                    ->helperText('Генерируется автоматически на основе названия.'),

                FileUpload::make('image')
                    ->label('Логотип')
                    ->image()
                    ->disk('public')
                    ->directory('restaurants/images')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Краткое описание')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    protected function handleRegistration(array $data): Restaurant
    {
        $restaurant = Restaurant::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'image' => $data['image'] ?? null,
            'description' => $data['description'] ?? null,
            'owner_id' => auth()->id(),
            'is_active' => false, // Require admin approval
        ]);

        return $restaurant;
    }
}
