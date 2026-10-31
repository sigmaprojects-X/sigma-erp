<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRewardRedemption;
use App\Models\Product;
use App\Models\Store;
use App\Services\LoyaltyPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RewardController extends Controller
{
    public function __construct(
        protected LoyaltyPointService $loyaltyService
    ) {}

    /**
     * Tampilkan Katalog Hadiah & Riwayat Klaim Merchandise.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $rewards = LoyaltyReward::where('company_id', $company->id)
            ->with(['product'])
            ->orderBy('points_required', 'asc')
            ->paginate(15);

        $recentRedemptions = LoyaltyRewardRedemption::where('company_id', $company->id)
            ->with(['customer', 'loyaltyReward', 'store', 'processor'])
            ->latest('redemption_date')
            ->limit(10)
            ->get();

        $customers = Customer::where('company_id', $company->id)
            ->where('loyalty_points', '>', 0)
            ->orderBy('name')
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'rewards' => $rewards,
                'recent_redemptions' => $recentRedemptions,
            ]);
        }

        return view('customers.rewards.index', compact('rewards', 'recentRedemptions', 'customers', 'company'));
    }

    /**
     * Form Tambah Hadiah Baru.
     */
    public function create(): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $products = Product::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('customers.rewards.create', compact('products'));
    }

    /**
     * Simpan Hadiah Baru ke Katalog.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:200',
            'points_required' => 'required|integer|min:1',
            'stock_quantity' => 'required|numeric|min:0',
            'product_id' => 'nullable|uuid|exists:products,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

            $reward = LoyaltyReward::create([
                'company_id' => $company->id,
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'points_required' => (int) $validated['points_required'],
                'stock_quantity' => (float) $validated['stock_quantity'],
                'product_id' => $validated['product_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'reward' => $reward]);
            }

            return redirect()->route('customers.rewards.index')
                ->with('success', "Hadiah {$reward->name} berhasil ditambahkan ke katalog.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal menambah hadiah: '.$e->getMessage()]);
        }
    }

    /**
     * Proses Tukar Hadiah (Redeem Merchandise).
     */
    public function redeem(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'loyalty_reward_id' => 'required|uuid|exists:loyalty_rewards,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            $reward = LoyaltyReward::findOrFail($validated['loyalty_reward_id']);
            $store = Store::where('company_id', $customer->company_id)->firstOrFail();
            $user = Auth::user();

            $redemption = $this->loyaltyService->redeemMerchandise(
                customer: $customer,
                reward: $reward,
                store: $store,
                qty: (int) $validated['quantity'],
                userId: $user?->id,
                notes: $validated['notes'] ?? null
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Penukaran hadiah berhasil diproses!',
                    'redemption' => $redemption,
                ]);
            }

            return redirect()->route('customers.rewards.index')
                ->with('success', "Hadiah {$reward->name} x {$validated['quantity']} berhasil ditukarkan untuk {$customer->name} (No: {$redemption->redemption_number}).");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Hapus Hadiah dari Katalog.
     */
    public function destroy(LoyaltyReward $reward): RedirectResponse|JsonResponse
    {
        $name = $reward->name;
        $reward->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Hadiah {$name} dihapus."]);
        }

        return redirect()->route('customers.rewards.index')
            ->with('success', "Hadiah {$name} berhasil dihapus dari katalog.");
    }
}
