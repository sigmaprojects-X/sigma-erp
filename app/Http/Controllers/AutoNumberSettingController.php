<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\TransactionNumberPattern;
use App\Services\AutoNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutoNumberSettingController extends Controller
{
    public function __construct(
        protected AutoNumberService $autoNumberService
    ) {}

    /**
     * Tampilkan halaman Konfigurasi Penomoran Otomatis Seluruh Dokumen Transaksi ERP & POS.
     */
    public function index(): View
    {
        $company = Company::firstOrFail();

        // Pastikan default patterns selalu ada
        $this->autoNumberService->seedDefaultPatterns($company);

        $patterns = TransactionNumberPattern::where('company_id', $company->id)
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        // Kelompokkan per modul bisnis
        $groupedPatterns = [
            'penjualan' => [
                'name' => 'Modul Penjualan & Kasir POS',
                'icon' => '💵',
                'description' => 'Struk Kasir POS, Sales Order, Invoice Back-Office, dan Retur Penjualan',
                'items' => $patterns->where('module', 'penjualan'),
            ],
            'pembelian' => [
                'name' => 'Modul Pembelian & Supplier',
                'icon' => '🛒',
                'description' => 'Purchase Order (PO), Penerimaan Barang (GRN), dan Retur Supplier',
                'items' => $patterns->where('module', 'pembelian'),
            ],
            'persediaan' => [
                'name' => 'Modul Persediaan & Logistik Gudang',
                'icon' => '📦',
                'description' => 'Stock Opname, Transfer Cabang, Penyesuaian/Karantina, dan Item Keluar/Masuk',
                'items' => $patterns->where('module', 'persediaan'),
            ],
            'akuntansi' => [
                'name' => 'Modul Akuntansi & Kas Bank',
                'icon' => '⚖️',
                'description' => 'Bukti Jurnal Umum (GL) dan Kas & Bank Masuk/Keluar Operasional',
                'items' => $patterns->where('module', 'akuntansi'),
            ],
        ];

        return view('settings.numbering', compact('company', 'groupedPatterns', 'patterns'));
    }

    /**
     * Perbarui pola format, prefix, panjang digit, siklus reset, atau kunci nomor transaksi.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $pattern = TransactionNumberPattern::findOrFail($id);

        $validated = $request->validate([
            'prefix' => 'required|string|max:30|alpha_dash',
            'pattern' => 'required|string|max:100',
            'digits' => 'required|integer|min:3|max:8',
            'reset_cycle' => 'required|in:MONTHLY,YEARLY,CONTINUOUS',
            'is_locked' => 'nullable|boolean',
        ], [
            'prefix.required' => 'Prefix tidak boleh kosong.',
            'prefix.alpha_dash' => 'Prefix hanya boleh berisi huruf, angka, dan strip/garis bawah.',
            'pattern.required' => 'Format pola nomor wajib diisi.',
            'digits.min' => 'Panjang nomor urut minimal 3 digit.',
            'digits.max' => 'Panjang nomor urut maksimal 8 digit.',
        ]);

        $pattern->prefix = strtoupper($validated['prefix']);
        $pattern->pattern = $validated['pattern'];
        $pattern->digits = (int) $validated['digits'];
        $pattern->reset_cycle = $validated['reset_cycle'];
        $pattern->is_locked = $request->boolean('is_locked');
        $pattern->save();

        return redirect()
            ->route('settings.numbering.index')
            ->with('success', "Format nomor untuk '{$pattern->name}' ({$pattern->document_type}) berhasil diperbarui.");
    }

    /**
     * Setel ulang nomor urut saat ini (Manual Reset / Adjustment oleh Manajer).
     */
    public function resetSequence(Request $request, string $id): RedirectResponse
    {
        $pattern = TransactionNumberPattern::findOrFail($id);

        $validated = $request->validate([
            'current_sequence' => 'required|integer|min:0',
        ]);

        $pattern->current_sequence = (int) $validated['current_sequence'];
        $pattern->save();

        return redirect()
            ->route('settings.numbering.index')
            ->with('success', "Counter urutan untuk '{$pattern->name}' berhasil diubah ke {$pattern->current_sequence}.");
    }

    /**
     * Pulihkan semua format penomoran dokumen transaksi ke standar bawaan sistem.
     */
    public function restoreDefaults(): RedirectResponse
    {
        $company = Company::firstOrFail();
        $defaults = TransactionNumberPattern::getDefaultPatterns();

        foreach ($defaults as $docType => $d) {
            TransactionNumberPattern::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'document_type' => $docType,
                ],
                [
                    'module' => $d['module'],
                    'name' => $d['name'],
                    'prefix' => $d['prefix'],
                    'pattern' => $d['pattern'],
                    'digits' => $d['digits'],
                    'reset_cycle' => $d['reset_cycle'],
                    'is_locked' => $d['is_locked'],
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('settings.numbering.index')
            ->with('success', 'Semua pola penomoran transaksi berhasil dikembalikan ke standar bawaan sistem.');
    }
}
