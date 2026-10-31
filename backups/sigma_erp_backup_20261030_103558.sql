--
-- PostgreSQL database dump
--

\restrict rnXZrx2CJBzETSf8oPxqytX9foUGul35IvFaMsl6tEKRh2YXV1lxmJiq9Wuuta8

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

-- Started on 2026-10-30 10:35:58

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 7 (class 2615 OID 24694)
-- Name: sigma; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA sigma;


ALTER SCHEMA sigma OWNER TO postgres;

--
-- TOC entry 2 (class 3079 OID 24695)
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA sigma;


--
-- TOC entry 6449 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 303 (class 1259 OID 131109)
-- Name: accounting_settings; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.accounting_settings (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    setting_key character varying(80) NOT NULL,
    account_id uuid NOT NULL,
    category character varying(50) DEFAULT 'GENERAL'::character varying NOT NULL,
    description character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.accounting_settings OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 26687)
-- Name: asset_depreciation_records; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.asset_depreciation_records (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    fixed_asset_id uuid NOT NULL,
    journal_entry_id uuid,
    period_date date NOT NULL,
    amount numeric(18,2) NOT NULL,
    book_value_after numeric(18,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.asset_depreciation_records OWNER TO postgres;

--
-- TOC entry 307 (class 1259 OID 131302)
-- Name: audit_logs; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.audit_logs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    user_id uuid,
    user_name character varying(150),
    user_role character varying(50),
    module character varying(50) NOT NULL,
    action character varying(80) NOT NULL,
    description text NOT NULL,
    ip_address character varying(45),
    user_agent text,
    old_values json,
    new_values json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.audit_logs OWNER TO postgres;

--
-- TOC entry 257 (class 1259 OID 26859)
-- Name: bank_accounts; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.bank_accounts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    bank_code character varying(30) NOT NULL,
    bank_name character varying(100) NOT NULL,
    account_number character varying(50) NOT NULL,
    account_holder character varying(150) NOT NULL,
    branch character varying(100),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.bank_accounts OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 25983)
-- Name: cache; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE sigma.cache OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 25994)
-- Name: cache_locks; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE sigma.cache_locks OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 26447)
-- Name: cash_drops; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.cash_drops (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    cashier_shift_id uuid NOT NULL,
    amount numeric(15,2) NOT NULL,
    reason character varying(255),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE sigma.cash_drops OWNER TO postgres;

--
-- TOC entry 300 (class 1259 OID 130916)
-- Name: cash_transactions; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.cash_transactions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    transaction_number character varying(50) NOT NULL,
    type character varying(20) NOT NULL,
    transaction_date date NOT NULL,
    source_account_id uuid,
    destination_account_id uuid,
    category_account_id uuid,
    amount numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    admin_fee numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    admin_fee_account_id uuid,
    contact_person character varying(150),
    notes text,
    journal_entry_id uuid,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.cash_transactions OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 26413)
-- Name: cashier_shifts; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.cashier_shifts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    cashier_id uuid NOT NULL,
    shift_number character varying(50) NOT NULL,
    starting_cash numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'OPEN'::character varying NOT NULL,
    opened_at timestamp(0) without time zone NOT NULL,
    closed_at timestamp(0) without time zone,
    actual_cash_counted numeric(15,2),
    expected_cash numeric(15,2),
    cash_discrepancy numeric(15,2),
    discrepancy_resolved boolean DEFAULT false NOT NULL,
    resolved_by uuid,
    resolution_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.cashier_shifts OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 26563)
-- Name: chart_of_accounts; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.chart_of_accounts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    type character varying(30) NOT NULL,
    normal_balance character varying(10) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.chart_of_accounts OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 26055)
-- Name: companies; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.companies (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    tax_id character varying(50),
    email character varying(100),
    phone character varying(50),
    address text,
    currency_code character varying(10) DEFAULT 'IDR'::character varying NOT NULL,
    is_tax_registered boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    wallpaper_path character varying(255),
    logo_path character varying(255),
    receipt_footer text,
    rounding_enabled boolean DEFAULT false NOT NULL,
    rounding_step integer DEFAULT 100 NOT NULL,
    rounding_mode character varying(30) DEFAULT 'ROUND_NEAREST'::character varying NOT NULL,
    rounding_applies_to character varying(30) DEFAULT 'CASH_ONLY'::character varying NOT NULL,
    receipt_paper_size character varying(10) DEFAULT '58'::character varying NOT NULL,
    receipt_font_family character varying(30) DEFAULT 'monospace'::character varying NOT NULL,
    receipt_font_size character varying(20) DEFAULT 'normal'::character varying NOT NULL,
    receipt_item_layout character varying(30) DEFAULT 'compact_double'::character varying NOT NULL,
    receipt_show_logo boolean DEFAULT true NOT NULL,
    receipt_header_line1 character varying(255),
    receipt_header_line2 character varying(255),
    receipt_footer_line1 text,
    receipt_footer_line2 text,
    receipt_show_customer boolean DEFAULT true NOT NULL,
    receipt_show_cashier boolean DEFAULT true NOT NULL,
    receipt_show_barcode boolean DEFAULT true NOT NULL,
    receipt_auto_print boolean DEFAULT true NOT NULL,
    receipt_auto_cut boolean DEFAULT true NOT NULL,
    receipt_open_drawer boolean DEFAULT true NOT NULL,
    receipt_print_copies smallint DEFAULT '1'::smallint NOT NULL,
    cashier_default_starting_cash numeric(15,2) DEFAULT '200000'::numeric NOT NULL,
    cashier_max_starting_cash numeric(15,2) DEFAULT '2000000'::numeric NOT NULL,
    cashier_require_starting_cash boolean DEFAULT true NOT NULL,
    cashier_max_change_limit numeric(15,2) DEFAULT '500000'::numeric NOT NULL,
    cashier_cash_drop_threshold numeric(15,2) DEFAULT '2000000'::numeric NOT NULL,
    cashier_cash_drop_warning_enabled boolean DEFAULT true NOT NULL,
    cashier_max_discrepancy_tolerance numeric(15,2) DEFAULT '2000'::numeric NOT NULL,
    cashier_allow_pending_transaction boolean DEFAULT true NOT NULL,
    cashier_max_pending_transactions smallint DEFAULT '5'::smallint NOT NULL,
    cashier_quick_cash_nominal character varying(100) DEFAULT '10000,20000,50000,100000'::character varying NOT NULL,
    whatsapp character varying(50),
    website character varying(150),
    tax_name character varying(150),
    tax_address text,
    default_tax_rate numeric(5,2) DEFAULT '11'::numeric NOT NULL,
    tax_mode character varying(20) DEFAULT 'INCLUSIVE'::character varying NOT NULL
);


ALTER TABLE sigma.companies OWNER TO postgres;

--
-- TOC entry 281 (class 1259 OID 101640)
-- Name: consignment_receipt_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.consignment_receipt_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    consignment_receipt_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    quantity numeric(15,4) NOT NULL,
    unit_cost numeric(15,4) NOT NULL,
    sold_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    returned_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    settled_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_cost numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.consignment_receipt_items OWNER TO postgres;

--
-- TOC entry 280 (class 1259 OID 101600)
-- Name: consignment_receipts; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.consignment_receipts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    supplier_id uuid NOT NULL,
    receipt_number character varying(50) NOT NULL,
    receipt_date date NOT NULL,
    total_items integer DEFAULT 0 NOT NULL,
    total_estimated_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'RECEIVED'::character varying NOT NULL,
    notes text,
    received_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.consignment_receipts OWNER TO postgres;

--
-- TOC entry 283 (class 1259 OID 101714)
-- Name: consignment_settlement_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.consignment_settlement_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    consignment_settlement_id uuid NOT NULL,
    consignment_receipt_item_id uuid CONSTRAINT consignment_settlement_item_consignment_receipt_item_i_not_null NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    settled_quantity numeric(15,4) NOT NULL,
    unit_cost numeric(15,4) NOT NULL,
    total_amount numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.consignment_settlement_items OWNER TO postgres;

--
-- TOC entry 282 (class 1259 OID 101675)
-- Name: consignment_settlements; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.consignment_settlements (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    supplier_id uuid NOT NULL,
    settlement_number character varying(50) NOT NULL,
    settlement_date date NOT NULL,
    period_start date,
    period_end date,
    total_sold_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_settlement_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    payment_status character varying(20) DEFAULT 'UNPAID'::character varying NOT NULL,
    payment_method character varying(20),
    notes text,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.consignment_settlements OWNER TO postgres;

--
-- TOC entry 301 (class 1259 OID 130982)
-- Name: customer_deposits; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.customer_deposits (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    customer_id uuid NOT NULL,
    deposit_number character varying(50) NOT NULL,
    type character varying(255) DEFAULT 'TOP_UP'::character varying NOT NULL,
    amount numeric(15,2) NOT NULL,
    balance_before numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    balance_after numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    payment_method character varying(30) DEFAULT 'CASH'::character varying NOT NULL,
    bank_account_id uuid,
    account_id uuid,
    journal_entry_id uuid,
    reference_type character varying(50),
    reference_id uuid,
    reference_number character varying(100),
    transaction_date date NOT NULL,
    notes text,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT customer_deposits_type_check CHECK (((type)::text = ANY ((ARRAY['TOP_UP'::character varying, 'WITHDRAWAL'::character varying, 'USAGE'::character varying, 'REFUND'::character varying])::text[])))
);


ALTER TABLE sigma.customer_deposits OWNER TO postgres;

--
-- TOC entry 273 (class 1259 OID 101274)
-- Name: customer_payment_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.customer_payment_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    customer_payment_id uuid NOT NULL,
    pos_transaction_id uuid NOT NULL,
    amount numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.customer_payment_items OWNER TO postgres;

--
-- TOC entry 272 (class 1259 OID 101238)
-- Name: customer_payments; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.customer_payments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    customer_id uuid NOT NULL,
    payment_number character varying(50) NOT NULL,
    payment_date date NOT NULL,
    payment_method character varying(20) NOT NULL,
    bank_name character varying(50),
    bank_account_number character varying(50),
    reference_number character varying(100),
    total_amount numeric(15,2) NOT NULL,
    notes text,
    received_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.customer_payments OWNER TO postgres;

--
-- TOC entry 279 (class 1259 OID 101559)
-- Name: customer_point_mutations; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.customer_point_mutations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    customer_id uuid NOT NULL,
    pos_transaction_id uuid,
    type character varying(20) NOT NULL,
    points integer NOT NULL,
    balance_before integer NOT NULL,
    balance_after integer NOT NULL,
    description character varying(255) NOT NULL,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    expired_at date,
    is_expired boolean DEFAULT false NOT NULL
);


ALTER TABLE sigma.customer_point_mutations OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 26098)
-- Name: customer_tiers; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.customer_tiers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    code character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    default_margin_percentage numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    point_multiplier numeric(5,2) DEFAULT '1'::numeric NOT NULL
);


ALTER TABLE sigma.customer_tiers OWNER TO postgres;

--
-- TOC entry 253 (class 1259 OID 26736)
-- Name: customers; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.customers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    customer_tier_id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    phone character varying(50),
    email character varying(100),
    address text,
    credit_limit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    current_credit_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    credit_term_days integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    loyalty_points integer DEFAULT 0 NOT NULL,
    lifetime_points integer DEFAULT 0 NOT NULL,
    deposit_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL
);


ALTER TABLE sigma.customers OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 26036)
-- Name: failed_jobs; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE sigma.failed_jobs OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 26035)
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: sigma; Owner: postgres
--

CREATE SEQUENCE sigma.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE sigma.failed_jobs_id_seq OWNER TO postgres;

--
-- TOC entry 6450 (class 0 OID 0)
-- Dependencies: 231
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: sigma; Owner: postgres
--

ALTER SEQUENCE sigma.failed_jobs_id_seq OWNED BY sigma.failed_jobs.id;


--
-- TOC entry 304 (class 1259 OID 131135)
-- Name: fiscal_year_closings; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.fiscal_year_closings (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    fiscal_year integer NOT NULL,
    closing_date date NOT NULL,
    total_revenue numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_expense numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    net_profit numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    retained_earnings_account_id uuid NOT NULL,
    closing_journal_entry_id uuid,
    closed_by uuid NOT NULL,
    status character varying(30) DEFAULT 'CLOSED'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.fiscal_year_closings OWNER TO postgres;

--
-- TOC entry 250 (class 1259 OID 26651)
-- Name: fixed_assets; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.fixed_assets (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    asset_code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    category character varying(50) NOT NULL,
    acquisition_date date NOT NULL,
    acquisition_cost numeric(18,2) NOT NULL,
    salvage_value numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    useful_life_months integer NOT NULL,
    monthly_depreciation_amount numeric(18,2) NOT NULL,
    accumulated_depreciation numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    book_value numeric(18,2) NOT NULL,
    last_depreciation_date date,
    status character varying(20) DEFAULT 'ACTIVE'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.fixed_assets OWNER TO postgres;

--
-- TOC entry 293 (class 1259 OID 130611)
-- Name: goods_receipt_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.goods_receipt_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    goods_receipt_id uuid NOT NULL,
    purchase_order_item_id uuid,
    product_id uuid NOT NULL,
    product_unit_id uuid,
    unit_code character varying(20) DEFAULT 'PCS'::character varying NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    quantity_received numeric(12,2) NOT NULL,
    base_quantity numeric(12,4) NOT NULL,
    estimated_unit_price numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    allocated_landed_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    effective_cost_price numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    batch_number character varying(50),
    expiry_date date,
    physical_condition character varying(100) DEFAULT 'BAIK'::character varying NOT NULL,
    notes character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.goods_receipt_items OWNER TO postgres;

--
-- TOC entry 292 (class 1259 OID 130555)
-- Name: goods_receipts; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.goods_receipts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    purchase_order_id uuid,
    supplier_id uuid NOT NULL,
    receipt_number character varying(50) NOT NULL,
    delivery_order_number character varying(100) NOT NULL,
    receipt_date date NOT NULL,
    status character varying(30) DEFAULT 'RECEIVED'::character varying NOT NULL,
    total_items_received integer DEFAULT 0 NOT NULL,
    estimated_total_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    shipping_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    handling_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_landed_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    driver_name character varying(100),
    vehicle_number character varying(30),
    received_by uuid,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.goods_receipts OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 26021)
-- Name: job_batches; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE sigma.job_batches OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 26006)
-- Name: jobs; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE sigma.jobs OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 26005)
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: sigma; Owner: postgres
--

CREATE SEQUENCE sigma.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE sigma.jobs_id_seq OWNER TO postgres;

--
-- TOC entry 6451 (class 0 OID 0)
-- Dependencies: 228
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: sigma; Owner: postgres
--

ALTER SEQUENCE sigma.jobs_id_seq OWNED BY sigma.jobs.id;


--
-- TOC entry 248 (class 1259 OID 26587)
-- Name: journal_entries; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.journal_entries (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    entry_number character varying(50) NOT NULL,
    entry_date date NOT NULL,
    reference_type character varying(50) NOT NULL,
    reference_id uuid,
    reference_number character varying(100),
    description text NOT NULL,
    total_debit numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    total_credit numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    is_balanced boolean DEFAULT true NOT NULL,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_journal_entries_balanced CHECK ((total_debit = total_credit))
);


ALTER TABLE sigma.journal_entries OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 26626)
-- Name: journal_entry_lines; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.journal_entry_lines (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    journal_entry_id uuid NOT NULL,
    account_id uuid NOT NULL,
    debit numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    credit numeric(18,2) DEFAULT '0'::numeric NOT NULL,
    memo character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_journal_lines_non_negative CHECK (((debit >= (0)::numeric) AND (credit >= (0)::numeric)))
);


ALTER TABLE sigma.journal_entry_lines OWNER TO postgres;

--
-- TOC entry 278 (class 1259 OID 101535)
-- Name: loyalty_programs; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.loyalty_programs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    name character varying(100) DEFAULT 'Program Poin Pelanggan'::character varying NOT NULL,
    spend_amount_per_point numeric(15,2) DEFAULT '10000'::numeric NOT NULL,
    point_redemption_value numeric(15,2) DEFAULT '100'::numeric NOT NULL,
    min_points_to_redeem integer DEFAULT 10 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    points_expiry_days integer DEFAULT 365 NOT NULL,
    allow_merchandise_redemption boolean DEFAULT true NOT NULL,
    only_registered_customers boolean DEFAULT true NOT NULL
);


ALTER TABLE sigma.loyalty_programs OWNER TO postgres;

--
-- TOC entry 285 (class 1259 OID 101918)
-- Name: loyalty_reward_redemptions; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.loyalty_reward_redemptions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    customer_id uuid NOT NULL,
    loyalty_reward_id uuid NOT NULL,
    redemption_number character varying(50) NOT NULL,
    points_used integer NOT NULL,
    quantity numeric(12,2) DEFAULT '1'::numeric NOT NULL,
    redemption_date date NOT NULL,
    notes text,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.loyalty_reward_redemptions OWNER TO postgres;

--
-- TOC entry 284 (class 1259 OID 101888)
-- Name: loyalty_rewards; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.loyalty_rewards (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    product_id uuid,
    points_required integer NOT NULL,
    stock_quantity numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.loyalty_rewards OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 25939)
-- Name: migrations; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE sigma.migrations OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 25938)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: sigma; Owner: postgres
--

CREATE SEQUENCE sigma.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE sigma.migrations_id_seq OWNER TO postgres;

--
-- TOC entry 6452 (class 0 OID 0)
-- Dependencies: 221
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: sigma; Owner: postgres
--

ALTER SEQUENCE sigma.migrations_id_seq OWNED BY sigma.migrations.id;


--
-- TOC entry 224 (class 1259 OID 25962)
-- Name: password_reset_tokens; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE sigma.password_reset_tokens OWNER TO postgres;

--
-- TOC entry 261 (class 1259 OID 100799)
-- Name: pos_return_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.pos_return_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    pos_return_id uuid NOT NULL,
    pos_transaction_item_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    quantity numeric(12,2) NOT NULL,
    base_quantity numeric(15,4) NOT NULL,
    unit_price numeric(15,2) NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.pos_return_items OWNER TO postgres;

--
-- TOC entry 260 (class 1259 OID 100749)
-- Name: pos_returns; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.pos_returns (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    pos_transaction_id uuid NOT NULL,
    cashier_shift_id uuid,
    processed_by uuid NOT NULL,
    approved_by uuid,
    return_number character varying(50) NOT NULL,
    return_date date NOT NULL,
    refund_method character varying(20) DEFAULT 'CASH'::character varying NOT NULL,
    subtotal_returned numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax_returned numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_refund_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    reason character varying(255),
    status character varying(20) DEFAULT 'COMPLETED'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.pos_returns OWNER TO postgres;

--
-- TOC entry 246 (class 1259 OID 26528)
-- Name: pos_transaction_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.pos_transaction_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    pos_transaction_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    quantity numeric(12,2) NOT NULL,
    base_quantity numeric(15,4) NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    unit_price numeric(15,2) NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_price numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    batch_id uuid,
    batch_number character varying(50),
    expired_date date
);


ALTER TABLE sigma.pos_transaction_items OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 26463)
-- Name: pos_transactions; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.pos_transactions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    cashier_shift_id uuid NOT NULL,
    customer_tier_id uuid NOT NULL,
    cashier_id uuid NOT NULL,
    invoice_number character varying(50) NOT NULL,
    total_item_count integer DEFAULT 0 NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    voucher_id uuid,
    voucher_discount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    grand_total numeric(15,2) NOT NULL,
    payment_method character varying(20) NOT NULL,
    paid_amount numeric(15,2) NOT NULL,
    change_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'COMPLETED'::character varying NOT NULL,
    reprint_count integer DEFAULT 0 NOT NULL,
    last_reprinted_at timestamp(0) without time zone,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    customer_id uuid,
    sales_person_id uuid,
    tax_mode character varying(20) DEFAULT 'NON'::character varying NOT NULL,
    bank_name character varying(50),
    bank_account_number character varying(50),
    payment_reference character varying(100),
    credit_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    split_payment_method character varying(20),
    due_date date,
    payment_status character varying(20) DEFAULT 'PAID'::character varying NOT NULL,
    remaining_receivable numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    points_earned integer DEFAULT 0 NOT NULL,
    points_redeemed integer DEFAULT 0 NOT NULL,
    points_discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    rounding_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    split_cash_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    split_non_cash_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    split_non_cash_method character varying(30)
);


ALTER TABLE sigma.pos_transactions OWNER TO postgres;

--
-- TOC entry 275 (class 1259 OID 101330)
-- Name: product_assemblies; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_assemblies (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    assembly_number character varying(50) NOT NULL,
    assembly_date date NOT NULL,
    type character varying(20) DEFAULT 'ASSEMBLY'::character varying NOT NULL,
    parent_product_id uuid NOT NULL,
    parent_product_unit_id uuid NOT NULL,
    quantity numeric(12,2) NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    assembled_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.product_assemblies OWNER TO postgres;

--
-- TOC entry 276 (class 1259 OID 101377)
-- Name: product_assembly_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_assembly_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    product_assembly_id uuid NOT NULL,
    component_product_id uuid NOT NULL,
    component_product_unit_id uuid NOT NULL,
    quantity numeric(12,2) NOT NULL,
    base_quantity numeric(15,4) NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.product_assembly_items OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 26213)
-- Name: product_barcodes; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_barcodes (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    barcode character varying(100) NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.product_barcodes OWNER TO postgres;

--
-- TOC entry 277 (class 1259 OID 101409)
-- Name: product_batches; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_batches (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    product_id uuid NOT NULL,
    batch_number character varying(50) NOT NULL,
    expired_date date NOT NULL,
    manufacture_date date,
    initial_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    current_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'ACTIVE'::character varying NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.product_batches OWNER TO postgres;

--
-- TOC entry 274 (class 1259 OID 101296)
-- Name: product_boms; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_boms (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    parent_product_id uuid NOT NULL,
    component_product_id uuid NOT NULL,
    component_product_unit_id uuid NOT NULL,
    quantity numeric(12,4) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.product_boms OWNER TO postgres;

--
-- TOC entry 255 (class 1259 OID 26810)
-- Name: product_categories; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_categories (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.product_categories OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 26251)
-- Name: product_prices; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_prices (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    customer_tier_id uuid NOT NULL,
    min_quantity numeric(12,2) DEFAULT '1'::numeric NOT NULL,
    price numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    margin_percentage numeric(8,2),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    discount_1 numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    discount_2 numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    discount_3 numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    discount_4 numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    CONSTRAINT product_prices_min_quantity_check CHECK ((min_quantity > (0)::numeric)),
    CONSTRAINT product_prices_price_check CHECK ((price >= (0)::numeric))
);


ALTER TABLE sigma.product_prices OWNER TO postgres;

--
-- TOC entry 240 (class 1259 OID 26299)
-- Name: product_stocks; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_stocks (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    product_id uuid NOT NULL,
    physical_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    reserved_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    quarantined_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_available_quantity_valid CHECK ((physical_quantity >= (reserved_quantity + quarantined_quantity))),
    CONSTRAINT chk_physical_quantity_non_negative CHECK ((physical_quantity >= (0)::numeric)),
    CONSTRAINT chk_quarantined_quantity_non_negative CHECK ((quarantined_quantity >= (0)::numeric)),
    CONSTRAINT chk_reserved_quantity_non_negative CHECK ((reserved_quantity >= (0)::numeric))
);


ALTER TABLE sigma.product_stocks OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 26177)
-- Name: product_units; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.product_units (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    product_id uuid NOT NULL,
    unit_code character varying(30) NOT NULL,
    unit_name character varying(50) NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    is_base_unit boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    weight_gram numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    length_cm numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    width_cm numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    height_cm numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    point_reward numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    commission_rate numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    CONSTRAINT product_units_conversion_factor_check CHECK ((conversion_factor > (0)::numeric))
);


ALTER TABLE sigma.product_units OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 26129)
-- Name: products; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.products (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    sku character varying(100) NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255),
    description text,
    category character varying(100),
    brand character varying(100),
    cost_price numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    last_purchase_price numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_stock_value numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    total_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    min_stock_alert numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    max_stock numeric(12,2),
    has_serial_number boolean DEFAULT false NOT NULL,
    has_batch_expiry boolean DEFAULT false NOT NULL,
    is_point_of_sale boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    category_id uuid,
    type character varying(30) DEFAULT 'BARANG'::character varying NOT NULL,
    supplier_id uuid,
    sub_category character varying(100),
    rack_location character varying(100),
    sale_status character varying(30) DEFAULT 'ACTIVE'::character varying NOT NULL,
    notes text,
    tax_type character varying(30) DEFAULT 'NON'::character varying NOT NULL,
    tax_reference_code character varying(100),
    image_path character varying(255),
    additional_images json,
    is_consignment boolean DEFAULT false NOT NULL,
    consignment_supplier_id uuid,
    consignment_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    CONSTRAINT products_cost_price_check CHECK ((cost_price >= (0)::numeric)),
    CONSTRAINT products_total_quantity_check CHECK ((total_quantity >= (0)::numeric))
);


ALTER TABLE sigma.products OWNER TO postgres;

--
-- TOC entry 286 (class 1259 OID 101960)
-- Name: promotions; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.promotions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    code character varying(50),
    name character varying(150) NOT NULL,
    type character varying(30) NOT NULL,
    description text,
    buy_product_id uuid,
    buy_quantity numeric(12,2) DEFAULT '1'::numeric NOT NULL,
    reward_product_id uuid,
    reward_quantity numeric(12,2) DEFAULT '1'::numeric NOT NULL,
    special_price numeric(15,2),
    discount_percent numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    discount_nominal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    min_spend numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    max_usage_per_transaction integer DEFAULT 1 NOT NULL,
    start_date date,
    end_date date,
    start_time time(0) without time zone,
    end_time time(0) without time zone,
    days_of_week json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.promotions OWNER TO postgres;

--
-- TOC entry 259 (class 1259 OID 26968)
-- Name: purchase_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.purchase_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    purchase_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    unit_code character varying(20) NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    quantity numeric(12,2) NOT NULL,
    base_quantity numeric(15,4) NOT NULL,
    unit_price numeric(15,2) NOT NULL,
    base_unit_price numeric(15,4) NOT NULL,
    discount_percent numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    old_cost_price numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    new_cost_price numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    is_price_increased boolean DEFAULT false NOT NULL,
    is_selling_price_updated boolean DEFAULT false NOT NULL,
    old_selling_price numeric(15,2),
    new_selling_price numeric(15,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    allocated_shipping_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL
);


ALTER TABLE sigma.purchase_items OWNER TO postgres;

--
-- TOC entry 265 (class 1259 OID 100969)
-- Name: purchase_order_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.purchase_order_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    purchase_order_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    unit_code character varying(20) NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    quantity_ordered numeric(12,2) NOT NULL,
    quantity_received numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    unit_price numeric(15,2) NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.purchase_order_items OWNER TO postgres;

--
-- TOC entry 264 (class 1259 OID 100923)
-- Name: purchase_orders; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.purchase_orders (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    supplier_id uuid NOT NULL,
    po_number character varying(50) NOT NULL,
    order_date date NOT NULL,
    expected_delivery_date date,
    status character varying(25) DEFAULT 'DRAFT'::character varying NOT NULL,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    grand_total numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid NOT NULL,
    approved_by uuid,
    approved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.purchase_orders OWNER TO postgres;

--
-- TOC entry 291 (class 1259 OID 130521)
-- Name: purchase_return_debt_allocations; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.purchase_return_debt_allocations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    purchase_return_id uuid NOT NULL,
    purchase_id uuid NOT NULL,
    allocated_amount numeric(15,2) NOT NULL,
    notes character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.purchase_return_debt_allocations OWNER TO postgres;

--
-- TOC entry 263 (class 1259 OID 100886)
-- Name: purchase_return_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.purchase_return_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    purchase_return_id uuid NOT NULL,
    purchase_item_id uuid,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    quantity numeric(12,2) NOT NULL,
    base_quantity numeric(15,4) NOT NULL,
    unit_price numeric(15,2) NOT NULL,
    subtotal numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    condition character varying(30) DEFAULT 'GOOD'::character varying NOT NULL,
    notes character varying(255)
);


ALTER TABLE sigma.purchase_return_items OWNER TO postgres;

--
-- TOC entry 262 (class 1259 OID 100837)
-- Name: purchase_returns; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.purchase_returns (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    purchase_id uuid,
    supplier_id uuid NOT NULL,
    return_number character varying(50) NOT NULL,
    return_date date NOT NULL,
    refund_type character varying(20) DEFAULT 'DEBT_REDUCTION'::character varying NOT NULL,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    grand_total numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    reason character varying(255),
    status character varying(20) DEFAULT 'COMPLETED'::character varying NOT NULL,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    return_type character varying(30) DEFAULT 'INVOICE_BASED'::character varying NOT NULL,
    approved_by uuid,
    approved_at timestamp(0) without time zone,
    notes text
);


ALTER TABLE sigma.purchase_returns OWNER TO postgres;

--
-- TOC entry 258 (class 1259 OID 26912)
-- Name: purchases; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.purchases (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    supplier_id uuid NOT NULL,
    invoice_number character varying(100) NOT NULL,
    reference_number character varying(50) NOT NULL,
    purchase_date date NOT NULL,
    due_date date,
    payment_type character varying(20) DEFAULT 'CASH'::character varying NOT NULL,
    bank_account_id uuid,
    status character varying(20) DEFAULT 'RECEIVED'::character varying NOT NULL,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    grand_total numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    paid_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    purchase_order_id uuid,
    goods_receipt_id uuid,
    three_way_match_status character varying(30) DEFAULT 'DIRECT'::character varying NOT NULL,
    shipping_cost numeric(15,2) DEFAULT '0'::numeric NOT NULL
);


ALTER TABLE sigma.purchases OWNER TO postgres;

--
-- TOC entry 288 (class 1259 OID 102068)
-- Name: sales_order_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.sales_order_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    sales_order_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    unit_code character varying(20) NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    quantity numeric(12,2) DEFAULT '1'::numeric NOT NULL,
    unit_price numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.sales_order_items OWNER TO postgres;

--
-- TOC entry 287 (class 1259 OID 102015)
-- Name: sales_orders; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.sales_orders (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    customer_id uuid,
    order_number character varying(50) NOT NULL,
    order_date date NOT NULL,
    expected_delivery_date date,
    status character varying(25) DEFAULT 'PENDING'::character varying NOT NULL,
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    grand_total numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid,
    approved_by uuid,
    approved_at timestamp(0) without time zone,
    pos_transaction_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.sales_orders OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 25971)
-- Name: sessions; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.sessions (
    id character varying(255) NOT NULL,
    user_id uuid,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE sigma.sessions OWNER TO postgres;

--
-- TOC entry 295 (class 1259 OID 130710)
-- Name: stock_adjustment_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_adjustment_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    stock_adjustment_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    unit_code character varying(20) NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    quantity numeric(12,2) NOT NULL,
    base_quantity numeric(15,4) NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_cost numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    action character varying(30) DEFAULT 'MOVE_TO_QUARANTINE'::character varying NOT NULL,
    reason character varying(150),
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_adjustment_items OWNER TO postgres;

--
-- TOC entry 294 (class 1259 OID 130671)
-- Name: stock_adjustments; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_adjustments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    adjustment_number character varying(50) NOT NULL,
    adjustment_date date NOT NULL,
    adjustment_type character varying(30) DEFAULT 'DAMAGE_WASTE'::character varying NOT NULL,
    status character varying(20) DEFAULT 'COMPLETED'::character varying NOT NULL,
    total_items integer DEFAULT 0 NOT NULL,
    total_cost_value numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid,
    approved_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_adjustments OWNER TO postgres;

--
-- TOC entry 297 (class 1259 OID 130788)
-- Name: stock_movement_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_movement_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    stock_movement_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    unit_code character varying(20) NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    quantity numeric(12,2) NOT NULL,
    base_quantity numeric(15,4) NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_cost numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_movement_items OWNER TO postgres;

--
-- TOC entry 296 (class 1259 OID 130748)
-- Name: stock_movements; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_movements (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    movement_number character varying(50) NOT NULL,
    movement_type character varying(10) NOT NULL,
    movement_date date NOT NULL,
    category_reason character varying(50) NOT NULL,
    recipient_or_source character varying(150),
    status character varying(20) DEFAULT 'COMPLETED'::character varying NOT NULL,
    total_items integer DEFAULT 0 NOT NULL,
    total_cost_value numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid,
    approved_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_movements OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 26337)
-- Name: stock_mutations; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_mutations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    product_id uuid NOT NULL,
    reference_number character varying(100) NOT NULL,
    mutation_type character varying(50) NOT NULL,
    quantity numeric(15,4) NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_cost numeric(18,4) DEFAULT '0'::numeric NOT NULL,
    qty_before numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    qty_after numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_mutations OWNER TO postgres;

--
-- TOC entry 271 (class 1259 OID 101202)
-- Name: stock_opname_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_opname_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    stock_opname_id uuid NOT NULL,
    product_id uuid NOT NULL,
    system_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    actual_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    discrepancy_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    discrepancy_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_opname_items OWNER TO postgres;

--
-- TOC entry 270 (class 1259 OID 101159)
-- Name: stock_opnames; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_opnames (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    opname_number character varying(50) NOT NULL,
    opname_date date NOT NULL,
    status character varying(25) DEFAULT 'DRAFT'::character varying NOT NULL,
    is_blind boolean DEFAULT true NOT NULL,
    total_system_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_actual_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_discrepancy_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_discrepancy_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid NOT NULL,
    approved_by uuid,
    approved_at timestamp(0) without time zone,
    approval_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_opnames OWNER TO postgres;

--
-- TOC entry 290 (class 1259 OID 130466)
-- Name: stock_requirement_allocations; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_requirement_allocations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    stock_requirement_id uuid NOT NULL,
    allocation_type character varying(30) NOT NULL,
    reference_id uuid NOT NULL,
    reference_item_id uuid,
    reference_number character varying(100),
    idempotency_key character varying(100),
    initial_allocated_base_qty numeric(15,4) CONSTRAINT stock_requirement_allocatio_initial_allocated_base_qty_not_null NOT NULL,
    received_base_qty numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    released_base_qty numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    outstanding_base_qty numeric(15,4) NOT NULL,
    status character varying(30) DEFAULT 'ACTIVE'::character varying NOT NULL,
    allocated_by uuid,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_requirement_allocations OWNER TO postgres;

--
-- TOC entry 289 (class 1259 OID 130424)
-- Name: stock_requirements; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_requirements (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    product_id uuid NOT NULL,
    target_stock numeric(15,4) NOT NULL,
    available_stock_snapshot numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    gross_deficit numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_allocated numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    status character varying(30) DEFAULT 'OPEN'::character varying NOT NULL,
    notes text,
    created_by uuid,
    updated_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_requirements OWNER TO postgres;

--
-- TOC entry 269 (class 1259 OID 101120)
-- Name: stock_transfer_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_transfer_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    stock_transfer_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    unit_code character varying(20) NOT NULL,
    conversion_factor numeric(12,4) DEFAULT '1'::numeric NOT NULL,
    quantity_sent numeric(12,2) NOT NULL,
    base_quantity_sent numeric(15,4) NOT NULL,
    quantity_received numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    base_quantity_received numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    unit_cost numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_transfer_items OWNER TO postgres;

--
-- TOC entry 268 (class 1259 OID 101073)
-- Name: stock_transfers; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stock_transfers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    source_store_id uuid NOT NULL,
    destination_store_id uuid NOT NULL,
    transfer_number character varying(50) NOT NULL,
    transfer_date date NOT NULL,
    status character varying(25) DEFAULT 'DRAFT'::character varying NOT NULL,
    driver_name character varying(100),
    vehicle_number character varying(50),
    dispatched_at timestamp(0) without time zone,
    dispatched_by uuid,
    received_at timestamp(0) without time zone,
    received_by uuid,
    total_items integer DEFAULT 0 NOT NULL,
    total_sent_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_received_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_by uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.stock_transfers OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 26074)
-- Name: stores; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.stores (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    type character varying(50) DEFAULT 'STORE'::character varying NOT NULL,
    phone character varying(50),
    address text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    city character varying(100),
    postal_code character varying(20),
    pic_name character varying(100),
    is_main_store boolean DEFAULT false NOT NULL
);


ALTER TABLE sigma.stores OWNER TO postgres;

--
-- TOC entry 302 (class 1259 OID 131045)
-- Name: supplier_deposits; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.supplier_deposits (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    supplier_id uuid NOT NULL,
    deposit_number character varying(50) NOT NULL,
    type character varying(255) DEFAULT 'TOP_UP'::character varying NOT NULL,
    amount numeric(15,2) NOT NULL,
    balance_before numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    balance_after numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    payment_method character varying(30) DEFAULT 'BANK_TRANSFER'::character varying NOT NULL,
    bank_account_id uuid,
    account_id uuid,
    journal_entry_id uuid,
    reference_type character varying(50),
    reference_id uuid,
    reference_number character varying(100),
    transaction_date date NOT NULL,
    notes text,
    created_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT supplier_deposits_type_check CHECK (((type)::text = ANY ((ARRAY['TOP_UP'::character varying, 'WITHDRAWAL'::character varying, 'USAGE'::character varying, 'REFUND'::character varying])::text[])))
);


ALTER TABLE sigma.supplier_deposits OWNER TO postgres;

--
-- TOC entry 267 (class 1259 OID 101052)
-- Name: supplier_payment_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.supplier_payment_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    supplier_payment_id uuid NOT NULL,
    purchase_id uuid NOT NULL,
    amount_paid numeric(15,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.supplier_payment_items OWNER TO postgres;

--
-- TOC entry 266 (class 1259 OID 101008)
-- Name: supplier_payments; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.supplier_payments (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid NOT NULL,
    supplier_id uuid NOT NULL,
    payment_number character varying(50) NOT NULL,
    payment_date date NOT NULL,
    payment_method character varying(20) DEFAULT 'TRANSFER'::character varying NOT NULL,
    bank_account_id uuid,
    total_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    reference_number character varying(100),
    notes text,
    created_by uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.supplier_payments OWNER TO postgres;

--
-- TOC entry 254 (class 1259 OID 26786)
-- Name: suppliers; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.suppliers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(150) NOT NULL,
    contact_person character varying(100),
    phone character varying(50),
    email character varying(100),
    address text,
    term_of_payment_days integer DEFAULT 0 NOT NULL,
    bank_name character varying(50),
    bank_account_no character varying(50),
    bank_account_holder character varying(100),
    notes text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_pkp boolean DEFAULT false NOT NULL,
    npwp character varying(50),
    tax_name character varying(150),
    deposit_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL
);


ALTER TABLE sigma.suppliers OWNER TO postgres;

--
-- TOC entry 252 (class 1259 OID 26711)
-- Name: tax_configurations; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.tax_configurations (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    is_tax_enabled boolean DEFAULT false NOT NULL,
    tax_name character varying(50) DEFAULT 'PPN'::character varying NOT NULL,
    tax_rate_percent numeric(5,2) DEFAULT '11'::numeric NOT NULL,
    is_tax_inclusive boolean DEFAULT false NOT NULL,
    effective_from date NOT NULL,
    effective_to date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.tax_configurations OWNER TO postgres;

--
-- TOC entry 306 (class 1259 OID 131243)
-- Name: transaction_number_patterns; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.transaction_number_patterns (
    id uuid NOT NULL,
    company_id uuid NOT NULL,
    document_type character varying(30) NOT NULL,
    module character varying(30) DEFAULT 'penjualan'::character varying NOT NULL,
    name character varying(100) NOT NULL,
    prefix character varying(30) NOT NULL,
    pattern character varying(100) DEFAULT '{PREFIX}-{Y}{m}-{SEQ}'::character varying NOT NULL,
    digits smallint DEFAULT '4'::smallint NOT NULL,
    reset_cycle character varying(20) DEFAULT 'MONTHLY'::character varying NOT NULL,
    current_sequence integer DEFAULT 0 NOT NULL,
    last_period character varying(20),
    is_locked boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.transaction_number_patterns OWNER TO postgres;

--
-- TOC entry 299 (class 1259 OID 130884)
-- Name: transfer_request_items; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.transfer_request_items (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    transfer_request_id uuid NOT NULL,
    product_id uuid NOT NULL,
    product_unit_id uuid NOT NULL,
    requested_quantity numeric(15,4) DEFAULT '1'::numeric NOT NULL,
    approved_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    fulfilled_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    notes character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.transfer_request_items OWNER TO postgres;

--
-- TOC entry 298 (class 1259 OID 130824)
-- Name: transfer_requests; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.transfer_requests (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    request_number character varying(50) NOT NULL,
    requesting_store_id uuid NOT NULL,
    destination_store_id uuid NOT NULL,
    request_date date NOT NULL,
    status character varying(30) DEFAULT 'REQUESTED'::character varying NOT NULL,
    total_items integer DEFAULT 0 NOT NULL,
    total_requested_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_approved_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    total_fulfilled_quantity numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    notes text,
    rejection_reason text,
    created_by uuid,
    approved_by uuid,
    approved_at timestamp(0) without time zone,
    stock_transfer_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.transfer_requests OWNER TO postgres;

--
-- TOC entry 256 (class 1259 OID 26837)
-- Name: units; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.units (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    code character varying(20) NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.units OWNER TO postgres;

--
-- TOC entry 305 (class 1259 OID 131194)
-- Name: user_groups; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.user_groups (
    id uuid NOT NULL,
    company_id uuid NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(100) NOT NULL,
    description character varying(255),
    permissions json,
    is_system boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE sigma.user_groups OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 25948)
-- Name: users; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.users (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(30) DEFAULT 'cashier'::character varying NOT NULL,
    store_id uuid,
    manager_pin character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    valid_from date,
    valid_until date,
    user_group_id uuid
);


ALTER TABLE sigma.users OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 26380)
-- Name: vouchers; Type: TABLE; Schema: sigma; Owner: postgres
--

CREATE TABLE sigma.vouchers (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id uuid NOT NULL,
    store_id uuid,
    code character varying(50) NOT NULL,
    type character varying(20) DEFAULT 'VALUE'::character varying NOT NULL,
    amount numeric(15,2) NOT NULL,
    min_spend numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(20) DEFAULT 'ISSUED'::character varying NOT NULL,
    valid_from timestamp(0) without time zone NOT NULL,
    valid_until timestamp(0) without time zone NOT NULL,
    redeemed_at timestamp(0) without time zone,
    redeemed_in_transaction_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    barcode character varying(50),
    title character varying(150),
    is_printed_on_receipt boolean DEFAULT false NOT NULL,
    trigger_min_spend numeric(15,2) DEFAULT '0'::numeric NOT NULL
);


ALTER TABLE sigma.vouchers OWNER TO postgres;

--
-- TOC entry 5143 (class 2604 OID 26039)
-- Name: failed_jobs id; Type: DEFAULT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.failed_jobs ALTER COLUMN id SET DEFAULT nextval('sigma.failed_jobs_id_seq'::regclass);


--
-- TOC entry 5142 (class 2604 OID 26009)
-- Name: jobs id; Type: DEFAULT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.jobs ALTER COLUMN id SET DEFAULT nextval('sigma.jobs_id_seq'::regclass);


--
-- TOC entry 5138 (class 2604 OID 25942)
-- Name: migrations id; Type: DEFAULT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.migrations ALTER COLUMN id SET DEFAULT nextval('sigma.migrations_id_seq'::regclass);


--
-- TOC entry 6439 (class 0 OID 131109)
-- Dependencies: 303
-- Data for Name: accounting_settings; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.accounting_settings (id, company_id, setting_key, account_id, category, description, created_at, updated_at) FROM stdin;
44632949-bc33-45d0-bbba-fe35b63ac322	01a157f4-189f-7352-8b9a-ef0be10c7b1a	sales_cash_account_id	01a157f4-1953-71f2-ae2f-d611958a8240	SALES	Akun Kas Utama Kasir Toko	2026-10-28 09:38:36	2026-10-28 09:38:36
ed0bb271-83df-4e85-baf1-3bb09ccb3f2d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	sales_receivable_account_id	01a157f4-195b-71c6-89aa-6ed6ca31f4df	SALES	Akun Piutang Usaha Penjualan Tempo	2026-10-28 09:38:36	2026-10-28 09:38:36
0bb88fb1-1b4c-4732-9b26-21e7b4ca9b75	01a157f4-189f-7352-8b9a-ef0be10c7b1a	sales_revenue_account_id	01a157f4-196b-7395-9ee7-565f2c2d6e85	SALES	Akun Pendapatan Penjualan Toko	2026-10-28 09:38:36	2026-10-28 09:38:36
87cdc466-aaef-491c-abf4-f460caaa4d02	01a157f4-189f-7352-8b9a-ef0be10c7b1a	sales_return_account_id	01a157f4-196d-727d-bfa8-cd1a5c9460e8	SALES	Akun Retur Penjualan Pelanggan	2026-10-28 09:38:36	2026-10-28 09:38:36
fbb0bdf6-7859-4cd9-8bf8-80a15db744e0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	purchase_payable_account_id	01a157f4-1964-71c1-8699-ae85c3a3fa56	PURCHASING	Akun Hutang Usaha Supplier	2026-10-28 09:38:36	2026-10-28 09:38:36
7d7bbd88-b752-4de8-bbfc-f0547e0cf6be	01a157f4-189f-7352-8b9a-ef0be10c7b1a	grni_account_id	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	PURCHASING	Akun Hutang Barang Belum Difakturkan (GRNI)	2026-10-28 09:38:36	2026-10-28 09:38:36
b7db67ed-f66e-4106-9b7d-92f38f2220b8	01a157f4-189f-7352-8b9a-ef0be10c7b1a	inventory_account_id	01a157f4-195d-72fc-93fc-7c042b48382a	INVENTORY	Akun Aset Persediaan Barang Dagang	2026-10-28 09:38:36	2026-10-28 09:38:36
b6137d07-c782-4518-8434-a8394bb00237	01a157f4-189f-7352-8b9a-ef0be10c7b1a	cogs_account_id	01a157f4-196e-7350-8529-780f013ba229	INVENTORY	Akun Beban Pokok Penjualan (HPP)	2026-10-28 09:38:36	2026-10-28 09:38:36
39118c64-3261-4b96-8a9d-8e7b2caf0e1c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	equity_opening_account_id	01a157f4-196a-73bc-968a-f2c5ce22b88f	EQUITY	Akun Modal Pemilik / Ekuitas Saldo Awal	2026-10-28 09:38:36	2026-10-28 09:38:36
\.


--
-- TOC entry 6387 (class 0 OID 26687)
-- Dependencies: 251
-- Data for Name: asset_depreciation_records; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.asset_depreciation_records (id, fixed_asset_id, journal_entry_id, period_date, amount, book_value_after, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6443 (class 0 OID 131302)
-- Dependencies: 307
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.audit_logs (id, company_id, user_id, user_name, user_role, module, action, description, ip_address, user_agent, old_values, new_values, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6393 (class 0 OID 26859)
-- Dependencies: 257
-- Data for Name: bank_accounts; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.bank_accounts (id, company_id, bank_code, bank_name, account_number, account_holder, branch, is_active, created_at, updated_at) FROM stdin;
01a15a4d-78ff-73f1-9045-d2e9f93711cd	01a157f4-189f-7352-8b9a-ef0be10c7b1a	BCA	Bank Central Asia	1234567890	PT SIGMA RETAIL INDONESIA	KCU Sudirman Jakarta	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-7901-71c0-b2a2-d949e7fdc3de	01a157f4-189f-7352-8b9a-ef0be10c7b1a	MANDIRI	Bank Mandiri	0987654321	PT SIGMA RETAIL INDONESIA	KCP Gatot Subroto	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-7902-7287-8e90-dab4049d202e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	BRI	Bank Rakyat Indonesia	334455667788	PT SIGMA RETAIL INDONESIA	KC Jakarta Thamrin	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-7904-71d1-94f4-6be82dead727	01a157f4-189f-7352-8b9a-ef0be10c7b1a	QRIS	QRIS Dinamis / Statis Toko	ID102030405060	SIGMA MART UTAMA	Merchant Aggregator	t	2026-10-20 17:13:09	2026-10-20 17:13:09
\.


--
-- TOC entry 6362 (class 0 OID 25983)
-- Dependencies: 226
-- Data for Name: cache; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.cache (key, value, expiration) FROM stdin;
sigma-erp-cache-idempotency:stock_alloc:PO-ALLOC-01a18ab8-f56c-7149-91ec-341e539525a4	s:36:"01a18ab8-f5e6-732f-a5eb-5150c62fa52d";	1793328859
sigma-erp-cache-idempotency:stock_alloc:PO-ALLOC-01a18ab9-1eb8-7060-8b22-929f2837b464	s:36:"01a18ab9-1ee6-734b-981f-140789c84008";	1793328870
sigma-erp-cache-idempotency:stock_alloc:PO-ALLOC-01a18ab9-ae52-7064-92ca-771ee6a5a0a7	s:36:"01a18ab9-ae67-7177-8b5d-d253def3d9cd";	1793328907
sigma-erp-cache-idempotency:stock_alloc:PO-ALLOC-01a18aba-6d99-7059-b957-2a0acd07214a	s:36:"01a18aba-6da9-7065-849b-3a4859a07c3b";	1793328956
sigma-erp-cache-idempotency:stock_alloc:PO-ALLOC-01a18aba-a8cb-7182-866d-c7739719128b	s:36:"01a18aba-a8ec-71a6-ad71-34e28eb303e2";	1793328971
sigma-erp-cache-idempotency:stock_alloc:PO-ALLOC-01a18abb-61ca-7374-bb50-ce066cbf4ec5	s:36:"01a18abb-61db-70ed-869b-48570adb2d28";	1793329018
sigma-erp-cache-sidebar_overdue_debt_count	i:0;	1793330702
\.


--
-- TOC entry 6363 (class 0 OID 25994)
-- Dependencies: 227
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- TOC entry 6380 (class 0 OID 26447)
-- Dependencies: 244
-- Data for Name: cash_drops; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.cash_drops (id, cashier_shift_id, amount, reason, created_at) FROM stdin;
\.


--
-- TOC entry 6436 (class 0 OID 130916)
-- Dependencies: 300
-- Data for Name: cash_transactions; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.cash_transactions (id, company_id, store_id, transaction_number, type, transaction_date, source_account_id, destination_account_id, category_account_id, amount, admin_fee, admin_fee_account_id, contact_person, notes, journal_entry_id, created_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6379 (class 0 OID 26413)
-- Dependencies: 243
-- Data for Name: cashier_shifts; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.cashier_shifts (id, company_id, store_id, cashier_id, shift_number, starting_cash, status, opened_at, closed_at, actual_cash_counted, expected_cash, cash_discrepancy, discrepancy_resolved, resolved_by, resolution_notes, created_at, updated_at) FROM stdin;
01a157fc-2622-72c4-a62f-972389680675	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-177e-732e-aa30-52b7729936fe	SHF-20261020-OKEGV	200000.00	CLOSED	2026-10-20 06:25:05	2026-10-20 09:11:40	0.00	2205000.00	-2205000.00	f	\N	\N	2026-10-20 06:25:05	2026-10-20 09:11:40
01a15894-bec6-70d5-a6cb-43e8f76c6db7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-177e-732e-aa30-52b7729936fe	SHF-20261020-GBQUN	200000.00	CLOSED	2026-10-20 09:11:45	2026-10-23 04:15:38	0.00	217500.00	-217500.00	f	\N	\N	2026-10-20 09:11:45	2026-10-23 04:15:38
01a166f8-ecf9-723d-9131-a82b9cc662a5	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-177e-732e-aa30-52b7729936fe	SHF-20261023-ROJHS	200000.00	OPEN	2026-10-23 04:15:52	\N	\N	\N	\N	f	\N	\N	2026-10-23 04:15:52	2026-10-23 04:15:52
01a16c06-ec35-7139-bdb6-bd8b7ae0ce16	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a16777-4238-731f-af87-87381b4f044a	SHF-20261024-NBWBW	200000.00	CLOSED	2026-10-24 03:49:15	2026-10-28 14:21:18	100000.00	442500.00	-342500.00	f	\N	\N	2026-10-24 03:49:15	2026-10-28 14:21:18
01a182e3-7233-71af-b1f0-07481dda729f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a16777-4238-731f-af87-87381b4f044a	SHF-20261028-HN9SN	200000.00	OPEN	2026-10-28 14:21:46	\N	\N	\N	\N	f	\N	\N	2026-10-28 14:21:46	2026-10-28 14:21:46
01a182fa-eb76-724e-9220-0a8a9e2fbca1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15821-8537-7151-9b04-c67ec65e63f9	SHF-20261028-EQUQZ	200000.00	OPEN	2026-10-28 14:47:24	\N	\N	\N	\N	f	\N	\N	2026-10-28 14:47:24	2026-10-28 14:47:24
\.


--
-- TOC entry 6383 (class 0 OID 26563)
-- Dependencies: 247
-- Data for Name: chart_of_accounts; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.chart_of_accounts (id, company_id, code, name, type, normal_balance, description, is_active, created_at, updated_at) FROM stdin;
01a157f4-1953-71f2-ae2f-d611958a8240	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1001-001	Kas Toko / Kasir	ASSET	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1956-7303-aaf9-1028a8db8293	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1002-001	Bank Transfer	ASSET	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1959-73f5-bc18-d8e12ff20427	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1003-001	QRIS / Dompet Digital	ASSET	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-195b-71c6-89aa-6ed6ca31f4df	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1004-001	Piutang Usaha (Tempo / Kredit)	ASSET	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-195d-72fc-93fc-7c042b48382a	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1005-001	Persediaan Barang Dagang	ASSET	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-195f-73f0-87fa-9ce2ed7bf8a1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1006-001	PPN Masukan (VAT In)	ASSET	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1961-708f-b69a-db7668d19868	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1501-001	Aset Tetap - Peralatan & Mesin	ASSET	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1962-71f1-a0db-1cdd6e9b3d66	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1502-001	Akumulasi Penyusutan Peralatan	ASSET	CREDIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1964-71c1-8699-ae85c3a3fa56	01a157f4-189f-7352-8b9a-ef0be10c7b1a	2001-001	Hutang Usaha (Accounts Payable)	LIABILITY	CREDIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1965-724e-92a7-de08a313b3cf	01a157f4-189f-7352-8b9a-ef0be10c7b1a	2002-001	Pendapatan Diterima Dimuka (Voucher / Gift Card)	LIABILITY	CREDIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1967-700d-8e14-719d81050362	01a157f4-189f-7352-8b9a-ef0be10c7b1a	2003-001	PPN Keluaran (VAT Out)	LIABILITY	CREDIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-196a-73bc-968a-f2c5ce22b88f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	3001-001	Modal Pemilik	EQUITY	CREDIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-196b-7395-9ee7-565f2c2d6e85	01a157f4-189f-7352-8b9a-ef0be10c7b1a	4001-001	Pendapatan Penjualan Toko	REVENUE	CREDIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-196d-727d-bfa8-cd1a5c9460e8	01a157f4-189f-7352-8b9a-ef0be10c7b1a	4002-001	Pendapatan Selisih Kas (Cash Overage)	REVENUE	CREDIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-196e-7350-8529-780f013ba229	01a157f4-189f-7352-8b9a-ef0be10c7b1a	5001-001	Beban Pokok Penjualan (HPP / COGS)	EXPENSE	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1970-72f7-aaba-05594424aa7b	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6001-001	Beban Penyusutan Aset Tetap	EXPENSE	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1972-71c7-bed3-5cae0982fed0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6002-001	Beban Selisih Kas (Cash Shortage)	EXPENSE	DEBIT	\N	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a15833-3af9-73fb-92c2-1cae3abb210c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1002-002	Bank Mandiri (Rek: 098-765-4321)	ASSET	DEBIT	\N	t	2026-10-20 07:25:14	2026-10-20 07:25:14
01a15833-3b04-71bc-97d1-3d293a6f9a9e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1002-003	Bank BRI (Rek: 567-890-1234)	ASSET	DEBIT	\N	t	2026-10-20 07:25:14	2026-10-20 07:25:14
01a15833-3b07-715a-99a7-12b8ef96c5c9	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1002-004	Bank BNI (Rek: 321-654-0987)	ASSET	DEBIT	\N	t	2026-10-20 07:25:14	2026-10-20 07:25:14
01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	01a157f4-189f-7352-8b9a-ef0be10c7b1a	2004-001	Hutang Belum Difakturkan (GRNI / Penerimaan Barang)	LIABILITY	CREDIT	\N	t	2026-10-27 09:03:41	2026-10-27 09:03:41
01a17cbe-8d16-7250-949d-03120a546679	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1501-002	Aset Tetap - Kendaraan Operasional	ASSET	DEBIT	\N	t	2026-10-27 09:43:45	2026-10-27 09:43:45
01a17cbe-8d39-70f1-b826-ed6a4834ef99	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1502-002	Akumulasi Penyusutan Kendaraan	ASSET	CREDIT	\N	t	2026-10-27 09:43:45	2026-10-27 09:43:45
01a17cbe-8d3b-7240-8823-cb85da24a317	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1501-003	Aset Tetap - Bangunan & Renovasi	ASSET	DEBIT	\N	t	2026-10-27 09:43:45	2026-10-27 09:43:45
01a17cbe-8d3d-738c-8f70-2cef16c5be1f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1502-003	Akumulasi Penyusutan Bangunan	ASSET	CREDIT	\N	t	2026-10-27 09:43:45	2026-10-27 09:43:45
01a17cbe-8d3e-73a5-94cf-951866695f73	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1501-004	Aset Tetap - Mebel & Inventaris Toko	ASSET	DEBIT	\N	t	2026-10-27 09:43:45	2026-10-27 09:43:45
01a17cbe-8d40-7033-b971-4bfe5dc61f48	01a157f4-189f-7352-8b9a-ef0be10c7b1a	1502-004	Akumulasi Penyusutan Mebel & Inventaris	ASSET	CREDIT	\N	t	2026-10-27 09:43:45	2026-10-27 09:43:45
01a181ce-c3bd-727f-8772-64f030ebdb6c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6101-001	Beban Gaji & Upah Karyawan	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:33	2026-10-28 09:19:33
01a181ce-c4c1-7024-8500-3323abb77465	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6102-001	Beban Listrik (PLN)	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4c3-733c-9cd7-966314f21ba4	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6103-001	Beban Internet, Air & Komunikasi	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4c5-7171-bf82-ee17a8012bf1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6104-001	Beban Sewa Toko / Tempat	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4c7-739f-ac85-75fcba080c9e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6105-001	Beban BBM & Transportasi Operasional	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4da-73ca-9e72-fd36bfa4444f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6106-001	Beban Perlengkapan Toko & ATK	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4dc-7147-be6f-0e838e17cf29	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6107-001	Beban Pemeliharaan & Perbaikan	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4de-73cb-be98-4aadb413248c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6108-001	Beban Konsumsi & Kesejahteraan Karyawan	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4e2-70b8-b52c-aad2f9263468	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6109-001	Beban Iklan, Promosi & Pemasaran	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4e4-72a4-bbb4-e4886fcd66ec	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6110-001	Beban Iuran Kebersihan & Keamanan	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4e6-73c8-9ca8-563a2d4c0016	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6111-001	Beban Administrasi Bank & MDR	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
01a181ce-c4e8-73ed-bb64-ea0cfc4783c1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	6199-001	Beban Operasional Lain-lain	EXPENSE	DEBIT	\N	t	2026-10-28 09:19:34	2026-10-28 09:19:34
\.


--
-- TOC entry 6369 (class 0 OID 26055)
-- Dependencies: 233
-- Data for Name: companies; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.companies (id, code, name, tax_id, email, phone, address, currency_code, is_tax_registered, is_active, created_at, updated_at, wallpaper_path, logo_path, receipt_footer, rounding_enabled, rounding_step, rounding_mode, rounding_applies_to, receipt_paper_size, receipt_font_family, receipt_font_size, receipt_item_layout, receipt_show_logo, receipt_header_line1, receipt_header_line2, receipt_footer_line1, receipt_footer_line2, receipt_show_customer, receipt_show_cashier, receipt_show_barcode, receipt_auto_print, receipt_auto_cut, receipt_open_drawer, receipt_print_copies, cashier_default_starting_cash, cashier_max_starting_cash, cashier_require_starting_cash, cashier_max_change_limit, cashier_cash_drop_threshold, cashier_cash_drop_warning_enabled, cashier_max_discrepancy_tolerance, cashier_allow_pending_transaction, cashier_max_pending_transactions, cashier_quick_cash_nominal, whatsapp, website, tax_name, tax_address, default_tax_rate, tax_mode) FROM stdin;
01a157f4-189f-7352-8b9a-ef0be10c7b1a	SIGMA-HO	PT SIGMA PROJECTS INDONESIA	01.234.567.8-901.000	hq@sigmamart.co.id	021-5551234	Jl. Jendral Sudirman No. 88, Jakarta Pusat	IDR	t	t	2026-10-20 06:16:17	2026-10-30 02:36:53	uploads/wallpapers/wallpaper_1792740172_6adb0b4c19c60.png	\N	Terima Kasih Atas Kunjungan Anda\r\nBarang yang dibeli tidak dapat ditukar/dikembalikan	t	100	ROUND_NEAREST	CASH_ONLY	58	monospace	normal	compact_double	t	\N	\N	Terima Kasih Atas Kunjungan Anda\r\nBarang yang dibeli tidak dapat ditukar/dikembalikan	\N	t	t	f	t	t	t	1	200000.00	2000000.00	t	500000.00	2000000.00	t	2000.00	t	5	10000,20000,50000,100000	\N	\N	\N	\N	11.00	NON_TAX
\.


--
-- TOC entry 6417 (class 0 OID 101640)
-- Dependencies: 281
-- Data for Name: consignment_receipt_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.consignment_receipt_items (id, consignment_receipt_id, product_id, product_unit_id, quantity, unit_cost, sold_quantity, returned_quantity, settled_quantity, total_cost, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6416 (class 0 OID 101600)
-- Dependencies: 280
-- Data for Name: consignment_receipts; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.consignment_receipts (id, company_id, store_id, supplier_id, receipt_number, receipt_date, total_items, total_estimated_amount, status, notes, received_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6419 (class 0 OID 101714)
-- Dependencies: 283
-- Data for Name: consignment_settlement_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.consignment_settlement_items (id, consignment_settlement_id, consignment_receipt_item_id, product_id, product_unit_id, settled_quantity, unit_cost, total_amount, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6418 (class 0 OID 101675)
-- Dependencies: 282
-- Data for Name: consignment_settlements; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.consignment_settlements (id, company_id, store_id, supplier_id, settlement_number, settlement_date, period_start, period_end, total_sold_quantity, total_settlement_amount, payment_status, payment_method, notes, created_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6437 (class 0 OID 130982)
-- Dependencies: 301
-- Data for Name: customer_deposits; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.customer_deposits (id, company_id, store_id, customer_id, deposit_number, type, amount, balance_before, balance_after, payment_method, bank_account_id, account_id, journal_entry_id, reference_type, reference_id, reference_number, transaction_date, notes, created_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6409 (class 0 OID 101274)
-- Dependencies: 273
-- Data for Name: customer_payment_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.customer_payment_items (id, customer_payment_id, pos_transaction_id, amount, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6408 (class 0 OID 101238)
-- Dependencies: 272
-- Data for Name: customer_payments; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.customer_payments (id, company_id, store_id, customer_id, payment_number, payment_date, payment_method, bank_name, bank_account_number, reference_number, total_amount, notes, received_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6415 (class 0 OID 101559)
-- Dependencies: 279
-- Data for Name: customer_point_mutations; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.customer_point_mutations (id, company_id, customer_id, pos_transaction_id, type, points, balance_before, balance_after, description, created_by, created_at, updated_at, expired_at, is_expired) FROM stdin;
\.


--
-- TOC entry 6371 (class 0 OID 26098)
-- Dependencies: 235
-- Data for Name: customer_tiers; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.customer_tiers (id, company_id, store_id, code, name, description, default_margin_percentage, is_default, is_active, created_at, updated_at, point_multiplier) FROM stdin;
01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	RETAIL	Level 1: Umum / Eceran	Harga standar retail untuk pelanggan umum tanpa membership	20.00	t	t	2026-10-20 06:16:17	2026-10-20 18:21:13	1.00
01a157f4-18ab-7101-8c73-7e36f26bd832	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	GROSIR	Level 2: Grosir / Toko	Harga khusus pedagang, warung, dan agen kulakan	15.00	f	t	2026-10-20 06:16:17	2026-10-20 18:21:13	1.00
01a15a8b-cafb-719b-b053-6eced397d3ee	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	AGEN	Level 3: Agen / Reseller	Harga agen dan toko mitra kulakan	12.00	f	t	2026-10-20 18:21:13	2026-10-20 18:21:13	1.00
01a15a8b-cb01-72ec-b0a4-8fdf1291a177	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	DISTRIBUTOR	Level 4: Distributor / Partai	Harga pembelian partai besar / distributor	8.00	f	t	2026-10-20 18:21:13	2026-10-20 18:21:13	1.00
01a15a8b-cb03-727b-ad07-309eaf531ee5	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	MEMBER	Level 5: Member / VIP	Harga pelanggan loyal / kartu member	18.00	f	t	2026-10-20 18:21:13	2026-10-20 18:21:13	1.00
01a15a8b-cb05-73e9-b6a1-62473d51b830	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	KHUSUS	Level 6: Mitra Khusus / Proyek	Harga kontrak proyek dan instansi	5.00	f	t	2026-10-20 18:21:13	2026-10-20 18:21:13	1.00
\.


--
-- TOC entry 6389 (class 0 OID 26736)
-- Dependencies: 253
-- Data for Name: customers; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.customers (id, company_id, store_id, customer_tier_id, code, name, phone, email, address, credit_limit, current_credit_balance, credit_term_days, is_active, created_at, updated_at, loyalty_points, lifetime_points, deposit_balance) FROM stdin;
01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18a8-73e8-b659-3e7d146cb6ed	CUST-UMUM	UMUM (Pelanggan Umum / Eceran)	-	\N	Walk-in Customer (Tunai)	0.00	0.00	0	t	2026-10-20 06:43:20	2026-10-20 06:43:20	0	0	0.00
01a1580c-dc73-7157-90b5-134513328848	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ab-7101-8c73-7e36f26bd832	CUST-001	Toko Berkah (Grosir)	081234567890	\N	Pasar Induk Kramat Jati Kios B-12	10000000.00	2500000.00	14	t	2026-10-20 06:43:20	2026-10-20 06:43:20	0	0	0.00
01a1580c-dc75-7026-bb02-d81145229e99	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ab-7101-8c73-7e36f26bd832	CUST-002	Warung Bu Siti (Grosir)	081398765432	\N	Jl. Kebon Jeruk No. 8	5000000.00	1200000.00	7	t	2026-10-20 06:43:20	2026-10-20 06:43:20	0	0	0.00
01a1580c-dc77-719a-9398-0c8c82805bc4	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ab-7101-8c73-7e36f26bd832	CUST-003	CV Maju Jaya (Grosir)	081122334455	\N	Ruko Sentra Niaga Blok C-5	25000000.00	83200.00	30	t	2026-10-20 06:43:20	2026-10-20 07:46:06	0	0	0.00
\.


--
-- TOC entry 6368 (class 0 OID 26036)
-- Dependencies: 232
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- TOC entry 6440 (class 0 OID 131135)
-- Dependencies: 304
-- Data for Name: fiscal_year_closings; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.fiscal_year_closings (id, company_id, fiscal_year, closing_date, total_revenue, total_expense, net_profit, retained_earnings_account_id, closing_journal_entry_id, closed_by, status, notes, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6386 (class 0 OID 26651)
-- Dependencies: 250
-- Data for Name: fixed_assets; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.fixed_assets (id, company_id, store_id, asset_code, name, category, acquisition_date, acquisition_cost, salvage_value, useful_life_months, monthly_depreciation_amount, accumulated_depreciation, book_value, last_depreciation_date, status, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6429 (class 0 OID 130611)
-- Dependencies: 293
-- Data for Name: goods_receipt_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.goods_receipt_items (id, goods_receipt_id, purchase_order_item_id, product_id, product_unit_id, unit_code, conversion_factor, quantity_received, base_quantity, estimated_unit_price, allocated_landed_cost, effective_cost_price, subtotal, batch_number, expiry_date, physical_condition, notes, created_at, updated_at) FROM stdin;
01a18ab9-1f6a-7195-84e8-5a2005307416	01a18ab9-1f53-71b1-9405-2bc3073f49fe	01a18ab9-1eb8-7060-8b22-929f2837b464	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	1500.00	71500.00	1400000.00	BATCH-20261030-01	2027-10-30	BAIK	Fisik karung utuh, bersih, dan timbangan sesuai.	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae82-728f-ba05-1b287822c375	01a18ab9-ae7d-7354-843d-261448f50b02	01a18ab9-ae52-7064-92ca-771ee6a5a0a7	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	1500.00	71500.00	1400000.00	BATCH-20261030-01	2027-10-30	BAIK	Fisik karung utuh, bersih, dan timbangan sesuai.	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6dc4-718c-b948-d341b9aafdc5	01a18aba-6dc0-723c-8975-33bcf8fb9543	01a18aba-6d99-7059-b957-2a0acd07214a	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	1500.00	71500.00	1400000.00	BATCH-20261030-01	2027-10-30	BAIK	Fisik karung utuh, bersih, dan timbangan sesuai.	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-a909-7038-b3a9-21115186319d	01a18aba-a905-73ec-bbcc-3e82d20694ae	01a18aba-a8cb-7182-866d-c7739719128b	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	1500.00	71500.00	1400000.00	BATCH-20261030-01	2027-10-30	BAIK	Fisik karung utuh, bersih, dan timbangan sesuai.	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-61f5-7118-86bd-18be7f6985ba	01a18abb-61f1-737c-9655-3076b9f118fc	01a18abb-61ca-7374-bb50-ce066cbf4ec5	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	1500.00	71500.00	1400000.00	BATCH-20261030-01	2027-10-30	BAIK	Fisik karung utuh, bersih, dan timbangan sesuai.	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6428 (class 0 OID 130555)
-- Dependencies: 292
-- Data for Name: goods_receipts; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.goods_receipts (id, company_id, store_id, purchase_order_id, supplier_id, receipt_number, delivery_order_number, receipt_date, status, total_items_received, estimated_total_value, shipping_cost, handling_cost, total_landed_cost, driver_name, vehicle_number, received_by, notes, created_at, updated_at) FROM stdin;
01a18ab9-1f53-71b1-9405-2bc3073f49fe	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a18ab9-1eb4-7185-b1fe-63b9826a49fe	01a15a4d-7908-7000-bf21-6593682a64d5	LPB-20261030-0001	DO-SUPP-20261030-999	2026-10-30	MATCHED	1	1400000.00	20000.00	10000.00	30000.00	\N	\N	01a157f4-189b-710f-b186-ff701beaa316	Barang diterima lengkap oleh Staf Gudang.	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae7d-7354-843d-261448f50b02	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a18ab9-ae4d-72ea-be0a-8770636d06db	01a15a4d-7908-7000-bf21-6593682a64d5	LPB-20261030-0002	DO-SUPP-20261030-999	2026-10-30	MATCHED	1	1400000.00	20000.00	10000.00	30000.00	\N	\N	01a157f4-189b-710f-b186-ff701beaa316	Barang diterima lengkap oleh Staf Gudang.	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6dc0-723c-8975-33bcf8fb9543	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a18aba-6d95-70d4-aa62-0208f4c18c00	01a15a4d-7908-7000-bf21-6593682a64d5	LPB-20261030-0003	DO-SUPP-20261030-999	2026-10-30	MATCHED	1	1400000.00	20000.00	10000.00	30000.00	\N	\N	01a157f4-189b-710f-b186-ff701beaa316	Barang diterima lengkap oleh Staf Gudang.	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-a905-73ec-bbcc-3e82d20694ae	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a18aba-a8c7-73ef-8036-496ebe9e4513	01a15a4d-7908-7000-bf21-6593682a64d5	LPB-20261030-0004	DO-SUPP-20261030-999	2026-10-30	MATCHED	1	1400000.00	20000.00	10000.00	30000.00	\N	\N	01a157f4-189b-710f-b186-ff701beaa316	Barang diterima lengkap oleh Staf Gudang.	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-61f1-737c-9655-3076b9f118fc	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a18abb-61c6-71df-be0c-23ff4526f5cf	01a15a4d-7908-7000-bf21-6593682a64d5	LPB-20261030-0005	DO-SUPP-20261030-999	2026-10-30	MATCHED	1	1400000.00	20000.00	10000.00	30000.00	\N	\N	01a157f4-189b-710f-b186-ff701beaa316	Barang diterima lengkap oleh Staf Gudang.	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6366 (class 0 OID 26021)
-- Dependencies: 230
-- Data for Name: job_batches; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- TOC entry 6365 (class 0 OID 26006)
-- Dependencies: 229
-- Data for Name: jobs; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- TOC entry 6384 (class 0 OID 26587)
-- Dependencies: 248
-- Data for Name: journal_entries; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.journal_entries (id, company_id, store_id, entry_number, entry_date, reference_type, reference_id, reference_number, description, total_debit, total_credit, is_balanced, created_by, created_at, updated_at) FROM stdin;
01a15844-79b6-72b1-b552-00bed0449fbf	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261020-KQEHOX	2026-10-20	POS_SALE	\N	POS-20261020-LXHNKQ	Jurnal Penjualan Kasir POS Faktur: POS-20261020-LXHNKQ	3837000.00	3837000.00	t	01a157f4-177e-732e-aa30-52b7729936fe	2026-10-20 07:44:05	2026-10-20 07:44:05
01a15846-54fe-729d-b0b1-508ac2a91ac7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261020-CD5I5O	2026-10-20	POS_SALE	\N	POS-20261020-WEWHPE	Jurnal Penjualan Kasir POS Faktur: POS-20261020-WEWHPE	162200.00	162200.00	t	01a157f4-177e-732e-aa30-52b7729936fe	2026-10-20 07:46:06	2026-10-20 07:46:06
01a15894-ac7d-7299-bef2-dee7d8334fc3	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261020-PJZYRT	2026-10-20	CASH_DISCREPANCY	\N	SHF-20261020-OKEGV	Jurnal Selisih Kas Blind Closing Shift: SHF-20261020-OKEGV	2205000.00	2205000.00	t	\N	2026-10-20 09:11:40	2026-10-20 09:11:40
01a166f6-f02d-72c2-b503-7cccc6bb679c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261023-WGHQXK	2026-10-23	POS_SALE	\N	POS-20261023-EWEKQK	Jurnal Penjualan Kasir POS Faktur: POS-20261023-EWEKQK	32500.00	32500.00	t	01a157f4-177e-732e-aa30-52b7729936fe	2026-10-23 04:13:41	2026-10-23 04:13:41
01a166f8-ba20-7211-8a74-66f41313b048	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261023-VSS6US	2026-10-23	CASH_DISCREPANCY	\N	SHF-20261020-GBQUN	Jurnal Selisih Kas Blind Closing Shift: SHF-20261020-GBQUN	217500.00	217500.00	t	\N	2026-10-23 04:15:39	2026-10-23 04:15:39
01a17d1f-128e-71ed-b377-9ae578a376f9	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261027-N9EYIX	2026-10-27	POS_SALE	\N	POS-20261027-YOHDVR	Jurnal Penjualan Kasir POS Faktur: POS-20261027-YOHDVR	143000.00	143000.00	t	01a16777-4238-731f-af87-87381b4f044a	2026-10-27 11:29:10	2026-10-27 11:29:10
01a17d1f-152f-7041-961d-ba28012e4aa7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261027-YDRHHL	2026-10-27	POS_SALE	\N	POS-20261027-QES3WX	Jurnal Penjualan Kasir POS Faktur: POS-20261027-QES3WX	143000.00	143000.00	t	01a16777-4238-731f-af87-87381b4f044a	2026-10-27 11:29:11	2026-10-27 11:29:11
01a180eb-c013-7386-9381-df3c1f769dff	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261028-JEDL7O	2026-10-28	POS_SALE	\N	POS-20261028-RTD7MQ	Jurnal Penjualan Kasir POS Faktur: POS-20261028-RTD7MQ	143000.00	143000.00	t	01a16777-4238-731f-af87-87381b4f044a	2026-10-28 05:11:36	2026-10-28 05:11:36
01a180ee-82ad-7055-b08a-efe9421beeee	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261028-OGUSVO	2026-10-28	STOCK_ADJUSTMENT	\N	ADJ-20261028-0001	Jurnal Penyesuaian Stok: ADJ-20261028-0001 (DAMAGE_WASTE)	68000.00	68000.00	t	\N	2026-10-28 05:14:37	2026-10-28 05:14:37
01a182e1-be83-71ae-9f0c-5db0a1d958ce	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261028-FMAARH	2026-10-28	POS_SALE	\N	POS-20261028-DQWW6U	Jurnal Penjualan Kasir POS Faktur: POS-20261028-DQWW6U	175500.00	175500.00	t	01a16777-4238-731f-af87-87381b4f044a	2026-10-28 14:19:54	2026-10-28 14:19:54
01a182e3-0759-72b7-b8cf-81f8cbd061b9	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261028-3TRTN8	2026-10-28	CASH_DISCREPANCY	\N	SHF-20261024-NBWBW	Jurnal Selisih Kas Blind Closing Shift: SHF-20261024-NBWBW	342500.00	342500.00	t	\N	2026-10-28 14:21:19	2026-10-28 14:21:19
01a18a94-74ff-703f-ab50-6a5d852d026d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-VHTK8P	2026-10-30	POS_SALE	\N	POS-STR001-202610-0001	Jurnal Penjualan Kasir POS Faktur: POS-STR001-202610-0001	710000.00	710000.00	t	01a15821-8537-7151-9b04-c67ec65e63f9	2026-10-30 02:12:27	2026-10-30 02:12:27
01a18a97-d254-735e-a283-0112369574f1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-KXDEAT	2026-10-30	POS_SALE	\N	POS-STR001-202610-0002	Jurnal Penjualan Kasir POS Faktur: POS-STR001-202610-0002	710000.00	710000.00	t	01a157f4-177e-732e-aa30-52b7729936fe	2026-10-30 02:16:08	2026-10-30 02:16:08
01a18aa3-8ee5-734e-95ad-8b9dcdd009b6	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-4K2LLB	2026-10-30	POS_SALE	\N	POS-STR001-202610-0003	Jurnal Penjualan Kasir POS Faktur: POS-STR001-202610-0003	715000.00	715000.00	t	01a16777-4238-731f-af87-87381b4f044a	2026-10-30 02:28:57	2026-10-30 02:28:57
01a18aa3-e62c-73d2-8ee5-2457822d5ad0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-2YDMTS	2026-10-30	POS_SALE	\N	POS-STR001-202610-0004	Jurnal Penjualan Kasir POS Faktur: POS-STR001-202610-0004	143000.00	143000.00	t	01a16777-4238-731f-af87-87381b4f044a	2026-10-30 02:29:19	2026-10-30 02:29:19
01a18aa7-773a-73af-b522-36fdd73a3f80	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-2GYJOC	2026-10-30	POS_SALE	\N	POS-STR001-202610-0005	Jurnal Penjualan Kasir POS Faktur: POS-STR001-202610-0005	710000.00	710000.00	t	01a157f4-177e-732e-aa30-52b7729936fe	2026-10-30 02:33:13	2026-10-30 02:33:13
01a18aab-2681-7119-9839-bec05f228c56	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-CUX3RM	2026-10-30	POS_SALE	\N	POS-STR001-202610-0006	Jurnal Penjualan Kasir POS Faktur: POS-STR001-202610-0006	715000.00	715000.00	t	01a16777-4238-731f-af87-87381b4f044a	2026-10-30 02:37:14	2026-10-30 02:37:14
01a18ab9-1fce-700f-816e-d39b0a6f028c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-CQG2QY	2026-10-30	GOODS_RECEIPT_LPB	\N	LPB-20261030-0001	Jurnal Penerimaan Fisik LPB Gudang No: LPB-20261030-0001 DO: DO-SUPP-20261030-999	1430000.00	1430000.00	t	\N	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-202f-73f6-b178-dd171ee6f006	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-KYF53S	2026-10-30	THREE_WAY_MATCH	\N	FB-20261030-0001	Jurnal Three-Way Matching Faktur: INV-SPL-20261030-007 vs LPB: LPB-20261030-0001	1400000.00	1400000.00	t	\N	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae97-727f-89b1-d8c4eadef5dc	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-WHGIKM	2026-10-30	GOODS_RECEIPT_LPB	\N	LPB-20261030-0002	Jurnal Penerimaan Fisik LPB Gudang No: LPB-20261030-0002 DO: DO-SUPP-20261030-999	1430000.00	1430000.00	t	\N	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18ab9-aeb4-7370-bfc6-e222d9d73f34	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-YXINEP	2026-10-30	THREE_WAY_MATCH	\N	FB-20261030-0002	Jurnal Three-Way Matching Faktur: INV-SPL-20261030-007 vs LPB: LPB-20261030-0002	1400000.00	1400000.00	t	\N	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6dd9-704a-9603-ef9e05f7256e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-0IWHTR	2026-10-30	GOODS_RECEIPT_LPB	\N	LPB-20261030-0003	Jurnal Penerimaan Fisik LPB Gudang No: LPB-20261030-0003 DO: DO-SUPP-20261030-999	1430000.00	1430000.00	t	\N	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-6df6-73eb-9f42-379557e2657e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-DIBMGU	2026-10-30	THREE_WAY_MATCH	\N	FB-20261030-0003	Jurnal Three-Way Matching Faktur: INV-SPL-20261030-007 vs LPB: LPB-20261030-0003	1400000.00	1400000.00	t	\N	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-a921-7209-989c-ad00e9b910e0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-72OVUI	2026-10-30	GOODS_RECEIPT_LPB	\N	LPB-20261030-0004	Jurnal Penerimaan Fisik LPB Gudang No: LPB-20261030-0004 DO: DO-SUPP-20261030-999	1430000.00	1430000.00	t	\N	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a93f-738e-8c56-c062b059f365	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-A5UUT6	2026-10-30	THREE_WAY_MATCH	\N	FB-20261030-0004	Jurnal Three-Way Matching Faktur: INV-SPL-20261030-007 vs LPB: LPB-20261030-0004	1400000.00	1400000.00	t	\N	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a9d4-73c7-89ab-82c46f647b17	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-H6BJIF	2026-10-30	SUPPLIER_PAYMENT	\N	PAY-20261030-0001	Jurnal Pelunasan Hutang Supplier No: PAY-20261030-0001	1400000.00	1400000.00	t	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-620a-71c9-beb9-7aaf528c3f94	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-45KEHZ	2026-10-30	GOODS_RECEIPT_LPB	\N	LPB-20261030-0005	Jurnal Penerimaan Fisik LPB Gudang No: LPB-20261030-0005 DO: DO-SUPP-20261030-999	1430000.00	1430000.00	t	\N	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-6224-724e-91f7-2b9f8ed5e8e1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-UDWTQK	2026-10-30	THREE_WAY_MATCH	\N	FB-20261030-0005	Jurnal Three-Way Matching Faktur: INV-SPL-20261030-007 vs LPB: LPB-20261030-0005	1400000.00	1400000.00	t	\N	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-6249-713d-b347-4e0652307b13	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	JRN-20261030-IITVC3	2026-10-30	SUPPLIER_PAYMENT	\N	PAY-20261030-0002	Jurnal Pelunasan Hutang Supplier No: PAY-20261030-0002	1400000.00	1400000.00	t	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6385 (class 0 OID 26626)
-- Dependencies: 249
-- Data for Name: journal_entry_lines; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.journal_entry_lines (id, journal_entry_id, account_id, debit, credit, memo, created_at, updated_at) FROM stdin;
01a15844-79e0-70e9-a2dc-7722eff3fe97	01a15844-79b6-72b1-b552-00bed0449fbf	01a157f4-1953-71f2-ae2f-d611958a8240	2005000.00	0.00	Penerimaan Kas Penjualan POS POS-20261020-LXHNKQ	2026-10-20 07:44:05	2026-10-20 07:44:05
01a15844-79e4-706c-84e7-fb09aa539778	01a15844-79b6-72b1-b552-00bed0449fbf	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	2005000.00	Pendapatan Penjualan Non-PKP POS-20261020-LXHNKQ	2026-10-20 07:44:05	2026-10-20 07:44:05
01a15844-79e6-702f-b39a-5d8dd95fdd3d	01a15844-79b6-72b1-b552-00bed0449fbf	01a157f4-196e-7350-8529-780f013ba229	1832000.00	0.00	Beban HPP Penjualan POS-20261020-LXHNKQ	2026-10-20 07:44:05	2026-10-20 07:44:05
01a15844-79e7-71d2-a981-a9e964f423f5	01a15844-79b6-72b1-b552-00bed0449fbf	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	1832000.00	Pengurangan Persediaan Penjualan POS-20261020-LXHNKQ	2026-10-20 07:44:05	2026-10-20 07:44:05
01a15846-5501-73f0-9a14-d76afcfe04bc	01a15846-54fe-729d-b0b1-508ac2a91ac7	01a157f4-195b-71c6-89aa-6ed6ca31f4df	83200.00	0.00	Piutang Penjualan Tempo/Kredit POS POS-20261020-WEWHPE	2026-10-20 07:46:06	2026-10-20 07:46:06
01a15846-5503-73a6-9aaa-58cfdd3707e6	01a15846-54fe-729d-b0b1-508ac2a91ac7	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	83200.00	Pendapatan Penjualan Non-PKP POS-20261020-WEWHPE	2026-10-20 07:46:06	2026-10-20 07:46:06
01a15846-5504-719f-a6df-20c1476cebb4	01a15846-54fe-729d-b0b1-508ac2a91ac7	01a157f4-196e-7350-8529-780f013ba229	79000.00	0.00	Beban HPP Penjualan POS-20261020-WEWHPE	2026-10-20 07:46:06	2026-10-20 07:46:06
01a15846-5505-739b-ad76-9030f88bc6dc	01a15846-54fe-729d-b0b1-508ac2a91ac7	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	79000.00	Pengurangan Persediaan Penjualan POS-20261020-WEWHPE	2026-10-20 07:46:06	2026-10-20 07:46:06
01a15894-acb0-721d-9309-e1a6e268616c	01a15894-ac7d-7299-bef2-dee7d8334fc3	01a157f4-1972-71c7-bed3-5cae0982fed0	2205000.00	0.00	Selisih Kurang Kas Shift SHF-20261020-OKEGV	2026-10-20 09:11:40	2026-10-20 09:11:40
01a15894-acbc-7014-9377-458d05501a44	01a15894-ac7d-7299-bef2-dee7d8334fc3	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	2205000.00	Penyesuaian Fisik Kas Toko Shift SHF-20261020-OKEGV	2026-10-20 09:11:40	2026-10-20 09:11:40
01a166f6-f091-71e2-958c-0b5a4e7a1f89	01a166f6-f02d-72c2-b503-7cccc6bb679c	01a157f4-1953-71f2-ae2f-d611958a8240	17500.00	0.00	Penerimaan Kas Penjualan POS POS-20261023-EWEKQK	2026-10-23 04:13:41	2026-10-23 04:13:41
01a166f6-f0ac-724b-ab2f-8485e96f3bc9	01a166f6-f02d-72c2-b503-7cccc6bb679c	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	17500.00	Pendapatan Penjualan Non-PKP POS-20261023-EWEKQK	2026-10-23 04:13:41	2026-10-23 04:13:41
01a166f6-f0ae-722c-b497-a8a3172f2765	01a166f6-f02d-72c2-b503-7cccc6bb679c	01a157f4-196e-7350-8529-780f013ba229	15000.00	0.00	Beban HPP Penjualan POS-20261023-EWEKQK	2026-10-23 04:13:41	2026-10-23 04:13:41
01a166f6-f0af-7034-bed6-19a3dcea1545	01a166f6-f02d-72c2-b503-7cccc6bb679c	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	15000.00	Pengurangan Persediaan Penjualan POS-20261023-EWEKQK	2026-10-23 04:13:41	2026-10-23 04:13:41
01a166f8-ba24-703e-bb57-fe3660e3e249	01a166f8-ba20-7211-8a74-66f41313b048	01a157f4-1972-71c7-bed3-5cae0982fed0	217500.00	0.00	Selisih Kurang Kas Shift SHF-20261020-GBQUN	2026-10-23 04:15:39	2026-10-23 04:15:39
01a166f8-ba26-71b7-81cb-5eb259036856	01a166f8-ba20-7211-8a74-66f41313b048	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	217500.00	Penyesuaian Fisik Kas Toko Shift SHF-20261020-GBQUN	2026-10-23 04:15:39	2026-10-23 04:15:39
01a17d1f-12d8-71ab-b04a-0ba251c6d911	01a17d1f-128e-71ed-b377-9ae578a376f9	01a157f4-1953-71f2-ae2f-d611958a8240	75000.00	0.00	Penerimaan Kas Penjualan POS POS-20261027-YOHDVR	2026-10-27 11:29:10	2026-10-27 11:29:10
01a17d1f-1311-7072-8eb5-32178ec5cb89	01a17d1f-128e-71ed-b377-9ae578a376f9	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	75000.00	Pendapatan Penjualan Non-PKP POS-20261027-YOHDVR	2026-10-27 11:29:10	2026-10-27 11:29:10
01a17d1f-1312-73aa-b7ea-d19434c6cc9d	01a17d1f-128e-71ed-b377-9ae578a376f9	01a157f4-196e-7350-8529-780f013ba229	68000.00	0.00	Beban HPP Penjualan POS-20261027-YOHDVR	2026-10-27 11:29:10	2026-10-27 11:29:10
01a17d1f-1313-7164-8243-0aaed686ad34	01a17d1f-128e-71ed-b377-9ae578a376f9	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	68000.00	Pengurangan Persediaan Penjualan POS-20261027-YOHDVR	2026-10-27 11:29:10	2026-10-27 11:29:10
01a17d1f-1532-7227-aad7-1b1dfd803035	01a17d1f-152f-7041-961d-ba28012e4aa7	01a157f4-1953-71f2-ae2f-d611958a8240	75000.00	0.00	Penerimaan Kas Penjualan POS POS-20261027-QES3WX	2026-10-27 11:29:11	2026-10-27 11:29:11
01a17d1f-1536-7032-b760-9e63bc23dbb9	01a17d1f-152f-7041-961d-ba28012e4aa7	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	75000.00	Pendapatan Penjualan Non-PKP POS-20261027-QES3WX	2026-10-27 11:29:11	2026-10-27 11:29:11
01a17d1f-1537-7147-a7c6-b14c8290b301	01a17d1f-152f-7041-961d-ba28012e4aa7	01a157f4-196e-7350-8529-780f013ba229	68000.00	0.00	Beban HPP Penjualan POS-20261027-QES3WX	2026-10-27 11:29:11	2026-10-27 11:29:11
01a17d1f-1539-716d-a3a9-30ef30fd9520	01a17d1f-152f-7041-961d-ba28012e4aa7	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	68000.00	Pengurangan Persediaan Penjualan POS-20261027-QES3WX	2026-10-27 11:29:11	2026-10-27 11:29:11
01a180eb-c02f-7044-a210-723043a626dd	01a180eb-c013-7386-9381-df3c1f769dff	01a157f4-1956-7303-aaf9-1028a8db8293	75000.00	0.00	Penerimaan Transfer Bank [Bank Rakyat Indonesia - 334455667788] POS POS-20261028-RTD7MQ	2026-10-28 05:11:36	2026-10-28 05:11:36
01a180eb-c041-732d-8b5b-d70538016f15	01a180eb-c013-7386-9381-df3c1f769dff	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	75000.00	Pendapatan Penjualan Non-PKP POS-20261028-RTD7MQ	2026-10-28 05:11:36	2026-10-28 05:11:36
01a180eb-c042-71c1-99cc-224e5b61b5b5	01a180eb-c013-7386-9381-df3c1f769dff	01a157f4-196e-7350-8529-780f013ba229	68000.00	0.00	Beban HPP Penjualan POS-20261028-RTD7MQ	2026-10-28 05:11:36	2026-10-28 05:11:36
01a180eb-c044-7217-9d3d-18a2a93c414e	01a180eb-c013-7386-9381-df3c1f769dff	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	68000.00	Pengurangan Persediaan Penjualan POS-20261028-RTD7MQ	2026-10-28 05:11:36	2026-10-28 05:11:36
01a180ee-82b0-73c0-8b78-a7c87e28f516	01a180ee-82ad-7055-b08a-efe9421beeee	01a157f4-1972-71c7-bed3-5cae0982fed0	68000.00	0.00	Beban Kerusakan Persediaan: ADJ-20261028-0001	2026-10-28 05:14:37	2026-10-28 05:14:37
01a180ee-82b2-7333-b0ef-eeb9aff317b3	01a180ee-82ad-7055-b08a-efe9421beeee	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	68000.00	Pengurangan Persediaan Rusak: ADJ-20261028-0001	2026-10-28 05:14:37	2026-10-28 05:14:37
01a182e1-be8e-730e-bc08-d7f8f44fff92	01a182e1-be83-71ae-9f0c-5db0a1d958ce	01a157f4-1953-71f2-ae2f-d611958a8240	92500.00	0.00	Penerimaan Kas Penjualan POS POS-20261028-DQWW6U	2026-10-28 14:19:54	2026-10-28 14:19:54
01a182e1-beb3-71db-8a32-d1ab1e8ea62b	01a182e1-be83-71ae-9f0c-5db0a1d958ce	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	92500.00	Pendapatan Penjualan Non-PKP POS-20261028-DQWW6U	2026-10-28 14:19:54	2026-10-28 14:19:54
01a182e1-beb5-70ff-85d0-4169936b6824	01a182e1-be83-71ae-9f0c-5db0a1d958ce	01a157f4-196e-7350-8529-780f013ba229	83000.00	0.00	Beban HPP Penjualan POS-20261028-DQWW6U	2026-10-28 14:19:54	2026-10-28 14:19:54
01a182e1-beb6-71f4-9366-307e3549550c	01a182e1-be83-71ae-9f0c-5db0a1d958ce	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	83000.00	Pengurangan Persediaan Penjualan POS-20261028-DQWW6U	2026-10-28 14:19:54	2026-10-28 14:19:54
01a182e3-075e-7225-a160-d224c37efcf2	01a182e3-0759-72b7-b8cf-81f8cbd061b9	01a157f4-1972-71c7-bed3-5cae0982fed0	342500.00	0.00	Selisih Kurang Kas Shift SHF-20261024-NBWBW	2026-10-28 14:21:19	2026-10-28 14:21:19
01a182e3-0760-7205-a40d-32fe3c88abef	01a182e3-0759-72b7-b8cf-81f8cbd061b9	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	342500.00	Penyesuaian Fisik Kas Toko Shift SHF-20261024-NBWBW	2026-10-28 14:21:19	2026-10-28 14:21:19
01a18a94-754a-71ae-9c16-de085c106a63	01a18a94-74ff-703f-ab50-6a5d852d026d	01a157f4-1953-71f2-ae2f-d611958a8240	370000.00	0.00	Penerimaan Kas Penjualan POS POS-STR001-202610-0001	2026-10-30 02:12:27	2026-10-30 02:12:27
01a18a94-7579-71f4-b9b2-6a60d44ebd7f	01a18a94-74ff-703f-ab50-6a5d852d026d	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	370000.00	Pendapatan Penjualan Non-PKP POS-STR001-202610-0001	2026-10-30 02:12:27	2026-10-30 02:12:27
01a18a94-757a-73bb-ac17-4ea7bd775bd7	01a18a94-74ff-703f-ab50-6a5d852d026d	01a157f4-196e-7350-8529-780f013ba229	340000.00	0.00	Beban HPP Penjualan POS-STR001-202610-0001	2026-10-30 02:12:27	2026-10-30 02:12:27
01a18a94-757b-7300-8d50-159ecb8b3f49	01a18a94-74ff-703f-ab50-6a5d852d026d	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	340000.00	Pengurangan Persediaan Penjualan POS-STR001-202610-0001	2026-10-30 02:12:27	2026-10-30 02:12:27
01a18a97-d258-705b-b17d-8746dde07ef1	01a18a97-d254-735e-a283-0112369574f1	01a157f4-1953-71f2-ae2f-d611958a8240	370000.00	0.00	Penerimaan Kas Penjualan POS POS-STR001-202610-0002	2026-10-30 02:16:08	2026-10-30 02:16:08
01a18a97-d25a-7359-ad7e-05288d1e3e2b	01a18a97-d254-735e-a283-0112369574f1	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	370000.00	Pendapatan Penjualan Non-PKP POS-STR001-202610-0002	2026-10-30 02:16:08	2026-10-30 02:16:08
01a18a97-d25b-720e-ad4a-ba318312aa1c	01a18a97-d254-735e-a283-0112369574f1	01a157f4-196e-7350-8529-780f013ba229	340000.00	0.00	Beban HPP Penjualan POS-STR001-202610-0002	2026-10-30 02:16:08	2026-10-30 02:16:08
01a18a97-d25c-703f-8373-b0f25b89ca6a	01a18a97-d254-735e-a283-0112369574f1	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	340000.00	Pengurangan Persediaan Penjualan POS-STR001-202610-0002	2026-10-30 02:16:08	2026-10-30 02:16:08
01a18aa3-8ee8-7035-9b81-f04f39ecd3ce	01a18aa3-8ee5-734e-95ad-8b9dcdd009b6	01a157f4-1953-71f2-ae2f-d611958a8240	375000.00	0.00	Penerimaan Kas Penjualan POS POS-STR001-202610-0003	2026-10-30 02:28:57	2026-10-30 02:28:57
01a18aa3-8eea-72a4-ae59-176fb56b8eb3	01a18aa3-8ee5-734e-95ad-8b9dcdd009b6	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	337838.00	Pendapatan Bersih Penjualan POS-STR001-202610-0003	2026-10-30 02:28:57	2026-10-30 02:28:57
01a18aa3-8eeb-7105-87cf-7408459bb4e1	01a18aa3-8ee5-734e-95ad-8b9dcdd009b6	01a157f4-1967-700d-8e14-719d81050362	0.00	37162.00	PPN Keluaran Penjualan POS-STR001-202610-0003	2026-10-30 02:28:57	2026-10-30 02:28:57
01a18aa3-8eec-72f8-98f1-6d5e7a80e4d4	01a18aa3-8ee5-734e-95ad-8b9dcdd009b6	01a157f4-196e-7350-8529-780f013ba229	340000.00	0.00	Beban HPP Penjualan POS-STR001-202610-0003	2026-10-30 02:28:57	2026-10-30 02:28:57
01a18aa3-8eed-714e-8520-c7e2841adda1	01a18aa3-8ee5-734e-95ad-8b9dcdd009b6	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	340000.00	Pengurangan Persediaan Penjualan POS-STR001-202610-0003	2026-10-30 02:28:57	2026-10-30 02:28:57
01a18aa3-e62f-7318-b369-4f837ee15680	01a18aa3-e62c-73d2-8ee5-2457822d5ad0	01a157f4-1953-71f2-ae2f-d611958a8240	75000.00	0.00	Penerimaan Kas Penjualan POS POS-STR001-202610-0004	2026-10-30 02:29:19	2026-10-30 02:29:19
01a18aa3-e631-719f-92db-4c06b2938adf	01a18aa3-e62c-73d2-8ee5-2457822d5ad0	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	75000.00	Pendapatan Penjualan Non-PKP POS-STR001-202610-0004	2026-10-30 02:29:19	2026-10-30 02:29:19
01a18aa3-e633-71e4-b67e-fd1529339bbf	01a18aa3-e62c-73d2-8ee5-2457822d5ad0	01a157f4-196e-7350-8529-780f013ba229	68000.00	0.00	Beban HPP Penjualan POS-STR001-202610-0004	2026-10-30 02:29:19	2026-10-30 02:29:19
01a18aa3-e635-70d5-9117-48852a86609f	01a18aa3-e62c-73d2-8ee5-2457822d5ad0	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	68000.00	Pengurangan Persediaan Penjualan POS-STR001-202610-0004	2026-10-30 02:29:19	2026-10-30 02:29:19
01a18aa7-773e-710f-9649-df64e38dbd54	01a18aa7-773a-73af-b522-36fdd73a3f80	01a157f4-1953-71f2-ae2f-d611958a8240	370000.00	0.00	Penerimaan Kas Penjualan POS POS-STR001-202610-0005	2026-10-30 02:33:13	2026-10-30 02:33:13
01a18aa7-7740-725c-8da0-450dd33ff97f	01a18aa7-773a-73af-b522-36fdd73a3f80	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	370000.00	Pendapatan Penjualan Non-PKP POS-STR001-202610-0005	2026-10-30 02:33:13	2026-10-30 02:33:13
01a18aa7-7741-7252-b94a-6df0c2391f34	01a18aa7-773a-73af-b522-36fdd73a3f80	01a157f4-196e-7350-8529-780f013ba229	340000.00	0.00	Beban HPP Penjualan POS-STR001-202610-0005	2026-10-30 02:33:13	2026-10-30 02:33:13
01a18aa7-7743-71c9-acca-de2b58e2e8ff	01a18aa7-773a-73af-b522-36fdd73a3f80	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	340000.00	Pengurangan Persediaan Penjualan POS-STR001-202610-0005	2026-10-30 02:33:13	2026-10-30 02:33:13
01a18aab-2684-727d-ab37-676382673c50	01a18aab-2681-7119-9839-bec05f228c56	01a157f4-1953-71f2-ae2f-d611958a8240	375000.00	0.00	Penerimaan Kas Penjualan POS POS-STR001-202610-0006	2026-10-30 02:37:14	2026-10-30 02:37:14
01a18aab-2686-7290-baf6-a95121073ee2	01a18aab-2681-7119-9839-bec05f228c56	01a157f4-196b-7395-9ee7-565f2c2d6e85	0.00	375000.00	Pendapatan Penjualan Non-PKP POS-STR001-202610-0006	2026-10-30 02:37:14	2026-10-30 02:37:14
01a18aab-2687-71c2-8584-9c3362cbfd0f	01a18aab-2681-7119-9839-bec05f228c56	01a157f4-196e-7350-8529-780f013ba229	340000.00	0.00	Beban HPP Penjualan POS-STR001-202610-0006	2026-10-30 02:37:14	2026-10-30 02:37:14
01a18aab-2688-72f9-86cd-8aafc91c0686	01a18aab-2681-7119-9839-bec05f228c56	01a157f4-195d-72fc-93fc-7c042b48382a	0.00	340000.00	Pengurangan Persediaan Penjualan POS-STR001-202610-0006	2026-10-30 02:37:14	2026-10-30 02:37:14
01a18ab9-1fd1-73ff-9cb7-64e950a522d3	01a18ab9-1fce-700f-816e-d39b0a6f028c	01a157f4-195d-72fc-93fc-7c042b48382a	1430000.00	0.00	Penerimaan Fisik Gudang LPB: LPB-20261030-0001	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-1fe8-7286-adf8-18e7a1704ac4	01a18ab9-1fce-700f-816e-d39b0a6f028c	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	0.00	1400000.00	Akrual Hutang Belum Ditagih LPB: LPB-20261030-0001	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-1fe9-7025-b6eb-9e4e9c8fabba	01a18ab9-1fce-700f-816e-d39b0a6f028c	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	30000.00	Biaya Angkut/Landed Cost LPB: LPB-20261030-0001	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-2031-7010-9556-5e7db486219a	01a18ab9-202f-73f6-b178-dd171ee6f006	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	1400000.00	0.00	Eliminasi Akrual GRNI LPB LPB-20261030-0001 via Faktur INV-SPL-20261030-007	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-2032-73f0-8471-0523fdadfaf9	01a18ab9-202f-73f6-b178-dd171ee6f006	01a157f4-1964-71c1-8699-ae85c3a3fa56	0.00	1400000.00	Hutang Usaha Resmi Faktur INV-SPL-20261030-007	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae9a-713b-bf9f-55bbeb38c98a	01a18ab9-ae97-727f-89b1-d8c4eadef5dc	01a157f4-195d-72fc-93fc-7c042b48382a	1430000.00	0.00	Penerimaan Fisik Gudang LPB: LPB-20261030-0002	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18ab9-ae9c-735a-83ed-73b52ffb868f	01a18ab9-ae97-727f-89b1-d8c4eadef5dc	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	0.00	1400000.00	Akrual Hutang Belum Ditagih LPB: LPB-20261030-0002	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18ab9-ae9d-732e-b5d1-3b511a896cda	01a18ab9-ae97-727f-89b1-d8c4eadef5dc	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	30000.00	Biaya Angkut/Landed Cost LPB: LPB-20261030-0002	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18ab9-aeb5-738e-b7c8-de204030bf52	01a18ab9-aeb4-7370-bfc6-e222d9d73f34	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	1400000.00	0.00	Eliminasi Akrual GRNI LPB LPB-20261030-0002 via Faktur INV-SPL-20261030-007	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18ab9-aeb6-73a2-938d-b68762f5b020	01a18ab9-aeb4-7370-bfc6-e222d9d73f34	01a157f4-1964-71c1-8699-ae85c3a3fa56	0.00	1400000.00	Hutang Usaha Resmi Faktur INV-SPL-20261030-007	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6ddb-730d-95da-efecfe72886e	01a18aba-6dd9-704a-9603-ef9e05f7256e	01a157f4-195d-72fc-93fc-7c042b48382a	1430000.00	0.00	Penerimaan Fisik Gudang LPB: LPB-20261030-0003	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-6ddd-71dd-b39a-7dd931e4aed0	01a18aba-6dd9-704a-9603-ef9e05f7256e	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	0.00	1400000.00	Akrual Hutang Belum Ditagih LPB: LPB-20261030-0003	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-6dde-72f9-b0ac-9a9304060cf9	01a18aba-6dd9-704a-9603-ef9e05f7256e	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	30000.00	Biaya Angkut/Landed Cost LPB: LPB-20261030-0003	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-6df7-7032-be46-843b12ede65c	01a18aba-6df6-73eb-9f42-379557e2657e	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	1400000.00	0.00	Eliminasi Akrual GRNI LPB LPB-20261030-0003 via Faktur INV-SPL-20261030-007	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-6df8-720b-aff7-d103acc297ba	01a18aba-6df6-73eb-9f42-379557e2657e	01a157f4-1964-71c1-8699-ae85c3a3fa56	0.00	1400000.00	Hutang Usaha Resmi Faktur INV-SPL-20261030-007	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-a923-735a-8cc7-ae1fec76b153	01a18aba-a921-7209-989c-ad00e9b910e0	01a157f4-195d-72fc-93fc-7c042b48382a	1430000.00	0.00	Penerimaan Fisik Gudang LPB: LPB-20261030-0004	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a926-73b9-a27b-e3dfb35c05be	01a18aba-a921-7209-989c-ad00e9b910e0	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	0.00	1400000.00	Akrual Hutang Belum Ditagih LPB: LPB-20261030-0004	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a927-7057-88bd-bef5ce2ee191	01a18aba-a921-7209-989c-ad00e9b910e0	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	30000.00	Biaya Angkut/Landed Cost LPB: LPB-20261030-0004	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a940-71f5-8e57-3fd1d853f46f	01a18aba-a93f-738e-8c56-c062b059f365	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	1400000.00	0.00	Eliminasi Akrual GRNI LPB LPB-20261030-0004 via Faktur INV-SPL-20261030-007	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a942-73d9-b406-ab92e5d71573	01a18aba-a93f-738e-8c56-c062b059f365	01a157f4-1964-71c1-8699-ae85c3a3fa56	0.00	1400000.00	Hutang Usaha Resmi Faktur INV-SPL-20261030-007	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a9d5-70a5-9f1f-0f8e5884bfba	01a18aba-a9d4-73c7-89ab-82c46f647b17	01a157f4-1964-71c1-8699-ae85c3a3fa56	1400000.00	0.00	Pelunasan Hutang Supplier PT INDOFOOD CBP SUKSES MAKMUR (PAY-20261030-0001)	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18aba-a9d6-73f4-a960-d618edab4fea	01a18aba-a9d4-73c7-89ab-82c46f647b17	01a157f4-1956-7303-aaf9-1028a8db8293	0.00	1400000.00	Pengeluaran Bank Pembayaran Hutang Supplier PAY-20261030-0001	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-620d-72b0-98ca-cc8e125d2a6b	01a18abb-620a-71c9-beb9-7aaf528c3f94	01a157f4-195d-72fc-93fc-7c042b48382a	1430000.00	0.00	Penerimaan Fisik Gudang LPB: LPB-20261030-0005	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-620f-7127-8009-5a033e5ef343	01a18abb-620a-71c9-beb9-7aaf528c3f94	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	0.00	1400000.00	Akrual Hutang Belum Ditagih LPB: LPB-20261030-0005	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-6210-70b7-bd7b-51af6515cc9e	01a18abb-620a-71c9-beb9-7aaf528c3f94	01a157f4-1953-71f2-ae2f-d611958a8240	0.00	30000.00	Biaya Angkut/Landed Cost LPB: LPB-20261030-0005	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-6225-7335-b517-01e7ae84d72a	01a18abb-6224-724e-91f7-2b9f8ed5e8e1	01a17c99-ddd2-7258-ba2f-59ebbb6a17a8	1400000.00	0.00	Eliminasi Akrual GRNI LPB LPB-20261030-0005 via Faktur INV-SPL-20261030-007	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-6226-71de-910e-7242bf75857f	01a18abb-6224-724e-91f7-2b9f8ed5e8e1	01a157f4-1964-71c1-8699-ae85c3a3fa56	0.00	1400000.00	Hutang Usaha Resmi Faktur INV-SPL-20261030-007	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-624a-7289-ae53-61632e4d4450	01a18abb-6249-713d-b347-4e0652307b13	01a157f4-1964-71c1-8699-ae85c3a3fa56	1400000.00	0.00	Pelunasan Hutang Supplier PT INDOFOOD CBP SUKSES MAKMUR (PAY-20261030-0002)	2026-10-30 02:54:58	2026-10-30 02:54:58
01a18abb-624b-73e3-bd39-1a2357862f5a	01a18abb-6249-713d-b347-4e0652307b13	01a157f4-1956-7303-aaf9-1028a8db8293	0.00	1400000.00	Pengeluaran Bank Pembayaran Hutang Supplier PAY-20261030-0002	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6414 (class 0 OID 101535)
-- Dependencies: 278
-- Data for Name: loyalty_programs; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.loyalty_programs (id, company_id, name, spend_amount_per_point, point_redemption_value, min_points_to_redeem, is_active, created_at, updated_at, points_expiry_days, allow_merchandise_redemption, only_registered_customers) FROM stdin;
01a16987-da89-7000-a581-75419cd5cf22	01a157f4-189f-7352-8b9a-ef0be10c7b1a	Program Poin Reward Belanja	10000.00	100.00	10	t	2026-10-23 16:11:13	2026-10-23 16:11:13	365	t	t
\.


--
-- TOC entry 6421 (class 0 OID 101918)
-- Dependencies: 285
-- Data for Name: loyalty_reward_redemptions; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.loyalty_reward_redemptions (id, company_id, store_id, customer_id, loyalty_reward_id, redemption_number, points_used, quantity, redemption_date, notes, created_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6420 (class 0 OID 101888)
-- Dependencies: 284
-- Data for Name: loyalty_rewards; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.loyalty_rewards (id, company_id, code, name, product_id, points_required, stock_quantity, description, is_active, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6358 (class 0 OID 25939)
-- Dependencies: 222
-- Data for Name: migrations; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_10_20_000001_create_sigma_schema_and_extensions	1
5	2026_10_20_000002_create_companies_table	1
6	2026_10_20_000003_create_stores_table	1
7	2026_10_20_000004_create_customer_tiers_table	1
8	2026_10_20_000005_create_products_table	1
9	2026_10_20_000006_create_product_units_table	1
10	2026_10_20_000007_create_product_barcodes_table	1
11	2026_10_20_000008_create_product_prices_table	1
12	2026_10_20_000009_create_product_stocks_table	1
13	2026_10_20_000010_create_stock_mutations_table	1
14	2026_10_20_000011_create_vouchers_table	1
15	2026_10_20_000012_create_cashier_shifts_tables	1
16	2026_10_20_000013_create_pos_transactions_tables	1
17	2026_10_20_000014_create_chart_of_accounts_tables	1
18	2026_10_20_000015_create_fixed_assets_tables	1
19	2026_10_20_000016_create_tax_configurations_table	1
20	2026_10_20_000017_create_customers_table	2
21	2026_10_20_000018_add_sales_person_id_to_pos_transactions_table	3
22	2026_10_20_000019_add_tax_mode_to_pos_transactions_table	4
23	2026_10_20_000020_add_bank_details_to_pos_transactions_table	5
24	2026_10_20_000021_add_split_payment_to_pos_transactions_table	6
25	2026_10_20_000022_add_wallpaper_path_to_companies_table	7
26	2026_10_20_000023_create_suppliers_table	8
27	2026_10_20_000024_create_product_categories_table	8
28	2026_10_20_000025_add_category_id_to_products_table	8
29	2026_10_20_000026_create_units_table	8
30	2026_10_20_000027_create_bank_accounts_table	8
31	2026_10_20_000026_add_extended_ipos5_fields_to_products_and_units_tables	9
32	2026_10_20_000027_seed_six_customer_tiers_table	10
33	2026_10_20_000028_create_purchases_tables	11
34	2026_10_23_000001_add_security_columns_to_users_table	12
35	2026_10_23_000002_create_pos_returns_tables	13
36	2026_10_23_000003_create_purchase_returns_tables	13
37	2026_10_23_000004_create_purchase_orders_tables	14
38	2026_10_23_000005_create_supplier_payments_tables	14
39	2026_10_23_000006_create_stock_transfers_tables	15
40	2026_10_23_000007_create_stock_opnames_tables	15
41	2026_10_23_000008_create_customer_payments_and_receivables_tables	16
42	2026_10_23_000009_create_product_assemblies_and_boms_tables	17
43	2026_10_23_000010_create_product_batches_and_fefo_tables	18
45	2026_10_23_000011_create_customer_loyalty_points_tables	19
46	2026_10_23_000012_create_consignment_tables	20
48	2026_10_23_000013_create_promotions_and_advanced_loyalty_tables	21
49	2026_10_23_000014_add_only_registered_customers_to_loyalty_programs	22
50	2026_10_23_000015_create_sales_orders_tables	23
51	2026_10_24_000001_create_stock_requirements_tables	24
52	2026_10_24_000002_add_flexible_fields_to_purchase_returns_table	24
53	2026_10_24_000003_create_purchase_return_debt_allocations_table	24
54	2026_10_27_000001_create_goods_receipts_tables	25
55	2026_10_27_000002_add_tax_fields_to_suppliers_table	26
56	2026_10_27_174935_add_shipping_cost_to_purchases_table	27
57	2026_10_28_014801_create_stock_adjustments_tables	28
58	2026_10_28_024236_create_stock_movements_tables	29
59	2026_10_28_041000_create_transfer_requests_tables	30
60	2026_10_28_051000_create_cash_transactions_tables	31
61	2026_10_28_060000_create_deposit_management_tables	32
62	2026_10_28_091500_seed_operating_expense_chart_of_accounts	33
63	2026_10_28_100000_create_accounting_settings_table	34
64	2026_10_28_110000_create_fiscal_year_closings_table	35
65	2026_10_28_120000_add_rounding_and_split_payment_fields	36
66	2026_10_28_170950_create_user_groups_table	37
67	2026_10_29_003000_add_printer_and_receipt_settings_to_companies_table	38
68	2026_10_29_004000_create_transaction_number_patterns_table	39
69	2026_10_29_005000_add_cashier_operational_settings_to_companies_table	40
70	2026_10_29_013653_add_tax_metadata_and_store_profile_fields	41
71	2026_10_29_182151_create_audit_logs_table	42
\.


--
-- TOC entry 6360 (class 0 OID 25962)
-- Dependencies: 224
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- TOC entry 6397 (class 0 OID 100799)
-- Dependencies: 261
-- Data for Name: pos_return_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.pos_return_items (id, pos_return_id, pos_transaction_item_id, product_id, product_unit_id, quantity, base_quantity, unit_price, subtotal, unit_cost, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6396 (class 0 OID 100749)
-- Dependencies: 260
-- Data for Name: pos_returns; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.pos_returns (id, company_id, store_id, pos_transaction_id, cashier_shift_id, processed_by, approved_by, return_number, return_date, refund_method, subtotal_returned, tax_returned, total_refund_amount, reason, status, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6382 (class 0 OID 26528)
-- Dependencies: 246
-- Data for Name: pos_transaction_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.pos_transaction_items (id, pos_transaction_id, product_id, product_unit_id, quantity, base_quantity, unit_cost, unit_price, subtotal, discount_amount, total_price, created_at, updated_at, batch_id, batch_number, expired_date) FROM stdin;
01a15844-791f-7336-8927-97ab3f52a629	01a15844-7917-72f8-83ea-2835834b57d3	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	5.00	25.0000	68000.0000	370000.00	1850000.00	0.00	1850000.00	2026-10-20 07:44:04	2026-10-20 07:44:04	\N	\N	\N
01a15844-79a8-7213-b79f-e8d12fbc00a2	01a15844-7917-72f8-83ea-2835834b57d3	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-191a-7232-93c6-de44ed3257ba	1.00	12.0000	11000.0000	155000.00	155000.00	0.00	155000.00	2026-10-20 07:44:05	2026-10-20 07:44:05	\N	\N	\N
01a15846-54ef-70a5-a09e-f1bd266d95f7	01a15846-54ec-72e7-8bce-a03ba90012ed	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	1.00	1.0000	68000.0000	71000.00	71000.00	0.00	71000.00	2026-10-20 07:46:06	2026-10-20 07:46:06	\N	\N	\N
01a15846-54f2-7214-9c59-e8d3e870050e	01a15846-54ec-72e7-8bce-a03ba90012ed	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-1919-7378-8a2e-b91ab32d7d03	1.00	1.0000	11000.0000	12200.00	12200.00	0.00	12200.00	2026-10-20 07:46:06	2026-10-20 07:46:06	\N	\N	\N
01a166f6-efc4-716b-93ab-917eebecd5d2	01a166f6-ef9a-7303-8414-e043459de838	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e1-73dc-80a7-86a547023d42	1.00	1.0000	15000.0000	17500.00	17500.00	0.00	17500.00	2026-10-23 04:13:41	2026-10-23 04:13:41	\N	\N	\N
01a17d1f-123a-72a0-b9f2-ff55c2eabe10	01a17d1f-1219-73d2-9d2d-8557ba92d608	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	1.00	1.0000	68000.0000	75000.00	75000.00	0.00	75000.00	2026-10-27 11:29:10	2026-10-27 11:29:10	\N	\N	\N
01a17d1f-1510-737a-aefb-4577a168b784	01a17d1f-150c-7147-a966-2a17d8edbb74	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	1.00	1.0000	68000.0000	75000.00	75000.00	0.00	75000.00	2026-10-27 11:29:11	2026-10-27 11:29:11	\N	\N	\N
01a180eb-bf5e-7048-8315-3e9a8d9b7df8	01a180eb-bf41-705a-8d61-99805be2affb	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	1.00	1.0000	68000.0000	75000.00	75000.00	0.00	75000.00	2026-10-28 05:11:36	2026-10-28 05:11:36	\N	\N	\N
01a182e1-bda8-71ae-bf25-78403723aacd	01a182e1-bd90-72bc-b5d3-2268fb8b83fd	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	1.00	1.0000	68000.0000	75000.00	75000.00	0.00	75000.00	2026-10-28 14:19:54	2026-10-28 14:19:54	\N	\N	\N
01a182e1-bdab-73c0-b5f4-0d399dec9c6f	01a182e1-bd90-72bc-b5d3-2268fb8b83fd	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e1-73dc-80a7-86a547023d42	1.00	1.0000	15000.0000	17500.00	17500.00	0.00	17500.00	2026-10-28 14:19:54	2026-10-28 14:19:54	\N	\N	\N
01a18a94-73fe-73fd-aa27-c4decdb33eac	01a18a94-73c8-70e8-8e7d-24f820a68a3d	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	1.00	5.0000	68000.0000	370000.00	370000.00	0.00	370000.00	2026-10-30 02:12:27	2026-10-30 02:12:27	\N	\N	\N
01a18a97-d244-738f-bdb9-06b9903ff619	01a18a97-d23e-7161-8053-7961b11f673c	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	1.00	5.0000	68000.0000	370000.00	370000.00	0.00	370000.00	2026-10-30 02:16:08	2026-10-30 02:16:08	\N	\N	\N
01a18aa3-8ec0-735f-ae2f-648ec1962071	01a18aa3-8ebb-718f-83a4-aff1619cb2ff	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	5.00	5.0000	68000.0000	75000.00	375000.00	0.00	375000.00	2026-10-30 02:28:57	2026-10-30 02:28:57	\N	\N	\N
01a18aa3-e617-72a2-b0fb-fccb83ec8ad7	01a18aa3-e613-716d-90dc-a558faea17ef	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	1.00	1.0000	68000.0000	75000.00	75000.00	0.00	75000.00	2026-10-30 02:29:19	2026-10-30 02:29:19	\N	\N	\N
01a18aa7-7724-719f-ad80-eec06a878a9a	01a18aa7-7720-73a9-b930-6dcc93cdf5f7	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	1.00	5.0000	68000.0000	370000.00	370000.00	0.00	370000.00	2026-10-30 02:33:13	2026-10-30 02:33:13	\N	\N	\N
01a18aab-266d-71f1-88e6-76d93cd26a28	01a18aab-2669-71f2-b316-6978cac2eff2	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	5.00	5.0000	68000.0000	75000.00	375000.00	0.00	375000.00	2026-10-30 02:37:14	2026-10-30 02:37:14	\N	\N	\N
\.


--
-- TOC entry 6381 (class 0 OID 26463)
-- Dependencies: 245
-- Data for Name: pos_transactions; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.pos_transactions (id, company_id, store_id, cashier_shift_id, customer_tier_id, cashier_id, invoice_number, total_item_count, subtotal, discount_amount, voucher_id, voucher_discount, tax_amount, grand_total, payment_method, paid_amount, change_amount, status, reprint_count, last_reprinted_at, notes, created_at, updated_at, customer_id, sales_person_id, tax_mode, bank_name, bank_account_number, payment_reference, credit_amount, split_payment_method, due_date, payment_status, remaining_receivable, points_earned, points_redeemed, points_discount_amount, rounding_amount, split_cash_amount, split_non_cash_amount, split_non_cash_method) FROM stdin;
01a15844-7917-72f8-83ea-2835834b57d3	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157fc-2622-72c4-a62f-972389680675	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a157f4-177e-732e-aa30-52b7729936fe	POS-20261020-LXHNKQ	6	2005000.00	0.00	\N	0.00	0.00	2005000.00	CASH	3000000.00	995000.00	COMPLETED	0	\N	\N	2026-10-20 07:44:04	2026-10-20 07:44:04	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a157f4-177e-732e-aa30-52b7729936fe	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a15846-54ec-72e7-8bce-a03ba90012ed	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157fc-2622-72c4-a62f-972389680675	01a157f4-18ab-7101-8c73-7e36f26bd832	01a157f4-177e-732e-aa30-52b7729936fe	POS-20261020-WEWHPE	2	83200.00	0.00	\N	0.00	0.00	83200.00	TEMPO	0.00	0.00	COMPLETED	0	\N	\N	2026-10-20 07:46:06	2026-10-20 07:46:06	01a1580c-dc77-719a-9398-0c8c82805bc4	01a157f4-177e-732e-aa30-52b7729936fe	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a166f6-ef9a-7303-8414-e043459de838	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15894-bec6-70d5-a6cb-43e8f76c6db7	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a157f4-177e-732e-aa30-52b7729936fe	POS-20261023-EWEKQK	1	17500.00	0.00	\N	0.00	0.00	17500.00	CASH	17500.00	0.00	COMPLETED	0	\N	\N	2026-10-23 04:13:41	2026-10-23 04:13:41	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a157f4-177e-732e-aa30-52b7729936fe	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a17d1f-1219-73d2-9d2d-8557ba92d608	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a16c06-ec35-7139-bdb6-bd8b7ae0ce16	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a16777-4238-731f-af87-87381b4f044a	POS-20261027-YOHDVR	1	75000.00	0.00	\N	0.00	0.00	75000.00	CASH	75000.00	0.00	COMPLETED	0	\N	\N	2026-10-27 11:29:10	2026-10-27 11:29:10	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a16777-4238-731f-af87-87381b4f044a	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a17d1f-150c-7147-a966-2a17d8edbb74	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a16c06-ec35-7139-bdb6-bd8b7ae0ce16	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a16777-4238-731f-af87-87381b4f044a	POS-20261027-QES3WX	1	75000.00	0.00	\N	0.00	0.00	75000.00	CASH	75000.00	0.00	COMPLETED	0	\N	\N	2026-10-27 11:29:11	2026-10-27 11:29:11	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a16777-4238-731f-af87-87381b4f044a	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a180eb-bf41-705a-8d61-99805be2affb	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a16c06-ec35-7139-bdb6-bd8b7ae0ce16	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a16777-4238-731f-af87-87381b4f044a	POS-20261028-RTD7MQ	1	75000.00	0.00	\N	0.00	0.00	75000.00	TRANSFER	75000.00	0.00	COMPLETED	0	\N	\N	2026-10-28 05:11:36	2026-10-28 05:11:36	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a16777-4238-731f-af87-87381b4f044a	NON	Bank Rakyat Indonesia	334455667788	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a182e1-bd90-72bc-b5d3-2268fb8b83fd	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a16c06-ec35-7139-bdb6-bd8b7ae0ce16	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a16777-4238-731f-af87-87381b4f044a	POS-20261028-DQWW6U	2	92500.00	0.00	\N	0.00	0.00	92500.00	CASH	100000.00	7500.00	COMPLETED	0	\N	\N	2026-10-28 14:19:54	2026-10-28 14:19:54	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a16777-4238-731f-af87-87381b4f044a	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a18a94-73c8-70e8-8e7d-24f820a68a3d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a182fa-eb76-724e-9220-0a8a9e2fbca1	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a15821-8537-7151-9b04-c67ec65e63f9	POS-STR001-202610-0001	1	370000.00	0.00	\N	0.00	0.00	370000.00	CASH	375000.00	5000.00	COMPLETED	0	\N	\N	2026-10-30 02:12:27	2026-10-30 02:12:27	\N	01a15821-8537-7151-9b04-c67ec65e63f9	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a18a97-d23e-7161-8053-7961b11f673c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a166f8-ecf9-723d-9131-a82b9cc662a5	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a157f4-177e-732e-aa30-52b7729936fe	POS-STR001-202610-0002	1	370000.00	0.00	\N	0.00	0.00	370000.00	CASH	375000.00	5000.00	COMPLETED	0	\N		2026-10-30 02:16:07	2026-10-30 02:16:07	\N	01a157f4-177e-732e-aa30-52b7729936fe	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a18aa3-8ebb-718f-83a4-aff1619cb2ff	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a182e3-7233-71af-b1f0-07481dda729f	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a16777-4238-731f-af87-87381b4f044a	POS-STR001-202610-0003	5	375000.00	0.00	\N	0.00	37162.00	375000.00	CASH	375000.00	0.00	COMPLETED	0	\N	\N	2026-10-30 02:28:57	2026-10-30 02:28:57	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a16777-4238-731f-af87-87381b4f044a	PPN_INCLUDE	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a18aa3-e613-716d-90dc-a558faea17ef	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a182e3-7233-71af-b1f0-07481dda729f	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a16777-4238-731f-af87-87381b4f044a	POS-STR001-202610-0004	1	75000.00	0.00	\N	0.00	0.00	75000.00	CASH	75000.00	0.00	COMPLETED	0	\N	\N	2026-10-30 02:29:19	2026-10-30 02:29:19	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a16777-4238-731f-af87-87381b4f044a	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a18aa7-7720-73a9-b930-6dcc93cdf5f7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a166f8-ecf9-723d-9131-a82b9cc662a5	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a157f4-177e-732e-aa30-52b7729936fe	POS-STR001-202610-0005	1	370000.00	0.00	\N	0.00	0.00	370000.00	CASH	500000.00	130000.00	COMPLETED	0	\N	\N	2026-10-30 02:33:13	2026-10-30 02:33:13	\N	01a157f4-177e-732e-aa30-52b7729936fe	PPN_INCLUDE	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
01a18aab-2669-71f2-b316-6978cac2eff2	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a182e3-7233-71af-b1f0-07481dda729f	01a157f4-18a8-73e8-b659-3e7d146cb6ed	01a16777-4238-731f-af87-87381b4f044a	POS-STR001-202610-0006	5	375000.00	0.00	\N	0.00	0.00	375000.00	CASH	375000.00	0.00	COMPLETED	0	\N	\N	2026-10-30 02:37:14	2026-10-30 02:37:14	01a1580c-dc67-71c3-9e8b-55da4d529d6a	01a16777-4238-731f-af87-87381b4f044a	NON	\N	\N	\N	0.00	\N	\N	PAID	0.00	0	0	0.00	0.00	0.00	0.00	\N
\.


--
-- TOC entry 6411 (class 0 OID 101330)
-- Dependencies: 275
-- Data for Name: product_assemblies; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_assemblies (id, company_id, store_id, assembly_number, assembly_date, type, parent_product_id, parent_product_unit_id, quantity, unit_cost, total_cost, notes, assembled_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6412 (class 0 OID 101377)
-- Dependencies: 276
-- Data for Name: product_assembly_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_assembly_items (id, product_assembly_id, component_product_id, component_product_unit_id, quantity, base_quantity, unit_cost, total_cost, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6374 (class 0 OID 26213)
-- Dependencies: 238
-- Data for Name: product_barcodes; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_barcodes (id, company_id, store_id, product_id, product_unit_id, barcode, is_primary, created_at, updated_at) FROM stdin;
01a157f4-18ba-71af-9101-d2b89474d978	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	8991234560011	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-18be-72bb-9b00-57eb241609bf	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	8991234560012	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-18e8-7102-9257-c24f45565d44	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e1-73dc-80a7-86a547023d42	8991234560021	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-18eb-704b-90d7-ae27378ab6be	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e5-7065-bef4-7d5550fb9fe2	8991234560022	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1902-72a9-a8b3-118fdc3352c0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-18ff-7025-a0d5-a3004a58d4c9	8991234560031	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1905-7069-a007-f57c8cc7ba5f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-1900-70a9-a12f-cfb8e3f5ebbd	8991234560032	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-191c-73f3-a527-89a760779010	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-1919-7378-8a2e-b91ab32d7d03	8991234560041	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-191d-7044-833d-ebcb67c5fdcd	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-191a-7232-93c6-de44ed3257ba	8991234560042	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1935-7121-9a24-9744baa04eed	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-1931-7027-b8e0-cff292708fb6	8991234560051	t	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1937-7395-b260-3a27d6ade34c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-1933-709f-a68c-2cf34e6a4afa	8991234560052	t	2026-10-20 06:16:17	2026-10-20 06:16:17
\.


--
-- TOC entry 6413 (class 0 OID 101409)
-- Dependencies: 277
-- Data for Name: product_batches; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_batches (id, company_id, store_id, product_id, batch_number, expired_date, manufacture_date, initial_quantity, current_quantity, unit_cost, status, notes, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6410 (class 0 OID 101296)
-- Dependencies: 274
-- Data for Name: product_boms; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_boms (id, company_id, parent_product_id, component_product_id, component_product_unit_id, quantity, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6391 (class 0 OID 26810)
-- Dependencies: 255
-- Data for Name: product_categories; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_categories (id, company_id, code, name, description, is_active, created_at, updated_at) FROM stdin;
01a15a4d-78e4-716e-9a06-ae5d8a5db8c7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SEMBAKO	Sembako & Bahan Pokok	Beras, minyak goreng, gula, tepung, telur	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78eb-7037-b28f-91820e227b8b	01a157f4-189f-7352-8b9a-ef0be10c7b1a	MINUMAN	Minuman & Kemasan	Air mineral, teh, kopi, minuman bersoda	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78ec-739d-9dbf-cec33f8138b3	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SNACK	Makanan Ringan & Biskuit	Snack, biskuit, wafer, permen	t	2026-10-20 17:13:09	2026-10-20 17:13:09
\.


--
-- TOC entry 6375 (class 0 OID 26251)
-- Dependencies: 239
-- Data for Name: product_prices; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_prices (id, company_id, store_id, product_id, product_unit_id, customer_tier_id, min_quantity, price, margin_percentage, is_active, created_at, updated_at, discount_1, discount_2, discount_3, discount_4) FROM stdin;
01a157f4-18c5-7000-a347-9243306ebb2e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	75000.00	10.29	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18c9-7247-a53e-6d3429c7b196	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	71000.00	4.41	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18cb-71e8-839f-43b13551fb29	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	01a157f4-18ab-7101-8c73-7e36f26bd832	10.00	69500.00	2.21	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18cd-728d-bf96-afd9b9eefb70	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	370000.00	8.82	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18cf-7366-af2b-96a9ba7979c5	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	350000.00	2.94	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18d2-732b-830e-ae1dcbdde81b	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b7-7142-8edd-84ff83e4bb64	01a157f4-18ab-7101-8c73-7e36f26bd832	5.00	345000.00	1.47	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18ed-7010-8856-35a897bc7df1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e1-73dc-80a7-86a547023d42	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	17500.00	16.67	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18ef-73cd-88e0-75fcd60e79f0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e1-73dc-80a7-86a547023d42	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	16200.00	8.00	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18f0-7390-9243-52b1bf8a6feb	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e1-73dc-80a7-86a547023d42	01a157f4-18ab-7101-8c73-7e36f26bd832	12.00	15800.00	5.33	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18f2-70d9-a1d8-885ac896fdc0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e5-7065-bef4-7d5550fb9fe2	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	205000.00	13.89	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18f5-714b-8568-326cf7d1f55d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e5-7065-bef4-7d5550fb9fe2	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	190000.00	5.56	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-18f7-723d-8805-94fe5e870a89	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-18e5-7065-bef4-7d5550fb9fe2	01a157f4-18ab-7101-8c73-7e36f26bd832	5.00	186000.00	3.33	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1907-7106-970d-cb81e21ea902	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-18ff-7025-a0d5-a3004a58d4c9	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	17000.00	17.24	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1909-716d-bcd3-00abfce524c9	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-18ff-7025-a0d5-a3004a58d4c9	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	15800.00	8.97	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-190c-7111-bab0-4e6833fb7907	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-18ff-7025-a0d5-a3004a58d4c9	01a157f4-18ab-7101-8c73-7e36f26bd832	10.00	15300.00	5.52	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-190e-72b2-a19a-a34ff5fccf97	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-1900-70a9-a12f-cfb8e3f5ebbd	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	825000.00	13.79	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1910-7130-9156-572d22fb3fcc	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-1900-70a9-a12f-cfb8e3f5ebbd	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	765000.00	5.52	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-191f-7291-a828-3333a429e771	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-1919-7378-8a2e-b91ab32d7d03	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	13500.00	22.73	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1920-7219-a981-1999a8b7fe27	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-1919-7378-8a2e-b91ab32d7d03	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	12200.00	10.91	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1922-7354-89f6-3cc15f18a722	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-1919-7378-8a2e-b91ab32d7d03	01a157f4-18ab-7101-8c73-7e36f26bd832	12.00	11800.00	7.27	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1924-70ff-8d95-c932136aba2e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-191a-7232-93c6-de44ed3257ba	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	155000.00	17.42	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1928-73a6-af37-cae2c43c62ee	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-191a-7232-93c6-de44ed3257ba	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	141000.00	6.82	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1939-7266-8983-c8f6f89d0cff	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-1931-7027-b8e0-cff292708fb6	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	55000.00	14.58	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-193b-732a-a4c7-43e7b154dbc3	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-1931-7027-b8e0-cff292708fb6	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	51500.00	7.29	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-193d-70aa-94ce-edb3b8098926	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-1931-7027-b8e0-cff292708fb6	01a157f4-18ab-7101-8c73-7e36f26bd832	5.00	50000.00	4.17	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-193e-72c8-a8ec-5839d816aaab	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-1933-709f-a68c-2cf34e6a4afa	01a157f4-18a8-73e8-b659-3e7d146cb6ed	1.00	265000.00	10.42	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
01a157f4-1940-7276-a10f-3947e020515f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-1933-709f-a68c-2cf34e6a4afa	01a157f4-18ab-7101-8c73-7e36f26bd832	1.00	248000.00	3.33	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00
\.


--
-- TOC entry 6376 (class 0 OID 26299)
-- Dependencies: 240
-- Data for Name: product_stocks; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_stocks (id, company_id, store_id, product_id, physical_quantity, reserved_quantity, quarantined_quantity, created_at, updated_at) FROM stdin;
01a157f4-1912-70cf-93a4-47edbeae7bf6	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18fd-718a-b85a-3d29f23396ae	200.0000	0.0000	0.0000	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1941-7091-9e09-ad0dba0395c4	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-192e-7383-9cf2-f785ac084ed7	80.0000	0.0000	0.0000	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1929-7370-b3ff-e1f6d5935e29	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	150.0000	0.0000	0.0000	2026-10-20 06:16:17	2026-10-20 17:13:09
01a157f4-18f9-731b-a5f7-2c6649139837	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18df-7040-b7aa-8aa4960c7bdd	239.0000	0.0000	0.0000	2026-10-20 06:16:17	2026-10-28 14:19:54
01a157f4-18d7-7247-bf1b-3c5641bc9e29	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	170.0000	0.0000	0.0000	2026-10-20 06:16:17	2026-10-30 02:54:58
\.


--
-- TOC entry 6373 (class 0 OID 26177)
-- Dependencies: 237
-- Data for Name: product_units; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.product_units (id, company_id, store_id, product_id, unit_code, unit_name, conversion_factor, is_base_unit, is_active, created_at, updated_at, weight_gram, length_cm, width_cm, height_cm, point_reward, commission_rate) FROM stdin;
01a157f4-18b4-7108-b036-dbda0ed54654	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	SAK	Sak 5 Kg	1.0000	t	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-18b7-7142-8edd-84ff83e4bb64	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18ae-7201-a4e9-f414a835527f	BAL	Bal (isi 5 Sak)	5.0000	f	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-18e1-73dc-80a7-86a547023d42	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	PCS	Botol 1 Liter	1.0000	t	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-18e5-7065-bef4-7d5550fb9fe2	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18df-7040-b7aa-8aa4960c7bdd	DUS	Karton Dus (isi 12 Botol)	12.0000	f	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-18ff-7025-a0d5-a3004a58d4c9	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	BKS	Bungkus 1 Kg	1.0000	t	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-1900-70a9-a12f-cfb8e3f5ebbd	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-18fd-718a-b85a-3d29f23396ae	KRG	Karung (isi 50 Bks)	50.0000	f	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-1919-7378-8a2e-b91ab32d7d03	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	PCS	Kemasan 1 Kg	1.0000	t	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-191a-7232-93c6-de44ed3257ba	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	DUS	Dus Karton (isi 12 Pcs)	12.0000	f	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-1931-7027-b8e0-cff292708fb6	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	PRG	Piring (isi 30 Butir)	1.0000	t	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
01a157f4-1933-709f-a68c-2cf34e6a4afa	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	01a157f4-192e-7383-9cf2-f785ac084ed7	IKT	Ikat (isi 5 Piring / 150 Butir)	5.0000	f	t	2026-10-20 06:16:17	2026-10-20 06:16:17	0.00	0.00	0.00	0.00	0.00	0.00
\.


--
-- TOC entry 6372 (class 0 OID 26129)
-- Dependencies: 236
-- Data for Name: products; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.products (id, company_id, store_id, sku, name, slug, description, category, brand, cost_price, last_purchase_price, total_stock_value, total_quantity, min_stock_alert, max_stock, has_serial_number, has_batch_expiry, is_point_of_sale, is_active, created_at, updated_at, category_id, type, supplier_id, sub_category, rack_location, sale_status, notes, tax_type, tax_reference_code, image_path, additional_images, is_consignment, consignment_supplier_id, consignment_cost) FROM stdin;
01a157f4-18fd-718a-b85a-3d29f23396ae	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	SBK-GLA-001	Gula Pasir Putih Premium 1kg	gula-pasir-putih-premium-1kg	\N	Sembako	Gulaku	14500.0000	14500.0000	2900000.0000	200.0000	30.00	\N	f	f	t	t	2026-10-20 06:16:17	2026-10-20 06:16:17	\N	BARANG	\N	\N	\N	ACTIVE	\N	NON	\N	\N	\N	f	\N	0.0000
01a157f4-192e-7383-9cf2-f785ac084ed7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	SBK-TLR-001	Telur Ayam Ras Segar 1 Piring	telur-ayam-ras-segar-1-piring	\N	Sembako	Peternak Lokal	48000.0000	48000.0000	3840000.0000	80.0000	15.00	\N	f	f	t	t	2026-10-20 06:16:17	2026-10-20 06:16:17	\N	BARANG	\N	\N	\N	ACTIVE	\N	NON	\N	\N	\N	f	\N	0.0000
01a157f4-1917-7246-b4f8-3ac4ee26c8e7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	SBK-TRG-001	Tepung Terigu Segitiga Biru 1kg	tepung-terigu-segitiga-biru-1kg	\N	Sembako	Bogasari	11000.0000	11000.0000	1507000.0000	137.0000	25.00	\N	f	f	t	t	2026-10-20 06:16:17	2026-10-20 07:46:06	\N	BARANG	\N	\N	\N	ACTIVE	\N	NON	\N	\N	\N	f	\N	0.0000
01a157f4-18df-7040-b7aa-8aa4960c7bdd	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	SBK-MYK-001	Minyak Goreng Sawit 1 Liter	minyak-goreng-sawit-1-liter	\N	Sembako	Sanira	15000.0000	15000.0000	3570000.0000	238.0000	48.00	\N	f	f	t	t	2026-10-20 06:16:17	2026-10-28 14:19:54	\N	BARANG	\N	\N	\N	ACTIVE	\N	NON	\N	\N	\N	f	\N	0.0000
01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	SBK-BRS-001	Beras Premium Pandan Wangi 5kg	beras-premium-pandan-wangi-5kg	\N	Sembako	Pandan Wangi	70430.5556	71500.0000	10142000.0000	144.0000	20.00	\N	f	f	t	t	2026-10-20 06:16:17	2026-10-30 02:54:58	\N	BARANG	\N	\N	\N	ACTIVE	\N	NON	\N	\N	\N	f	\N	0.0000
\.


--
-- TOC entry 6422 (class 0 OID 101960)
-- Dependencies: 286
-- Data for Name: promotions; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.promotions (id, company_id, store_id, code, name, type, description, buy_product_id, buy_quantity, reward_product_id, reward_quantity, special_price, discount_percent, discount_nominal, min_spend, max_usage_per_transaction, start_date, end_date, start_time, end_time, days_of_week, is_active, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6395 (class 0 OID 26968)
-- Dependencies: 259
-- Data for Name: purchase_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.purchase_items (id, purchase_id, product_id, product_unit_id, unit_code, conversion_factor, quantity, base_quantity, unit_price, base_unit_price, discount_percent, subtotal, old_cost_price, new_cost_price, is_price_increased, is_selling_price_updated, old_selling_price, new_selling_price, created_at, updated_at, allocated_shipping_cost) FROM stdin;
01a18ab9-2006-71ef-853c-9e7a0706c104	01a18ab9-1ff8-7218-929e-499ab9ac0d6c	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	70000.0000	0.00	1400000.00	0.0000	0.0000	f	f	\N	\N	2026-10-30 02:52:30	2026-10-30 02:52:30	0.00
01a18ab9-aeac-72f7-8b17-d57a6dd9f641	01a18ab9-aea9-70c5-a53d-3331836ad9c9	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	70000.0000	0.00	1400000.00	0.0000	0.0000	f	f	\N	\N	2026-10-30 02:53:07	2026-10-30 02:53:07	0.00
01a18aba-6def-71e2-9081-1145d4276082	01a18aba-6dec-7178-b527-8946817e08e0	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	70000.0000	0.00	1400000.00	0.0000	0.0000	f	f	\N	\N	2026-10-30 02:53:56	2026-10-30 02:53:56	0.00
01a18aba-a938-72fc-8a33-84d4e925e710	01a18aba-a935-73bb-8f38-c56374050990	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	70000.0000	0.00	1400000.00	0.0000	0.0000	f	f	\N	\N	2026-10-30 02:54:11	2026-10-30 02:54:11	0.00
01a18abb-621e-7392-a55f-e516eac1d164	01a18abb-621b-71e1-b411-8720d7ad4369	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.0000	70000.00	70000.0000	0.00	1400000.00	0.0000	0.0000	f	f	\N	\N	2026-10-30 02:54:58	2026-10-30 02:54:58	0.00
\.


--
-- TOC entry 6401 (class 0 OID 100969)
-- Dependencies: 265
-- Data for Name: purchase_order_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.purchase_order_items (id, purchase_order_id, product_id, product_unit_id, unit_code, conversion_factor, quantity_ordered, quantity_received, unit_price, subtotal, created_at, updated_at) FROM stdin;
01a18ab8-f56c-7149-91ec-341e539525a4	01a18ab8-f530-7088-adb8-76ea346e295d	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	0.00	70000.00	1400000.00	2026-10-30 02:52:19	2026-10-30 02:52:19
01a18ab9-1eb8-7060-8b22-929f2837b464	01a18ab9-1eb4-7185-b1fe-63b9826a49fe	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.00	70000.00	1400000.00	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae52-7064-92ca-771ee6a5a0a7	01a18ab9-ae4d-72ea-be0a-8770636d06db	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.00	70000.00	1400000.00	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6d99-7059-b957-2a0acd07214a	01a18aba-6d95-70d4-aa62-0208f4c18c00	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.00	70000.00	1400000.00	2026-10-30 02:53:55	2026-10-30 02:53:56
01a18aba-a8cb-7182-866d-c7739719128b	01a18aba-a8c7-73ef-8036-496ebe9e4513	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.00	70000.00	1400000.00	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-61ca-7374-bb50-ce066cbf4ec5	01a18abb-61c6-71df-be0c-23ff4526f5cf	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	20.00	20.00	70000.00	1400000.00	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6400 (class 0 OID 100923)
-- Dependencies: 264
-- Data for Name: purchase_orders; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.purchase_orders (id, company_id, store_id, supplier_id, po_number, order_date, expected_delivery_date, status, subtotal, tax_amount, grand_total, notes, created_by, approved_by, approved_at, created_at, updated_at) FROM stdin;
01a18ab8-f530-7088-adb8-76ea346e295d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PO-20261030-0001	2026-10-30	2026-11-02	DRAFT	1400000.00	0.00	1400000.00	PO Uji Coba Alur Supply Chain & Moving Average HPP	01a157f4-189b-710f-b186-ff701beaa316	\N	\N	2026-10-30 02:52:19	2026-10-30 02:52:19
01a18ab9-1eb4-7185-b1fe-63b9826a49fe	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PO-20261030-0002	2026-10-30	2026-11-02	RECEIVED	1400000.00	0.00	1400000.00	PO Uji Coba Alur Supply Chain & Moving Average HPP	01a157f4-189b-710f-b186-ff701beaa316	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:52:30	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae4d-72ea-be0a-8770636d06db	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PO-20261030-0003	2026-10-30	2026-11-02	RECEIVED	1400000.00	0.00	1400000.00	PO Uji Coba Alur Supply Chain & Moving Average HPP	01a157f4-189b-710f-b186-ff701beaa316	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:53:07	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6d95-70d4-aa62-0208f4c18c00	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PO-20261030-0004	2026-10-30	2026-11-02	RECEIVED	1400000.00	0.00	1400000.00	PO Uji Coba Alur Supply Chain & Moving Average HPP	01a157f4-189b-710f-b186-ff701beaa316	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:53:56	2026-10-30 02:53:55	2026-10-30 02:53:56
01a18aba-a8c7-73ef-8036-496ebe9e4513	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PO-20261030-0005	2026-10-30	2026-11-02	RECEIVED	1400000.00	0.00	1400000.00	PO Uji Coba Alur Supply Chain & Moving Average HPP	01a157f4-189b-710f-b186-ff701beaa316	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:11	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-61c6-71df-be0c-23ff4526f5cf	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PO-20261030-0006	2026-10-30	2026-11-02	RECEIVED	1400000.00	0.00	1400000.00	PO Uji Coba Alur Supply Chain & Moving Average HPP	01a157f4-189b-710f-b186-ff701beaa316	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:58	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6427 (class 0 OID 130521)
-- Dependencies: 291
-- Data for Name: purchase_return_debt_allocations; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.purchase_return_debt_allocations (id, company_id, store_id, purchase_return_id, purchase_id, allocated_amount, notes, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6399 (class 0 OID 100886)
-- Dependencies: 263
-- Data for Name: purchase_return_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.purchase_return_items (id, purchase_return_id, purchase_item_id, product_id, product_unit_id, quantity, base_quantity, unit_price, subtotal, created_at, updated_at, condition, notes) FROM stdin;
\.


--
-- TOC entry 6398 (class 0 OID 100837)
-- Dependencies: 262
-- Data for Name: purchase_returns; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.purchase_returns (id, company_id, store_id, purchase_id, supplier_id, return_number, return_date, refund_type, subtotal, tax_amount, grand_total, reason, status, created_by, created_at, updated_at, return_type, approved_by, approved_at, notes) FROM stdin;
\.


--
-- TOC entry 6394 (class 0 OID 26912)
-- Dependencies: 258
-- Data for Name: purchases; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.purchases (id, company_id, store_id, supplier_id, invoice_number, reference_number, purchase_date, due_date, payment_type, bank_account_id, status, subtotal, tax_amount, discount_amount, grand_total, paid_amount, notes, created_by, created_at, updated_at, purchase_order_id, goods_receipt_id, three_way_match_status, shipping_cost) FROM stdin;
01a18ab9-1ff8-7218-929e-499ab9ac0d6c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	INV-SPL-20261030-007	FB-20261030-0001	2026-10-30	2026-11-29	TEMPO	\N	RECEIVED	1400000.00	0.00	0.00	1400000.00	0.00	Verifikasi invoice cocok 100% dengan PO & LPB.	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:52:30	2026-10-30 02:52:30	01a18ab9-1eb4-7185-b1fe-63b9826a49fe	01a18ab9-1f53-71b1-9405-2bc3073f49fe	MATCHED	0.00
01a18ab9-aea9-70c5-a53d-3331836ad9c9	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	INV-SPL-20261030-007	FB-20261030-0002	2026-10-30	2026-11-29	TEMPO	\N	RECEIVED	1400000.00	0.00	0.00	1400000.00	0.00	Verifikasi invoice cocok 100% dengan PO & LPB.	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:53:07	2026-10-30 02:53:07	01a18ab9-ae4d-72ea-be0a-8770636d06db	01a18ab9-ae7d-7354-843d-261448f50b02	MATCHED	0.00
01a18aba-6dec-7178-b527-8946817e08e0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	INV-SPL-20261030-007	FB-20261030-0003	2026-10-30	2026-11-29	TEMPO	\N	RECEIVED	1400000.00	0.00	0.00	1400000.00	0.00	Verifikasi invoice cocok 100% dengan PO & LPB.	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:53:56	2026-10-30 02:53:56	01a18aba-6d95-70d4-aa62-0208f4c18c00	01a18aba-6dc0-723c-8975-33bcf8fb9543	MATCHED	0.00
01a18aba-a935-73bb-8f38-c56374050990	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	INV-SPL-20261030-007	FB-20261030-0004	2026-10-30	2026-11-29	TEMPO	\N	RECEIVED	1400000.00	0.00	0.00	1400000.00	1400000.00	Verifikasi invoice cocok 100% dengan PO & LPB.	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:11	2026-10-30 02:54:11	01a18aba-a8c7-73ef-8036-496ebe9e4513	01a18aba-a905-73ec-bbcc-3e82d20694ae	MATCHED	0.00
01a18abb-621b-71e1-b411-8720d7ad4369	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	INV-SPL-20261030-007	FB-20261030-0005	2026-10-30	2026-11-29	TEMPO	\N	RECEIVED	1400000.00	0.00	0.00	1400000.00	1400000.00	Verifikasi invoice cocok 100% dengan PO & LPB.	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:58	2026-10-30 02:54:58	01a18abb-61c6-71df-be0c-23ff4526f5cf	01a18abb-61f1-737c-9655-3076b9f118fc	MATCHED	0.00
\.


--
-- TOC entry 6424 (class 0 OID 102068)
-- Dependencies: 288
-- Data for Name: sales_order_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.sales_order_items (id, sales_order_id, product_id, product_unit_id, unit_code, conversion_factor, quantity, unit_price, discount_amount, subtotal, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6423 (class 0 OID 102015)
-- Dependencies: 287
-- Data for Name: sales_orders; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.sales_orders (id, company_id, store_id, customer_id, order_number, order_date, expected_delivery_date, status, subtotal, discount_amount, tax_amount, grand_total, notes, created_by, approved_by, approved_at, pos_transaction_id, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6361 (class 0 OID 25971)
-- Dependencies: 225
-- Data for Name: sessions; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
LrMNJu5xZjzhgDODkIz94AO40kdrWUlguyrVsEbp	01a16777-4238-731f-af87-87381b4f044a	127.0.0.1	Symfony	eyJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6IjAxYTE2Nzc3LTQyMzgtNzMxZi1hZjg3LTg3MzgxYjRmMDQ0YSIsIl90b2tlbiI6Ikp0UkVsaHlnVmdkV0FSTm4zMXVyTXREMGxqNENLZWNRTEJHSmhwdjMiLCJwYXNzd29yZF9oYXNoX3dlYiI6ImU3OGYwYzUwYWJmM2RjNDNlODljYTNhMDM4ZmYyMjQ2YjEzMmQwOWYxZjA3NmM0YjBkMzdjYTEwNGI5ZmNmYWMiLCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0XC9zZXR0aW5nc1wvdXNlcnMiLCJyb3V0ZSI6InNldHRpbmdzLnVzZXJzLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=	1793316419
AnhfLl2AHYew0euNLFSzhffjdViMnaZ5QnsNMaem	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiJzNDZZeHV4VDdDSFE0dXBQNEFQWDZnWUVaZGtDSzBqamV1OTJKRHNVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793320846
Q4vI2CVaQT8cckaHxonisuZ0fnFHcOuLGebpZH5p	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiJ5QUJPQ0tOVEZLNWg2T0JlcTZNbGx6Z3J3aWkzMjNCV0pWeURqQXVkIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDhcL3B1cmNoYXNlc1wvZGVidC1sZWRnZXIifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793322928
3GpQxLnnea9zsXEyXZ1te8hgIeBZ8EV2zuH555BR	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiJ5Z3ZzV1hLTzFpRlZtdzJvRzRVckJhREhnTFcxQzlDcjZYT1REdDZFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793324950
4VvFlPpqdkaTHQi2Vx1t2ejS8nqfZOQeILPSTOaj	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiJ0WFdsc1BzMVQwUVF6SDVheVE0Vkc2ekpZdU5YUUVWUXh5eGZMM3FwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9zdG9yZSIsInJvdXRlIjoicHdhLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=	1793324967
hOeK0zNhu6dIg0ZtQvzZhJjY8yz0twnOFEaGip7S	01a16777-4238-731f-af87-87381b4f044a	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJCZThjUlFhU25DOHV4M1dxM1BXWkg3NXNnRkdxWGdmdHBva3pXYk94IiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOiIwMWExNjc3Ny00MjM4LTczMWYtYWY4Ny04NzM4MWI0ZjA0NGEiLCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDhcL3JlcG9ydHNcL3NhbGVzIiwicm91dGUiOiJyZXBvcnRzLnNhbGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=	1793330676
d6W8KOirJc7hsE6GAyMSWx63FDiGe6Br3brJYeRU	\N	127.0.0.1	Symfony	eyJfdG9rZW4iOiI4TVh2S01JbUtHd200dlZyWEFSdWo1WGM5czQ4SjFYMFVKUUZIRWJNIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793326608
YVANX0BWMVCPWzFyT7HFlHtdIWrFoKu4XLVK0g9J	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiIwUktYdHQxNFh3bGh3eUJoRUJUS285blJTR29tZnRhR1I1VjFzVk00IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793327609
F2BCGzvfydU5ULefPhxaLXKDO6M2oVeTzmS1t30A	01a16777-4238-731f-af87-87381b4f044a	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJ6akxwSnlNMDV5dEM3RTNBWENHOWJlNE5hR2ZnVmx2ZFhzZUZCd1AxIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOiIwMWExNjc3Ny00MjM4LTczMWYtYWY4Ny04NzM4MWI0ZjA0NGEiLCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDhcL3JlcG9ydHNcL3NhbGVzIiwicm91dGUiOiJyZXBvcnRzLnNhbGVzIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwicG9zX2FjdGl2ZV9jYXNoaWVyX2lkIjoiMDFhMTY3NzctNDIzOC03MzFmLWFmODctODczODFiNGYwNDRhIn0=	1793330560
Dqc0fFvaS4J881RlM71b9Eusy3i1Jjco2mTuFg9r	\N	127.0.0.1	Symfony	eyJfdG9rZW4iOiJNS1h6MlE0c3d0Qko1QjRtdmhzejJsMUMyMUFuSjJKc05BU3Yyd0xJIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793326597
ieXiD4pLVhOxuEQEwl61MBGL1jmVB8HcSeyLSlBh	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiJOMjdMMkV0aGNnYWNIZUJEODVjdWdCcnJHV3h2Mm9JRW1SNlIxU1NCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793327143
I1qO3jnAANZoimVvbDY8iZBqwsMWumnzj9NuOht7	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiI2d1ZLUFhJVnM1UzQ4OEFVTmxKQnJVMmhxNGh0akJvcEpVbFoxTGNEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793328418
oJiLdIjQ9imVlbWwld4Ag8VtqbT0CwgSkV73fzUs	01a16777-4238-731f-af87-87381b4f044a	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJkdTJ3NHY1TjhxdElQWVJCUTd1Z28xUXRoMVBkd3k5NGpvQWpjUTNaIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOiIwMWExNjc3Ny00MjM4LTczMWYtYWY4Ny04NzM4MWI0ZjA0NGEiLCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDhcL3BvcyIsInJvdXRlIjoicG9zLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwicG9zX2FjdGl2ZV9jYXNoaWVyX2lkIjoiMDFhMTY3NzctNDIzOC03MzFmLWFmODctODczODFiNGYwNDRhIn0=	1793319171
KFTjFJwDvxPlpTYT0LhCg9rsAnmagJE3aYx322Jt	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiI3ejJhQWJlNDhmSnY4VFFqY3RoWlFnOG1TOW5HNWhjRTR2eEZjNjFOIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDhcL3B1cmNoYXNlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDhcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19	1793322922
9zFLyXdNMwJu08COhjIOr0Xlv8JhNxb6v2HQiMmx	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiJLNlFmTE5ReFdoWm12a1NVOHhvUVVwRXNJRERkNk0zREpLWE4wYWVFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDA4XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==	1793324592
Jqlrhl6GMxd9wFlcxuujxqyv7G35cPccq5VGhIsn	\N	127.0.0.1	Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.6456	eyJfdG9rZW4iOiJFUXBQdTd6VDc0MHhlMmV4ZFloNVFNblg4dnpRaUcwNnZtemh3bWY3IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDhcL3B1cmNoYXNlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDhcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19	1793324951
\.


--
-- TOC entry 6431 (class 0 OID 130710)
-- Dependencies: 295
-- Data for Name: stock_adjustment_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_adjustment_items (id, stock_adjustment_id, product_id, product_unit_id, unit_code, conversion_factor, quantity, base_quantity, unit_cost, total_cost, action, reason, notes, created_at, updated_at) FROM stdin;
01a180ee-827b-7171-b41b-9745d55cb1da	01a180ee-8256-730b-a09a-b0599625273e	01a157f4-18ae-7201-a4e9-f414a835527f	01a157f4-18b4-7108-b036-dbda0ed54654	SAK	1.0000	1.00	1.0000	68000.0000	68000.0000	MOVE_TO_QUARANTINE	Pecah / Rusak Fisik	\N	2026-10-28 05:14:37	2026-10-28 05:14:37
\.


--
-- TOC entry 6430 (class 0 OID 130671)
-- Dependencies: 294
-- Data for Name: stock_adjustments; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_adjustments (id, company_id, store_id, adjustment_number, adjustment_date, adjustment_type, status, total_items, total_cost_value, notes, created_by, approved_by, created_at, updated_at) FROM stdin;
01a180ee-8256-730b-a09a-b0599625273e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	ADJ-20261028-0001	2026-10-28	DAMAGE_WASTE	COMPLETED	1	68000.0000	\N	01a16777-4238-731f-af87-87381b4f044a	01a16777-4238-731f-af87-87381b4f044a	2026-10-28 05:14:37	2026-10-28 05:14:37
\.


--
-- TOC entry 6433 (class 0 OID 130788)
-- Dependencies: 297
-- Data for Name: stock_movement_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_movement_items (id, stock_movement_id, product_id, product_unit_id, unit_code, conversion_factor, quantity, base_quantity, unit_cost, total_cost, notes, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6432 (class 0 OID 130748)
-- Dependencies: 296
-- Data for Name: stock_movements; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_movements (id, company_id, store_id, movement_number, movement_type, movement_date, category_reason, recipient_or_source, status, total_items, total_cost_value, notes, created_by, approved_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6377 (class 0 OID 26337)
-- Dependencies: 241
-- Data for Name: stock_mutations; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_mutations (id, company_id, store_id, product_id, reference_number, mutation_type, quantity, unit_cost, total_cost, qty_before, qty_after, notes, created_by, created_at, updated_at) FROM stdin;
01a157f4-18dc-7386-9697-d42d7a239eb9	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	INIT-SBK-BRS-001	STOCK_OPNAME_ADJUSTMENT	100.0000	68000.0000	6800000.0000	0.0000	100.0000	Saldo awal stok Beras Premium	\N	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-18fb-7265-aef0-8c05cf931e55	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18df-7040-b7aa-8aa4960c7bdd	INIT-SBK-MYK-001	STOCK_OPNAME_ADJUSTMENT	240.0000	15000.0000	3600000.0000	0.0000	240.0000	Saldo awal stok Minyak Goreng	\N	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1914-72f4-bbaf-ee4f8f81f077	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18fd-718a-b85a-3d29f23396ae	INIT-SBK-GLA-001	STOCK_OPNAME_ADJUSTMENT	200.0000	14500.0000	2900000.0000	0.0000	200.0000	Saldo awal stok Gula Pasir	\N	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-192c-7276-94aa-4eefa265d579	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	INIT-SBK-TRG-001	STOCK_OPNAME_ADJUSTMENT	150.0000	11000.0000	1650000.0000	0.0000	150.0000	Saldo awal stok Tepung Terigu	\N	2026-10-20 06:16:17	2026-10-20 06:16:17
01a157f4-1943-7122-9cb9-5377b6758837	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-192e-7383-9cf2-f785ac084ed7	INIT-SBK-TLR-001	STOCK_OPNAME_ADJUSTMENT	80.0000	48000.0000	3840000.0000	0.0000	80.0000	Saldo awal stok Telur Ayam	\N	2026-10-20 06:16:17	2026-10-20 06:16:17
01a15844-7903-7308-92b8-27f2bd06a8f2	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-20261020-LXHNKQ	SALE	-25.0000	68000.0000	1700000.0000	100.0000	75.0000	Penjualan POS Faktur: POS-20261020-LXHNKQ	\N	2026-10-20 07:44:04	2026-10-20 07:44:04
01a15844-7912-7169-a3f6-c9427e6e5401	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	POS-20261020-LXHNKQ	SALE	-12.0000	11000.0000	132000.0000	150.0000	138.0000	Penjualan POS Faktur: POS-20261020-LXHNKQ	\N	2026-10-20 07:44:04	2026-10-20 07:44:04
01a15846-54d8-7297-ad91-747e387576a2	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-20261020-WEWHPE	SALE	-1.0000	68000.0000	68000.0000	75.0000	74.0000	Penjualan POS Faktur: POS-20261020-WEWHPE	\N	2026-10-20 07:46:06	2026-10-20 07:46:06
01a15846-54e4-7133-903d-88e1842c5e56	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	POS-20261020-WEWHPE	SALE	-1.0000	11000.0000	11000.0000	138.0000	137.0000	Penjualan POS Faktur: POS-20261020-WEWHPE	\N	2026-10-20 07:46:06	2026-10-20 07:46:06
01a166f6-ef24-724d-bfcf-4f9c25d9f594	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18df-7040-b7aa-8aa4960c7bdd	POS-20261023-EWEKQK	SALE	-1.0000	15000.0000	15000.0000	240.0000	239.0000	Penjualan POS Faktur: POS-20261023-EWEKQK	\N	2026-10-23 04:13:41	2026-10-23 04:13:41
01a17d1f-120b-736f-989b-71d9f833c3ed	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-20261027-YOHDVR	SALE	-1.0000	68000.0000	68000.0000	100.0000	99.0000	Penjualan POS Faktur: POS-20261027-YOHDVR	\N	2026-10-27 11:29:10	2026-10-27 11:29:10
01a17d1f-1505-71f0-b36f-51f6f276d646	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-20261027-QES3WX	SALE	-1.0000	68000.0000	68000.0000	99.0000	98.0000	Penjualan POS Faktur: POS-20261027-QES3WX	\N	2026-10-27 11:29:11	2026-10-27 11:29:11
01a180eb-bf22-708e-a879-3d18f90e62bd	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-20261028-RTD7MQ	SALE	-1.0000	68000.0000	68000.0000	98.0000	97.0000	Penjualan POS Faktur: POS-20261028-RTD7MQ	\N	2026-10-28 05:11:35	2026-10-28 05:11:35
01a180ee-82a1-726b-b24b-0c9eb041f93c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	ADJ-20261028-0001	QUARANTINE	1.0000	68000.0000	68000.0000	97.0000	96.0000	Masuk karantina: Pecah / Rusak Fisik (ADJ: ADJ-20261028-0001)	01a16777-4238-731f-af87-87381b4f044a	2026-10-28 05:14:37	2026-10-28 05:14:37
01a182e1-bd61-70b5-8ba6-1c23972b4b4c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-20261028-DQWW6U	SALE	-1.0000	68000.0000	68000.0000	97.0000	96.0000	Penjualan POS Faktur: POS-20261028-DQWW6U	\N	2026-10-28 14:19:54	2026-10-28 14:19:54
01a182e1-bd8b-7372-8327-d0e65d6fd55c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18df-7040-b7aa-8aa4960c7bdd	POS-20261028-DQWW6U	SALE	-1.0000	15000.0000	15000.0000	240.0000	239.0000	Penjualan POS Faktur: POS-20261028-DQWW6U	\N	2026-10-28 14:19:54	2026-10-28 14:19:54
01a18335-fe19-7215-b346-17ec2265d0f6	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	QRES-20261028-6250	QUARANTINE_REPLACEMENT	1.0000	68000.0000	68000.0000	95.0000	96.0000	Penyelesaian Karantina (REPLACEMENT)	01a16777-4238-731f-af87-87381b4f044a	2026-10-28 15:51:56	2026-10-28 15:51:56
01a18a94-737f-711b-881a-63f00f150cee	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-STR001-202610-0001	SALE	-5.0000	68000.0000	340000.0000	96.0000	91.0000	Penjualan POS Faktur: POS-STR001-202610-0001	\N	2026-10-30 02:12:27	2026-10-30 02:12:27
01a18a97-d239-71dd-bb8b-2a6d1d6bdc8b	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-STR001-202610-0002	SALE	-5.0000	68000.0000	340000.0000	91.0000	86.0000	Penjualan POS Faktur: POS-STR001-202610-0002	\N	2026-10-30 02:16:07	2026-10-30 02:16:07
01a18aa3-8eb1-73e8-bb9c-7cc3e1f3df7d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-STR001-202610-0003	SALE	-5.0000	68000.0000	340000.0000	86.0000	81.0000	Penjualan POS Faktur: POS-STR001-202610-0003	\N	2026-10-30 02:28:57	2026-10-30 02:28:57
01a18aa3-e60a-72b5-82d1-841fb0d2db5f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-STR001-202610-0004	SALE	-1.0000	68000.0000	68000.0000	81.0000	80.0000	Penjualan POS Faktur: POS-STR001-202610-0004	\N	2026-10-30 02:29:19	2026-10-30 02:29:19
01a18aa7-771a-73be-8376-6aa029ea2dee	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-STR001-202610-0005	SALE	-5.0000	68000.0000	340000.0000	80.0000	75.0000	Penjualan POS Faktur: POS-STR001-202610-0005	\N	2026-10-30 02:33:13	2026-10-30 02:33:13
01a18aab-2660-71cc-8be2-6171f3d4c75c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	POS-STR001-202610-0006	SALE	-5.0000	68000.0000	340000.0000	75.0000	70.0000	Penjualan POS Faktur: POS-STR001-202610-0006	\N	2026-10-30 02:37:14	2026-10-30 02:37:14
01a18ab9-1fc3-7060-b363-bd34a88192e6	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	LPB-20261030-0001	GOODS_RECEIPT	20.0000	69093.7500	1430000.0000	70.0000	90.0000	Penerimaan Fisik LPB LPB-20261030-0001 Surat Jalan DO-SUPP-20261030-999 dari PO PO-20261030-0002	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae8a-718d-8539-c2cb54931a34	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	LPB-20261030-0002	GOODS_RECEIPT	20.0000	69666.6667	1430000.0000	90.0000	110.0000	Penerimaan Fisik LPB LPB-20261030-0002 Surat Jalan DO-SUPP-20261030-999 dari PO PO-20261030-0003	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6dce-70d4-9f5c-c70bf94d8abb	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	LPB-20261030-0003	GOODS_RECEIPT	20.0000	70019.2308	1430000.0000	110.0000	130.0000	Penerimaan Fisik LPB LPB-20261030-0003 Surat Jalan DO-SUPP-20261030-999 dari PO PO-20261030-0004	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-a914-72cd-b0b1-163bcec72ac5	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	LPB-20261030-0004	GOODS_RECEIPT	20.0000	70258.0645	1430000.0000	130.0000	150.0000	Penerimaan Fisik LPB LPB-20261030-0004 Surat Jalan DO-SUPP-20261030-999 dari PO PO-20261030-0005	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-61ff-7094-8e71-8031e897481d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	LPB-20261030-0005	GOODS_RECEIPT	20.0000	70430.5556	1430000.0000	150.0000	170.0000	Penerimaan Fisik LPB LPB-20261030-0005 Surat Jalan DO-SUPP-20261030-999 dari PO PO-20261030-0006	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6407 (class 0 OID 101202)
-- Dependencies: 271
-- Data for Name: stock_opname_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_opname_items (id, stock_opname_id, product_id, system_quantity, actual_quantity, discrepancy_quantity, unit_cost, discrepancy_value, notes, created_at, updated_at) FROM stdin;
01a18093-a699-7068-aa59-305a30063a2d	01a18093-a673-7042-b724-11cc3e73a10c	01a157f4-18ae-7201-a4e9-f414a835527f	98.0000	0.0000	-98.0000	68000.0000	-6664000.00	\N	2026-10-28 03:35:22	2026-10-28 03:35:22
01a18093-a69b-7009-ad0d-f7febadd55b9	01a18093-a673-7042-b724-11cc3e73a10c	01a157f4-18df-7040-b7aa-8aa4960c7bdd	240.0000	0.0000	-240.0000	15000.0000	-3600000.00	\N	2026-10-28 03:35:22	2026-10-28 03:35:22
01a18093-a69d-73ee-9e46-6511033766df	01a18093-a673-7042-b724-11cc3e73a10c	01a157f4-18fd-718a-b85a-3d29f23396ae	200.0000	0.0000	-200.0000	14500.0000	-2900000.00	\N	2026-10-28 03:35:22	2026-10-28 03:35:22
01a18093-a69f-7207-bc4c-ccbbef0ecc47	01a18093-a673-7042-b724-11cc3e73a10c	01a157f4-1917-7246-b4f8-3ac4ee26c8e7	150.0000	0.0000	-150.0000	11000.0000	-1650000.00	\N	2026-10-28 03:35:22	2026-10-28 03:35:22
01a18093-a6a1-718c-88b1-bee08ad4aaa5	01a18093-a673-7042-b724-11cc3e73a10c	01a157f4-192e-7383-9cf2-f785ac084ed7	80.0000	0.0000	-80.0000	48000.0000	-3840000.00	\N	2026-10-28 03:35:22	2026-10-28 03:35:22
\.


--
-- TOC entry 6406 (class 0 OID 101159)
-- Dependencies: 270
-- Data for Name: stock_opnames; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_opnames (id, company_id, store_id, opname_number, opname_date, status, is_blind, total_system_quantity, total_actual_quantity, total_discrepancy_quantity, total_discrepancy_value, notes, created_by, approved_by, approved_at, approval_notes, created_at, updated_at) FROM stdin;
01a18093-a673-7042-b724-11cc3e73a10c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	OPN-20261028-0002	2026-10-28	IN_PROGRESS	t	768.0000	0.0000	0.0000	0.00	\N	01a16777-4238-731f-af87-87381b4f044a	\N	\N	\N	2026-10-28 03:35:22	2026-10-28 03:35:22
\.


--
-- TOC entry 6426 (class 0 OID 130466)
-- Dependencies: 290
-- Data for Name: stock_requirement_allocations; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_requirement_allocations (id, stock_requirement_id, allocation_type, reference_id, reference_item_id, reference_number, idempotency_key, initial_allocated_base_qty, received_base_qty, released_base_qty, outstanding_base_qty, status, allocated_by, notes, created_at, updated_at) FROM stdin;
01a18ab8-f5e6-732f-a5eb-5150c62fa52d	01a18ab8-f4c6-7244-b76e-1d5b0d31b88f	PURCHASE_ORDER	01a18ab8-f530-7088-adb8-76ea346e295d	01a18ab8-f56c-7149-91ec-341e539525a4	PO-20261030-0001	PO-ALLOC-01a18ab8-f56c-7149-91ec-341e539525a4	20.0000	0.0000	0.0000	20.0000	ACTIVE	01a157f4-189b-710f-b186-ff701beaa316	\N	2026-10-30 02:52:19	2026-10-30 02:52:19
01a18ab9-1ee6-734b-981f-140789c84008	01a18ab8-f4c6-7244-b76e-1d5b0d31b88f	PURCHASE_ORDER	01a18ab9-1eb4-7185-b1fe-63b9826a49fe	01a18ab9-1eb8-7060-8b22-929f2837b464	PO-20261030-0002	PO-ALLOC-01a18ab9-1eb8-7060-8b22-929f2837b464	20.0000	0.0000	0.0000	20.0000	COMMITTED	01a157f4-189b-710f-b186-ff701beaa316	\N	2026-10-30 02:52:30	2026-10-30 02:52:30
01a18ab9-ae67-7177-8b5d-d253def3d9cd	01a18ab8-f4c6-7244-b76e-1d5b0d31b88f	PURCHASE_ORDER	01a18ab9-ae4d-72ea-be0a-8770636d06db	01a18ab9-ae52-7064-92ca-771ee6a5a0a7	PO-20261030-0003	PO-ALLOC-01a18ab9-ae52-7064-92ca-771ee6a5a0a7	20.0000	0.0000	0.0000	20.0000	COMMITTED	01a157f4-189b-710f-b186-ff701beaa316	\N	2026-10-30 02:53:07	2026-10-30 02:53:07
01a18aba-6da9-7065-849b-3a4859a07c3b	01a18ab8-f4c6-7244-b76e-1d5b0d31b88f	PURCHASE_ORDER	01a18aba-6d95-70d4-aa62-0208f4c18c00	01a18aba-6d99-7059-b957-2a0acd07214a	PO-20261030-0004	PO-ALLOC-01a18aba-6d99-7059-b957-2a0acd07214a	20.0000	0.0000	0.0000	20.0000	COMMITTED	01a157f4-189b-710f-b186-ff701beaa316	\N	2026-10-30 02:53:56	2026-10-30 02:53:56
01a18aba-a8ec-71a6-ad71-34e28eb303e2	01a18ab8-f4c6-7244-b76e-1d5b0d31b88f	PURCHASE_ORDER	01a18aba-a8c7-73ef-8036-496ebe9e4513	01a18aba-a8cb-7182-866d-c7739719128b	PO-20261030-0005	PO-ALLOC-01a18aba-a8cb-7182-866d-c7739719128b	20.0000	0.0000	0.0000	20.0000	COMMITTED	01a157f4-189b-710f-b186-ff701beaa316	\N	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-61db-70ed-869b-48570adb2d28	01a18ab8-f4c6-7244-b76e-1d5b0d31b88f	PURCHASE_ORDER	01a18abb-61c6-71df-be0c-23ff4526f5cf	01a18abb-61ca-7374-bb50-ce066cbf4ec5	PO-20261030-0006	PO-ALLOC-01a18abb-61ca-7374-bb50-ce066cbf4ec5	20.0000	0.0000	0.0000	20.0000	COMMITTED	01a157f4-189b-710f-b186-ff701beaa316	\N	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6425 (class 0 OID 130424)
-- Dependencies: 289
-- Data for Name: stock_requirements; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_requirements (id, company_id, store_id, product_id, target_stock, available_stock_snapshot, gross_deficit, total_allocated, status, notes, created_by, updated_by, created_at, updated_at) FROM stdin;
01a18ab8-f4c6-7244-b76e-1d5b0d31b88f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-18ae-7201-a4e9-f414a835527f	270.0000	150.0000	120.0000	120.0000	PARTIALLY_ALLOCATED	\N	01a157f4-189b-710f-b186-ff701beaa316	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:52:19	2026-10-30 02:54:58
\.


--
-- TOC entry 6405 (class 0 OID 101120)
-- Dependencies: 269
-- Data for Name: stock_transfer_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_transfer_items (id, stock_transfer_id, product_id, product_unit_id, unit_code, conversion_factor, quantity_sent, base_quantity_sent, quantity_received, base_quantity_received, unit_cost, notes, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6404 (class 0 OID 101073)
-- Dependencies: 268
-- Data for Name: stock_transfers; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stock_transfers (id, company_id, source_store_id, destination_store_id, transfer_number, transfer_date, status, driver_name, vehicle_number, dispatched_at, dispatched_by, received_at, received_by, total_items, total_sent_quantity, total_received_quantity, notes, created_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6370 (class 0 OID 26074)
-- Dependencies: 234
-- Data for Name: stores; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.stores (id, company_id, code, name, type, phone, address, is_active, created_at, updated_at, city, postal_code, pic_name, is_main_store) FROM stdin;
01a157f4-18a4-7317-8fa5-5453c9443640	01a157f4-189f-7352-8b9a-ef0be10c7b1a	STR-001	SIGMA MART CABANG UTAMA	STORE	021-5559876	Jl. Gatot Subroto Kav. 12, Jakarta Selatan	t	2026-10-20 06:16:17	2026-10-29 01:37:08	\N	\N	\N	t
\.


--
-- TOC entry 6438 (class 0 OID 131045)
-- Dependencies: 302
-- Data for Name: supplier_deposits; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.supplier_deposits (id, company_id, store_id, supplier_id, deposit_number, type, amount, balance_before, balance_after, payment_method, bank_account_id, account_id, journal_entry_id, reference_type, reference_id, reference_number, transaction_date, notes, created_by, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6403 (class 0 OID 101052)
-- Dependencies: 267
-- Data for Name: supplier_payment_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.supplier_payment_items (id, supplier_payment_id, purchase_id, amount_paid, created_at, updated_at) FROM stdin;
01a18aba-a9b1-73f8-adf4-772bd6fa756b	01a18aba-a97c-71a9-a8fe-42cd041f2136	01a18aba-a935-73bb-8f38-c56374050990	1400000.00	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-623f-7346-b481-eee9160ee6cb	01a18abb-623c-7258-bb2f-1793e475d21f	01a18abb-621b-71e1-b411-8720d7ad4369	1400000.00	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6402 (class 0 OID 101008)
-- Dependencies: 266
-- Data for Name: supplier_payments; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.supplier_payments (id, company_id, store_id, supplier_id, payment_number, payment_date, payment_method, bank_account_id, total_amount, reference_number, notes, created_by, created_at, updated_at) FROM stdin;
01a18aba-a97c-71a9-a8fe-42cd041f2136	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PAY-20261030-0001	2026-10-30	TRANSFER	01a15a4d-78ff-73f1-9045-d2e9f93711cd	1400000.00	TRF-PAY-1793328851	Pelunasan lunas via Transfer Bank Central Asia	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:11	2026-10-30 02:54:11
01a18abb-623c-7258-bb2f-1793e475d21f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	01a157f4-18a4-7317-8fa5-5453c9443640	01a15a4d-7908-7000-bf21-6593682a64d5	PAY-20261030-0002	2026-10-30	TRANSFER	01a15a4d-78ff-73f1-9045-d2e9f93711cd	1400000.00	TRF-PAY-1793328898	Pelunasan lunas via Transfer Bank Central Asia	01a157f4-189b-710f-b186-ff701beaa316	2026-10-30 02:54:58	2026-10-30 02:54:58
\.


--
-- TOC entry 6390 (class 0 OID 26786)
-- Dependencies: 254
-- Data for Name: suppliers; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.suppliers (id, company_id, code, name, contact_person, phone, email, address, term_of_payment_days, bank_name, bank_account_no, bank_account_holder, notes, is_active, created_at, updated_at, is_pkp, npwp, tax_name, deposit_balance) FROM stdin;
01a15a4d-7908-7000-bf21-6593682a64d5	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SUP-001	PT INDOFOOD CBP SUKSES MAKMUR	Bambang Irawan (Sales Spv)	021-57958822	sales@indofood.co.id	Sudirman Plaza, Indofood Tower Lt. 23, Jakarta	30	BCA	0019283746	PT Indofood CBP Sukses Makmur Tbk	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09	f	\N	\N	0.00
01a15a4d-790a-714b-838c-76f9276dc27f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SUP-002	PT WINGS SURYA DISTRIBUTION	Agus Hartono	021-4600101	order@wingscorp.com	Kawasan Industri Pulogadung Blok B-3, Jakarta Timur	14	Mandiri	1250009876543	PT Wings Surya Distribution	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09	f	\N	\N	0.00
01a15a4d-790c-71f6-984e-d4344eef3214	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SUP-003	CV SUMBER BERAS MAKMUR JAYA	Haji Mansyur	081288990011	sumberberas@gmail.com	Pasar Induk Beras Cipinang Kios AL-09, Jakarta Timur	0	BRI	012301000999501	H. Mansyur / CV Sumber Beras	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09	f	\N	\N	0.00
\.


--
-- TOC entry 6388 (class 0 OID 26711)
-- Dependencies: 252
-- Data for Name: tax_configurations; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.tax_configurations (id, company_id, is_tax_enabled, tax_name, tax_rate_percent, is_tax_inclusive, effective_from, effective_to, created_at, updated_at) FROM stdin;
01a157f4-1976-72a6-87ad-b82ce0db669d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	f	PPN	11.00	f	2026-01-01	\N	2026-10-20 06:16:17	2026-10-20 06:16:17
\.


--
-- TOC entry 6442 (class 0 OID 131243)
-- Dependencies: 306
-- Data for Name: transaction_number_patterns; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.transaction_number_patterns (id, company_id, document_type, module, name, prefix, pattern, digits, reset_cycle, current_sequence, last_period, is_locked, is_active, created_at, updated_at) FROM stdin;
01a18393-bac8-7186-9ea6-bff1f3324de4	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SO	penjualan	Pesanan Penjualan (Sales Order Grosir)	SO	{PREFIX}/{Y}{m}/{SEQ}	4	MONTHLY	0	202610	f	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baca-7056-89c9-80e1c8c5660c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SI	penjualan	Faktur Penjualan Back-Office (Invoice)	INV	{PREFIX}/{Y}{m}/{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-bacc-7187-87bc-053400c34c75	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SR	penjualan	Retur Penjualan / Tukar Barang Konsumen	SR	{PREFIX}/{Y}{m}/{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-bace-7087-b952-529f93658091	01a157f4-189f-7352-8b9a-ef0be10c7b1a	PO	pembelian	Pesanan Pembelian (Purchase Order)	PO	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	f	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-bad2-73eb-8b3f-6411badfc92d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	GRN	pembelian	Penerimaan Barang Supplier (Goods Receipt)	GRN	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-badf-72f1-bad7-c4b6efe46504	01a157f4-189f-7352-8b9a-ef0be10c7b1a	PR	pembelian	Retur Pembelian ke Supplier	PR	{PREFIX}/{Y}{m}/{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baec-73f3-926d-85b2ca3ac618	01a157f4-189f-7352-8b9a-ef0be10c7b1a	OPN	persediaan	Dokumen Stock Opname Fisik Gudang	OPN	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baee-72f8-8081-6c4bf08e5a17	01a157f4-189f-7352-8b9a-ef0be10c7b1a	TR	persediaan	Surat Jalan Transfer Antar Cabang	TR	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baf0-705f-b422-d6cb4c8404cc	01a157f4-189f-7352-8b9a-ef0be10c7b1a	ADJ	persediaan	Penyesuaian Stok Harian & Karantina	ADJ	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baf4-724b-b525-1e968301df2d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	IN	persediaan	Item Masuk Non-Beli (Stock In Hibah/Sample)	IN	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baf6-72ab-b837-15b0e56cb304	01a157f4-189f-7352-8b9a-ef0be10c7b1a	OUT	persediaan	Item Keluar Non-Jual (Stock Out Rusak/Promosi)	OUT	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baf8-703a-9477-4e9122e17159	01a157f4-189f-7352-8b9a-ef0be10c7b1a	JRN	akuntansi	Bukti Jurnal Umum (General Ledger Entry)	JRN	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-bafa-70ff-a1e6-fad38a8452b8	01a157f4-189f-7352-8b9a-ef0be10c7b1a	CAS	akuntansi	Bukti Kas & Bank Masuk / Keluar Operasional	CAS	{PREFIX}-{Y}{m}-{SEQ}	4	MONTHLY	0	202610	t	t	2026-10-28 17:34:19	2026-10-28 17:34:19
01a18393-baa0-725f-9992-56ae8cafcb3d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	POS	penjualan	Struk Penjualan Kasir POS	POS	{PREFIX}-{STORE}-{Y}{m}-{SEQ}	4	MONTHLY	6	202610	f	t	2026-10-28 17:34:19	2026-10-30 02:37:14
\.


--
-- TOC entry 6435 (class 0 OID 130884)
-- Dependencies: 299
-- Data for Name: transfer_request_items; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.transfer_request_items (id, transfer_request_id, product_id, product_unit_id, requested_quantity, approved_quantity, fulfilled_quantity, notes, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6434 (class 0 OID 130824)
-- Dependencies: 298
-- Data for Name: transfer_requests; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.transfer_requests (id, company_id, request_number, requesting_store_id, destination_store_id, request_date, status, total_items, total_requested_quantity, total_approved_quantity, total_fulfilled_quantity, notes, rejection_reason, created_by, approved_by, approved_at, stock_transfer_id, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 6392 (class 0 OID 26837)
-- Dependencies: 256
-- Data for Name: units; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.units (id, company_id, code, name, description, is_active, created_at, updated_at) FROM stdin;
01a15a4d-78f0-7318-b19f-e3a15ff9c231	01a157f4-189f-7352-8b9a-ef0be10c7b1a	PCS	Pieces / Satuan	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78f2-7034-99d3-5393dba27730	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SAK	Sak / Karung	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78f4-7288-afd6-3a19667f40f7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	DUS	Dus / Karton	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78f6-7166-bac8-74327d0bd89e	01a157f4-189f-7352-8b9a-ef0be10c7b1a	KG	Kilogram	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78f7-712c-bc4b-ac684efa23d1	01a157f4-189f-7352-8b9a-ef0be10c7b1a	LITER	Liter	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78f9-71ce-9958-119a388ce9d6	01a157f4-189f-7352-8b9a-ef0be10c7b1a	PACK	Pack / Bungkus	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78fb-720b-be80-b3893687ff62	01a157f4-189f-7352-8b9a-ef0be10c7b1a	RENCENG	Renceng	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
01a15a4d-78fc-707a-91e7-4b818836454c	01a157f4-189f-7352-8b9a-ef0be10c7b1a	BOX	Box / Kotak	\N	t	2026-10-20 17:13:09	2026-10-20 17:13:09
\.


--
-- TOC entry 6441 (class 0 OID 131194)
-- Dependencies: 305
-- Data for Name: user_groups; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.user_groups (id, company_id, code, name, description, permissions, is_system, created_at, updated_at) FROM stdin;
01a18380-013c-7052-ae99-27b2230271df	01a157f4-189f-7352-8b9a-ef0be10c7b1a	MANAGER	Manajer Operasional Toko	Otorisasi Penuh Operasional Toko, Pembelian, Penjualan, Gudang, dan Laporan	["master_item.buka","master_item.baru","master_item.ubah","master_item.hapus","master_supplier.buka","master_supplier.baru","master_supplier.ubah","master_customer.buka","master_customer.baru","master_customer.ubah","master_sales.buka","master_sales.baru","master_sales.ubah","kartu_stok.buka","view_cost_price.buka","view_avg_cost.buka","view_selling_price.buka","purchase_order.buka","purchase_order.baru","purchase_order.ubah","purchase_order.hapus","purchase_receipt.buka","purchase_receipt.baru","purchase_receipt.ubah","purchase_invoice.buka","purchase_invoice.baru","purchase_invoice.ubah","purchase_payment.buka","purchase_payment.baru","purchase_return.buka","purchase_return.baru","purchase_return.ubah","pos_terminal.buka","pos_terminal.baru","sales_order.buka","sales_order.baru","sales_order.ubah","sales_order.hapus","sales_invoice.buka","sales_invoice.baru","sales_invoice.ubah","sales_receivable_payment.buka","sales_receivable_payment.baru","pos_return.buka","pos_return.baru","pos_return.hapus","pos_shift_management.buka","pos_shift_management.baru","pos_shift_discrepancy_resolve.buka","pos_shift_discrepancy_resolve.baru","pos_cash_drawer.buka","pos_reprint_past_shift.buka","pos_credit_payment.buka","pos_sell_below_min_margin.buka","pos_void_cancel_item.buka","pos_void_cancel_item.hapus","stock_in.buka","stock_in.baru","stock_in.ubah","stock_out.buka","stock_out.baru","stock_out.ubah","stock_transfer.buka","stock_transfer.baru","stock_transfer.ubah","stock_opname.buka","stock_opname.baru","stock_opname_approve.buka","batch_expiry_fefo.buka","consignment_management.buka","cash_in.buka","cash_in.baru","cash_out.buka","cash_out.baru","report_sales.buka","report_purchases.buka","report_profit_loss.buka","report_aging.buka","report_stock_valuation.buka","menu_dashboard_charts.buka","menu_view_master.buka","menu_view_purchasing.buka","menu_view_sales.buka","menu_view_inventory.buka","menu_view_accounting.buka","menu_view_reports.buka"]	t	2026-10-28 17:12:46	2026-10-28 17:12:46
01a18380-013f-734a-91f1-c77a5cb84c5a	01a157f4-189f-7352-8b9a-ef0be10c7b1a	SUPERVISOR	Kepala Toko / Supervisor	Supervisi Kasir, Otorisasi Diskon/Void, Cetak Ulang Shift Lampau, dan Opname	["master_item.buka","master_customer.buka","kartu_stok.buka","view_selling_price.buka","pos_terminal.buka","pos_terminal.baru","pos_return.buka","pos_return.baru","pos_shift_management.buka","pos_shift_management.baru","pos_shift_discrepancy_resolve.buka","pos_cash_drawer.buka","pos_reprint_past_shift.buka","pos_credit_payment.buka","pos_void_cancel_item.buka","pos_void_cancel_item.hapus","purchase_receipt.buka","purchase_receipt.baru","stock_in.buka","stock_out.buka","stock_transfer.buka","stock_opname.buka","stock_opname.baru","report_sales.buka","menu_dashboard_charts.buka","menu_view_sales.buka","menu_view_inventory.buka","menu_view_reports.buka"]	t	2026-10-28 17:12:46	2026-10-28 17:12:46
01a18380-0144-708f-96bf-f4273a1ef7a7	01a157f4-189f-7352-8b9a-ef0be10c7b1a	GUDANG	Staf Logistik & Gudang	Penerimaan LPB Supplier, Mutasi Stok, Transfer Cabang, dan Stock Opname Buta.	["master_item.buka","kartu_stok.buka","purchase_receipt.buka","purchase_receipt.baru","stock_in.buka","stock_in.baru","stock_out.buka","stock_out.baru","stock_transfer.buka","stock_transfer.baru","stock_transfer.ubah","stock_opname.buka","stock_opname.baru","batch_expiry_fefo.buka","batch_expiry_fefo.baru","menu_view_inventory.buka"]	t	2026-10-28 17:12:46	2026-10-28 17:12:46
01a18380-0147-70a5-9ae9-8e8c1621e38d	01a157f4-189f-7352-8b9a-ef0be10c7b1a	PURCHASING	Staf Pembelian / Purchasing	Kelola Master Supplier, Draft Purchase Order, dan Riwayat Harga Beli.	["master_item.buka","master_supplier.buka","master_supplier.baru","master_supplier.ubah","purchase_order.buka","purchase_order.baru","purchase_order.ubah","purchase_receipt.buka","purchase_return.buka","purchase_return.baru","purchase_price_history.buka","menu_view_purchasing.buka","report_purchases.buka"]	t	2026-10-28 17:12:46	2026-10-28 17:12:46
01a18380-0148-7011-911d-144844770437	01a157f4-189f-7352-8b9a-ef0be10c7b1a	KEUANGAN	Staf Keuangan & Akuntansi	Kas Masuk, Kas Keluar, Buku Kasir, Pelunasan Hutang/Piutang, Jurnal, dan Laporan.	["master_supplier.buka","master_customer.buka","purchase_invoice.buka","purchase_payment.buka","purchase_payment.baru","sales_invoice.buka","sales_receivable_payment.buka","sales_receivable_payment.baru","cash_in.buka","cash_in.baru","cash_in.ubah","cash_out.buka","cash_out.baru","cash_out.ubah","cash_transfer.buka","cash_transfer.baru","accounting_coa.buka","accounting_journals.buka","accounting_journals.baru","accounting_ledger.buka","report_sales.buka","report_profit_loss.buka","report_balance_sheet.buka","report_aging.buka","report_cash_flow.buka","menu_view_accounting.buka","menu_view_reports.buka"]	t	2026-10-28 17:12:46	2026-10-28 17:12:46
01a18380-0104-73cb-aa9d-f525fc73670a	01a157f4-189f-7352-8b9a-ef0be10c7b1a	ADMINISTRATOR	Administrator / Owner	Akses Penuh Seluruh Modul, HPP, Finansial, dan Pengaturan Sistem	["pos_terminal.buka","pos_terminal.baru","pos_shift_management.buka","pos_shift_management.baru","pos_credit_payment.buka","view_selling_price.buka","menu_direct_pos.buka","menu_view_sales.buka"]	t	2026-10-28 17:12:46	2026-10-29 23:51:57
01a18380-0141-721b-aa23-7dfea3f8ee1f	01a157f4-189f-7352-8b9a-ef0be10c7b1a	KASIR	Kasir Toko (Cashier Standard)	Layar POS Kasir, Buka/Tutup Shift, Cash Drop. HPP & Laba Dirahasiakan.	["view_selling_price.buka","pos_terminal.buka","pos_terminal.baru","pos_return.buka","pos_return.baru","pos_return.hapus","pos_shift_management.buka","pos_shift_management.baru","pos_credit_payment.buka","pos_void_cancel_item.buka","menu_direct_pos.buka","menu_view_sales.buka"]	t	2026-10-28 17:12:46	2026-10-29 23:59:14
\.


--
-- TOC entry 6359 (class 0 OID 25948)
-- Dependencies: 223
-- Data for Name: users; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role, store_id, manager_pin, is_active, valid_from, valid_until, user_group_id) FROM stdin;
01a157f4-177e-732e-aa30-52b7729936fe	Budi Santoso (Kasir 1)	kasir1@sigma.test	\N	$2y$12$lySBowpd4.iIpd8uP70rT.8HMFBT/UUXNqy3iX8LsLkcvYAphc3k.	\N	2026-10-20 06:16:17	2026-10-28 17:12:46	cashier	\N	\N	t	\N	\N	01a18380-0141-721b-aa23-7dfea3f8ee1f
01a157f4-189b-710f-b186-ff701beaa316	Hendro Wijaya (Store Manager)	manager@sigma.test	\N	$2y$12$.jYuxmnu1obuU8BAsM8vyejkJphh2yJC6JMS9sU7KcrWtkxtdN7Rm	\N	2026-10-20 06:16:17	2026-10-28 17:12:46	manager	\N	$2y$12$OvMnxQH1DW0BOjxY4TjSYO0hoTFL5/8wLsWw/Bxua7aS4/j.5oWcm	t	\N	\N	01a18380-013c-7052-ae99-27b2230271df
01a15821-843a-720e-8669-b27ded23f94e	Rian Pratama (Sales Counter)	sales1@sigma.test	\N	$2y$12$aF3kJy32X10j/x/Yj.ewe.sea1qwbs2hWxPVt1w6t97kzAUufPZNm	\N	2026-10-20 07:05:53	2026-10-28 17:12:46	sales	\N	\N	t	\N	\N	01a18380-0141-721b-aa23-7dfea3f8ee1f
01a15821-8537-7151-9b04-c67ec65e63f9	Dewi Lestari (Sales Grosir)	sales2@sigma.test	\N	$2y$12$9Qiroe7uGLa7XMi8rqnOmurlNYv8K4USltDaIrle8.Ilg1BXMbOGe	\N	2026-10-20 07:05:54	2026-10-28 17:12:46	sales	\N	\N	t	\N	\N	01a18380-0141-721b-aa23-7dfea3f8ee1f
01a16777-4238-731f-af87-87381b4f044a	Owner SIGMA	owner@sigma.test	\N	$2y$12$BK351N/3WHZHAmatPRBrWeboQ/AUWEsLexwLQmviBuOrLtdzLWQmG	4OkmJ1wxVoWVfPJplFMooWVh4YgoNQWl4RFKkGiQkLiCMeBBcqnYz9hHTJUN	2026-10-23 06:33:51	2026-10-28 17:12:46	owner	\N	\N	t	\N	\N	01a18380-0104-73cb-aa9d-f525fc73670a
\.


--
-- TOC entry 6378 (class 0 OID 26380)
-- Dependencies: 242
-- Data for Name: vouchers; Type: TABLE DATA; Schema: sigma; Owner: postgres
--

COPY sigma.vouchers (id, company_id, store_id, code, type, amount, min_spend, status, valid_from, valid_until, redeemed_at, redeemed_in_transaction_id, created_at, updated_at, barcode, title, is_printed_on_receipt, trigger_min_spend) FROM stdin;
01a157f4-1948-7081-be7f-c18cc7f5e905	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	HEMAT20K	VALUE	20000.00	50000.00	ISSUED	2026-10-19 06:16:17	2026-11-20 06:16:17	\N	\N	2026-10-20 06:16:17	2026-10-20 06:16:17	\N	\N	f	0.00
01a157f4-194b-72a3-9a4d-4852a5d11843	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	DISKON10	DISCOUNT_PERCENT	10.00	100000.00	ISSUED	2026-10-19 06:16:17	2026-11-20 06:16:17	\N	\N	2026-10-20 06:16:17	2026-10-20 06:16:17	\N	\N	f	0.00
01a1699c-829d-73fe-a5be-aec911cfb883	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	VCH-BADQKKIK	VALUE	100001.00	50000.00	ISSUED	2026-10-23 00:00:00	2026-11-22 00:00:00	\N	\N	2026-10-23 16:33:47	2026-10-23 16:33:47	993948684202	Voucher Belanja Hemat	f	100000.00
01a17d31-a473-7228-9340-7a4aaecf43eb	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	VCH-ALZJ-J4CG	VALUE	100001.00	50000.00	ISSUED	2026-10-27 00:00:00	2026-11-26 00:00:00	\N	\N	2026-10-27 11:49:27	2026-10-27 11:49:27	\N	Voucher Belanja Hemat	f	100000.00
01a17d32-ff76-7091-a75b-bf4e05a42fe0	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	VCH-RKS1-IFM2	VALUE	100000.00	50000.00	ISSUED	2026-10-27 00:00:00	2026-11-26 00:00:00	\N	\N	2026-10-27 11:50:56	2026-10-27 11:50:56	\N	Voucher Belanja Hemat	f	100000.00
01a182f3-193c-71b7-9dd4-0a5d3e831051	01a157f4-189f-7352-8b9a-ef0be10c7b1a	\N	VCH-BP0A-HZU8	VALUE	100000.00	50000.00	ISSUED	2026-10-28 00:00:00	2026-11-27 00:00:00	\N	\N	2026-10-28 14:38:52	2026-10-28 14:38:52	\N	bonus belanja	f	100000.00
\.


--
-- TOC entry 6453 (class 0 OID 0)
-- Dependencies: 231
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: sigma; Owner: postgres
--

SELECT pg_catalog.setval('sigma.failed_jobs_id_seq', 1, false);


--
-- TOC entry 6454 (class 0 OID 0)
-- Dependencies: 228
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: sigma; Owner: postgres
--

SELECT pg_catalog.setval('sigma.jobs_id_seq', 1, false);


--
-- TOC entry 6455 (class 0 OID 0)
-- Dependencies: 221
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: sigma; Owner: postgres
--

SELECT pg_catalog.setval('sigma.migrations_id_seq', 71, true);


--
-- TOC entry 5958 (class 2606 OID 131133)
-- Name: accounting_settings accounting_settings_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.accounting_settings
    ADD CONSTRAINT accounting_settings_pkey PRIMARY KEY (id);


--
-- TOC entry 5668 (class 2606 OID 26707)
-- Name: asset_depreciation_records asset_depreciation_records_fixed_asset_id_period_date_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.asset_depreciation_records
    ADD CONSTRAINT asset_depreciation_records_fixed_asset_id_period_date_unique UNIQUE (fixed_asset_id, period_date);


--
-- TOC entry 5671 (class 2606 OID 26709)
-- Name: asset_depreciation_records asset_depreciation_records_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.asset_depreciation_records
    ADD CONSTRAINT asset_depreciation_records_pkey PRIMARY KEY (id);


--
-- TOC entry 5980 (class 2606 OID 131325)
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- TOC entry 5698 (class 2606 OID 26877)
-- Name: bank_accounts bank_accounts_company_id_bank_code_account_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.bank_accounts
    ADD CONSTRAINT bank_accounts_company_id_bank_code_account_number_unique UNIQUE (company_id, bank_code, account_number);


--
-- TOC entry 5701 (class 2606 OID 26880)
-- Name: bank_accounts bank_accounts_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.bank_accounts
    ADD CONSTRAINT bank_accounts_pkey PRIMARY KEY (id);


--
-- TOC entry 5548 (class 2606 OID 26003)
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- TOC entry 5545 (class 2606 OID 25992)
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- TOC entry 5626 (class 2606 OID 26462)
-- Name: cash_drops cash_drops_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_drops
    ADD CONSTRAINT cash_drops_pkey PRIMARY KEY (id);


--
-- TOC entry 5938 (class 2606 OID 130973)
-- Name: cash_transactions cash_transactions_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_pkey PRIMARY KEY (id);


--
-- TOC entry 5941 (class 2606 OID 130975)
-- Name: cash_transactions cash_transactions_transaction_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_transaction_number_unique UNIQUE (transaction_number);


--
-- TOC entry 5621 (class 2606 OID 26443)
-- Name: cashier_shifts cashier_shifts_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cashier_shifts
    ADD CONSTRAINT cashier_shifts_pkey PRIMARY KEY (id);


--
-- TOC entry 5645 (class 2606 OID 26583)
-- Name: chart_of_accounts chart_of_accounts_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5647 (class 2606 OID 26585)
-- Name: chart_of_accounts chart_of_accounts_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_pkey PRIMARY KEY (id);


--
-- TOC entry 5560 (class 2606 OID 26073)
-- Name: companies companies_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.companies
    ADD CONSTRAINT companies_code_unique UNIQUE (code);


--
-- TOC entry 5562 (class 2606 OID 26071)
-- Name: companies companies_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.companies
    ADD CONSTRAINT companies_pkey PRIMARY KEY (id);


--
-- TOC entry 5832 (class 2606 OID 101674)
-- Name: consignment_receipt_items consignment_receipt_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipt_items
    ADD CONSTRAINT consignment_receipt_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5825 (class 2606 OID 101636)
-- Name: consignment_receipts consignment_receipts_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipts
    ADD CONSTRAINT consignment_receipts_pkey PRIMARY KEY (id);


--
-- TOC entry 5828 (class 2606 OID 101638)
-- Name: consignment_receipts consignment_receipts_receipt_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipts
    ADD CONSTRAINT consignment_receipts_receipt_number_unique UNIQUE (receipt_number);


--
-- TOC entry 5841 (class 2606 OID 101747)
-- Name: consignment_settlement_items consignment_settlement_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlement_items
    ADD CONSTRAINT consignment_settlement_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5836 (class 2606 OID 101710)
-- Name: consignment_settlements consignment_settlements_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlements
    ADD CONSTRAINT consignment_settlements_pkey PRIMARY KEY (id);


--
-- TOC entry 5838 (class 2606 OID 101712)
-- Name: consignment_settlements consignment_settlements_settlement_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlements
    ADD CONSTRAINT consignment_settlements_settlement_number_unique UNIQUE (settlement_number);


--
-- TOC entry 5945 (class 2606 OID 131039)
-- Name: customer_deposits customer_deposits_company_id_deposit_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_company_id_deposit_number_unique UNIQUE (company_id, deposit_number);


--
-- TOC entry 5949 (class 2606 OID 131044)
-- Name: customer_deposits customer_deposits_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_pkey PRIMARY KEY (id);


--
-- TOC entry 5796 (class 2606 OID 101294)
-- Name: customer_payment_items customer_payment_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payment_items
    ADD CONSTRAINT customer_payment_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5790 (class 2606 OID 101272)
-- Name: customer_payments customer_payments_payment_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payments
    ADD CONSTRAINT customer_payments_payment_number_unique UNIQUE (payment_number);


--
-- TOC entry 5792 (class 2606 OID 101270)
-- Name: customer_payments customer_payments_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payments
    ADD CONSTRAINT customer_payments_pkey PRIMARY KEY (id);


--
-- TOC entry 5823 (class 2606 OID 101589)
-- Name: customer_point_mutations customer_point_mutations_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_point_mutations
    ADD CONSTRAINT customer_point_mutations_pkey PRIMARY KEY (id);


--
-- TOC entry 5569 (class 2606 OID 26125)
-- Name: customer_tiers customer_tiers_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_tiers
    ADD CONSTRAINT customer_tiers_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5572 (class 2606 OID 26128)
-- Name: customer_tiers customer_tiers_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_tiers
    ADD CONSTRAINT customer_tiers_pkey PRIMARY KEY (id);


--
-- TOC entry 5677 (class 2606 OID 26771)
-- Name: customers customers_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customers
    ADD CONSTRAINT customers_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5681 (class 2606 OID 26775)
-- Name: customers customers_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customers
    ADD CONSTRAINT customers_pkey PRIMARY KEY (id);


--
-- TOC entry 5556 (class 2606 OID 26051)
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5558 (class 2606 OID 26054)
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- TOC entry 5963 (class 2606 OID 131179)
-- Name: fiscal_year_closings fiscal_year_closings_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fiscal_year_closings
    ADD CONSTRAINT fiscal_year_closings_pkey PRIMARY KEY (id);


--
-- TOC entry 5663 (class 2606 OID 26682)
-- Name: fixed_assets fixed_assets_company_id_asset_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fixed_assets
    ADD CONSTRAINT fixed_assets_company_id_asset_code_unique UNIQUE (company_id, asset_code);


--
-- TOC entry 5665 (class 2606 OID 26684)
-- Name: fixed_assets fixed_assets_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fixed_assets
    ADD CONSTRAINT fixed_assets_pkey PRIMARY KEY (id);


--
-- TOC entry 5898 (class 2606 OID 130656)
-- Name: goods_receipt_items goods_receipt_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipt_items
    ADD CONSTRAINT goods_receipt_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5892 (class 2606 OID 130608)
-- Name: goods_receipts goods_receipts_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipts
    ADD CONSTRAINT goods_receipts_pkey PRIMARY KEY (id);


--
-- TOC entry 5553 (class 2606 OID 26034)
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- TOC entry 5550 (class 2606 OID 26019)
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 5652 (class 2606 OID 26619)
-- Name: journal_entries journal_entries_entry_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.journal_entries
    ADD CONSTRAINT journal_entries_entry_number_unique UNIQUE (entry_number);


--
-- TOC entry 5654 (class 2606 OID 26617)
-- Name: journal_entries journal_entries_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.journal_entries
    ADD CONSTRAINT journal_entries_pkey PRIMARY KEY (id);


--
-- TOC entry 5660 (class 2606 OID 26649)
-- Name: journal_entry_lines journal_entry_lines_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.journal_entry_lines
    ADD CONSTRAINT journal_entry_lines_pkey PRIMARY KEY (id);


--
-- TOC entry 5818 (class 2606 OID 101558)
-- Name: loyalty_programs loyalty_programs_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_programs
    ADD CONSTRAINT loyalty_programs_pkey PRIMARY KEY (id);


--
-- TOC entry 5850 (class 2606 OID 101956)
-- Name: loyalty_reward_redemptions loyalty_reward_redemptions_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_reward_redemptions
    ADD CONSTRAINT loyalty_reward_redemptions_pkey PRIMARY KEY (id);


--
-- TOC entry 5852 (class 2606 OID 101958)
-- Name: loyalty_reward_redemptions loyalty_reward_redemptions_redemption_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_reward_redemptions
    ADD CONSTRAINT loyalty_reward_redemptions_redemption_number_unique UNIQUE (redemption_number);


--
-- TOC entry 5843 (class 2606 OID 101917)
-- Name: loyalty_rewards loyalty_rewards_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_rewards
    ADD CONSTRAINT loyalty_rewards_code_unique UNIQUE (code);


--
-- TOC entry 5846 (class 2606 OID 101915)
-- Name: loyalty_rewards loyalty_rewards_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_rewards
    ADD CONSTRAINT loyalty_rewards_pkey PRIMARY KEY (id);


--
-- TOC entry 5531 (class 2606 OID 25947)
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- TOC entry 5538 (class 2606 OID 25970)
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- TOC entry 5723 (class 2606 OID 100836)
-- Name: pos_return_items pos_return_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_return_items
    ADD CONSTRAINT pos_return_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5715 (class 2606 OID 100793)
-- Name: pos_returns pos_returns_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_returns
    ADD CONSTRAINT pos_returns_pkey PRIMARY KEY (id);


--
-- TOC entry 5719 (class 2606 OID 100797)
-- Name: pos_returns pos_returns_return_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_returns
    ADD CONSTRAINT pos_returns_return_number_unique UNIQUE (return_number);


--
-- TOC entry 5641 (class 2606 OID 26562)
-- Name: pos_transaction_items pos_transaction_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transaction_items
    ADD CONSTRAINT pos_transaction_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5631 (class 2606 OID 26525)
-- Name: pos_transactions pos_transactions_invoice_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_invoice_number_unique UNIQUE (invoice_number);


--
-- TOC entry 5635 (class 2606 OID 26522)
-- Name: pos_transactions pos_transactions_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_pkey PRIMARY KEY (id);


--
-- TOC entry 5803 (class 2606 OID 101374)
-- Name: product_assemblies product_assemblies_assembly_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assemblies
    ADD CONSTRAINT product_assemblies_assembly_number_unique UNIQUE (assembly_number);


--
-- TOC entry 5806 (class 2606 OID 101372)
-- Name: product_assemblies product_assemblies_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assemblies
    ADD CONSTRAINT product_assemblies_pkey PRIMARY KEY (id);


--
-- TOC entry 5810 (class 2606 OID 101408)
-- Name: product_assembly_items product_assembly_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assembly_items
    ADD CONSTRAINT product_assembly_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5588 (class 2606 OID 26245)
-- Name: product_barcodes product_barcodes_company_id_barcode_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_barcodes
    ADD CONSTRAINT product_barcodes_company_id_barcode_unique UNIQUE (company_id, barcode);


--
-- TOC entry 5591 (class 2606 OID 26249)
-- Name: product_barcodes product_barcodes_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_barcodes
    ADD CONSTRAINT product_barcodes_pkey PRIMARY KEY (id);


--
-- TOC entry 5815 (class 2606 OID 101448)
-- Name: product_batches product_batches_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_batches
    ADD CONSTRAINT product_batches_pkey PRIMARY KEY (id);


--
-- TOC entry 5800 (class 2606 OID 101329)
-- Name: product_boms product_boms_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_boms
    ADD CONSTRAINT product_boms_pkey PRIMARY KEY (id);


--
-- TOC entry 5688 (class 2606 OID 26828)
-- Name: product_categories product_categories_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_categories
    ADD CONSTRAINT product_categories_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5691 (class 2606 OID 26831)
-- Name: product_categories product_categories_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_categories
    ADD CONSTRAINT product_categories_pkey PRIMARY KEY (id);


--
-- TOC entry 5597 (class 2606 OID 26294)
-- Name: product_prices product_prices_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_prices
    ADD CONSTRAINT product_prices_pkey PRIMARY KEY (id);


--
-- TOC entry 5601 (class 2606 OID 26332)
-- Name: product_stocks product_stocks_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_stocks
    ADD CONSTRAINT product_stocks_pkey PRIMARY KEY (id);


--
-- TOC entry 5583 (class 2606 OID 26211)
-- Name: product_units product_units_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_units
    ADD CONSTRAINT product_units_pkey PRIMARY KEY (id);


--
-- TOC entry 5585 (class 2606 OID 26208)
-- Name: product_units product_units_product_id_unit_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_units
    ADD CONSTRAINT product_units_product_id_unit_code_unique UNIQUE (product_id, unit_code);


--
-- TOC entry 5575 (class 2606 OID 26168)
-- Name: products products_company_id_sku_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.products
    ADD CONSTRAINT products_company_id_sku_unique UNIQUE (company_id, sku);


--
-- TOC entry 5579 (class 2606 OID 26172)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- TOC entry 5856 (class 2606 OID 102006)
-- Name: promotions promotions_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.promotions
    ADD CONSTRAINT promotions_pkey PRIMARY KEY (id);


--
-- TOC entry 5712 (class 2606 OID 27010)
-- Name: purchase_items purchase_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_items
    ADD CONSTRAINT purchase_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5749 (class 2606 OID 101002)
-- Name: purchase_order_items purchase_order_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_order_items
    ADD CONSTRAINT purchase_order_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5742 (class 2606 OID 100963)
-- Name: purchase_orders purchase_orders_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_orders
    ADD CONSTRAINT purchase_orders_pkey PRIMARY KEY (id);


--
-- TOC entry 5744 (class 2606 OID 100965)
-- Name: purchase_orders purchase_orders_po_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_orders
    ADD CONSTRAINT purchase_orders_po_number_unique UNIQUE (po_number);


--
-- TOC entry 5890 (class 2606 OID 130554)
-- Name: purchase_return_debt_allocations purchase_return_debt_allocations_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_debt_allocations
    ADD CONSTRAINT purchase_return_debt_allocations_pkey PRIMARY KEY (id);


--
-- TOC entry 5736 (class 2606 OID 100921)
-- Name: purchase_return_items purchase_return_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_items
    ADD CONSTRAINT purchase_return_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5728 (class 2606 OID 100881)
-- Name: purchase_returns purchase_returns_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_returns
    ADD CONSTRAINT purchase_returns_pkey PRIMARY KEY (id);


--
-- TOC entry 5731 (class 2606 OID 100883)
-- Name: purchase_returns purchase_returns_return_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_returns
    ADD CONSTRAINT purchase_returns_return_number_unique UNIQUE (return_number);


--
-- TOC entry 5705 (class 2606 OID 26963)
-- Name: purchases purchases_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT purchases_pkey PRIMARY KEY (id);


--
-- TOC entry 5707 (class 2606 OID 26965)
-- Name: purchases purchases_reference_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT purchases_reference_number_unique UNIQUE (reference_number);


--
-- TOC entry 5865 (class 2606 OID 102103)
-- Name: sales_order_items sales_order_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_order_items
    ADD CONSTRAINT sales_order_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5859 (class 2606 OID 102065)
-- Name: sales_orders sales_orders_order_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_orders
    ADD CONSTRAINT sales_orders_order_number_unique UNIQUE (order_number);


--
-- TOC entry 5861 (class 2606 OID 102063)
-- Name: sales_orders sales_orders_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_orders
    ADD CONSTRAINT sales_orders_pkey PRIMARY KEY (id);


--
-- TOC entry 5541 (class 2606 OID 25980)
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- TOC entry 5896 (class 2606 OID 130610)
-- Name: goods_receipts sigma_goods_receipts_receipt_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipts
    ADD CONSTRAINT sigma_goods_receipts_receipt_number_unique UNIQUE (receipt_number);


--
-- TOC entry 5911 (class 2606 OID 130747)
-- Name: stock_adjustment_items stock_adjustment_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustment_items
    ADD CONSTRAINT stock_adjustment_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5901 (class 2606 OID 130705)
-- Name: stock_adjustments stock_adjustments_adjustment_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustments
    ADD CONSTRAINT stock_adjustments_adjustment_number_unique UNIQUE (adjustment_number);


--
-- TOC entry 5907 (class 2606 OID 130703)
-- Name: stock_adjustments stock_adjustments_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustments
    ADD CONSTRAINT stock_adjustments_pkey PRIMARY KEY (id);


--
-- TOC entry 5924 (class 2606 OID 130823)
-- Name: stock_movement_items stock_movement_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movement_items
    ADD CONSTRAINT stock_movement_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5917 (class 2606 OID 130782)
-- Name: stock_movements stock_movements_movement_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movements
    ADD CONSTRAINT stock_movements_movement_number_unique UNIQUE (movement_number);


--
-- TOC entry 5920 (class 2606 OID 130780)
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- TOC entry 5609 (class 2606 OID 26376)
-- Name: stock_mutations stock_mutations_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_mutations
    ADD CONSTRAINT stock_mutations_pkey PRIMARY KEY (id);


--
-- TOC entry 5785 (class 2606 OID 101231)
-- Name: stock_opname_items stock_opname_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_opname_items
    ADD CONSTRAINT stock_opname_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5779 (class 2606 OID 101198)
-- Name: stock_opnames stock_opnames_opname_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_opnames
    ADD CONSTRAINT stock_opnames_opname_number_unique UNIQUE (opname_number);


--
-- TOC entry 5781 (class 2606 OID 101196)
-- Name: stock_opnames stock_opnames_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_opnames
    ADD CONSTRAINT stock_opnames_pkey PRIMARY KEY (id);


--
-- TOC entry 5878 (class 2606 OID 130499)
-- Name: stock_requirement_allocations stock_requirement_allocations_idempotency_key_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirement_allocations
    ADD CONSTRAINT stock_requirement_allocations_idempotency_key_unique UNIQUE (idempotency_key);


--
-- TOC entry 5880 (class 2606 OID 130493)
-- Name: stock_requirement_allocations stock_requirement_allocations_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirement_allocations
    ADD CONSTRAINT stock_requirement_allocations_pkey PRIMARY KEY (id);


--
-- TOC entry 5870 (class 2606 OID 130461)
-- Name: stock_requirements stock_requirements_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirements
    ADD CONSTRAINT stock_requirements_pkey PRIMARY KEY (id);


--
-- TOC entry 5773 (class 2606 OID 101158)
-- Name: stock_transfer_items stock_transfer_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfer_items
    ADD CONSTRAINT stock_transfer_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5766 (class 2606 OID 101113)
-- Name: stock_transfers stock_transfers_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfers
    ADD CONSTRAINT stock_transfers_pkey PRIMARY KEY (id);


--
-- TOC entry 5771 (class 2606 OID 101115)
-- Name: stock_transfers stock_transfers_transfer_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfers
    ADD CONSTRAINT stock_transfers_transfer_number_unique UNIQUE (transfer_number);


--
-- TOC entry 5564 (class 2606 OID 26094)
-- Name: stores stores_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stores
    ADD CONSTRAINT stores_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5567 (class 2606 OID 26097)
-- Name: stores stores_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stores
    ADD CONSTRAINT stores_pkey PRIMARY KEY (id);


--
-- TOC entry 5951 (class 2606 OID 131102)
-- Name: supplier_deposits supplier_deposits_company_id_deposit_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_company_id_deposit_number_unique UNIQUE (company_id, deposit_number);


--
-- TOC entry 5956 (class 2606 OID 131107)
-- Name: supplier_deposits supplier_deposits_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_pkey PRIMARY KEY (id);


--
-- TOC entry 5759 (class 2606 OID 101072)
-- Name: supplier_payment_items supplier_payment_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payment_items
    ADD CONSTRAINT supplier_payment_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5754 (class 2606 OID 101050)
-- Name: supplier_payments supplier_payments_payment_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payments
    ADD CONSTRAINT supplier_payments_payment_number_unique UNIQUE (payment_number);


--
-- TOC entry 5756 (class 2606 OID 101048)
-- Name: supplier_payments supplier_payments_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payments
    ADD CONSTRAINT supplier_payments_pkey PRIMARY KEY (id);


--
-- TOC entry 5683 (class 2606 OID 26806)
-- Name: suppliers suppliers_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.suppliers
    ADD CONSTRAINT suppliers_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5686 (class 2606 OID 26809)
-- Name: suppliers suppliers_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.suppliers
    ADD CONSTRAINT suppliers_pkey PRIMARY KEY (id);


--
-- TOC entry 5675 (class 2606 OID 26733)
-- Name: tax_configurations tax_configurations_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.tax_configurations
    ADD CONSTRAINT tax_configurations_pkey PRIMARY KEY (id);


--
-- TOC entry 5972 (class 2606 OID 131271)
-- Name: transaction_number_patterns transaction_number_patterns_company_id_document_type_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transaction_number_patterns
    ADD CONSTRAINT transaction_number_patterns_company_id_document_type_unique UNIQUE (company_id, document_type);


--
-- TOC entry 5975 (class 2606 OID 131273)
-- Name: transaction_number_patterns transaction_number_patterns_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transaction_number_patterns
    ADD CONSTRAINT transaction_number_patterns_pkey PRIMARY KEY (id);


--
-- TOC entry 5934 (class 2606 OID 130915)
-- Name: transfer_request_items transfer_request_items_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_request_items
    ADD CONSTRAINT transfer_request_items_pkey PRIMARY KEY (id);


--
-- TOC entry 5928 (class 2606 OID 130880)
-- Name: transfer_requests transfer_requests_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_pkey PRIMARY KEY (id);


--
-- TOC entry 5930 (class 2606 OID 130882)
-- Name: transfer_requests transfer_requests_request_number_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_request_number_unique UNIQUE (request_number);


--
-- TOC entry 5961 (class 2606 OID 131130)
-- Name: accounting_settings uk_acc_settings_company_key; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.accounting_settings
    ADD CONSTRAINT uk_acc_settings_company_key UNIQUE (company_id, setting_key);


--
-- TOC entry 5966 (class 2606 OID 131176)
-- Name: fiscal_year_closings uk_fiscal_year_company; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fiscal_year_closings
    ADD CONSTRAINT uk_fiscal_year_company UNIQUE (company_id, fiscal_year);


--
-- TOC entry 5603 (class 2606 OID 26329)
-- Name: product_stocks unique_store_product_stock; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_stocks
    ADD CONSTRAINT unique_store_product_stock UNIQUE (store_id, product_id);


--
-- TOC entry 5693 (class 2606 OID 26855)
-- Name: units units_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.units
    ADD CONSTRAINT units_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5696 (class 2606 OID 26858)
-- Name: units units_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.units
    ADD CONSTRAINT units_pkey PRIMARY KEY (id);


--
-- TOC entry 5886 (class 2606 OID 130491)
-- Name: stock_requirement_allocations uq_alloc_type_ref_item; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirement_allocations
    ADD CONSTRAINT uq_alloc_type_ref_item UNIQUE (allocation_type, reference_item_id);


--
-- TOC entry 5968 (class 2606 OID 131211)
-- Name: user_groups user_groups_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.user_groups
    ADD CONSTRAINT user_groups_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5970 (class 2606 OID 131213)
-- Name: user_groups user_groups_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.user_groups
    ADD CONSTRAINT user_groups_pkey PRIMARY KEY (id);


--
-- TOC entry 5533 (class 2606 OID 25961)
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- TOC entry 5535 (class 2606 OID 25959)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 5614 (class 2606 OID 26407)
-- Name: vouchers vouchers_company_id_code_unique; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.vouchers
    ADD CONSTRAINT vouchers_company_id_code_unique UNIQUE (company_id, code);


--
-- TOC entry 5616 (class 2606 OID 26409)
-- Name: vouchers vouchers_pkey; Type: CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.vouchers
    ADD CONSTRAINT vouchers_pkey PRIMARY KEY (id);


--
-- TOC entry 5808 (class 1259 OID 101406)
-- Name: assembly_items_lookup; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX assembly_items_lookup ON sigma.product_assembly_items USING btree (product_assembly_id, component_product_id);


--
-- TOC entry 5669 (class 1259 OID 26710)
-- Name: asset_depreciation_records_period_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX asset_depreciation_records_period_date_index ON sigma.asset_depreciation_records USING btree (period_date);


--
-- TOC entry 5976 (class 1259 OID 131327)
-- Name: audit_logs_action_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX audit_logs_action_index ON sigma.audit_logs USING btree (action);


--
-- TOC entry 5977 (class 1259 OID 131323)
-- Name: audit_logs_company_id_created_at_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX audit_logs_company_id_created_at_index ON sigma.audit_logs USING btree (company_id, created_at);


--
-- TOC entry 5978 (class 1259 OID 131326)
-- Name: audit_logs_module_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX audit_logs_module_index ON sigma.audit_logs USING btree (module);


--
-- TOC entry 5699 (class 1259 OID 26878)
-- Name: bank_accounts_company_id_is_active_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX bank_accounts_company_id_is_active_index ON sigma.bank_accounts USING btree (company_id, is_active);


--
-- TOC entry 5543 (class 1259 OID 25993)
-- Name: cache_expiration_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cache_expiration_index ON sigma.cache USING btree (expiration);


--
-- TOC entry 5546 (class 1259 OID 26004)
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cache_locks_expiration_index ON sigma.cache_locks USING btree (expiration);


--
-- TOC entry 5936 (class 1259 OID 130971)
-- Name: cash_transactions_company_id_type_transaction_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cash_transactions_company_id_type_transaction_date_index ON sigma.cash_transactions USING btree (company_id, type, transaction_date);


--
-- TOC entry 5939 (class 1259 OID 130977)
-- Name: cash_transactions_transaction_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cash_transactions_transaction_date_index ON sigma.cash_transactions USING btree (transaction_date);


--
-- TOC entry 5942 (class 1259 OID 130976)
-- Name: cash_transactions_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cash_transactions_type_index ON sigma.cash_transactions USING btree (type);


--
-- TOC entry 5619 (class 1259 OID 26444)
-- Name: cashier_shifts_cashier_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cashier_shifts_cashier_id_index ON sigma.cashier_shifts USING btree (cashier_id);


--
-- TOC entry 5622 (class 1259 OID 26445)
-- Name: cashier_shifts_shift_number_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cashier_shifts_shift_number_index ON sigma.cashier_shifts USING btree (shift_number);


--
-- TOC entry 5623 (class 1259 OID 26446)
-- Name: cashier_shifts_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cashier_shifts_status_index ON sigma.cashier_shifts USING btree (status);


--
-- TOC entry 5624 (class 1259 OID 26441)
-- Name: cashier_shifts_store_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX cashier_shifts_store_id_status_index ON sigma.cashier_shifts USING btree (store_id, status);


--
-- TOC entry 5643 (class 1259 OID 26586)
-- Name: chart_of_accounts_code_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX chart_of_accounts_code_index ON sigma.chart_of_accounts USING btree (code);


--
-- TOC entry 5826 (class 1259 OID 101634)
-- Name: consignment_receipts_receipt_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX consignment_receipts_receipt_date_index ON sigma.consignment_receipts USING btree (receipt_date);


--
-- TOC entry 5829 (class 1259 OID 101639)
-- Name: consignment_receipts_received_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX consignment_receipts_received_by_index ON sigma.consignment_receipts USING btree (received_by);


--
-- TOC entry 5830 (class 1259 OID 101633)
-- Name: consignment_receipts_store_id_supplier_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX consignment_receipts_store_id_supplier_id_status_index ON sigma.consignment_receipts USING btree (store_id, supplier_id, status);


--
-- TOC entry 5834 (class 1259 OID 101713)
-- Name: consignment_settlements_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX consignment_settlements_created_by_index ON sigma.consignment_settlements USING btree (created_by);


--
-- TOC entry 5839 (class 1259 OID 101708)
-- Name: consignment_settlements_store_id_supplier_id_payment_status_ind; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX consignment_settlements_store_id_supplier_id_payment_status_ind ON sigma.consignment_settlements USING btree (store_id, supplier_id, payment_status);


--
-- TOC entry 5943 (class 1259 OID 131040)
-- Name: customer_deposits_company_id_customer_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_deposits_company_id_customer_id_index ON sigma.customer_deposits USING btree (company_id, customer_id);


--
-- TOC entry 5946 (class 1259 OID 131042)
-- Name: customer_deposits_company_id_transaction_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_deposits_company_id_transaction_date_index ON sigma.customer_deposits USING btree (company_id, transaction_date);


--
-- TOC entry 5947 (class 1259 OID 131041)
-- Name: customer_deposits_company_id_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_deposits_company_id_type_index ON sigma.customer_deposits USING btree (company_id, type);


--
-- TOC entry 5794 (class 1259 OID 101292)
-- Name: customer_payment_items_customer_payment_id_pos_transaction_id_i; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_payment_items_customer_payment_id_pos_transaction_id_i ON sigma.customer_payment_items USING btree (customer_payment_id, pos_transaction_id);


--
-- TOC entry 5787 (class 1259 OID 101267)
-- Name: customer_payments_company_id_store_id_payment_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_payments_company_id_store_id_payment_date_index ON sigma.customer_payments USING btree (company_id, store_id, payment_date);


--
-- TOC entry 5788 (class 1259 OID 101268)
-- Name: customer_payments_customer_id_payment_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_payments_customer_id_payment_date_index ON sigma.customer_payments USING btree (customer_id, payment_date);


--
-- TOC entry 5793 (class 1259 OID 101273)
-- Name: customer_payments_received_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_payments_received_by_index ON sigma.customer_payments USING btree (received_by);


--
-- TOC entry 5819 (class 1259 OID 101587)
-- Name: customer_point_mutations_company_id_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_point_mutations_company_id_type_index ON sigma.customer_point_mutations USING btree (company_id, type);


--
-- TOC entry 5820 (class 1259 OID 101590)
-- Name: customer_point_mutations_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_point_mutations_created_by_index ON sigma.customer_point_mutations USING btree (created_by);


--
-- TOC entry 5821 (class 1259 OID 101586)
-- Name: customer_point_mutations_customer_id_created_at_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_point_mutations_customer_id_created_at_index ON sigma.customer_point_mutations USING btree (customer_id, created_at);


--
-- TOC entry 5570 (class 1259 OID 26126)
-- Name: customer_tiers_company_id_store_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customer_tiers_company_id_store_id_index ON sigma.customer_tiers USING btree (company_id, store_id);


--
-- TOC entry 5678 (class 1259 OID 26772)
-- Name: customers_company_id_customer_tier_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customers_company_id_customer_tier_id_index ON sigma.customers USING btree (company_id, customer_tier_id);


--
-- TOC entry 5679 (class 1259 OID 26773)
-- Name: customers_company_id_name_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX customers_company_id_name_index ON sigma.customers USING btree (company_id, name);


--
-- TOC entry 5554 (class 1259 OID 26052)
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON sigma.failed_jobs USING btree (connection, queue, failed_at);


--
-- TOC entry 5661 (class 1259 OID 26685)
-- Name: fixed_assets_asset_code_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX fixed_assets_asset_code_index ON sigma.fixed_assets USING btree (asset_code);


--
-- TOC entry 5666 (class 1259 OID 26686)
-- Name: fixed_assets_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX fixed_assets_status_index ON sigma.fixed_assets USING btree (status);


--
-- TOC entry 5959 (class 1259 OID 131131)
-- Name: idx_acc_settings_company_cat; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_acc_settings_company_cat ON sigma.accounting_settings USING btree (company_id, category);


--
-- TOC entry 5874 (class 1259 OID 130489)
-- Name: idx_alloc_type_ref_id; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_alloc_type_ref_id ON sigma.stock_requirement_allocations USING btree (allocation_type, reference_id);


--
-- TOC entry 5811 (class 1259 OID 101446)
-- Name: idx_batch_expiry; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_batch_expiry ON sigma.product_batches USING btree (expired_date);


--
-- TOC entry 5812 (class 1259 OID 101444)
-- Name: idx_batch_fefo_lookup; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_batch_fefo_lookup ON sigma.product_batches USING btree (store_id, product_id, status, expired_date);


--
-- TOC entry 5813 (class 1259 OID 101445)
-- Name: idx_batch_number; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_batch_number ON sigma.product_batches USING btree (batch_number);


--
-- TOC entry 5833 (class 1259 OID 101672)
-- Name: idx_cs_receipt_item_prod; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_cs_receipt_item_prod ON sigma.consignment_receipt_items USING btree (consignment_receipt_id, product_id);


--
-- TOC entry 5964 (class 1259 OID 131177)
-- Name: idx_fiscal_year_company_status; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_fiscal_year_company_status ON sigma.fiscal_year_closings USING btree (company_id, status);


--
-- TOC entry 5893 (class 1259 OID 130605)
-- Name: idx_gr_store_date; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_gr_store_date ON sigma.goods_receipts USING btree (company_id, store_id, receipt_date);


--
-- TOC entry 5894 (class 1259 OID 130606)
-- Name: idx_gr_supplier_status; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_gr_supplier_status ON sigma.goods_receipts USING btree (supplier_id, status);


--
-- TOC entry 5899 (class 1259 OID 130654)
-- Name: idx_gri_receipt_product; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_gri_receipt_product ON sigma.goods_receipt_items USING btree (goods_receipt_id, product_id);


--
-- TOC entry 5887 (class 1259 OID 130551)
-- Name: idx_prda_purchase_return; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_prda_purchase_return ON sigma.purchase_return_debt_allocations USING btree (purchase_id, purchase_return_id);


--
-- TOC entry 5888 (class 1259 OID 130552)
-- Name: idx_prda_return; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_prda_return ON sigma.purchase_return_debt_allocations USING btree (purchase_return_id);


--
-- TOC entry 5593 (class 1259 OID 26291)
-- Name: idx_product_prices_lookup; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_product_prices_lookup ON sigma.product_prices USING btree (company_id, product_id, product_unit_id, customer_tier_id, min_quantity);


--
-- TOC entry 5604 (class 1259 OID 26373)
-- Name: idx_stock_mutations_ledger; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_stock_mutations_ledger ON sigma.stock_mutations USING btree (store_id, product_id, created_at);


--
-- TOC entry 5866 (class 1259 OID 130458)
-- Name: idx_stock_req_company_store_status; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_stock_req_company_store_status ON sigma.stock_requirements USING btree (company_id, store_id, status);


--
-- TOC entry 5867 (class 1259 OID 130459)
-- Name: idx_stock_req_store_product_status; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX idx_stock_req_store_product_status ON sigma.stock_requirements USING btree (store_id, product_id, status);


--
-- TOC entry 5551 (class 1259 OID 26020)
-- Name: jobs_queue_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX jobs_queue_index ON sigma.jobs USING btree (queue);


--
-- TOC entry 5648 (class 1259 OID 26615)
-- Name: journal_entries_company_id_entry_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX journal_entries_company_id_entry_date_index ON sigma.journal_entries USING btree (company_id, entry_date);


--
-- TOC entry 5649 (class 1259 OID 26624)
-- Name: journal_entries_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX journal_entries_created_by_index ON sigma.journal_entries USING btree (created_by);


--
-- TOC entry 5650 (class 1259 OID 26620)
-- Name: journal_entries_entry_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX journal_entries_entry_date_index ON sigma.journal_entries USING btree (entry_date);


--
-- TOC entry 5655 (class 1259 OID 26622)
-- Name: journal_entries_reference_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX journal_entries_reference_id_index ON sigma.journal_entries USING btree (reference_id);


--
-- TOC entry 5656 (class 1259 OID 26623)
-- Name: journal_entries_reference_number_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX journal_entries_reference_number_index ON sigma.journal_entries USING btree (reference_number);


--
-- TOC entry 5657 (class 1259 OID 26621)
-- Name: journal_entries_reference_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX journal_entries_reference_type_index ON sigma.journal_entries USING btree (reference_type);


--
-- TOC entry 5658 (class 1259 OID 26647)
-- Name: journal_entry_lines_journal_entry_id_account_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX journal_entry_lines_journal_entry_id_account_id_index ON sigma.journal_entry_lines USING btree (journal_entry_id, account_id);


--
-- TOC entry 5816 (class 1259 OID 101556)
-- Name: loyalty_programs_company_id_is_active_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX loyalty_programs_company_id_is_active_index ON sigma.loyalty_programs USING btree (company_id, is_active);


--
-- TOC entry 5847 (class 1259 OID 101959)
-- Name: loyalty_reward_redemptions_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX loyalty_reward_redemptions_created_by_index ON sigma.loyalty_reward_redemptions USING btree (created_by);


--
-- TOC entry 5848 (class 1259 OID 101954)
-- Name: loyalty_reward_redemptions_customer_id_redemption_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX loyalty_reward_redemptions_customer_id_redemption_date_index ON sigma.loyalty_reward_redemptions USING btree (customer_id, redemption_date);


--
-- TOC entry 5844 (class 1259 OID 101913)
-- Name: loyalty_rewards_company_id_is_active_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX loyalty_rewards_company_id_is_active_index ON sigma.loyalty_rewards USING btree (company_id, is_active);


--
-- TOC entry 5724 (class 1259 OID 100834)
-- Name: pos_return_items_pos_return_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_return_items_pos_return_id_product_id_index ON sigma.pos_return_items USING btree (pos_return_id, product_id);


--
-- TOC entry 5713 (class 1259 OID 100795)
-- Name: pos_returns_approved_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_returns_approved_by_index ON sigma.pos_returns USING btree (approved_by);


--
-- TOC entry 5716 (class 1259 OID 100791)
-- Name: pos_returns_pos_transaction_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_returns_pos_transaction_id_index ON sigma.pos_returns USING btree (pos_transaction_id);


--
-- TOC entry 5717 (class 1259 OID 100794)
-- Name: pos_returns_processed_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_returns_processed_by_index ON sigma.pos_returns USING btree (processed_by);


--
-- TOC entry 5720 (class 1259 OID 100798)
-- Name: pos_returns_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_returns_status_index ON sigma.pos_returns USING btree (status);


--
-- TOC entry 5721 (class 1259 OID 100790)
-- Name: pos_returns_store_id_return_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_returns_store_id_return_date_index ON sigma.pos_returns USING btree (store_id, return_date);


--
-- TOC entry 5639 (class 1259 OID 101454)
-- Name: pos_transaction_items_batch_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transaction_items_batch_id_index ON sigma.pos_transaction_items USING btree (batch_id);


--
-- TOC entry 5642 (class 1259 OID 26560)
-- Name: pos_transaction_items_pos_transaction_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transaction_items_pos_transaction_id_product_id_index ON sigma.pos_transaction_items USING btree (pos_transaction_id, product_id);


--
-- TOC entry 5627 (class 1259 OID 26523)
-- Name: pos_transactions_cashier_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_cashier_id_index ON sigma.pos_transactions USING btree (cashier_id);


--
-- TOC entry 5628 (class 1259 OID 26520)
-- Name: pos_transactions_cashier_shift_id_created_at_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_cashier_shift_id_created_at_index ON sigma.pos_transactions USING btree (cashier_shift_id, created_at);


--
-- TOC entry 5629 (class 1259 OID 101236)
-- Name: pos_transactions_due_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_due_date_index ON sigma.pos_transactions USING btree (due_date);


--
-- TOC entry 5632 (class 1259 OID 26526)
-- Name: pos_transactions_payment_method_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_payment_method_index ON sigma.pos_transactions USING btree (payment_method);


--
-- TOC entry 5633 (class 1259 OID 101237)
-- Name: pos_transactions_payment_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_payment_status_index ON sigma.pos_transactions USING btree (payment_status);


--
-- TOC entry 5636 (class 1259 OID 26781)
-- Name: pos_transactions_sales_person_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_sales_person_id_index ON sigma.pos_transactions USING btree (sales_person_id);


--
-- TOC entry 5637 (class 1259 OID 26527)
-- Name: pos_transactions_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_status_index ON sigma.pos_transactions USING btree (status);


--
-- TOC entry 5638 (class 1259 OID 26519)
-- Name: pos_transactions_store_id_created_at_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX pos_transactions_store_id_created_at_index ON sigma.pos_transactions USING btree (store_id, created_at);


--
-- TOC entry 5801 (class 1259 OID 101376)
-- Name: product_assemblies_assembled_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_assemblies_assembled_by_index ON sigma.product_assemblies USING btree (assembled_by);


--
-- TOC entry 5804 (class 1259 OID 101370)
-- Name: product_assemblies_company_id_store_id_assembly_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_assemblies_company_id_store_id_assembly_date_index ON sigma.product_assemblies USING btree (company_id, store_id, assembly_date);


--
-- TOC entry 5807 (class 1259 OID 101375)
-- Name: product_assemblies_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_assemblies_type_index ON sigma.product_assemblies USING btree (type);


--
-- TOC entry 5586 (class 1259 OID 26250)
-- Name: product_barcodes_barcode_trgm_idx; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_barcodes_barcode_trgm_idx ON sigma.product_barcodes USING gin (barcode sigma.gin_trgm_ops);


--
-- TOC entry 5589 (class 1259 OID 26247)
-- Name: product_barcodes_company_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_barcodes_company_id_product_id_index ON sigma.product_barcodes USING btree (company_id, product_id);


--
-- TOC entry 5592 (class 1259 OID 26246)
-- Name: product_barcodes_product_id_product_unit_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_barcodes_product_id_product_unit_id_index ON sigma.product_barcodes USING btree (product_id, product_unit_id);


--
-- TOC entry 5797 (class 1259 OID 101326)
-- Name: product_boms_company_id_parent_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_boms_company_id_parent_product_id_index ON sigma.product_boms USING btree (company_id, parent_product_id);


--
-- TOC entry 5798 (class 1259 OID 101327)
-- Name: product_boms_component_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_boms_component_product_id_index ON sigma.product_boms USING btree (component_product_id);


--
-- TOC entry 5689 (class 1259 OID 26829)
-- Name: product_categories_company_id_name_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_categories_company_id_name_index ON sigma.product_categories USING btree (company_id, name);


--
-- TOC entry 5594 (class 1259 OID 26292)
-- Name: product_prices_company_id_store_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_prices_company_id_store_id_index ON sigma.product_prices USING btree (company_id, store_id);


--
-- TOC entry 5595 (class 1259 OID 26295)
-- Name: product_prices_global_unique; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE UNIQUE INDEX product_prices_global_unique ON sigma.product_prices USING btree (company_id, product_id, product_unit_id, customer_tier_id, min_quantity) WHERE (store_id IS NULL);


--
-- TOC entry 5598 (class 1259 OID 26296)
-- Name: product_prices_store_unique; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE UNIQUE INDEX product_prices_store_unique ON sigma.product_prices USING btree (company_id, store_id, product_id, product_unit_id, customer_tier_id, min_quantity) WHERE (store_id IS NOT NULL);


--
-- TOC entry 5599 (class 1259 OID 26330)
-- Name: product_stocks_company_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_stocks_company_id_product_id_index ON sigma.product_stocks USING btree (company_id, product_id);


--
-- TOC entry 5581 (class 1259 OID 26209)
-- Name: product_units_company_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX product_units_company_id_product_id_index ON sigma.product_units USING btree (company_id, product_id);


--
-- TOC entry 5573 (class 1259 OID 26169)
-- Name: products_company_id_name_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX products_company_id_name_index ON sigma.products USING btree (company_id, name);


--
-- TOC entry 5576 (class 1259 OID 26170)
-- Name: products_company_id_store_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX products_company_id_store_id_index ON sigma.products USING btree (company_id, store_id);


--
-- TOC entry 5577 (class 1259 OID 26173)
-- Name: products_name_trgm_idx; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX products_name_trgm_idx ON sigma.products USING gin (name sigma.gin_trgm_ops);


--
-- TOC entry 5580 (class 1259 OID 26174)
-- Name: products_sku_trgm_idx; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX products_sku_trgm_idx ON sigma.products USING gin (sku sigma.gin_trgm_ops);


--
-- TOC entry 5853 (class 1259 OID 102007)
-- Name: promotions_code_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX promotions_code_index ON sigma.promotions USING btree (code);


--
-- TOC entry 5854 (class 1259 OID 102004)
-- Name: promotions_company_id_is_active_start_date_end_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX promotions_company_id_is_active_start_date_end_date_index ON sigma.promotions USING btree (company_id, is_active, start_date, end_date);


--
-- TOC entry 5750 (class 1259 OID 101000)
-- Name: purchase_order_items_purchase_order_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_order_items_purchase_order_id_product_id_index ON sigma.purchase_order_items USING btree (purchase_order_id, product_id);


--
-- TOC entry 5738 (class 1259 OID 100968)
-- Name: purchase_orders_approved_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_orders_approved_by_index ON sigma.purchase_orders USING btree (approved_by);


--
-- TOC entry 5739 (class 1259 OID 100959)
-- Name: purchase_orders_company_id_order_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_orders_company_id_order_date_index ON sigma.purchase_orders USING btree (company_id, order_date);


--
-- TOC entry 5740 (class 1259 OID 100967)
-- Name: purchase_orders_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_orders_created_by_index ON sigma.purchase_orders USING btree (created_by);


--
-- TOC entry 5745 (class 1259 OID 100966)
-- Name: purchase_orders_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_orders_status_index ON sigma.purchase_orders USING btree (status);


--
-- TOC entry 5746 (class 1259 OID 100960)
-- Name: purchase_orders_store_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_orders_store_id_status_index ON sigma.purchase_orders USING btree (store_id, status);


--
-- TOC entry 5747 (class 1259 OID 100961)
-- Name: purchase_orders_supplier_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_orders_supplier_id_status_index ON sigma.purchase_orders USING btree (supplier_id, status);


--
-- TOC entry 5737 (class 1259 OID 100919)
-- Name: purchase_return_items_purchase_return_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_return_items_purchase_return_id_product_id_index ON sigma.purchase_return_items USING btree (purchase_return_id, product_id);


--
-- TOC entry 5725 (class 1259 OID 130513)
-- Name: purchase_returns_approved_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_returns_approved_by_index ON sigma.purchase_returns USING btree (approved_by);


--
-- TOC entry 5726 (class 1259 OID 100885)
-- Name: purchase_returns_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_returns_created_by_index ON sigma.purchase_returns USING btree (created_by);


--
-- TOC entry 5729 (class 1259 OID 130502)
-- Name: purchase_returns_purchase_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_returns_purchase_id_index ON sigma.purchase_returns USING btree (purchase_id);


--
-- TOC entry 5732 (class 1259 OID 130512)
-- Name: purchase_returns_return_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_returns_return_type_index ON sigma.purchase_returns USING btree (return_type);


--
-- TOC entry 5733 (class 1259 OID 100884)
-- Name: purchase_returns_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_returns_status_index ON sigma.purchase_returns USING btree (status);


--
-- TOC entry 5734 (class 1259 OID 100878)
-- Name: purchase_returns_supplier_id_return_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchase_returns_supplier_id_return_date_index ON sigma.purchase_returns USING btree (supplier_id, return_date);


--
-- TOC entry 5702 (class 1259 OID 26959)
-- Name: purchases_company_id_purchase_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchases_company_id_purchase_date_index ON sigma.purchases USING btree (company_id, purchase_date);


--
-- TOC entry 5703 (class 1259 OID 26967)
-- Name: purchases_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchases_created_by_index ON sigma.purchases USING btree (created_by);


--
-- TOC entry 5708 (class 1259 OID 26966)
-- Name: purchases_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchases_status_index ON sigma.purchases USING btree (status);


--
-- TOC entry 5709 (class 1259 OID 26960)
-- Name: purchases_store_id_purchase_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchases_store_id_purchase_date_index ON sigma.purchases USING btree (store_id, purchase_date);


--
-- TOC entry 5710 (class 1259 OID 26961)
-- Name: purchases_supplier_id_purchase_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX purchases_supplier_id_purchase_date_index ON sigma.purchases USING btree (supplier_id, purchase_date);


--
-- TOC entry 5857 (class 1259 OID 102061)
-- Name: sales_orders_company_id_status_order_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX sales_orders_company_id_status_order_date_index ON sigma.sales_orders USING btree (company_id, status, order_date);


--
-- TOC entry 5862 (class 1259 OID 102067)
-- Name: sales_orders_pos_transaction_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX sales_orders_pos_transaction_id_index ON sigma.sales_orders USING btree (pos_transaction_id);


--
-- TOC entry 5863 (class 1259 OID 102066)
-- Name: sales_orders_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX sales_orders_status_index ON sigma.sales_orders USING btree (status);


--
-- TOC entry 5539 (class 1259 OID 25982)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON sigma.sessions USING btree (last_activity);


--
-- TOC entry 5542 (class 1259 OID 25981)
-- Name: sessions_user_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON sigma.sessions USING btree (user_id);


--
-- TOC entry 5902 (class 1259 OID 130706)
-- Name: stock_adjustments_adjustment_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_adjustments_adjustment_type_index ON sigma.stock_adjustments USING btree (adjustment_type);


--
-- TOC entry 5903 (class 1259 OID 130709)
-- Name: stock_adjustments_approved_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_adjustments_approved_by_index ON sigma.stock_adjustments USING btree (approved_by);


--
-- TOC entry 5904 (class 1259 OID 130700)
-- Name: stock_adjustments_company_id_adjustment_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_adjustments_company_id_adjustment_date_index ON sigma.stock_adjustments USING btree (company_id, adjustment_date);


--
-- TOC entry 5905 (class 1259 OID 130708)
-- Name: stock_adjustments_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_adjustments_created_by_index ON sigma.stock_adjustments USING btree (created_by);


--
-- TOC entry 5908 (class 1259 OID 130707)
-- Name: stock_adjustments_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_adjustments_status_index ON sigma.stock_adjustments USING btree (status);


--
-- TOC entry 5909 (class 1259 OID 130701)
-- Name: stock_adjustments_store_id_adjustment_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_adjustments_store_id_adjustment_date_index ON sigma.stock_adjustments USING btree (store_id, adjustment_date);


--
-- TOC entry 5912 (class 1259 OID 130787)
-- Name: stock_movements_approved_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_movements_approved_by_index ON sigma.stock_movements USING btree (approved_by);


--
-- TOC entry 5913 (class 1259 OID 130784)
-- Name: stock_movements_category_reason_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_movements_category_reason_index ON sigma.stock_movements USING btree (category_reason);


--
-- TOC entry 5914 (class 1259 OID 130777)
-- Name: stock_movements_company_id_movement_type_movement_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_movements_company_id_movement_type_movement_date_index ON sigma.stock_movements USING btree (company_id, movement_type, movement_date);


--
-- TOC entry 5915 (class 1259 OID 130786)
-- Name: stock_movements_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_movements_created_by_index ON sigma.stock_movements USING btree (created_by);


--
-- TOC entry 5918 (class 1259 OID 130783)
-- Name: stock_movements_movement_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_movements_movement_type_index ON sigma.stock_movements USING btree (movement_type);


--
-- TOC entry 5921 (class 1259 OID 130785)
-- Name: stock_movements_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_movements_status_index ON sigma.stock_movements USING btree (status);


--
-- TOC entry 5922 (class 1259 OID 130778)
-- Name: stock_movements_store_id_movement_type_movement_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_movements_store_id_movement_type_movement_date_index ON sigma.stock_movements USING btree (store_id, movement_type, movement_date);


--
-- TOC entry 5605 (class 1259 OID 26374)
-- Name: stock_mutations_company_id_created_at_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_mutations_company_id_created_at_index ON sigma.stock_mutations USING btree (company_id, created_at);


--
-- TOC entry 5606 (class 1259 OID 26379)
-- Name: stock_mutations_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_mutations_created_by_index ON sigma.stock_mutations USING btree (created_by);


--
-- TOC entry 5607 (class 1259 OID 26378)
-- Name: stock_mutations_mutation_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_mutations_mutation_type_index ON sigma.stock_mutations USING btree (mutation_type);


--
-- TOC entry 5610 (class 1259 OID 26377)
-- Name: stock_mutations_reference_number_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_mutations_reference_number_index ON sigma.stock_mutations USING btree (reference_number);


--
-- TOC entry 5786 (class 1259 OID 101229)
-- Name: stock_opname_items_stock_opname_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_opname_items_stock_opname_id_product_id_index ON sigma.stock_opname_items USING btree (stock_opname_id, product_id);


--
-- TOC entry 5775 (class 1259 OID 101201)
-- Name: stock_opnames_approved_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_opnames_approved_by_index ON sigma.stock_opnames USING btree (approved_by);


--
-- TOC entry 5776 (class 1259 OID 101193)
-- Name: stock_opnames_company_id_opname_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_opnames_company_id_opname_date_index ON sigma.stock_opnames USING btree (company_id, opname_date);


--
-- TOC entry 5777 (class 1259 OID 101200)
-- Name: stock_opnames_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_opnames_created_by_index ON sigma.stock_opnames USING btree (created_by);


--
-- TOC entry 5782 (class 1259 OID 101199)
-- Name: stock_opnames_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_opnames_status_index ON sigma.stock_opnames USING btree (status);


--
-- TOC entry 5783 (class 1259 OID 101194)
-- Name: stock_opnames_store_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_opnames_store_id_status_index ON sigma.stock_opnames USING btree (store_id, status);


--
-- TOC entry 5875 (class 1259 OID 130501)
-- Name: stock_requirement_allocations_allocated_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirement_allocations_allocated_by_index ON sigma.stock_requirement_allocations USING btree (allocated_by);


--
-- TOC entry 5876 (class 1259 OID 130494)
-- Name: stock_requirement_allocations_allocation_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirement_allocations_allocation_type_index ON sigma.stock_requirement_allocations USING btree (allocation_type);


--
-- TOC entry 5881 (class 1259 OID 130495)
-- Name: stock_requirement_allocations_reference_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirement_allocations_reference_id_index ON sigma.stock_requirement_allocations USING btree (reference_id);


--
-- TOC entry 5882 (class 1259 OID 130496)
-- Name: stock_requirement_allocations_reference_item_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirement_allocations_reference_item_id_index ON sigma.stock_requirement_allocations USING btree (reference_item_id);


--
-- TOC entry 5883 (class 1259 OID 130497)
-- Name: stock_requirement_allocations_reference_number_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirement_allocations_reference_number_index ON sigma.stock_requirement_allocations USING btree (reference_number);


--
-- TOC entry 5884 (class 1259 OID 130500)
-- Name: stock_requirement_allocations_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirement_allocations_status_index ON sigma.stock_requirement_allocations USING btree (status);


--
-- TOC entry 5868 (class 1259 OID 130463)
-- Name: stock_requirements_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirements_created_by_index ON sigma.stock_requirements USING btree (created_by);


--
-- TOC entry 5871 (class 1259 OID 130462)
-- Name: stock_requirements_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirements_status_index ON sigma.stock_requirements USING btree (status);


--
-- TOC entry 5872 (class 1259 OID 130464)
-- Name: stock_requirements_updated_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_requirements_updated_by_index ON sigma.stock_requirements USING btree (updated_by);


--
-- TOC entry 5774 (class 1259 OID 101156)
-- Name: stock_transfer_items_stock_transfer_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfer_items_stock_transfer_id_product_id_index ON sigma.stock_transfer_items USING btree (stock_transfer_id, product_id);


--
-- TOC entry 5761 (class 1259 OID 101109)
-- Name: stock_transfers_company_id_transfer_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfers_company_id_transfer_date_index ON sigma.stock_transfers USING btree (company_id, transfer_date);


--
-- TOC entry 5762 (class 1259 OID 101119)
-- Name: stock_transfers_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfers_created_by_index ON sigma.stock_transfers USING btree (created_by);


--
-- TOC entry 5763 (class 1259 OID 101111)
-- Name: stock_transfers_destination_store_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfers_destination_store_id_status_index ON sigma.stock_transfers USING btree (destination_store_id, status);


--
-- TOC entry 5764 (class 1259 OID 101117)
-- Name: stock_transfers_dispatched_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfers_dispatched_by_index ON sigma.stock_transfers USING btree (dispatched_by);


--
-- TOC entry 5767 (class 1259 OID 101118)
-- Name: stock_transfers_received_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfers_received_by_index ON sigma.stock_transfers USING btree (received_by);


--
-- TOC entry 5768 (class 1259 OID 101110)
-- Name: stock_transfers_source_store_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfers_source_store_id_status_index ON sigma.stock_transfers USING btree (source_store_id, status);


--
-- TOC entry 5769 (class 1259 OID 101116)
-- Name: stock_transfers_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stock_transfers_status_index ON sigma.stock_transfers USING btree (status);


--
-- TOC entry 5565 (class 1259 OID 26095)
-- Name: stores_company_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX stores_company_id_index ON sigma.stores USING btree (company_id);


--
-- TOC entry 5952 (class 1259 OID 131103)
-- Name: supplier_deposits_company_id_supplier_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX supplier_deposits_company_id_supplier_id_index ON sigma.supplier_deposits USING btree (company_id, supplier_id);


--
-- TOC entry 5953 (class 1259 OID 131105)
-- Name: supplier_deposits_company_id_transaction_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX supplier_deposits_company_id_transaction_date_index ON sigma.supplier_deposits USING btree (company_id, transaction_date);


--
-- TOC entry 5954 (class 1259 OID 131104)
-- Name: supplier_deposits_company_id_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX supplier_deposits_company_id_type_index ON sigma.supplier_deposits USING btree (company_id, type);


--
-- TOC entry 5760 (class 1259 OID 101070)
-- Name: supplier_payment_items_supplier_payment_id_purchase_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX supplier_payment_items_supplier_payment_id_purchase_id_index ON sigma.supplier_payment_items USING btree (supplier_payment_id, purchase_id);


--
-- TOC entry 5751 (class 1259 OID 101045)
-- Name: supplier_payments_company_id_payment_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX supplier_payments_company_id_payment_date_index ON sigma.supplier_payments USING btree (company_id, payment_date);


--
-- TOC entry 5752 (class 1259 OID 101051)
-- Name: supplier_payments_created_by_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX supplier_payments_created_by_index ON sigma.supplier_payments USING btree (created_by);


--
-- TOC entry 5757 (class 1259 OID 101046)
-- Name: supplier_payments_supplier_id_payment_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX supplier_payments_supplier_id_payment_date_index ON sigma.supplier_payments USING btree (supplier_id, payment_date);


--
-- TOC entry 5684 (class 1259 OID 26807)
-- Name: suppliers_company_id_name_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX suppliers_company_id_name_index ON sigma.suppliers USING btree (company_id, name);


--
-- TOC entry 5672 (class 1259 OID 26731)
-- Name: tax_configurations_company_id_is_tax_enabled_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX tax_configurations_company_id_is_tax_enabled_index ON sigma.tax_configurations USING btree (company_id, is_tax_enabled);


--
-- TOC entry 5673 (class 1259 OID 26734)
-- Name: tax_configurations_effective_from_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX tax_configurations_effective_from_index ON sigma.tax_configurations USING btree (effective_from);


--
-- TOC entry 5973 (class 1259 OID 131274)
-- Name: transaction_number_patterns_document_type_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX transaction_number_patterns_document_type_index ON sigma.transaction_number_patterns USING btree (document_type);


--
-- TOC entry 5935 (class 1259 OID 130913)
-- Name: transfer_request_items_transfer_request_id_product_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX transfer_request_items_transfer_request_id_product_id_index ON sigma.transfer_request_items USING btree (transfer_request_id, product_id);


--
-- TOC entry 5925 (class 1259 OID 130876)
-- Name: transfer_requests_company_id_status_request_date_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX transfer_requests_company_id_status_request_date_index ON sigma.transfer_requests USING btree (company_id, status, request_date);


--
-- TOC entry 5926 (class 1259 OID 130878)
-- Name: transfer_requests_destination_store_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX transfer_requests_destination_store_id_status_index ON sigma.transfer_requests USING btree (destination_store_id, status);


--
-- TOC entry 5931 (class 1259 OID 130877)
-- Name: transfer_requests_requesting_store_id_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX transfer_requests_requesting_store_id_status_index ON sigma.transfer_requests USING btree (requesting_store_id, status);


--
-- TOC entry 5932 (class 1259 OID 130883)
-- Name: transfer_requests_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX transfer_requests_status_index ON sigma.transfer_requests USING btree (status);


--
-- TOC entry 5694 (class 1259 OID 26856)
-- Name: units_company_id_name_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX units_company_id_name_index ON sigma.units USING btree (company_id, name);


--
-- TOC entry 5873 (class 1259 OID 130465)
-- Name: uq_single_active_stock_requirement; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE UNIQUE INDEX uq_single_active_stock_requirement ON sigma.stock_requirements USING btree (company_id, store_id, product_id) WHERE ((status)::text = ANY ((ARRAY['OPEN'::character varying, 'PARTIALLY_ALLOCATED'::character varying, 'AWAITING_SUPPLY'::character varying, 'OVER_COMMITTED'::character varying])::text[]));


--
-- TOC entry 5536 (class 1259 OID 100748)
-- Name: users_role_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX users_role_index ON sigma.users USING btree (role);


--
-- TOC entry 5611 (class 1259 OID 102012)
-- Name: vouchers_barcode_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX vouchers_barcode_index ON sigma.vouchers USING btree (barcode);


--
-- TOC entry 5612 (class 1259 OID 26410)
-- Name: vouchers_code_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX vouchers_code_index ON sigma.vouchers USING btree (code);


--
-- TOC entry 5617 (class 1259 OID 26412)
-- Name: vouchers_redeemed_in_transaction_id_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX vouchers_redeemed_in_transaction_id_index ON sigma.vouchers USING btree (redeemed_in_transaction_id);


--
-- TOC entry 5618 (class 1259 OID 26411)
-- Name: vouchers_status_index; Type: INDEX; Schema: sigma; Owner: postgres
--

CREATE INDEX vouchers_status_index ON sigma.vouchers USING btree (status);


--
-- TOC entry 6200 (class 2606 OID 131124)
-- Name: accounting_settings accounting_settings_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.accounting_settings
    ADD CONSTRAINT accounting_settings_account_id_foreign FOREIGN KEY (account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE CASCADE;


--
-- TOC entry 6201 (class 2606 OID 131119)
-- Name: accounting_settings accounting_settings_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.accounting_settings
    ADD CONSTRAINT accounting_settings_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6031 (class 2606 OID 26696)
-- Name: asset_depreciation_records asset_depreciation_records_fixed_asset_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.asset_depreciation_records
    ADD CONSTRAINT asset_depreciation_records_fixed_asset_id_foreign FOREIGN KEY (fixed_asset_id) REFERENCES sigma.fixed_assets(id) ON DELETE CASCADE;


--
-- TOC entry 6032 (class 2606 OID 26701)
-- Name: asset_depreciation_records asset_depreciation_records_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.asset_depreciation_records
    ADD CONSTRAINT asset_depreciation_records_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES sigma.journal_entries(id) ON DELETE SET NULL;


--
-- TOC entry 6208 (class 2606 OID 131313)
-- Name: audit_logs audit_logs_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.audit_logs
    ADD CONSTRAINT audit_logs_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6209 (class 2606 OID 131318)
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6040 (class 2606 OID 26871)
-- Name: bank_accounts bank_accounts_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.bank_accounts
    ADD CONSTRAINT bank_accounts_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6013 (class 2606 OID 26456)
-- Name: cash_drops cash_drops_cashier_shift_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_drops
    ADD CONSTRAINT cash_drops_cashier_shift_id_foreign FOREIGN KEY (cashier_shift_id) REFERENCES sigma.cashier_shifts(id) ON DELETE CASCADE;


--
-- TOC entry 6178 (class 2606 OID 130956)
-- Name: cash_transactions cash_transactions_admin_fee_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_admin_fee_account_id_foreign FOREIGN KEY (admin_fee_account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6179 (class 2606 OID 130951)
-- Name: cash_transactions cash_transactions_category_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_category_account_id_foreign FOREIGN KEY (category_account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6180 (class 2606 OID 130931)
-- Name: cash_transactions cash_transactions_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6181 (class 2606 OID 130966)
-- Name: cash_transactions cash_transactions_created_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_created_by_foreign FOREIGN KEY (created_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6182 (class 2606 OID 130946)
-- Name: cash_transactions cash_transactions_destination_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_destination_account_id_foreign FOREIGN KEY (destination_account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6183 (class 2606 OID 130961)
-- Name: cash_transactions cash_transactions_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES sigma.journal_entries(id) ON DELETE SET NULL;


--
-- TOC entry 6184 (class 2606 OID 130941)
-- Name: cash_transactions cash_transactions_source_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_source_account_id_foreign FOREIGN KEY (source_account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6185 (class 2606 OID 130936)
-- Name: cash_transactions cash_transactions_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cash_transactions
    ADD CONSTRAINT cash_transactions_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6011 (class 2606 OID 26431)
-- Name: cashier_shifts cashier_shifts_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cashier_shifts
    ADD CONSTRAINT cashier_shifts_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6012 (class 2606 OID 26436)
-- Name: cashier_shifts cashier_shifts_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.cashier_shifts
    ADD CONSTRAINT cashier_shifts_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6024 (class 2606 OID 26577)
-- Name: chart_of_accounts chart_of_accounts_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6114 (class 2606 OID 101657)
-- Name: consignment_receipt_items consignment_receipt_items_consignment_receipt_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipt_items
    ADD CONSTRAINT consignment_receipt_items_consignment_receipt_id_foreign FOREIGN KEY (consignment_receipt_id) REFERENCES sigma.consignment_receipts(id) ON DELETE CASCADE;


--
-- TOC entry 6115 (class 2606 OID 101662)
-- Name: consignment_receipt_items consignment_receipt_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipt_items
    ADD CONSTRAINT consignment_receipt_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6116 (class 2606 OID 101667)
-- Name: consignment_receipt_items consignment_receipt_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipt_items
    ADD CONSTRAINT consignment_receipt_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6111 (class 2606 OID 101618)
-- Name: consignment_receipts consignment_receipts_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipts
    ADD CONSTRAINT consignment_receipts_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6112 (class 2606 OID 101623)
-- Name: consignment_receipts consignment_receipts_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipts
    ADD CONSTRAINT consignment_receipts_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6113 (class 2606 OID 101628)
-- Name: consignment_receipts consignment_receipts_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_receipts
    ADD CONSTRAINT consignment_receipts_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6120 (class 2606 OID 101731)
-- Name: consignment_settlement_items consignment_settlement_items_consignment_receipt_item_id_foreig; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlement_items
    ADD CONSTRAINT consignment_settlement_items_consignment_receipt_item_id_foreig FOREIGN KEY (consignment_receipt_item_id) REFERENCES sigma.consignment_receipt_items(id) ON DELETE CASCADE;


--
-- TOC entry 6121 (class 2606 OID 101726)
-- Name: consignment_settlement_items consignment_settlement_items_consignment_settlement_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlement_items
    ADD CONSTRAINT consignment_settlement_items_consignment_settlement_id_foreign FOREIGN KEY (consignment_settlement_id) REFERENCES sigma.consignment_settlements(id) ON DELETE CASCADE;


--
-- TOC entry 6122 (class 2606 OID 101736)
-- Name: consignment_settlement_items consignment_settlement_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlement_items
    ADD CONSTRAINT consignment_settlement_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6123 (class 2606 OID 101741)
-- Name: consignment_settlement_items consignment_settlement_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlement_items
    ADD CONSTRAINT consignment_settlement_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6117 (class 2606 OID 101693)
-- Name: consignment_settlements consignment_settlements_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlements
    ADD CONSTRAINT consignment_settlements_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6118 (class 2606 OID 101698)
-- Name: consignment_settlements consignment_settlements_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlements
    ADD CONSTRAINT consignment_settlements_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6119 (class 2606 OID 101703)
-- Name: consignment_settlements consignment_settlements_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.consignment_settlements
    ADD CONSTRAINT consignment_settlements_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6186 (class 2606 OID 131023)
-- Name: customer_deposits customer_deposits_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_account_id_foreign FOREIGN KEY (account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6187 (class 2606 OID 131018)
-- Name: customer_deposits customer_deposits_bank_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_bank_account_id_foreign FOREIGN KEY (bank_account_id) REFERENCES sigma.bank_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6188 (class 2606 OID 131003)
-- Name: customer_deposits customer_deposits_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6189 (class 2606 OID 131033)
-- Name: customer_deposits customer_deposits_created_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_created_by_foreign FOREIGN KEY (created_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6190 (class 2606 OID 131013)
-- Name: customer_deposits customer_deposits_customer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES sigma.customers(id) ON DELETE CASCADE;


--
-- TOC entry 6191 (class 2606 OID 131028)
-- Name: customer_deposits customer_deposits_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES sigma.journal_entries(id) ON DELETE SET NULL;


--
-- TOC entry 6192 (class 2606 OID 131008)
-- Name: customer_deposits customer_deposits_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_deposits
    ADD CONSTRAINT customer_deposits_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6091 (class 2606 OID 101282)
-- Name: customer_payment_items customer_payment_items_customer_payment_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payment_items
    ADD CONSTRAINT customer_payment_items_customer_payment_id_foreign FOREIGN KEY (customer_payment_id) REFERENCES sigma.customer_payments(id) ON DELETE CASCADE;


--
-- TOC entry 6092 (class 2606 OID 101287)
-- Name: customer_payment_items customer_payment_items_pos_transaction_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payment_items
    ADD CONSTRAINT customer_payment_items_pos_transaction_id_foreign FOREIGN KEY (pos_transaction_id) REFERENCES sigma.pos_transactions(id) ON DELETE CASCADE;


--
-- TOC entry 6088 (class 2606 OID 101252)
-- Name: customer_payments customer_payments_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payments
    ADD CONSTRAINT customer_payments_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6089 (class 2606 OID 101262)
-- Name: customer_payments customer_payments_customer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payments
    ADD CONSTRAINT customer_payments_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES sigma.customers(id) ON DELETE CASCADE;


--
-- TOC entry 6090 (class 2606 OID 101257)
-- Name: customer_payments customer_payments_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_payments
    ADD CONSTRAINT customer_payments_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6108 (class 2606 OID 101571)
-- Name: customer_point_mutations customer_point_mutations_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_point_mutations
    ADD CONSTRAINT customer_point_mutations_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6109 (class 2606 OID 101576)
-- Name: customer_point_mutations customer_point_mutations_customer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_point_mutations
    ADD CONSTRAINT customer_point_mutations_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES sigma.customers(id) ON DELETE CASCADE;


--
-- TOC entry 6110 (class 2606 OID 101581)
-- Name: customer_point_mutations customer_point_mutations_pos_transaction_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_point_mutations
    ADD CONSTRAINT customer_point_mutations_pos_transaction_id_foreign FOREIGN KEY (pos_transaction_id) REFERENCES sigma.pos_transactions(id) ON DELETE SET NULL;


--
-- TOC entry 5984 (class 2606 OID 26114)
-- Name: customer_tiers customer_tiers_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_tiers
    ADD CONSTRAINT customer_tiers_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 5985 (class 2606 OID 26119)
-- Name: customer_tiers customer_tiers_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customer_tiers
    ADD CONSTRAINT customer_tiers_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6034 (class 2606 OID 26755)
-- Name: customers customers_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customers
    ADD CONSTRAINT customers_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6035 (class 2606 OID 26765)
-- Name: customers customers_customer_tier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customers
    ADD CONSTRAINT customers_customer_tier_id_foreign FOREIGN KEY (customer_tier_id) REFERENCES sigma.customer_tiers(id) ON DELETE CASCADE;


--
-- TOC entry 6036 (class 2606 OID 26760)
-- Name: customers customers_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.customers
    ADD CONSTRAINT customers_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6202 (class 2606 OID 131170)
-- Name: fiscal_year_closings fiscal_year_closings_closed_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fiscal_year_closings
    ADD CONSTRAINT fiscal_year_closings_closed_by_foreign FOREIGN KEY (closed_by) REFERENCES sigma.users(id) ON DELETE CASCADE;


--
-- TOC entry 6203 (class 2606 OID 131165)
-- Name: fiscal_year_closings fiscal_year_closings_closing_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fiscal_year_closings
    ADD CONSTRAINT fiscal_year_closings_closing_journal_entry_id_foreign FOREIGN KEY (closing_journal_entry_id) REFERENCES sigma.journal_entries(id) ON DELETE SET NULL;


--
-- TOC entry 6204 (class 2606 OID 131155)
-- Name: fiscal_year_closings fiscal_year_closings_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fiscal_year_closings
    ADD CONSTRAINT fiscal_year_closings_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6205 (class 2606 OID 131160)
-- Name: fiscal_year_closings fiscal_year_closings_retained_earnings_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fiscal_year_closings
    ADD CONSTRAINT fiscal_year_closings_retained_earnings_account_id_foreign FOREIGN KEY (retained_earnings_account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE CASCADE;


--
-- TOC entry 6029 (class 2606 OID 26671)
-- Name: fixed_assets fixed_assets_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fixed_assets
    ADD CONSTRAINT fixed_assets_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6030 (class 2606 OID 26676)
-- Name: fixed_assets fixed_assets_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.fixed_assets
    ADD CONSTRAINT fixed_assets_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6025 (class 2606 OID 26605)
-- Name: journal_entries journal_entries_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.journal_entries
    ADD CONSTRAINT journal_entries_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6026 (class 2606 OID 26610)
-- Name: journal_entries journal_entries_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.journal_entries
    ADD CONSTRAINT journal_entries_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6027 (class 2606 OID 26642)
-- Name: journal_entry_lines journal_entry_lines_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.journal_entry_lines
    ADD CONSTRAINT journal_entry_lines_account_id_foreign FOREIGN KEY (account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE CASCADE;


--
-- TOC entry 6028 (class 2606 OID 26637)
-- Name: journal_entry_lines journal_entry_lines_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.journal_entry_lines
    ADD CONSTRAINT journal_entry_lines_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES sigma.journal_entries(id) ON DELETE CASCADE;


--
-- TOC entry 6107 (class 2606 OID 101551)
-- Name: loyalty_programs loyalty_programs_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_programs
    ADD CONSTRAINT loyalty_programs_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6126 (class 2606 OID 101934)
-- Name: loyalty_reward_redemptions loyalty_reward_redemptions_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_reward_redemptions
    ADD CONSTRAINT loyalty_reward_redemptions_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6127 (class 2606 OID 101944)
-- Name: loyalty_reward_redemptions loyalty_reward_redemptions_customer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_reward_redemptions
    ADD CONSTRAINT loyalty_reward_redemptions_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES sigma.customers(id) ON DELETE CASCADE;


--
-- TOC entry 6128 (class 2606 OID 101949)
-- Name: loyalty_reward_redemptions loyalty_reward_redemptions_loyalty_reward_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_reward_redemptions
    ADD CONSTRAINT loyalty_reward_redemptions_loyalty_reward_id_foreign FOREIGN KEY (loyalty_reward_id) REFERENCES sigma.loyalty_rewards(id) ON DELETE CASCADE;


--
-- TOC entry 6129 (class 2606 OID 101939)
-- Name: loyalty_reward_redemptions loyalty_reward_redemptions_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_reward_redemptions
    ADD CONSTRAINT loyalty_reward_redemptions_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6124 (class 2606 OID 101903)
-- Name: loyalty_rewards loyalty_rewards_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_rewards
    ADD CONSTRAINT loyalty_rewards_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6125 (class 2606 OID 101908)
-- Name: loyalty_rewards loyalty_rewards_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.loyalty_rewards
    ADD CONSTRAINT loyalty_rewards_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE SET NULL;


--
-- TOC entry 6054 (class 2606 OID 100814)
-- Name: pos_return_items pos_return_items_pos_return_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_return_items
    ADD CONSTRAINT pos_return_items_pos_return_id_foreign FOREIGN KEY (pos_return_id) REFERENCES sigma.pos_returns(id) ON DELETE CASCADE;


--
-- TOC entry 6055 (class 2606 OID 100819)
-- Name: pos_return_items pos_return_items_pos_transaction_item_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_return_items
    ADD CONSTRAINT pos_return_items_pos_transaction_item_id_foreign FOREIGN KEY (pos_transaction_item_id) REFERENCES sigma.pos_transaction_items(id) ON DELETE CASCADE;


--
-- TOC entry 6056 (class 2606 OID 100824)
-- Name: pos_return_items pos_return_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_return_items
    ADD CONSTRAINT pos_return_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6057 (class 2606 OID 100829)
-- Name: pos_return_items pos_return_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_return_items
    ADD CONSTRAINT pos_return_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6050 (class 2606 OID 100785)
-- Name: pos_returns pos_returns_cashier_shift_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_returns
    ADD CONSTRAINT pos_returns_cashier_shift_id_foreign FOREIGN KEY (cashier_shift_id) REFERENCES sigma.cashier_shifts(id) ON DELETE SET NULL;


--
-- TOC entry 6051 (class 2606 OID 100770)
-- Name: pos_returns pos_returns_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_returns
    ADD CONSTRAINT pos_returns_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6052 (class 2606 OID 100780)
-- Name: pos_returns pos_returns_pos_transaction_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_returns
    ADD CONSTRAINT pos_returns_pos_transaction_id_foreign FOREIGN KEY (pos_transaction_id) REFERENCES sigma.pos_transactions(id) ON DELETE CASCADE;


--
-- TOC entry 6053 (class 2606 OID 100775)
-- Name: pos_returns pos_returns_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_returns
    ADD CONSTRAINT pos_returns_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6020 (class 2606 OID 101449)
-- Name: pos_transaction_items pos_transaction_items_batch_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transaction_items
    ADD CONSTRAINT pos_transaction_items_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES sigma.product_batches(id) ON DELETE SET NULL;


--
-- TOC entry 6021 (class 2606 OID 26545)
-- Name: pos_transaction_items pos_transaction_items_pos_transaction_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transaction_items
    ADD CONSTRAINT pos_transaction_items_pos_transaction_id_foreign FOREIGN KEY (pos_transaction_id) REFERENCES sigma.pos_transactions(id) ON DELETE CASCADE;


--
-- TOC entry 6022 (class 2606 OID 26550)
-- Name: pos_transaction_items pos_transaction_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transaction_items
    ADD CONSTRAINT pos_transaction_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6023 (class 2606 OID 26555)
-- Name: pos_transaction_items pos_transaction_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transaction_items
    ADD CONSTRAINT pos_transaction_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6014 (class 2606 OID 26504)
-- Name: pos_transactions pos_transactions_cashier_shift_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_cashier_shift_id_foreign FOREIGN KEY (cashier_shift_id) REFERENCES sigma.cashier_shifts(id) ON DELETE CASCADE;


--
-- TOC entry 6015 (class 2606 OID 26494)
-- Name: pos_transactions pos_transactions_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6016 (class 2606 OID 26776)
-- Name: pos_transactions pos_transactions_customer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES sigma.customers(id) ON DELETE SET NULL;


--
-- TOC entry 6017 (class 2606 OID 26509)
-- Name: pos_transactions pos_transactions_customer_tier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_customer_tier_id_foreign FOREIGN KEY (customer_tier_id) REFERENCES sigma.customer_tiers(id) ON DELETE CASCADE;


--
-- TOC entry 6018 (class 2606 OID 26499)
-- Name: pos_transactions pos_transactions_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6019 (class 2606 OID 26514)
-- Name: pos_transactions pos_transactions_voucher_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.pos_transactions
    ADD CONSTRAINT pos_transactions_voucher_id_foreign FOREIGN KEY (voucher_id) REFERENCES sigma.vouchers(id) ON DELETE SET NULL;


--
-- TOC entry 6097 (class 2606 OID 101350)
-- Name: product_assemblies product_assemblies_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assemblies
    ADD CONSTRAINT product_assemblies_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6098 (class 2606 OID 101360)
-- Name: product_assemblies product_assemblies_parent_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assemblies
    ADD CONSTRAINT product_assemblies_parent_product_id_foreign FOREIGN KEY (parent_product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6099 (class 2606 OID 101365)
-- Name: product_assemblies product_assemblies_parent_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assemblies
    ADD CONSTRAINT product_assemblies_parent_product_unit_id_foreign FOREIGN KEY (parent_product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6100 (class 2606 OID 101355)
-- Name: product_assemblies product_assemblies_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assemblies
    ADD CONSTRAINT product_assemblies_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6101 (class 2606 OID 101396)
-- Name: product_assembly_items product_assembly_items_component_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assembly_items
    ADD CONSTRAINT product_assembly_items_component_product_id_foreign FOREIGN KEY (component_product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6102 (class 2606 OID 101401)
-- Name: product_assembly_items product_assembly_items_component_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assembly_items
    ADD CONSTRAINT product_assembly_items_component_product_unit_id_foreign FOREIGN KEY (component_product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6103 (class 2606 OID 101391)
-- Name: product_assembly_items product_assembly_items_product_assembly_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_assembly_items
    ADD CONSTRAINT product_assembly_items_product_assembly_id_foreign FOREIGN KEY (product_assembly_id) REFERENCES sigma.product_assemblies(id) ON DELETE CASCADE;


--
-- TOC entry 5994 (class 2606 OID 26224)
-- Name: product_barcodes product_barcodes_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_barcodes
    ADD CONSTRAINT product_barcodes_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 5995 (class 2606 OID 26234)
-- Name: product_barcodes product_barcodes_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_barcodes
    ADD CONSTRAINT product_barcodes_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 5996 (class 2606 OID 26239)
-- Name: product_barcodes product_barcodes_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_barcodes
    ADD CONSTRAINT product_barcodes_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 5997 (class 2606 OID 26229)
-- Name: product_barcodes product_barcodes_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_barcodes
    ADD CONSTRAINT product_barcodes_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6104 (class 2606 OID 101429)
-- Name: product_batches product_batches_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_batches
    ADD CONSTRAINT product_batches_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6105 (class 2606 OID 101439)
-- Name: product_batches product_batches_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_batches
    ADD CONSTRAINT product_batches_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6106 (class 2606 OID 101434)
-- Name: product_batches product_batches_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_batches
    ADD CONSTRAINT product_batches_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6093 (class 2606 OID 101306)
-- Name: product_boms product_boms_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_boms
    ADD CONSTRAINT product_boms_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6094 (class 2606 OID 101316)
-- Name: product_boms product_boms_component_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_boms
    ADD CONSTRAINT product_boms_component_product_id_foreign FOREIGN KEY (component_product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6095 (class 2606 OID 101321)
-- Name: product_boms product_boms_component_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_boms
    ADD CONSTRAINT product_boms_component_product_unit_id_foreign FOREIGN KEY (component_product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6096 (class 2606 OID 101311)
-- Name: product_boms product_boms_parent_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_boms
    ADD CONSTRAINT product_boms_parent_product_id_foreign FOREIGN KEY (parent_product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6038 (class 2606 OID 26822)
-- Name: product_categories product_categories_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_categories
    ADD CONSTRAINT product_categories_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 5998 (class 2606 OID 26266)
-- Name: product_prices product_prices_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_prices
    ADD CONSTRAINT product_prices_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 5999 (class 2606 OID 26286)
-- Name: product_prices product_prices_customer_tier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_prices
    ADD CONSTRAINT product_prices_customer_tier_id_foreign FOREIGN KEY (customer_tier_id) REFERENCES sigma.customer_tiers(id) ON DELETE CASCADE;


--
-- TOC entry 6000 (class 2606 OID 26276)
-- Name: product_prices product_prices_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_prices
    ADD CONSTRAINT product_prices_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6001 (class 2606 OID 26281)
-- Name: product_prices product_prices_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_prices
    ADD CONSTRAINT product_prices_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6002 (class 2606 OID 26271)
-- Name: product_prices product_prices_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_prices
    ADD CONSTRAINT product_prices_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6003 (class 2606 OID 26313)
-- Name: product_stocks product_stocks_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_stocks
    ADD CONSTRAINT product_stocks_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6004 (class 2606 OID 26323)
-- Name: product_stocks product_stocks_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_stocks
    ADD CONSTRAINT product_stocks_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6005 (class 2606 OID 26318)
-- Name: product_stocks product_stocks_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_stocks
    ADD CONSTRAINT product_stocks_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 5991 (class 2606 OID 26192)
-- Name: product_units product_units_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_units
    ADD CONSTRAINT product_units_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 5992 (class 2606 OID 26202)
-- Name: product_units product_units_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_units
    ADD CONSTRAINT product_units_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 5993 (class 2606 OID 26197)
-- Name: product_units product_units_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.product_units
    ADD CONSTRAINT product_units_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 5986 (class 2606 OID 26832)
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES sigma.product_categories(id) ON DELETE SET NULL;


--
-- TOC entry 5987 (class 2606 OID 26157)
-- Name: products products_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.products
    ADD CONSTRAINT products_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 5988 (class 2606 OID 101593)
-- Name: products products_consignment_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.products
    ADD CONSTRAINT products_consignment_supplier_id_foreign FOREIGN KEY (consignment_supplier_id) REFERENCES sigma.suppliers(id) ON DELETE SET NULL;


--
-- TOC entry 5989 (class 2606 OID 26162)
-- Name: products products_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.products
    ADD CONSTRAINT products_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 5990 (class 2606 OID 26883)
-- Name: products products_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.products
    ADD CONSTRAINT products_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE SET NULL;


--
-- TOC entry 6130 (class 2606 OID 101994)
-- Name: promotions promotions_buy_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.promotions
    ADD CONSTRAINT promotions_buy_product_id_foreign FOREIGN KEY (buy_product_id) REFERENCES sigma.products(id) ON DELETE SET NULL;


--
-- TOC entry 6131 (class 2606 OID 101984)
-- Name: promotions promotions_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.promotions
    ADD CONSTRAINT promotions_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6132 (class 2606 OID 101999)
-- Name: promotions promotions_reward_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.promotions
    ADD CONSTRAINT promotions_reward_product_id_foreign FOREIGN KEY (reward_product_id) REFERENCES sigma.products(id) ON DELETE SET NULL;


--
-- TOC entry 6133 (class 2606 OID 101989)
-- Name: promotions promotions_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.promotions
    ADD CONSTRAINT promotions_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6047 (class 2606 OID 26999)
-- Name: purchase_items purchase_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_items
    ADD CONSTRAINT purchase_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6048 (class 2606 OID 27004)
-- Name: purchase_items purchase_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_items
    ADD CONSTRAINT purchase_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6049 (class 2606 OID 26994)
-- Name: purchase_items purchase_items_purchase_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_items
    ADD CONSTRAINT purchase_items_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES sigma.purchases(id) ON DELETE CASCADE;


--
-- TOC entry 6069 (class 2606 OID 100990)
-- Name: purchase_order_items purchase_order_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_order_items
    ADD CONSTRAINT purchase_order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6070 (class 2606 OID 100995)
-- Name: purchase_order_items purchase_order_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_order_items
    ADD CONSTRAINT purchase_order_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6071 (class 2606 OID 100985)
-- Name: purchase_order_items purchase_order_items_purchase_order_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_order_items
    ADD CONSTRAINT purchase_order_items_purchase_order_id_foreign FOREIGN KEY (purchase_order_id) REFERENCES sigma.purchase_orders(id) ON DELETE CASCADE;


--
-- TOC entry 6066 (class 2606 OID 100944)
-- Name: purchase_orders purchase_orders_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_orders
    ADD CONSTRAINT purchase_orders_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6067 (class 2606 OID 100949)
-- Name: purchase_orders purchase_orders_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_orders
    ADD CONSTRAINT purchase_orders_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6068 (class 2606 OID 100954)
-- Name: purchase_orders purchase_orders_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_orders
    ADD CONSTRAINT purchase_orders_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6146 (class 2606 OID 130531)
-- Name: purchase_return_debt_allocations purchase_return_debt_allocations_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_debt_allocations
    ADD CONSTRAINT purchase_return_debt_allocations_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6147 (class 2606 OID 130546)
-- Name: purchase_return_debt_allocations purchase_return_debt_allocations_purchase_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_debt_allocations
    ADD CONSTRAINT purchase_return_debt_allocations_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES sigma.purchases(id) ON DELETE CASCADE;


--
-- TOC entry 6148 (class 2606 OID 130541)
-- Name: purchase_return_debt_allocations purchase_return_debt_allocations_purchase_return_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_debt_allocations
    ADD CONSTRAINT purchase_return_debt_allocations_purchase_return_id_foreign FOREIGN KEY (purchase_return_id) REFERENCES sigma.purchase_returns(id) ON DELETE CASCADE;


--
-- TOC entry 6149 (class 2606 OID 130536)
-- Name: purchase_return_debt_allocations purchase_return_debt_allocations_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_debt_allocations
    ADD CONSTRAINT purchase_return_debt_allocations_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6062 (class 2606 OID 100909)
-- Name: purchase_return_items purchase_return_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_items
    ADD CONSTRAINT purchase_return_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6063 (class 2606 OID 100914)
-- Name: purchase_return_items purchase_return_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_items
    ADD CONSTRAINT purchase_return_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6064 (class 2606 OID 130514)
-- Name: purchase_return_items purchase_return_items_purchase_item_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_items
    ADD CONSTRAINT purchase_return_items_purchase_item_id_foreign FOREIGN KEY (purchase_item_id) REFERENCES sigma.purchase_items(id) ON DELETE CASCADE;


--
-- TOC entry 6065 (class 2606 OID 100899)
-- Name: purchase_return_items purchase_return_items_purchase_return_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_return_items
    ADD CONSTRAINT purchase_return_items_purchase_return_id_foreign FOREIGN KEY (purchase_return_id) REFERENCES sigma.purchase_returns(id) ON DELETE CASCADE;


--
-- TOC entry 6058 (class 2606 OID 100858)
-- Name: purchase_returns purchase_returns_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_returns
    ADD CONSTRAINT purchase_returns_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6059 (class 2606 OID 130503)
-- Name: purchase_returns purchase_returns_purchase_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_returns
    ADD CONSTRAINT purchase_returns_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES sigma.purchases(id) ON DELETE CASCADE;


--
-- TOC entry 6060 (class 2606 OID 100863)
-- Name: purchase_returns purchase_returns_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_returns
    ADD CONSTRAINT purchase_returns_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6061 (class 2606 OID 100873)
-- Name: purchase_returns purchase_returns_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchase_returns
    ADD CONSTRAINT purchase_returns_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6041 (class 2606 OID 26954)
-- Name: purchases purchases_bank_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT purchases_bank_account_id_foreign FOREIGN KEY (bank_account_id) REFERENCES sigma.bank_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6042 (class 2606 OID 26939)
-- Name: purchases purchases_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT purchases_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6043 (class 2606 OID 101003)
-- Name: purchases purchases_purchase_order_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT purchases_purchase_order_id_foreign FOREIGN KEY (purchase_order_id) REFERENCES sigma.purchase_orders(id) ON DELETE SET NULL;


--
-- TOC entry 6044 (class 2606 OID 26944)
-- Name: purchases purchases_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT purchases_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6045 (class 2606 OID 26949)
-- Name: purchases purchases_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT purchases_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6139 (class 2606 OID 102092)
-- Name: sales_order_items sales_order_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_order_items
    ADD CONSTRAINT sales_order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6140 (class 2606 OID 102097)
-- Name: sales_order_items sales_order_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_order_items
    ADD CONSTRAINT sales_order_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6141 (class 2606 OID 102087)
-- Name: sales_order_items sales_order_items_sales_order_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_order_items
    ADD CONSTRAINT sales_order_items_sales_order_id_foreign FOREIGN KEY (sales_order_id) REFERENCES sigma.sales_orders(id) ON DELETE CASCADE;


--
-- TOC entry 6134 (class 2606 OID 102056)
-- Name: sales_orders sales_orders_approved_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_orders
    ADD CONSTRAINT sales_orders_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6135 (class 2606 OID 102036)
-- Name: sales_orders sales_orders_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_orders
    ADD CONSTRAINT sales_orders_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6136 (class 2606 OID 102051)
-- Name: sales_orders sales_orders_created_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_orders
    ADD CONSTRAINT sales_orders_created_by_foreign FOREIGN KEY (created_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6137 (class 2606 OID 102046)
-- Name: sales_orders sales_orders_customer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_orders
    ADD CONSTRAINT sales_orders_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES sigma.customers(id) ON DELETE SET NULL;


--
-- TOC entry 6138 (class 2606 OID 102041)
-- Name: sales_orders sales_orders_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.sales_orders
    ADD CONSTRAINT sales_orders_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6155 (class 2606 OID 130634)
-- Name: goods_receipt_items sigma_goods_receipt_items_goods_receipt_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipt_items
    ADD CONSTRAINT sigma_goods_receipt_items_goods_receipt_id_foreign FOREIGN KEY (goods_receipt_id) REFERENCES sigma.goods_receipts(id) ON DELETE CASCADE;


--
-- TOC entry 6156 (class 2606 OID 130644)
-- Name: goods_receipt_items sigma_goods_receipt_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipt_items
    ADD CONSTRAINT sigma_goods_receipt_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6157 (class 2606 OID 130649)
-- Name: goods_receipt_items sigma_goods_receipt_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipt_items
    ADD CONSTRAINT sigma_goods_receipt_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE SET NULL;


--
-- TOC entry 6158 (class 2606 OID 130639)
-- Name: goods_receipt_items sigma_goods_receipt_items_purchase_order_item_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipt_items
    ADD CONSTRAINT sigma_goods_receipt_items_purchase_order_item_id_foreign FOREIGN KEY (purchase_order_item_id) REFERENCES sigma.purchase_order_items(id) ON DELETE SET NULL;


--
-- TOC entry 6150 (class 2606 OID 130580)
-- Name: goods_receipts sigma_goods_receipts_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipts
    ADD CONSTRAINT sigma_goods_receipts_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6151 (class 2606 OID 130590)
-- Name: goods_receipts sigma_goods_receipts_purchase_order_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipts
    ADD CONSTRAINT sigma_goods_receipts_purchase_order_id_foreign FOREIGN KEY (purchase_order_id) REFERENCES sigma.purchase_orders(id) ON DELETE SET NULL;


--
-- TOC entry 6152 (class 2606 OID 130600)
-- Name: goods_receipts sigma_goods_receipts_received_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipts
    ADD CONSTRAINT sigma_goods_receipts_received_by_foreign FOREIGN KEY (received_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6153 (class 2606 OID 130585)
-- Name: goods_receipts sigma_goods_receipts_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipts
    ADD CONSTRAINT sigma_goods_receipts_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6154 (class 2606 OID 130595)
-- Name: goods_receipts sigma_goods_receipts_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.goods_receipts
    ADD CONSTRAINT sigma_goods_receipts_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6046 (class 2606 OID 130657)
-- Name: purchases sigma_purchases_goods_receipt_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.purchases
    ADD CONSTRAINT sigma_purchases_goods_receipt_id_foreign FOREIGN KEY (goods_receipt_id) REFERENCES sigma.goods_receipts(id) ON DELETE SET NULL;


--
-- TOC entry 6161 (class 2606 OID 130736)
-- Name: stock_adjustment_items stock_adjustment_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustment_items
    ADD CONSTRAINT stock_adjustment_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6162 (class 2606 OID 130741)
-- Name: stock_adjustment_items stock_adjustment_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustment_items
    ADD CONSTRAINT stock_adjustment_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6163 (class 2606 OID 130731)
-- Name: stock_adjustment_items stock_adjustment_items_stock_adjustment_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustment_items
    ADD CONSTRAINT stock_adjustment_items_stock_adjustment_id_foreign FOREIGN KEY (stock_adjustment_id) REFERENCES sigma.stock_adjustments(id) ON DELETE CASCADE;


--
-- TOC entry 6159 (class 2606 OID 130690)
-- Name: stock_adjustments stock_adjustments_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustments
    ADD CONSTRAINT stock_adjustments_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6160 (class 2606 OID 130695)
-- Name: stock_adjustments stock_adjustments_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_adjustments
    ADD CONSTRAINT stock_adjustments_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6166 (class 2606 OID 130812)
-- Name: stock_movement_items stock_movement_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movement_items
    ADD CONSTRAINT stock_movement_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6167 (class 2606 OID 130817)
-- Name: stock_movement_items stock_movement_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movement_items
    ADD CONSTRAINT stock_movement_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6168 (class 2606 OID 130807)
-- Name: stock_movement_items stock_movement_items_stock_movement_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movement_items
    ADD CONSTRAINT stock_movement_items_stock_movement_id_foreign FOREIGN KEY (stock_movement_id) REFERENCES sigma.stock_movements(id) ON DELETE CASCADE;


--
-- TOC entry 6164 (class 2606 OID 130767)
-- Name: stock_movements stock_movements_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movements
    ADD CONSTRAINT stock_movements_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6165 (class 2606 OID 130772)
-- Name: stock_movements stock_movements_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_movements
    ADD CONSTRAINT stock_movements_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6006 (class 2606 OID 26358)
-- Name: stock_mutations stock_mutations_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_mutations
    ADD CONSTRAINT stock_mutations_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6007 (class 2606 OID 26368)
-- Name: stock_mutations stock_mutations_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_mutations
    ADD CONSTRAINT stock_mutations_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6008 (class 2606 OID 26363)
-- Name: stock_mutations stock_mutations_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_mutations
    ADD CONSTRAINT stock_mutations_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6086 (class 2606 OID 101224)
-- Name: stock_opname_items stock_opname_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_opname_items
    ADD CONSTRAINT stock_opname_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6087 (class 2606 OID 101219)
-- Name: stock_opname_items stock_opname_items_stock_opname_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_opname_items
    ADD CONSTRAINT stock_opname_items_stock_opname_id_foreign FOREIGN KEY (stock_opname_id) REFERENCES sigma.stock_opnames(id) ON DELETE CASCADE;


--
-- TOC entry 6084 (class 2606 OID 101183)
-- Name: stock_opnames stock_opnames_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_opnames
    ADD CONSTRAINT stock_opnames_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6085 (class 2606 OID 101188)
-- Name: stock_opnames stock_opnames_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_opnames
    ADD CONSTRAINT stock_opnames_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6145 (class 2606 OID 130484)
-- Name: stock_requirement_allocations stock_requirement_allocations_stock_requirement_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirement_allocations
    ADD CONSTRAINT stock_requirement_allocations_stock_requirement_id_foreign FOREIGN KEY (stock_requirement_id) REFERENCES sigma.stock_requirements(id) ON DELETE CASCADE;


--
-- TOC entry 6142 (class 2606 OID 130443)
-- Name: stock_requirements stock_requirements_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirements
    ADD CONSTRAINT stock_requirements_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6143 (class 2606 OID 130453)
-- Name: stock_requirements stock_requirements_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirements
    ADD CONSTRAINT stock_requirements_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6144 (class 2606 OID 130448)
-- Name: stock_requirements stock_requirements_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_requirements
    ADD CONSTRAINT stock_requirements_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6081 (class 2606 OID 101146)
-- Name: stock_transfer_items stock_transfer_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfer_items
    ADD CONSTRAINT stock_transfer_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6082 (class 2606 OID 101151)
-- Name: stock_transfer_items stock_transfer_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfer_items
    ADD CONSTRAINT stock_transfer_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6083 (class 2606 OID 101141)
-- Name: stock_transfer_items stock_transfer_items_stock_transfer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfer_items
    ADD CONSTRAINT stock_transfer_items_stock_transfer_id_foreign FOREIGN KEY (stock_transfer_id) REFERENCES sigma.stock_transfers(id) ON DELETE CASCADE;


--
-- TOC entry 6078 (class 2606 OID 101094)
-- Name: stock_transfers stock_transfers_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfers
    ADD CONSTRAINT stock_transfers_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6079 (class 2606 OID 101104)
-- Name: stock_transfers stock_transfers_destination_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfers
    ADD CONSTRAINT stock_transfers_destination_store_id_foreign FOREIGN KEY (destination_store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6080 (class 2606 OID 101099)
-- Name: stock_transfers stock_transfers_source_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stock_transfers
    ADD CONSTRAINT stock_transfers_source_store_id_foreign FOREIGN KEY (source_store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 5983 (class 2606 OID 26088)
-- Name: stores stores_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.stores
    ADD CONSTRAINT stores_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6193 (class 2606 OID 131086)
-- Name: supplier_deposits supplier_deposits_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_account_id_foreign FOREIGN KEY (account_id) REFERENCES sigma.chart_of_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6194 (class 2606 OID 131081)
-- Name: supplier_deposits supplier_deposits_bank_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_bank_account_id_foreign FOREIGN KEY (bank_account_id) REFERENCES sigma.bank_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6195 (class 2606 OID 131066)
-- Name: supplier_deposits supplier_deposits_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6196 (class 2606 OID 131096)
-- Name: supplier_deposits supplier_deposits_created_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_created_by_foreign FOREIGN KEY (created_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6197 (class 2606 OID 131091)
-- Name: supplier_deposits supplier_deposits_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES sigma.journal_entries(id) ON DELETE SET NULL;


--
-- TOC entry 6198 (class 2606 OID 131071)
-- Name: supplier_deposits supplier_deposits_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 6199 (class 2606 OID 131076)
-- Name: supplier_deposits supplier_deposits_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_deposits
    ADD CONSTRAINT supplier_deposits_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6076 (class 2606 OID 101065)
-- Name: supplier_payment_items supplier_payment_items_purchase_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payment_items
    ADD CONSTRAINT supplier_payment_items_purchase_id_foreign FOREIGN KEY (purchase_id) REFERENCES sigma.purchases(id) ON DELETE CASCADE;


--
-- TOC entry 6077 (class 2606 OID 101060)
-- Name: supplier_payment_items supplier_payment_items_supplier_payment_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payment_items
    ADD CONSTRAINT supplier_payment_items_supplier_payment_id_foreign FOREIGN KEY (supplier_payment_id) REFERENCES sigma.supplier_payments(id) ON DELETE CASCADE;


--
-- TOC entry 6072 (class 2606 OID 101040)
-- Name: supplier_payments supplier_payments_bank_account_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payments
    ADD CONSTRAINT supplier_payments_bank_account_id_foreign FOREIGN KEY (bank_account_id) REFERENCES sigma.bank_accounts(id) ON DELETE SET NULL;


--
-- TOC entry 6073 (class 2606 OID 101025)
-- Name: supplier_payments supplier_payments_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payments
    ADD CONSTRAINT supplier_payments_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6074 (class 2606 OID 101030)
-- Name: supplier_payments supplier_payments_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payments
    ADD CONSTRAINT supplier_payments_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6075 (class 2606 OID 101035)
-- Name: supplier_payments supplier_payments_supplier_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.supplier_payments
    ADD CONSTRAINT supplier_payments_supplier_id_foreign FOREIGN KEY (supplier_id) REFERENCES sigma.suppliers(id) ON DELETE CASCADE;


--
-- TOC entry 6037 (class 2606 OID 26800)
-- Name: suppliers suppliers_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.suppliers
    ADD CONSTRAINT suppliers_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6033 (class 2606 OID 26726)
-- Name: tax_configurations tax_configurations_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.tax_configurations
    ADD CONSTRAINT tax_configurations_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6207 (class 2606 OID 131265)
-- Name: transaction_number_patterns transaction_number_patterns_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transaction_number_patterns
    ADD CONSTRAINT transaction_number_patterns_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6175 (class 2606 OID 130903)
-- Name: transfer_request_items transfer_request_items_product_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_request_items
    ADD CONSTRAINT transfer_request_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES sigma.products(id) ON DELETE CASCADE;


--
-- TOC entry 6176 (class 2606 OID 130908)
-- Name: transfer_request_items transfer_request_items_product_unit_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_request_items
    ADD CONSTRAINT transfer_request_items_product_unit_id_foreign FOREIGN KEY (product_unit_id) REFERENCES sigma.product_units(id) ON DELETE CASCADE;


--
-- TOC entry 6177 (class 2606 OID 130898)
-- Name: transfer_request_items transfer_request_items_transfer_request_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_request_items
    ADD CONSTRAINT transfer_request_items_transfer_request_id_foreign FOREIGN KEY (transfer_request_id) REFERENCES sigma.transfer_requests(id) ON DELETE CASCADE;


--
-- TOC entry 6169 (class 2606 OID 130866)
-- Name: transfer_requests transfer_requests_approved_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6170 (class 2606 OID 130846)
-- Name: transfer_requests transfer_requests_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6171 (class 2606 OID 130861)
-- Name: transfer_requests transfer_requests_created_by_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_created_by_foreign FOREIGN KEY (created_by) REFERENCES sigma.users(id) ON DELETE SET NULL;


--
-- TOC entry 6172 (class 2606 OID 130856)
-- Name: transfer_requests transfer_requests_destination_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_destination_store_id_foreign FOREIGN KEY (destination_store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6173 (class 2606 OID 130851)
-- Name: transfer_requests transfer_requests_requesting_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_requesting_store_id_foreign FOREIGN KEY (requesting_store_id) REFERENCES sigma.stores(id) ON DELETE CASCADE;


--
-- TOC entry 6174 (class 2606 OID 130871)
-- Name: transfer_requests transfer_requests_stock_transfer_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.transfer_requests
    ADD CONSTRAINT transfer_requests_stock_transfer_id_foreign FOREIGN KEY (stock_transfer_id) REFERENCES sigma.stock_transfers(id) ON DELETE SET NULL;


--
-- TOC entry 6039 (class 2606 OID 26849)
-- Name: units units_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.units
    ADD CONSTRAINT units_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6206 (class 2606 OID 131205)
-- Name: user_groups user_groups_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.user_groups
    ADD CONSTRAINT user_groups_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 5981 (class 2606 OID 100741)
-- Name: users users_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.users
    ADD CONSTRAINT users_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


--
-- TOC entry 5982 (class 2606 OID 131214)
-- Name: users users_user_group_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.users
    ADD CONSTRAINT users_user_group_id_foreign FOREIGN KEY (user_group_id) REFERENCES sigma.user_groups(id) ON DELETE SET NULL;


--
-- TOC entry 6009 (class 2606 OID 26396)
-- Name: vouchers vouchers_company_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.vouchers
    ADD CONSTRAINT vouchers_company_id_foreign FOREIGN KEY (company_id) REFERENCES sigma.companies(id) ON DELETE CASCADE;


--
-- TOC entry 6010 (class 2606 OID 26401)
-- Name: vouchers vouchers_store_id_foreign; Type: FK CONSTRAINT; Schema: sigma; Owner: postgres
--

ALTER TABLE ONLY sigma.vouchers
    ADD CONSTRAINT vouchers_store_id_foreign FOREIGN KEY (store_id) REFERENCES sigma.stores(id) ON DELETE SET NULL;


-- Completed on 2026-10-30 10:36:02

--
-- PostgreSQL database dump complete
--

\unrestrict rnXZrx2CJBzETSf8oPxqytX9foUGul35IvFaMsl6tEKRh2YXV1lxmJiq9Wuuta8

