{{-- Sağ altta sabit yüzen İSG Mevzuat Asistanı — formdan çıkmadan anında 6331/yönetmelik danışma --}}
@auth
<div
    x-data="{
        acik: false,
        soru: '',
        yukleniyor: false,
        yanit: '',
        kaynaklar: [],
        hata: '',
        async sorGonder(metin = null) {
            const q = (metin ?? this.soru).trim();
            if (!q || this.yukleniyor) return;
            this.soru = q;
            this.yukleniyor = true;
            this.hata = '';
            try {
                const r = await fetch('{{ route('mehse.mevzuat.sor') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                    },
                    body: JSON.stringify({ soru: q })
                });
                if (!r.ok) throw new Error('Yanıt alınamadı');
                const d = await r.json();
                this.yanit = d.yanit || '';
                this.kaynaklar = d.kaynaklar || [];
            } catch (e) {
                this.hata = 'Şu an yanıt alınamadı, lütfen tekrar deneyin.';
            } finally {
                this.yukleniyor = false;
            }
        }
    }"
    class="mehse-yuzen-asistan"
    style="position:fixed;right:1rem;bottom:4.6rem;z-index:55"
>
    <div
        x-show="acik"
        x-transition
        style="width:min(calc(100vw - 2rem),23.5rem);background:#ffffff;color:#111827;border-radius:1rem;border:1px solid #cbd5e1;box-shadow:0 20px 50px rgb(0 0 0 / .28);margin-bottom:.65rem;overflow:hidden"
        role="dialog" aria-label="İSG Mevzuat Asistanı"
    >
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem .95rem;background:#1e3a8a;color:#ffffff">
            <div>
                <div style="font-weight:800;font-size:.88rem">⚖️ İSG Mevzuat Asistanı</div>
                <div style="font-size:.7rem;opacity:.85">6331 s.K. &amp; yönetmelikler hakkında anında sorun</div>
            </div>
            <button type="button" x-on:click="acik = false" aria-label="Kapat"
                style="border:none;background:transparent;color:#fff;font-size:1.05rem;cursor:pointer;padding:.2rem .4rem">✕</button>
        </div>

        <div style="padding:.8rem .9rem;display:flex;flex-direction:column;gap:.55rem;max-height:24rem;overflow-y:auto">
            <div style="display:flex;flex-wrap:wrap;gap:.3rem">
                @foreach (array_slice(\App\Support\GeminiMevzuatDanismani::HAZIR_SORULAR, 0, 3) as $h)
                    <button type="button"
                        x-on:click="sorGonder(@js($h['soru']))"
                        style="font-size:.7rem;padding:.22rem .5rem;border-radius:999px;border:1px solid #bfdbfe;background:#eff6ff;color:#1e40af;cursor:pointer;text-align:left">
                        {{ \Illuminate\Support\Str::limit($h['soru'], 44) }}
                    </button>
                @endforeach
            </div>

            <div style="display:flex;gap:.35rem">
                <input type="text" x-model="soru" maxlength="500"
                    x-on:keydown.enter.prevent="sorGonder()"
                    placeholder="Mevzuat sorunuzu yazın..."
                    style="flex:1;padding:.45rem .6rem;border-radius:.45rem;border:1px solid #cbd5e1;font-size:.8rem;color:#111827;background:#fff">
                <button type="button" x-on:click="sorGonder()" :disabled="yukleniyor"
                    style="padding:.45rem .75rem;border-radius:.45rem;border:none;background:#2563eb;color:#fff;font-size:.8rem;font-weight:700;cursor:pointer">
                    <span x-text="yukleniyor ? '...' : 'Sor'">Sor</span>
                </button>
            </div>

            <template x-if="hata">
                <div style="font-size:.78rem;color:#b91c1c" x-text="hata"></div>
            </template>

            <template x-if="yanit">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:.6rem;padding:.65rem .75rem;font-size:.79rem;line-height:1.45;white-space:pre-line;color:#1e293b" x-text="yanit"></div>
            </template>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end">
        <button
            type="button"
            x-on:click="acik = !acik"
            style="display:inline-flex;align-items:center;gap:.4rem;padding:.55rem .95rem;border-radius:999px;border:none;background:#1e3a8a;color:#ffffff;font-size:.8rem;font-weight:700;cursor:pointer;box-shadow:0 8px 24px rgb(30 58 138 / .35)"
            title="İSG Mevzuat Asistanı (6331 & Yönetmelikler)"
        >
            <span>⚖️</span>
            <span x-text="acik ? 'Asistanı Kapat' : 'İSG Asistan'">İSG Asistan</span>
        </button>
    </div>
</div>
@endauth
