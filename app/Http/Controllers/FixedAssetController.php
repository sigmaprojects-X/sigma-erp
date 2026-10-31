<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\FixedAsset;
use App\Models\Store;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FixedAssetController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Tampilan Daftar Aset Tetap, Nilai Buku Bersih & Ringkasan Depresiasi.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();

        $category = $request->query('category');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = FixedAsset::with(['store'])
            ->where('company_id', $company->id)
            ->latest('acquisition_date')
            ->latest('created_at');

        if ($category) {
            $query->where('category', $category);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('asset_code', 'ilike', "%{$search}%");
            });
        }

        $assets = $query->paginate(15)->withQueryString();

        // Ringkasan Finansial Aset
        $stats = [
            'total_cost' => (float) FixedAsset::where('company_id', $company->id)->where('status', '!=', 'DISPOSED')->sum('acquisition_cost'),
            'total_accumulated' => (float) FixedAsset::where('company_id', $company->id)->where('status', '!=', 'DISPOSED')->sum('accumulated_depreciation'),
            'total_book_value' => (float) FixedAsset::where('company_id', $company->id)->where('status', '!=', 'DISPOSED')->sum('book_value'),
            'active_count' => FixedAsset::where('company_id', $company->id)->where('status', 'ACTIVE')->count(),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $assets,
                'stats' => $stats,
            ]);
        }

        return view('assets.index', compact('assets', 'stats', 'category', 'status', 'search'));
    }

    /**
     * Form Pendaftaran Aset Tetap Baru.
     */
    public function create(): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $bankAccounts = BankAccount::where('company_id', $company->id)->where('is_active', true)->get();

        $monthStr = date('Ym');
        $count = FixedAsset::where('company_id', $company->id)->count() + 1;
        $suggestedCode = 'AST-'.$monthStr.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);

        return view('assets.create', compact('company', 'stores', 'bankAccounts', 'suggestedCode'));
    }

    /**
     * Simpan Aset Tetap Baru & Otomasi Jurnal Perolehan Awal.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();

        $request->validate([
            'asset_code' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'category' => 'required|in:EQUIPMENT,VEHICLE,BUILDING,FURNITURE',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:1000',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'store_id' => 'nullable|uuid',
            'funding_source' => 'required|in:CASH,BANK,MODAL_AWAL,NONE',
            'bank_account_id' => 'nullable|uuid',
        ]);

        $cost = (float) $request->acquisition_cost;
        $salvage = (float) ($request->salvage_value ?? 0.0);
        $months = (int) $request->useful_life_months;

        if ($salvage >= $cost) {
            return back()->withInput()->with('error', 'Nilai residu tidak boleh lebih besar atau sama dengan harga perolehan aset.');
        }

        $monthlyDepreciation = round(($cost - $salvage) / $months, 2);

        try {
            $asset = DB::transaction(function () use ($request, $company, $cost, $salvage, $months, $monthlyDepreciation) {
                $asset = FixedAsset::create([
                    'company_id' => $company->id,
                    'store_id' => $request->store_id,
                    'asset_code' => $request->asset_code,
                    'name' => $request->name,
                    'category' => $request->category,
                    'acquisition_date' => $request->acquisition_date,
                    'acquisition_cost' => $cost,
                    'salvage_value' => $salvage,
                    'useful_life_months' => $months,
                    'monthly_depreciation_amount' => $monthlyDepreciation,
                    'accumulated_depreciation' => 0.00,
                    'book_value' => $cost,
                    'status' => 'ACTIVE',
                ]);

                if ($request->funding_source !== 'NONE') {
                    $bankAccount = $request->bank_account_id ? BankAccount::find($request->bank_account_id) : null;
                    $this->accountingService->recordAssetAcquisition(
                        asset: $asset,
                        fundingSource: $request->funding_source,
                        bankAccount: $bankAccount
                    );
                }

                return $asset;
            });

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Aset Tetap [{$asset->name}] berhasil didaftarkan.",
                    'asset' => $asset,
                ]);
            }

            return redirect()->route('assets.show', $asset->id)
                ->with('success', "Aset Tetap [{$asset->name}] ({$asset->asset_code}) berhasil didaftarkan!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Rincian Aset, Jadwal & Riwayat Depresiasi Garis Lurus.
     */
    public function show(string $id): View
    {
        $asset = FixedAsset::with([
            'company',
            'store',
            'depreciationRecords.journalEntry',
        ])->findOrFail($id);

        return view('assets.show', compact('asset'));
    }

    /**
     * Eksekusi Depresiasi Bulanan untuk 1 Aset Spesifik.
     */
    public function depreciate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $asset = FixedAsset::findOrFail($id);
        $periodDate = $request->input('period_date', now()->endOfMonth()->toDateString());

        try {
            $journal = $this->accountingService->recordStraightLineDepreciation($asset, $periodDate);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Depresiasi periode {$periodDate} untuk aset [{$asset->name}] berhasil dicatat.",
                    'journal' => $journal,
                ]);
            }

            return back()->with('success', "Penyusutan periode {$periodDate} sebesar Rp " . number_format($asset->fresh()->monthly_depreciation_amount, 0, ',', '.') . " berhasil dijurnal ke Buku Besar!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Eksekusi Massal Depresiasi Bulanan Seluruh Aset Aktif.
     */
    public function depreciateAll(Request $request): RedirectResponse|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();
        $periodDate = $request->input('period_date', now()->endOfMonth()->toDateString());

        $activeAssets = FixedAsset::where('company_id', $company->id)
            ->where('status', 'ACTIVE')
            ->where(function ($q) use ($periodDate) {
                $q->whereNull('last_depreciation_date')
                  ->orWhere('last_depreciation_date', '<', $periodDate);
            })
            ->get();

        $processedCount = 0;
        $totalDepreciated = 0.0;
        $errors = [];

        foreach ($activeAssets as $asset) {
            try {
                $this->accountingService->recordStraightLineDepreciation($asset, $periodDate);
                $processedCount++;
                $totalDepreciated += (float) $asset->monthly_depreciation_amount;
            } catch (\Exception $e) {
                $errors[] = "Aset [{$asset->name}]: " . $e->getMessage();
            }
        }

        $msg = "Depresiasi massal selesai: {$processedCount} aset berhasil disusutkan total Rp " . number_format($totalDepreciated, 0, ',', '.') . " untuk periode {$periodDate}.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'processed_count' => $processedCount,
                'total_depreciated' => $totalDepreciated,
                'errors' => $errors,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Pelepasan / Penghapusan Aset Tetap (Asset Disposal / Write-off).
     */
    public function dispose(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $asset = FixedAsset::findOrFail($id);

        $request->validate([
            'disposal_proceeds' => 'nullable|numeric|min:0',
            'disposal_date' => 'required|date',
            'bank_account_id' => 'nullable|uuid',
        ]);

        try {
            $bankAccount = $request->bank_account_id ? BankAccount::find($request->bank_account_id) : null;
            $this->accountingService->recordAssetDisposal(
                asset: $asset,
                disposalProceeds: (float) ($request->disposal_proceeds ?? 0.0),
                disposalDate: $request->disposal_date,
                bankAccount: $bankAccount
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Aset [{$asset->name}] berhasil dilepas dan jurnal penutupan telah dicatat.",
                ]);
            }

            return redirect()->route('assets.show', $asset->id)
                ->with('success', "Aset [{$asset->name}] berhasil dilepas (Disposed) dan akun aset telah ditutup di General Ledger!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
