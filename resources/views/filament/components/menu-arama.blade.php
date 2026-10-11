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
            init() {
                // Görsel temaya göre değişen menü yazı rengini kopyala (4 tema, ayrı CSS).
                const etiket = document.querySelector('.fi-sidebar-item-label');
                if (etiket) this.$root.style.color = getComputedStyle(etiket).color;
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
</div>

<style>
    .mehse-menu-arama { padding: 0 .5rem .5rem; }
    .mehse-menu-arama input {
        width: 100%; font-size: .8rem; padding: .4rem .6rem; border-radius: .5rem;
        color: inherit; background: color-mix(in srgb, currentColor 7%, transparent);
        border: 1px solid color-mix(in srgb, currentColor 22%, transparent); outline: none;
    }
    .mehse-menu-arama input:focus { border-color: color-mix(in srgb, currentColor 55%, transparent); }
    .mehse-menu-arama input::placeholder { color: inherit; opacity: .6; }
    .mehse-menu-arama-ek { list-style: none; margin: .4rem 0 0; padding: 0; display: grid; gap: .15rem; }
    .mehse-menu-arama-ek a {
        display: flex; flex-direction: column; padding: .35rem .6rem; border-radius: .4rem;
        font-size: .8rem; color: inherit; text-decoration: none;
        background: color-mix(in srgb, currentColor 5%, transparent);
    }
    .mehse-menu-arama-ek a:hover, .mehse-menu-arama-ek a:focus-visible { background: color-mix(in srgb, currentColor 13%, transparent); }
    .mehse-menu-arama-ek small { font-size: .68rem; opacity: .65; }
</style>
