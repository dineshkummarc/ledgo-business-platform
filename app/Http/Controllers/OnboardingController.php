<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Modules\Auth\BusinessRoles;
use App\Support\Tenancy\BusinessContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function show()
    {
        return view('onboarding');
    }

    public function store(Request $request, BusinessContext $context)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'type' => ['required', Rule::in(['grocery', 'pharmacy', 'garment', 'restaurant', 'service'])], 'locale' => ['required', Rule::in(['bn', 'en'])]]);
        abort_unless($request->user()->phone_verified_at, 403);
        $business = DB::transaction(function () use ($data, $request, $context) {
            $business = Business::create($data + ['country' => 'BD', 'currency' => 'BDT']);
            $business->users()->attach($request->user(), ['role' => 'owner']);
            app(BusinessRoles::class)->assignOwner($business, $request->user());
            $context->run($business, function () use ($request, $business) {
                Branch::create(['name' => __('ledgo.main_branch')]);
                AuditLog::create(['user_id' => $request->user()->id, 'event' => 'created', 'subject_type' => Business::class, 'subject_id' => $business->id, 'new_values' => $business->toArray(), 'ip' => $request->ip(), 'device' => $request->userAgent()]);
            });

            return $business;
        });

        return redirect('/app/'.$business->id);
    }
}
