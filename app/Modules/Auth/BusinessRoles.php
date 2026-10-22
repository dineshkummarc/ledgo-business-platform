<?php

namespace App\Modules\Auth;

use App\Models\Business;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BusinessRoles
{
    public function assignOwner(Business $business, User $user): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($business->id);
        try {
            foreach (['owner', 'manager', 'accountant', 'cashier', 'stock_keeper', 'viewer'] as $name) {
                Role::firstOrCreate(['name' => $name, 'guard_name' => 'web', 'business_id' => $business->id]);
            } $user->unsetRelation('roles');
            $user->assignRole('owner');
        } finally {
            $registrar->setPermissionsTeamId($previous);
            $user->unsetRelation('roles');
        }
    }
}
