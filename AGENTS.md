# AGENTS.md - Master Blueprint & System Instructions for SIGMA ERP & POS

> **NOTICE FOR AI AGENTS:** This document contains the definitive business rules, architectural constraints, and technical standards for the SIGMA ERP & POS project. All code generation, migrations, queries, and business logic MUST strictly adhere to the guidelines specified herein.

---

## 1. Core Architecture & Technology Stack
* **Framework:** Laravel 11 / 12 (Modular Monolith Architecture).
* **Database:** PostgreSQL 16+ with dedicated schema named `sigma`.
* **Database Conventions:**
  * Primary Keys: `UUID` using PostgreSQL `gen_random_uuid()`.
  * Naming Convention: `snake_case` strictly applied to all tables, columns, foreign keys, and API endpoints.
  * Multi-tenancy Ready: All tables must include `company_id` and `store_id` (where applicable) to support future SaaS multi-company expansion.
* **Security & Transactions:** All database write operations must use strict database transactions (`DB::transaction`) to guarantee ACID compliance and zero data corruption.

---

## 2. Inventory & Stock Safety Rules (CRITICAL)
* **Zero Negative Inventory Policy:** The database must enforce strict stock control. Stock can never drop below zero.
* **Available Stock Calculation Formula:**
  `Available Stock = quantity (Physical) - reserved_quantity (PWA Orders) - quarantined_quantity (Damaged/Return)`
* **Valuation Method:** HPP (Cost of Goods Sold) strictly uses **Moving Average (Weighted Average Cost)** calculated automatically upon Goods Receipt.
* **Blind Stock Opname:** Warehouse staff using PWA are NOT allowed to see system expected quantities (`system_qty`). They only input actual physical counts (`actual_qty`). Discrepancy approvals are handled by Managers in the Back-Office.

---

## 3. POS, Promo, Voucher & Pricing Engine
* **Zero Manual Price Override:** Cashiers are strictly FORBIDDEN from modifying prices manually at the POS screen. No override permission exists for cashiers.
* **3-Dimensional Automated Pricing:** Prices are calculated automatically by the backend based on: Unit/Packaging (`unit_code`), Customer Tier (`customer_tier_id`), and Tiered Quantity (`min_qty`).
* **Margin Automation:** Auto-Margin Markup when supplier purchase prices increase (with preview/approval), and FIFO margin protection when prices drop.
* **Promo Engine:** Support time-based promotions (Buy X Get Y, % Discounts, Tiered Promos) validated automatically at checkout.
* **Voucher & Gift Card System:** Support for unique alphanumeric barcode vouchers (value-based or discount-based). Vouchers must be single-use (tracked via status `ISSUED`, `REDEEMED`, `EXPIRED`) with precise validity dates. Value vouchers act as a distinct payment method, securely posting to Unearned Revenue/Sales.
* **Lightning-Fast Search:** POS UI must support Full Keyboard Navigation (F1-F5 shortcuts) and Partial Keyword/Barcode matching optimized via PostgreSQL Trigram (`pg_trgm`) indexes.

---

## 4. Operational & Cashier Workflows
### A. Cashier, Shift & Blind Closing
* Shift opening (Input Starting Cash) -> Sales transactions -> Cash Drop.
* **Blind Closing Rule:** Cashiers input physical counted cash at the end of the shift WITHOUT seeing the system's expected total. Discrepancies (Over/Short) are logged automatically and require Manager resolution.
* **Payment Methods:** Support Cash, QRIS, Bank Transfer, **Voucher**, and **KREDIT/TEMPO (Accounts Receivable)** for verified wholesale customers with Credit Limits.

### B. POS Receipt Reprint & Audit Security
* **Reprint Capability:** Fast lookup of recent shift transactions to reprint receipts.
* **Reprint Watermark:** Any reprinted receipt must automatically include a distinct flag: `** CETAK ULANG / REPRINT **` with the exact timestamp.
* **Access Control:** Cashiers can only reprint transactions from their *currently active shift*. Past shift reprints require Manager PIN approval.

### C. Purchasing, Returns & Internal Transfer
* Purchasing follows: Restock Recommendation -> Draft PO -> Goods Receipt -> Three-Way Matching -> Accounts Payable (AP).
* Internal transfers require Dispatch (In-Transit stock) and Store Manager confirmation upon arrival.

### D. PWA Customer & Manual Transfer
* Customer checkout -> Atomic stock reservation (TTL 30 minutes) -> Upload bank transfer proof -> Store clerk verification -> Order fulfillment.

---

## 5. Accounting & Financial Integrity
* **Automated Double-Entry Bookkeeping:** Every transaction (Sales, Purchasing, Returns, Expenses, Kasbon/Tempo, Voucher Redemption) must trigger balanced Debit/Credit journal entries to the General Ledger.
* **Fixed Asset Management:** Track acquisition cost and run automated monthly Straight-Line Depreciation journals.
* **Taxation (Flexible Toggle):** Configurable Non-PKP mode (No VAT) vs PKP mode (VAT included/excluded) with effective-dated tax rules.

---

## 6. Access Control & Security
* **Role-Based Access Control (RBAC):** Strict separation of roles (Cashier, Warehouse, Manager, Purchasing, Finance, Owner).
* **Sensitive Data Protection:** Cost prices (HPP), gross profit, and financial reports are restricted exclusively to Owner/Finance roles.
* **Time-Window Access:** User accounts can be restricted by active hours, working days, and contract expiration dates (`valid_until`).
