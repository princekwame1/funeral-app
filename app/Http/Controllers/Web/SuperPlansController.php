<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Support\Plans;
use Illuminate\Http\Request;

class SuperPlansController extends Controller
{
    public function index()
    {
        $plans = Plan::query()->orderBy('sort_order')->orderBy('id')->get();

        $tenantCounts = Tenant::query()
            ->selectRaw('plan, COUNT(*) as c')
            ->groupBy('plan')
            ->pluck('c', 'plan');

        return view('super.plans.index', compact('plans', 'tenantCounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        Plan::create($data);
        Plans::clearCache();

        return redirect()->route('super.plans.index')
            ->with('super_flash', ['ok' => true, 'message' => "Plan \"{$data['name']}\" created."]);
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validated($request, $plan);
        $plan->update($data);
        Plans::clearCache();

        return redirect()->route('super.plans.index')
            ->with('super_flash', ['ok' => true, 'message' => "Plan \"{$plan->name}\" saved."]);
    }

    public function destroy(Plan $plan)
    {
        $inUse = Tenant::where('plan', $plan->slug)->count();
        if ($inUse > 0) {
            return back()->with('super_flash', [
                'ok' => false,
                'message' => "Cannot delete \"{$plan->name}\" — {$inUse} tenant(s) are on it. Move them to another plan first.",
            ]);
        }
        $name = $plan->name;
        $plan->delete();
        Plans::clearCache();

        return redirect()->route('super.plans.index')
            ->with('super_flash', ['ok' => true, 'message' => "Plan \"{$name}\" deleted."]);
    }

    private function validated(Request $request, ?Plan $plan): array
    {
        $slugRule = 'unique:plans,slug' . ($plan ? ',' . $plan->id : '');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/', $slugRule],
            'tagline' => ['nullable', 'string', 'max:200'],
            'sms_monthly' => ['nullable', 'integer', 'min:0'],
            'donations_total' => ['nullable', 'integer', 'min:0'],
            'price_ghs' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Empty-string checkbox → false; empty number field for unlimited → null (preserved).
        $data['is_active'] = $request->boolean('is_active');
        $data['sms_monthly'] = $request->input('sms_monthly') === '' ? null : $data['sms_monthly'] ?? null;
        $data['donations_total'] = $request->input('donations_total') === '' ? null : $data['donations_total'] ?? null;
        $data['price_ghs'] = $data['price_ghs'] ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
