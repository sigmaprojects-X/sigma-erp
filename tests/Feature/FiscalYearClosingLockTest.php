<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\FiscalYearClosing;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PurchaseReturn;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\FiscalYearClosingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalYearClosingLockTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected User $owner;

    protected AccountingService $accountingService;

    protected FiscalYearClosingService $closingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();

        $this->company = Company::firstOrFail();
        $this->store = Store::firstOrFail();
        $this->owner = User::where('role', 'owner')->firstOrFail();
        $this->accountingService = app(AccountingService::class);
        $this->closingService = app(FiscalYearClosingService::class);

        $accounts = [
            ['code' => '1001-001', 'name' => 'Kas Toko', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1005-001', 'name' => 'Persediaan Barang Dagang', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '2001-001', 'name' => 'Hutang Usaha', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
            ['code' => '4001-001', 'name' => 'Pendapatan Penjualan', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],
            ['code' => '4002-001', 'name' => 'Pendapatan Selisih Kas/Stok', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],
            ['code' => '5001-001', 'name' => 'Beban Pokok Penjualan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6002-001', 'name' => 'Beban Selisih Kas/Stok', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['company_id' => $this->company->id, 'code' => $acc['code']],
                ['name' => $acc['name'], 'type' => $acc['type'], 'normal_balance' => $acc['normal_balance'], 'is_active' => true]
            );
        }
    }

    public function test_create_journal_entry_rejects_default_today_date_when_current_fiscal_year_is_closed(): void
    {
        $currentYear = (int) date('Y');
        $this->closingService->closeFiscalYear($this->company, $currentYear, $this->owner, 'Tutup buku tahun berjalan');

        $this->expectException(\InvalidArgumentException::class);

        // Memanggil createJournalEntry tanpa entryDate (null -> default hari ini) harus tetap ditolak!
        $this->accountingService->createJournalEntry(
            company: $this->company,
            referenceType: 'MANUAL_JOURNAL',
            referenceNumber: 'JRN-TEST-CLOSED',
            description: 'Jurnal pada tahun yang sudah ditutup',
            lines: [
                ['account_code' => '1001-001', 'debit' => 50000, 'credit' => 0],
                ['account_code' => '4001-001', 'debit' => 0, 'credit' => 50000],
            ],
            store: $this->store,
            userId: $this->owner->id,
            entryDate: null
        );
    }

    public function test_returns_and_adjustments_reject_posting_journals_in_closed_fiscal_year(): void
    {
        $closedYear = 2025;
        $this->closingService->closeFiscalYear($this->company, $closedYear, $this->owner, 'Tutup buku 2025');

        $shift = CashierShift::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_id' => $this->owner->id,
            'shift_number' => 'SHF-2025-01',
            'opened_at' => '2025-11-15 08:00:00',
            'starting_cash' => 100000,
            'status' => 'OPEN',
        ]);

        $posTx = PosTransaction::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_shift_id' => $shift->id,
            'customer_tier_id' => CustomerTier::firstOrFail()->id,
            'cashier_id' => $this->owner->id,
            'invoice_number' => 'INV-2025-001',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100000,
            'payment_method' => 'CASH',
            'paid_amount' => 100000,
            'change_amount' => 0,
            'status' => 'COMPLETED',
        ]);

        $posReturn = PosReturn::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'pos_transaction_id' => $posTx->id,
            'processed_by' => $this->owner->id,
            'return_number' => 'RET-POS-2025-01',
            'return_date' => '2025-11-15',
            'refund_method' => 'CASH',
            'subtotal_returned' => 50000,
            'tax_returned' => 0,
            'total_refund_amount' => 50000,
            'reason' => 'Barang cacat',
            'status' => 'COMPLETED',
        ]);

        try {
            $this->accountingService->recordSalesReturnJournal($posReturn);
            $this->fail('recordSalesReturnJournal seharusnya menolak retur pada tahun buku yang sudah ditutup.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('2025', $e->getMessage());
        }

        $supplier = Supplier::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'SUP-TEST-01'],
            ['name' => 'Supplier Test', 'is_active' => true]
        );

        $purchaseReturn = PurchaseReturn::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'return_type' => 'FLEXIBLE',
            'supplier_id' => $supplier->id,
            'return_number' => 'PRT-2025-01',
            'return_date' => '2025-10-20',
            'refund_type' => 'CASH',
            'subtotal' => 40000,
            'tax_amount' => 0,
            'grand_total' => 40000,
            'reason' => 'Kemasan rusak',
            'status' => 'APPROVED',
            'created_by' => $this->owner->id,
        ]);

        try {
            $this->accountingService->recordPurchaseReturnJournal($purchaseReturn);
            $this->fail('recordPurchaseReturnJournal seharusnya menolak retur pembelian pada tahun buku yang sudah ditutup.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('2025', $e->getMessage());
        }

        $opname = StockOpname::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'opname_number' => 'OPN-2025-01',
            'opname_date' => '2025-12-01',
            'status' => 'APPROVED',
            'is_blind' => true,
            'total_system_quantity' => 10,
            'total_actual_quantity' => 8,
            'total_discrepancy_quantity' => -2,
            'total_discrepancy_value' => -20000,
            'created_by' => $this->owner->id,
            'approved_by' => $this->owner->id,
            'approved_at' => '2025-12-01 15:00:00',
        ]);

        try {
            $this->accountingService->recordOpnameAdjustmentJournal($opname);
            $this->fail('recordOpnameAdjustmentJournal seharusnya menolak penyesuaian opname pada tahun buku yang sudah ditutup.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('2025', $e->getMessage());
        }

        $adjustment = StockAdjustment::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'adjustment_number' => 'ADJ-2025-01',
            'adjustment_date' => '2025-09-10',
            'adjustment_type' => 'DAMAGE_WASTE',
            'total_cost_value' => 15000,
            'status' => 'APPROVED',
            'created_by' => $this->owner->id,
        ]);

        try {
            $this->accountingService->recordStockAdjustmentJournal($adjustment);
            $this->fail('recordStockAdjustmentJournal seharusnya menolak penyesuaian stok pada tahun buku yang sudah ditutup.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('2025', $e->getMessage());
        }
    }

    public function test_official_reopen_flow_allows_posting_and_reclosing(): void
    {
        $year = 2025;
        $closing = $this->closingService->closeFiscalYear($this->company, $year, $this->owner, 'Tutup buku 2025');
        $this->assertSame('CLOSED', $closing->status);

        // Reopen resmi oleh Owner
        $this->closingService->reopenFiscalYear($this->company, $year, $this->owner);

        // Setelah reopen, posting jurnal di tahun 2025 diizinkan
        $entry = $this->accountingService->createJournalEntry(
            company: $this->company,
            referenceType: 'AUDIT_ADJUSTMENT',
            referenceNumber: 'ADJ-AUDIT-2025',
            description: 'Koreksi audit setelah reopen resmi',
            lines: [
                ['account_code' => '1001-001', 'debit' => 75000, 'credit' => 0],
                ['account_code' => '4001-001', 'debit' => 0, 'credit' => 75000],
            ],
            store: $this->store,
            userId: $this->owner->id,
            entryDate: '2025-12-20'
        );

        $this->assertNotNull($entry->id);

        // Tutup kembali tahun buku 2025 setelah koreksi selesai
        $reclosed = $this->closingService->closeFiscalYear($this->company, $year, $this->owner, 'Tutup ulang 2025 pasca audit');
        $this->assertSame('CLOSED', $reclosed->status);
        $this->assertEquals(75000.0, (float) $reclosed->net_profit);
    }
}
