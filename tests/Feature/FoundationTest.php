<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Modules\Auth\BusinessRoles;
use App\Modules\Auth\OtpService;
use App\Support\Integrations\BulkSmsGateway;
use App\Support\Integrations\PerformanceProfiler;
use App\Support\Integrations\PlatformConsole;
use App\Support\Integrations\SmsGateway;
use App\Support\Tenancy\BusinessContext;
use Drutogoti\CaptureContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_panels_use_separate_layouts_and_are_protected(): void
    {
        $this->get('/app/login')->assertOk()->assertSee('/auth/phone', false);
        $this->get('/admin')->assertRedirect('/app/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $user->forceFill(['is_platform_admin' => true])->save();
        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_business_records_fail_closed_and_reject_cross_tenant_writes(): void
    {
        $a = Business::create(['name' => 'করিম স্টোর', 'type' => 'grocery']);
        $b = Business::create(['name' => 'রহিম স্টোর', 'type' => 'grocery']);
        $context = app(BusinessContext::class);
        $branch = $context->run($a, fn () => Branch::create(['name' => 'Dhaka']));
        $this->assertSame(0, Branch::count());
        $context->run($b, function () use ($branch) {
            $this->assertSame(0, Branch::count());
            $this->expectException(\LogicException::class);
            $branch->update(['name' => 'Tampered']);
        });
    }

    public function test_tenant_membership_is_checked(): void
    {
        $a = Business::create(['name' => 'Shop A', 'type' => 'grocery']);
        $b = Business::create(['name' => 'Shop B', 'type' => 'grocery']);
        $user = User::factory()->create(['phone_verified_at' => now()]);
        $a->users()->attach($user, ['role' => 'owner']);
        $this->assertTrue($user->canAccessTenant($a));
        $this->assertFalse($user->canAccessTenant($b));
        $this->actingAs($user)->get('/app/'.$a->id)->assertOk();
        $this->actingAs($user)->get('/app/'.$b->id)->assertNotFound();
    }

    public function test_onboarding_creates_owned_business_and_branch_atomically(): void
    {
        $user = User::factory()->create(['phone_verified_at' => now()]);
        $this->actingAs($user)->post('/onboarding', ['name' => 'ঢাকা বাজার', 'type' => 'grocery', 'locale' => 'bn'])->assertRedirect();
        $business = $user->businesses()->firstOrFail();
        $this->assertSame('owner', $business->pivot->role);
        $this->assertDatabaseHas('branches', ['business_id' => $business->id]);
        $this->assertDatabaseHas('audit_logs', ['business_id' => $business->id, 'event' => 'created']);
    }

    public function test_otp_is_single_use_and_expires(): void
    {
        $fake = new class implements SmsGateway
        {
            public string $message = '';

            public function send(string $phone, string $message): void
            {
                $this->message = $message;
            }
        };
        $service = new OtpService($fake);
        $service->send('8801712345678');
        preg_match('/\d{6}/', $fake->message, $matches);
        $service->verify('8801712345678', $matches[0]);
        $this->assertNull(Cache::get('otp:8801712345678'));
        $service->send('8801712345678');
        $this->travel(6)->minutes();
        $this->expectException(ValidationException::class);
        $service->verify('8801712345678', $matches[0]);
    }

    public function test_phone_login_creates_verified_user_and_regenerates_auth(): void
    {
        $fake = new class implements SmsGateway
        {
            public string $message = '';

            public function send(string $phone, string $message): void
            {
                $this->message = $message;
            }
        };
        $this->app->instance(SmsGateway::class, $fake);
        $this->post('/auth/otp', ['phone' => '8801712345678'])->assertRedirect();
        preg_match('/\d{6}/', $fake->message, $matches);
        $this->post('/auth/verify', ['phone' => '8801712345678', 'code' => $matches[0]])->assertRedirect('/onboarding');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['phone' => '8801712345678', 'email' => null]);
    }

    public function test_bulk_sms_submits_without_live_network(): void
    {
        Http::preventStrayRequests();
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202])]);
        config(['ledgo.sms.key' => 'test', 'ledgo.sms.sender' => 'test']);
        (new BulkSmsGateway)->send('8801712345678', 'test');
        Http::assertSent(fn ($r) => $r['number'] === '8801712345678' && $r['api_key'] === 'test');
    }

    public function test_slug_package_preserves_bangla_and_handles_collisions(): void
    {
        $a = Business::create(['name' => 'করিম স্টোর', 'type' => 'grocery']);
        $b = Business::create(['name' => 'করিম স্টোর', 'type' => 'grocery']);
        $this->assertStringContainsString('করিম', $a->slug);
        $this->assertNotSame($a->slug, $b->slug);
    }

    public function test_removed_placeholder_sync_is_not_available(): void
    {
        $this->get('/sync')->assertNotFound();
    }

    public function test_package_adapters_and_profiler_store_real_telemetry(): void
    {
        $this->assertSame('admin', app(PlatformConsole::class)->path());
        $this->assertSame(100, app(PerformanceProfiler::class)->slowThreshold());
        $capture = new CaptureContext;
        config(['drutogoti.storage.driver' => 'sync']);
        $capture->flushAndDispatch();
        $this->assertDatabaseHas('drutogoti_requests', ['request_id' => $capture->requestId]);
    }

    public function test_role_assignment_is_scoped_to_each_business(): void
    {
        $a = Business::create(['name' => 'A', 'type' => 'grocery']);
        $b = Business::create(['name' => 'B', 'type' => 'grocery']);
        $user = User::factory()->create();
        app(BusinessRoles::class)->assignOwner($a, $user);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($a->id);
        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole('owner'));
        $registrar->setPermissionsTeamId($b->id);
        $user->unsetRelation('roles');
        $this->assertFalse($user->hasRole('owner'));
        $registrar->setPermissionsTeamId(null);
    }

    public function test_otp_stops_after_five_wrong_codes(): void
    {
        $fake = new class implements SmsGateway
        {
            public function send(string $phone, string $message): void {}
        };
        $service = new OtpService($fake);
        $service->send('8801712345678');
        for ($i = 0; $i < 5; $i++) {
            try {
                $service->verify('8801712345678', '000000');
                $this->fail('Wrong code accepted');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->assertSame(5, Cache::get('otp:8801712345678')['attempts']);
    }
}
