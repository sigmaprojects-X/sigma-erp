<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Daftar resmi role sistem SIGMA ERP.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'owner' => 'Owner / Pemilik Toko',
        'manager' => 'Store Manager / Manajer',
        'store_head' => 'Kepala Toko / Supervisor',
        'cashier' => 'Kasir Toko (Cashier)',
        'warehouse' => 'Staf Logistik & Gudang',
        'purchasing' => 'Staf Pembelian / Purchasing',
        'finance' => 'Staf Keuangan & Akunting',
        'sales' => 'Salesman / Counter Sales',
    ];

    /**
     * Hierarki level otoritas setiap role untuk mencegah eskalasi hak akses.
     *
     * @var array<string, int>
     */
    public const ROLE_LEVELS = [
        'owner' => 100,
        'manager' => 80,
        'store_head' => 60,
        'finance' => 50,
        'purchasing' => 50,
        'warehouse' => 40,
        'sales' => 40,
        'cashier' => 40,
    ];

    /**
     * Pastikan aktor tidak mengubah atau mereset akun Owner (kecuali aktor adalah Owner)
     * atau akun dengan level role di atas dirinya.
     */
    protected function authorizeTargetUserModification(?User $actor, User $targetUser): void
    {
        if (! $actor) {
            abort(403, 'Akses ditolak.');
        }

        if ($actor->role === 'owner') {
            return;
        }

        if ($targetUser->role === 'owner') {
            abort(403, 'Hanya Owner yang dapat mengubah atau mereset akun berole Owner.');
        }

        $actorLevel = self::ROLE_LEVELS[$actor->role] ?? 0;
        $targetLevel = self::ROLE_LEVELS[$targetUser->role] ?? 0;

        if ($targetLevel > $actorLevel) {
            abort(403, 'Anda tidak diizinkan mengubah akun dengan level role di atas role Anda.');
        }
    }

    /**
     * Pastikan aktor tidak menetapkan role Owner (kecuali aktor adalah Owner)
     * atau menaikkan role melebihi role dirinya sendiri.
     */
    protected function authorizeRoleAssignment(?User $actor, string $targetRole): void
    {
        if (! $actor) {
            abort(403, 'Akses ditolak.');
        }

        if ($actor->role === 'owner') {
            return;
        }

        if ($targetRole === 'owner') {
            abort(403, 'Hanya Owner yang dapat membuat atau menetapkan pengguna berole Owner.');
        }

        $actorLevel = self::ROLE_LEVELS[$actor->role] ?? 0;
        $assignedLevel = self::ROLE_LEVELS[$targetRole] ?? 0;

        if ($assignedLevel > $actorLevel) {
            abort(403, 'Anda tidak dapat menaikkan role pengguna melebihi role Anda sendiri.');
        }
    }

    /**
     * Tampilkan halaman manajemen pengguna & matriks ceklist hak akses user group.
     */
    public function index(Request $request): View
    {
        $company = Company::first();
        $userGroups = UserGroup::withCount('users')
            ->orderBy('is_system', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $selectedGroupId = $request->query('group_id', $userGroups->first()?->id);
        $selectedGroup = $userGroups->firstWhere('id', $selectedGroupId) ?? $userGroups->first();

        $catalog = UserGroup::getPermissionCatalog();
        $presets = UserGroup::getPresets();

        $users = User::with(['store', 'userGroup'])
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $stores = Store::orderBy('name')->get();

        $roles = self::ROLES;

        return view('settings.users', compact(
            'company',
            'userGroups',
            'selectedGroup',
            'catalog',
            'presets',
            'users',
            'stores',
            'roles'
        ));
    }

    /**
     * Buat Kelompok Hak Akses Baru.
     */
    public function storeGroup(Request $request): RedirectResponse
    {
        $company = Company::first();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('user_groups', 'code')->where('company_id', $company?->id),
            ],
            'description' => 'nullable|string|max:255',
            'preset' => 'nullable|string',
        ]);

        $permissions = [];
        if (! empty($validated['preset'])) {
            $presets = UserGroup::getPresets();
            if (isset($presets[$validated['preset']])) {
                $permissions = $presets[$validated['preset']]['permissions'];
            }
        }

        $group = UserGroup::create([
            'company_id' => $company?->id,
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return redirect()
            ->route('settings.users.index', ['group_id' => $group->id, 'tab' => 'groups'])
            ->with('success', "Kelompok hak akses '{$group->name}' berhasil dibuat.");
    }

    /**
     * Perbarui data kelompok hak akses / simpan matriks ceklist izin.
     */
    public function updateGroup(Request $request, string $id): RedirectResponse
    {
        $group = UserGroup::findOrFail($id);

        // Jika hanya memperbarui izin ceklist dari form matriks
        if ($request->has('save_permissions_matrix')) {
            $permissions = $request->input('permissions', []);
            if (! is_array($permissions)) {
                $permissions = [];
            }

            $group->permissions = array_values(array_unique($permissions));
            $group->save();

            return redirect()
                ->route('settings.users.index', ['group_id' => $group->id, 'tab' => 'groups'])
                ->with('success', "Matriks hak akses untuk kelompok '{$group->name}' berhasil diperbarui.");
        }

        // Perbarui profil kelompok (nama, deskripsi, dsb)
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $group->name = $validated['name'];
        $group->description = $validated['description'] ?? null;
        $group->save();

        return redirect()
            ->route('settings.users.index', ['group_id' => $group->id, 'tab' => 'groups'])
            ->with('success', "Informasi kelompok '{$group->name}' berhasil diperbarui.");
    }

    /**
     * Terapkan preset template hak akses secara instan ke kelompok yang dipilih.
     */
    public function applyPreset(Request $request, string $id): RedirectResponse
    {
        $group = UserGroup::findOrFail($id);
        $presetKey = $request->input('preset');
        $presets = UserGroup::getPresets();

        if (! isset($presets[$presetKey])) {
            return back()->with('error', 'Template preset hak akses tidak ditemukan.');
        }

        $group->permissions = $presets[$presetKey]['permissions'];
        $group->save();

        return redirect()
            ->route('settings.users.index', ['group_id' => $group->id, 'tab' => 'groups'])
            ->with('success', "Template preset '{$presets[$presetKey]['name']}' berhasil diterapkan ke kelompok '{$group->name}'.");
    }

    /**
     * Hapus kelompok hak akses kustom (Kelompok sistem bawaan dilindungi).
     */
    public function destroyGroup(string $id): RedirectResponse
    {
        $group = UserGroup::withCount('users')->findOrFail($id);

        if ($group->is_system) {
            return back()->with('error', 'Kelompok sistem bawaan tidak dapat dihapus demi keamanan sistem.');
        }

        if ($group->users_count > 0) {
            return back()->with('error', "Kelompok ini masih digunakan oleh {$group->users_count} pengguna. Pindahkan pengguna terlebih dahulu sebelum menghapus.");
        }

        $name = $group->name;
        $group->delete();

        return redirect()
            ->route('settings.users.index', ['tab' => 'groups'])
            ->with('success', "Kelompok hak akses '{$name}' berhasil dihapus.");
    }

    /**
     * Tambah Pengguna / Staf Kasir Baru.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', 'string', Rule::in(array_keys(self::ROLES))],
            'user_group_id' => 'nullable|exists:user_groups,id',
            'store_id' => 'nullable|exists:stores,id',
            'manager_pin' => 'nullable|string|digits_between:4,6',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date' . ($request->filled('valid_from') ? '|after_or_equal:valid_from' : ''),
        ]);

        $this->authorizeRoleAssignment($request->user(), $validated['role']);

        DB::transaction(function () use ($validated, $request) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'user_group_id' => ! empty($validated['user_group_id']) ? $validated['user_group_id'] : null,
                'store_id' => ! empty($validated['store_id']) ? $validated['store_id'] : null,
                'is_active' => $request->boolean('is_active', true),
                'valid_from' => $validated['valid_from'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
            ];

            if (! empty($validated['manager_pin'])) {
                $userData['manager_pin'] = Hash::make($validated['manager_pin']);
            }

            $createdUser = User::create($userData);

            AuditLog::record(
                module: 'USERS',
                action: 'ASSIGN_ROLE',
                description: "Pembuatan pengguna baru '{$createdUser->name}' ({$createdUser->email}) dengan role '{$createdUser->role}'.",
                oldValues: null,
                newValues: [
                    'target_user_id' => $createdUser->id,
                    'email' => $createdUser->email,
                    'role' => $createdUser->role,
                ]
            );
        });

        return redirect()
            ->route('settings.users.index', ['tab' => 'users'])
            ->with('success', "Pengguna '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Perbarui data pengguna / staf kasir.
     */
    public function updateUser(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $this->authorizeTargetUserModification($request->user(), $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(array_keys(self::ROLES))],
            'user_group_id' => 'nullable|exists:user_groups,id',
            'store_id' => 'nullable|exists:stores,id',
            'password' => 'nullable|string|min:8',
            'manager_pin' => 'nullable|string|digits_between:4,6',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date' . ($request->filled('valid_from') ? '|after_or_equal:valid_from' : ''),
        ]);

        $this->authorizeRoleAssignment($request->user(), $validated['role']);

        DB::transaction(function () use ($user, $validated, $request) {
            $oldRole = $user->role;

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->user_group_id = ! empty($validated['user_group_id']) ? $validated['user_group_id'] : null;
            $user->store_id = ! empty($validated['store_id']) ? $validated['store_id'] : null;
            $user->is_active = $request->boolean('is_active', true);
            $user->valid_from = $validated['valid_from'] ?? null;
            $user->valid_until = $validated['valid_until'] ?? null;

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if (array_key_exists('manager_pin', $validated)) {
                if (! empty($validated['manager_pin'])) {
                    $user->manager_pin = Hash::make($validated['manager_pin']);
                } elseif ($request->boolean('clear_manager_pin')) {
                    $user->manager_pin = null;
                }
            }

            $user->save();

            if ($oldRole !== $user->role) {
                AuditLog::record(
                    module: 'USERS',
                    action: 'CHANGE_ROLE',
                    description: "Perubahan role pengguna '{$user->name}' ({$user->email}) dari '{$oldRole}' menjadi '{$user->role}'.",
                    oldValues: [
                        'target_user_id' => $user->id,
                        'email' => $user->email,
                        'role' => $oldRole,
                    ],
                    newValues: [
                        'target_user_id' => $user->id,
                        'email' => $user->email,
                        'role' => $user->role,
                    ]
                );
            }
        });

        return redirect()
            ->route('settings.users.index', ['tab' => 'users'])
            ->with('success', "Data pengguna '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Aktifkan / Nonaktifkan akun staf kasir/pengguna secara instan.
     */
    public function toggleStatus(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $this->authorizeTargetUserModification(Auth::user(), $user);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        DB::transaction(function () use ($user) {
            $user->is_active = ! $user->is_active;
            $user->save();
        });

        $statusStr = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()
            ->route('settings.users.index', ['tab' => 'users'])
            ->with('success', "Akun '{$user->name}' berhasil {$statusStr}.");
    }

    /**
     * Reset password staf secara cepat oleh Owner/Manager.
     */
    public function resetPassword(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $this->authorizeTargetUserModification($request->user(), $user);

        $validated = $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->password = Hash::make($validated['new_password']);
            $user->save();
        });

        return redirect()
            ->route('settings.users.index', ['tab' => 'users'])
            ->with('success', "Password untuk '{$user->name}' berhasil direset.");
    }

    /**
     * Pasang atau perbarui PIN Otorisasi Manajer.
     */
    public function setManagerPin(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $this->authorizeTargetUserModification($request->user(), $user);

        $validated = $request->validate([
            'manager_pin' => 'nullable|string|digits_between:4,6',
        ]);

        $msg = DB::transaction(function () use ($user, $validated) {
            if (empty($validated['manager_pin'])) {
                $user->manager_pin = null;
                $message = "PIN Otorisasi Manajer untuk '{$user->name}' berhasil dihapus.";
            } else {
                $user->manager_pin = Hash::make($validated['manager_pin']);
                $message = "PIN Otorisasi Manajer untuk '{$user->name}' berhasil disimpan.";
            }

            $user->save();

            return $message;
        });

        return redirect()
            ->route('settings.users.index', ['tab' => 'users'])
            ->with('success', $msg);
    }
}
