<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'user_role',
        'module',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat log aktivitas sistem secara otomatis.
     */
    public static function record(
        string $module,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?string $companyId = null
    ): self {
        $actor = $user ?? Auth::user();
        $targetCompanyId = $companyId ?? ($actor?->company_id ?? Company::where('code', 'SIGMA-HO')->value('id') ?? Company::value('id'));

        return static::create([
            'company_id' => $targetCompanyId,
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? 'Sistem / Anonim',
            'user_role' => $actor?->role ?? 'system',
            'module' => strtoupper($module),
            'action' => strtoupper($action),
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
