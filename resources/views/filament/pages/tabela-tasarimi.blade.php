@php
    $kutu = 'border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:.75rem';
    $girdi = 'width:100%;padding:.4rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem';
    $btn = 'padding:.32rem .55rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .3);font-size:.76rem;background:transparent;cursor:pointer;white-space:nowrap';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <div style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:260px;max-width:560px">
            <label style="font-weight:600;font-size:.8rem">Firma</label>
            <select wire:model.live="firmaId" style="{{ $girdi }}">
                <option value="">Firma seçin...</option>
                @foreach ($this->firmalar as $id => $unvan)<option value="{{ $id }}">{{ $unvan }}</option>@endforeach
            </select>
        </div>
    </div>

    @if ($this->firma)
        {{-- Firma başına kayıtlı tasarımlar: aç / sil / yeni --}}
        <div style="{{ $kutu }};display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
            <button type="button" wire:click="yeni" style="{{ $btn }};{{ ! $tasarimId ? 'background:#2563eb;color:#fff;border-color:#2563eb;font-weight:700' : '' }}">
                + Yeni Tasarım
            </button>

            @forelse ($this->tasarimlar as $t)
                <div style="display:flex;align-items:center;gap:.3rem;border:1px solid rgb(107 114 128 / .25);border-radius:.5rem;padding:.25rem .4rem;{{ $tasarimId === $t->id ? 'background:#eff6ff;border-color:#2563eb' : '' }}">
                    @if ($this->gorselUrl($t))
                        <img src="{{ $this->gorselUrl($t) }}" alt="" style="width:28px;height:28px;object-fit:contain;border-radius:.25rem;border:1px solid rgb(107 114 128 / .25)">
                    @endif
                    <button type="button" wire:click="ac({{ $t->id }})" style="background:none;border:none;cursor:pointer;font-size:.76rem;font-weight:{{ $tasarimId === $t->id ? '700' : '500' }}">
                        {{ $t->baslik }}
                    </button>
                    <button type="button" wire:click="svgIndir({{ $t->id }})" style="{{ $btn }};padding:.15rem .4rem;font-size:.75rem" title="Gerçek vektör SVG (baskıya hazır)">SVG</button>
                    <button type="button" wire:click="pdfIndir({{ $t->id }})" style="{{ $btn }};padding:.15rem .4rem;font-size:.75rem" title="Gerçek ölçüde A4 PDF şablonu">PDF</button>
                    <button type="button" wire:click="sil({{ $t->id }})" wire:confirm="'{{ $t->baslik }}' silinsin mi?" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:.76rem" title="Sil">✕</button>
                </div>
            @empty
                <span style="font-size:.76rem;color:rgb(107 114 128)">Bu firma için henüz kayıtlı tabela tasarımı yok.</span>
            @endforelse

            @if ($tasarimId)
                <button type="button" wire:click="whatsapp" style="{{ $btn }};margin-left:auto;background:#16a34a;color:#fff;border-color:#16a34a;font-weight:700"
                    title="Açık tasarımın 30 gün geçerli baskı PDF bağlantısı — işverenin telefonu yoksa WhatsApp'ta alıcıyı (ör. tabelacı) siz seçersiniz">📲 WhatsApp ile Paylaş</button>
            @endif
        </div>

        {{-- Risk analizi ve saha bulgularından türetilen levha önerileri --}}
        <div style="{{ $kutu }}">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap">
                <div style="font-weight:700;font-size:.85rem">Risk Analizi & Saha Bulgularından Önerilen Levhalar ({{ count($this->oneriler) }})</div>
                @if ($this->oneriler)
                    <div style="display:flex;gap:.35rem;align-items:center">
                        <span style="font-size:.75rem;font-weight:700">⚡ Önerilen Tüm Levhaları Otomatik Paketle (ZIP):</span>
                        <button type="button" wire:click="oneriPaketi('a4')" wire:loading.attr="disabled" wire:target="oneriPaketi" style="{{ $btn }};background:#d97706;color:#fff;border-color:#d97706;font-weight:700">A4</button>
                        <button type="button" wire:click="oneriPaketi('a3')" wire:loading.attr="disabled" wire:target="oneriPaketi" style="{{ $btn }};background:#d97706;color:#fff;border-color:#d97706;font-weight:700">A3</button>
                        <span wire:loading wire:target="oneriPaketi" style="font-size:.75rem;color:#b45309">Hazırlanıyor…</span>
                    </div>
                @endif
            </div>
            <div style="font-size:.75rem;color:rgb(107 114 128);margin:.15rem 0 .6rem">
                Firmanın son risk değerlendirmesindeki açık maddeler ve açık saha bulguları taranır; tehlikeye uygun ikaz / yasak / emredici levha şablonu hazır gelir.
                Sinyal sözcüğü en yüksek risk düzeyine göre seçilir (Tehlike · Uyarı · Dikkat).
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:.6rem">
            @forelse ($this->oneriler as $o)
                <div style="display:flex;gap:.6rem;border:1px solid {{ $oneri === $o['anahtar'] ? '#2563eb' : 'rgb(107 114 128 / .25)' }};border-radius:.75rem;padding:.55rem;background:#fff">
                    <img src="data:image/svg+xml;base64,{{ base64_encode(\App\Support\TabelaSvgUretici::svg($o['tasarim'])) }}" alt="" style="width:72px;height:auto;flex-shrink:0;align-self:flex-start">
                    <div style="min-width:0;flex:1">
                        <div style="font-weight:700;font-size:.78rem">{{ $o['etiket'] }}</div>
                        <div style="display:flex;gap:.3rem;margin:.15rem 0;flex-wrap:wrap">
                            <span style="font-size:.68rem;font-weight:700;padding:1px 6px;border-radius:9999px;color:#fff;background:{{ $o['seviye'] === 'yuksek' ? '#dc2626' : ($o['seviye'] === 'orta' ? '#d97706' : '#64748b') }}">
                                {{ $o['tasarim']['signalTr'] }}
                            </span>
                            <span style="font-size:.68rem;color:rgb(107 114 128)">{{ $o['sayi'] }} kayıt</span>
                        </div>
                        <ul style="font-size:.68rem;color:rgb(107 114 128);margin:0;padding-left:.9rem">
                            @foreach ($o['kaynaklar'] as $k)<li>{{ $k }}</li>@endforeach
                        </ul>
                        <button type="button" wire:click="oneriyiAc('{{ $o['anahtar'] }}')" style="{{ $btn }};margin-top:.35rem;background:#2563eb;color:#fff;border-color:#2563eb;font-weight:700">Tasarla</button>
                    </div>
                </div>
            @empty
                <span style="font-size:.76rem;color:rgb(107 114 128)">Önerilecek levha bulunamadı — firmanın risk değerlendirmesinde ya da açık saha bulgularında tanınan bir tehlike (elektrik, yüksekte çalışma, kimyasal, yangın vb.) yok.</span>
            @endforelse
            </div>
        </div>
        {{-- Editör: yalıtılmış iframe (kendi Tailwind CDN teması). Kaydet butonu postMessage ile gönderir, kaydet()'e gider. --}}
        <div wire:ignore wire:key="tabela-{{ $this->firma->id }}-{{ $tasarimId ?? ($oneri ? 'o-'.$oneri : 'yeni') }}"
            x-data="{
                kaydediliyor: false,
                mesaj(e) {
                    if (e.source !== this.$refs.cerceve.contentWindow) { return; }
                    const m = e.data || {};
                    if (m.tip === 'kaydet') {
                        this.kaydediliyor = true;
                        this.$wire.kaydet(m.baslik, m.veri, m.png).then(() => { this.kaydediliyor = false; });
                    }
                    if (m.tip === 'cevir') {
                        this.$wire.cevir(m.baslik, m.aciklama, m.sinyal).then((s) => {
                            this.$refs.cerceve.contentWindow.postMessage({ tip: 'ceviri', ...(s || { hata: 'Çeviri alınamadı' }) }, '*');
                        });
                    }
                },
            }"
            x-on:message.window="mesaj($event)">
            <div style="font-size:.75rem;color:rgb(107 114 128);margin-bottom:.3rem">
                Tasarımı bitirince <strong>"mehse'ye Kaydet"</strong> düğmesine basın.
                <span x-show="kaydediliyor" style="color:#2563eb;font-weight:600">· Kaydediliyor…</span>
            </div>
            <iframe x-ref="cerceve" title="Tabela / Güvenlik Levhası Tasarım Atölyesi" srcdoc="{{ $this->editorHtml }}"
                style="width:100%;height:calc(100vh - 320px);min-height:640px;border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;background:#fff"></iframe>
        </div>
    @endif
</x-filament-panels::page>
