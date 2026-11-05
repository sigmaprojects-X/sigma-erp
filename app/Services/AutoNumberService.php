<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TransactionNumberPattern;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AutoNumberService
{
    /**
     * Hasilkan nomor dokumen berikutnya secara otomatis & terjamin unik (ACID Lock).
     */
    public function generateNextNumber(
        string $documentType,
        ?string $storeCode = null,
        ?Company $company = null,
        ?\DateTimeInterface $date = null
    ): string {
        $now = $date ? Carbon::instance($date) : now();
        $targetCompany = $company ?? app(CompanyService::class)->getActiveCompany();

        if (! $targetCompany) {
            return strtoupper($documentType) . '-' . $now->format('Ymd') . '-0001';
        }

        return DB::transaction(function () use ($documentType, $storeCode, $targetCompany, $now) {
            // Dapatkan pattern dengan pesimistic row locking agar terhindar dari duplikasi nomor saat kasir ramai
            $pattern = TransactionNumberPattern::where('company_id', $targetCompany->id)
                ->where('document_type', strtoupper($documentType))
                ->lockForUpdate()
                ->first();

            // Jika belum ada di database, inisialisasi dari template standar
            if (! $pattern) {
                $defaults = TransactionNumberPattern::getDefaultPatterns();
                $defaultData = $defaults[strtoupper($documentType)] ?? [
                    'module' => 'penjualan',
                    'name' => strtoupper($documentType) . ' Document',
                    'prefix' => strtoupper($documentType),
                    'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                    'digits' => 4,
                    'reset_cycle' => 'MONTHLY',
                    'is_locked' => true,
                ];

                $pattern = TransactionNumberPattern::create([
                    'company_id' => $targetCompany->id,
                    'document_type' => strtoupper($documentType),
                    'module' => $defaultData['module'],
                    'name' => $defaultData['name'],
                    'prefix' => $defaultData['prefix'],
                    'pattern' => $defaultData['pattern'],
                    'digits' => $defaultData['digits'],
                    'reset_cycle' => $defaultData['reset_cycle'],
                    'current_sequence' => 0,
                    'last_period' => null,
                    'is_locked' => $defaultData['is_locked'],
                    'is_active' => true,
                ]);

                // Re-lock
                $pattern = TransactionNumberPattern::where('id', $pattern->id)->lockForUpdate()->first();
            }

            // Tentukan periode saat ini berdasarkan reset cycle
            $currentPeriod = match ($pattern->reset_cycle) {
                'MONTHLY' => $now->format('Ym'),
                'YEARLY' => $now->format('Y'),
                default => 'CONTINUOUS',
            };

            // Evaluasi apakah periode berganti dan perlu di-reset ke 1
            if ($pattern->reset_cycle !== 'CONTINUOUS' && $pattern->last_period !== $currentPeriod) {
                $nextSequence = 1;
            } else {
                $nextSequence = (int) $pattern->current_sequence + 1;
            }

            // Update state
            $pattern->current_sequence = $nextSequence;
            $pattern->last_period = $currentPeriod;
            $pattern->save();

            // Format nomor akhir
            return $this->formatNumber(
                patternTemplate: $pattern->pattern,
                prefix: $pattern->prefix,
                digits: (int) $pattern->digits,
                sequence: $nextSequence,
                storeCode: $storeCode,
                date: $now
            );
        });
    }

    /**
     * Format string nomor berdasarkan tag template variabel.
     */
    public function formatNumber(
        string $patternTemplate,
        string $prefix,
        int $digits,
        int $sequence,
        ?string $storeCode = null,
        ?\DateTimeInterface $date = null
    ): string {
        $now = $date ? Carbon::instance($date) : now();
        $formattedSequence = str_pad((string) $sequence, max(3, $digits), '0', STR_PAD_LEFT);
        $cleanStore = $storeCode ? preg_replace('/[^A-Za-z0-9]/', '', $storeCode) : 'PST';

        $replacements = [
            '{PREFIX}' => $prefix,
            '{Y}' => $now->format('Y'),
            '{y}' => $now->format('y'),
            '{m}' => $now->format('m'),
            '{d}' => $now->format('d'),
            '{STORE}' => $cleanStore,
            '{SEQ}' => $formattedSequence,
        ];

        return strtr($patternTemplate, $replacements);
    }

    /**
     * Preview contoh penomoran secara instan untuk antarmuka UI.
     */
    public function previewPattern(
        string $patternTemplate,
        string $prefix,
        int $digits,
        ?string $storeCode = 'SOLO',
        int $sampleSequence = 1
    ): string {
        return $this->formatNumber(
            patternTemplate: $patternTemplate,
            prefix: $prefix,
            digits: $digits,
            sequence: $sampleSequence,
            storeCode: $storeCode
        );
    }

    /**
     * Inisialisasi seluruh template default untuk sebuah perusahaan.
     */
    public function seedDefaultPatterns(Company $company): void
    {
        $defaults = TransactionNumberPattern::getDefaultPatterns();

        DB::transaction(function () use ($company, $defaults) {
            foreach ($defaults as $docType => $data) {
                TransactionNumberPattern::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'document_type' => $docType,
                    ],
                    [
                        'module' => $data['module'],
                        'name' => $data['name'],
                        'prefix' => $data['prefix'],
                        'pattern' => $data['pattern'],
                        'digits' => $data['digits'],
                        'reset_cycle' => $data['reset_cycle'],
                        'current_sequence' => 0,
                        'last_period' => now()->format('Ym'),
                        'is_locked' => $data['is_locked'],
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
