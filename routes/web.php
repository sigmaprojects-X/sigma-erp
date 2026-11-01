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
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

// ==========================================
// MARKETPLACE & KATALOG PWA HP PELANGGAN (PUBLIC / GUEST ACCESSIBLE)
// ==========================================
Route::prefix('store')->name('pwa.')->group(function () {
    Route::get('/', [PwaStorefrontController::class, 'index'])->name('index');
    Route::get('/c/{company_code}', [PwaStorefrontController::class, 'index'])->name('company');
    Route::get('/s/{branch_code}', [PwaStorefrontController::class, 'index'])->name('branch');
    Route::get('/manifest.json', [PwaStorefrontController::class, 'manifest'])->name('manifest');
    Route::get('/products/{id}', [PwaStorefrontController::class, 'productDetail'])->name('product');
    Route::post('/checkout', [PwaStorefrontController::class, 'checkout'])->middleware('throttle:10,1')->name('checkout');
    Route::get('/orders/{orderNumber}', [PwaStorefrontController::class, 'orderStatus'])->name('order-status');
});
Route::get('/toko/{company_code}', [PwaStorefrontController::class, 'index'])->name('pwa.toko');
Route::get('/cabang/{branch_code}', [PwaStorefrontController::class, 'index'])->name('pwa.cabang');
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

    // SAKELAR MULTI-COMPANY (COMPANY SWITCHER UNTUK OWNER)
    Route::post('/company/switch', [CompanyController::class, 'switchCompany'])->name('company.switch');

    // Cetak Nota Retur Penjualan (Dapat diakses oleh Kasir POS dan Manajemen)
    Route::get('/sales/returns/{id}/print', [SalesController::class, 'printReturnNote'])
        ->middleware(['role:owner,manager,store_head,finance,sales,cashier', 'permission:pos_return.buka,sales_invoice.buka'])
        ->name('sales.returns.print');

    // 1. MODUL PENJUALAN & BACK-OFFICE
    Route::prefix('sales')->name('sales.')->middleware('role:owner,manager,store_head,finance,sales')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->middleware('permission:sales_invoice.buka,sales_order.buka,menu_view_sales.buka')->name('index');
        Route::get('/analytics', [SalesController::class, 'analytics'])->middleware('permission:sales_invoice.buka,report_sales.buka')->name('analytics');
        Route::get('/transactions', [SalesController::class, 'transactions'])->middleware('permission:sales_invoice.buka,pos_terminal.buka')->name('transactions');
        Route::get('/invoices/create', [SalesController::class, 'createInvoice'])->middleware('permission:sales_invoice.baru')->name('invoices.create');
        Route::get('/search-product', [SalesController::class, 'searchProduct'])->middleware('permission:sales_invoice.buka,sales_order.buka,master_item.buka')->name('search-product');
        Route::post('/invoices', [SalesController::class, 'storeInvoice'])->middleware('permission:sales_invoice.baru')->name('invoices.store');
        Route::get('/transactions/{id}/print-invoice', [SalesController::class, 'printInvoice'])->middleware('permission:sales_invoice.buka')->name('print-invoice');
        Route::get('/transactions/{id}/print-delivery-order', [SalesController::class, 'printDeliveryOrder'])->middleware('permission:sales_invoice.buka')->name('print-delivery-order');
        Route::get('/transactions/{id}/print-reminder', [SalesController::class, 'printReminder'])->middleware('permission:sales_invoice.buka,sales_receivable_payment.buka')->name('print-reminder');
        Route::get('/transactions/{id}', [SalesController::class, 'show'])->middleware('permission:sales_invoice.buka,pos_terminal.buka')->name('transactions.show');

        // Retur Penjualan (Sales Returns & Credit Notes)
        Route::get('/returns', [SalesController::class, 'returnsIndex'])->middleware('permission:pos_return.buka,sales_invoice.buka')->name('returns.index');
        Route::get('/returns/create', [SalesController::class, 'returnsCreate'])->middleware('permission:pos_return.baru,sales_invoice.baru')->name('returns.create');
        Route::post('/returns', [SalesController::class, 'returnsStore'])->middleware('permission:pos_return.baru,sales_invoice.baru')->name('returns.store');

        // Pesanan Penjualan (Sales Orders / SO)
        Route::get('/orders', [SalesOrderController::class, 'index'])->middleware('permission:sales_order.buka')->name('orders.index');
        Route::get('/orders/create', [SalesOrderController::class, 'create'])->middleware('permission:sales_order.baru')->name('orders.create');
        Route::post('/orders', [SalesOrderController::class, 'store'])->middleware('permission:sales_order.baru')->name('orders.store');
        Route::get('/orders/{id}', [SalesOrderController::class, 'show'])->middleware('permission:sales_order.buka')->name('orders.show');
        Route::post('/orders/{id}/approve', [SalesOrderController::class, 'approve'])->middleware('permission:sales_order.ubah')->name('orders.approve');
        Route::post('/orders/{id}/complete', [SalesOrderController::class, 'complete'])->middleware('permission:sales_order.ubah')->name('orders.complete');
        Route::post('/orders/{id}/cancel', [SalesOrderController::class, 'cancel'])->middleware('permission:sales_order.hapus,sales_order.ubah')->name('orders.cancel');
        Route::post('/orders/{id}/process-to-sale', [SalesOrderController::class, 'processToSale'])->middleware('permission:sales_invoice.baru,sales_order.ubah')->name('orders.process-to-sale');

        // Cek & Manajemen Poin Member Pelanggan
        Route::get('/points', [CustomerPointController::class, 'index'])->middleware('permission:master_customer.buka')->name('points.index');
        Route::post('/points/adjust', [CustomerPointController::class, 'adjust'])->middleware('permission:master_customer.ubah')->name('points.adjust');
        Route::get('/points/lookup/{customerId}', [CustomerPointController::class, 'lookup'])->middleware('permission:master_customer.buka')->name('points.lookup');
    });

    // 2. MODUL PEMBELIAN, SMART REPLENISHMENT & HUTANG SUPPLIER (PURCHASING & AP)
    Route::prefix('purchases')->name('purchases.')->group(function () {
        // Aksi pembelian & PO (owner, manager, purchasing)
        Route::middleware('role:owner,manager,purchasing')->group(function () {
            Route::get('/create', [PurchaseController::class, 'create'])->middleware('permission:purchase_invoice.baru,purchase_order.baru')->name('create');
            Route::post('/', [PurchaseController::class, 'store'])->middleware('permission:purchase_invoice.baru,purchase_order.baru')->name('store');
            Route::post('/{id}/return', [PurchaseController::class, 'storeReturn'])->middleware('permission:purchase_return.baru')->name('return');
            Route::get('/search-product', [PurchaseController::class, 'searchProduct'])->middleware('permission:purchase_order.buka,purchase_invoice.buka,master_item.buka')->name('search-product');

            // Retur Pembelian Fleksibel & Pembatalan (Jalur 2)
            Route::get('/returns/create-flexible', [PurchaseController::class, 'createFlexibleReturn'])->middleware('permission:purchase_return.baru')->name('returns.create-flexible');
            Route::post('/returns/flexible', [PurchaseController::class, 'storeFlexibleReturn'])->middleware('permission:purchase_return.baru')->name('returns.store-flexible');
            Route::post('/returns/{id}/cancel', [PurchaseController::class, 'cancelReturn'])->middleware('permission:purchase_return.hapus,purchase_return.ubah,purchase_return.baru')->name('returns.cancel');
            Route::get('/suppliers/{supplierId}/unpaid-invoices', [PurchaseController::class, 'supplierUnpaidInvoices'])->middleware('permission:purchase_return.buka,purchase_payment.buka')->name('suppliers.unpaid-invoices');

            // Smart Replenishment & Purchase Orders
            Route::get('/orders/create', [PurchaseOrderController::class, 'create'])->middleware('permission:purchase_order.baru')->name('orders.create');
            Route::post('/orders', [PurchaseOrderController::class, 'store'])->middleware('permission:purchase_order.baru')->name('orders.store');
            Route::post('/orders/{id}/approve', [PurchaseOrderController::class, 'approve'])->middleware('permission:purchase_order.ubah')->name('orders.approve');
            Route::post('/orders/{id}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:purchase_order.ubah,purchase_receipt.baru')->name('orders.receive');
            Route::post('/orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('permission:purchase_order.hapus,purchase_order.ubah')->name('orders.cancel');
        });

        // Pelunasan Hutang Supplier (owner, manager, finance)
        Route::middleware('role:owner,manager,finance')->group(function () {
            Route::post('/pay-supplier', [PurchaseOrderController::class, 'paySupplier'])->middleware('permission:purchase_payment.baru')->name('pay-supplier');
            Route::get('/receipts/{id}/match', [GoodsReceiptController::class, 'matchingForm'])->middleware('permission:purchase_invoice.buka,purchase_invoice.ubah')->name('receipts.match');
            Route::post('/receipts/{id}/match', [GoodsReceiptController::class, 'processMatching'])->middleware('permission:purchase_invoice.baru,purchase_invoice.ubah')->name('receipts.process-match');
        });

        // Penerimaan Fisik Barang Gudang (LPB) & Surat Jalan (owner, manager, purchasing, warehouse, store_head)
        Route::middleware('role:owner,manager,purchasing,warehouse,store_head')->group(function () {
            Route::get('/receipts', [GoodsReceiptController::class, 'index'])->middleware('permission:purchase_receipt.buka')->name('receipts.index');
            Route::get('/receipts/create/{poId}', [GoodsReceiptController::class, 'create'])->middleware('permission:purchase_receipt.baru')->name('receipts.create');
            Route::post('/receipts', [GoodsReceiptController::class, 'store'])->middleware('permission:purchase_receipt.baru')->name('receipts.store');
            Route::get('/receipts/{id}', [GoodsReceiptController::class, 'show'])->middleware('permission:purchase_receipt.buka')->name('receipts.show');
            Route::get('/receipts/{id}/print', [GoodsReceiptController::class, 'print'])->middleware('permission:purchase_receipt.buka')->name('receipts.print');
        });

        // Persetujuan Retur Pembelian & Otorisasi Finansial (owner, manager)
        Route::middleware('role:owner,manager')->group(function () {
            Route::post('/returns/{id}/approve', [PurchaseController::class, 'approveReturn'])->middleware('permission:purchase_return.ubah')->name('returns.approve');
        });

        // Read-only viewing (owner, manager, purchasing, finance)
        Route::middleware('role:owner,manager,purchasing,finance')->group(function () {
            Route::get('/', [PurchaseController::class, 'index'])->middleware('permission:purchase_order.buka,purchase_invoice.buka')->name('index');
            Route::get('/export', [PurchaseController::class, 'exportPurchases'])->middleware('permission:purchase_order.buka,purchase_invoice.buka')->name('export');
            Route::get('/returns', [PurchaseController::class, 'returnsIndex'])->middleware('permission:purchase_return.buka')->name('returns.index');
            Route::get('/returns/{id}', [PurchaseController::class, 'returnsShow'])->middleware('permission:purchase_return.buka')->name('returns.show');
            Route::get('/returns/{id}/print', [PurchaseController::class, 'printReturn'])->middleware('permission:purchase_return.buka')->name('returns.print');
            Route::get('/recommendations', [PurchaseOrderController::class, 'recommendations'])->middleware('permission:purchase_order.buka')->name('recommendations');
            Route::get('/orders', [PurchaseOrderController::class, 'index'])->middleware('permission:purchase_order.buka')->name('orders.index');
            Route::get('/orders/{id}', [PurchaseOrderController::class, 'show'])->middleware('permission:purchase_order.buka')->name('orders.show');
            Route::get('/orders/{id}/print', [PurchaseOrderController::class, 'print'])->middleware('permission:purchase_order.buka')->name('orders.print');
            Route::get('/payments/{id}/print', [PurchaseOrderController::class, 'printPayment'])->middleware('permission:purchase_payment.buka')->name('payments.print');
            Route::get('/debt-ledger', [PurchaseOrderController::class, 'debtLedger'])->middleware('permission:purchase_payment.buka,purchase_invoice.buka')->name('debt-ledger');
            Route::get('/debt-ledger/export', [PurchaseOrderController::class, 'exportDebtLedger'])->middleware('permission:purchase_payment.buka,purchase_invoice.buka')->name('debt-ledger.export');
            Route::get('/{id}/print', [PurchaseController::class, 'print'])->middleware('permission:purchase_order.buka,purchase_invoice.buka')->name('print');
            Route::get('/{id}', [PurchaseController::class, 'show'])->middleware('permission:purchase_order.buka,purchase_invoice.buka')->name('show');
            Route::get('/{id}/shelf-tags', [PurchaseController::class, 'printShelfTags'])->middleware('permission:purchase_order.buka,master_item.buka')->name('shelf-tags');
        });
    });

    // 2b. MODUL GUDANG, TRANSFER ANTAR CABANG & BLIND STOCK OPNAME (M04 & M05)
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        // Akses Operasional Gudang (owner, manager, store_head, warehouse)
        Route::middleware('role:owner,manager,store_head,warehouse')->group(function () {
            // Monitoring & Valuasi Persediaan Toko (Rekomendasi 1)
            Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:master_item.buka,kartu_stok.buka')->name('inventory.index');
            Route::get('/inventory/export', [InventoryController::class, 'exportStockSummary'])->middleware('permission:master_item.buka,kartu_stok.buka')->name('inventory.export');
            Route::get('/inventory/search-product', [InventoryController::class, 'searchProduct'])->middleware('permission:master_item.buka,kartu_stok.buka')->name('inventory.search-product');

            // Saldo Awal Item & Persediaan (Fitur iPos 5 / Ketoko)
            Route::prefix('opening-stock')->name('opening-stock.')->group(function () {
                Route::get('/', [ItemStockManagementController::class, 'openingStockIndex'])->middleware('permission:stock_in.buka')->name('index');
                Route::post('/', [ItemStockManagementController::class, 'openingStockStore'])->middleware('permission:stock_in.baru')->name('store');
                Route::post('/import', [ItemStockManagementController::class, 'openingStockImport'])->middleware('permission:stock_in.baru')->name('import');
                Route::get('/template', [ItemStockManagementController::class, 'downloadOpeningStockTemplate'])->middleware('permission:stock_in.buka')->name('template');
            });

            // Stok Minimum Item & Peringatan Restock (Fitur iPos 5 / Ketoko)
            Route::prefix('minimum-stock')->name('minimum-stock.')->group(function () {
                Route::get('/', [ItemStockManagementController::class, 'minimumStockIndex'])->middleware('permission:master_item.buka')->name('index');
                Route::post('/update', [ItemStockManagementController::class, 'minimumStockUpdate'])->middleware('permission:master_item.ubah,stock_in.ubah')->name('update');
                Route::post('/import', [ItemStockManagementController::class, 'minimumStockImport'])->middleware('permission:master_item.ubah,stock_in.ubah')->name('import');
                Route::get('/template', [ItemStockManagementController::class, 'downloadMinimumStockTemplate'])->middleware('permission:master_item.buka')->name('template');
            });

            // Penyesuaian Stok Harian (Stock Adjustment) & Karantina (Rekomendasi 2)
            Route::get('/adjustments', [InventoryController::class, 'adjustmentsIndex'])->middleware('permission:stock_in.buka,stock_out.buka')->name('adjustments.index');
            Route::get('/adjustments/create', [InventoryController::class, 'adjustmentsCreate'])->middleware('permission:stock_in.baru,stock_out.baru')->name('adjustments.create');
            Route::post('/adjustments', [InventoryController::class, 'adjustmentsStore'])->middleware('permission:stock_in.baru,stock_out.baru')->name('adjustments.store');
            Route::get('/quarantine', [InventoryController::class, 'quarantineIndex'])->middleware('permission:stock_in.buka,stock_out.buka')->name('adjustments.quarantine');
            Route::post('/quarantine/resolve', [InventoryController::class, 'quarantineResolve'])->middleware('permission:stock_in.ubah,stock_out.ubah,stock_in.baru')->name('adjustments.quarantine.resolve');

            // Item Masuk & Item Keluar (Stock In / Stock Out Non-Beli & Non-Jual)
            Route::get('/movements/in', [StockMovementController::class, 'indexIn'])->middleware('permission:stock_in.buka')->name('movements.in.index');
            Route::get('/movements/in/create', [StockMovementController::class, 'createIn'])->middleware('permission:stock_in.baru')->name('movements.in.create');
            Route::post('/movements/in', [StockMovementController::class, 'storeIn'])->middleware('permission:stock_in.baru')->name('movements.in.store');
            Route::get('/movements/out', [StockMovementController::class, 'indexOut'])->middleware('permission:stock_out.buka')->name('movements.out.index');
            Route::get('/movements/out/create', [StockMovementController::class, 'createOut'])->middleware('permission:stock_out.baru')->name('movements.out.create');
            Route::post('/movements/out', [StockMovementController::class, 'storeOut'])->middleware('permission:stock_out.baru')->name('movements.out.store');
            Route::get('/movements/{id}', [StockMovementController::class, 'show'])->middleware('permission:stock_in.buka,stock_out.buka')->name('movements.show');
            Route::get('/movements/{id}/print', [StockMovementController::class, 'printSlip'])->middleware('permission:stock_in.buka,stock_out.buka')->name('movements.print');

            // Permintaan Transfer Cabang (Transfer Request) & Smart Restock Cabang
            Route::get('/requests', [TransferRequestController::class, 'index'])->middleware('permission:stock_transfer.buka')->name('requests.index');
            Route::get('/requests/create', [TransferRequestController::class, 'create'])->middleware('permission:stock_transfer.baru')->name('requests.create');
            Route::post('/requests', [TransferRequestController::class, 'store'])->middleware('permission:stock_transfer.baru')->name('requests.store');
            Route::get('/requests/smart-restock', [TransferRequestController::class, 'smartRestock'])->middleware('permission:stock_transfer.buka')->name('requests.smart-restock');
            Route::get('/requests/{transferRequest}', [TransferRequestController::class, 'show'])->middleware('permission:stock_transfer.buka')->name('requests.show');
            Route::post('/requests/{transferRequest}/approve', [TransferRequestController::class, 'approve'])->middleware('permission:stock_transfer.ubah')->name('requests.approve');
            Route::post('/requests/{transferRequest}/reject', [TransferRequestController::class, 'reject'])->middleware('permission:stock_transfer.ubah')->name('requests.reject');

            // Transfer Antar Cabang (Pengiriman / Surat Jalan)
            Route::get('/transfers', [StockTransferController::class, 'index'])->middleware('permission:stock_transfer.buka')->name('transfers.index');
            Route::get('/transfers/create', [StockTransferController::class, 'create'])->middleware('permission:stock_transfer.baru')->name('transfers.create');
            Route::post('/transfers', [StockTransferController::class, 'store'])->middleware('permission:stock_transfer.baru')->name('transfers.store');
            Route::get('/transfers/{id}', [StockTransferController::class, 'show'])->middleware('permission:stock_transfer.buka')->name('transfers.show');
            Route::get('/transfers/{id}/print', [StockTransferController::class, 'printDeliveryNote'])->middleware('permission:stock_transfer.buka')->name('transfers.print');
            Route::post('/transfers/{id}/dispatch', [StockTransferController::class, 'dispatch'])->middleware('permission:stock_transfer.ubah,stock_transfer.baru')->name('transfers.dispatch');
            Route::post('/transfers/{id}/receive', [StockTransferController::class, 'receive'])->middleware('permission:stock_transfer.ubah,stock_transfer.baru')->name('transfers.receive');
            Route::post('/transfers/{id}/cancel', [StockTransferController::class, 'cancel'])->middleware('permission:stock_transfer.hapus,stock_transfer.ubah')->name('transfers.cancel');

            // Stock Opname (Back-Office PC)
            Route::get('/opnames', [StockOpnameController::class, 'index'])->middleware('permission:stock_opname.buka')->name('opnames.index');
            Route::get('/opnames/create', [StockOpnameController::class, 'create'])->middleware('permission:stock_opname.baru')->name('opnames.create');
            Route::post('/opnames', [StockOpnameController::class, 'store'])->middleware('permission:stock_opname.baru')->name('opnames.store');
            Route::get('/opnames/{id}', [StockOpnameController::class, 'show'])->middleware('permission:stock_opname.buka')->name('opnames.show');
            Route::post('/opnames/{id}/count', [StockOpnameController::class, 'submitCount'])->middleware('permission:stock_opname.baru,stock_opname.ubah')->name('opnames.count');

            // Perakitan & Paket Bundling (Assembly & BOM)
            Route::get('/assemblies', [AssemblyController::class, 'index'])->middleware('permission:assembly_process.buka,assembly_bom.buka')->name('assemblies.index');
            Route::get('/assemblies/create', [AssemblyController::class, 'create'])->middleware('permission:assembly_process.baru')->name('assemblies.create');
            Route::post('/assemblies', [AssemblyController::class, 'store'])->middleware('permission:assembly_process.baru')->name('assemblies.store');
            Route::get('/assemblies/boms', [AssemblyController::class, 'boms'])->middleware('permission:assembly_bom.buka')->name('assemblies.boms');
            Route::post('/assemblies/boms', [AssemblyController::class, 'storeBom'])->middleware('permission:assembly_bom.baru,assembly_bom.ubah')->name('assemblies.boms.store');
            Route::get('/assemblies/bom-data/{productId}', [AssemblyController::class, 'getBom'])->middleware('permission:assembly_bom.buka,assembly_process.buka')->name('assemblies.bom.data');
            Route::get('/assemblies/{id}', [AssemblyController::class, 'show'])->middleware('permission:assembly_process.buka')->name('assemblies.show');
            Route::post('/assemblies/{id}/disassemble', [AssemblyController::class, 'disassemble'])->middleware('permission:assembly_disassemble.buka,assembly_disassemble.baru')->name('assemblies.disassemble');

            // Pelacakan Nomor Batch Lot, Expired Date & Rekomendasi FEFO (iPos 5 Ultimate Parity)
            Route::get('/batches', [BatchExpiryController::class, 'index'])->middleware('permission:batch_expiry_fefo.buka')->name('batches.index');
            Route::get('/batches/near-expired', [BatchExpiryController::class, 'nearExpired'])->middleware('permission:batch_expiry_fefo.buka')->name('batches.near-expired');
            Route::get('/batches/create', [BatchExpiryController::class, 'create'])->middleware('permission:batch_expiry_fefo.baru')->name('batches.create');
            Route::post('/batches', [BatchExpiryController::class, 'store'])->middleware('permission:batch_expiry_fefo.baru')->name('batches.store');
            Route::get('/batches/fefo/{productId}', [BatchExpiryController::class, 'fefoLookup'])->middleware('permission:batch_expiry_fefo.buka')->name('batches.fefo');

            // Konsinyasi Masuk & Rekonsiliasi Tagihan Terjual (iPos 5 Ultimate Parity)
            Route::get('/consignments/receipts', [ConsignmentController::class, 'receipts'])->middleware('permission:consignment_management.buka')->name('consignments.receipts.index');
            Route::get('/consignments/receipts/create', [ConsignmentController::class, 'createReceipt'])->middleware('permission:consignment_management.baru')->name('consignments.receipts.create');
            Route::post('/consignments/receipts', [ConsignmentController::class, 'storeReceipt'])->middleware('permission:consignment_management.baru')->name('consignments.receipts.store');
            Route::get('/consignments/settlements', [ConsignmentController::class, 'settlements'])->middleware('permission:consignment_management.buka')->name('consignments.settlements.index');
            Route::get('/consignments/settlements/create', [ConsignmentController::class, 'createSettlement'])->middleware('permission:consignment_management.baru')->name('consignments.settlements.create');
            Route::post('/consignments/settlements', [ConsignmentController::class, 'storeSettlement'])->middleware('permission:consignment_management.baru')->name('consignments.settlements.store');
            Route::post('/consignments/settlements/{id}/pay', [ConsignmentController::class, 'paySettlement'])->middleware('permission:consignment_management.ubah,consignment_management.baru')->name('consignments.settlements.pay');
            Route::get('/consignments/unsettled-items/{supplierId}', [ConsignmentController::class, 'getUnsettledItems'])->middleware('permission:consignment_management.buka')->name('consignments.unsettled.items');
        });

        // Persetujuan Rekonsiliasi Opname (Hanya owner, manager, store_head)
        Route::middleware('role:owner,manager,store_head')->group(function () {
            Route::post('/opnames/{id}/approve', [StockOpnameController::class, 'approve'])->middleware('permission:stock_opname_approve.buka,stock_opname_approve.baru')->name('opnames.approve');
        });

        // PWA Mobile Stock Opname (HP Staf Gudang & Kasir Toko)
        Route::prefix('opname-pwa')->name('opname-pwa.')->middleware(['role:owner,manager,store_head,warehouse,cashier', 'permission:stock_opname.buka,stock_opname.baru'])->group(function () {
            Route::get('/', [PwaStockOpnameController::class, 'index'])->name('index');
            Route::post('/create-session', [PwaStockOpnameController::class, 'storeSession'])->name('store-session');
            Route::delete('/session/{stockOpname}', [PwaStockOpnameController::class, 'cancelSession'])->name('cancel-session');
            Route::get('/session/{stockOpname}', [PwaStockOpnameController::class, 'session'])->name('session');
            Route::post('/session/{stockOpname}/update-item', [PwaStockOpnameController::class, 'updateItem'])->name('update-item');
            Route::post('/session/{stockOpname}/submit', [PwaStockOpnameController::class, 'submit'])->name('submit');
        });
    });

    // 2c. MODUL PIUTANG PELANGGAN / ACCOUNTS RECEIVABLE (M02 / M07 / M08)
    Route::prefix('customers')->name('customers.')->group(function () {
        // Rute Manajemen & Aksi Sensitif Loyalitas / Hadiah (Hanya Owner, Manager, Store Head)
        Route::middleware('role:owner,manager,store_head')->group(function () {
            Route::get('/loyalty/settings', [LoyaltyController::class, 'settings'])->name('loyalty.settings');
            Route::post('/loyalty/settings', [LoyaltyController::class, 'updateSettings'])->name('loyalty.settings.update');
            Route::post('/loyalty/expire-points', [LoyaltyController::class, 'expirePoints'])->name('loyalty.expire');
            Route::post('/loyalty/{id}/adjust', [LoyaltyController::class, 'adjust'])->name('loyalty.adjust');

            Route::get('/rewards/create', [RewardController::class, 'create'])->name('rewards.create');
            Route::post('/rewards', [RewardController::class, 'store'])->name('rewards.store');
            Route::delete('/rewards/{reward}', [RewardController::class, 'destroy'])->name('rewards.destroy');
        });

        // Rute Baca & Operasional Kasir / Staf (Owner, Manager, Store Head, Finance, Sales, Cashier)
        Route::middleware('role:owner,manager,store_head,finance,sales,cashier')->group(function () {
            Route::get('/receivables', [CustomerReceivableController::class, 'index'])->middleware('permission:sales_receivable_payment.buka,pos_credit_payment.buka')->name('receivables.index');
            Route::get('/{id}/ledger', [CustomerReceivableController::class, 'ledger'])->middleware('permission:sales_receivable_payment.buka,pos_credit_payment.buka')->name('receivables.ledger');
            Route::get('/{id}/pay', [CustomerReceivableController::class, 'createPayment'])->middleware('permission:sales_receivable_payment.baru,pos_credit_payment.buka')->name('receivables.pay');
            Route::post('/{id}/pay', [CustomerReceivableController::class, 'storePayment'])->middleware('permission:sales_receivable_payment.baru,pos_credit_payment.buka')->name('receivables.pay.store');
            Route::get('/payments/{id}/receipt', [CustomerReceivableController::class, 'showReceipt'])->middleware('permission:sales_receivable_payment.buka,pos_credit_payment.buka')->name('receivables.receipt');

            // Program Loyalitas & Poin Reward Pelanggan (Baca & Lookup POS)
            Route::get('/loyalty', [LoyaltyController::class, 'index'])->middleware('permission:master_customer.buka,pos_terminal.buka')->name('loyalty.index');
            Route::get('/loyalty/{id}/ledger', [LoyaltyController::class, 'customerLedger'])->middleware('permission:master_customer.buka,pos_terminal.buka')->name('loyalty.ledger');
            Route::get('/loyalty/{id}/lookup', [LoyaltyController::class, 'pointsLookup'])->middleware('permission:master_customer.buka,pos_terminal.buka')->name('loyalty.lookup');

            // Hadiah & Merchandise Loyalty (Baca & Klaim Penukaran)
            Route::get('/rewards', [RewardController::class, 'index'])->middleware('permission:master_customer.buka,pos_terminal.buka')->name('rewards.index');
            Route::post('/rewards/redeem', [RewardController::class, 'redeem'])->middleware('permission:master_customer.buka,pos_terminal.baru')->name('rewards.redeem');
        });
    });

    // MODUL PROMOSI (Diskon, Buy X Get Y, Tebus Murah)
    Route::prefix('promotions')->name('promotions.')->group(function () {
        // Rute Ubah / Buat / Hapus Promo (Hanya Owner, Manager, Store Head)
        Route::middleware('role:owner,manager,store_head')->group(function () {
            Route::get('/create', [PromotionController::class, 'create'])->name('create');
            Route::post('/', [PromotionController::class, 'store'])->name('store');
            Route::patch('/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('toggle');
            Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
        });

        // Rute Baca & Evaluasi Promo Kasir
        Route::middleware('role:owner,manager,store_head,sales,cashier')->group(function () {
            Route::get('/', [PromotionController::class, 'index'])->middleware('permission:pos_terminal.buka,sales_invoice.buka')->name('index');
            Route::post('/evaluate', [PromotionController::class, 'evaluate'])->middleware('permission:pos_terminal.buka,pos_terminal.baru')->name('evaluate');
        });
    });

    // MODUL VOUCHER & GIFT CARDS (Ala Indomaret)
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        // Rute Buat & Hapus Voucher (Hanya Owner, Manager, Store Head)
        Route::middleware('role:owner,manager,store_head')->group(function () {
            Route::get('/create', [VoucherController::class, 'create'])->name('create');
            Route::post('/', [VoucherController::class, 'store'])->name('store');
            Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('destroy');
        });

        // Rute Baca Daftar Voucher & Cetak Kupon
        Route::middleware('role:owner,manager,store_head,sales,cashier')->group(function () {
            Route::get('/print-coupons', [VoucherController::class, 'printCoupons'])->middleware('permission:pos_terminal.buka,sales_invoice.buka')->name('print-coupons');
            Route::get('/', [VoucherController::class, 'index'])->middleware('permission:pos_terminal.buka,sales_invoice.buka')->name('index');
        });
    });

    // 2d. MODUL LAPORAN KEUANGAN & BISNIS INTELLIGENCE (M08 & M11)
    // Akses dibatasi ketat khusus Owner, Manager, dan Finance (AGENTS.md Section 6)
    Route::prefix('reports')->name('reports.')->middleware('role:owner,manager,finance')->group(function () {
        Route::get('/shifts', [FinancialReportController::class, 'shifts'])->middleware('permission:report_sales.buka')->name('shifts');
        Route::get('/shifts/{id}/z-report', [FinancialReportController::class, 'shiftZReport'])->middleware('permission:report_sales.buka')->name('shifts.z-report');
        Route::get('/income-statement', [FinancialReportController::class, 'incomeStatement'])->middleware('permission:report_profit_loss.buka')->name('income-statement');
        Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->middleware('permission:report_balance_sheet.buka')->name('balance-sheet');
        Route::get('/trial-balance', [FinancialReportController::class, 'trialBalance'])->middleware('permission:report_balance_sheet.buka,accounting_ledger.buka')->name('trial-balance');
        Route::get('/general-ledger', [FinancialReportController::class, 'generalLedger'])->middleware('permission:accounting_ledger.buka,report_balance_sheet.buka')->name('general-ledger');
        Route::get('/cash-flow', [FinancialReportController::class, 'cashFlow'])->middleware('permission:report_cash_flow.buka')->name('cash-flow');

        // Laporan Operasional Ritel & Pembelian
        Route::get('/sales', [FinancialReportController::class, 'sales'])->middleware('permission:report_sales.buka')->name('sales');
        Route::get('/purchases', [FinancialReportController::class, 'purchases'])->middleware('permission:report_purchases.buka')->name('purchases');
        Route::get('/aging-receivables', [FinancialReportController::class, 'agingReceivables'])->middleware('permission:report_aging.buka')->name('aging-receivables');
        Route::get('/aging-payables', [FinancialReportController::class, 'agingPayables'])->middleware('permission:report_aging.buka')->name('aging-payables');

        // Laporan Persediaan & Nilai Stok Barang (Tahap 2)
        Route::prefix('inventory')->name('inventory.')->middleware('permission:report_stock_valuation.buka')->group(function () {
            Route::get('/valuation', [InventoryReportController::class, 'valuation'])->name('valuation');
            Route::get('/stock-card', [InventoryReportController::class, 'stockCard'])->name('stock-card');
            Route::get('/low-stock', [InventoryReportController::class, 'lowStock'])->name('low-stock');
            Route::get('/turnover', [InventoryReportController::class, 'turnover'])->name('turnover');
            Route::get('/expiry', [InventoryReportController::class, 'expiry'])->name('expiry');
            Route::get('/opname-discrepancy', [InventoryReportController::class, 'opnameDiscrepancy'])->name('opname-discrepancy');
        });

        // Laporan Operasional & Audit Kasir (Tahap 3)
        Route::prefix('operational')->name('operational.')->middleware('permission:report_sales.buka')->group(function () {
            Route::get('/peak-hours', [OperationalReportController::class, 'peakHours'])->name('peak-hours');
            Route::get('/void-returns', [OperationalReportController::class, 'voidReturns'])->name('void-returns');
            Route::get('/consignment', [OperationalReportController::class, 'consignment'])->name('consignment');
        });

        // Laporan Poin, Voucher, Promo & Diskon Penjualan
        Route::get('/promotions', [PromotionReportController::class, 'index'])->middleware('permission:report_sales.buka')->name('promotions');
        Route::get('/promotions/print', [PromotionReportController::class, 'printReport'])->middleware('permission:report_sales.buka')->name('promotions.print');
    });

    // 2e. MODUL AKUNTANSI, KAS & BUKU BESAR (AGENTS.md Section 5)
    Route::prefix('accounting')->name('accounting.')->group(function () {
        // Manajemen Kas & Bank Harian (Kas Masuk, Kas Keluar, Transfer Kas)
        Route::prefix('cash')->name('cash.')->middleware('role:owner,manager,store_head,finance,cashier')->group(function () {
            Route::get('/', [CashManagementController::class, 'index'])->middleware('permission:cash_in.buka,cash_out.buka,cash_transfer.buka')->name('index');
            Route::get('/in', [CashManagementController::class, 'createIn'])->middleware('permission:cash_in.baru')->name('in');
            Route::post('/in', [CashManagementController::class, 'storeIn'])->middleware('permission:cash_in.baru')->name('store-in');
            Route::get('/out', [CashManagementController::class, 'createOut'])->middleware('permission:cash_out.baru')->name('out');
            Route::post('/out', [CashManagementController::class, 'storeOut'])->middleware('permission:cash_out.baru')->name('store-out');
            Route::get('/transfer', [CashManagementController::class, 'createTransfer'])->middleware('permission:cash_transfer.baru')->name('transfer');
            Route::post('/transfer', [CashManagementController::class, 'storeTransfer'])->middleware('permission:cash_transfer.baru')->name('store-transfer');
            Route::get('/{id}', [CashManagementController::class, 'show'])->middleware('permission:cash_in.buka,cash_out.buka,cash_transfer.buka')->name('show');
        });

        // Bagan Akun & Jurnal Umum (Khusus owner, manager, finance)
        Route::middleware('role:owner,manager,finance')->group(function () {
            // Bagan Akun (Chart of Accounts / COA)
            Route::get('/coa', [AccountingController::class, 'coaIndex'])->middleware('permission:accounting_coa.buka')->name('coa.index');
            Route::post('/coa', [AccountingController::class, 'coaStore'])->middleware('permission:accounting_coa.baru')->name('coa.store');
            Route::put('/coa/{id}', [AccountingController::class, 'coaUpdate'])->middleware('permission:accounting_coa.ubah')->name('coa.update');

            // Setting Perkiraan / Pemetaan Akun Otomatis (Fitur No. 13 iPos 5)
            Route::get('/settings', [AccountingSettingController::class, 'index'])->middleware('permission:accounting_settings_map.buka')->name('settings.index');
            Route::post('/settings', [AccountingSettingController::class, 'update'])->middleware('permission:accounting_settings_map.ubah')->name('settings.update');

            // Jurnal Umum & Pencatatan Biaya Operasional Manual
            Route::get('/journals', [AccountingController::class, 'journalIndex'])->middleware('permission:accounting_journals.buka')->name('journals.index');
            Route::get('/journals/create', [AccountingController::class, 'journalCreate'])->middleware('permission:accounting_journals.baru')->name('journals.create');
            Route::post('/journals', [AccountingController::class, 'journalStore'])->middleware('permission:accounting_journals.baru')->name('journals.store');
            Route::get('/journals/{id}', [AccountingController::class, 'journalShow'])->middleware('permission:accounting_journals.buka')->name('journals.show');

            // Saldo Awal Migrasi Toko (Fitur No. 10, 11, 12 iPos 5)
            Route::prefix('opening')->name('opening.')->middleware('permission:accounting_coa.buka,accounting_coa.ubah')->group(function () {
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
            Route::prefix('deposits')->name('deposits.')->middleware('permission:cash_in.buka,cash_out.buka,accounting_journals.buka')->group(function () {
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
            Route::prefix('closing')->name('closing.')->middleware('permission:accounting_journals.buka,accounting_ledger.buka')->group(function () {
                Route::get('/', [FiscalYearClosingController::class, 'index'])->name('index');
                Route::post('/', [FiscalYearClosingController::class, 'store'])->name('store');
                Route::post('/{year}/reopen', [FiscalYearClosingController::class, 'reopen'])->name('reopen');
            });

            // Catatan Transaksi / Audit Trail (Fitur No. 14 iPos 5 / Ketoko)
            Route::get('/transaction-logs', [TransactionAuditLogController::class, 'index'])->middleware('permission:accounting_ledger.buka,accounting_journals.buka')->name('transaction-logs.index');

            // Modul Perpajakan & PPN (AGENTS.md Section 5)
            Route::prefix('tax')->name('tax.')->middleware('permission:accounting_settings_map.buka,accounting_ledger.buka')->group(function () {
                Route::get('/settings', [TaxManagementController::class, 'settings'])->name('settings');
                Route::post('/settings', [TaxManagementController::class, 'updateSettings'])->name('settings.update');
                Route::get('/report', [TaxManagementController::class, 'report'])->name('report');
            });
        });
    });

    // 2e. MODUL ASET TETAP & DEPRESIASI OTOMATIS (AGENTS.md Section 5)
    Route::prefix('assets')->name('assets.')->middleware('role:owner,manager,finance')->group(function () {
        Route::get('/', [FixedAssetController::class, 'index'])->middleware('permission:fixed_assets.buka')->name('index');
        Route::get('/create', [FixedAssetController::class, 'create'])->middleware('permission:fixed_assets.baru')->name('create');
        Route::post('/', [FixedAssetController::class, 'store'])->middleware('permission:fixed_assets.baru')->name('store');
        Route::post('/depreciate-all', [FixedAssetController::class, 'depreciateAll'])->middleware('permission:fixed_assets.ubah')->name('depreciate-all');
        Route::get('/{id}', [FixedAssetController::class, 'show'])->middleware('permission:fixed_assets.buka')->name('show');
        Route::post('/{id}/depreciate', [FixedAssetController::class, 'depreciate'])->middleware('permission:fixed_assets.ubah')->name('depreciate');
        Route::post('/{id}/dispose', [FixedAssetController::class, 'dispose'])->middleware('permission:fixed_assets.ubah,fixed_assets.hapus')->name('dispose');
    });

    // 3. MODUL MASTER DATA
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('master.products');
        });

        // View Master Data
        Route::middleware('role:owner,manager,store_head,finance,purchasing,warehouse')->group(function () {
            Route::get('/products', [MasterDataController::class, 'products'])->middleware('permission:master_item.buka')->name('products');
            Route::get('/products/export', [MasterDataController::class, 'exportProducts'])->middleware('permission:master_item.buka')->name('products.export');
            Route::get('/products/template', [MasterDataController::class, 'downloadProductTemplate'])->middleware('permission:master_item.buka')->name('products.template');
            Route::get('/barcodes/print', [MasterDataController::class, 'barcodePrint'])->middleware('permission:master_item.buka')->name('barcodes.print');
            Route::get('/customers', [MasterDataController::class, 'customers'])->middleware('permission:master_customer.buka')->name('customers');
            Route::get('/suppliers', [MasterDataController::class, 'suppliers'])->middleware('permission:master_supplier.buka')->name('suppliers');
            Route::get('/categories', [MasterDataController::class, 'categories'])->middleware('permission:master_satuan_kategori.buka,master_item.buka')->name('categories');
            Route::get('/units', [MasterDataController::class, 'units'])->middleware('permission:master_satuan_kategori.buka,master_item.buka')->name('units');
            Route::get('/bank-accounts', [MasterDataController::class, 'bankAccounts'])->middleware('permission:master_warehouse.buka,master_item.buka')->name('bank-accounts');
            Route::get('/stores', [MasterDataController::class, 'stores'])->middleware('permission:master_warehouse.buka,master_item.buka')->name('stores');
            Route::get('/customer-tiers', [MasterDataController::class, 'customerTiers'])->middleware('permission:master_customer.buka')->name('customer-tiers');
            Route::get('/stock-card', [MasterDataController::class, 'stockCard'])->middleware('permission:kartu_stok.buka')->name('stock-card');
        });

        // Modifikasi Master Data (Hanya Owner & Manager)
        Route::middleware('role:owner,manager')->group(function () {
            Route::get('/products/create', [MasterDataController::class, 'createProduct'])->middleware('permission:master_item.baru')->name('products.create');
            Route::post('/products', [MasterDataController::class, 'storeProduct'])->middleware('permission:master_item.baru')->name('products.store');
            Route::get('/products/{id}/edit', [MasterDataController::class, 'editProduct'])->middleware('permission:master_item.ubah')->name('products.edit');
            Route::put('/products/{id}', [MasterDataController::class, 'updateProduct'])->middleware('permission:master_item.ubah')->name('products.update');
            Route::delete('/products/{id}', [MasterDataController::class, 'destroyProduct'])->middleware('permission:master_item.hapus')->name('products.destroy');
            Route::post('/products/import', [MasterDataController::class, 'importProducts'])->middleware('permission:master_item.baru')->name('products.import');
            Route::post('/customers', [MasterDataController::class, 'storeCustomer'])->middleware('permission:master_customer.baru')->name('customers.store');
            Route::put('/customers/{id}', [MasterDataController::class, 'updateCustomer'])->middleware('permission:master_customer.ubah')->name('customers.update');
            Route::patch('/customers/{id}/toggle-status', [MasterDataController::class, 'toggleCustomerStatus'])->middleware('permission:master_customer.ubah')->name('customers.toggle-status');

            Route::post('/suppliers', [MasterDataController::class, 'storeSupplier'])->middleware('permission:master_supplier.baru')->name('suppliers.store');
            Route::put('/suppliers/{id}', [MasterDataController::class, 'updateSupplier'])->middleware('permission:master_supplier.ubah')->name('suppliers.update');
            Route::patch('/suppliers/{id}/toggle-status', [MasterDataController::class, 'toggleSupplierStatus'])->middleware('permission:master_supplier.ubah')->name('suppliers.toggle-status');

            Route::post('/categories', [MasterDataController::class, 'storeCategory'])->middleware('permission:master_satuan_kategori.baru,master_item.baru')->name('categories.store');
            Route::put('/categories/{id}', [MasterDataController::class, 'updateCategory'])->middleware('permission:master_satuan_kategori.ubah,master_item.ubah')->name('categories.update');
            Route::patch('/categories/{id}/toggle-status', [MasterDataController::class, 'toggleCategoryStatus'])->middleware('permission:master_satuan_kategori.ubah,master_item.ubah')->name('categories.toggle-status');

            Route::post('/units', [MasterDataController::class, 'storeUnit'])->middleware('permission:master_satuan_kategori.baru,master_item.baru')->name('units.store');
            Route::put('/units/{id}', [MasterDataController::class, 'updateUnit'])->middleware('permission:master_satuan_kategori.ubah,master_item.ubah')->name('units.update');
            Route::patch('/units/{id}/toggle-status', [MasterDataController::class, 'toggleUnitStatus'])->middleware('permission:master_satuan_kategori.ubah,master_item.ubah')->name('units.toggle-status');

            Route::post('/bank-accounts', [MasterDataController::class, 'storeBankAccount'])->middleware('permission:master_warehouse.baru,master_item.baru')->name('bank-accounts.store');
            Route::put('/bank-accounts/{id}', [MasterDataController::class, 'updateBankAccount'])->middleware('permission:master_warehouse.ubah,master_item.ubah')->name('bank-accounts.update');
            Route::patch('/bank-accounts/{id}/toggle-status', [MasterDataController::class, 'toggleBankAccountStatus'])->middleware('permission:master_warehouse.ubah,master_item.ubah')->name('bank-accounts.toggle-status');

            Route::post('/stores', [MasterDataController::class, 'storeStore'])->middleware('permission:master_warehouse.baru,master_item.baru')->name('stores.store');
            Route::put('/stores/{id}', [MasterDataController::class, 'updateStore'])->middleware('permission:master_warehouse.ubah,master_item.ubah')->name('stores.update');
            Route::patch('/stores/{id}/toggle-status', [MasterDataController::class, 'toggleStoreStatus'])->middleware('permission:master_warehouse.ubah,master_item.ubah')->name('stores.toggle-status');

            Route::post('/customer-tiers', [MasterDataController::class, 'storeCustomerTier'])->middleware('permission:master_customer.baru')->name('customer-tiers.store');
            Route::put('/customer-tiers/{id}', [MasterDataController::class, 'updateCustomerTier'])->middleware('permission:master_customer.ubah')->name('customer-tiers.update');
            Route::patch('/customer-tiers/{id}/toggle-status', [MasterDataController::class, 'toggleCustomerTierStatus'])->middleware('permission:master_customer.ubah')->name('customer-tiers.toggle-status');
        });
    });

    // 4. LAYAR KASIR POS (POINT OF SALE)
    Route::prefix('pos')->name('pos.')->middleware('role:owner,manager,store_head,cashier,sales')->group(function () {
        Route::get('/', [PosController::class, 'index'])->middleware('permission:pos_terminal.buka')->name('index');
        Route::get('/search', [PosController::class, 'search'])->middleware('permission:pos_terminal.buka')->name('search');
        Route::get('/customers/search', [PosController::class, 'searchCustomers'])->middleware('permission:pos_terminal.buka')->name('customers.search');
        Route::post('/customers/quick-store', [PosController::class, 'quickStoreCustomer'])->middleware('permission:pos_terminal.baru')->name('customers.quick-store');
        Route::get('/lookup-item', [PosController::class, 'lookupItem'])->middleware('permission:pos_terminal.buka')->name('lookup-item');
        Route::post('/calculate-price', [PosController::class, 'calculatePrice'])->middleware('permission:pos_terminal.buka')->name('calculate-price');
        Route::post('/recalculate-cart', [PosController::class, 'recalculateCart'])->middleware('permission:pos_terminal.buka')->name('recalculate-cart');
        Route::post('/validate-voucher', [PosController::class, 'validateVoucher'])->middleware('permission:pos_terminal.buka')->name('validate-voucher');
        Route::post('/calculate-points-discount', [PosController::class, 'calculatePointsDiscount'])->middleware('permission:pos_terminal.buka')->name('calculate-points-discount');
        Route::post('/open-shift', [PosController::class, 'openShift'])->middleware('permission:pos_shift_management.baru,pos_shift_management.buka')->name('open-shift');
        Route::post('/cash-drop', [PosController::class, 'cashDrop'])->middleware('permission:pos_shift_management.baru,pos_shift_management.buka')->name('cash-drop');
        Route::post('/blind-close', [PosController::class, 'blindClose'])->middleware('permission:pos_shift_management.baru,pos_shift_management.buka')->name('blind-close');
        Route::post('/checkout', [PosController::class, 'checkout'])->middleware('permission:pos_terminal.baru')->name('checkout');
        Route::get('/receipt-sample', [PosController::class, 'sampleReceipt'])->middleware('permission:pos_terminal.buka')->name('receipt.sample');
        Route::get('/receipt/{id}', [PosController::class, 'printReceipt'])->middleware('permission:pos_terminal.buka')->name('print');
        Route::post('/reprint/{id}', [PosController::class, 'reprintReceipt'])->middleware('permission:pos_terminal.buka,pos_reprint_past_shift.buka')->name('reprint');
        Route::get('/recent-transactions', [PosController::class, 'getRecentTransactions'])->middleware('permission:pos_terminal.buka')->name('recent');
        Route::get('/lookup-invoice', [PosController::class, 'lookupInvoice'])->middleware('permission:pos_terminal.buka,pos_return.buka')->name('lookup-invoice');
        Route::post('/transactions/{id}/return', [PosController::class, 'returnTransaction'])->middleware('permission:pos_return.baru')->name('return');
        Route::post('/shifts/{id}/resolve-discrepancy', [PosController::class, 'resolveDiscrepancy'])->middleware('permission:pos_shift_discrepancy_resolve.buka,pos_shift_discrepancy_resolve.baru')->name('resolve-discrepancy');
        Route::post('/settings/rounding', [PosController::class, 'saveRoundingSettings'])->middleware('permission:settings_general.ubah,pos_terminal.buka')->name('settings.rounding');
    });

    // 5. MODUL PENGATURAN (SETTINGS)
    Route::prefix('settings')->name('settings.')->middleware('role:owner,manager')->group(function () {
        Route::get('/', function () {
            return redirect()->route('settings.general');
        });
        Route::get('/general', [SettingController::class, 'index'])->middleware('permission:settings_general.buka')->name('general');
        Route::post('/general', [SettingController::class, 'updateGeneral'])->middleware('permission:settings_general.ubah')->name('general.update');
        Route::post('/wallpaper/reset', [SettingController::class, 'resetWallpaper'])->middleware('permission:settings_general.ubah')->name('wallpaper.reset');
        Route::post('/logo/reset', [SettingController::class, 'resetLogo'])->middleware('permission:settings_general.ubah')->name('logo.reset');

        // 5-HOLDING. KELOLA PERUSAHAAN (MULTI-COMPANY HOLDING)
        Route::prefix('companies')->name('companies.')->middleware('role:owner')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::post('/', [CompanyController::class, 'store'])->name('store');
            Route::put('/{id}', [CompanyController::class, 'update'])->name('update');
            Route::patch('/{id}/toggle-status', [CompanyController::class, 'toggleStatus'])->name('toggle-status');
        });

        // 5a. CABANG TOKO & GUDANG MULTI-STORE
        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/', [SettingController::class, 'stores'])->middleware('permission:settings_general.buka,master_warehouse.buka')->name('index');
            Route::post('/', [SettingController::class, 'storeStore'])->middleware('permission:settings_general.ubah,master_warehouse.baru')->name('store');
            Route::put('/{id}', [SettingController::class, 'updateStore'])->middleware('permission:settings_general.ubah,master_warehouse.ubah')->name('update');
            Route::patch('/{id}/toggle-status', [SettingController::class, 'toggleStoreStatus'])->middleware('permission:settings_general.ubah,master_warehouse.ubah')->name('toggle-status');
            Route::patch('/{id}/set-main', [SettingController::class, 'setMainStore'])->middleware('permission:settings_general.ubah,master_warehouse.ubah')->name('set-main');
        });

        // 5c. KONFIGURASI PRINTER STRUK THERMAL & FORMAT LAYOUT (58mm / 80mm)
        Route::get('/printer', [SettingController::class, 'printer'])->middleware('permission:settings_general.buka')->name('printer');
        Route::post('/printer', [SettingController::class, 'updatePrinter'])->middleware('permission:settings_general.ubah')->name('printer.update');
        Route::get('/printer/test-print', [SettingController::class, 'testPrint'])->middleware('permission:settings_general.buka')->name('printer.test');

        // 5e. PENGATURAN OPERASIONAL KASIR, MODAL AWAL & LIMIT KAS DROP
        Route::get('/cashier', [SettingController::class, 'cashier'])->middleware('permission:settings_general.buka')->name('cashier');
        Route::post('/cashier', [SettingController::class, 'updateCashier'])->middleware('permission:settings_general.ubah')->name('cashier.update');

        // 5b. MANAJEMEN PENGGUNA, STAF KASIR & KELOMPOK HAK AKSES CEKLIST (RBAC)
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->middleware('permission:settings_users_rbac.buka')->name('index');
            Route::post('/', [UserManagementController::class, 'storeUser'])->middleware('permission:settings_users_rbac.baru')->name('store');
            Route::put('/{id}', [UserManagementController::class, 'updateUser'])->middleware('permission:settings_users_rbac.ubah')->name('update');
            Route::patch('/{id}/toggle-status', [UserManagementController::class, 'toggleStatus'])->middleware('permission:settings_users_rbac.ubah')->name('toggle-status');
            Route::post('/{id}/reset-password', [UserManagementController::class, 'resetPassword'])->middleware('permission:settings_users_rbac.ubah')->name('reset-password');
            Route::post('/{id}/set-pin', [UserManagementController::class, 'setManagerPin'])->middleware('permission:settings_users_rbac.ubah')->name('set-pin');
        });

        Route::prefix('user-groups')->name('user-groups.')->group(function () {
            Route::post('/', [UserManagementController::class, 'storeGroup'])->middleware('permission:settings_users_rbac.baru')->name('store');
            Route::put('/{id}', [UserManagementController::class, 'updateGroup'])->middleware('permission:settings_users_rbac.ubah')->name('update');
            Route::post('/{id}/preset', [UserManagementController::class, 'applyPreset'])->middleware('permission:settings_users_rbac.ubah')->name('preset');
            Route::delete('/{id}', [UserManagementController::class, 'destroyGroup'])->middleware('permission:settings_users_rbac.hapus')->name('destroy');
        });

        // 5d. PENOMORAN OTOMATIS & FORMAT NOMOR TRANSAKSI (AUTO-NUMBERING)
        Route::prefix('numbering')->name('numbering.')->group(function () {
            Route::get('/', [AutoNumberSettingController::class, 'index'])->middleware('permission:settings_numbering.buka')->name('index');
            Route::put('/{id}', [AutoNumberSettingController::class, 'update'])->middleware('permission:settings_numbering.ubah')->name('update');
            Route::patch('/{id}/reset-sequence', [AutoNumberSettingController::class, 'resetSequence'])->middleware('permission:settings_numbering.ubah')->name('reset-sequence');
            Route::post('/restore-defaults', [AutoNumberSettingController::class, 'restoreDefaults'])->middleware('permission:settings_numbering.ubah')->name('restore-defaults');
        });

        // 5f. BACKUP, RESTORE & PEMELIHARAAN DATABASE (MAINTENANCE)
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MaintenanceController::class, 'index'])->middleware('permission:settings_backup_db.buka')->name('index');
            Route::post('/backup', [MaintenanceController::class, 'createBackup'])->middleware('permission:settings_backup_db.baru')->name('backup');
            Route::get('/download/{filename}', [MaintenanceController::class, 'downloadBackup'])->middleware('permission:settings_backup_db.buka')->name('download');
            Route::delete('/delete/{filename}', [MaintenanceController::class, 'deleteBackup'])->middleware('permission:settings_backup_db.hapus')->name('delete');
            Route::post('/clear-cache', [MaintenanceController::class, 'clearCache'])->middleware('permission:settings_backup_db.buka')->name('clear-cache');
            Route::post('/optimize-db', [MaintenanceController::class, 'optimizeDatabase'])->middleware('permission:settings_backup_db.buka')->name('optimize-db');
            Route::post('/switch-mode', [MaintenanceController::class, 'switchMode'])->middleware('permission:settings_backup_db.buka')->name('switch-mode');
            // Demo Reset — owner only (extra protection via confirm_text)
            Route::post('/reset-demo', [MaintenanceController::class, 'resetDemo'])->middleware('role:owner')->name('reset-demo');
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
