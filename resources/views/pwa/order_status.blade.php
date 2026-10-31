<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pesanan {{ $order->order_number }} - {{ $order->store->company->name ?? 'SIGMA Supermarket' }}</title>
    <meta name="theme-color" content="#2563eb">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased pb-12">

    <!-- TOP HEADER -->
    <header class="bg-white border-b border-slate-200 px-4 py-3 shadow-xs">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <a href="{{ route('pwa.index') }}" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                <span>&larr;</span> Kembali ke Belanja
            </a>
            <span class="text-xs font-black text-slate-900">{{ $order->store->name }}</span>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-4 space-y-4">

        <!-- STATUS CARD -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 text-center shadow-xs">
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner">
                ✓
            </div>
            <h1 class="text-base font-black text-slate-900">Pesanan Berhasil Dibuat!</h1>
            <div class="font-mono text-xs font-black text-blue-600 mt-1">{{ $order->order_number }}</div>

            <!-- STATUS BADGE -->
            <div class="mt-3 inline-block">
                @if($order->status === 'PENDING')
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-200">
                        ⏳ Menunggu Konfirmasi &amp; Pembayaran
                    </span>
                @elseif($order->status === 'APPROVED')
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-200">
                        📦 Pesanan Dikonfirmasi (Sedang Disiapkan)
                    </span>
                @elseif($order->status === 'PROCESSED')
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                        🛵 Selesai / Siap Diambil
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800">
                        ✕ Dibatalkan
                    </span>
                @endif
            </div>

            <!-- 30-MINUTES RESERVATION NOTICE -->
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-xl text-left text-xs text-blue-900">
                <div class="font-black flex items-center gap-1.5 text-blue-700">
                    <span>⏱️</span> Stok Direservasi (30 Menit)
                </div>
                <p class="text-[11px] mt-1 text-blue-800 leading-normal">
                    Stok barang Anda telah diamankan otomatis oleh sistem selama 30 menit. Silakan konfirmasi atau selesaikan pembayaran ke toko.
                </p>
            </div>
        </div>

        <!-- WHATSAPP DIRECT BUTTON -->
        @php
            $rawPhone = $order->store->phone ?: '081234567890';
            $waPhone = preg_replace('/[^0-9]/', '', $rawPhone);
            if (str_starts_with($waPhone, '0')) {
                $waPhone = '62' . substr($waPhone, 1);
            }
            $waText = urlencode("Halo {$order->store->name},\nSaya konfirmasi pesanan saya:\n" .
                "📌 No. Pesanan: *{$order->order_number}*\n" .
                "👤 Nama: {$order->customer->name}\n" .
                "💰 Total: *Rp " . number_format($order->grand_total, 0, ',', '.') . "*\n\n" .
                "Mohon info selanjutnya ya, terima kasih!");
            $waUrl = "https://wa.me/{$waPhone}?text={$waText}";
        @endphp

        <a href="{{ $waUrl }}" target="_blank"
           class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-2xl shadow-md flex items-center justify-center gap-2 transition-all active:scale-95 text-center">
            <span class="text-lg">💬</span>
            <span>Konfirmasi via WhatsApp Toko</span>
        </a>

        <!-- REKENING PEMBAYARAN BANK -->
        @if($bankAccounts->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-3">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                    <span>🏦</span> Rekening Pembayaran Resmi
                </h3>
                <div class="space-y-2">
                    @foreach($bankAccounts as $bank)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
                            <div>
                                <div class="font-black text-slate-900">{{ $bank->bank_name }}</div>
                                <div class="font-mono text-sm font-bold text-blue-700 mt-0.5">{{ $bank->account_number }}</div>
                                <div class="text-[11px] text-slate-500">a/n {{ $bank->account_holder }}</div>
                            </div>
                            <button type="button"
                                    onclick="copyToClipboard('{{ $bank->account_number }}')"
                                    class="px-2.5 py-1 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg text-[11px] font-bold text-slate-700 cursor-pointer">
                                Salin
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- RINCIAN BARANG PESANAN -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-3">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Rincian Barang</h3>
            <div class="divide-y divide-slate-100 text-xs">
                @foreach($order->items as $item)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-900">{{ $item->product->name }}</div>
                            <div class="text-[11px] text-slate-500">
                                {{ (float)$item->quantity }} {{ $item->unit_code }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="font-black text-slate-900">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs font-black text-slate-900 uppercase">Total Pesanan:</span>
                <span class="text-base font-black text-blue-600">
                    Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- INFO PENERIMA & CATATAN -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs text-xs space-y-2">
            <h3 class="font-black uppercase tracking-wider text-slate-800 text-[11px]">Informasi Penerima</h3>
            <div class="text-slate-600">
                <div>Nama: <strong>{{ $order->customer->name }}</strong></div>
                <div>No. HP: <strong>{{ $order->customer->phone }}</strong></div>
                <div class="mt-1 text-[11px] text-slate-500 italic">{{ $order->notes }}</div>
            </div>
        </div>

        <div class="text-center pt-2">
            <a href="{{ route('pwa.index') }}" class="text-xs font-bold text-blue-600 hover:underline">
                🛍️ Mau Belanja Barang Lainnya?
            </a>
        </div>
    </main>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Nomor rekening ' + text + ' berhasil disalin!');
            });
        }
    </script>
</body>
</html>
