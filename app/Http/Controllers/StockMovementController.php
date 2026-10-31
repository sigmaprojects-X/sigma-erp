<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Store;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Daftar Riwayat Item Masuk (Stock In).
     */
    public function indexIn(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);

        $query = StockMovement::where('company_id', $company->id)
            ->where('movement_type', 'IN')
            ->with(['store', 'creator', 'items.product', 'items.productUnit']);

        if ($selectedStoreId) {
            $query->where('store_id', $selectedStoreId);
        }

        if ($cat = $request->get('category_reason')) {
            $query->where('category_reason', $cat);
        }

        $movements = $query->orderByDesc('created_at')->paginate(20);

        return view('warehouse.movements.in_index', compact('stores', 'selectedStoreId', 'movements'));
    }

    /**
     * Form Input Item Masuk Baru.
     */
    public function createIn(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);
        $selectedStore = $stores->firstWhere('id', $selectedStoreId) ?? $stores->first();

        return view('warehouse.movements.in_create', compact('stores', 'selectedStore'));
    }

    /**
     * Simpan Transaksi Item Masuk & Eksekusi Jurnal Akuntansi.
     */
    public function storeIn(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|uuid',
            'movement_date' => 'required|date',
            'category_reason' => 'required|string|max:50',
            'recipient_or_source' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::findOrFail($request->store_id);
        $userId = Auth::id();

        $dateStr = date('Ymd', strtotime($request->movement_date));
        $count = StockMovement::where('company_id', $company->id)
            ->where('movement_type', 'IN')
            ->whereDate('created_at', today())
            ->count() + 1;
        $movementNumber = 'IN-' . $dateStr . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($request, $company, $store, $userId, $movementNumber) {
            $totalCost = 0.0;
            $itemsData = [];

            foreach ($request->items as $row) {
                $product = Product::findOrFail($row['product_id']);
                $unit = ProductUnit::findOrFail($row['product_unit_id']);

                $qty = (float) $row['quantity'];
                $conv = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQty = round($qty * $conv, 4);

                $unitCost = (float) $product->cost_price;
                $lineCost = round($baseQty * $unitCost, 4);
                $totalCost += $lineCost;

                $itemsData[] = [
                    'product' => $product,
                    'unit' => $unit,
                    'quantity' => $qty,
                    'conversion_factor' => $conv,
                    'base_quantity' => $baseQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                    'notes' => $row['notes'] ?? null,
                ];
            }

            $movement = StockMovement::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'movement_number' => $movementNumber,
                'movement_type' => 'IN',
                'movement_date' => $request->movement_date,
                'category_reason' => $request->category_reason,
                'recipient_or_source' => $request->recipient_or_source,
                'status' => 'COMPLETED',
                'total_items' => count($itemsData),
                'total_cost_value' => $totalCost,
                'notes' => $request->notes,
                'created_by' => $userId,
                'approved_by' => $userId,
            ]);

            // Eksekusi mutasi fisik ke inventori
            foreach ($itemsData as $it) {
                $movement->items()->create([
                    'product_id' => $it['product']->id,
                    'product_unit_id' => $it['unit']->id,
                    'unit_code' => $it['unit']->unit_code,
                    'conversion_factor' => $it['conversion_factor'],
                    'quantity' => $it['quantity'],
                    'base_quantity' => $it['base_quantity'],
                    'unit_cost' => $it['unit_cost'],
                    'total_cost' => $it['total_cost'],
                    'notes' => $it['notes'],
                ]);

                // Tambah stok fisik menggunakan Goods Receipt logic (HPP Moving Average preserved)
                $this->inventoryService->recordGoodsReceipt(
                    product: $it['product'],
                    store: $store,
                    quantity: $it['base_quantity'],
                    purchaseCostPerUnit: $it['unit_cost'],
                    referenceNumber: $movementNumber,
                    notes: "Item Masuk ({$request->category_reason}): " . ($it['notes'] ?: $request->recipient_or_source ?: 'Penerimaan non-beli'),
                    userId: $userId
                );
            }

            // Catat Jurnal Akuntansi Dobel Entri
            $this->accountingService->recordStockMovementJournal($movement);
        });

        return redirect()->route('warehouse.movements.in.index', ['store_id' => $store->id])
            ->with('success', "Transaksi Item Masuk {$movementNumber} berhasil disimpan!");
    }

    /**
     * Daftar Riwayat Item Keluar (Stock Out).
     */
    public function indexOut(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);

        $query = StockMovement::where('company_id', $company->id)
            ->where('movement_type', 'OUT')
            ->with(['store', 'creator', 'items.product', 'items.productUnit']);

        if ($selectedStoreId) {
            $query->where('store_id', $selectedStoreId);
        }

        if ($cat = $request->get('category_reason')) {
            $query->where('category_reason', $cat);
        }

        $movements = $query->orderByDesc('created_at')->paginate(20);

        return view('warehouse.movements.out_index', compact('stores', 'selectedStoreId', 'movements'));
    }

    /**
     * Form Input Item Keluar Baru.
     */
    public function createOut(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);
        $selectedStore = $stores->firstWhere('id', $selectedStoreId) ?? $stores->first();

        return view('warehouse.movements.out_create', compact('stores', 'selectedStore'));
    }

    /**
     * Simpan Transaksi Item Keluar & Eksekusi Jurnal Akuntansi.
     */
    public function storeOut(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|uuid',
            'movement_date' => 'required|date',
            'category_reason' => 'required|string|max:50',
            'recipient_or_source' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::findOrFail($request->store_id);
        $userId = Auth::id();

        $dateStr = date('Ymd', strtotime($request->movement_date));
        $count = StockMovement::where('company_id', $company->id)
            ->where('movement_type', 'OUT')
            ->whereDate('created_at', today())
            ->count() + 1;
        $movementNumber = 'OUT-' . $dateStr . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        try {
            DB::transaction(function () use ($request, $company, $store, $userId, $movementNumber) {
                $totalCost = 0.0;
                $itemsData = [];

                foreach ($request->items as $row) {
                    $product = Product::findOrFail($row['product_id']);
                    $unit = ProductUnit::findOrFail($row['product_unit_id']);

                    $qty = (float) $row['quantity'];
                    $conv = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                    $baseQty = round($qty * $conv, 4);

                    // Validasi Zero Negative Inventory
                    $stock = ProductStock::where('product_id', $product->id)->where('store_id', $store->id)->first();
                    $avail = $stock ? max(0, (float) $stock->physical_quantity - (float) $stock->reserved_quantity - (float) $stock->quarantined_quantity) : 0;

                    if ($baseQty > $avail) {
                        throw new \DomainException("Stok tidak mencukupi untuk item [{$product->name}]. Tersedia: {$avail}, Diminta: {$baseQty}.");
                    }

                    $unitCost = (float) $product->cost_price;
                    $lineCost = round($baseQty * $unitCost, 4);
                    $totalCost += $lineCost;

                    $itemsData[] = [
                        'product' => $product,
                        'unit' => $unit,
                        'quantity' => $qty,
                        'conversion_factor' => $conv,
                        'base_quantity' => $baseQty,
                        'unit_cost' => $unitCost,
                        'total_cost' => $lineCost,
                        'notes' => $row['notes'] ?? null,
                    ];
                }

                $movement = StockMovement::create([
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'movement_number' => $movementNumber,
                    'movement_type' => 'OUT',
                    'movement_date' => $request->movement_date,
                    'category_reason' => $request->category_reason,
                    'recipient_or_source' => $request->recipient_or_source,
                    'status' => 'COMPLETED',
                    'total_items' => count($itemsData),
                    'total_cost_value' => $totalCost,
                    'notes' => $request->notes,
                    'created_by' => $userId,
                    'approved_by' => $userId,
                ]);

                // Eksekusi mutasi fisik ke inventori
                foreach ($itemsData as $it) {
                    $movement->items()->create([
                        'product_id' => $it['product']->id,
                        'product_unit_id' => $it['unit']->id,
                        'unit_code' => $it['unit']->unit_code,
                        'conversion_factor' => $it['conversion_factor'],
                        'quantity' => $it['quantity'],
                        'base_quantity' => $it['base_quantity'],
                        'unit_cost' => $it['unit_cost'],
                        'total_cost' => $it['total_cost'],
                        'notes' => $it['notes'],
                    ]);

                    // Potong stok fisik toko asal
                    $this->inventoryService->deductStock(
                        product: $it['product'],
                        store: $store,
                        quantity: $it['base_quantity'],
                        mutationType: 'STOCK_OUT',
                        referenceNumber: $movementNumber,
                        notes: "Item Keluar ({$request->category_reason}): " . ($it['notes'] ?: $request->recipient_or_source ?: 'Pengeluaran non-jual'),
                        userId: $userId
                    );
                }

                // Catat Jurnal Akuntansi Dobel Entri
                $this->accountingService->recordStockMovementJournal($movement);
            });
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('warehouse.movements.out.index', ['store_id' => $store->id])
            ->with('success', "Transaksi Item Keluar {$movementNumber} berhasil disimpan!");
    }

    /**
     * Rincian Transaksi Item Masuk / Keluar.
     */
    public function show(string $id): View
    {
        $movement = StockMovement::with(['company', 'store', 'creator', 'items.product', 'items.productUnit'])
            ->findOrFail($id);

        return view('warehouse.movements.show', compact('movement'));
    }

    /**
     * Cetak Slip / Bukti Transaksi Item Masuk atau Keluar.
     */
    public function printSlip(string $id): View
    {
        $movement = StockMovement::with(['company', 'store', 'creator', 'items.product', 'items.productUnit'])
            ->findOrFail($id);

        return view('warehouse.movements.print', compact('movement'));
    }
}
