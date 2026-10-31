<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Tampilkan halaman Pengaturan Umum & Desktop Wallpaper.
     */
    public function index(): View
    {
        $company = Company::first();
        $store = Store::where('code', 'STR-001')->first() ?? Store::first();

        return view('settings.general', compact('company', 'store'));
    }

    /**
     * Simpan pembaruan Pengaturan Umum & Profil Perusahaan, Pajak, Logo, dan Wallpaper.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $company = Company::firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:500',
            'receipt_footer' => 'nullable|string|max:500',
            'wallpaper' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Maks 5MB
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048', // Maks 2MB
            'tax_id' => 'nullable|string|max:50',
            'tax_name' => 'nullable|string|max:150',
            'tax_address' => 'nullable|string|max:500',
            'is_tax_registered' => 'nullable|boolean',
            'default_tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_mode' => 'nullable|string|in:INCLUSIVE,EXCLUSIVE,NON_TAX',
            'rounding_enabled' => 'nullable|boolean',
            'rounding_step' => 'nullable|integer|in:50,100,500,1000',
            'rounding_mode' => 'nullable|string|in:ROUND_NEAREST,ROUND_UP,ROUND_DOWN',
            'rounding_applies_to' => 'nullable|string|in:CASH_ONLY,ALL',
        ], [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'wallpaper.image' => 'File yang diunggah harus berupa gambar (JPG, PNG, atau WEBP).',
            'wallpaper.max' => 'Ukuran gambar wallpaper maksimal 5 MB.',
            'logo.image' => 'File logo yang diunggah harus berupa gambar (JPG, PNG, WEBP, atau SVG).',
            'logo.max' => 'Ukuran file logo maksimal 2 MB.',
        ]);

        DB::transaction(function () use ($request, $company, $validated) {
            // Upload Wallpaper Desktop
            if ($request->hasFile('wallpaper')) {
                $file = $request->file('wallpaper');
                $destinationPath = public_path('uploads'.DIRECTORY_SEPARATOR.'wallpapers');

                if (! File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                if ($company->wallpaper_path && File::exists(public_path($company->wallpaper_path))) {
                    File::delete(public_path($company->wallpaper_path));
                }

                $filename = 'wallpaper_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                try {
                    $file->move($destinationPath, $filename);
                } catch (\Exception $e) {
                    File::put($destinationPath.DIRECTORY_SEPARATOR.$filename, File::get($file->getRealPath()));
                }

                $company->wallpaper_path = 'uploads/wallpapers/'.$filename;
            }

            // Upload Logo Resmi Bisnis
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $destinationPath = public_path('uploads'.DIRECTORY_SEPARATOR.'logos');

                if (! File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                if ($company->logo_path && File::exists(public_path($company->logo_path))) {
                    File::delete(public_path($company->logo_path));
                }

                $filename = 'logo_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                try {
                    $file->move($destinationPath, $filename);
                } catch (\Exception $e) {
                    File::put($destinationPath.DIRECTORY_SEPARATOR.$filename, File::get($file->getRealPath()));
                }

                $company->logo_path = 'uploads/logos/'.$filename;
            }

            // Identitas Bisnis
            $company->name = $validated['name'];
            $company->phone = $validated['phone'] ?? null;
            $company->whatsapp = $validated['whatsapp'] ?? null;
            $company->email = $validated['email'] ?? null;
            $company->website = $validated['website'] ?? null;
            $company->address = $validated['address'] ?? null;
            $company->receipt_footer = $validated['receipt_footer'] ?? $company->receipt_footer;

            // Pajak & Faktur Pajak
            $company->is_tax_registered = $request->boolean('is_tax_registered');
            $company->tax_id = $validated['tax_id'] ?? null;
            $company->tax_name = $validated['tax_name'] ?? null;
            $company->tax_address = $validated['tax_address'] ?? null;
            $company->default_tax_rate = isset($validated['default_tax_rate']) ? (float) $validated['default_tax_rate'] : 11.00;
            $company->tax_mode = $validated['tax_mode'] ?? 'INCLUSIVE';

            // Pembulatan Transaksi
            $company->rounding_enabled = $request->boolean('rounding_enabled');
            if (isset($validated['rounding_step'])) {
                $company->rounding_step = (int) $validated['rounding_step'];
            }
            if (isset($validated['rounding_mode'])) {
                $company->rounding_mode = $validated['rounding_mode'];
            }
            if (isset($validated['rounding_applies_to'])) {
                $company->rounding_applies_to = $validated['rounding_applies_to'];
            }

            $company->save();
        });

        return redirect()->route('settings.general')->with('success', 'Alhamdulillah, profil perusahaan dan metadata faktur pajak berhasil diperbarui!');
    }

    /**
     * Kembalikan logo perusahaan ke bawaan / hapus logo.
     */
    public function resetLogo(): RedirectResponse
    {
        $company = Company::firstOrFail();

        DB::transaction(function () use ($company) {
            if ($company->logo_path && File::exists(public_path($company->logo_path))) {
                File::delete(public_path($company->logo_path));
            }

            $company->logo_path = null;
            $company->save();
        });

        return redirect()->route('settings.general')->with('success', 'Logo perusahaan berhasil dihapus!');
    }

    /**
     * Kembalikan wallpaper ke gambar standar Endogan bawaan sistem.
     */
    public function resetWallpaper(): RedirectResponse
    {
        $company = Company::firstOrFail();

        DB::transaction(function () use ($company) {
            if ($company->wallpaper_path && File::exists(public_path($company->wallpaper_path))) {
                File::delete(public_path($company->wallpaper_path));
            }

            $company->wallpaper_path = null;
            $company->save();
        });

        return redirect()->route('settings.general')->with('success', 'Wallpaper desktop berhasil dikembalikan ke tampilan default Endogan!');
    }

    /**
     * Tampilkan halaman Konfigurasi Printer Struk Thermal & Format Layout (58mm / 80mm).
     */
    public function printer(): View
    {
        $company = Company::firstOrFail();
        $store = Store::where('code', 'STR-001')->first() ?? Store::first();

        // Default value fill jika belum ada
        if (empty($company->receipt_footer_line1)) {
            $company->receipt_footer_line1 = $company->receipt_footer ?: "Terima Kasih Atas Kunjungan Anda\nBarang yang dibeli tidak dapat ditukar/dikembalikan";
        }

        return view('settings.printer', compact('company', 'store'));
    }

    /**
     * Simpan pembaruan Konfigurasi Printer Struk Thermal & Auto-Cutter.
     */
    public function updatePrinter(Request $request): RedirectResponse
    {
        $company = Company::firstOrFail();

        $validated = $request->validate([
            'receipt_paper_size' => 'required|in:58,80',
            'receipt_font_family' => 'required|in:monospace,courier,sans-serif',
            'receipt_font_size' => 'required|in:compact,normal,large',
            'receipt_item_layout' => 'required|in:compact_double,single_line',
            'receipt_show_logo' => 'nullable|boolean',
            'receipt_header_line1' => 'nullable|string|max:150',
            'receipt_header_line2' => 'nullable|string|max:150',
            'receipt_footer_line1' => 'nullable|string|max:500',
            'receipt_footer_line2' => 'nullable|string|max:500',
            'receipt_show_customer' => 'nullable|boolean',
            'receipt_show_cashier' => 'nullable|boolean',
            'receipt_show_barcode' => 'nullable|boolean',
            'receipt_auto_print' => 'nullable|boolean',
            'receipt_auto_cut' => 'nullable|boolean',
            'receipt_open_drawer' => 'nullable|boolean',
            'receipt_print_copies' => 'required|integer|in:1,2',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::transaction(function () use ($request, $company, $validated) {
            // Upload Logo Struk Toko
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $destinationPath = public_path('uploads'.DIRECTORY_SEPARATOR.'logos');

                if (! File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                if ($company->logo_path && File::exists(public_path($company->logo_path))) {
                    File::delete(public_path($company->logo_path));
                }

                $filename = 'logo_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                try {
                    $file->move($destinationPath, $filename);
                } catch (\Exception $e) {
                    File::put($destinationPath.DIRECTORY_SEPARATOR.$filename, File::get($file->getRealPath()));
                }

                $company->logo_path = 'uploads/logos/'.$filename;
            }

            // Hapus logo jika dicentang
            if ($request->boolean('remove_logo') && $company->logo_path) {
                if (File::exists(public_path($company->logo_path))) {
                    File::delete(public_path($company->logo_path));
                }
                $company->logo_path = null;
            }

            $company->receipt_paper_size = $validated['receipt_paper_size'];
            $company->receipt_font_family = $validated['receipt_font_family'];
            $company->receipt_font_size = $validated['receipt_font_size'];
            $company->receipt_item_layout = $validated['receipt_item_layout'];
            $company->receipt_show_logo = $request->boolean('receipt_show_logo');
            $company->receipt_header_line1 = $validated['receipt_header_line1'] ?? null;
            $company->receipt_header_line2 = $validated['receipt_header_line2'] ?? null;
            $company->receipt_footer_line1 = $validated['receipt_footer_line1'] ?? null;
            $company->receipt_footer_line2 = $validated['receipt_footer_line2'] ?? null;
            // Sinkronkan juga ke receipt_footer lama untuk backward-compatibility
            $company->receipt_footer = $company->receipt_footer_line1;
            $company->receipt_show_customer = $request->boolean('receipt_show_customer');
            $company->receipt_show_cashier = $request->boolean('receipt_show_cashier');
            $company->receipt_show_barcode = $request->boolean('receipt_show_barcode');
            $company->receipt_auto_print = $request->boolean('receipt_auto_print');
            $company->receipt_auto_cut = $request->boolean('receipt_auto_cut');
            $company->receipt_open_drawer = $request->boolean('receipt_open_drawer');
            $company->receipt_print_copies = (int) $validated['receipt_print_copies'];

            $company->save();
        });

        return redirect()->route('settings.printer')->with('success', 'Konfigurasi Printer Struk & Layout Kertas berhasil disimpan!');
    }

    /**
     * Cetak Struk Uji Coba Langsung (Test Print Dialog).
     */
    public function testPrint(Request $request): View
    {
        $company = Company::firstOrFail();
        $store = Store::where('code', 'STR-001')->first() ?? Store::first();

        // Buat dummy mock transaction object untuk keperluan preview/test print
        $mockTransaction = (object) [
            'id' => '00000000-0000-0000-0000-000000000000',
            'invoice_number' => 'POS-'.date('Ymd').'-9999',
            'created_at' => now(),
            'company' => $company,
            'store' => $store,
            'cashier' => (object) ['name' => auth()->user()?->name ?? 'Kasir Percobaan'],
            'shift' => null,
            'customerTier' => (object) ['name' => 'Umum / Retail'],
            'customer' => (object) ['code' => 'CUST-001', 'name' => 'Bpk. Hendrawan Santoso'],
            'items' => collect([
                (object) [
                    'product' => (object) ['name' => 'Kopi Kapal Api Spesial Mix 24g', 'sku' => 'BRG-001'],
                    'unit' => (object) ['unit_code' => 'PCS'],
                    'quantity' => 2,
                    'unit_price' => 3500,
                    'total_price' => 7000,
                ],
                (object) [
                    'product' => (object) ['name' => 'Gula Pasir Gulaku Premium 1kg', 'sku' => 'BRG-002'],
                    'unit' => (object) ['unit_code' => 'BKS'],
                    'quantity' => 1,
                    'unit_price' => 17500,
                    'total_price' => 17500,
                ],
                (object) [
                    'product' => (object) ['name' => 'Minyak Goreng Bimoli 2L Pouch', 'sku' => 'BRG-003'],
                    'unit' => (object) ['unit_code' => 'PCH'],
                    'quantity' => 1,
                    'unit_price' => 36500,
                    'total_price' => 36500,
                ],
            ]),
            'subtotal' => 61000,
            'discount_amount' => 1000,
            'voucher_discount' => 0,
            'voucher' => null,
            'tax_amount' => 0,
            'rounding_amount' => 0,
            'grand_total' => 60000,
            'payment_method' => 'CASH',
            'paid_amount' => 100000,
            'change_amount' => 40000,
            'credit_amount' => 0,
            'split_payment_method' => null,
            'split_cash_amount' => 0,
            'split_non_cash_amount' => 0,
            'split_non_cash_method' => null,
            'bank_name' => null,
            'bank_account_number' => null,
            'reprint_count' => 0,
            'last_reprinted_at' => null,
            'notes' => null,
        ];

        $paperSize = $request->query('paper', $company->receipt_paper_size ?? '58');

        return view('pos.receipt', [
            'transaction' => $mockTransaction,
            'paperSize' => $paperSize,
            'company' => $company,
            'store' => $store,
            'isTestPrint' => true,
        ]);
    }

    /**
     * Tampilkan halaman Pengaturan Operasional Kasir, Modal Awal & Peringatan Kas Drop.
     */
    public function cashier(): View
    {
        $company = Company::firstOrFail();
        $store = Store::where('code', 'STR-001')->first() ?? Store::first();

        return view('settings.cashier', compact('company', 'store'));
    }

    /**
     * Simpan pembaruan Pengaturan Operasional Kasir.
     */
    public function updateCashier(Request $request): RedirectResponse
    {
        $company = Company::firstOrFail();

        $validated = $request->validate([
            'cashier_default_starting_cash' => 'required|numeric|min:0',
            'cashier_max_starting_cash' => 'required|numeric|min:0|gte:cashier_default_starting_cash',
            'cashier_require_starting_cash' => 'nullable|boolean',
            'cashier_max_change_limit' => 'required|numeric|min:0',
            'cashier_cash_drop_threshold' => 'required|numeric|min:0',
            'cashier_cash_drop_warning_enabled' => 'nullable|boolean',
            'cashier_max_discrepancy_tolerance' => 'required|numeric|min:0',
            'cashier_allow_pending_transaction' => 'nullable|boolean',
            'cashier_max_pending_transactions' => 'required|integer|min:1|max:20',
            'cashier_quick_cash_nominal' => 'required|string|max:100',
        ], [
            'cashier_default_starting_cash.required' => 'Modal awal standar wajib diisi.',
            'cashier_max_starting_cash.gte' => 'Batas maksimal modal awal harus lebih besar atau sama dengan modal awal standar.',
            'cashier_cash_drop_threshold.required' => 'Ambang batas kas drop wajib diisi.',
        ]);

        DB::transaction(function () use ($company, $validated, $request) {
            $company->cashier_default_starting_cash = (float) $validated['cashier_default_starting_cash'];
            $company->cashier_max_starting_cash = (float) $validated['cashier_max_starting_cash'];
            $company->cashier_require_starting_cash = $request->boolean('cashier_require_starting_cash');
            $company->cashier_max_change_limit = (float) $validated['cashier_max_change_limit'];
            $company->cashier_cash_drop_threshold = (float) $validated['cashier_cash_drop_threshold'];
            $company->cashier_cash_drop_warning_enabled = $request->boolean('cashier_cash_drop_warning_enabled');
            $company->cashier_max_discrepancy_tolerance = (float) $validated['cashier_max_discrepancy_tolerance'];
            $company->cashier_allow_pending_transaction = $request->boolean('cashier_allow_pending_transaction');
            $company->cashier_max_pending_transactions = (int) $validated['cashier_max_pending_transactions'];

            // Bersihkan format nominal tombol cepat
            $nominals = array_filter(array_map('trim', explode(',', $validated['cashier_quick_cash_nominal'])));
            $company->cashier_quick_cash_nominal = implode(',', $nominals);

            $company->save();
        });

        return redirect()->route('settings.cashier')->with('success', 'Pengaturan operasional kasir, modal awal & batas kas drop berhasil disimpan!');
    }

    /**
     * Tampilkan halaman Pengaturan Cabang Toko & Gudang.
     */
    public function stores(): View
    {
        $company = Company::firstOrFail();
        $stores = Store::where('company_id', $company->id)
            ->withCount(['stocks', 'products'])
            ->orderByDesc('is_main_store')
            ->orderBy('code')
            ->get();

        return view('settings.stores', compact('company', 'stores'));
    }

    /**
     * Simpan Cabang Toko / Gudang Baru.
     */
    public function storeStore(Request $request): RedirectResponse
    {
        $company = Company::firstOrFail();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:stores,code',
            'name' => 'required|string|max:150',
            'type' => 'required|in:STORE,WAREHOUSE',
            'pic_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'is_main_store' => 'nullable|boolean',
        ], [
            'code.required' => 'Kode cabang/gudang wajib diisi.',
            'code.unique' => 'Kode cabang/gudang sudah digunakan.',
            'name.required' => 'Nama cabang/gudang wajib diisi.',
        ]);

        DB::transaction(function () use ($company, $validated, $request) {
            $isMain = $request->boolean('is_main_store');

            // Jika cabang ini diatur sebagai Toko Utama, jadikan cabang lainnya non-utama
            if ($isMain) {
                Store::where('company_id', $company->id)->update(['is_main_store' => false]);
            } else {
                // Jika belum ada toko utama sama sekali, jadikan yang pertama sebagai toko utama
                $hasMain = Store::where('company_id', $company->id)->where('is_main_store', true)->exists();
                if (! $hasMain) {
                    $isMain = true;
                }
            }

            Store::create([
                'company_id' => $company->id,
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'type' => $validated['type'],
                'pic_name' => $validated['pic_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'is_active' => true,
                'is_main_store' => $isMain,
            ]);
        });

        return redirect()->route('settings.stores.index')->with('success', "Cabang / Gudang [{$validated['name']}] berhasil ditambahkan!");
    }

    /**
     * Perbarui Data Cabang Toko / Gudang.
     */
    public function updateStore(Request $request, string $id): RedirectResponse
    {
        $company = Company::firstOrFail();
        $store = Store::where('company_id', $company->id)->findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:stores,code,'.$store->id,
            'name' => 'required|string|max:150',
            'type' => 'required|in:STORE,WAREHOUSE',
            'pic_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'is_main_store' => 'nullable|boolean',
        ], [
            'code.required' => 'Kode cabang/gudang wajib diisi.',
            'code.unique' => 'Kode cabang/gudang sudah digunakan oleh cabang lain.',
            'name.required' => 'Nama cabang/gudang wajib diisi.',
        ]);

        DB::transaction(function () use ($company, $store, $validated, $request) {
            $isMain = $request->boolean('is_main_store');

            if ($isMain) {
                Store::where('company_id', $company->id)->where('id', '!=', $store->id)->update(['is_main_store' => false]);
            }

            $store->update([
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'type' => $validated['type'],
                'pic_name' => $validated['pic_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'is_main_store' => $isMain ? true : $store->is_main_store,
            ]);
        });

        return redirect()->route('settings.stores.index')->with('success', "Data Cabang / Gudang [{$store->name}] berhasil diperbarui!");
    }

    /**
     * Aktifkan / Nonaktifkan Status Operasional Cabang.
     */
    public function toggleStoreStatus(string $id): RedirectResponse
    {
        $company = Company::firstOrFail();
        $store = Store::where('company_id', $company->id)->findOrFail($id);

        if ($store->is_main_store && $store->is_active) {
            return redirect()->route('settings.stores.index')->with('error', 'Cabang Utama / Head Store tidak dapat dinonaktifkan.');
        }

        $store->update(['is_active' => ! $store->is_active]);
        $statusStr = $store->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return redirect()->route('settings.stores.index')->with('success', "Status operasional Cabang [{$store->name}] berhasil {$statusStr}!");
    }

    /**
     * Jadikan Cabang sebagai Toko Utama / Kantor Pusat.
     */
    public function setMainStore(string $id): RedirectResponse
    {
        $company = Company::firstOrFail();
        $store = Store::where('company_id', $company->id)->findOrFail($id);

        if (! $store->is_active) {
            return redirect()->route('settings.stores.index')->with('error', 'Cabang yang non-aktif tidak dapat dijadikan Toko Utama.');
        }

        DB::transaction(function () use ($company, $store) {
            Store::where('company_id', $company->id)->update(['is_main_store' => false]);
            $store->update(['is_main_store' => true]);
        });

        return redirect()->route('settings.stores.index')->with('success', "Cabang [{$store->name}] sekarang ditetapkan sebagai Toko Utama / Kantor Pusat!");
    }
}
