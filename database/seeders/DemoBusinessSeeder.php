<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Modules\Auth\BusinessRoles;
use App\Support\Tenancy\BusinessContext;
use Illuminate\Database\Seeder;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data is restricted to local/testing.');
        }
        foreach (['grocery' => 'করিম মুদি দোকান', 'pharmacy' => 'সুস্থতা ফার্মেসি', 'service' => 'ঢাকা সার্ভিস সেন্টার'] as $type => $name) {
            $user = User::firstOrCreate(['email' => $type.'@demo.ledgo.test'], ['name' => $name, 'password' => bin2hex(random_bytes(24))]);
            $user->forceFill(['phone_verified_at' => now()])->save();
            if ($user->businesses()->exists()) {
                continue;
            }
            $business = Business::create(['name' => $name, 'type' => $type, 'locale' => 'bn']);
            $business->users()->attach($user, ['role' => 'owner']);
            app(BusinessRoles::class)->assignOwner($business, $user);
            app(BusinessContext::class)->run($business, fn () => Branch::create(['name' => 'প্রধান শাখা']));
        }
    }
}
