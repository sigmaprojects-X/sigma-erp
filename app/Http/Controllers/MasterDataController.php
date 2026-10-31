<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\StockMutation;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataController extends Controller
{
    /**
     * Master Data Produk & Harga 3D.
     */
    public function products(Request $request): View
    {
        $query = Product::with(['units', 'barcodes', 'prices.customerTier', 'stocks']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('sku', 'ILIKE', "%{$search}%")
                    ->orWhere('category', 'ILIKE', "%{$search}%")
                    ->orWhereHas('barcodes', function ($bq) use ($search) {
                        $bq->where('barcode', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $products = $query->orderBy('name')->paginate(20)->withQueryString();
        $customerTiers = CustomerTier::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('code')->get();

        return view('master.products', compact('products', 'customerTiers', 'categories', 'units'));
    }

    /**
     * Tampilan Formulir Tambah Item / Produk Baru (6 Tab Lengkap ala iPOS 5 Pro).
     */
    public function createProduct(Request $request): View
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        $customerTiers = CustomerTier::where('is_active', true)
            ->orderByRaw("CASE 
                WHEN code = 'RETAIL' THEN 1 
                WHEN code = 'GROSIR' THEN 2 
                WHEN code = 'AGEN' THEN 3 
                WHEN code = 'DISTRIBUTOR' THEN 4 
                WHEN code = 'MEMBER' THEN 5 
                WHEN code = 'KHUSUS' THEN 6 
                ELSE 7 END")
            ->get();
        $autoSku = 'PRD-'.date('ymd').'-'.strtoupper(Str::random(4));

        return view('master.product_form', compact('categories', 'suppliers', 'units', 'customerTiers', 'autoSku'));
    }

    /**
     * Tampilan Formulir Edit Item / Produk.
     */
    public function editProduct(string $id): View
    {
        $product = Product::with(['units.barcodes', 'units.prices.customerTier', 'supplier', 'category', 'stocks'])->findOrFail($id);
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        $customerTiers = CustomerTier::where('is_active', true)
            ->orderByRaw("CASE 
                WHEN code = 'RETAIL' THEN 1 
                WHEN code = 'GROSIR' THEN 2 
                WHEN code = 'AGEN' THEN 3 
                WHEN code = 'DISTRIBUTOR' THEN 4 
                WHEN code = 'MEMBER' THEN 5 
                WHEN code = 'KHUSUS' THEN 6 
                ELSE 7 END")
            ->get();

        return view('master.product_form', compact('product', 'categories', 'suppliers', 'units', 'customerTiers'));
    }

    /**
     * Simpan Produk Baru Beserta Multi Satuan, Barcode, Dimensi & Harga Jual 3D.
     */
    public function storeProduct(Request $request): RedirectResponse
    {
        $isMultiUnit = $request->has('units') && is_array($request->input('units'));

        if ($isMultiUnit) {
            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products,sku',
                'cost_price' => 'required|numeric|min:0',
                'units' => 'required|array|min:1',
                'units.0.unit_code' => 'required|string|max:30',
                'image' => 'nullable|image|max:2048',
                'additional_images.*' => 'nullable|image|max:2048',
            ]);
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products,sku',
                'category' => 'nullable|string|max:100',
                'cost_price' => 'required|numeric|min:0',
                'unit_code' => 'required|string|max:30',
                'unit_name' => 'nullable|string|max:50',
                'barcode' => 'nullable|string|max:50|unique:product_barcodes,barcode',
                'price_retail' => 'required|numeric|min:0',
                'price_grosir' => 'nullable|numeric|min:0',
                'image' => 'nullable|image|max:2048',
                'additional_images.*' => 'nullable|image|max:2048',
            ]);
        }

        if ($isMultiUnit && $request->has('units') && is_array($request->input('units'))) {
            $seenCodes = [];
            foreach ($request->input('units') as $idx => $uData) {
                $code = strtoupper(trim($uData['unit_code'] ?? ''));
                if ($code !== '') {
                    if (isset($seenCodes[$code])) {
                        return back()->with('error', "Kode kemasan/satuan [{$code}] duplikat. Setiap baris kemasan harus menggunakan satuan yang berbeda.")->withInput();
                    }
                    $seenCodes[$code] = $idx;
                }
            }
        }

        $company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $store = Store::where('code', 'STR-001')->firstOrFail();
        $tierRetail = CustomerTier::where('code', 'RETAIL')->first();
        $tierGrosir = CustomerTier::where('code', 'GROSIR')->first();

        DB::transaction(function () use ($request, $company, $store, $tierRetail, $tierGrosir, $isMultiUnit) {
            // 1. Tentukan Kategori
            $categoryId = $request->category_id;
            $categoryName = $request->category;
            if ($categoryId && ! $categoryName) {
                $categoryName = ProductCategory::find($categoryId)?->name;
            } elseif (! $categoryId && $categoryName) {
                $matchedCat = ProductCategory::where('name', $categoryName)->orWhere('code', $categoryName)->first();
                $categoryId = $matchedCat?->id;
            }

            // 2. Upload Gambar Utama jika ada
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            // 3. Upload Galeri Foto Tambahan jika ada
            $additionalImages = [];
            if ($request->hasFile('additional_images')) {
                foreach ($request->file('additional_images') as $file) {
                    $additionalImages[] = $file->store('products/gallery', 'public');
                }
            }

            // 4. Buat Master Produk
            $product = Product::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'category_id' => $categoryId,
                'supplier_id' => $request->supplier_id,
                'sku' => strtoupper(trim($request->sku)),
                'type' => $request->input('type', 'BARANG'),
                'name' => trim($request->name),
                'slug' => Str::slug($request->name.'-'.$request->sku),
                'category' => $categoryName ?: 'Umum',
                'sub_category' => $request->sub_category,
                'brand' => $request->brand,
                'rack_location' => $request->rack_location,
                'sale_status' => $request->input('sale_status', 'ACTIVE'),
                'cost_price' => (float) $request->cost_price,
                'last_purchase_price' => (float) $request->cost_price,
                'total_stock_value' => 0.00,
                'total_quantity' => 0.00,
                'min_stock_alert' => (float) ($request->min_stock_alert ?? 5.00),
                'max_stock' => $request->filled('max_stock') ? (float) $request->max_stock : null,
                'has_serial_number' => $request->boolean('has_serial_number'),
                'has_batch_expiry' => $request->boolean('has_batch_expiry'),
                'is_point_of_sale' => $request->input('sale_status', 'ACTIVE') === 'DISCONTINUED' ? false : true,
                'is_active' => $request->boolean('is_active', true),
                'notes' => $request->notes,
                'tax_type' => $request->input('tax_type', 'NON'),
                'tax_reference_code' => $request->tax_reference_code,
                'image_path' => $imagePath,
                'additional_images' => $additionalImages,
            ]);

            // 5. Inisialisasi Record Stok di Toko STR-001
            ProductStock::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'physical_quantity' => 0.00,
                'reserved_quantity' => 0.00,
                'quarantined_quantity' => 0.00,
            ]);

            // 6. Buat Satuan & Harga (Multi-Satuan vs Flat)
            if ($isMultiUnit) {
                foreach ($request->input('units') as $idx => $uData) {
                    if (empty($uData['unit_code'])) {
                        continue;
                    }

                    $unitCode = strtoupper(trim($uData['unit_code']));
                    $conversion = (float) ($uData['conversion_factor'] ?? 1);
                    $isBase = $idx === 0 || ! empty($uData['is_base_unit']);

                    $pUnit = ProductUnit::create([
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                        'unit_code' => $unitCode,
                        'unit_name' => trim($uData['unit_name'] ?? $unitCode),
                        'conversion_factor' => $conversion > 0 ? $conversion : 1.0000,
                        'weight_gram' => (float) ($uData['weight_gram'] ?? 0.00),
                        'length_cm' => (float) ($uData['length_cm'] ?? 0.00),
                        'width_cm' => (float) ($uData['width_cm'] ?? 0.00),
                        'height_cm' => (float) ($uData['height_cm'] ?? 0.00),
                        'point_reward' => (float) ($uData['point_reward'] ?? 0.00),
                        'commission_rate' => (float) ($uData['commission_rate'] ?? 0.00),
                        'is_base_unit' => $isBase,
                        'is_active' => true,
                    ]);

                    // Barcode khusus satuan ini
                    if (! empty($uData['barcode'])) {
                        ProductBarcode::create([
                            'company_id' => $company->id,
                            'store_id' => $store->id,
                            'product_id' => $product->id,
                            'product_unit_id' => $pUnit->id,
                            'barcode' => trim($uData['barcode']),
                            'is_primary' => $isBase,
                        ]);
                    }

                    // 1. Simpan Harga per Level Pelanggan (Customer Tier: Level 1 s/d Level 6)
                    if (! empty($uData['tier_prices']) && is_array($uData['tier_prices'])) {
                        foreach ($uData['tier_prices'] as $tierId => $tData) {
                            if (isset($tData['price']) && $tData['price'] !== '' && (float) $tData['price'] > 0) {
                                ProductPrice::create([
                                    'company_id' => $company->id,
                                    'product_id' => $product->id,
                                    'product_unit_id' => $pUnit->id,
                                    'customer_tier_id' => $tierId,
                                    'store_id' => null,
                                    'min_quantity' => 1.00,
                                    'price' => (float) $tData['price'],
                                    'margin_percentage' => isset($tData['margin']) ? (float) $tData['margin'] : null,
                                    'discount_1' => (float) ($uData['discount_1'] ?? 0.00),
                                    'discount_2' => (float) ($uData['discount_2'] ?? 0.00),
                                    'discount_3' => (float) ($uData['discount_3'] ?? 0.00),
                                    'discount_4' => (float) ($uData['discount_4'] ?? 0.00),
                                    'is_active' => true,
                                ]);
                            }
                        }
                    } else {
                        // Fallback: Harga Retail & Grosir jika tier_prices tidak dikirim
                        if ($tierRetail && isset($uData['price_retail']) && $uData['price_retail'] !== '') {
                            ProductPrice::create([
                                'company_id' => $company->id,
                                'product_id' => $product->id,
                                'product_unit_id' => $pUnit->id,
                                'customer_tier_id' => $tierRetail->id,
                                'store_id' => null,
                                'min_quantity' => 1.00,
                                'price' => (float) $uData['price_retail'],
                                'margin_percentage' => isset($uData['margin_retail']) ? (float) $uData['margin_retail'] : null,
                                'discount_1' => (float) ($uData['discount_1'] ?? 0.00),
                                'discount_2' => (float) ($uData['discount_2'] ?? 0.00),
                                'discount_3' => (float) ($uData['discount_3'] ?? 0.00),
                                'discount_4' => (float) ($uData['discount_4'] ?? 0.00),
                                'is_active' => true,
                            ]);
                        }

                        if ($tierGrosir && isset($uData['price_grosir']) && $uData['price_grosir'] !== '') {
                            ProductPrice::create([
                                'company_id' => $company->id,
                                'product_id' => $product->id,
                                'product_unit_id' => $pUnit->id,
                                'customer_tier_id' => $tierGrosir->id,
                                'store_id' => null,
                                'min_quantity' => 1.00,
                                'price' => (float) $uData['price_grosir'],
                                'margin_percentage' => isset($uData['margin_grosir']) ? (float) $uData['margin_grosir'] : null,
                                'is_active' => true,
                            ]);
                        }
                    }

                    // 2. Simpan Harga Berdasarkan Jumlah Pembelian (Tiered Qty / Grosir Min Qty)
                    if (! empty($uData['tiered_prices']) && is_array($uData['tiered_prices'])) {
                        foreach ($uData['tiered_prices'] as $tPrice) {
                            $minQty = (float) ($tPrice['min_qty'] ?? 1);
                            $qtyPrice = (float) ($tPrice['price'] ?? 0);
                            if ($minQty > 1 && $qtyPrice > 0) {
                                ProductPrice::create([
                                    'company_id' => $company->id,
                                    'product_id' => $product->id,
                                    'product_unit_id' => $pUnit->id,
                                    'customer_tier_id' => $tierRetail ? $tierRetail->id : CustomerTier::first()->id,
                                    'store_id' => null,
                                    'min_quantity' => $minQty,
                                    'price' => $qtyPrice,
                                    'margin_percentage' => isset($tPrice['margin']) ? (float) $tPrice['margin'] : null,
                                    'is_active' => true,
                                ]);
                            }
                        }
                    }
                }
            } else {
                // Fallback / Simple Payload
                $unitCode = strtoupper(trim($request->unit_code ?: 'PCS'));
                $unit = ProductUnit::create([
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'unit_code' => $unitCode,
                    'unit_name' => trim($request->unit_name ?: $unitCode),
                    'conversion_factor' => 1.0000,
                    'is_base_unit' => true,
                ]);

                if ($request->filled('barcode')) {
                    ProductBarcode::create([
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                        'product_unit_id' => $unit->id,
                        'barcode' => trim($request->barcode),
                        'is_primary' => true,
                    ]);
                }

                if ($tierRetail && $request->filled('price_retail')) {
                    ProductPrice::create([
                        'company_id' => $company->id,
                        'product_id' => $product->id,
                        'product_unit_id' => $unit->id,
                        'customer_tier_id' => $tierRetail->id,
                        'store_id' => null,
                        'min_quantity' => 1.00,
                        'price' => (float) $request->price_retail,
                        'is_active' => true,
                    ]);
                }

                if ($tierGrosir && $request->filled('price_grosir')) {
                    ProductPrice::create([
                        'company_id' => $company->id,
                        'product_id' => $product->id,
                        'product_unit_id' => $unit->id,
                        'customer_tier_id' => $tierGrosir->id,
                        'store_id' => null,
                        'min_quantity' => 1.00,
                        'price' => (float) $request->price_grosir,
                        'is_active' => true,
                    ]);
                }
            }
        });

        return redirect()->route('master.products')->with('success', "Alhamdulillah, data item [{$request->name}] berhasil disimpan ke sistem SIGMA ERP.");
    }

    /**
     * Perbarui Data Item / Produk.
     */
    public function updateProduct(Request $request, string $id): RedirectResponse
    {
        $product = Product::with(['units.barcodes', 'units.prices', 'stocks'])->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,'.$product->id,
            'cost_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'additional_images.*' => 'nullable|image|max:2048',
        ]);

        // Aturan Discontinue: Stok fisik harus kosong (0) sebelum dapat di-discontinue
        $newSaleStatus = $request->input('sale_status', $product->sale_status);
        if ($newSaleStatus === 'DISCONTINUED') {
            $totalQty = (float) $product->total_quantity;
            $physicalStock = (float) ProductStock::where('product_id', $product->id)->sum('physical_quantity');
            $reservedStock = (float) ProductStock::where('product_id', $product->id)->sum('reserved_quantity');
            $quarantinedStock = (float) ProductStock::where('product_id', $product->id)->sum('quarantined_quantity');
            $effectiveStock = max($totalQty, $physicalStock, $reservedStock, $quarantinedStock);

            if ($effectiveStock > 0) {
                return back()->with('error', "Barang [{$product->name}] tidak dapat di-discontinue karena masih memiliki sisa stok fisik ({$effectiveStock} unit). Sisa stok harus kosong (0) terlebih dahulu sebelum status dapat diubah menjadi Discontinue.")->withInput();
            }
        }

        // Validasi duplikasi unit_code pada payload form
        if ($request->has('units') && is_array($request->input('units'))) {
            $seenCodes = [];
            foreach ($request->input('units') as $idx => $uData) {
                $code = strtoupper(trim($uData['unit_code'] ?? ''));
                if ($code !== '') {
                    if (isset($seenCodes[$code])) {
                        return back()->with('error', "Kode kemasan/satuan [{$code}] duplikat. Setiap baris kemasan harus menggunakan kode satuan yang berbeda.")->withInput();
                    }
                    $seenCodes[$code] = $idx;
                }
            }
        }

        $company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $store = Store::where('code', 'STR-001')->firstOrFail();
        $tierRetail = CustomerTier::where('code', 'RETAIL')->first();
        $tierGrosir = CustomerTier::where('code', 'GROSIR')->first();

        DB::transaction(function () use ($request, $product, $company, $store, $tierRetail, $tierGrosir, $newSaleStatus) {
            $categoryId = $request->category_id;
            $categoryName = $request->category;
            if ($categoryId && ! $categoryName) {
                $categoryName = ProductCategory::find($categoryId)?->name;
            } elseif (! $categoryId && $categoryName) {
                $matchedCat = ProductCategory::where('name', $categoryName)->orWhere('code', $categoryName)->first();
                $categoryId = $matchedCat?->id;
            }

            $updateData = [
                'sku' => strtoupper(trim($request->sku)),
                'type' => $request->input('type', $product->type),
                'name' => trim($request->name),
                'category_id' => $categoryId,
                'supplier_id' => $request->supplier_id,
                'category' => $categoryName ?: $product->category,
                'sub_category' => $request->sub_category,
                'brand' => $request->brand,
                'rack_location' => $request->rack_location,
                'sale_status' => $newSaleStatus,
                'cost_price' => (float) $request->cost_price,
                'min_stock_alert' => (float) ($request->min_stock_alert ?? $product->min_stock_alert),
                'max_stock' => $request->filled('max_stock') ? (float) $request->max_stock : null,
                'has_serial_number' => $request->boolean('has_serial_number'),
                'has_batch_expiry' => $request->boolean('has_batch_expiry'),
                'is_point_of_sale' => $newSaleStatus === 'DISCONTINUED' ? false : $request->boolean('is_point_of_sale', $product->is_point_of_sale),
                'is_active' => $request->boolean('is_active', true),
                'notes' => $request->notes,
                'tax_type' => $request->input('tax_type', $product->tax_type),
                'tax_reference_code' => $request->tax_reference_code,
            ];

            if ($request->hasFile('image')) {
                $updateData['image_path'] = $request->file('image')->store('products', 'public');
            }

            if ($request->hasFile('additional_images')) {
                $existingGallery = $product->additional_images ?: [];
                foreach ($request->file('additional_images') as $file) {
                    $existingGallery[] = $file->store('products/gallery', 'public');
                }
                $updateData['additional_images'] = $existingGallery;
            }

            $product->update($updateData);

            // Sinkronisasi Multi-Satuan & Mencegah Collision Unique Constraint PostgreSQL
            if ($request->has('units') && is_array($request->input('units'))) {
                $existingUnits = ProductUnit::where('product_id', $product->id)->get()->keyBy('id');
                $submittedRows = $request->input('units');

                // Kumpulkan ID satuan yang tetap dipertahankan
                $submittedUnitIds = [];
                foreach ($submittedRows as $uData) {
                    if (! empty($uData['id'])) {
                        $submittedUnitIds[] = $uData['id'];
                    }
                }

                // 1. Tangani unit yang dihapus dari baris UI
                $removedUnits = $existingUnits->filter(fn ($u) => ! in_array($u->id, $submittedUnitIds));
                foreach ($removedUnits as $remUnit) {
                    $isUsedInTx = DB::table('pos_transaction_items')->where('product_unit_id', $remUnit->id)->exists()
                        || DB::table('sales_order_items')->where('product_unit_id', $remUnit->id)->exists()
                        || DB::table('purchase_order_items')->where('product_unit_id', $remUnit->id)->exists()
                        || DB::table('purchase_items')->where('product_unit_id', $remUnit->id)->exists()
                        || DB::table('goods_receipt_items')->where('product_unit_id', $remUnit->id)->exists()
                        || DB::table('stock_mutations')->where('product_unit_id', $remUnit->id)->exists()
                        || DB::table('pos_return_items')->where('product_unit_id', $remUnit->id)->exists();

                    if ($isUsedInTx) {
                        $remUnit->update([
                            'is_active' => false,
                            'unit_code' => substr($remUnit->unit_code . '_ARCH_' . Str::random(6), 0, 50),
                        ]);
                    } else {
                        ProductBarcode::where('product_unit_id', $remUnit->id)->delete();
                        ProductPrice::where('product_unit_id', $remUnit->id)->delete();
                        $remUnit->delete();
                    }
                }

                // 2. Tahap 1: Temporary Rename jika ada perubahan kode satuan untuk menghindari UniqueConstraintViolationException
                foreach ($submittedRows as $uData) {
                    if (! empty($uData['id']) && isset($existingUnits[$uData['id']])) {
                        $pUnit = $existingUnits[$uData['id']];
                        $targetCode = strtoupper(trim($uData['unit_code'] ?? ''));
                        if ($targetCode !== '' && $pUnit->unit_code !== $targetCode) {
                            $pUnit->update([
                                'unit_code' => 'TMP_' . Str::random(10),
                            ]);
                        }
                    }
                }

                // 3. Tahap 2: Terapkan kode satuan final, konversi, harga dan barcode
                foreach ($submittedRows as $idx => $uData) {
                    if (empty($uData['unit_code'])) {
                        continue;
                    }

                    $unitCode = strtoupper(trim($uData['unit_code']));
                    $conversion = (float) ($uData['conversion_factor'] ?? 1);
                    $isBase = $idx === 0 || ! empty($uData['is_base_unit']);

                    $pUnit = null;
                    if (! empty($uData['id'])) {
                        $pUnit = ProductUnit::where('id', $uData['id'])->where('product_id', $product->id)->first();
                    }
                    if (! $pUnit) {
                        $pUnit = ProductUnit::where('product_id', $product->id)->where('unit_code', $unitCode)->first();
                    }
                    $isActiveUnit = $isBase ? true : (! empty($uData['is_active']));

                    $unitPayload = [
                        'unit_code' => $unitCode,
                        'unit_name' => trim($uData['unit_name'] ?? $unitCode),
                        'conversion_factor' => $conversion > 0 ? $conversion : 1.0000,
                        'weight_gram' => (float) ($uData['weight_gram'] ?? 0.00),
                        'length_cm' => (float) ($uData['length_cm'] ?? 0.00),
                        'width_cm' => (float) ($uData['width_cm'] ?? 0.00),
                        'height_cm' => (float) ($uData['height_cm'] ?? 0.00),
                        'point_reward' => (float) ($uData['point_reward'] ?? 0.00),
                        'commission_rate' => (float) ($uData['commission_rate'] ?? 0.00),
                        'is_base_unit' => $isBase,
                        'is_active' => $isActiveUnit,
                    ];

                    if (! $pUnit) {
                        $unitPayload['company_id'] = $company->id;
                        $unitPayload['store_id'] = $store->id;
                        $unitPayload['product_id'] = $product->id;
                        $pUnit = ProductUnit::create($unitPayload);
                    } else {
                        $pUnit->update($unitPayload);
                    }

                    // Update atau create barcode
                    if (! empty($uData['barcode'])) {
                        ProductBarcode::updateOrCreate(
                            ['product_id' => $product->id, 'product_unit_id' => $pUnit->id],
                            [
                                'company_id' => $company->id,
                                'store_id' => $store->id,
                                'barcode' => trim($uData['barcode']),
                                'is_primary' => $isBase,
                            ]
                        );
                    }

                    // 1. Simpan Harga per Level Pelanggan (Customer Tier: Level 1 s/d Level 6)
                    if (! empty($uData['tier_prices']) && is_array($uData['tier_prices'])) {
                        foreach ($uData['tier_prices'] as $tierId => $tData) {
                            if (isset($tData['price']) && $tData['price'] !== '' && (float) $tData['price'] > 0) {
                                ProductPrice::updateOrCreate(
                                    [
                                        'company_id' => $company->id,
                                        'product_id' => $product->id,
                                        'product_unit_id' => $pUnit->id,
                                        'customer_tier_id' => $tierId,
                                        'min_quantity' => 1.00,
                                        'store_id' => null,
                                    ],
                                    [
                                        'price' => (float) $tData['price'],
                                        'margin_percentage' => isset($tData['margin']) ? (float) $tData['margin'] : null,
                                        'discount_1' => (float) ($uData['discount_1'] ?? 0.00),
                                        'discount_2' => (float) ($uData['discount_2'] ?? 0.00),
                                        'discount_3' => (float) ($uData['discount_3'] ?? 0.00),
                                        'discount_4' => (float) ($uData['discount_4'] ?? 0.00),
                                        'is_active' => true,
                                    ]
                                );
                            }
                        }
                    } else {
                        // Update harga Retail
                        if ($tierRetail && isset($uData['price_retail']) && $uData['price_retail'] !== '') {
                            ProductPrice::updateOrCreate(
                                [
                                    'company_id' => $company->id,
                                    'product_id' => $product->id,
                                    'product_unit_id' => $pUnit->id,
                                    'customer_tier_id' => $tierRetail->id,
                                    'min_quantity' => 1.00,
                                    'store_id' => null,
                                ],
                                [
                                    'price' => (float) $uData['price_retail'],
                                    'margin_percentage' => isset($uData['margin_retail']) ? (float) $uData['margin_retail'] : null,
                                    'discount_1' => (float) ($uData['discount_1'] ?? 0.00),
                                    'discount_2' => (float) ($uData['discount_2'] ?? 0.00),
                                    'discount_3' => (float) ($uData['discount_3'] ?? 0.00),
                                    'discount_4' => (float) ($uData['discount_4'] ?? 0.00),
                                    'is_active' => true,
                                ]
                            );
                        }

                        // Update harga Grosir
                        if ($tierGrosir && isset($uData['price_grosir']) && $uData['price_grosir'] !== '') {
                            ProductPrice::updateOrCreate(
                                [
                                    'company_id' => $company->id,
                                    'product_id' => $product->id,
                                    'product_unit_id' => $pUnit->id,
                                    'customer_tier_id' => $tierGrosir->id,
                                    'min_quantity' => 1.00,
                                    'store_id' => null,
                                ],
                                [
                                    'price' => (float) $uData['price_grosir'],
                                    'margin_percentage' => isset($uData['margin_grosir']) ? (float) $uData['margin_grosir'] : null,
                                    'is_active' => true,
                                ]
                            );
                        }
                    }

                    // 2. Simpan Harga Berdasarkan Jumlah Pembelian (Tiered Qty / Grosir Min Qty)
                    if (! empty($uData['tiered_prices']) && is_array($uData['tiered_prices'])) {
                        foreach ($uData['tiered_prices'] as $tPrice) {
                            $minQty = (float) ($tPrice['min_qty'] ?? 1);
                            $qtyPrice = (float) ($tPrice['price'] ?? 0);
                            if ($minQty > 1 && $qtyPrice > 0) {
                                ProductPrice::updateOrCreate(
                                    [
                                        'company_id' => $company->id,
                                        'product_id' => $product->id,
                                        'product_unit_id' => $pUnit->id,
                                        'customer_tier_id' => $tierRetail ? $tierRetail->id : CustomerTier::first()->id,
                                        'min_quantity' => $minQty,
                                        'store_id' => null,
                                    ],
                                    [
                                        'price' => $qtyPrice,
                                        'margin_percentage' => isset($tPrice['margin']) ? (float) $tPrice['margin'] : null,
                                        'is_active' => true,
                                    ]
                                );
                            }
                        }
                    }
                }
            }

            AuditLog::record(
                module: 'PRODUCT',
                action: 'PRICE_UPDATE',
                description: "Update data item & penetapan harga produk [{$product->name}] (SKU: {$product->sku})"
            );
        });

        return redirect()->route('master.products')->with('success', "Alhamdulillah, data item [{$product->name}] berhasil diperbarui.");
    }

    /**
     * Hapus Item / Produk (Hanya jika belum pernah ada transaksi sama sekali).
     */
    public function destroyProduct(string $id): RedirectResponse
    {
        $product = Product::with(['stocks', 'units', 'barcodes', 'prices'])->findOrFail($id);

        // 1. Cek Saldo Stok Fisik
        $physicalStock = (float) $product->stocks->sum('physical_quantity');
        $reservedStock = (float) $product->stocks->sum('reserved_quantity');
        $quarantinedStock = (float) $product->stocks->sum('quarantined_quantity');
        $totalQty = (float) $product->total_quantity;

        if ($physicalStock > 0 || $reservedStock > 0 || $quarantinedStock > 0 || $totalQty > 0) {
            $effectiveStock = max($physicalStock, $totalQty);
            return back()->with('error', "Barang [{$product->name}] TIDAK DAPAT DIHAPUS karena masih memiliki sisa stok fisik ({$effectiveStock} unit). Barang hanya dapat dihapus jika belum pernah memiliki stok atau transaksi.");
        }

        // 2. Audit Riwayat Transaksi Operasional & Finansial
        $transactionChecks = [
            'Penjualan Kasir (POS)' => DB::table('pos_transaction_items')->where('product_id', $product->id)->exists(),
            'Pesanan Penjualan (SO)' => DB::table('sales_order_items')->where('product_id', $product->id)->exists(),
            'Purchase Order (PO)' => DB::table('purchase_order_items')->where('product_id', $product->id)->exists(),
            'Faktur Pembelian (Purchase)' => DB::table('purchase_items')->where('product_id', $product->id)->exists(),
            'Penerimaan Barang (Goods Receipt)' => DB::table('goods_receipt_items')->where('product_id', $product->id)->exists(),
            'Retur Penjualan' => DB::table('pos_return_items')->where('product_id', $product->id)->exists(),
            'Retur Pembelian' => DB::table('purchase_return_items')->where('product_id', $product->id)->exists(),
            'Kartu Mutasi Stok' => DB::table('stock_mutations')->where('product_id', $product->id)->exists(),
            'Stock Opname' => DB::table('stock_opname_items')->where('product_id', $product->id)->exists(),
            'Transfer Antar Toko' => DB::table('stock_transfer_items')->where('product_id', $product->id)->exists()
                || DB::table('transfer_request_items')->where('product_id', $product->id)->exists(),
            'Penyesuaian Stok' => DB::table('stock_adjustment_items')->where('product_id', $product->id)->exists()
                || DB::table('stock_movement_items')->where('product_id', $product->id)->exists(),
            'Konsinyasi' => DB::table('consignment_receipt_items')->where('product_id', $product->id)->exists()
                || DB::table('consignment_settlement_items')->where('product_id', $product->id)->exists(),
            'Promosi / Diskon' => DB::table('promotions')->where('buy_product_id', $product->id)->orWhere('reward_product_id', $product->id)->exists(),
            'Perakitan / BOM' => DB::table('product_boms')->where('parent_product_id', $product->id)->orWhere('component_product_id', $product->id)->exists()
                || DB::table('product_assemblies')->where('parent_product_id', $product->id)->exists()
                || DB::table('product_assembly_items')->where('component_product_id', $product->id)->exists(),
        ];

        $foundTransactions = [];
        foreach ($transactionChecks as $txName => $hasTx) {
            if ($hasTx) {
                $foundTransactions[] = $txName;
            }
        }

        if (! empty($foundTransactions)) {
            $txList = implode(', ', $foundTransactions);
            return back()->with('error', "Barang [{$product->name}] TIDAK DAPAT DIHAPUS karena sudah tercatat dalam riwayat: {$txList}. Untuk menjaga keutuhan laporan akuntansi dan riwayat audit, barang yang sudah pernah bertransaksi hanya dapat diubah statusnya menjadi 'Non-Aktif' atau 'Discontinue'.");
        }

        // 3. Eksekusi Penghapusan Aman untuk Barang yang Belum Pernah Bertransaksi (Salah Input)
        $productName = $product->name;
        $productSku = $product->sku;

        DB::transaction(function () use ($product, $productName, $productSku) {
            // Hapus file gambar jika ada
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            if (! empty($product->additional_images) && is_array($product->additional_images)) {
                foreach ($product->additional_images as $img) {
                    if (Storage::disk('public')->exists($img)) {
                        Storage::disk('public')->delete($img);
                    }
                }
            }

            // Hapus relasi master child
            ProductPrice::where('product_id', $product->id)->delete();
            ProductBarcode::where('product_id', $product->id)->delete();
            ProductStock::where('product_id', $product->id)->delete();
            ProductUnit::where('product_id', $product->id)->delete();

            if (DB::getSchemaBuilder()->hasTable('product_batches')) {
                DB::table('product_batches')->where('product_id', $product->id)->delete();
            }
            if (DB::getSchemaBuilder()->hasTable('loyalty_rewards')) {
                DB::table('loyalty_rewards')->where('product_id', $product->id)->delete();
            }

            // Hapus record master produk
            $product->delete();

            // Catat ke Audit Log
            AuditLog::record(
                module: 'PRODUCT',
                action: 'DELETE',
                description: "Menghapus master produk salah input [{$productName}] (SKU: {$productSku}) yang belum pernah bertransaksi."
            );
        });

        return redirect()->route('master.products')->with('success', "Alhamdulillah, barang salah input [{$productName}] berhasil dihapus dari sistem.");
    }

    /**
     * Master Data Pelanggan & Plafon Kredit.
     */
    public function customers(Request $request): View
    {
        $query = Customer::with('customerTier');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();
        $customerTiers = CustomerTier::where('is_active', true)->get();

        return view('master.customers', compact('customers', 'customerTiers'));
    }

    /**
     * Simpan Pelanggan Baru Beserta Plafon Kredit.
     */
    public function storeCustomer(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:customers,code',
            'name' => 'required|string|max:255',
            'customer_tier_id' => 'required|uuid',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_term_days' => 'nullable|integer|min:0',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $store = Store::where('code', 'STR-001')->firstOrFail();

        Customer::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'customer_tier_id' => $request->customer_tier_id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'phone' => trim($request->phone ?: '-'),
            'address' => trim($request->address ?: '-'),
            'credit_limit' => (float) ($request->credit_limit ?? 0.00),
            'current_credit_balance' => 0.00,
            'credit_term_days' => (int) ($request->credit_term_days ?? 0),
            'is_active' => true,
        ]);

        return redirect()->route('master.customers')->with('success', "Alhamdulillah, data pelanggan [{$request->name}] berhasil disimpan.");
    }

    /**
     * Perbarui Data Pelanggan.
     */
    public function updateCustomer(Request $request, string $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:customers,code,' . $customer->id,
            'name' => 'required|string|max:255',
            'customer_tier_id' => 'required|uuid',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_term_days' => 'nullable|integer|min:0',
        ]);

        $customer->update([
            'customer_tier_id' => $request->customer_tier_id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'phone' => trim($request->phone ?: '-'),
            'address' => trim($request->address ?: '-'),
            'credit_limit' => (float) ($request->credit_limit ?? 0.00),
            'credit_term_days' => (int) ($request->credit_term_days ?? 0),
        ]);

        return redirect()->route('master.customers')->with('success', "Data pelanggan [{$customer->name}] berhasil diperbarui.");
    }

    /**
     * Ubah Status Aktif Pelanggan (Toggle).
     */
    public function toggleCustomerStatus(string $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['is_active' => !$customer->is_active]);

        $statusStr = $customer->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.customers')->with('success', "Status pelanggan [{$customer->name}] berhasil {$statusStr}.");
    }

    /**
     * Master Data Supplier / Pemasok Barang.
     */
    public function suppliers(Request $request): View
    {
        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('contact_person', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('master.suppliers', compact('suppliers'));
    }

    /**
     * Simpan Supplier Baru.
     */
    public function storeSupplier(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:suppliers,code',
            'name' => 'required|string|max:150',
            'is_pkp' => 'nullable|boolean',
            'npwp' => 'nullable|string|max:50',
            'tax_name' => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'term_of_payment_days' => 'nullable|integer|min:0',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->firstOrFail();

        Supplier::create([
            'company_id' => $company->id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'is_pkp' => $request->boolean('is_pkp'),
            'npwp' => $request->npwp ? trim($request->npwp) : null,
            'tax_name' => $request->tax_name ? trim($request->tax_name) : null,
            'contact_person' => trim($request->contact_person ?: '-'),
            'phone' => trim($request->phone ?: '-'),
            'email' => $request->email ? trim($request->email) : null,
            'address' => trim($request->address ?: '-'),
            'term_of_payment_days' => (int) ($request->term_of_payment_days ?? 0),
            'bank_name' => $request->bank_name ? trim($request->bank_name) : null,
            'bank_account_no' => $request->bank_account_no ? trim($request->bank_account_no) : null,
            'bank_account_holder' => $request->bank_account_holder ? trim($request->bank_account_holder) : null,
            'is_active' => true,
        ]);

        return redirect()->route('master.suppliers')->with('success', "Alhamdulillah, data supplier [{$request->name}] berhasil disimpan.");
    }

    /**
     * Perbarui Data Supplier.
     */
    public function updateSupplier(Request $request, string $id): RedirectResponse
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:suppliers,code,' . $supplier->id,
            'name' => 'required|string|max:150',
            'is_pkp' => 'nullable|boolean',
            'npwp' => 'nullable|string|max:50',
            'tax_name' => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'term_of_payment_days' => 'nullable|integer|min:0',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_no' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
        ]);

        $supplier->update([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'is_pkp' => $request->boolean('is_pkp'),
            'npwp' => $request->npwp ? trim($request->npwp) : null,
            'tax_name' => $request->tax_name ? trim($request->tax_name) : null,
            'contact_person' => trim($request->contact_person ?: '-'),
            'phone' => trim($request->phone ?: '-'),
            'email' => $request->email ? trim($request->email) : null,
            'address' => trim($request->address ?: '-'),
            'term_of_payment_days' => (int) ($request->term_of_payment_days ?? 0),
            'bank_name' => $request->bank_name ? trim($request->bank_name) : null,
            'bank_account_no' => $request->bank_account_no ? trim($request->bank_account_no) : null,
            'bank_account_holder' => $request->bank_account_holder ? trim($request->bank_account_holder) : null,
        ]);

        return redirect()->route('master.suppliers')->with('success', "Data supplier [{$supplier->name}] berhasil diperbarui.");
    }

    /**
     * Ubah Status Aktif Supplier (Toggle).
     */
    public function toggleSupplierStatus(string $id): RedirectResponse
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => !$supplier->is_active]);

        $statusStr = $supplier->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.suppliers')->with('success', "Status supplier [{$supplier->name}] berhasil {$statusStr}.");
    }

    /**
     * Master Data Jenis / Kategori Produk.
     */
    public function categories(Request $request): View
    {
        $query = ProductCategory::withCount('products');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%");
            });
        }

        $categories = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('master.categories', compact('categories'));
    }

    /**
     * Simpan Kategori Produk Baru.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:product_categories,code',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->firstOrFail();

        ProductCategory::create([
            'company_id' => $company->id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description ? trim($request->description) : null,
            'is_active' => true,
        ]);

        return redirect()->route('master.categories')->with('success', "Alhamdulillah, kategori [{$request->name}] berhasil ditambahkan.");
    }

    /**
     * Perbarui Data Kategori Produk.
     */
    public function updateCategory(Request $request, string $id): RedirectResponse
    {
        $category = ProductCategory::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:product_categories,code,' . $category->id,
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description ? trim($request->description) : null,
        ]);

        return redirect()->route('master.categories')->with('success', "Kategori [{$category->name}] berhasil diperbarui.");
    }

    /**
     * Ubah Status Aktif Kategori (Toggle).
     */
    public function toggleCategoryStatus(string $id): RedirectResponse
    {
        $category = ProductCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        $statusStr = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.categories')->with('success', "Status kategori [{$category->name}] berhasil {$statusStr}.");
    }

    /**
     * Master Data Satuan (Units of Measure).
     */
    public function units(Request $request): View
    {
        $query = Unit::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%");
            });
        }

        $units = $query->orderBy('code')->paginate(20)->withQueryString();

        return view('master.units', compact('units'));
    }

    /**
     * Simpan Satuan Baru.
     */
    public function storeUnit(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->firstOrFail();

        Unit::create([
            'company_id' => $company->id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description ? trim($request->description) : null,
            'is_active' => true,
        ]);

        return redirect()->route('master.units')->with('success', "Alhamdulillah, satuan [{$request->code}] berhasil ditambahkan.");
    }

    /**
     * Perbarui Data Satuan.
     */
    public function updateUnit(Request $request, string $id): RedirectResponse
    {
        $unit = Unit::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:20|unique:units,code,' . $unit->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $unit->update([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description ? trim($request->description) : null,
        ]);

        return redirect()->route('master.units')->with('success', "Satuan [{$unit->code}] berhasil diperbarui.");
    }

    /**
     * Ubah Status Aktif Satuan (Toggle).
     */
    public function toggleUnitStatus(string $id): RedirectResponse
    {
        $unit = Unit::findOrFail($id);
        $unit->update(['is_active' => !$unit->is_active]);

        $statusStr = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.units')->with('success', "Status satuan [{$unit->code}] berhasil {$statusStr}.");
    }

    /**
     * Master Data Akun Bank & E-Money.
     */
    public function bankAccounts(Request $request): View
    {
        $bankAccounts = BankAccount::orderBy('bank_code')->get();

        return view('master.bank_accounts', compact('bankAccounts'));
    }

    /**
     * Simpan Rekening Bank Baru.
     */
    public function storeBankAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_code' => 'required|string|max:30',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:150',
            'branch' => 'nullable|string|max:100',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->firstOrFail();

        BankAccount::create([
            'company_id' => $company->id,
            'bank_code' => strtoupper(trim($request->bank_code)),
            'bank_name' => trim($request->bank_name),
            'account_number' => trim($request->account_number),
            'account_holder' => trim($request->account_holder),
            'branch' => $request->branch ? trim($request->branch) : null,
            'is_active' => true,
        ]);

        return redirect()->route('master.bank-accounts')->with('success', "Alhamdulillah, rekening [{$request->bank_name}] berhasil ditambahkan.");
    }

    /**
     * Perbarui Data Rekening Bank.
     */
    public function updateBankAccount(Request $request, string $id): RedirectResponse
    {
        $account = BankAccount::findOrFail($id);

        $request->validate([
            'bank_code' => 'required|string|max:30',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:150',
            'branch' => 'nullable|string|max:100',
        ]);

        $account->update([
            'bank_code' => strtoupper(trim($request->bank_code)),
            'bank_name' => trim($request->bank_name),
            'account_number' => trim($request->account_number),
            'account_holder' => trim($request->account_holder),
            'branch' => $request->branch ? trim($request->branch) : null,
        ]);

        return redirect()->route('master.bank-accounts')->with('success', "Rekening [{$account->bank_name}] berhasil diperbarui.");
    }

    /**
     * Ubah Status Aktif Rekening Bank (Toggle).
     */
    public function toggleBankAccountStatus(string $id): RedirectResponse
    {
        $account = BankAccount::findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);

        $statusStr = $account->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.bank-accounts')->with('success', "Status rekening [{$account->bank_name}] berhasil {$statusStr}.");
    }

    /**
     * Kartu Stok (Stock Movement Ledger).
     */
    public function stockCard(Request $request): View
    {
        $products = Product::where('is_active', true)
            ->with(['category', 'baseUnit', 'barcodes', 'stocks'])
            ->orderBy('name')
            ->get();

        $selectedProductId = $request->input('product_id', $products->first()?->id);
        $selectedProduct = $products->firstWhere('id', $selectedProductId);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $mutations = collect();
        $openingBalance = 0.0;
        $totalIn = 0.0;
        $totalOut = 0.0;
        $closingBalance = 0.0;

        if ($selectedProduct) {
            // Saldo Awal (Mutasi sebelum $startDate)
            $lastMutationBefore = StockMutation::where('product_id', $selectedProduct->id)
                ->where('created_at', '<', $startDate)
                ->orderByDesc('created_at')
                ->first();

            $openingBalance = $lastMutationBefore ? (float) $lastMutationBefore->qty_after : 0.0;

            // Mutasi dalam periode
            $mutations = StockMutation::where('product_id', $selectedProduct->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with(['creator', 'store'])
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($mutations as $m) {
                if ($m->quantity > 0) {
                    $totalIn += (float) $m->quantity;
                } else {
                    $totalOut += abs((float) $m->quantity);
                }
            }

            $lastMutationInRange = $mutations->last();
            $closingBalance = $lastMutationInRange ? (float) $lastMutationInRange->qty_after : $openingBalance;
        }

        $productsJson = $products->map(function ($p) {
            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category?->name ?? (is_string($p->category) ? $p->category : 'Sembako'),
                'unit' => $p->baseUnit?->unit_code ?? 'PCS',
                'barcode' => $p->barcodes->first()?->barcode ?? '-',
                'stock' => (float) ($p->stocks->first()?->available_quantity ?? $p->total_quantity ?? 0),
            ];
        });

        return view('master.stock_card', compact(
            'products',
            'productsJson',
            'selectedProduct',
            'selectedProductId',
            'startDate',
            'endDate',
            'openingBalance',
            'mutations',
            'totalIn',
            'totalOut',
            'closingBalance'
        ));
    }

    /**
     * Master Data Cabang, Toko & Gudang.
     */
    public function stores(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();
        $query = Store::where('company_id', $company->id);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $stores = $query->orderBy('type')->orderBy('name')->paginate(20)->withQueryString();

        return view('master.stores', compact('stores'));
    }

    /**
     * Simpan Toko / Gudang Baru.
     */
    public function storeStore(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:stores,code',
            'name' => 'required|string|max:150',
            'type' => 'required|in:STORE,WAREHOUSE',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();

        Store::create([
            'company_id' => $company->id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'type' => $request->type,
            'phone' => $request->phone ? trim($request->phone) : null,
            'address' => $request->address ? trim($request->address) : null,
            'is_active' => true,
        ]);

        return redirect()->route('master.stores')->with('success', "Cabang / Gudang [{$request->name}] berhasil ditambahkan.");
    }

    /**
     * Perbarui Data Toko / Gudang.
     */
    public function updateStore(Request $request, string $id): RedirectResponse
    {
        $store = Store::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:stores,code,' . $store->id,
            'name' => 'required|string|max:150',
            'type' => 'required|in:STORE,WAREHOUSE',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        $store->update([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'type' => $request->type,
            'phone' => $request->phone ? trim($request->phone) : null,
            'address' => $request->address ? trim($request->address) : null,
        ]);

        return redirect()->route('master.stores')->with('success', "Data cabang / gudang [{$store->name}] berhasil diperbarui.");
    }

    /**
     * Ubah Status Aktif Toko / Gudang (Toggle).
     */
    public function toggleStoreStatus(string $id): RedirectResponse
    {
        $store = Store::findOrFail($id);
        $store->update(['is_active' => !$store->is_active]);

        $statusStr = $store->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.stores')->with('success', "Status cabang / gudang [{$store->name}] berhasil {$statusStr}.");
    }

    /**
     * Master Data Tingkatan / Tier Pelanggan (Customer Tiers).
     */
    public function customerTiers(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();
        $query = CustomerTier::where('company_id', $company->id)->withCount('customers');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $tiers = $query->orderByRaw("CASE 
                WHEN code = 'RETAIL' THEN 1 
                WHEN code = 'GROSIR' THEN 2 
                WHEN code = 'AGEN' THEN 3 
                WHEN code = 'DISTRIBUTOR' THEN 4 
                WHEN code = 'MEMBER' THEN 5 
                WHEN code = 'KHUSUS' THEN 6 
                ELSE 7 END")
            ->paginate(20)
            ->withQueryString();

        return view('master.customer_tiers', compact('tiers'));
    }

    /**
     * Simpan Tier Pelanggan Baru.
     */
    public function storeCustomerTier(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:customer_tiers,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'default_margin_percentage' => 'required|numeric|min:0|max:1000',
            'point_multiplier' => 'nullable|numeric|min:0',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();

        CustomerTier::create([
            'company_id' => $company->id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description ? trim($request->description) : null,
            'default_margin_percentage' => (float) $request->default_margin_percentage,
            'point_multiplier' => (float) ($request->point_multiplier ?? 1.00),
            'is_default' => false,
            'is_active' => true,
        ]);

        return redirect()->route('master.customer-tiers')->with('success', "Tier pelanggan [{$request->name}] berhasil ditambahkan.");
    }

    /**
     * Perbarui Tier Pelanggan.
     */
    public function updateCustomerTier(Request $request, string $id): RedirectResponse
    {
        $tier = CustomerTier::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:customer_tiers,code,' . $tier->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'default_margin_percentage' => 'required|numeric|min:0|max:1000',
            'point_multiplier' => 'nullable|numeric|min:0',
        ]);

        $tier->update([
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description ? trim($request->description) : null,
            'default_margin_percentage' => (float) $request->default_margin_percentage,
            'point_multiplier' => (float) ($request->point_multiplier ?? 1.00),
        ]);

        return redirect()->route('master.customer-tiers')->with('success', "Data tier pelanggan [{$tier->name}] berhasil diperbarui.");
    }

    /**
     * Ubah Status Aktif Tier Pelanggan (Toggle).
     */
    public function toggleCustomerTierStatus(string $id): RedirectResponse
    {
        $tier = CustomerTier::findOrFail($id);
        $tier->update(['is_active' => !$tier->is_active]);

        $statusStr = $tier->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master.customer-tiers')->with('success', "Status tier pelanggan [{$tier->name}] berhasil {$statusStr}.");
    }

    /**
     * Tampilan Pusat Cetak Label Barcode & Price Tag Rak Gondola.
     */
    public function barcodePrint(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $categories = ProductCategory::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $customerTiers = CustomerTier::where('company_id', $company->id)->where('is_active', true)->get();

        $query = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->with([
                'category',
                'units' => fn($u) => $u->where('is_active', true)->orderBy('conversion_factor'),
                'barcodes',
                'prices.customerTier',
            ]);

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->input('search')) {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('name', $likeOp, "%{$search}%")
                    ->orWhere('sku', $likeOp, "%{$search}%")
                    ->orWhereHas('barcodes', function ($bq) use ($search, $likeOp) {
                        $bq->where('barcode', $likeOp, "%{$search}%");
                    });
            });
        }

        $products = $query->orderBy('name')->limit(100)->get();

        // Parameter jika ada produk terpilih langsung dari tabel master item
        $preselectedProduct = null;
        if ($productId = $request->input('product_id')) {
            $preselectedProduct = Product::with([
                'category',
                'units' => fn($u) => $u->where('is_active', true)->orderBy('conversion_factor'),
                'barcodes',
                'prices.customerTier',
            ])->find($productId);
        }

        return view('master.barcode_print', compact('products', 'categories', 'stores', 'customerTiers', 'preselectedProduct'));
    }

    /**
     * Export Seluruh Master Produk & Harga ke Format CSV Excel.
     */
    public function exportProducts(Request $request): StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();
        $tierRetail = CustomerTier::where('code', 'RETAIL')->first();
        $tierGrosir = CustomerTier::where('code', 'GROSIR')->first();

        $filename = 'master_produk_sigma_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($company, $tierRetail, $tierGrosir) {
            $handle = fopen('php://output', 'w');
            // Menulis UTF-8 BOM agar Excel Windows langsung membaca karakter tanpa corrupt
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'SKU',
                'NAMA_PRODUK',
                'KATEGORI',
                'SATUAN_DASAR',
                'BARCODE_UTAMA',
                'HARGA_BELI_HPP',
                'HARGA_JUAL_RETAIL',
                'HARGA_JUAL_GROSIR',
                'MIN_STOCK_ALERT',
                'LOKASI_RAK',
                'STATUS_AKTIF',
            ]);

            Product::where('company_id', $company->id)
                ->with(['category', 'baseUnit', 'barcodes', 'prices.customerTier'])
                ->orderBy('name')
                ->chunk(150, function ($products) use ($handle, $tierRetail, $tierGrosir) {
                    foreach ($products as $prod) {
                        $barcode = $prod->barcodes->firstWhere('is_primary', true)?->barcode 
                            ?? $prod->barcodes->first()?->barcode 
                            ?? '';
                        $unitCode = $prod->baseUnit?->unit_code ?? 'PCS';

                        $priceRetail = 0;
                        $priceGrosir = 0;
                        foreach ($prod->prices as $p) {
                            if ($tierRetail && $p->customer_tier_id === $tierRetail->id && $p->min_quantity == 1) {
                                $priceRetail = (float) $p->price;
                            }
                            if ($tierGrosir && $p->customer_tier_id === $tierGrosir->id && $p->min_quantity == 1) {
                                $priceGrosir = (float) $p->price;
                            }
                        }

                        fputcsv($handle, [
                            $prod->sku,
                            $prod->name,
                            $prod->category?->name ?? $prod->category ?? '',
                            $unitCode,
                            $barcode,
                            $prod->cost_price,
                            $priceRetail,
                            $priceGrosir,
                            $prod->min_stock_alert,
                            $prod->rack_location ?? '',
                            $prod->is_active ? 'AKTIF' : 'NONAKTIF',
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download Template File CSV Excel untuk Import Produk Massal.
     */
    public function downloadProductTemplate(): StreamedResponse
    {
        $filename = 'template_import_produk_sigma.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'SKU',
                'NAMA_PRODUK',
                'KATEGORI',
                'SATUAN_DASAR',
                'BARCODE_UTAMA',
                'HARGA_BELI_HPP',
                'HARGA_JUAL_RETAIL',
                'HARGA_JUAL_GROSIR',
                'MIN_STOCK_ALERT',
                'LOKASI_RAK',
                'STATUS_AKTIF',
            ]);

            // Contoh Data 1
            fputcsv($handle, [
                'SBN-LUX-001',
                'Sabun Mandi Lux Velvet 85g',
                'Personal Care',
                'PCS',
                '8991234567890',
                '4200',
                '5500',
                '5000',
                '10',
                'RAK-B02-01',
                'AKTIF',
            ]);

            // Contoh Data 2
            fputcsv($handle, [
                'MIE-SDA-002',
                'Mie Sedaap Goreng 90g',
                'Makanan & Minuman',
                'BKS',
                '8992345678901',
                '2800',
                '3500',
                '3200',
                '24',
                'RAK-A01-04',
                'AKTIF',
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import Produk Massal dari File CSV Excel.
     */
    public function importProducts(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();
        $store = Store::where('code', 'STR-001')->first() ?? Store::firstOrFail();
        $tierRetail = CustomerTier::where('code', 'RETAIL')->first();
        $tierGrosir = CustomerTier::where('code', 'GROSIR')->first();

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return redirect()->back()->with('error', 'Gagal membuka file dokumen CSV.');
        }

        // Baca baris pertama (Header) dan buang UTF-8 BOM jika ada
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return redirect()->back()->with('error', 'File yang diupload kosong.');
        }

        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $rawHeaders = str_getcsv(trim($firstLine), $delimiter);

        // Petakan indeks kolom header secara dinamis
        $colIndex = [];
        foreach ($rawHeaders as $idx => $name) {
            $cleanName = strtoupper(trim(str_replace([' ', '-', '_'], '', $name)));
            $colIndex[$cleanName] = $idx;
        }

        $getCol = function ($keys, $default = null) use ($colIndex) {
            foreach ($keys as $k) {
                if (isset($colIndex[$k])) {
                    return $colIndex[$k];
                }
            }
            return $default;
        };

        $idxSku = $getCol(['SKU', 'KODE', 'KODEBARANG', 'KODEITEM'], 0);
        $idxName = $getCol(['NAMAPRODUK', 'NAMA', 'NAMABARANG', 'NAMAITEM'], 1);
        $idxCategory = $getCol(['KATEGORI', 'CATEGORY', 'KELOMPOK'], 2);
        $idxUnit = $getCol(['SATUANDASAR', 'SATUAN', 'UOM', 'UNIT'], 3);
        $idxBarcode = $getCol(['BARCODEUTAMA', 'BARCODE', 'KODEBARCODE'], 4);
        $idxHpp = $getCol(['HARGABELIHPP', 'HPP', 'HARGABELI', 'COSTPRICE'], 5);
        $idxRetail = $getCol(['HARGAJUALRETAIL', 'HARGARETAIL', 'HARGAJUAL', 'RETAIL'], 6);
        $idxGrosir = $getCol(['HARGAJUALGROSIR', 'HARGAGROSIR', 'GROSIR'], 7);
        $idxMinStock = $getCol(['MINSTOCKALERT', 'MINSTOCK', 'STOKMINIMAL', 'MINSTOK'], 8);
        $idxRack = $getCol(['LOKASIRAK', 'RAK', 'RACKLOCATION'], 9);
        $idxActive = $getCol(['STATUSAKTIF', 'STATUS', 'ISACTIVE', 'AKTIF'], 10);

        $rowCount = 0;
        $successCount = 0;
        $errors = [];

        DB::transaction(function () use (
            $handle, $delimiter, $company, $store, $tierRetail, $tierGrosir,
            $idxSku, $idxName, $idxCategory, $idxUnit, $idxBarcode, $idxHpp, $idxRetail, $idxGrosir, $idxMinStock, $idxRack, $idxActive,
            &$rowCount, &$successCount, &$errors
        ) {
            while (($row = fgetcsv($handle, 2000, $delimiter)) !== false) {
                $rowCount++;
                if (empty(array_filter($row))) {
                    continue;
                }

                $sku = isset($row[$idxSku]) ? strtoupper(trim($row[$idxSku])) : '';
                $name = isset($row[$idxName]) ? trim($row[$idxName]) : '';
                $categoryName = isset($row[$idxCategory]) ? trim($row[$idxCategory]) : '';
                $unitCode = isset($row[$idxUnit]) ? strtoupper(trim($row[$idxUnit])) : 'PCS';
                $barcode = isset($row[$idxBarcode]) ? trim($row[$idxBarcode]) : '';
                
                $cleanNumber = function ($val) {
                    if (!$val) return 0.0;
                    $v = str_replace(['Rp', 'rp', ' ', '.'], '', $val);
                    $v = str_replace(',', '.', $v);
                    return (float) $v;
                };

                $costPrice = isset($row[$idxHpp]) ? $cleanNumber($row[$idxHpp]) : 0.0;
                $priceRetail = isset($row[$idxRetail]) ? $cleanNumber($row[$idxRetail]) : 0.0;
                $priceGrosir = isset($row[$idxGrosir]) ? $cleanNumber($row[$idxGrosir]) : 0.0;
                $minStock = isset($row[$idxMinStock]) && is_numeric($row[$idxMinStock]) ? (float)$row[$idxMinStock] : 5.0;
                $rackLocation = isset($row[$idxRack]) ? trim($row[$idxRack]) : null;

                $statusStr = isset($row[$idxActive]) ? strtoupper(trim($row[$idxActive])) : 'AKTIF';
                $isActive = !in_array($statusStr, ['NONAKTIF', 'NON-AKTIF', 'TIDAK', '0', 'FALSE', 'OFF']);

                if (empty($sku) || empty($name)) {
                    $errors[] = "Baris #{$rowCount}: SKU & Nama Produk tidak boleh kosong.";
                    continue;
                }

                // 1. Kategori
                $categoryId = null;
                if (!empty($categoryName)) {
                    $catCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $categoryName), 0, 10));
                    $cat = ProductCategory::firstOrCreate(
                        ['company_id' => $company->id, 'name' => $categoryName],
                        ['code' => $catCode ?: 'KAT-01', 'is_active' => true]
                    );
                    $categoryId = $cat->id;
                }

                // 2. Unit Satuan
                if (!empty($unitCode)) {
                    Unit::firstOrCreate(
                        ['company_id' => $company->id, 'code' => $unitCode],
                        ['name' => $unitCode, 'is_active' => true]
                    );
                }

                // 3. Upsert Produk
                $product = Product::updateOrCreate(
                    ['company_id' => $company->id, 'sku' => $sku],
                    [
                        'name' => $name,
                        'category_id' => $categoryId,
                        'category' => $categoryName,
                        'cost_price' => $costPrice,
                        'min_stock_alert' => $minStock,
                        'rack_location' => $rackLocation,
                        'is_active' => $isActive,
                        'is_point_of_sale' => true,
                    ]
                );

                // 4. Upsert Product Unit (Base Unit)
                $pUnit = ProductUnit::updateOrCreate(
                    ['company_id' => $company->id, 'product_id' => $product->id, 'unit_code' => $unitCode],
                    [
                        'unit_name' => $unitCode,
                        'conversion_factor' => 1.00,
                        'is_base_unit' => true,
                        'is_active' => true,
                    ]
                );

                // 5. Upsert Barcode
                if (!empty($barcode)) {
                    ProductBarcode::updateOrCreate(
                        ['company_id' => $company->id, 'barcode' => $barcode],
                        [
                            'product_id' => $product->id,
                            'product_unit_id' => $pUnit->id,
                            'is_primary' => true,
                        ]
                    );
                }

                // 6. Upsert Harga Retail
                if ($tierRetail && $priceRetail > 0) {
                    ProductPrice::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'product_id' => $product->id,
                            'product_unit_id' => $pUnit->id,
                            'customer_tier_id' => $tierRetail->id,
                            'min_quantity' => 1.00,
                            'store_id' => null,
                        ],
                        [
                            'price' => $priceRetail,
                            'is_active' => true,
                        ]
                    );
                }

                // 7. Upsert Harga Grosir
                if ($tierGrosir && $priceGrosir > 0) {
                    ProductPrice::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'product_id' => $product->id,
                            'product_unit_id' => $pUnit->id,
                            'customer_tier_id' => $tierGrosir->id,
                            'min_quantity' => 1.00,
                            'store_id' => null,
                        ],
                        [
                            'price' => $priceGrosir,
                            'is_active' => true,
                        ]
                    );
                }

                // 8. Inisialisasi Stok Produk jika belum ada
                ProductStock::firstOrCreate(
                    ['company_id' => $company->id, 'store_id' => $store->id, 'product_id' => $product->id],
                    ['quantity' => 0.00, 'reserved_quantity' => 0.00, 'quarantined_quantity' => 0.00]
                );

                $successCount++;
            }
        });

        fclose($handle);

        $msg = "Alhamdulillah, berhasil mengimpor {$successCount} produk.";
        if (!empty($errors)) {
            $msg .= " Peringatan: " . implode('; ', array_slice($errors, 0, 3));
        }

        return redirect()->route('master.products')->with('success', $msg);
    }
}
