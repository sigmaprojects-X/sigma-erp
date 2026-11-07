<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPointMutation;
use App\Models\Store;
use App\Services\CompanyService;
use App\Services\LoyaltyPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerPointController extends Controller
{
    public function __construct(
        protected LoyaltyPointService $loyaltyPointService,
        protected CompanyService $companyService
    ) {}

    /**
     * Tampilkan halaman Cek & Manajemen Poin Member Pelanggan di Menu Penjualan.
     */
    public function index(Request $request): View
    {
        $company = $this->companyService->getActiveCompany();
        $program = $this->loyaltyPointService->getActiveProgram($company);

        $search = trim($request->input('q', ''));
        $selectedCustomerId = $request->input('customer_id');

        // Query Member Customers
        $customerQuery = Customer::where('company_id', $company->id)
            ->where('code', '!=', 'CUST-UMUM')
            ->where('is_active', true)
            ->with('tier');

        if (!empty($search)) {
            $customerQuery->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $customers = $customerQuery->orderByDesc('loyalty_points')->paginate(15)->withQueryString();

        // Selected Customer Detail (jika ada yang dipilih)
        $selectedCustomer = null;
        $customerMutations = collect();
        if ($selectedCustomerId) {
            $selectedCustomer = Customer::where('company_id', $company->id)->with('tier')->find($selectedCustomerId);
            if ($selectedCustomer) {
                $customerMutations = CustomerPointMutation::where('customer_id', $selectedCustomer->id)
                    ->with(['transaction', 'creator'])
                    ->latest()
                    ->take(30)
                    ->get();
            }
        }

        // Metrik Ringkasan Poin
        $totalRegisteredMembers = Customer::where('company_id', $company->id)
            ->where('code', '!=', 'CUST-UMUM')
            ->count();

        $totalPointsCirculating = (int) Customer::where('company_id', $company->id)->sum('loyalty_points');
        $pointValueRupiah = (float) ($program->point_redemption_value ?? 100);
        $totalPointsValueRupiah = $totalPointsCirculating * $pointValueRupiah;

        $pointsRedeemedThisMonth = (int) abs(CustomerPointMutation::where('company_id', $company->id)
            ->where('type', 'REDEEM')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('points'));

        // Riwayat Mutasi Poin Terbaru Seluruh Member
        $recentMutations = CustomerPointMutation::where('company_id', $company->id)
            ->with(['customer.tier', 'transaction', 'creator'])
            ->latest()
            ->paginate(20, ['*'], 'mutations_page')
            ->withQueryString();

        return view('sales.points.index', compact(
            'company',
            'program',
            'customers',
            'selectedCustomer',
            'customerMutations',
            'search',
            'totalRegisteredMembers',
            'totalPointsCirculating',
            'pointValueRupiah',
            'totalPointsValueRupiah',
            'pointsRedeemedThisMonth',
            'recentMutations'
        ));
    }

    /**
     * API Lookup Poin & Riwayat Pelanggan Cepat (JSON).
     */
    public function lookup(string $customerId): JsonResponse
    {
        $company = $this->companyService->getActiveCompany();
        $customer = Customer::where('company_id', $company->id)->with('tier')->findOrFail($customerId);
        $program = $this->loyaltyPointService->getActiveProgram($company);

        $mutations = CustomerPointMutation::where('customer_id', $customer->id)
            ->with(['transaction', 'creator'])
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'points' => (int) $m->points,
                'balance_before' => (int) ($m->balance_before ?? 0),
                'balance_after' => (int) ($m->balance_after ?? 0),
                'invoice_number' => $m->transaction?->invoice_number,
                'description' => $m->description,
                'creator_name' => $m->creator?->name ?? 'Sistem Kasir POS',
                'created_at' => $m->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'phone' => $customer->phone ?? '-',
                'tier_name' => $customer->tier?->name ?? 'Retail',
                'loyalty_points' => (int) ($customer->loyalty_points ?? 0),
                'point_rupiah_value' => ((int) ($customer->loyalty_points ?? 0)) * (float) ($program->point_redemption_value ?? 100),
                'lifetime_points' => (int) ($customer->lifetime_points ?? 0),
            ],
            'program' => [
                'point_redemption_value' => (float) ($program->point_redemption_value ?? 100),
                'min_points_to_redeem' => (int) ($program->min_points_to_redeem ?? 10),
            ],
            'mutations' => $mutations,
        ]);
    }

    /**
     * Penyesuaian Manual Poin Member (Bonus / Koreksi oleh Manajer/Owner).
     */
    public function adjust(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id' => 'required|uuid',
            'points_delta' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $company = $this->companyService->getActiveCompany();
        $customer = Customer::where('company_id', $company->id)->findOrFail($request->customer_id);
        $pointsDelta = (int) $request->points_delta;
        $reason = trim($request->reason);

        try {
            $this->loyaltyPointService->adjustPoints(
                customer: $customer,
                pointsDelta: $pointsDelta,
                reason: $reason,
                userId: Auth::id()
            );

            $actionText = $pointsDelta >= 0 ? "ditambahkan sebesar +{$pointsDelta}" : "dikurangi sebesar {$pointsDelta}";
            return redirect()
                ->route('sales.points.index', ['customer_id' => $customer->id])
                ->with('success', "Poin pelanggan '{$customer->name}' berhasil {$actionText} poin. Alasan: {$reason}");
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menyesuaikan poin: ' . $e->getMessage());
        }
    }
}

