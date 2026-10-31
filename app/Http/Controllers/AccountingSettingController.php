<?php

namespace App\Http\Controllers;

use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingSettingController extends Controller
{
    /**
     * Tampilkan Halaman Setting Perkiraan / Pemetaan Akun Otomatis (Fitur No. 13 IPOS 5 / Ketoko).
     */
    public function index(): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();

        $accounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $settings = AccountingSetting::where('company_id', $company->id)
            ->get()
            ->keyBy('setting_key');

        $groups = [
            'SALES' => [
                'title' => '🛒 Penjualan, Kasir & Piutang',
                'description' => 'Akun perkiraan yang digunakan secara otomatis saat transaksi kasir POS, penjualan kredit/tempo, diskon, dan retur penjualan.',
                'items' => [
                    [
                        'key' => 'sales_cash_account_id',
                        'label' => 'Akun Kas Utama Kasir',
                        'default_code' => '1001-001',
                        'type' => 'ASSET',
                        'notes' => 'Menampung penerimaan uang tunai kasir toko.',
                    ],
                    [
                        'key' => 'sales_receivable_account_id',
                        'label' => 'Akun Piutang Usaha (Tempo / Kredit)',
                        'default_code' => '1004-001',
                        'type' => 'ASSET',
                        'notes' => 'Mencatat saldo piutang saat pelanggan berbelanja secara kredit/tempo.',
                    ],
                    [
                        'key' => 'sales_revenue_account_id',
                        'label' => 'Akun Pendapatan Penjualan Toko',
                        'default_code' => '4001-001',
                        'type' => 'REVENUE',
                        'notes' => 'Menampung nilai penjualan barang dagang.',
                    ],
                    [
                        'key' => 'sales_discount_account_id',
                        'label' => 'Akun Potongan / Diskon Penjualan',
                        'default_code' => '4001-002',
                        'type' => 'REVENUE',
                        'notes' => 'Mencatat diskon nota, diskon promo, atau poin belanja kasir.',
                    ],
                    [
                        'key' => 'sales_return_account_id',
                        'label' => 'Akun Retur Penjualan',
                        'default_code' => '4002-001',
                        'type' => 'REVENUE',
                        'notes' => 'Mencatat pengembalian barang dari pelanggan.',
                    ],
                ],
            ],
            'PURCHASING' => [
                'title' => '📦 Pembelian & Hutang Supplier',
                'description' => 'Akun perkiraan yang digunakan saat penerimaan barang (GRN), faktur pembelian kredit (AP), dan pembayaran ke supplier.',
                'items' => [
                    [
                        'key' => 'purchase_payable_account_id',
                        'label' => 'Akun Hutang Usaha (Accounts Payable)',
                        'default_code' => '2001-001',
                        'type' => 'LIABILITY',
                        'notes' => 'Mencatat kewajiban hutang ke supplier atas pembelian tempo.',
                    ],
                    [
                        'key' => 'grni_account_id',
                        'label' => 'Akun Hutang Belum Difakturkan (GRNI)',
                        'default_code' => '2004-001',
                        'type' => 'LIABILITY',
                        'notes' => 'Jembatan sementara saat fisik barang diterima sebelum invoice supplier terbit.',
                    ],
                ],
            ],
            'INVENTORY' => [
                'title' => '🏷️ Persediaan & Beban Pokok Penjualan (HPP)',
                'description' => 'Akun neraca dan laba rugi untuk perputaran barang dagang serta perolehan HPP Moving Average.',
                'items' => [
                    [
                        'key' => 'inventory_account_id',
                        'label' => 'Akun Persediaan Barang Dagang',
                        'default_code' => '1005-001',
                        'type' => 'ASSET',
                        'notes' => 'Nilai aset stok fisik yang ada di gudang dan toko.',
                    ],
                    [
                        'key' => 'cogs_account_id',
                        'label' => 'Akun Beban Pokok Penjualan (HPP)',
                        'default_code' => '5001-001',
                        'type' => 'EXPENSE',
                        'notes' => 'Harga pokok barang yang terjual pada laporan laba rugi.',
                    ],
                ],
            ],
            'EQUITY' => [
                'title' => '🏛️ Ekuitas, Modal & Saldo Awal',
                'description' => 'Akun neraca modal pemilik, saldo awal migrasi, dan penampungan laba ditahan.',
                'items' => [
                    [
                        'key' => 'equity_opening_account_id',
                        'label' => 'Akun Modal Pemilik / Saldo Awal',
                        'default_code' => '3001-001',
                        'type' => 'EQUITY',
                        'notes' => 'Penyeimbang saldo awal persediaan, piutang, dan hutang migrasi.',
                    ],
                    [
                        'key' => 'retained_earnings_account_id',
                        'label' => 'Akun Laba Ditahan (Retained Earnings)',
                        'default_code' => '3002-001',
                        'type' => 'EQUITY',
                        'notes' => 'Akun penampung akumulasi laba bersih pada proses tutup buku tahunan.',
                    ],
                ],
            ],
        ];

        return view('accounting.settings.index', compact('company', 'accounts', 'settings', 'groups'));
    }

    /**
     * Simpan Pembaruan Setting Perkiraan / Pemetaan Akun Otomatis.
     */
    public function update(Request $request): RedirectResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required|uuid|exists:chart_of_accounts,id',
        ]);

        DB::transaction(function () use ($company, $validated) {
            foreach ($validated['settings'] as $key => $accountId) {
                AccountingSetting::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'setting_key' => $key,
                    ],
                    [
                        'account_id' => $accountId,
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return redirect()->route('accounting.settings.index')
            ->with('success', 'Setting Perkiraan (Default Linked Accounts) berhasil disimpan dan aktif digunakan oleh sistem.');
    }
}
