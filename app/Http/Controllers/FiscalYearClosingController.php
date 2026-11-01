<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FiscalYearClosing;
use App\Services\FiscalYearClosingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FiscalYearClosingController extends Controller
{
    public function __construct(
        protected FiscalYearClosingService $closingService
    ) {}

    /**
     * Tampilkan Halaman Tutup Buku Tahunan (Fitur No. 18 IPOS 5 / Ketoko).
     */
    public function index(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $selectedYear = (int) $request->input('year', date('Y'));

        $summary = $this->closingService->getClosingSummary($company, $selectedYear);

        $closings = FiscalYearClosing::where('company_id', $company->id)
            ->with(['closer', 'closingJournalEntry', 'retainedEarningsAccount'])
            ->orderByDesc('fiscal_year')
            ->get();

        return view('accounting.closing.index', compact(
            'company',
            'selectedYear',
            'summary',
            'closings'
        ));
    }

    /**
     * Eksekusi Tutup Buku Tahunan.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'fiscal_year' => 'required|integer|min:2020|max:2050',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $closing = $this->closingService->closeFiscalYear(
                company: $company,
                fiscalYear: $validated['fiscal_year'],
                user: Auth::user(),
                notes: $validated['notes'] ?? null
            );

            $msg = "Tutup buku tahunan {$closing->fiscal_year} berhasil diproses! Laba/Rugi bersih sebesar Rp ".number_format($closing->net_profit, 0, ',', '.')." telah dialokasikan ke Laba Ditahan.";
            return redirect()->route('accounting.closing.index', ['year' => $closing->fiscal_year])
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Buka Kembali Periode Tahun Buku yang Ditutup.
     */
    public function reopen(Request $request, int $year): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        // Hanya role owner atau manager yang berhak membuka kembali tutup buku
        if (! in_array(Auth::user()?->role, ['owner', 'manager'])) {
            return back()->withErrors(['error' => 'Hanya Owner atau Manajer yang memiliki wewenang untuk membuka kembali tahun buku yang telah ditutup.']);
        }

        try {
            $this->closingService->reopenFiscalYear($company, $year, Auth::user());

            return redirect()->route('accounting.closing.index', ['year' => $year])
                ->with('success', "Tutup buku tahun {$year} berhasil dibuka kembali. Jurnal penutup telah ditarik.");
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
