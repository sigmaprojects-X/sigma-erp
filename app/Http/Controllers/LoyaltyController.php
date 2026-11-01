<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPointMutation;
use App\Services\LoyaltyPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyPointService $loyaltyService
    ) {}

    /**
     * Dashboard Poin & Program Loyalitas Pelanggan.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $program = $this->loyaltyService->getActiveProgram($company);

        $query = Customer::where('company_id', $company->id)
            ->with(['tier'])
            ->orderBy('loyalty_points', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        $recentMutations = CustomerPointMutation::where('company_id', $company->id)
            ->with(['customer', 'transaction'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'program' => $program,
                'customers' => $customers,
            ]);
        }

        return view('customers.loyalty.index', compact('program', 'customers', 'recentMutations', 'company'));
    }

    /**
     * Halaman Konfigurasi Aturan Program Loyalitas.
     */
    public function settings(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $program = $this->loyaltyService->getActiveProgram($company);

        return view('customers.loyalty.settings', compact('program', 'company'));
    }

    /**
     * Simpan Perubahan Aturan Program Loyalitas.
     */
    public function updateSettings(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'spend_amount_per_point' => 'required|numeric|min:1',
            'point_redemption_value' => 'required|numeric|min:0.01',
            'min_points_to_redeem' => 'required|integer|min:1',
            'points_expiry_days' => 'nullable|integer|min:1',
            'allow_merchandise_redemption' => 'nullable|boolean',
            'only_registered_customers' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $company = app(\App\Services\CompanyService::class)->getActiveCompany();
            $data = $validated;
            $data['is_active'] = $request->has('is_active');
            $data['allow_merchandise_redemption'] = $request->has('allow_merchandise_redemption');
            $data['only_registered_customers'] = $request->has('only_registered_customers');

            $program = $this->loyaltyService->updateProgram($company, $data);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengaturan program loyalitas berhasil disimpan.',
                    'program' => $program,
                ]);
            }

            return redirect()->route('customers.loyalty.settings')
                ->with('success', 'Konfigurasi aturan poin reward loyalitas berhasil diperbarui.');
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan pengaturan: '.$e->getMessage()]);
        }
    }

    /**
     * Buku Besar Mutasi Poin Pelanggan (Point Ledger).
     */
    public function customerLedger(string $customerId): View|JsonResponse
    {
        $customer = Customer::with(['tier'])->findOrFail($customerId);
        $mutations = CustomerPointMutation::where('customer_id', $customer->id)
            ->with(['transaction', 'creator'])
            ->latest('created_at')
            ->paginate(20);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'mutations' => $mutations,
            ]);
        }

        return view('customers.loyalty.ledger', compact('customer', 'mutations'));
    }

    /**
     * Penyesuaian Saldo Poin Manual oleh Manajemen.
     */
    public function adjust(Request $request, string $customerId): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'points_delta' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $customer = Customer::findOrFail($customerId);
            $user = Auth::user();

            $mutation = $this->loyaltyService->adjustPoints(
                customer: $customer,
                pointsDelta: (int) $validated['points_delta'],
                reason: $validated['reason'],
                userId: $user?->id
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Penyesuaian saldo poin berhasil diproses.',
                    'customer' => $customer->fresh(),
                    'mutation' => $mutation,
                ]);
            }

            return redirect()->route('customers.loyalty.ledger', $customer->id)
                ->with('success', "Penyesuaian poin untuk {$customer->name} berhasil disimpan.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal menyesuaikan poin: '.$e->getMessage()]);
        }
    }

    /**
     * Jalankan Batch Pembersihan Poin Kedaluwarsa.
     */
    public function expirePoints(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $company = app(\App\Services\CompanyService::class)->getActiveCompany();
            $expiredCount = $this->loyaltyService->expirePoints($company);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Proses kedaluwarsa selesai. {$expiredCount} mutasi poin telah diproses.",
                    'expired_count' => $expiredCount,
                ]);
            }

            return redirect()->route('customers.loyalty.index')
                ->with('success', "Proses kedaluwarsa selesai. {$expiredCount} catatan poin telah ditandai kedaluwarsa.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Gagal memproses poin kedaluwarsa: '.$e->getMessage()]);
        }
    }

    /**
     * API JSON Cek Saldo Poin & Nilai Diskon untuk Kasir POS.
     */
    public function pointsLookup(string $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);
        $company = $customer->company;
        $program = $this->loyaltyService->getActiveProgram($company);

        $points = (int) $customer->loyalty_points;
        $redeemValue = (float) $program->point_redemption_value;
        $potentialDiscount = $points >= $program->min_points_to_redeem
            ? round($points * $redeemValue, 2)
            : 0.0;

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'loyalty_points' => $points,
                'min_points_to_redeem' => $program->min_points_to_redeem,
                'point_redemption_value' => $redeemValue,
                'potential_discount' => $potentialDiscount,
                'can_redeem' => ($points >= $program->min_points_to_redeem),
            ],
        ]);
    }
}
