@php
    $kutu = 'border:1px solid rgb(107 114 128 / .25);border-radius:.75rem;padding:.75rem';
    $girdi = 'width:100%;padding:.4rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;font-size:.8rem';
    $btn = 'padding:.32rem .55rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .3);font-size:.76rem;background:transparent;cursor:pointer;white-space:nowrap';
@endphp

<x-filament-panels::page>
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
        <script>
            window.krokiEditoru = function (veri, ayar) {
                const W = ayar.genislik, H = ayar.yukseklik, IZ = 10;
                const kopya = (o) => JSON.parse(JSON.stringify(o));
                const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                const EMOJI = 'Segoe UI Emoji, Apple Color Emoji, Noto Color Emoji, sans-serif';

                return {
                    d: { duvarlar: veri.duvarlar || [], semboller: veri.semboller || [], ogeler: veri.ogeler || { odalar: [], yollar: [], metinler: [] }, antet: veri.antet || {} },
                    bilgi: veri.bilgi || {},
                    ayar,
                    arac: 'secim',
                    kategori: 'kacis',
                    arama: '',
                    secili: null,          // {tur, i}
                    zoom: 1,
                    gecmis: [], ileri: [],
                    taslak: null,           // çizim önizlemesi
                    surukle: null,
                    altlikVeri: null,
                    degisti: false,
                    kaydediliyor: false,
                    disaAktariliyor: false,

                    baslat() {
                        ['odalar', 'yollar', 'metinler'].forEach((k) => { this.d.ogeler[k] = this.d.ogeler[k] || []; });
                        const a = this.d.antet;
                        a.antet_goster = a.antet_goster ?? true; a.lejant_goster = a.lejant_goster ?? true; a.izgara = a.izgara ?? true;
                        a.altlik_opaklik = a.altlik_opaklik ?? 0.5; a.baslik = a.baslik || 'ACİL DURUM TAHLİYE KROKİSİ';
                        if (this.bilgi.altlik) {
                            fetch(this.bilgi.altlik).then((r) => r.blob()).then((b) => new Promise((ok) => { const f = new FileReader(); f.onload = () => ok(f.result); f.readAsDataURL(b); }))
                                .then((u) => { this.altlikVeri = u; this.ciz(); }).catch(() => {});
                        }
                        window.addEventListener('keydown', (e) => this.tus(e));
                        window.addEventListener('beforeunload', (e) => { if (this.degisti) { e.preventDefault(); e.returnValue = ''; } });
                        this.ciz();
                    },

                    // ---------- yardımcılar ----------
                    get svg() { return this.$refs.tuval; },
                    nokta(e) {
                        const p = this.svg.createSVGPoint(); p.x = e.clientX; p.y = e.clientY;
                        const q = p.matrixTransform(this.svg.getScreenCTM().inverse());
                        return { x: q.x, y: q.y };
                    },
                    izgara(v) { return Math.round(v / IZ) * IZ; },
                    kaydetGecmis() { this.gecmis.push(JSON.stringify(this.d)); if (this.gecmis.length > 80) this.gecmis.shift(); this.ileri = []; this.degisti = true; },
                    geriAl() { if (!this.gecmis.length) return; this.ileri.push(JSON.stringify(this.d)); this.d = JSON.parse(this.gecmis.pop()); this.secili = null; this.ciz(); },
                    yinele() { if (!this.ileri.length) return; this.gecmis.push(JSON.stringify(this.d)); this.d = JSON.parse(this.ileri.pop()); this.secili = null; this.ciz(); },
                    liste(tur) { return { duvar: this.d.duvarlar, sembol: this.d.semboller, oda: this.d.ogeler.odalar, yol: this.d.ogeler.yollar, metin: this.d.ogeler.metinler }[tur]; },
                    seciliOge() { return this.secili ? this.liste(this.secili.tur)[this.secili.i] : null; },
                    sembolListesi() {
                        const q = this.arama.toLocaleLowerCase('tr');
                        return Object.entries(this.ayar.semboller).filter(([k, s]) => q ? s.ad.toLocaleLowerCase('tr').includes(q) : s.kategori === this.kategori);
                    },
                    aracSec(a) { this.arac = a; this.taslak = null; this.secili = null; this.ciz(); },

                    // ---------- ekleme ----------
                    sembolEkle(tip) {
                        this.kaydetGecmis();
                        const kb = this.$refs.kap.getBoundingClientRect(), sb = this.svg.getBoundingClientRect();
                        // Tuvalin ekranda görünen kısmının (kaydırma alanı ∩ SVG) ortası
                        const merkez = this.nokta({
                            clientX: (Math.max(kb.left, sb.left) + Math.min(kb.right, sb.right)) / 2,
                            clientY: (Math.max(kb.top, sb.top) + Math.min(kb.bottom, sb.bottom)) / 2,
                        });
                        this.d.semboller.push({ tip, x: this.izgara(merkez.x), y: this.izgara(merkez.y), r: 0, s: 1, etiket: null });
                        this.arac = 'secim';
                        this.secili = { tur: 'sembol', i: this.d.semboller.length - 1 };
                        this.ciz();
                    },

                    // ---------- işaretçi olayları ----------
                    basla(e) {
                        if (e.button !== 0) return;
                        const p = this.nokta(e);
                        const hedef = e.target.closest('[data-tur]');
                        if (this.arac === 'secim') {
                            if (hedef && hedef.dataset.tur === 'tutamac') {
                                this.kaydetGecmis(); this.surukle = { tur: 'boyut', i: this.secili.i, bas: p, ilk: kopya(this.seciliOge()) };
                            } else if (hedef) {
                                this.secili = { tur: hedef.dataset.tur, i: +hedef.dataset.i };
                                this.surukle = { tur: 'tasi', bas: p, ilk: kopya(this.seciliOge()), oynadi: false, onceki: JSON.stringify(this.d) };
                            } else { this.secili = null; }
                            this.svg.setPointerCapture(e.pointerId);
                            this.ciz(); return;
                        }
                        const g = { x: this.izgara(p.x), y: this.izgara(p.y) };
                        if (this.arac === 'duvar') {
                            if (!this.taslak) { this.taslak = { tur: 'duvar', x: g.x, y: g.y, sx: g.x, sy: g.y }; }
                            else {
                                const t = this.taslak; let { x, y } = g;
                                if (Math.abs(x - t.x) >= Math.abs(y - t.y)) y = t.y; else x = t.x;
                                if (x !== t.x || y !== t.y) { this.kaydetGecmis(); this.d.duvarlar.push({ x1: t.x, y1: t.y, x2: x, y2: y }); }
                                this.taslak = { tur: 'duvar', x, y, sx: x, sy: y };
                            }
                        } else if (this.arac === 'oda') {
                            this.taslak = { tur: 'oda', x: g.x, y: g.y, w: 0, h: 0 }; this.svg.setPointerCapture(e.pointerId);
                        } else if (this.arac === 'yol') {
                            if (!this.taslak) this.taslak = { tur: 'yol', noktalar: [[g.x, g.y]], imlec: [g.x, g.y] };
                            else this.taslak.noktalar.push([g.x, g.y]);
                        } else if (this.arac === 'metin') {
                            const m = prompt('Krokiye yazılacak metin:', 'TOPLANMA ALANI');
                            if (m) { this.kaydetGecmis(); this.d.ogeler.metinler.push({ x: g.x, y: g.y, metin: m.slice(0, 120), boyut: 20, renk: '#111827', r: 0 }); this.arac = 'secim'; this.secili = { tur: 'metin', i: this.d.ogeler.metinler.length - 1 }; }
                        }
                        this.ciz();
                    },
                    hareket(e) {
                        const p = this.nokta(e);
                        if (this.surukle) {
                            const s = this.surukle, o = this.seciliOge(); if (!o) return;
                            const dx = this.izgara(p.x - s.bas.x), dy = this.izgara(p.y - s.bas.y);
                            if (s.tur === 'boyut') { o.w = Math.max(20, s.ilk.w + dx); o.h = Math.max(20, s.ilk.h + dy); }
                            else {
                                if (!s.oynadi && (dx || dy)) { this.gecmis.push(s.onceki); s.oynadi = true; this.ileri = []; this.degisti = true; }
                                if (this.secili.tur === 'duvar') { o.x1 = s.ilk.x1 + dx; o.y1 = s.ilk.y1 + dy; o.x2 = s.ilk.x2 + dx; o.y2 = s.ilk.y2 + dy; }
                                else if (this.secili.tur === 'yol') { o.noktalar = s.ilk.noktalar.map(([x, y]) => [x + dx, y + dy]); }
                                else { o.x = s.ilk.x + dx; o.y = s.ilk.y + dy; }
                            }
                            this.ciz(); return;
                        }
                        if (!this.taslak) return;
                        const g = { x: this.izgara(p.x), y: this.izgara(p.y) };
                        if (this.taslak.tur === 'duvar') {
                            let { x, y } = g; const t = this.taslak;
                            if (Math.abs(x - t.x) >= Math.abs(y - t.y)) y = t.y; else x = t.x;
                            t.sx = x; t.sy = y;
                        } else if (this.taslak.tur === 'oda') { this.taslak.w = g.x - this.taslak.x; this.taslak.h = g.y - this.taslak.y; }
                        else if (this.taslak.tur === 'yol') { this.taslak.imlec = [g.x, g.y]; }
                        this.ciz();
                    },
                    bitir(e) {
                        if (this.surukle) { this.surukle = null; return; }
                        if (this.taslak && this.taslak.tur === 'oda') {
                            let { x, y, w, h } = this.taslak;
                            if (w < 0) { x += w; w = -w; } if (h < 0) { y += h; h = -h; }
                            if (w >= 20 && h >= 20) {
                                this.kaydetGecmis();
                                this.d.ogeler.odalar.push({ x, y, w, h, etiket: 'ODA / BÖLÜM', renk: '#e2e8f0' });
                                this.secili = { tur: 'oda', i: this.d.ogeler.odalar.length - 1 }; this.arac = 'secim';
                                this.$nextTick(() => this.$refs.etiketGirdi?.select());
                            }
                            this.taslak = null; this.ciz();
                        }
                    },
                    cift() {
                        if (this.taslak?.tur === 'yol') {
                            const n = this.taslak.noktalar.filter((v, i, a) => i === 0 || v[0] !== a[i - 1][0] || v[1] !== a[i - 1][1]);
                            if (n.length >= 2) { this.kaydetGecmis(); this.d.ogeler.yollar.push({ noktalar: n }); }
                            this.taslak = null; this.ciz();
                        } else if (this.taslak?.tur === 'duvar') { this.taslak = null; this.ciz(); }
                        else if (this.secili && ['sembol', 'oda', 'metin'].includes(this.secili.tur)) { this.$refs.etiketGirdi?.focus(); }
                    },
                    tus(e) {
                        const yazim = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
                        if (e.key === 'Escape') { if (this.taslak?.tur === 'yol') return this.cift(); this.taslak = null; this.secili = null; this.ciz(); return; }
                        if (yazim) return;
                        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') { e.preventDefault(); e.shiftKey ? this.yinele() : this.geriAl(); return; }
                        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') { e.preventDefault(); this.yinele(); return; }
                        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') { e.preventDefault(); this.cogalt(); return; }
                        if (['Delete', 'Backspace'].includes(e.key) && this.secili) { e.preventDefault(); this.sil(); return; }
                        const ok = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] }[e.key];
                        if (ok && this.secili) { e.preventDefault(); const k = e.shiftKey ? 10 : 2; this.kaydir(ok[0] * k, ok[1] * k); }
                    },

                    // ---------- seçili öğe işlemleri ----------
                    kaydir(dx, dy) {
                        const o = this.seciliOge(); if (!o) return; this.kaydetGecmis();
                        if (this.secili.tur === 'duvar') { o.x1 += dx; o.x2 += dx; o.y1 += dy; o.y2 += dy; }
                        else if (this.secili.tur === 'yol') { o.noktalar = o.noktalar.map(([x, y]) => [x + dx, y + dy]); }
                        else { o.x += dx; o.y += dy; }
                        this.ciz();
                    },
                    sil() { if (!this.secili) return; this.kaydetGecmis(); this.liste(this.secili.tur).splice(this.secili.i, 1); this.secili = null; this.ciz(); },
                    dondur() { const o = this.seciliOge(); if (!o || !['sembol', 'metin'].includes(this.secili.tur)) return; this.kaydetGecmis(); o.r = ((o.r || 0) + 45) % 360; this.ciz(); },
                    boyut(k) {
                        const o = this.seciliOge(); if (!o) return; this.kaydetGecmis();
                        if (this.secili.tur === 'sembol') o.s = Math.max(0.4, Math.min(4, Math.round(((o.s || 1) + k * 0.2) * 10) / 10));
                        else if (this.secili.tur === 'metin') o.boyut = Math.max(8, Math.min(72, o.boyut + k * 2));
                        this.ciz();
                    },
                    cogalt() {
                        const o = this.seciliOge(); if (!o) return; this.kaydetGecmis(); const y = kopya(o);
                        if (this.secili.tur === 'duvar') { y.x1 += 20; y.x2 += 20; y.y1 += 20; y.y2 += 20; }
                        else if (this.secili.tur === 'yol') y.noktalar = y.noktalar.map(([a, b]) => [a + 20, b + 20]);
                        else { y.x += 20; y.y += 20; }
                        const l = this.liste(this.secili.tur); l.push(y); this.secili = { tur: this.secili.tur, i: l.length - 1 }; this.ciz();
                    },
                    katman(yon) {
                        if (!this.secili) return; const l = this.liste(this.secili.tur); const o = l.splice(this.secili.i, 1)[0]; this.kaydetGecmis();
                        if (yon > 0) { l.push(o); this.secili.i = l.length - 1; } else { l.unshift(o); this.secili.i = 0; }
                        this.ciz();
                    },
                    temizle() { if (!confirm('Kroki tamamen temizlensin mi? (Geri Al ile dönebilirsiniz)')) return; this.kaydetGecmis(); this.d.duvarlar = []; this.d.semboller = []; this.d.ogeler = { odalar: [], yollar: [], metinler: [] }; this.secili = null; this.ciz(); },
                    ozellik(alan, deger) { const o = this.seciliOge(); if (!o) return; o[alan] = deger; this.degisti = true; this.ciz(); },
                    antetDegis() { this.degisti = true; this.ciz(); },

                    // ---------- çizim ----------
                    sembolSvg(tip, s = 1, r = 0, etiket = null) {
                        const c = this.ayar.semboller[tip] || { renk: '#666', sekil: 'kare', ikon: '?' };
                        const ikon = c.ikon || ''; const yazi = /^[\x20-\x7EÇĞİÖŞÜçğıöşü₂]+$/.test(ikon);
                        const metin = (renk, y = 7, boy = 22) => `<text x="0" y="${y}" text-anchor="middle" font-size="${yazi ? (ikon.length > 2 ? 12 : 18) : boy}" font-weight="700" fill="${renk}" font-family="${yazi ? 'Arial, sans-serif' : EMOJI}">${esc(ikon)}</text>`;
                        let g = '';
                        switch (c.sekil) {
                            case 'ucgen': g = `<polygon points="0,-24 25,20 -25,20" fill="#facc15" stroke="#111" stroke-width="3" stroke-linejoin="round"/>` + metin('#111', 14, 18); break;
                            case 'yasak': g = `<circle r="22" fill="#fff" stroke="#dc2626" stroke-width="5"/>` + metin('#111', 7, 20) + `<line x1="-15" y1="-15" x2="15" y2="15" stroke="#dc2626" stroke-width="5"/>`; break;
                            case 'daire': g = `<circle r="22" fill="${c.renk}"/>` + metin('#fff'); break;
                            case 'kapi': g = `<line x1="-22" y1="0" x2="22" y2="0" stroke="#fff" stroke-width="10"/><line x1="-22" y1="0" x2="-22" y2="-44" stroke="${c.renk}" stroke-width="3"/><path d="M -22 -44 A 44 44 0 0 1 22 0" fill="none" stroke="${c.renk}" stroke-width="1.5" stroke-dasharray="4 3"/>`; break;
                            case 'cift_kapi': g = `<line x1="-30" y1="0" x2="30" y2="0" stroke="#fff" stroke-width="10"/><line x1="-30" y1="0" x2="-30" y2="-30" stroke="${c.renk}" stroke-width="3"/><line x1="30" y1="0" x2="30" y2="-30" stroke="${c.renk}" stroke-width="3"/><path d="M -30 -30 A 30 30 0 0 1 0 0 A 30 30 0 0 1 30 -30" fill="none" stroke="${c.renk}" stroke-width="1.5" stroke-dasharray="4 3"/>`; break;
                            case 'merdiven': g = `<rect x="-30" y="-22" width="60" height="44" fill="#fff" stroke="${c.renk}" stroke-width="2"/>` + [-18, -6, 6, 18].map((x) => `<line x1="${x}" y1="-22" x2="${x}" y2="22" stroke="${c.renk}" stroke-width="1.5"/>`).join('') + `<line x1="-26" y1="0" x2="22" y2="0" stroke="${c.renk}" stroke-width="2"/><polygon points="26,0 18,-5 18,5" fill="${c.renk}"/>`; break;
                            default: g = `<rect x="-22" y="-22" width="44" height="44" rx="6" fill="${c.renk}"/>` + metin('#fff');
                        }
                        const lbl = etiket ? `<g transform="scale(${s})"><text x="0" y="40" text-anchor="middle" font-size="12" font-weight="600" fill="#111" font-family="Arial, sans-serif" paint-order="stroke" stroke="#fff" stroke-width="3">${esc(etiket)}</text></g>` : '';
                        return `<g transform="scale(${s}) rotate(${r})">${g}</g>` + lbl;
                    },
                    ciz() {
                        const d = this.d, a = d.antet; const sec = this.secili; let s = '';
                        s += `<defs><pattern id="kizgara" width="${IZ * 5}" height="${IZ * 5}" patternUnits="userSpaceOnUse"><path d="M ${IZ * 5} 0 L 0 0 0 ${IZ * 5}" fill="none" stroke="#e5e7eb" stroke-width="1"/></pattern>`
                            + `<marker id="kok" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="#16a34a"/></marker></defs>`;
                        s += `<rect x="0" y="0" width="${W}" height="${H}" fill="#fff"/>`;
                        if (this.altlikVeri) s += `<image href="${this.altlikVeri}" x="0" y="0" width="${W}" height="${H}" preserveAspectRatio="xMidYMid meet" opacity="${a.altlik_opaklik}"/>`;
                        if (a.izgara && !this.disaAktariliyor) s += `<rect x="0" y="0" width="${W}" height="${H}" fill="url(#kizgara)"/>`;
                        d.ogeler.odalar.forEach((o, i) => {
                            s += `<g data-tur="oda" data-i="${i}" style="cursor:move"><rect x="${o.x}" y="${o.y}" width="${o.w}" height="${o.h}" fill="${o.renk}" fill-opacity="0.55" stroke="#64748b" stroke-width="2"/>`
                                + `<text x="${o.x + o.w / 2}" y="${o.y + o.h / 2 + 5}" text-anchor="middle" font-size="15" font-weight="700" fill="#334155" font-family="Arial, sans-serif">${esc(o.etiket)}</text></g>`;
                        });
                        d.duvarlar.forEach((w, i) => { s += `<line data-tur="duvar" data-i="${i}" x1="${w.x1}" y1="${w.y1}" x2="${w.x2}" y2="${w.y2}" stroke="#1f2937" stroke-width="9" stroke-linecap="square" style="cursor:move"/>`; });
                        d.ogeler.yollar.forEach((y, i) => { s += `<polyline data-tur="yol" data-i="${i}" points="${y.noktalar.map((n) => n.join(',')).join(' ')}" fill="none" stroke="#16a34a" stroke-width="7" stroke-dasharray="18 10" stroke-linecap="round" stroke-linejoin="round" marker-end="url(#kok)" style="cursor:move"/>`; });
                        d.semboller.forEach((o, i) => { s += `<g data-tur="sembol" data-i="${i}" transform="translate(${o.x},${o.y})" style="cursor:move">${this.sembolSvg(o.tip, o.s || 1, o.r || 0, o.etiket)}</g>`; });
                        d.ogeler.metinler.forEach((m, i) => { s += `<text data-tur="metin" data-i="${i}" x="${m.x}" y="${m.y}" transform="rotate(${m.r || 0} ${m.x} ${m.y})" font-size="${m.boyut}" font-weight="700" fill="${m.renk}" font-family="Arial, sans-serif" style="cursor:move">${esc(m.metin)}</text>`; });

                        // taslak (önizleme)
                        const t = this.taslak;
                        if (t?.tur === 'duvar') s += `<line x1="${t.x}" y1="${t.y}" x2="${t.sx}" y2="${t.sy}" stroke="#f59e0b" stroke-width="9" stroke-dasharray="6 4"/><circle cx="${t.x}" cy="${t.y}" r="6" fill="#f59e0b"/>`;
                        if (t?.tur === 'oda') s += `<rect x="${Math.min(t.x, t.x + t.w)}" y="${Math.min(t.y, t.y + t.h)}" width="${Math.abs(t.w)}" height="${Math.abs(t.h)}" fill="#bfdbfe" fill-opacity="0.4" stroke="#3b82f6" stroke-dasharray="6 4" stroke-width="2"/>`;
                        if (t?.tur === 'yol') s += `<polyline points="${[...t.noktalar, t.imlec].map((n) => n.join(',')).join(' ')}" fill="none" stroke="#16a34a" stroke-width="7" stroke-dasharray="18 10" opacity="0.6" marker-end="url(#kok)"/>`;

                        // lejant + antet
                        if (a.lejant_goster) s += this.lejantSvg();
                        if (a.antet_goster) s += this.antetSvg();

                        // seçim çerçevesi
                        if (sec && !this.disaAktariliyor) {
                            const o = this.seciliOge(); let b = null;
                            if (o && sec.tur === 'oda') b = [o.x, o.y, o.w, o.h];
                            if (o && sec.tur === 'duvar') b = [Math.min(o.x1, o.x2) - 8, Math.min(o.y1, o.y2) - 8, Math.abs(o.x2 - o.x1) + 16, Math.abs(o.y2 - o.y1) + 16];
                            if (o && sec.tur === 'sembol') { const k = 30 * (o.s || 1); b = [o.x - k, o.y - k, 2 * k, 2 * k]; }
                            if (o && sec.tur === 'yol') { const xs = o.noktalar.map((n) => n[0]), ys = o.noktalar.map((n) => n[1]); b = [Math.min(...xs) - 8, Math.min(...ys) - 8, Math.max(...xs) - Math.min(...xs) + 16, Math.max(...ys) - Math.min(...ys) + 16]; }
                            if (o && sec.tur === 'metin') b = [o.x - 4, o.y - o.boyut, o.metin.length * o.boyut * 0.6 + 8, o.boyut * 1.3];
                            if (b) s += `<rect x="${b[0] - 4}" y="${b[1] - 4}" width="${b[2] + 8}" height="${b[3] + 8}" fill="none" stroke="#2563eb" stroke-width="2" stroke-dasharray="6 4" pointer-events="none"/>`;
                            if (o && sec.tur === 'oda') s += `<rect data-tur="tutamac" x="${o.x + o.w - 7}" y="${o.y + o.h - 7}" width="14" height="14" fill="#2563eb" style="cursor:nwse-resize"/>`;
                        }
                        this.$refs.cizim.innerHTML = s;
                    },
                    lejantSvg() {
                        const tipler = [...new Set(this.d.semboller.map((o) => o.tip))].filter((t) => this.ayar.semboller[t]);
                        const ekstra = (this.d.ogeler.yollar.length ? 1 : 0);
                        const n = tipler.length + ekstra; if (!n) return '';
                        const kol = n > 12 ? 2 : 1, satir = Math.ceil(n / kol), gen = 230 * kol + 20, yuk = 44 + satir * 30;
                        const x0 = 12, y0 = H - 12 - yuk;
                        let s = `<g><rect x="${x0}" y="${y0}" width="${gen}" height="${yuk}" fill="#fff" stroke="#111" stroke-width="1.5"/><text x="${x0 + 10}" y="${y0 + 24}" font-size="15" font-weight="800" fill="#111" font-family="Arial, sans-serif">LEJANT</text>`;
                        const kalemler = tipler.map((t) => [this.sembolSvg(t, 0.5), this.ayar.semboller[t].ad]);
                        if (ekstra) kalemler.push([`<line x1="-14" y1="0" x2="10" y2="0" stroke="#16a34a" stroke-width="5" stroke-dasharray="8 4" marker-end="url(#kok)"/>`, 'Kaçış Güzergâhı']);
                        kalemler.forEach(([ikon, ad], i) => {
                            const cx = x0 + 22 + Math.floor(i / satir) * 230, cy = y0 + 46 + (i % satir) * 30;
                            s += `<g transform="translate(${cx},${cy})">${ikon}</g><text x="${cx + 22}" y="${cy + 5}" font-size="12.5" fill="#111" font-family="Arial, sans-serif">${esc(ad)}</text>`;
                        });
                        return s + '</g>';
                    },
                    antetSvg() {
                        const a = this.d.antet, b = this.bilgi, gen = 440, yuk = 176, x0 = W - gen - 12, y0 = H - yuk - 12;
                        const satir = (y, etiket, deger) => `<text x="${x0 + 10}" y="${y}" font-size="11" fill="#555" font-family="Arial, sans-serif">${etiket}</text><text x="${x0 + 118}" y="${y}" font-size="12" font-weight="600" fill="#111" font-family="Arial, sans-serif">${esc(deger || '—')}</text>`;
                        return `<g><rect x="${x0}" y="${y0}" width="${gen}" height="${yuk}" fill="#fff" stroke="#111" stroke-width="1.5"/>`
                            + `<rect x="${x0}" y="${y0}" width="${gen}" height="30" fill="#15803d"/><text x="${x0 + gen / 2}" y="${y0 + 20}" text-anchor="middle" font-size="14" font-weight="800" fill="#fff" font-family="Arial, sans-serif">${esc(a.baslik)}</text>`
                            + satir(y0 + 50, 'İşyeri', (b.firma || '').length > 44 ? b.firma.slice(0, 43) + '…' : b.firma) + satir(y0 + 68, 'Adres', (b.adres || '').length > 44 ? b.adres.slice(0, 43) + '…' : b.adres) + satir(y0 + 86, 'Kat / Bölüm', a.kat)
                            + satir(y0 + 104, 'Hazırlayan', a.hazirlayan) + satir(y0 + 122, 'Onaylayan', a.onaylayan)
                            + satir(y0 + 140, 'Tarih / Rev.', `${b.tarih || ''}${a.revizyon ? ' · Rev. ' + a.revizyon : ''}`)
                            + `<text x="${x0 + 10}" y="${y0 + 163}" font-size="10" fill="#555" font-family="Arial, sans-serif">${esc((a.notlar || 'Acil durumda asansör kullanmayınız. Toplanma alanında yoklama veriniz.').slice(0, 80))}</text></g>`;
                    },

                    // ---------- dışa aktarım ----------
                    async pngVerisi(olcek = 2) {
                        this.disaAktariliyor = true; const sec = this.secili; this.secili = null; this.ciz();
                        const kaynak = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}">${new XMLSerializer().serializeToString(this.$refs.cizim)}</svg>`;
                        this.disaAktariliyor = false; this.secili = sec; this.ciz();
                        const url = URL.createObjectURL(new Blob([kaynak], { type: 'image/svg+xml;charset=utf-8' }));
                        const img = new Image();
                        await new Promise((ok, hata) => { img.onload = ok; img.onerror = hata; img.src = url; });
                        const c = document.createElement('canvas'); c.width = W * olcek; c.height = H * olcek;
                        const x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(img, 0, 0, c.width, c.height);
                        URL.revokeObjectURL(url);
                        return c.toDataURL('image/png');
                    },
                    async kaydet() {
                        this.kaydediliyor = true;
                        try {
                            let png = null; try { png = await this.pngVerisi(1.5); } catch (e) { console.warn('PNG üretilemedi', e); }
                            await this.$wire.kaydet(kopya(this.d), png);
                            this.degisti = false;
                        } finally { this.kaydediliyor = false; }
                    },
                    async pngIndir() { const u = await this.pngVerisi(2); const l = document.createElement('a'); l.href = u; l.download = 'acil-durum-krokisi.png'; l.click(); },
                    async yazdir() {
                        const u = await this.pngVerisi(2); const w = window.open('', '_blank'); if (!w) return;
                        w.document.write(`<html><head><title>Acil Durum Krokisi</title><style>${'@'}page{size:A4 landscape;margin:8mm}body{margin:0}img{width:100%}<\/style><\/head><body><img src="${u}" onload="setTimeout(()=>{print()},200)"><\/body><\/html>`);
                        w.document.close();
                    },
                    projeIndir() { const l = document.createElement('a'); l.href = URL.createObjectURL(new Blob([JSON.stringify({ mehse_kroki: 2, ...this.d }, null, 1)], { type: 'application/json' })); l.download = 'acil-durum-krokisi-proje.json'; l.click(); },
                    projeYukle(e) {
                        const f = e.target.files[0]; if (!f) return; const r = new FileReader();
                        r.onload = () => { try { const v = JSON.parse(r.result); if (!v.duvarlar || !v.semboller) throw 0; this.kaydetGecmis(); this.d = { duvarlar: v.duvarlar, semboller: v.semboller, ogeler: v.ogeler || { odalar: [], yollar: [], metinler: [] }, antet: { ...this.d.antet, ...(v.antet || {}) } }; this.secili = null; this.ciz(); } catch (x) { alert('Geçerli bir kroki proje dosyası değil.'); } };
                        r.readAsText(f); e.target.value = '';
                    },
                };
            };
        </script>

        <div wire:ignore x-data="krokiEditoru(@js($this->editorVerisi()), @js(config('isg.kroki')))" x-init="baslat()" style="display:flex;flex-direction:column;gap:.6rem">
            {{-- Araç çubuğu --}}
            <div style="{{ $kutu }};display:flex;flex-wrap:wrap;gap:.35rem;align-items:center;padding:.5rem .6rem">
                <template x-for="a in [['secim','↖ Seç / Taşı'],['duvar','🧱 Duvar'],['oda','▭ Oda / Bölüm'],['yol','➜ Kaçış Yolu'],['metin','🔤 Metin']]" :key="a[0]">
                    <button type="button" style="{{ $btn }}" :style="arac === a[0] ? { background: '#2563eb', color: '#fff', borderColor: '#2563eb' } : {}" x-on:click="aracSec(a[0])" x-text="a[1]"></button>
                </template>
                <span style="width:1px;height:22px;background:rgb(107 114 128 / .3);margin:0 .2rem"></span>
                <button type="button" style="{{ $btn }}" x-on:click="geriAl()" :disabled="!gecmis.length" title="Ctrl+Z">↩ Geri</button>
                <button type="button" style="{{ $btn }}" x-on:click="yinele()" :disabled="!ileri.length" title="Ctrl+Y">↪ İleri</button>
                <span style="width:1px;height:22px;background:rgb(107 114 128 / .3);margin:0 .2rem"></span>
                <button type="button" style="{{ $btn }}" x-on:click="zoom = Math.min(3, zoom + 0.25)">🔍+</button>
                <button type="button" style="{{ $btn }}" x-on:click="zoom = Math.max(0.5, zoom - 0.25)">🔍−</button>
                <button type="button" style="{{ $btn }}" x-on:click="zoom = 1">1:1</button>
                <label style="{{ $btn }};display:flex;gap:.25rem;align-items:center"><input type="checkbox" x-model="d.antet.izgara" x-on:change="antetDegis()"> Izgara</label>
                <label style="{{ $btn }};display:flex;gap:.25rem;align-items:center"><input type="checkbox" x-model="d.antet.lejant_goster" x-on:change="antetDegis()"> Lejant</label>
                <label style="{{ $btn }};display:flex;gap:.25rem;align-items:center"><input type="checkbox" x-model="d.antet.antet_goster" x-on:change="antetDegis()"> Antet</label>
                <span style="flex:1"></span>
                <button type="button" style="{{ $btn }}" x-on:click="pngIndir()">📥 PNG</button>
                <button type="button" style="{{ $btn }}" x-on:click="yazdir()">🖨 Yazdır</button>
                <button type="button" style="{{ $btn }}" x-on:click="projeIndir()" title="Projeyi JSON dosyası olarak indir">💾 Proje</button>
                <label style="{{ $btn }}" title="Daha önce indirilen JSON projesini yükle">📂 Yükle<input type="file" accept=".json,application/json" style="display:none" x-on:change="projeYukle($event)"></label>
                <button type="button" style="{{ $btn }};color:#b91c1c" x-on:click="temizle()">🗑 Temizle</button>
                <button type="button" style="{{ $btn }};background:#15803d;color:#fff;border-color:#15803d;font-weight:700" x-on:click="kaydet()" :disabled="kaydediliyor">
                    <span x-text="kaydediliyor ? 'Kaydediliyor…' : (degisti ? '✔ Kaydet •' : '✔ Kaydet')"></span>
                </button>
            </div>

            <div style="display:grid;grid-template-columns:230px minmax(0,1fr) 250px;gap:.6rem;align-items:start">
                {{-- İşaret kütüphanesi --}}
                <div style="{{ $kutu }};padding:.5rem;max-height:78vh;overflow:auto">
                    <input type="search" x-model="arama" placeholder="İşaret ara…" style="{{ $girdi }};margin-bottom:.4rem">
                    <div style="display:flex;flex-wrap:wrap;gap:.25rem;margin-bottom:.4rem" x-show="!arama">
                        <template x-for="kt in Object.entries(ayar.kategoriler)" :key="kt[0]">
                            <button type="button" style="{{ $btn }};font-size:.68rem;padding:.2rem .4rem" :style="kategori === kt[0] ? { background: '#0f766e', color: '#fff', borderColor: '#0f766e' } : {}" x-on:click="kategori = kt[0]" x-text="kt[1]"></button>
                        </template>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem">
                        <template x-for="sm in sembolListesi()" :key="sm[0]">
                            <button type="button" x-on:click="sembolEkle(sm[0])" :title="sm[1].ad"
                                style="display:flex;flex-direction:column;align-items:center;gap:.15rem;padding:.35rem .2rem;border:1px solid rgb(107 114 128 / .2);border-radius:.45rem;background:transparent;cursor:pointer">
                                <svg viewBox="-34 -34 68 68" width="40" height="40" x-html="sembolSvg(sm[0])"></svg>
                                <span style="font-size:.64rem;line-height:1.15;text-align:center" x-text="sm[1].ad"></span>
                            </button>
                        </template>
                    </div>
                    <p style="font-size:.68rem;color:rgb(107 114 128);margin-top:.5rem">İşarete tıklayınca görünür alanın ortasına eklenir; sürükleyerek yerleştirin.</p>
                </div>

                {{-- Tuval --}}
                <div x-ref="kap" style="{{ $kutu }};padding:0;overflow:auto;max-height:78vh;background:#f1f5f9">
                    <svg x-ref="tuval" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ config('isg.kroki.genislik') }} {{ config('isg.kroki.yukseklik') }}"
                         :style="`width:${zoom * 100}%;height:auto;display:block;touch-action:none;cursor:${arac === 'secim' ? 'default' : 'crosshair'}`"
                         x-on:pointerdown="basla($event)" x-on:pointermove="hareket($event)" x-on:pointerup="bitir($event)" x-on:dblclick="cift()">
                        <g x-ref="cizim"></g>
                    </svg>
                </div>

                {{-- Özellikler + antet --}}
                <div style="display:flex;flex-direction:column;gap:.6rem">
                    <div style="{{ $kutu }}">
                        <div style="font-weight:700;font-size:.8rem;margin-bottom:.4rem">Seçili öğe</div>
                        <template x-if="!secili">
                            <p style="font-size:.74rem;color:rgb(107 114 128);line-height:1.45">
                                <span x-show="arac === 'secim'">Bir öğeye tıklayıp sürükleyin. Delete: sil · Ctrl+D: çoğalt · oklar: kaydır.</span>
                                <span x-show="arac === 'duvar'">Tıkla-tıkla duvar çizin (90°'ye kenetlenir, zincirleme devam eder). Çift tık / Esc bitirir.</span>
                                <span x-show="arac === 'oda'">Sürükleyerek oda / bölüm dikdörtgeni çizin.</span>
                                <span x-show="arac === 'yol'">Kaçış güzergâhının noktalarına tıklayın; çift tık / Esc ile bitirin (ok yönü son noktadır).</span>
                                <span x-show="arac === 'metin'">Metnin yazılacağı yere tıklayın.</span>
                            </p>
                        </template>
                        <template x-if="secili">
                            <div style="display:flex;flex-direction:column;gap:.4rem">
                                <div style="font-size:.74rem;color:rgb(107 114 128)" x-text="({sembol: ayar.semboller[seciliOge()?.tip]?.ad, oda: 'Oda / bölüm', duvar: 'Duvar', yol: 'Kaçış yolu', metin: 'Metin'})[secili.tur]"></div>
                                <template x-if="['sembol','oda'].includes(secili.tur)">
                                    <input x-ref="etiketGirdi" type="text" placeholder="Etiket (ör. ÇIKIŞ 1)" :value="seciliOge()?.etiket ?? ''" x-on:input="ozellik('etiket', $event.target.value)" style="{{ $girdi }}">
                                </template>
                                <template x-if="secili.tur === 'metin'">
                                    <input x-ref="etiketGirdi" type="text" :value="seciliOge()?.metin" x-on:input="ozellik('metin', $event.target.value)" style="{{ $girdi }}">
                                </template>
                                <template x-if="['oda','metin'].includes(secili.tur)">
                                    <label style="font-size:.72rem;display:flex;gap:.4rem;align-items:center">Renk <input type="color" :value="seciliOge()?.renk" x-on:input="ozellik('renk', $event.target.value)"></label>
                                </template>
                                <div style="display:flex;flex-wrap:wrap;gap:.25rem">
                                    <button type="button" style="{{ $btn }}" x-show="['sembol','metin'].includes(secili.tur)" x-on:click="dondur()">⟳ 45°</button>
                                    <button type="button" style="{{ $btn }}" x-show="['sembol','metin'].includes(secili.tur)" x-on:click="boyut(1)">＋ Büyüt</button>
                                    <button type="button" style="{{ $btn }}" x-show="['sembol','metin'].includes(secili.tur)" x-on:click="boyut(-1)">－ Küçült</button>
                                    <button type="button" style="{{ $btn }}" x-on:click="cogalt()">⧉ Çoğalt</button>
                                    <button type="button" style="{{ $btn }}" x-on:click="katman(1)">⬆ Öne</button>
                                    <button type="button" style="{{ $btn }}" x-on:click="katman(-1)">⬇ Arkaya</button>
                                    <button type="button" style="{{ $btn }};color:#b91c1c" x-on:click="sil()">✕ Sil</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div style="{{ $kutu }};display:flex;flex-direction:column;gap:.35rem">
                        <div style="font-weight:700;font-size:.8rem">Antet</div>
                        <input type="text" x-model="d.antet.baslik" x-on:input="antetDegis()" placeholder="Başlık" style="{{ $girdi }}">
                        <input type="text" x-model="d.antet.kat" x-on:input="antetDegis()" placeholder="Kat / bölüm (ör. Zemin Kat)" style="{{ $girdi }}">
                        <input type="text" x-model="d.antet.hazirlayan" x-on:input="antetDegis()" placeholder="Hazırlayan" style="{{ $girdi }}">
                        <input type="text" x-model="d.antet.onaylayan" x-on:input="antetDegis()" placeholder="Onaylayan (işveren / vekili)" style="{{ $girdi }}">
                        <input type="text" x-model="d.antet.revizyon" x-on:input="antetDegis()" placeholder="Revizyon no" style="{{ $girdi }}">
                        <textarea x-model="d.antet.notlar" x-on:input="antetDegis()" rows="2" placeholder="Not (ör. Acil durumda asansör kullanmayınız)" style="{{ $girdi }}"></textarea>
                        <template x-if="bilgi.altlik">
                            <label style="font-size:.72rem">Altlık görünürlüğü <input type="range" min="0" max="1" step="0.05" x-model.number="d.antet.altlik_opaklik" x-on:input="antetDegis()" style="width:100%"></label>
                        </template>
                    </div>

                    <div style="{{ $kutu }};font-size:.72rem;color:rgb(107 114 128);line-height:1.45">
                        <strong>Sayım:</strong>
                        <span x-text="`${d.duvarlar.length} duvar · ${d.ogeler.odalar.length} oda · ${d.ogeler.yollar.length} kaçış yolu · ${d.semboller.length} işaret · ${d.ogeler.metinler.length} metin`"></span>
                        <div style="margin-top:.3rem">İşaretler ISO 7010 renk ve biçim kurallarına göre şematiktir; resmî piktogram baskıları için Acil Durum Planı → afişleri kullanın.</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <p style="color:rgb(107 114 128);margin-top:1rem">Kroki çizmek için önce bir firma seçin.</p>
    @endif
</x-filament-panels::page>
