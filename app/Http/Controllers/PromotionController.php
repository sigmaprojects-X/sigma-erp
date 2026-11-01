<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Store;
use App\Services\PromoEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(
        protected PromoEngineService $promoEngine
    ) {}

    /**
     * Tampilkan Daftar Promosi.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $promotions = Promotion::where('company_id', $company->id)
            ->with(['buyProduct', 'rewardProduct', 'store'])
            ->latest('created_at')
            ->paginate(15);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'promotions' => $promotions,
            ]);
        }

        return view('promotions.index', compact('promotions', 'company'));
    }

    /**
     * Form Tambah Promo Baru.
     */
    public function create(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $products = Product::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $stores = Store::where('company_id', $company->id)->get();

        return view('promotions.create', compact('products', 'stores'));
    }

    /**
     * Simpan Promo Baru.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:200',
            'type' => 'required|in:BUY_X_GET_Y,TEBUS_MURAH,DISCOUNT_PERCENT,DISCOUNT_NOMINAL',
            'description' => 'nullable|string|max:500',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'buy_product_id' => 'nullable|uuid|exists:products,id',
            'buy_quantity' => 'nullable|numeric|min:0.01',
            'reward_product_id' => 'nullable|uuid|exists:products,id',
            'reward_quantity' => 'nullable|numeric|min:0.01',
            'special_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_nominal' => 'nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $company = app(\App\Services\CompanyService::class)->getActiveCompany();

            $promo = Promotion::create([
                'company_id' => $company->id,
                'store_id' => $validated['store_id'] ?? null,
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'buy_product_id' => $validated['buy_product_id'] ?? null,
                'buy_quantity' => $validated['buy_quantity'] ?? 1,
                'reward_product_id' => $validated['reward_product_id'] ?? null,
                'reward_quantity' => $validated['reward_quantity'] ?? 1,
                'special_price' => $validated['special_price'] ?? 0,
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'discount_nominal' => $validated['discount_nominal'] ?? 0,
                'min_spend' => $validated['min_spend'] ?? 0,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'promotion' => $promo]);
            }

            return redirect()->route('promotions.index')
                ->with('success', "Promo {$promo->name} berhasil dibuat!");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal membuat promo: '.$e->getMessage()]);
        }
    }

    /**
     * Toggle Status Aktif Promosi.
     */
    public function toggle(Promotion $promotion): RedirectResponse|JsonResponse
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'is_active' => $promotion->is_active]);
        }

        return redirect()->route('promotions.index')
            ->with('success', "Status promo {$promotion->name} diperbarui.");
    }

    /**
     * Hapus Promosi.
     */
    public function destroy(Promotion $promotion): RedirectResponse|JsonResponse
    {
        $name = $promotion->name;
        $promotion->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Promo {$name} berhasil dihapus."]);
        }

        return redirect()->route('promotions.index')
            ->with('success', "Promo {$name} berhasil dihapus.");
    }

    /**
     * Evaluasi Keranjang Belanja untuk POS UI.
     */
    public function evaluate(Request $request): JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $cartItems = $request->input('cart_items', []);
        $cartSubtotal = (float) $request->input('subtotal', 0.0);

        $result = $this->promoEngine->evaluateCart($company, $store, $cartItems, $cartSubtotal);

        return response()->json(array_merge(['success' => true], $result));
    }
}
