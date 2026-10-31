<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Tampilkan Log Aktivitas Pengguna & Jejak Audit (Anti-Fraud Back-Office).
     */
    public function index(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $companyId = $company?->id;

        $query = AuditLog::with('user')->latest();
        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        // Filter Modul
        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        // Filter Aksi
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter Pengguna
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // Filter Pencarian Teks (Case-insensitive kompatibel PostgreSQL & SQLite)
        if ($request->filled('q')) {
            $q = strtolower(trim($request->input('q')));
            $query->where(function ($sub) use ($q) {
                $sub->whereRaw('LOWER(description) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(user_name) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(action) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(ip_address) LIKE ?', ["%{$q}%"]);
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        $modules = AuditLog::when($companyId, fn ($q) => $q->where(fn ($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))->distinct()->pluck('module');
        $actions = AuditLog::when($companyId, fn ($q) => $q->where(fn ($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))->distinct()->pluck('action');
        $users = User::orderBy('name')->get();

        // Metrik Ringkasan Hari Ini
        $todayCount = AuditLog::when($companyId, fn ($q) => $q->where(fn ($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))->whereDate('created_at', today())->count();
        $voidCount = AuditLog::when($companyId, fn ($q) => $q->where(fn ($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))->where('action', 'LIKE', '%VOID%')->whereDate('created_at', today())->count();
        $priceChangeCount = AuditLog::when($companyId, fn ($q) => $q->where(fn ($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))->where('action', 'LIKE', '%PRICE%')->whereDate('created_at', today())->count();

        return view('settings.audit_logs.index', compact(
            'logs',
            'modules',
            'actions',
            'users',
            'todayCount',
            'voidCount',
            'priceChangeCount'
        ));
    }
}
