<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AccountingSettingController;
use App\Http\Controllers\AssemblyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BatchExpiryController;
use App\Http\Controllers\CashManagementController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\CustomerReceivableController;
use App\Http\Controllers\DepositManagementController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\FiscalYearClosingController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\TransactionAuditLogController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\CustomerPointController;
use App\Http\Controllers\PromotionReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\ItemStockManagementController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\OperationalReportController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PwaStockOpnameController;
use App\Http\Controllers\PwaStorefrontController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\TaxManagementController;
use App\Http\Controllers\TransferRequestController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\AutoNumberSettingController;
use App\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

// ==========================================
// MARKETPLACE & KATALOG PWA HP PELANGGAN (PUBLIC / GUEST ACCESSIBLE)
// ==========================================
Route::prefix('store')->name('pwa.')->group(function () {
    Route::get('/', [PwaStorefrontController::class, 'index'])->name('index');
    Route::get('/manifest.json', [PwaStorefrontController::class, 'manifest'])->name('manifest');
    Route::get('/products/{id}', [PwaStorefrontController::class, 'productDetail'])->name('product');
    Route::post('/checkout', [PwaStorefrontController::class, 'checkout'])->middleware('throttle:10,1')->name('checkout');
    Route::get('/orders/{orderNumber}', [PwaStorefrontController::class, 'orderStatus'])->name('order-status');
});
Route::get('/katalog', fn () => redirect()->route('pwa.index'));

// PWA Stock Opname Manifest (Public / Browser installable)
Route::get('/warehouse/opname-pwa/manifest.json', [PwaStockOpnameController::class, 'manifest'])->name('warehouse.opname-pwa.manifest');

// ==========================================
// AUTENTIKASI PENGGUNA (GUEST ONLY)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ==========================================
// APLIKASI UTAMA (MEMERLUKAN LOGIN & AKUN AKTIF)
// ==========================================
Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/', function () {
        if (auth()->user()?->role === 'cashier') {
            return redirect()->route('pos.index');
        }

        return redirect()->route('sales.index');
    });

    // 1. MODUL PENJUALAN & BACK-OFFICE
    Route::prefix('sales')->name('sales.')->middleware('role:owner,manager,store_head,finance,sales')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::get('/analytics', [SalesController::class, 'analytics'])->name('analytics');
        Route::get('/transactions', [SalesController::class, 'transactions'])->name('transactions');
        Route::get('/invoices/create', [SalesController::class, 'createInvoice'])->name('invoices.create');
        Route::get('/search-product', [SalesController::class, 'searchProduct'])->name('search-product');
        Route::post('/invoices', [SalesController::class, 'storeInvoice'])->name('invoices.store');
        Route::get('/transactions/{id}/print-invoice', [SalesController::class, 'printInvoice'])->name('print-invoice');
        Route::get('/transactions/{id}/print-delivery-order', [SalesController::class, 'printDeliveryOrder'])->name('print-delivery-order');
        Route::get('/transactions/{id}/print-reminder', [SalesController::class, 'printReminder'])->name('print-reminder');
        Route::get('/transactions/{id}', [SalesController::class, 'show'])->name('transactions.show');

        // Retur Penjualan (Sales Returns & Credit Notes)
        Route::get('/returns', [SalesController::class, 'returnsIndex'])->name('returns.index');
        Route::get('/returns/create', [SalesController::class, 'returnsCreate'])->name('returns.create');
        Route::post('/returns', [SalesController::class, 'returnsStore'])->name('returns.store');
        Route::get('/returns/{id}/print', [SalesController::class, 'printReturnNote'])->name('returns.print');

        // Pesanan Penjualan (Sales Orders / SO)
        Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [SalesOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [SalesOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{id}', [SalesOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{id}/approve', [SalesOrderController::class, 'approve'])->name('orders.approve');
        Route::post('/orders/{id}/complete', [SalesOrderController::class, 'complete'])->name('orders.complete');
        Route::post('/orders/{id}/cancel', [SalesOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{id}/process-to-sale', [SalesOrderController::class, 'processToSale'])->name('orders.process-to-sale');

        // Cek & Manajemen Poin Member Pelanggan
        Route::get('/points', [CustomerPointController::class, 'index'])->name('points.index');
        Route::post('/points/adjust', [CustomerPointController::class, 'adjust'])->name('points.adjust');
        Route::get('/points/lookup/{customerId}', [CustomerPointController::class, 'lookup'])->name('points.lookup');
    });

    // 2. MODUL PEMBELIAN, SMART REPLENISHMENT & HUTANG SUPPLIER (PURCHASING & AP)
    Route::prefix('purchases')->name('purchases.')->group(function () {
        // Aksi pembelian & PO (owner, manager, purchasing)
        Route::middleware('role:owner,manager,purchasing')->group(function () {
            Route::get('/create', [PurchaseController::class, 'create'])->name('create');
            Route::post('/', [PurchaseController::class, 'store'])->name('store');
            Route::post('/{id}/return', [PurchaseController::class, 'storeReturn'])->name('return');
            Route::get('/search-product', [PurchaseController::class, 'searchProduct'])->name('search-product');

            // Retur Pembelian Fleksibel & Pembatalan (Jalur 2)
            Route::get('/returns/create-flexible', [PurchaseController::class, 'createFlexibleReturn'])->name('returns.create-flexible');
            Route::post('/returns/flexible', [PurchaseController::class, 'storeFlexibleReturn'])->name('returns.store-flexible');
            Route::post('/returns/{id}/cancel', [PurchaseController::class, 'cancelReturn'])->name('returns.cancel');
            Route::get('/suppliers/{supplierId}/unpaid-invoices', [PurchaseController::class, 'supplierUnpaidInvoices'])->name('suppliers.unpaid-invoices');

            // Smart Replenishment & Purchase Orders
            Route::get('/orders/create', [PurchaseOrderController::class, 'create'])->name('orders.create');
            Route::post('/orders', [PurchaseOrderController::class, 'store'])->name('orders.store');
            Route::post('/orders/{id}/approve', [PurchaseOrderController::class, 'approve'])->name('orders.approve');
            Route::post('/orders/{id}/receive', [PurchaseOrderController::class, 'receive'])->name('orders.receive');
            Route::post('/orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
        });

        // Pelunasan Hutang Supplier (owner, manager, finance)
        Route::middleware('role:owner,manager,finance')->group(function () {
            Route::post('/pay-supplier', [PurchaseOrderController::class, 'paySupplier'])->name('pay-supplier');
            Route::get('/receipts/{id}/match', [GoodsReceiptController::class, 'matchingForm'])->name('receipts.match');
            Route::post('/receipts/{id}/match', [GoodsReceiptController::class, 'processMatching'])->name('receipts.process-match');
        });

        // Penerimaan Fisik Barang Gudang (LPB) & Surat Jalan (owner, manager, purchasing, warehouse, store_head)
        Route::middleware('role:owner,manager,purchasing,warehouse,store_head')->group(function () {
            Route::get('/receipts', [GoodsReceiptController::class, 'index'])->name('receipts.index');
            Route::get('/receipts/create/{poId}', [GoodsReceiptController::class, 'create'])->name('receipts.create');
            Route::post('/receipts', [GoodsReceiptController::class, 'store'])->name('receipts.store');
            Route::get('/receipts/{id}', [GoodsReceiptController::class, 'show'])->name('receipts.show');
            Route::get('/receipts/{id}/print', [GoodsReceiptController::class, 'print'])->name('receipts.print');
        });

        // Persetujuan Retur Pembelian & Otorisasi Finansial (owner, manager)
        Route::middleware('role:owner,manager')->group(function () {
            Route::post('/returns/{id}/approve', [PurchaseController::class, 'approveReturn'])->name('returns.approve');
        });

        // Read-only viewing (owner, manager, purchasing, finance)
        Route::middleware('role:owner,manager,purchasing,finance')->group(function () {
            Route::get('/', [PurchaseController::class, 'index'])->name('index');
            Route::get('/export', [PurchaseController::class, 'exportPurchases'])->name('export');
            Route::get('/returns', [PurchaseController::class, 'returnsIndex'])->name('returns.index');
            Route::get('/returns/{id}', [PurchaseController::class, 'returnsShow'])->name('returns.show');
            Route::get('/returns/{id}/print', [PurchaseController::class, 'printReturn'])->name('returns.print');
            Route::get('/recommendations', [PurchaseOrderController::class, 'recommendations'])->name('recommendations');
            Route::get('/orders', [PurchaseOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{id}', [PurchaseOrderController::class, 'show'])->name('orders.show');
            Route::get('/orders/{id}/print', [PurchaseOrderController::class, 'print'])->name('orders.print');
            Route::get('/payments/{id}/print', [PurchaseOrderController::class, 'printPayment'])->name('payments.print');
            Route::get('/debt-ledger', [PurchaseOrderController::class, 'debtLedger'])->name('debt-ledger');
            Route::get('/debt-ledger/export', [PurchaseOrderController::class, 'exportDebtLedger'])->name('debt-ledger.export');
            Route::get('/{id}/print', [PurchaseController::class, 'print'])->name('print');
            Route::get('/{id}', [PurchaseController::class, 'show'])->name('show');
            Route::get('/{id}/shelf-tags', [PurchaseController::class, 'printShelfTags'])->name('shelf-tags');
        });
    });

    // 2b. MODUL GUDANG, TRANSFER ANTAR CABANG & BLIND STOCK OPNAME (M04 & M05)
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        // Akses Operasional Gudang (owner, manager, store_head, warehouse)
        Route::middleware('role:owner,manager,store_head,warehouse')->group(function () {
            // Monitoring & Valuasi Persediaan Toko (Rekomendasi 1)
            Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
            Route::get('/inventory/export', [InventoryController::class, 'exportStockSummary'])->name('inventory.export');
            Route::get('/inventory/search-product', [InventoryController::class, 'searchProduct'])->name('inventory.search-product');

            // Saldo Awal Item & Persediaan (Fitur iPos 5 / Ketoko)
            Route::prefix('opening-stock')->name('opening-stock.')->group(function () {
                Route::get('/', [ItemStockManagementController::class, 'openingStockIndex'])->name('index');
                Route::post('/', [ItemStockManagementController::class, 'openingStockStore'])->name('store');
                Route::post('/import', [ItemStockManagementController::class, 'openingStockImport'])->name('import');
                Route::get('/template', [ItemStockManagementController::class, 'downloadOpeningStockTemplate'])->name('template');
            });

            // Stok Minimum Item & Peringatan Restock (Fitur iPos 5 / Ketoko)
            Route::prefix('minimum-stock')->name('minimum-stock.')->group(function () {
                Route::get('/', [ItemStockManagementController::class, 'minimumStockIndex'])->name('index');
                Route::post('/update', [ItemStockManagementController::class, 'minimumStockUpdate'])->name('update');
                Route::post('/import', [ItemStockManagementController::class, 'minimumStockImport'])->name('import');
                Route::get('/template', [ItemStockManagementController::class, 'downloadMinimumStockTemplate'])->name('template');
            });

            // Penyesuaian Stok Harian (Stock Adjustment) & Karantina (Rekomendasi 2)
            Route::get('/adjustments', [InventoryController::class, 'adjustmentsIndex'])->name('adjustments.index');
            Route::get('/adjustments/create', [InventoryController::class, 'adjustmentsCreate'])->name('adjustments.create');
            Route::post('/adjustments', [InventoryController::class, 'adjustmentsStore'])->name('adjustments.store');
            Route::get('/quarantine', [InventoryController::class, 'quarantineIndex'])->name('adjustments.quarantine');
            Route::post('/quarantine/resolve', [InventoryController::class, 'quarantineResolve'])->name('adjustments.quarantine.resolve');

            // Item Masuk & Item Keluar (Stock In / Stock Out Non-Beli & Non-Jual)
            Route::get('/movements/in', [StockMovementController::class, 'indexIn'])->name('movements.in.index');
            Route::get('/movements/in/create', [StockMovementController::class, 'createIn'])->name('movements.in.create');
            Route::post('/movements/in', [StockMovementController::class, 'storeIn'])->name('movements.in.store');
            Route::get('/movements/out', [StockMovementController::class, 'indexOut'])->name('movements.out.index');
            Route::get('/movements/out/create', [StockMovementController::class, 'createOut'])->name('movements.out.create');
            Route::post('/movements/out', [StockMovementController::class, 'storeOut'])->name('movements.out.store');
            Route::get('/movements/{id}', [StockMovementController::class, 'show'])->name('movements.show');
            Route::get('/movements/{id}/print', [StockMovementController::class, 'printSlip'])->name('movements.print');

            // Permintaan Transfer Cabang (Transfer Request) & Smart Restock Cabang
            Route::get('/requests', [TransferRequestController::class, 'index'])->name('requests.index');
            Route::get('/requests/create', [TransferRequestController::class, 'create'])->name('requests.create');
            Route::post('/requests', [TransferRequestController::class, 'store'])->name('requests.store');
            Route::get('/requests/smart-restock', [TransferRequestController::class, 'smartRestock'])->name('requests.smart-restock');
            Route::get('/requests/{transferRequest}', [TransferRequestController::class, 'show'])->name('requests.show');
            Route::post('/requests/{transferRequest}/approve', [TransferRequestController::class, 'approve'])->name('requests.approve');
            Route::post('/requests/{transferRequest}/reject', [TransferRequestController::class, 'reject'])->name('requests.reject');

            // Transfer Antar Cabang (Pengiriman / Surat Jalan)
            Route::get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index');
            Route::get('/transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
            Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store');
            Route::get('/transfers/{id}', [StockTransferController::class, 'show'])->name('transfers.show');
            Route::get('/transfers/{id}/print', [StockTransferController::class, 'printDeliveryNote'])->name('transfers.print');
            Route::post('/transfers/{id}/dispatch', [StockTransferController::class, 'dispatch'])->name('transfers.dispatch');
            Route::post('/transfers/{id}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
            Route::post('/transfers/{id}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel');

            // Stock Opname (Back-Office PC)
            Route::get('/opnames', [StockOpnameController::class, 'index'])->name('opnames.index');
            Route::get('/opnames/create', [StockOpnameController::class, 'create'])->name('opnames.create');
            Route::post('/opnames', [StockOpnameController::class, 'store'])->name('opnames.store');
            Route::get('/opnames/{id}', [StockOpnameController::class, 'show'])->name('opnames.show');
            Route::post('/opnames/{id}/count', [StockOpnameController::class, 'submitCount'])->name('opnames.count');

            // Perakitan & Paket Bundling (Assembly & BOM)
            Route::get('/assemblies', [AssemblyController::class, 'index'])->name('assemblies.index');
            Route::get('/assemblies/create', [AssemblyController::class, 'create'])->name('assemblies.create');
            Route::post('/assemblies', [AssemblyController::class, 'store'])->name('assemblies.store');
            Route::get('/assemblies/boms', [AssemblyController::class, 'boms'])->name('assemblies.boms');
            Route::post('/assemblies/boms', [AssemblyController::class, 'storeBom'])->name('assemblies.boms.store');
            Route::get('/assemblies/bom-data/{productId}', [AssemblyController::class, 'getBom'])->name('assemblies.bom.data');
            Route::get('/assemblies/{id}', [AssemblyController::class, 'show'])->name('assemblies.show');
            Route::post('/assemblies/{id}/disassemble', [AssemblyController::class, 'disassemble'])->name('assemblies.disassemble');

            // Pelacakan Nomor Batch Lot, Expired Date & Rekomendasi FEFO (iPos 5 Ultimate Parity)
            Route::get('/batches', [BatchExpiryController::class, 'index'])->name('batches.index');
            Route::get('/batches/near-expired', [BatchExpiryController::class, 'nearExpired'])->name('batches.near-expired');
            Route::get('/batches/create', [BatchExpiryController::class, 'create'])->name('batches.create');
            Route::post('/batches', [BatchExpiryController::class, 'store'])->name('batches.store');
            Route::get('/batches/fefo/{productId}', [BatchExpiryController::class, 'fefoLookup'])->name('batches.fefo');

            // Konsinyasi Masuk & Rekonsiliasi Tagihan Terjual (iPos 5 Ultimate Parity)
            Route::get('/consignments/receipts', [ConsignmentController::class, 'receipts'])->name('consignments.receipts.index');
            Route::get('/consignments/receipts/create', [ConsignmentController::class, 'createReceipt'])->name('consignments.receipts.create');
            Route::post('/consignments/receipts', [ConsignmentController::class, 'storeReceipt'])->name('consignments.receipts.store');
            Route::get('/consignments/settlements', [ConsignmentController::class, 'settlements'])->name('consignments.settlements.index');
            Route::get('/consignments/settlements/create', [ConsignmentController::class, 'createSettlement'])->name('consignments.settlements.create');
            Route::post('/consignments/settlements', [ConsignmentController::class, 'storeSettlement'])->name('consignments.settlements.store');
            Route::post('/consignments/settlements/{id}/pay', [ConsignmentController::class, 'paySettlement'])->name('consignments.settlements.pay');
            Route::get('/consignments/unsettled-items/{supplierId}', [ConsignmentController::class, 'getUnsettledItems'])->name('consignments.unsettled.items');
        });

        // Persetujuan Rekonsiliasi Opname (Hanya owner, manager, store_head)
        Route::middleware('role:owner,manager,store_head')->group(function () {
            Route::post('/opnames/{id}/approve', [StockOpnameController::class, 'approve'])->name('opnames.approve');
        });

        // PWA Mobile Stock Opname (HP Staf Gudang & Kasir Toko)
        Route::prefix('opname-pwa')->name('opname-pwa.')->middleware('role:owner,manager,store_head,warehouse,cashier')->group(function () {
            Route::get('/', [PwaStockOpnameController::class, 'index'])->name('index');
            Route::post('/create-session', [PwaStockOpnameController::class, 'storeSession'])->name('store-session');
            Route::delete('/session/{stockOpname}', [PwaStockOpnameController::class, 'cancelSession'])->name('cancel-session');
            Route::get('/session/{stockOpname}', [PwaStockOpnameController::class, 'session'])->name('session');
            Route::post('/session/{stockOpname}/update-item', [PwaStockOpnameController::class, 'updateItem'])->name('update-item');
            Route::post('/session/{stockOpname}/submit', [PwaStockOpnameController::class, 'submit'])->name('submit');
        });
    });

    // 2c. MODUL PIUTANG PELANGGAN / ACCOUNTS RECEIVABLE (M02 / M07 / M08)
    Route::prefix('customers')->name('customers.')->middleware('role:owner,manager,store_head,finance,sales,cashier')->group(function () {
        Route::get('/receivables', [CustomerReceivableController::class, 'index'])->name('receivables.index');
        Route::get('/{id}/ledger', [CustomerReceivableController::class, 'ledger'])->name('receivables.ledger');
        Route::get('/{id}/pay', [CustomerReceivableController::class, 'createPayment'])->name('receivables.pay');
        Route::post('/{id}/pay', [CustomerReceivableController::class, 'storePayment'])->name('receivables.pay.store');
        Route::get('/payments/{id}/receipt', [CustomerReceivableController::class, 'showReceipt'])->name('receivables.receipt');

        // Program Loyalitas & Poin Reward Pelanggan (iPos 5 Ultimate Parity)
        Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
        Route::get('/loyalty/settings', [LoyaltyController::class, 'settings'])->name('loyalty.settings');
        Route::post('/loyalty/settings', [LoyaltyController::class, 'updateSettings'])->name('loyalty.settings.update');
        Route::post('/loyalty/expire-points', [LoyaltyController::class, 'expirePoints'])->name('loyalty.expire');
        Route::get('/loyalty/{id}/ledger', [LoyaltyController::class, 'customerLedger'])->name('loyalty.ledger');
        Route::post('/loyalty/{id}/adjust', [LoyaltyController::class, 'adjust'])->name('loyalty.adjust');
        Route::get('/loyalty/{id}/lookup', [LoyaltyController::class, 'pointsLookup'])->name('loyalty.lookup');

        // Hadiah & Merchandise Loyalty
        Route::get('/rewards', [RewardController::class, 'index'])->name('rewards.index');
        Route::get('/rewards/create', [RewardController::class, 'create'])->name('rewards.create');
        Route::post('/rewards', [RewardController::class, 'store'])->name('rewards.store');
        Route::post('/rewards/redeem', [RewardController::class, 'redeem'])->name('rewards.redeem');
        Route::delete('/rewards/{reward}', [RewardController::class, 'destroy'])->name('rewards.destroy');
    });

    // MODUL PROMOSI (Diskon, Buy X Get Y, Tebus Murah)
    Route::prefix('promotions')->name('promotions.')->middleware('role:owner,manager,store_head,sales,cashier')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::get('/create', [PromotionController::class, 'create'])->name('create');
        Route::post('/', [PromotionController::class, 'store'])->name('store');
        Route::patch('/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('toggle');
        Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
        Route::post('/evaluate', [PromotionController::class, 'evaluate'])->name('evaluate');
    });

    // MODUL VOUCHER & GIFT CARDS (Ala Indomaret)
    Route::prefix('vouchers')->name('vouchers.')->middleware('role:owner,manager,store_head,sales,cashier')->group(function () {
        Route::get('/print-coupons', [VoucherController::class, 'printCoupons'])->name('print-coupons');
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::get('/create', [VoucherController::class, 'create'])->name('create');
        Route::post('/', [VoucherController::class, 'store'])->name('store');
        Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('destroy');
    });

    // 2d. MODUL LAPORAN KEUANGAN & BISNIS INTELLIGENCE (M08 & M11)
    // Akses dibatasi ketat khusus Owner, Manager, dan Finance (AGENTS.md Section 6)
    Route::prefix('reports')->name('reports.')->middleware('role:owner,manager,finance')->group(function () {
        Route::get('/shifts', [FinancialReportController::class, 'shifts'])->name('shifts');
        Route::get('/shifts/{id}/z-report', [FinancialReportController::class, 'shiftZReport'])->name('shifts.z-report');
        Route::get('/income-statement', [FinancialReportController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/general-ledger', [FinancialReportController::class, 'generalLedger'])->name('general-ledger');
        Route::get('/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('cash-flow');

        // Laporan Operasional Ritel & Pembelian
        Route::get('/sales', [FinancialReportController::class, 'sales'])->name('sales');
        Route::get('/purchases', [FinancialReportController::class, 'purchases'])->name('purchases');
        Route::get('/aging-receivables', [FinancialReportController::class, 'agingReceivables'])->name('aging-receivables');
        Route::get('/aging-payables', [FinancialReportController::class, 'agingPayables'])->name('aging-payables');

        // Laporan Persediaan & Nilai Stok Barang (Tahap 2)
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/valuation', [InventoryReportController::class, 'valuation'])->name('valuation');
            Route::get('/stock-card', [InventoryReportController::class, 'stockCard'])->name('stock-card');
            Route::get('/low-stock', [InventoryReportController::class, 'lowStock'])->name('low-stock');
            Route::get('/turnover', [InventoryReportController::class, 'turnover'])->name('turnover');
            Route::get('/expiry', [InventoryReportController::class, 'expiry'])->name('expiry');
            Route::get('/opname-discrepancy', [InventoryReportController::class, 'opnameDiscrepancy'])->name('opname-discrepancy');
        });

        // Laporan Operasional & Audit Kasir (Tahap 3)
        Route::prefix('operational')->name('operational.')->group(function () {
            Route::get('/peak-hours', [OperationalReportController::class, 'peakHours'])->name('peak-hours');
            Route::get('/void-returns', [OperationalReportController::class, 'voidReturns'])->name('void-returns');
            Route::get('/consignment', [OperationalReportController::class, 'consignment'])->name('consignment');
        });

        // Laporan Poin, Voucher, Promo & Diskon Penjualan
        Route::get('/promotions', [PromotionReportController::class, 'index'])->name('promotions');
        Route::get('/promotions/print', [PromotionReportController::class, 'printReport'])->name('promotions.print');
    });

    // 2e. MODUL AKUNTANSI, KAS & BUKU BESAR (AGENTS.md Section 5)
    Route::prefix('accounting')->name('accounting.')->group(function () {
        // Manajemen Kas & Bank Harian (Kas Masuk, Kas Keluar, Transfer Kas)
        Route::prefix('cash')->name('cash.')->middleware('role:owner,manager,store_head,finance,cashier')->group(function () {
            Route::get('/', [CashManagementController::class, 'index'])->name('index');
            Route::get('/in', [CashManagementController::class, 'createIn'])->name('in');
            Route::post('/in', [CashManagementController::class, 'storeIn'])->name('store-in');
            Route::get('/out', [CashManagementController::class, 'createOut'])->name('out');
            Route::post('/out', [CashManagementController::class, 'storeOut'])->name('store-out');
            Route::get('/transfer', [CashManagementController::class, 'createTransfer'])->name('transfer');
            Route::post('/transfer', [CashManagementController::class, 'storeTransfer'])->name('store-transfer');
            Route::get('/{id}', [CashManagementController::class, 'show'])->name('show');
        });

        // Bagan Akun & Jurnal Umum (Khusus owner, manager, finance)
        Route::middleware('role:owner,manager,finance')->group(function () {
            // Bagan Akun (Chart of Accounts / COA)
            Route::get('/coa', [AccountingController::class, 'coaIndex'])->name('coa.index');
            Route::post('/coa', [AccountingController::class, 'coaStore'])->name('coa.store');
            Route::put('/coa/{id}', [AccountingController::class, 'coaUpdate'])->name('coa.update');

            // Setting Perkiraan / Pemetaan Akun Otomatis (Fitur No. 13 iPos 5)
            Route::get('/settings', [AccountingSettingController::class, 'index'])->name('settings.index');
            Route::post('/settings', [AccountingSettingController::class, 'update'])->name('settings.update');

            // Jurnal Umum & Pencatatan Biaya Operasional Manual
            Route::get('/journals', [AccountingController::class, 'journalIndex'])->name('journals.index');
            Route::get('/journals/create', [AccountingController::class, 'journalCreate'])->name('journals.create');
            Route::post('/journals', [AccountingController::class, 'journalStore'])->name('journals.store');
            Route::get('/journals/{id}', [AccountingController::class, 'journalShow'])->name('journals.show');

            // Saldo Awal Migrasi Toko (Fitur No. 10, 11, 12 iPos 5)
            Route::prefix('opening')->name('opening.')->group(function () {
                Route::get('/coa', [OpeningBalanceController::class, 'coaIndex'])->name('coa.index');
                Route::post('/coa', [OpeningBalanceController::class, 'coaStore'])->name('coa.store');
                Route::get('/receivables', [OpeningBalanceController::class, 'receivablesIndex'])->name('receivables.index');
                Route::post('/receivables', [OpeningBalanceController::class, 'receivablesStore'])->name('receivables.store');
                Route::get('/receivables/template', [OpeningBalanceController::class, 'receivablesTemplate'])->name('receivables.template');
                Route::post('/receivables/import', [OpeningBalanceController::class, 'receivablesImport'])->name('receivables.import');
                Route::delete('/receivables/{id}', [OpeningBalanceController::class, 'receivablesDestroy'])->name('receivables.destroy');
                Route::get('/payables', [OpeningBalanceController::class, 'payablesIndex'])->name('payables.index');
                Route::post('/payables', [OpeningBalanceController::class, 'payablesStore'])->name('payables.store');
                Route::get('/payables/template', [OpeningBalanceController::class, 'payablesTemplate'])->name('payables.template');
                Route::post('/payables/import', [OpeningBalanceController::class, 'payablesImport'])->name('payables.import');
                Route::delete('/payables/{id}', [OpeningBalanceController::class, 'payablesDestroy'])->name('payables.destroy');
            });

            // Deposito Pelanggan & Supplier (Fitur No. 5, 6, 7 iPos 5)
            Route::prefix('deposits')->name('deposits.')->group(function () {
                Route::prefix('customer')->name('customer.')->group(function () {
                    Route::get('/', [DepositManagementController::class, 'customerIndex'])->name('index');
                    Route::post('/top-up', [DepositManagementController::class, 'customerTopUp'])->name('top-up');
                    Route::post('/withdraw', [DepositManagementController::class, 'customerWithdraw'])->name('withdraw');
                    Route::get('/{id}/ledger', [DepositManagementController::class, 'customerLedger'])->name('ledger');
                });

                Route::prefix('supplier')->name('supplier.')->group(function () {
                    Route::get('/', [DepositManagementController::class, 'supplierIndex'])->name('index');
                    Route::post('/top-up', [DepositManagementController::class, 'supplierTopUp'])->name('top-up');
                    Route::post('/refund', [DepositManagementController::class, 'supplierRefund'])->name('refund');
                    Route::get('/{id}/ledger', [DepositManagementController::class, 'supplierLedger'])->name('ledger');
                });
            });

            // Tutup Buku Tahunan (Fitur No. 18 iPos 5 / Ketoko)
            Route::prefix('closing')->name('closing.')->group(function () {
                Route::get('/', [FiscalYearClosingController::class, 'index'])->name('index');
                Route::post('/', [FiscalYearClosingController::class, 'store'])->name('store');
                Route::post('/{year}/reopen', [FiscalYearClosingController::class, 'reopen'])->name('reopen');
            });

            // Catatan Transaksi / Audit Trail (Fitur No. 14 iPos 5 / Ketoko)
            Route::get('/transaction-logs', [TransactionAuditLogController::class, 'index'])->name('transaction-logs.index');

            // Modul Perpajakan & PPN (AGENTS.md Section 5)
            Route::prefix('tax')->name('tax.')->group(function () {
                Route::get('/settings', [TaxManagementController::class, 'settings'])->name('settings');
                Route::post('/settings', [TaxManagementController::class, 'updateSettings'])->name('settings.update');
                Route::get('/report', [TaxManagementController::class, 'report'])->name('report');
            });
        });
    });

    // 2e. MODUL ASET TETAP & DEPRESIASI OTOMATIS (AGENTS.md Section 5)
    Route::prefix('assets')->name('assets.')->middleware('role:owner,manager,finance')->group(function () {
        Route::get('/', [FixedAssetController::class, 'index'])->name('index');
        Route::get('/create', [FixedAssetController::class, 'create'])->name('create');
        Route::post('/', [FixedAssetController::class, 'store'])->name('store');
        Route::post('/depreciate-all', [FixedAssetController::class, 'depreciateAll'])->name('depreciate-all');
        Route::get('/{id}', [FixedAssetController::class, 'show'])->name('show');
        Route::post('/{id}/depreciate', [FixedAssetController::class, 'depreciate'])->name('depreciate');
        Route::post('/{id}/dispose', [FixedAssetController::class, 'dispose'])->name('dispose');
    });

    // 3. MODUL MASTER DATA
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('master.products');
        });

        // View Master Data
        Route::middleware('role:owner,manager,store_head,finance,purchasing,warehouse')->group(function () {
            Route::get('/products', [MasterDataController::class, 'products'])->name('products');
            Route::get('/products/export', [MasterDataController::class, 'exportProducts'])->name('products.export');
            Route::get('/products/template', [MasterDataController::class, 'downloadProductTemplate'])->name('products.template');
            Route::get('/barcodes/print', [MasterDataController::class, 'barcodePrint'])->name('barcodes.print');
            Route::get('/customers', [MasterDataController::class, 'customers'])->name('customers');
            Route::get('/suppliers', [MasterDataController::class, 'suppliers'])->name('suppliers');
            Route::get('/categories', [MasterDataController::class, 'categories'])->name('categories');
            Route::get('/units', [MasterDataController::class, 'units'])->name('units');
            Route::get('/bank-accounts', [MasterDataController::class, 'bankAccounts'])->name('bank-accounts');
            Route::get('/stores', [MasterDataController::class, 'stores'])->name('stores');
            Route::get('/customer-tiers', [MasterDataController::class, 'customerTiers'])->name('customer-tiers');
            Route::get('/stock-card', [MasterDataController::class, 'stockCard'])->name('stock-card');
        });

        // Modifikasi Master Data (Hanya Owner & Manager)
        Route::middleware('role:owner,manager')->group(function () {
            Route::get('/products/create', [MasterDataController::class, 'createProduct'])->name('products.create');
            Route::post('/products', [MasterDataController::class, 'storeProduct'])->name('products.store');
            Route::get('/products/{id}/edit', [MasterDataController::class, 'editProduct'])->name('products.edit');
            Route::put('/products/{id}', [MasterDataController::class, 'updateProduct'])->name('products.update');
            Route::delete('/products/{id}', [MasterDataController::class, 'destroyProduct'])->name('products.destroy');
            Route::post('/products/import', [MasterDataController::class, 'importProducts'])->name('products.import');
            Route::post('/customers', [MasterDataController::class, 'storeCustomer'])->name('customers.store');
            Route::put('/customers/{id}', [MasterDataController::class, 'updateCustomer'])->name('customers.update');
            Route::patch('/customers/{id}/toggle-status', [MasterDataController::class, 'toggleCustomerStatus'])->name('customers.toggle-status');

            Route::post('/suppliers', [MasterDataController::class, 'storeSupplier'])->name('suppliers.store');
            Route::put('/suppliers/{id}', [MasterDataController::class, 'updateSupplier'])->name('suppliers.update');
            Route::patch('/suppliers/{id}/toggle-status', [MasterDataController::class, 'toggleSupplierStatus'])->name('suppliers.toggle-status');

            Route::post('/categories', [MasterDataController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{id}', [MasterDataController::class, 'updateCategory'])->name('categories.update');
            Route::patch('/categories/{id}/toggle-status', [MasterDataController::class, 'toggleCategoryStatus'])->name('categories.toggle-status');

            Route::post('/units', [MasterDataController::class, 'storeUnit'])->name('units.store');
            Route::put('/units/{id}', [MasterDataController::class, 'updateUnit'])->name('units.update');
            Route::patch('/units/{id}/toggle-status', [MasterDataController::class, 'toggleUnitStatus'])->name('units.toggle-status');

            Route::post('/bank-accounts', [MasterDataController::class, 'storeBankAccount'])->name('bank-accounts.store');
            Route::put('/bank-accounts/{id}', [MasterDataController::class, 'updateBankAccount'])->name('bank-accounts.update');
            Route::patch('/bank-accounts/{id}/toggle-status', [MasterDataController::class, 'toggleBankAccountStatus'])->name('bank-accounts.toggle-status');

            Route::post('/stores', [MasterDataController::class, 'storeStore'])->name('stores.store');
            Route::put('/stores/{id}', [MasterDataController::class, 'updateStore'])->name('stores.update');
            Route::patch('/stores/{id}/toggle-status', [MasterDataController::class, 'toggleStoreStatus'])->name('stores.toggle-status');

            Route::post('/customer-tiers', [MasterDataController::class, 'storeCustomerTier'])->name('customer-tiers.store');
            Route::put('/customer-tiers/{id}', [MasterDataController::class, 'updateCustomerTier'])->name('customer-tiers.update');
            Route::patch('/customer-tiers/{id}/toggle-status', [MasterDataController::class, 'toggleCustomerTierStatus'])->name('customer-tiers.toggle-status');
        });
    });

    // 4. LAYAR KASIR POS (POINT OF SALE)
    Route::prefix('pos')->name('pos.')->middleware('role:owner,manager,store_head,cashier,sales')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/search', [PosController::class, 'search'])->name('search');
        Route::get('/lookup-item', [PosController::class, 'lookupItem'])->name('lookup-item');
        Route::post('/calculate-price', [PosController::class, 'calculatePrice'])->name('calculate-price');
        Route::post('/recalculate-cart', [PosController::class, 'recalculateCart'])->name('recalculate-cart');
        Route::post('/validate-voucher', [PosController::class, 'validateVoucher'])->name('validate-voucher');
        Route::post('/calculate-points-discount', [PosController::class, 'calculatePointsDiscount'])->name('calculate-points-discount');
        Route::post('/open-shift', [PosController::class, 'openShift'])->name('open-shift');
        Route::post('/cash-drop', [PosController::class, 'cashDrop'])->name('cash-drop');
        Route::post('/blind-close', [PosController::class, 'blindClose'])->name('blind-close');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt-sample', [PosController::class, 'sampleReceipt'])->name('receipt.sample');
        Route::get('/receipt/{id}', [PosController::class, 'printReceipt'])->name('print');
        Route::post('/reprint/{id}', [PosController::class, 'reprintReceipt'])->name('reprint');
        Route::get('/recent-transactions', [PosController::class, 'getRecentTransactions'])->name('recent');
        Route::get('/lookup-invoice', [PosController::class, 'lookupInvoice'])->name('lookup-invoice');
        Route::post('/transactions/{id}/return', [PosController::class, 'returnTransaction'])->name('return');
        Route::post('/shifts/{id}/resolve-discrepancy', [PosController::class, 'resolveDiscrepancy'])->name('resolve-discrepancy');
        Route::post('/settings/rounding', [PosController::class, 'saveRoundingSettings'])->name('settings.rounding');
    });

    // 5. MODUL PENGATURAN (SETTINGS)
    Route::prefix('settings')->name('settings.')->middleware('role:owner,manager')->group(function () {
        Route::get('/', function () {
            return redirect()->route('settings.general');
        });
        Route::get('/general', [SettingController::class, 'index'])->name('general');
        Route::post('/general', [SettingController::class, 'updateGeneral'])->name('general.update');
        Route::post('/wallpaper/reset', [SettingController::class, 'resetWallpaper'])->name('wallpaper.reset');
        Route::post('/logo/reset', [SettingController::class, 'resetLogo'])->name('logo.reset');

        // 5a. CABANG TOKO & GUDANG MULTI-STORE
        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/', [SettingController::class, 'stores'])->name('index');
            Route::post('/', [SettingController::class, 'storeStore'])->name('store');
            Route::put('/{id}', [SettingController::class, 'updateStore'])->name('update');
            Route::patch('/{id}/toggle-status', [SettingController::class, 'toggleStoreStatus'])->name('toggle-status');
            Route::patch('/{id}/set-main', [SettingController::class, 'setMainStore'])->name('set-main');
        });

        // 5c. KONFIGURASI PRINTER STRUK THERMAL & FORMAT LAYOUT (58mm / 80mm)
        Route::get('/printer', [SettingController::class, 'printer'])->name('printer');
        Route::post('/printer', [SettingController::class, 'updatePrinter'])->name('printer.update');
        Route::get('/printer/test-print', [SettingController::class, 'testPrint'])->name('printer.test');

        // 5e. PENGATURAN OPERASIONAL KASIR, MODAL AWAL & LIMIT KAS DROP
        Route::get('/cashier', [SettingController::class, 'cashier'])->name('cashier');
        Route::post('/cashier', [SettingController::class, 'updateCashier'])->name('cashier.update');

        // 5b. MANAJEMEN PENGGUNA, STAF KASIR & KELOMPOK HAK AKSES CEKLIST (RBAC)
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::post('/', [UserManagementController::class, 'storeUser'])->name('store');
            Route::put('/{id}', [UserManagementController::class, 'updateUser'])->name('update');
            Route::patch('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
            Route::post('/{id}/set-pin', [UserManagementController::class, 'setManagerPin'])->name('set-pin');
        });

        Route::prefix('user-groups')->name('user-groups.')->group(function () {
            Route::post('/', [UserManagementController::class, 'storeGroup'])->name('store');
            Route::put('/{id}', [UserManagementController::class, 'updateGroup'])->name('update');
            Route::post('/{id}/preset', [UserManagementController::class, 'applyPreset'])->name('preset');
            Route::delete('/{id}', [UserManagementController::class, 'destroyGroup'])->name('destroy');
        });

        // 5d. PENOMORAN OTOMATIS & FORMAT NOMOR TRANSAKSI (AUTO-NUMBERING)
        Route::prefix('numbering')->name('numbering.')->group(function () {
            Route::get('/', [AutoNumberSettingController::class, 'index'])->name('index');
            Route::put('/{id}', [AutoNumberSettingController::class, 'update'])->name('update');
            Route::patch('/{id}/reset-sequence', [AutoNumberSettingController::class, 'resetSequence'])->name('reset-sequence');
            Route::post('/restore-defaults', [AutoNumberSettingController::class, 'restoreDefaults'])->name('restore-defaults');
        });

        // 5f. BACKUP, RESTORE & PEMELIHARAAN DATABASE (MAINTENANCE)
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MaintenanceController::class, 'index'])->name('index');
            Route::post('/backup', [MaintenanceController::class, 'createBackup'])->name('backup');
            Route::get('/download/{filename}', [MaintenanceController::class, 'downloadBackup'])->name('download');
            Route::delete('/delete/{filename}', [MaintenanceController::class, 'deleteBackup'])->name('delete');
            Route::post('/clear-cache', [MaintenanceController::class, 'clearCache'])->name('clear-cache');
            Route::post('/optimize-db', [MaintenanceController::class, 'optimizeDatabase'])->name('optimize-db');
            Route::post('/switch-mode', [MaintenanceController::class, 'switchMode'])->name('switch-mode');
            // Demo Reset — owner only (extra protection via confirm_text)
            Route::post('/reset-demo', [MaintenanceController::class, 'resetDemo'])->name('reset-demo');
        });

        // 5g. AUDIT LOG & JEJAK AKTIVITAS SISTEM (ANTI-FRAUD)
        Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');

        // 5h. PEMBARUAN SISTEM / SYSTEM AUTO-UPDATER (COMMERCIAL GRADE)
        Route::prefix('update')->name('update.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SystemUpdateController::class, 'index'])->name('index');
            Route::get('/check', [\App\Http\Controllers\SystemUpdateController::class, 'check'])->name('check');
            Route::post('/apply', [\App\Http\Controllers\SystemUpdateController::class, 'applyUpdate'])->name('apply');
        });
    });

});
