{{-- Üst bar hızlı uzman notları çekmecesi — sayfa yükünde sorgu yapmaz, açılınca /mehse/notlar'dan yükler --}}
<div
    x-data="{
        acik: false,
        yuklendi: false,
        yukleniyor: false,
        notlar: [],
        firmalar: [],
        metin: '',
        etiket: 'genel',
        firmaId: '',
        filtre: 'tumu',
        async ac() {
            this.acik = true;
            if (!this.yuklendi) {
                this.yukleniyor = true;
                try {
                    const r = await fetch('{{ route('mehse.notlar.liste') }}', { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    this.notlar = d.notlar || [];
                    this.firmalar = d.firmalar || [];
                    this.yuklendi = true;
                } finally {
                    this.yukleniyor = false;
                }
            }
        },
        async ekle() {
            if (!this.metin.trim()) return;
            const r = await fetch('{{ route('mehse.notlar.ekle') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                },
                body: JSON.stringify({ metin: this.metin, etiket: this.etiket, firma_id: this.firmaId ? Number(this.firmaId) : null })
            });
            const d = await r.json();
            this.notlar = d.notlar || this.notlar;
            this.metin = '';
        },
        async toggle(id) {
            const r = await fetch('{{ url('/mehse/notlar') }}/' + encodeURIComponent(id) + '/tamamla', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }
            });
            const d = await r.json();
            this.notlar = d.notlar || this.notlar;
        },
        async sil(id) {
            const r = await fetch('{{ url('/mehse/notlar') }}/' + encodeURIComponent(id), {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }
            });
            const d = await r.json();
            this.notlar = d.notlar || this.notlar;
        },
        get filtreli() {
            return this.filtre === 'tumu' ? this.notlar : this.notlar.filter(n => n.etiket === this.filtre);
        }
    }"
    style="display:inline-flex;align-items:center"
>
    <button
        type="button"
        class="fi-topbar-item-btn"
        aria-label="Notlarım"
        x-on:click="ac()"
        x-tooltip="{ content: 'Hızlı Notlarım', theme: $store.theme }"
    >
        <x-filament::icon icon="heroicon-o-pencil-square" class="fi-icon fi-size-md" />
    </button>

    <template x-teleport="body">
        <div
            x-show="acik"
            x-transition.opacity
            x-on:click.self="acik = false"
            x-on:keydown.escape.window="acik = false"
            style="position:fixed;inset:0;z-index:85;background:rgb(15 23 42 / .5);display:flex;justify-content:flex-end"
            role="dialog" aria-modal="true" aria-label="Hızlı Notlarım"
        >
            <div style="background:#ffffff;color:#111827;width:min(100%,26rem);height:100%;display:flex;flex-direction:column;box-shadow:-12px 0 40px rgb(0 0 0 / .25)">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.95rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8fafc">
                    <div>
                        <div style="font-weight:800;font-size:.98rem;color:#0f172a">📝 Hızlı Notlarım</div>
                        <div style="font-size:.74rem;color:#64748b">Hesabınıza kayıtlı, tüm cihazlarda senkron uzman notları</div>
                    </div>
                    <button type="button" x-on:click="acik = false" aria-label="Kapat"
                        style="border:none;background:transparent;font-size:1.15rem;cursor:pointer;color:#475569;padding:.25rem .45rem">✕</button>
                </div>

                <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;display:flex;flex-direction:column;gap:.5rem">
                    <textarea x-model="metin" rows="2" placeholder="Saha notu, hatırlatma veya firma görevi yazın..."
                        style="width:100%;padding:.5rem .65rem;border-radius:.5rem;border:1px solid #cbd5e1;font-size:.82rem;resize:vertical;color:#111827;background:#fff"></textarea>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.45rem">
                        <select x-model="etiket" style="padding:.4rem .5rem;border-radius:.45rem;border:1px solid #cbd5e1;font-size:.78rem;background:#fff;color:#111827">
                            @foreach (\App\Support\UzmanNotlari::ETIKETLER as $kod => $ad)
                                <option value="{{ $kod }}">{{ $ad }}</option>
                            @endforeach
                        </select>
                        <select x-model="firmaId" style="padding:.4rem .5rem;border-radius:.45rem;border:1px solid #cbd5e1;font-size:.78rem;background:#fff;color:#111827">
                            <option value="">— Firma (Opsiyonel) —</option>
                            <template x-for="f in firmalar" :key="f.id">
                                <option :value="f.id" x-text="f.unvan"></option>
                            </template>
                        </select>
                    </div>
                    <button type="button" x-on:click="ekle()"
                        style="padding:.48rem .9rem;border-radius:.5rem;border:none;background:#2563eb;color:#ffffff;font-size:.82rem;font-weight:700;cursor:pointer">
                        + Not Ekle
                    </button>
                </div>

                <div style="display:flex;gap:.3rem;flex-wrap:wrap;padding:.6rem 1.1rem;border-bottom:1px solid #f1f5f9">
                    <button type="button" x-on:click="filtre = 'tumu'"
                        :style="filtre === 'tumu' ? 'background:#2563eb;color:#fff;border-color:#2563eb' : 'background:#f8fafc;color:#334155;border-color:#cbd5e1'"
                        style="font-size:.72rem;font-weight:600;padding:.2rem .55rem;border-radius:999px;border:1px solid;cursor:pointer">Tümü</button>
                    @foreach (\App\Support\UzmanNotlari::ETIKETLER as $kod => $ad)
                        <button type="button" x-on:click="filtre = '{{ $kod }}'"
                            :style="filtre === '{{ $kod }}' ? 'background:#2563eb;color:#fff;border-color:#2563eb' : 'background:#f8fafc;color:#334155;border-color:#cbd5e1'"
                            style="font-size:.72rem;font-weight:600;padding:.2rem .55rem;border-radius:999px;border:1px solid;cursor:pointer">{{ $ad }}</button>
                    @endforeach
                </div>

                <div style="flex:1;overflow-y:auto;padding:.75rem 1.1rem;display:flex;flex-direction:column;gap:.55rem">
                    <template x-if="yukleniyor">
                        <div style="text-align:center;color:#64748b;font-size:.82rem;padding:1.5rem">Yükleniyor...</div>
                    </template>
                    <template x-if="!yukleniyor && filtreli.length === 0">
                        <div style="text-align:center;color:#64748b;font-size:.82rem;padding:1.5rem">Henüz not bulunmuyor.</div>
                    </template>
                    <template x-for="n in filtreli" :key="n.id">
                        <div style="border:1px solid #e2e8f0;border-radius:.6rem;padding:.6rem .75rem;background:#f8fafc">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.45rem">
                                <label style="display:flex;gap:.5rem;align-items:flex-start;cursor:pointer;flex:1">
                                    <input type="checkbox" :checked="n.tamam" x-on:change="toggle(n.id)" style="margin-top:.2rem">
                                    <span style="font-size:.82rem;line-height:1.4" :style="n.tamam ? 'text-decoration:line-through;color:#94a3b8' : 'color:#0f172a'" x-text="n.metin"></span>
                                </label>
                                <button type="button" x-on:click="sil(n.id)" style="border:none;background:none;color:#dc2626;cursor:pointer;font-size:.82rem" title="Sil">✕</button>
                            </div>
                            <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;margin-top:.35rem;font-size:.7rem;color:#64748b">
                                <span style="padding:.06rem .45rem;border-radius:999px;background:#e0e7ff;color:#1e40af;font-weight:700" x-text="n.etiket"></span>
                                <template x-if="n.firma_adi">
                                    <span style="font-weight:600;color:#0f766e" x-text="'🏢 ' + n.firma_adi"></span>
                                </template>
                                <span style="margin-left:auto" x-text="n.tarih"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
