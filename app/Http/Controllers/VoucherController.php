<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Tampilkan Daftar Voucher & Gift Cards.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $query = Voucher::where('company_id', $company->id)->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('code', 'ilike', "%{$s}%")
                    ->orWhere('barcode', 'ilike', "%{$s}%")
                    ->orWhere('title', 'ilike', "%{$s}%");
            });
        }

        $vouchers = $query->paginate(15)->withQueryString();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'vouchers' => $vouchers,
            ]);
        }

        return view('vouchers.index', compact('vouchers', 'company'));
    }

    /**
     * Form Tambah / Generate Voucher.
     */
    public function create(): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->get();

        return view('vouchers.create', compact('stores'));
    }

    /**
     * Simpan / Generate Voucher Baru (Single atau Batch ala Indomaret).
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'type' => 'required|in:VALUE,DISCOUNT_PERCENT',
            'amount' => 'required|numeric|min:1',
            'min_spend' => 'nullable|numeric|min:0',
            'is_printed_on_receipt' => 'nullable|boolean',
            'trigger_min_spend' => 'nullable|numeric|min:0',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'is_template' => 'nullable|boolean',
            'quantity_to_generate' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
            $isTemplate = $request->has('is_template');
            $qty = $isTemplate ? 1 : (int) ($validated['quantity_to_generate'] ?? 1);

            $createdVouchers = [];

            for ($i = 0; $i < $qty; $i++) {
                $code = 'VCH-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

                $createdVouchers[] = Voucher::create([
                    'company_id' => $company->id,
                    'store_id' => $validated['store_id'] ?? null,
                    'code' => $code,
                    'title' => $validated['title'],
                    'type' => $validated['type'],
                    'amount' => (float) $validated['amount'],
                    'min_spend' => (float) ($validated['min_spend'] ?? 0),
                    'trigger_min_spend' => (float) ($validated['trigger_min_spend'] ?? 0),
                    'is_printed_on_receipt' => $request->has('is_printed_on_receipt'),
                    'status' => $isTemplate ? 'TEMPLATE' : 'ISSUED',
                    'valid_from' => $validated['valid_from'] ?? now(),
                    'valid_until' => $validated['valid_until'] ?? now()->addDays(30),
                ]);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$qty} voucher berhasil dibuat.",
                    'vouchers' => $createdVouchers,
                ]);
            }

            return redirect()->route('vouchers.index')
                ->with('success', "{$qty} voucher berhasil digenerate!");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal generate voucher: '.$e->getMessage()]);
        }
    }

    /**
     * Cetak Lembaran Kupon Voucher Belanja Fisik (Barcode Kupon).
     */
    public function printCoupons(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $query = Voucher::where('company_id', $company->id);

        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->whereIn('id', $ids);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default cetak voucher yang masih aktif / ISSUED
            $query->where('status', 'ISSUED');
        }

        $vouchers = $query->orderByDesc('created_at')->limit(50)->get();

        return view('vouchers.print_coupons', compact('vouchers', 'company'));
    }

    /**
     * Hapus Voucher.
     */
    public function destroy(Voucher $voucher): RedirectResponse|JsonResponse
    {
        $code = $voucher->code;
        $voucher->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => "Voucher {$code} dihapus."]);
        }

        return redirect()->route('vouchers.index')
            ->with('success', "Voucher {$code} berhasil dihapus.");
    }
}
