<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAssembly;
use App\Models\ProductBom;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Services\AssemblyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AssemblyController extends Controller
{
    public function __construct(
        protected AssemblyService $assemblyService
    ) {}

    /**
     * Riwayat Perakitan & Pembongkaran Barang Jadi / Paket Bundling.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $query = ProductAssembly::where('company_id', $company->id)
            ->with(['store', 'parentProduct', 'parentUnit', 'creator', 'items.componentProduct', 'items.componentUnit'])
            ->latest('assembly_date')
            ->latest('created_at');

        if ($request->filled('type')) {
            $query->where('type', strtoupper($request->type));
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('assembly_number', 'ilike', "%{$search}%")
                    ->orWhereHas('parentProduct', function ($pq) use ($search) {
                        $pq->where('name', 'ilike', "%{$search}%")
                            ->orWhere('sku', 'ilike', "%{$search}%");
                    });
            });
        }

        $assemblies = $query->paginate(15)->withQueryString();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'assemblies' => $assemblies,
            ]);
        }

        return view('warehouse.assemblies.index', compact('assemblies', 'stores', 'company'));
    }

    /**
     * Form Perakitan Paket Baru.
     */
    public function create(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['units'])
            ->orderBy('name')
            ->get();

        return view('warehouse.assemblies.create', compact('stores', 'products', 'company'));
    }

    /**
     * Eksekusi Perakitan Paket Bundling / Hampers.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'parent_product_id' => 'required|uuid|exists:products,id',
            'parent_product_unit_id' => 'required|uuid|exists:product_units,id',
            'quantity' => 'required|numeric|min:0.0001',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.component_product_id' => 'required|uuid|exists:products,id',
            'items.*.component_product_unit_id' => 'required|uuid|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
            $store = Store::where('company_id', $companyId)->findOrFail($validated['store_id']);
            $parentProduct = Product::where('company_id', $companyId)->findOrFail($validated['parent_product_id']);
            $parentUnit = ProductUnit::where('company_id', $companyId)->findOrFail($validated['parent_product_unit_id']);
            $user = Auth::user();

            $assembly = $this->assemblyService->assemble(
                store: $store,
                parent: $parentProduct,
                parentUnit: $parentUnit,
                quantity: (float) $validated['quantity'],
                components: $validated['items'],
                user: $user,
                notes: $validated['notes'] ?? null
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Perakitan barang jadi berhasil disimpan.',
                    'assembly' => $assembly->load(['items.componentProduct', 'items.componentUnit']),
                ]);
            }

            return redirect()->route('warehouse.assemblies.show', $assembly->id)
                ->with('success', "Perakitan paket {$assembly->assembly_number} berhasil diproses.");
        } catch (InsufficientStockException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal memproses perakitan: '.$e->getMessage()]);
        }
    }

    /**
     * Rincian Perakitan / Pembongkaran.
     */
    public function show(string $id): View|JsonResponse
    {
        $assembly = ProductAssembly::with([
            'store',
            'parentProduct',
            'parentUnit',
            'creator',
            'items.componentProduct',
            'items.componentUnit',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'assembly' => $assembly,
            ]);
        }

        return view('warehouse.assemblies.show', compact('assembly'));
    }

    /**
     * Bongkar Paket Kembali Menjadi Komponen Semula (Disassembly).
     */
    public function disassemble(Request $request, string $id): RedirectResponse|JsonResponse
    {
        try {
            $assembly = ProductAssembly::where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);
            $user = Auth::user();
            $notes = $request->input('notes', 'Pembongkaran manual melalui sistem.');

            $disassembly = $this->assemblyService->disassemble(
                originalAssembly: $assembly,
                user: $user,
                notes: $notes
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pembongkaran paket berhasil diproses.',
                    'disassembly' => $disassembly,
                ]);
            }

            return redirect()->route('warehouse.assemblies.show', $disassembly->id)
                ->with('success', "Pembongkaran paket {$disassembly->assembly_number} berhasil diproses.");
        } catch (InsufficientStockException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Gagal membongkar paket: '.$e->getMessage()]);
        }
    }

    /**
     * Manajemen Resep / Template BOM (Bill of Materials).
     */
    public function boms(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $boms = ProductBom::where('company_id', $company->id)
            ->with(['parentProduct', 'componentProduct', 'componentUnit'])
            ->get()
            ->groupBy('parent_product_id');

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['units'])
            ->orderBy('name')
            ->get();

        return view('warehouse.assemblies.boms', compact('boms', 'products', 'company'));
    }

    /**
     * Ambil Resep BOM untuk Parent Product via AJAX.
     */
    public function getBom(string $productId): JsonResponse
    {
        $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
        $product = Product::where('company_id', $companyId)->findOrFail($productId);
        $bomItems = $this->assemblyService->getBom($product);

        return response()->json([
            'success' => true,
            'parent_product' => $product,
            'items' => $bomItems,
        ]);
    }

    /**
     * Simpan Master Resep BOM.
     */
    public function storeBom(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'parent_product_id' => 'required|uuid|exists:products,id',
            'components' => 'required|array|min:1',
            'components.*.component_product_id' => 'required|uuid|exists:products,id',
            'components.*.component_product_unit_id' => 'required|uuid|exists:product_units,id',
            'components.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
            $parentProduct = Product::where('company_id', $companyId)->findOrFail($validated['parent_product_id']);
            $this->assemblyService->saveBom($parentProduct, $validated['components']);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Resep BOM berhasil disimpan.',
                ]);
            }

            return redirect()->route('warehouse.assemblies.boms')
                ->with('success', "Resep BOM untuk {$parentProduct->name} berhasil diperbarui.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan BOM: '.$e->getMessage()]);
        }
    }
}

