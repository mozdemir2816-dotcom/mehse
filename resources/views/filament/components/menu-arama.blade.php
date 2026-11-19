{{--
    Sol menü arama kutusu (AdminPanelProvider SIDEBAR_NAV_START) — isgpratik'teki
    "Menüde ara...". Görünen menü maddelerini süzer; menüden gizlenip sekme
    şeridine taşınan sayfaları (App\Support\SayfaAileleri) ayrı sonuç olarak listeler.
--}}
@php
    $ekler = collect(\App\Support\SayfaAileleri::aileler())
        ->flatMap(function (array $uyeler) {
            [$anaSinif, $anaEtiket] = $uyeler[0];
            $ust = $anaSinif::getNavigationLabel();

            return collect($uyeler)->slice(1)
                ->filter(fn (array $u) => $u[0]::canAccess())
                ->map(fn (array $u) => [
                    'etiket' => $u[1],
                    'ust' => $ust,
                    'url' => is_subclass_of($u[0], \Filament\Resources\Resource::class) ? $u[0]::getUrl('index') : $u[0]::getUrl(),
                ]);
        })
        ->values()
        ->all();
@endphp

<script>
    window.mehseMenuArama = window.mehseMenuArama || function (ekler) {
        const sade = (m) => (m || '').toLocaleLowerCase('tr')
            .replace(/ı/g, 'i').replace(/ş/g, 's').replace(/ğ/g, 'g')
            .replace(/ü/g, 'u').replace(/ö/g, 'o').replace(/ç/g, 'c').trim();

        return {
            q: '',
            ekler,
            ekSonuclar: [],
            favoriler: [],
            init() {
                // Görsel temaya göre değişen menü yazı rengini kopyala (4 tema, ayrı CSS).
                const etiket = document.querySelector('.fi-sidebar-item-label');
                if (etiket) this.$root.style.color = getComputedStyle(etiket).color;

                try {
                    this.favoriler = JSON.parse(localStorage.getItem('mehse_menu_favoriler') || '[]');
                } catch (e) {
                    this.favoriler = [];
                }
                this.yildizlariBagla();

                // Menü sadeleştirmesinden (11.10.2026) önce kapatılan gruplar tarayıcıda
                // (collapsedGroups) kalırsa, artık kapatma düğmesi olmayan grubun maddeleri
                // görünmez olur ve açılamaz — yalnız daraltılabilir grupları hafızada tut.
                const sidebar = window.Alpine?.store('sidebar');
                if (sidebar && Array.isArray(sidebar.collapsedGroups)) {
                    const daraltilabilir = [...document.querySelectorAll('.fi-sidebar-group')]
                        .filter((g) => g.querySelector('.fi-sidebar-group-collapse-btn'))
                        .map((g) => g.dataset.groupLabel);
                    const temiz = sidebar.collapsedGroups.filter((g) => daraltilabilir.includes(g));
                    if (temiz.length !== sidebar.collapsedGroups.length) sidebar.collapsedGroups = temiz;

                    // Akordeon (29.10.2026): menü çok uzamasın — her seferinde tek grup açık. Açılışta yalnız
                    // geçerli sayfanın grubu (yoksa ilk grup) açık, diğerleri kapalıdır; bir grubu açınca
                    // ötekiler kapanır. Arama sürerken (filtrele) kapalı gruplar zaten açılıp süzülür.
                    if (daraltilabilir.length > 1 && !sidebar.__akordeon) {
                        sidebar.__akordeon = true;
                        const aktif = [...document.querySelectorAll('.fi-sidebar-group')]
                            .find((g) => g.classList.contains('fi-active'))?.dataset.groupLabel;
                        const acik = aktif && daraltilabilir.includes(aktif) ? aktif : daraltilabilir[0];
                        sidebar.collapsedGroups = daraltilabilir.filter((g) => g !== acik);

                        const asil = sidebar.toggleCollapsedGroup.bind(sidebar);
                        sidebar.toggleCollapsedGroup = (etiket) => {
                            if (sidebar.groupIsCollapsed(etiket)) {
                                sidebar.collapsedGroups = daraltilabilir.filter((g) => g !== etiket);
                            } else {
                                asil(etiket);
                            }
                        };
                    }
                }
            },
            filtrele() {
                const q = sade(this.q);
                const nav = this.$root.closest('.fi-sidebar-nav') || document;

                nav.querySelectorAll('.fi-sidebar-group').forEach((grup) => {
                    const grupEslesti = q !== '' && sade(grup.dataset.groupLabel).includes(q);
                    const liste = grup.querySelector('.fi-sidebar-group-items');
                    let gorunen = 0;

                    grup.querySelectorAll('.fi-sidebar-item').forEach((madde) => {
                        const eslesti = q === '' || grupEslesti
                            || sade(madde.querySelector('.fi-sidebar-item-label')?.textContent).includes(q);
                        madde.style.display = eslesti ? '' : 'none';
                        if (eslesti) gorunen++;
                    });

                    grup.style.display = q === '' || gorunen > 0 ? '' : 'none';

                    if (liste) {
                        // Kapalı grup (x-collapse: height 0 + overflow hidden) arama sürerken açılır;
                        // arama bitince kullanıcının kayıtlı aç/kapa durumuna döner.
                        const kapali = window.Alpine?.store('sidebar')?.groupIsCollapsed(grup.dataset.groupLabel);
                        if (q !== '') {
                            liste.hidden = false;
                            liste.style.display = '';
                            liste.style.height = 'auto';
                            liste.style.overflow = 'visible';
                        } else {
                            liste.style.removeProperty('overflow');
                            liste.style.height = kapali ? '0px' : 'auto';
                            liste.style.display = kapali ? 'none' : '';
                            liste.hidden = !! kapali;
                        }
                    }
                });

                this.ekSonuclar = q === '' ? [] : this.ekler.filter((e) => sade(e.etiket + ' ' + e.ust).includes(q));
            },
            temizle() {
                this.q = '';
                this.filtrele();
            },
            favoriMi(url) {
                return this.favoriler.some((f) => f.url === url);
            },
            favoriToggle(url, etiket) {
                if (this.favoriMi(url)) {
                    this.favoriler = this.favoriler.filter((f) => f.url !== url);
                } else {
                    this.favoriler.push({ url, etiket });
                }
                try {
                    localStorage.setItem('mehse_menu_favoriler', JSON.stringify(this.favoriler));
                } catch (e) {}
                this.yildizlariGuncelle();
            },
            yildizlariBagla() {
                const nav = this.$root.closest('.fi-sidebar-nav') || document;
                nav.querySelectorAll('.fi-sidebar-item-btn').forEach((btn) => {
                    if (btn.querySelector('.mehse-fav-btn')) return;
                    const url = btn.getAttribute('href');
                    const etiket = btn.querySelector('.fi-sidebar-item-label')?.textContent?.trim();
                    if (!url || !etiket) return;
                    const star = document.createElement('span');
                    star.className = 'mehse-fav-btn';
                    star.dataset.favUrl = url;
                    star.title = 'Sık kullanılanlara ekle / çıkar';
                    star.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        ev.stopPropagation();
                        this.favoriToggle(url, etiket);
                    });
                    btn.appendChild(star);
                });
                this.yildizlariGuncelle();
            },
            yildizlariGuncelle() {
                document.querySelectorAll('.mehse-fav-btn').forEach((star) => {
                    const secili = this.favoriMi(star.dataset.favUrl);
                    star.textContent = secili ? '★' : '☆';
                    star.classList.toggle('aktif', secili);
                });
            },
        };
    };
</script>

<div x-data="mehseMenuArama(@js($ekler))" x-show="$store.sidebar.isOpen" class="mehse-menu-arama">
    <input
        id="mehse-menu-arama"
        type="search"
        x-model="q"
        x-on:input="filtrele()"
        x-on:keydown.escape="temizle()"
        placeholder="Menüde ara…"
        aria-label="Menüde ara"
        autocomplete="off"
    >
    <ul x-show="ekSonuclar.length" x-cloak class="mehse-menu-arama-ek">
        <template x-for="e in ekSonuclar" :key="e.url">
            <li>
                <a :href="e.url">
                    <span x-text="e.etiket"></span>
                    <small x-text="e.ust"></small>
                </a>
            </li>
        </template>
    </ul>
    <div x-show="favoriler.length && !q" x-cloak class="mehse-menu-favoriler">
        <div class="mehse-menu-favoriler-baslik">★ Sık Kullanılanlar</div>
        <ul>
            <template x-for="f in favoriler" :key="f.url">
                <li>
                    <a :href="f.url" x-text="f.etiket"></a>
                    <button type="button" x-on:click.prevent="favoriToggle(f.url, f.etiket)" title="Kaldır">×</button>
                </li>
            </template>
        </ul>
    </div>
</div>

<style>
    .mehse-menu-favoriler { margin-top: .45rem; padding-top: .35rem; border-top: 1px solid color-mix(in srgb, var(--mehse-sb-metin, currentColor) 15%, transparent); }
    .mehse-menu-favoriler-baslik { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; opacity: .75; margin-bottom: .25rem; color: #f59e0b; }
    .mehse-menu-favoriler ul { list-style: none; margin: 0; padding: 0; display: grid; gap: .15rem; }
    .mehse-menu-favoriler li { display: flex; align-items: center; justify-content: space-between; border-radius: .4rem; background: color-mix(in srgb, var(--mehse-sb-metin, currentColor) 7%, transparent); }
    .mehse-menu-favoriler a { flex: 1; padding: .3rem .55rem; font-size: .78rem; font-weight: 600; color: inherit; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mehse-menu-favoriler button { background: none; border: none; color: inherit; opacity: .55; cursor: pointer; padding: .2rem .45rem; font-size: .85rem; }
    .mehse-menu-favoriler button:hover { opacity: 1; color: #ef4444; }
    .mehse-fav-btn { margin-left: auto; font-size: .85rem; opacity: 0; transition: opacity .15s, color .15s; cursor: pointer; padding: 0 .2rem; color: inherit; }
    .fi-sidebar-item-btn:hover .mehse-fav-btn, .mehse-fav-btn.aktif { opacity: .85; }
    .mehse-fav-btn.aktif { color: #f59e0b; opacity: 1; }
    .mehse-menu-arama { padding: 0 .5rem .5rem; color: var(--mehse-sb-metin, #fff); }
    .mehse-menu-arama input {
        width: 100%; font-size: .8rem; padding: .42rem .65rem; border-radius: .5rem;
        color: var(--mehse-sb-metin, inherit);
        background: color-mix(in srgb, var(--mehse-sb-metin, currentColor) 9%, transparent);
        border: 1px solid color-mix(in srgb, var(--mehse-sb-metin, currentColor) 28%, transparent);
        outline: none;
    }
    .mehse-menu-arama input:focus { border-color: color-mix(in srgb, var(--mehse-sb-metin, currentColor) 65%, transparent); }
    .mehse-menu-arama input::placeholder { color: var(--mehse-sb-soluk, inherit); opacity: .9; }
    .mehse-menu-arama-ek { list-style: none; margin: .4rem 0 0; padding: 0; display: grid; gap: .15rem; }
    .mehse-menu-arama-ek a {
        display: flex; flex-direction: column; padding: .35rem .6rem; border-radius: .4rem;
        font-size: .8rem; color: inherit; text-decoration: none;
        background: color-mix(in srgb, currentColor 5%, transparent);
    }
    .mehse-menu-arama-ek a:hover, .mehse-menu-arama-ek a:focus-visible { background: color-mix(in srgb, currentColor 13%, transparent); }
    .mehse-menu-arama-ek small { font-size: .75rem; opacity: .65; }
</style>
