<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'store_id',
        'user_group_id',
        'manager_pin',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'manager_pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'manager_pin' => 'hashed',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    /**
     * Store relationship if user is tied to a specific store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * User Group / Hak Akses Ceklist relationship.
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    /**
     * Check if user has specific permission in RBAC Checklist.
     */
    public function hasPermission(string $permission): bool
    {
        // Owner always has god-mode access
        if ($this->role === 'owner') {
            return true;
        }

        // If user is attached to a UserGroup checklist, evaluate group permissions
        if ($this->userGroup) {
            return $this->userGroup->hasPermission($permission);
        }

        // Fallback default permissions based on role if no group assigned yet
        return match ($this->role) {
            'manager' => true,
            'cashier' => in_array($permission, [
                'pos_terminal.buka',
                'pos_terminal.baru',
                'pos_shift_management.buka',
                'pos_shift_management.baru',
                'pos_credit_payment.buka',
                'view_selling_price.buka',
                'menu_direct_pos.buka',
                'menu_view_sales.buka',
                'master_item.buka',
                'master_customer.buka',
            ], true),
            'warehouse' => in_array($permission, [
                'stock_in.buka',
                'stock_in.baru',
                'stock_out.buka',
                'stock_out.baru',
                'stock_opname.buka',
                'stock_opname.baru',
                'stock_transfer.buka',
                'stock_transfer.baru',
                'purchase_receipt.buka',
                'purchase_receipt.baru',
                'master_item.buka',
            ], true),
            'purchasing' => str_starts_with($permission, 'pembelian_') || in_array($permission, ['master_item.buka', 'master_supplier.buka'], true),
            'finance' => str_starts_with($permission, 'akuntansi_') || str_starts_with($permission, 'laporan_') || $permission === 'special_view_hpp',
            default => false,
        };
    }

    /**
     * Check whether the user is permitted to view HPP / Cost of Goods Sold.
     * Owner decision: only owner, manager, purchasing, finance (store_head and cashier cannot view HPP).
     */
    public function canViewHpp(): bool
    {
        if ($this->role === 'owner') {
            return true;
        }

        if ($this->userGroup) {
            return $this->userGroup->hasPermission('special_view_hpp');
        }

        return in_array($this->role, ['owner', 'manager', 'purchasing', 'finance'], true);
    }

    /**
     * Check if user account is active and within valid employment window.
     */
    public function isActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->valid_from && $today->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $today->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Verify manager authorization PIN.
     */
    public function verifyManagerPin(?string $pin): bool
    {
        if (empty($pin) || empty($this->manager_pin)) {
            return false;
        }

        return Hash::check($pin, $this->manager_pin);
    }
}
