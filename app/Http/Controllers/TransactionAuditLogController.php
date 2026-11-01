<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionAuditLogController extends Controller
{
    /**
     * Tampilkan Catatan Transaksi Lengkap / Audit Trail Seluruh Modul (Fitur No. 14 IPOS 5 / Ketoko).
     */
    public function index(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $typeFilter = $request->input('type');
        $storeId = $request->input('store_id');
        $userId = $request->input('user_id');
        $search = trim((string) $request->input('search'));

        $query = JournalEntry::where('company_id', $company->id)
            ->whereDate('entry_date', '>=', $startDate)
            ->whereDate('entry_date', '<=', $endDate)
            ->with(['store', 'user', 'lines.account']);

        if ($typeFilter) {
            $query->where('reference_type', $typeFilter);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere(function ($sub) use ($search) {
                        $sub->whereRaw('LOWER(description) LIKE ?', ['%'.strtolower($search).'%']);
                    });
            });
        }

        $logs = $query->orderByDesc('entry_date')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        $transactionTypes = [
            'POS_SALE' => ['label' => 'Penjualan Kasir POS', 'color' => 'emerald'],
            'GOODS_RECEIPT' => ['label' => 'Penerimaan Barang (GRN)', 'color' => 'blue'],
            'GOODS_RECEIPT_LPB' => ['label' => 'Penerimaan LPB Gudang', 'color' => 'blue'],
            'THREE_WAY_MATCH' => ['label' => 'Faktur Pembelian Hutang AP', 'color' => 'indigo'],
            'CASH_IN' => ['label' => 'Kas Masuk', 'color' => 'teal'],
            'CASH_OUT' => ['label' => 'Kas Keluar (Beban)', 'color' => 'rose'],
            'CASH_TRANSFER' => ['label' => 'Kas Transfer / Bank', 'color' => 'cyan'],
            'SALES_RETURN' => ['label' => 'Retur Penjualan', 'color' => 'amber'],
            'PURCHASE_RETURN' => ['label' => 'Retur Pembelian', 'color' => 'orange'],
            'ASSET_ACQUISITION' => ['label' => 'Perolehan Aset Tetap', 'color' => 'purple'],
            'ASSET_DEPRECIATION' => ['label' => 'Penyusutan Aset', 'color' => 'violet'],
            'OPENING_BALANCE' => ['label' => 'Saldo Awal COA', 'color' => 'slate'],
            'OPENING_AR' => ['label' => 'Saldo Awal Piutang', 'color' => 'blue'],
            'OPENING_AP' => ['label' => 'Saldo Awal Hutang', 'color' => 'rose'],
            'YEAR_END_CLOSING' => ['label' => 'Tutup Buku Tahunan', 'color' => 'yellow'],
            'MANUAL' => ['label' => 'Jurnal Manual', 'color' => 'slate'],
        ];

        return view('accounting.logs.index', compact(
            'company',
            'logs',
            'stores',
            'users',
            'transactionTypes',
            'startDate',
            'endDate',
            'typeFilter',
            'storeId',
            'userId',
            'search'
        ));
    }
}
