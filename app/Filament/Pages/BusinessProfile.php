<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BusinessProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return __('ledgo.business');
    }

    public static function canView(Model $tenant): bool
    {
        return auth()->user()?->businesses()->whereKey($tenant->id)->wherePivot('role', 'owner')->exists() ?? false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([TextInput::make('name')->label(__('ledgo.name'))->required()->maxLength(150), Select::make('locale')->label(__('ledgo.locale'))->options(['bn' => 'বাংলা', 'en' => 'English'])->required()]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless(static::canView($record), 403);

        return DB::transaction(function () use ($record, $data) {
            $old = $record->only(['name', 'locale']);
            $record->update($data);
            AuditLog::create(['user_id' => auth()->id(), 'event' => 'updated', 'subject_type' => $record::class, 'subject_id' => $record->id, 'old_values' => $old, 'new_values' => $record->only(['name', 'locale']), 'ip' => request()->ip(), 'device' => request()->userAgent()]);

            return $record;
        });
    }
}
