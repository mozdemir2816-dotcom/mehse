<x-filament-panels::page>
    {{-- Aktif Saha İşyeri Seçici --}}
    <div style="border:1px solid var(--border,#cbd5e1);background:var(--panel-bg,#fff);border-radius:14px;padding:.85rem 1rem;margin-bottom:.25rem">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.35rem">
            <label for="saha-firma-sec" style="font-size:.78rem;font-weight:700;color:var(--text-secondary,#475569);text-transform:uppercase;letter-spacing:.03em">
                📍 Sahadaki Aktif İşyeri
            </label>
            @if ($this->seciliFirma)
                <a href="{{ \App\Filament\Pages\FirmaDenetimOperasyon::getUrl(['firma' => $this->seciliFirma->id, 'bolum' => 'uzman']) }}"
                    style="font-size:.75rem;font-weight:700;color:var(--primary,#0b4f8a);text-decoration:none">
                    İşveren Özeti / WhatsApp →
                </a>
            @endif
        </div>
        <select id="saha-firma-sec" wire:model.live="firmaId"
            style="width:100%;padding:.55rem .75rem;border-radius:10px;border:1px solid var(--border,#cbd5e1);background:transparent;font-size:.9rem;font-weight:600;color:var(--text-heading,#0f172a)">
            <option value="" @selected(blank($firmaId))>— İşyeri seçin (tüm kısayollar o firmayla açılır) —</option>
            @foreach ($this->firmalar as $id => $ad)
                <option value="{{ $id }}" @selected((int) $firmaId === (int) $id)>{{ $ad }}</option>
            @endforeach
        </select>
        @if ($this->seciliFirma)
            <div style="display:flex;flex-wrap:wrap;gap:.5rem .9rem;margin-top:.5rem;font-size:.76rem;color:var(--text-secondary,#475569)">
                <span><strong>Tehlike:</strong> {{ $this->seciliFirma->tehlikeSinifiEtiketi() }}</span>
                <span><strong>Çalışan:</strong> {{ $this->seciliFirma->calisan_sayisi ?: '—' }}</span>
                <span><strong>NACE:</strong> {{ $this->seciliFirma->nace_kodu ?: '—' }}</span>
            </div>
        @endif
    </div>

    @if ($kartlar === [])
        <div class="mehse-saha-bos">
            <p>Saha Ekranı'nda henüz kısayol yok.</p>
            <a href="{{ \App\Filament\Pages\Ayarlar::getUrl() }}" class="fi-btn fi-color-primary fi-size-md">Ayarlar'da kısayol seç</a>
        </div>
    @else
        <nav class="mehse-saha-izgara" aria-label="Saha kısayolları">
            @foreach ($kartlar as $kart)
                <a href="{{ $kart['url'] }}" class="mehse-saha-kart" data-kisayol="{{ $kart['anahtar'] }}">
                    <span class="mehse-saha-kart-ikon">
                        <x-filament::icon :icon="$kart['ikon']" />
                    </span>
                    <span class="mehse-saha-kart-etiket">{{ $kart['etiket'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Telefona Uygulama Olarak Kurulum (PWA) Kartı --}}
        <div id="mehse-saha-pwa-kart" style="border:1px solid rgb(9 146 145 / .3);background:rgb(9 146 145 / .06);border-radius:14px;padding:.85rem 1rem;margin-top:.5rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem">
            <div style="display:flex;align-items:center;gap:.65rem;min-width:200px;flex:1">
                <img src="{{ asset('images/pwa/ikon-192.png') }}" alt="MEHSE İSG" style="width:38px;height:38px;border-radius:9px;flex-shrink:0">
                <div style="font-size:.8rem;line-height:1.35">
                    <strong style="display:block;font-size:.88rem;color:var(--text-heading,#0f172a)">📱 Telefon Uygulaması Olarak Kullanın</strong>
                    <span style="color:var(--text-secondary,#475569)">Android/Chrome'da "Uygulamayı Yükle"ye, iPhone Safari'de <b>Paylaş → Ana Ekrana Ekle</b>'ye dokunun.</span>
                </div>
            </div>
            <button type="button" id="mehse-saha-pwa-btn" hidden
                style="padding:.5rem 1rem;border-radius:10px;border:none;background:linear-gradient(135deg,#099291,#077a79);color:#fff;font-weight:700;font-size:.82rem;cursor:pointer">
                📲 Uygulamayı Yükle
            </button>
        </div>
        <script>
            (function () {
                var kart = document.getElementById('mehse-saha-pwa-kart');
                var btn = document.getElementById('mehse-saha-pwa-btn');
                if (!kart || !btn) return;
                if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                    kart.style.display = 'none';
                    return;
                }
                var deferredPrompt = null;
                window.addEventListener('beforeinstallprompt', function (e) {
                    e.preventDefault();
                    deferredPrompt = e;
                    btn.hidden = false;
                });
                btn.addEventListener('click', function () {
                    if (!deferredPrompt) return;
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.finally(function () {
                        deferredPrompt = null;
                        btn.hidden = true;
                    });
                });
            })();
        </script>

        <p class="mehse-saha-not" style="margin-top:.75rem;padding-bottom:1rem">
            Kartları ve telefon açılış ekranını değiştirmek için <a href="{{ \App\Filament\Pages\Ayarlar::getUrl() }}" style="font-weight:600;text-decoration:underline">Ayarlar</a>'a gidin.
        </p>
    @endif
</x-filament-panels::page>
