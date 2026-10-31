@extends('layouts.app')

@section('title', 'Master Rekening Bank & QRIS')
@section('tab_title', 'Master Rekening Bank')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full"
     x-data="{
         createModal: false,
         editModal: false,
         editAcc: {
             id: '',
             bank_code: '',
             bank_name: '',
             account_number: '',
             account_holder: '',
             branch: ''
         }
     }">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Rekening Bank & QRIS</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🏦 Master Rekening Bank & E-Money Toko</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="createModal = true"
                    class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Rekening Baru</span>
            </button>
        </div>
    </div>

    <!-- INFO BOX KASIR INTEGRATION -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 text-xs text-blue-900 flex items-start gap-2.5">
        <span class="text-lg">ℹ️</span>
        <div class="leading-relaxed">
            Rekening bank dan QRIS yang aktif di bawah ini otomatis menjadi opsi pilihan bagi kasir di <strong>Layar Kasir POS [F12]</strong> saat pelanggan memilih metode pembayaran <strong>TRANSFER / QRIS</strong>.
        </div>
    </div>

    <!-- GRID KARTU REKENING BANK & QRIS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($bankAccounts as $acc)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group">
                <!-- Top Accent Line -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r {{ $acc->bank_code === 'BCA' ? 'from-blue-600 to-cyan-500' : ($acc->bank_code === 'MANDIRI' ? 'from-amber-500 to-blue-700' : ($acc->bank_code === 'QRIS' ? 'from-rose-600 to-amber-500' : 'from-emerald-600 to-teal-500')) }}"></div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-1 rounded-md font-mono font-black text-xs text-white shadow-2xs {{ $acc->bank_code === 'BCA' ? 'bg-blue-700' : ($acc->bank_code === 'MANDIRI' ? 'bg-amber-600' : ($acc->bank_code === 'QRIS' ? 'bg-rose-600' : 'bg-emerald-700')) }}">
                            {{ $acc->bank_code }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $acc->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                            {{ $acc->is_active ? '● Aktif Kasir' : 'Non-Aktif' }}
                        </span>
                    </div>

                    <h3 class="font-extrabold text-slate-900 text-sm mb-1">{{ $acc->bank_name }}</h3>

                    <!-- No Rekening Besar -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 my-3">
                        <span class="block text-[10.5px] font-semibold text-slate-400">Nomor Rekening / ID Merchant:</span>
                        <span class="font-mono font-black text-lg text-slate-800 tracking-wider select-all">
                            {{ $acc->account_number }}
                        </span>
                    </div>

                    <div class="space-y-1 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="font-medium text-[11px]">Atas Nama (A.N):</span>
                            <span class="font-bold text-slate-800 text-[11px] truncate max-w-[180px]">{{ $acc->account_holder }}</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500 text-[10.5px]">
                            <span>Kantor Cabang:</span>
                            <span class="font-medium">{{ $acc->branch ?: '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 mt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <button type="button"
                            @click="editAcc = {
                                id: '{{ $acc->id }}',
                                bank_code: '{{ addslashes($acc->bank_code) }}',
                                bank_name: '{{ addslashes($acc->bank_name) }}',
                                account_number: '{{ addslashes($acc->account_number) }}',
                                account_holder: '{{ addslashes($acc->account_holder) }}',
                                branch: '{{ addslashes($acc->branch ?? '') }}'
                            }; editModal = true;"
                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg font-bold text-[11px] cursor-pointer">
                        ✏️ Edit
                    </button>
                    <form action="{{ route('master.bank-accounts.toggle-status', $acc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status rekening ini?');" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="px-2.5 py-1 {{ $acc->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }} rounded-lg font-bold text-[10.5px] cursor-pointer">
                            {{ $acc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-12 text-center text-slate-400 text-xs bg-white rounded-xl border border-slate-200">
                🏦 Belum ada rekening bank yang terdaftar. Klik <strong>Tambah Rekening Baru</strong>.
            </div>
        @endforelse
    </div>

    <!-- MODAL TAMBAH REKENING BARU -->
    <div x-show="createModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="createModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-blue-700 to-indigo-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🏦</span>
                    <div>
                        <h3 class="text-sm font-black">Tambah Rekening Bank / QRIS Baru</h3>
                        <p class="text-[11px] text-blue-200">Opsi penerimaan transfer dana di kasir POS</p>
                    </div>
                </div>
                <button type="button" @click="createModal = false" class="text-blue-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form action="{{ route('master.bank-accounts.store') }}" method="POST" class="p-5 space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Singkat: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="bank_code"
                               placeholder="BCA, MANDIRI, QRIS"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-black uppercase focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Bank / Merchant: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="bank_name"
                               placeholder="Bank Central Asia, QRIS Toko"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor Rekening / ID Merchant: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="account_number"
                           placeholder="Contoh: 1234567890"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-black text-sm tracking-wide focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Atas Nama (A.N Pemilik Rekening): <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="account_holder"
                           placeholder="Contoh: PT SIGMA RETAIL UTAMA"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kantor Cabang (Opsional):</label>
                    <input type="text"
                           name="branch"
                           placeholder="Contoh: KCU Sudirman Jakarta"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Rekening
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT REKENING -->
    <div x-show="editModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="editModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-amber-600 to-amber-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <div>
                        <h3 class="text-sm font-black">Edit Master Rekening Bank</h3>
                        <p class="text-[11px] text-amber-200">Perbarui nomor rekening dan identitas bank transfer kasir</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-amber-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('master/bank-accounts') }}/' + editAcc.id" method="POST" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Singkat: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="bank_code"
                               x-model="editAcc.bank_code"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-black uppercase focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Bank / Merchant: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="bank_name"
                               x-model="editAcc.bank_name"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor Rekening / ID Merchant: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="account_number"
                           x-model="editAcc.account_number"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-black text-sm tracking-wide focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Atas Nama (A.N Pemilik Rekening): <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="account_holder"
                           x-model="editAcc.account_holder"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kantor Cabang (Opsional):</label>
                    <input type="text"
                           name="branch"
                           x-model="editAcc.branch"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="editModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
