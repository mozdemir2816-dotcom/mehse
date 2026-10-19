{{--
    Fotoğraf üzerinde tehlike noktasını kırmızı çemberle işaretleme (isgceo "Tehlike Çemberi Editörü" benzeri).
    Tuval tarayıcıda çalışır; "Uygula" işaretli JPEG'i Livewire'a yükler (isaretliFoto) ve
    fotoIsaretliUygula(tur, index) taslaktaki orijinalin yerine koyar. Açmak için:
    $dispatch('foto-isaretle', { src, tur: 'yeni'|'kayitli', index }).
--}}
<div
    x-data="{
        acik: false, src: '', tur: '', index: 0, yukleniyor: false, hata: '',
        daireler: [], secili: -1, boyut: 75, kalinlik: 5, renk: '#ef4444', etiket: '',
        surukle: false, fark: { x: 0, y: 0 },
        ac(d) {
            this.src = d.src; this.tur = d.tur; this.index = d.index;
            this.daireler = []; this.secili = -1; this.hata = ''; this.etiket = ''; this.acik = true;
            const im = new Image();
            im.onload = () => {
                const olcek = Math.min(1, 1600 / Math.max(im.naturalWidth, im.naturalHeight));
                this.$nextTick(() => {
                    const c = this.$refs.tuval;
                    c.width = Math.round(im.naturalWidth * olcek);
                    c.height = Math.round(im.naturalHeight * olcek);
                    this.$refs.tuval.resim = im;
                    this.ciz();
                });
            };
            im.onerror = () => { this.hata = 'Fotoğraf açılamadı.'; };
            im.src = d.src;
        },
        k() { return this.$refs.tuval.width / 800; },
        ekle(x, y) {
            this.daireler.push({ x, y, r: this.boyut / 2 * this.k(), w: this.kalinlik * this.k(), c: this.renk, t: this.etiket });
            this.secili = this.daireler.length - 1;
        },
        yeniCember() { this.ekle(this.$refs.tuval.width / 2, this.$refs.tuval.height / 2); this.ciz(); },
        sec(i) {
            this.secili = i;
            const d = this.daireler[i];
            this.boyut = Math.round(d.r * 2 / this.k()); this.kalinlik = Math.round(d.w / this.k());
            this.renk = d.c; this.etiket = d.t;
        },
        guncelle() {
            const d = this.daireler[this.secili];
            if (!d) { return; }
            d.r = this.boyut / 2 * this.k(); d.w = this.kalinlik * this.k(); d.c = this.renk; d.t = this.etiket;
            this.ciz();
        },
        sil() { if (this.secili >= 0) { this.daireler.splice(this.secili, 1); this.secili = -1; this.ciz(); } },
        temizle() { this.daireler = []; this.secili = -1; this.ciz(); },
        nokta(e) {
            const c = this.$refs.tuval, r = c.getBoundingClientRect();
            return { x: (e.clientX - r.left) * c.width / r.width, y: (e.clientY - r.top) * c.height / r.height };
        },
        basla(e) {
            const p = this.nokta(e);
            let bulunan = -1;
            for (let i = this.daireler.length - 1; i >= 0; i--) {
                const d = this.daireler[i];
                if (Math.hypot(p.x - d.x, p.y - d.y) <= d.r + d.w + 12 * this.k()) { bulunan = i; break; }
            }
            if (bulunan >= 0) {
                this.sec(bulunan);
                this.surukle = true;
                this.fark = { x: p.x - this.daireler[bulunan].x, y: p.y - this.daireler[bulunan].y };
                e.target.setPointerCapture(e.pointerId);
            } else {
                this.ekle(p.x, p.y);
            }
            this.ciz();
        },
        tasi(e) {
            if (!this.surukle || this.secili < 0) { return; }
            const p = this.nokta(e), d = this.daireler[this.secili];
            d.x = p.x - this.fark.x; d.y = p.y - this.fark.y;
            this.ciz();
        },
        birak() { this.surukle = false; },
        ciz() {
            const c = this.$refs.tuval, g = c.getContext('2d');
            if (!c.resim) { return; }
            g.clearRect(0, 0, c.width, c.height);
            g.drawImage(c.resim, 0, 0, c.width, c.height);
            this.daireler.forEach((d, i) => {
                g.lineWidth = d.w; g.strokeStyle = d.c;
                g.beginPath(); g.arc(d.x, d.y, d.r, 0, Math.PI * 2); g.stroke();
                if (i === this.secili) {
                    g.save(); g.lineWidth = Math.max(1, this.k() * 2); g.strokeStyle = '#ffffff'; g.setLineDash([8 * this.k(), 6 * this.k()]);
                    g.beginPath(); g.arc(d.x, d.y, d.r + d.w, 0, Math.PI * 2); g.stroke(); g.restore();
                }
                if (d.t) {
                    const boy = Math.round(c.width / 45);
                    g.font = 'bold ' + boy + 'px sans-serif';
                    const gen = g.measureText(d.t).width + boy, y = Math.max(boy + 4, d.y - d.r - d.w - 6);
                    g.fillStyle = d.c; g.fillRect(d.x - gen / 2, y - boy - 2, gen, boy + 8);
                    g.fillStyle = '#ffffff'; g.textAlign = 'center'; g.fillText(d.t, d.x, y + 2);
                }
            });
        },
        uygula() {
            if (!this.daireler.length) { this.acik = false; return; }
            this.secili = -1; this.ciz(); this.hata = '';
            try {
                this.$refs.tuval.toBlob((b) => {
                    if (!b) { this.hata = 'Resim oluşturulamadı.'; return; }
                    this.yukleniyor = true;
                    $wire.upload('isaretliFoto', new File([b], 'isaretli.jpg', { type: 'image/jpeg' }),
                        () => $wire.fotoIsaretliUygula(this.tur, this.index)
                            .then(() => { this.yukleniyor = false; this.acik = false; })
                            .catch(() => { this.yukleniyor = false; this.hata = 'Kaydedilemedi.'; }),
                        () => { this.yukleniyor = false; this.hata = 'Yüklenemedi, tekrar deneyin.'; });
                }, 'image/jpeg', 0.9);
            } catch (err) {
                this.hata = 'Fotoğraf bu adresten işlenemedi (güvenlik kısıtı).';
            }
        },
    }"
    x-on:foto-isaretle.window="ac($event.detail)"
    x-show="acik" x-cloak
    x-on:keydown.escape.window="acik = false"
    style="position:fixed;inset:0;z-index:60;background:rgb(0 0 0 / .6);display:flex;align-items:center;justify-content:center;padding:1rem"
>
    <div style="background:#fff;color:#111827;border-radius:.75rem;max-width:960px;width:100%;max-height:96vh;overflow:auto;padding:1rem">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem">
            <div>
                <div style="font-weight:700">Tehlike Noktası İşaretleme</div>
                <div style="font-size:.75rem;color:#6b7280">Fotoğrafa dokunarak çember ekleyin; çemberi sürükleyerek taşıyın.</div>
            </div>
            <button type="button" x-on:click="acik = false" style="font-size:1.1rem;border:none;background:none;cursor:pointer">✕</button>
        </div>

        <div style="margin-top:.6rem;text-align:center;background:#f3f4f6;border-radius:.5rem">
            <canvas x-ref="tuval" x-on:pointerdown="basla($event)" x-on:pointermove="tasi($event)" x-on:pointerup="birak()" x-on:pointercancel="birak()"
                style="max-width:100%;max-height:60vh;touch-action:none;cursor:crosshair"></canvas>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.6rem;margin-top:.7rem;font-size:.8rem">
            <label>Çap <span x-text="boyut + 'px'"></span>
                <input type="range" min="30" max="300" step="5" x-model.number="boyut" x-on:input="guncelle()" style="width:100%"></label>
            <label>Çizgi <span x-text="kalinlik + 'px'"></span>
                <input type="range" min="2" max="16" step="1" x-model.number="kalinlik" x-on:input="guncelle()" style="width:100%"></label>
            <label>Renk
                <input type="color" x-model="renk" x-on:input="guncelle()" style="display:block;width:100%;height:1.9rem"></label>
            <label>Etiket (isteğe bağlı)
                <input type="text" x-model="etiket" x-on:input="guncelle()" maxlength="40" placeholder="Örn. Korkuluk yok"
                    style="display:block;width:100%;padding:.3rem .5rem;border:1px solid #d1d5db;border-radius:.4rem"></label>
        </div>

        <div x-show="hata" x-text="hata" style="color:#dc2626;font-size:.8rem;margin-top:.5rem"></div>

        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.8rem;justify-content:space-between">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <x-filament::button size="sm" color="gray" x-on:click="yeniCember()">Yeni çember</x-filament::button>
                <x-filament::button size="sm" color="gray" x-on:click="sil()">Seçiliyi sil</x-filament::button>
                <x-filament::button size="sm" color="gray" x-on:click="temizle()">Tümünü temizle</x-filament::button>
            </div>
            <div style="display:flex;gap:.5rem">
                <x-filament::button size="sm" color="gray" x-on:click="acik = false">Vazgeç</x-filament::button>
                <x-filament::button size="sm" x-on:click="uygula()" x-bind:disabled="yukleniyor">
                    <span x-show="!yukleniyor">Çemberleri uygula ve kaydet</span>
                    <span x-show="yukleniyor">Kaydediliyor…</span>
                </x-filament::button>
            </div>
        </div>
    </div>
</div>
