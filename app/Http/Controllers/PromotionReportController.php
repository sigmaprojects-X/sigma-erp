<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CustomerPointMutation;
use App\Models\PosTransaction;
use App\Models\Store;
use App\Models\Voucher;
use App\Services\CompanyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PromotionReportController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    /**
     * Tampilkan Laporan Promosi, Voucher, Poin Loyalitas & Diskon Penjualan.
     */
    public function index(Request $request): View
    {
        $company = $this->companyService->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $storeId = $request->input('store_id');
        $tab = $request->input('tab', 'all'); // 'all', 'discounts', 'vouchers', 'points'

        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        // Base Query Transaksi POS Periode
        $txQuery = PosTransaction::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('status', '!=', 'CANCELLED');

        if ($storeId) {
            $txQuery->where('store_id', $storeId);
        }

        // Metrik Agregat Utama
        $totalItemDiscounts = (float) (clone $txQuery)->sum('discount_amount');
        $totalVoucherDiscounts = (float) (clone $txQuery)->sum('voucher_discount');
        $totalPointsDiscounts = (float) (clone $txQuery)->sum('points_discount_amount');
        $totalPointsRedeemed = (int) (clone $txQuery)->sum('points_redeemed');
        $totalPointsEarned = (int) (clone $txQuery)->sum('points_earned');
        $totalCustomerSavings = $totalItemDiscounts + $totalVoucherDiscounts + $totalPointsDiscounts;

        $promoTxCount = (clone $txQuery)
            ->where(function ($q) {
                $q->where('discount_amount', '>', 0)
                    ->orWhere('voucher_discount', '>', 0)
                    ->orWhere('points_discount_amount', '>', 0);
            })
            ->count();

        $vouchersUsedCount = (clone $txQuery)->where('voucher_discount', '>', 0)->count();

        // 1. Rekapitulasi Harian
        $dailyBreakdown = (clone $txQuery)
            ->where(function ($q) {
                $q->where('discount_amount', '>', 0)
                    ->orWhere('voucher_discount', '>', 0)
                    ->orWhere('points_discount_amount', '>', 0);
            })
            ->selectRaw("
                DATE(created_at) as tx_date,
                COUNT(id) as total_tx,
                COALESCE(SUM(discount_amount), 0) as daily_discount,
                COALESCE(SUM(voucher_discount), 0) as daily_voucher,
                COALESCE(SUM(points_discount_amount), 0) as daily_points_discount,
                COALESCE(SUM(points_redeemed), 0) as daily_points_redeemed,
                COALESCE(SUM(points_earned), 0) as daily_points_earned,
                COALESCE(SUM(discount_amount + voucher_discount + points_discount_amount), 0) as total_savings
            ")
            ->groupBy('tx_date')
            ->orderByDesc('tx_date')
            ->get();

        // 2. Daftar Transaksi Terfilter Sesuai Tab
        $listQuery = (clone $txQuery)->with(['customer', 'customerTier', 'store', 'cashier']);

        if ($tab === 'discounts') {
            $listQuery->where('discount_amount', '>', 0);
        } elseif ($tab === 'vouchers') {
            $listQuery->where('voucher_discount', '>', 0);
        } elseif ($tab === 'points') {
            $listQuery->where(function ($q) {
                $q->where('points_discount_amount', '>', 0)
                    ->orWhere('points_earned', '>', 0);
            });
        } else {
            $listQuery->where(function ($q) {
                $q->where('discount_amount', '>', 0)
                    ->orWhere('voucher_discount', '>', 0)
                    ->orWhere('points_discount_amount', '>', 0);
            });
        }

        $transactions = $listQuery->latest()->paginate(25)->withQueryString();

        return view('reports.promotions.index', compact(
            'company',
            'stores',
            'startDate',
            'endDate',
            'storeId',
            'tab',
            'totalItemDiscounts',
            'totalVoucherDiscounts',
            'totalPointsDiscounts',
            'totalPointsRedeemed',
            'totalPointsEarned',
            'totalCustomerSavings',
            'promoTxCount',
            'vouchersUsedCount',
            'dailyBreakdown',
            'transactions'
        ));
    }

    /**
     * Tampilan Cetak / Print A4 Laporan Diskon, Voucher & Poin.
     */
    public function printReport(Request $request): View
    {
        $company = $this->companyService->getActiveCompany();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $storeId = $request->input('store_id');
        $selectedStore = $storeId ? Store::where('company_id', $company->id)->findOrFail($storeId) : null;

        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        $txQuery = PosTransaction::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('status', '!=', 'CANCELLED');

        if ($storeId) {
            $txQuery->where('store_id', $storeId);
        }

        $totalItemDiscounts = (float) (clone $txQuery)->sum('discount_amount');
        $totalVoucherDiscounts = (float) (clone $txQuery)->sum('voucher_discount');
        $totalPointsDiscounts = (float) (clone $txQuery)->sum('points_discount_amount');
        $totalPointsRedeemed = (int) (clone $txQuery)->sum('points_redeemed');
        $totalPointsEarned = (int) (clone $txQuery)->sum('points_earned');
        $totalCustomerSavings = $totalItemDiscounts + $totalVoucherDiscounts + $totalPointsDiscounts;

        $transactions = (clone $txQuery)
            ->where(function ($q) {
                $q->where('discount_amount', '>', 0)
                    ->orWhere('voucher_discount', '>', 0)
                    ->orWhere('points_discount_amount', '>', 0);
            })
            ->with(['customer', 'store', 'cashier'])
            ->latest()
            ->get();

        return view('reports.promotions.print', compact(
            'company',
            'selectedStore',
            'startDate',
            'endDate',
            'totalItemDiscounts',
            'totalVoucherDiscounts',
            'totalPointsDiscounts',
            'totalPointsRedeemed',
            'totalPointsEarned',
            'totalCustomerSavings',
            'transactions'
        ));
    }
}

