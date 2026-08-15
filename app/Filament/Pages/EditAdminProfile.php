<?php

namespace App\Filament\Pages;

use App\Support\PasswordGenerator;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class EditAdminProfile extends EditProfile
{
    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string|Htmlable
    {
        return __('admin.header.my_profile');
    }

    public static function getLabel(): string
    {
        return __('admin.header.my_profile');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->helperText(__('admin.fields.password_hint'))
            ->suffixAction(
                Action::make('generatePassword')
                    ->label(__('admin.actions.generate_password'))
                    ->icon(Heroicon::ArrowPath)
                    ->tooltip(__('admin.actions.generate_password'))
                    ->action(function (Set $set): void {
                        $password = PasswordGenerator::generate();

                        $set('password', $password);
                        $set('passwordConfirmation', $password);
                    }),
            );
    }
}
