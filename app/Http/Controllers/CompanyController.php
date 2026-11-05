<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    /**
     * Beralih perusahaan aktif (Khusus Owner).
     */
    public function switchCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
        ]);

        $company = $this->companyService->switchCompany($validated['company_id']);

        // Periksa URL sebelumnya (referrer)
        $previousUrl = url()->previous();
        $path = parse_url($previousUrl, PHP_URL_PATH) ?? '';

        // Jika user berpindah perusahaan saat sedang membuka halaman detail/edit entitas tertentu,
        // redirect ke halaman daftar (index) modul tersebut agar tidak terkena 404 Not Found karena ID milik perusahaan lama.
        if (preg_match('#/master/products/[^/]+#', $path)) {
            return redirect()->route('master.products')->with('success', "Berhasil beralih ke perusahaan: {$company->name}");
        }

        if (preg_match('#/purchases/orders/[^/]+#', $path)) {
            return redirect()->route('purchases.orders.index')->with('success', "Berhasil beralih ke perusahaan: {$company->name}");
        }

        if (preg_match('#/sales/transactions/[^/]+#', $path)) {
            return redirect()->route('sales.transactions')->with('success', "Berhasil beralih ke perusahaan: {$company->name}");
        }

        // Jika URL sebelumnya mengandung pola UUID (spesifik entitas ID milik perusahaan lama)
        if (preg_match('#/[0-9a-fA-F-]{36}#', $path)) {
            return redirect()->route('sales.index')->with('success', "Berhasil beralih ke perusahaan: {$company->name}");
        }

        return back()->with('success', "Berhasil beralih ke perusahaan: {$company->name}");
    }

    /**
     * Tampilkan daftar perusahaan di modul Pengaturan.
     */
    public function index(): View
    {
        $companies = Company::with(['stores' => function ($q) {
            $q->orderBy('is_main_store', 'desc');
        }])->orderBy('created_at')->get();

        $activeCompany = $this->companyService->getActiveCompany();

        return view('settings.companies.index', compact('companies', 'activeCompany'));
    }

    /**
     * Tambah perusahaan baru secara dinamis.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:companies,code',
            'tax_id' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'store_name' => 'nullable|string|max:150',
            'store_code' => 'nullable|string|max:50',
            'receipt_footer' => 'nullable|string|max:500',
            'is_tax_registered' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'code.required' => 'Kode perusahaan wajib diisi.',
            'code.unique' => 'Kode perusahaan sudah digunakan.',
        ]);

        $company = $this->companyService->createCompany($validated);

        return redirect()->route('settings.companies.index')
            ->with('success', "Perusahaan {$company->name} berhasil ditambahkan dan siap digunakan!");
    }

    /**
     * Perbarui data profil perusahaan.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:companies,code,' . $company->id,
            'tax_id' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'receipt_footer' => 'nullable|string|max:500',
            'is_tax_registered' => 'nullable|boolean',
        ]);

        $company->update($validated);

        return redirect()->route('settings.companies.index')
            ->with('success', "Data perusahaan {$company->name} berhasil diperbarui.");
    }

    /**
     * Aktif / Nonaktifkan perusahaan.
     */
    public function toggleStatus(string $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        // Jangan izinkan menonaktifkan jika hanya ada 1 perusahaan aktif
        $activeCount = Company::where('is_active', true)->count();
        if ($company->is_active && $activeCount <= 1) {
            return back()->with('error', 'Minimal harus ada 1 perusahaan yang aktif.');
        }

        $company->is_active = !$company->is_active;
        $company->save();

        $statusStr = $company->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Perusahaan {$company->name} berhasil {$statusStr}.");
    }
}
