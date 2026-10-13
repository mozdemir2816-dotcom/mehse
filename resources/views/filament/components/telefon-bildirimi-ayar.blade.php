{{--
    Ayarlar > Telefon bildirimleri (13.10.2026). Bu cihazda aç/kapat (tarayıcı izni +
    PushManager aboneliği → Ayarlar::pushAboneOl), deneme bildirimi, kayıtlı cihazlar.
    iPhone/iPad: bildirim yalnız Ana Ekrana eklenmiş uygulamada çalışır (iOS 16.4+).
--}}
@php
    /** @var string|null $anahtar */
    /** @var array<int, array{id: int, cihaz: string, eklenme: string, son: ?string}> $cihazlar */
@endphp
<div
    class="mehse-tb"
    x-data="{
        anahtar: @js($anahtar),
        durum: 'yukleniyor',
        mesgul: false,
        abonelik: null,
        async kayit() {
            return Promise.race([
                navigator.serviceWorker.ready,
                new Promise((_, red) => setTimeout(() => red(new Error('sw')), 6000)),
            ]);
        },
        async init() {
            const ios = /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            const kurulu = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
            if (! this.anahtar) { this.durum = 'sunucu'; return; }
            if (ios && ! kurulu) { this.durum = 'ios-kur'; return; }
            if (! ('serviceWorker' in navigator) || ! ('PushManager' in window) || ! ('Notification' in window)) { this.durum = 'desteklenmiyor'; return; }
            if (Notification.permission === 'denied') { this.durum = 'engelli'; return; }
            try {
                this.abonelik = await (await this.kayit()).pushManager.getSubscription();
                this.durum = this.abonelik ? 'acik' : 'kapali';
            } catch (e) { this.durum = 'desteklenmiyor'; }
        },
        anahtarBaytlari() {
            const b = atob(this.anahtar.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - this.anahtar.length % 4) % 4));
            return Uint8Array.from(b, (c) => c.charCodeAt(0));
        },
        cihazAdi() {
            const ua = navigator.userAgent;
            const sistem = /iPhone/.test(ua) ? 'iPhone' : /iPad/.test(ua) ? 'iPad' : /Android/.test(ua) ? 'Android' : /Windows/.test(ua) ? 'Windows' : /Mac/.test(ua) ? 'Mac' : 'Cihaz';
            const tarayici = /SamsungBrowser/.test(ua) ? 'Samsung' : /Edg\//.test(ua) ? 'Edge' : /Chrome|CriOS/.test(ua) ? 'Chrome' : /Firefox|FxiOS/.test(ua) ? 'Firefox' : /Safari/.test(ua) ? 'Safari' : '';
            return [sistem, tarayici].filter(Boolean).join(' · ');
        },
        async ac() {
            this.mesgul = true;
            try {
                const izin = await Notification.requestPermission();
                if (izin !== 'granted') { this.durum = izin === 'denied' ? 'engelli' : 'kapali'; return; }
                const kayit = await this.kayit();
                // Eski (farklı anahtarlı) abonelik kaldıysa önce bırak.
                const eski = await kayit.pushManager.getSubscription();
                if (eski) await eski.unsubscribe();
                this.abonelik = await kayit.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: this.anahtarBaytlari() });
                await $wire.pushAboneOl(JSON.parse(JSON.stringify(this.abonelik)), this.cihazAdi());
                this.durum = 'acik';
            } catch (e) {
                console.error(e);
                this.durum = 'hata';
            } finally { this.mesgul = false; }
        },
        async kapat() {
            this.mesgul = true;
            try {
                const endpoint = this.abonelik?.endpoint;
                await this.abonelik?.unsubscribe();
                if (endpoint) await $wire.pushAbonelikKaldir(endpoint);
                this.abonelik = null;
                this.durum = 'kapali';
            } finally { this.mesgul = false; }
        },
        async dene() {
            this.mesgul = true;
            try { await $wire.pushDene(); } finally { this.mesgul = false; }
        },
    }"
>
    <div class="mehse-tb-durum">
        <template x-if="durum === 'yukleniyor'"><p>Kontrol ediliyor…</p></template>

        <template x-if="durum === 'acik'">
            <p><span class="mehse-tb-nokta mehse-tb-nokta-acik"></span> <strong>Bu cihazda bildirimler açık.</strong></p>
        </template>

        <template x-if="durum === 'kapali'">
            <p><span class="mehse-tb-nokta"></span> Bu cihazda bildirimler kapalı.</p>
        </template>

        <template x-if="durum === 'ios-kur'">
            <p>iPhone'da bildirim için önce MEHSE'yi ana ekrana ekleyin: Safari'de <strong>Paylaş → Ana Ekrana Ekle</strong>. Sonra ana ekrandaki MEHSE simgesinden açıp bu sayfaya gelin.</p>
        </template>

        <template x-if="durum === 'engelli'">
            <p>Bildirim izni bu tarayıcıda <strong>engellenmiş</strong>. Adres çubuğundaki kilit simgesine (ya da telefon Ayarlar &gt; Uygulamalar &gt; MEHSE &gt; Bildirimler) dokunup izni açın, sonra sayfayı yenileyin.</p>
        </template>

        <template x-if="durum === 'desteklenmiyor'">
            <p>Bu tarayıcı telefon bildirimlerini desteklemiyor. Android'de Chrome, iPhone'da ana ekrana eklenmiş MEHSE kullanın.</p>
        </template>

        <template x-if="durum === 'sunucu'">
            <p>Bildirim servisi şu an kullanılamıyor.</p>
        </template>

        <template x-if="durum === 'hata'">
            <p>Bildirimler açılamadı. Sayfayı yenileyip tekrar deneyin.</p>
        </template>
    </div>

    <div class="mehse-tb-dugmeler">
        <x-filament::button x-show="durum === 'kapali' || durum === 'hata'" x-on:click="ac()" x-bind:disabled="mesgul" icon="heroicon-o-bell">
            Bu cihazda bildirimleri aç
        </x-filament::button>
        <x-filament::button x-show="durum === 'acik'" x-on:click="dene()" x-bind:disabled="mesgul" icon="heroicon-o-paper-airplane" color="gray">
            Deneme bildirimi gönder
        </x-filament::button>
        <x-filament::button x-show="durum === 'acik'" x-on:click="kapat()" x-bind:disabled="mesgul" icon="heroicon-o-bell-slash" color="gray" outlined>
            Bu cihazda kapat
        </x-filament::button>
    </div>

    @if (filled($cihazlar))
        <div class="mehse-tb-cihazlar">
            <p class="mehse-tb-baslik">Bildirim alan cihazlar</p>
            <ul>
                @foreach ($cihazlar as $c)
                    <li wire:key="push-cihaz-{{ $c['id'] }}">
                        <span>
                            <strong>{{ $c['cihaz'] }}</strong>
                            <small>Eklendi {{ $c['eklenme'] }}{{ $c['son'] ? ' · son bildirim '.$c['son'] : '' }}</small>
                        </span>
                        <x-filament::icon-button icon="heroicon-o-trash" color="gray" label="Cihazı kaldır"
                            wire:click="pushCihazSil({{ $c['id'] }})" wire:confirm="Bu cihaz bildirim almayı bıraksın mı?" />
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
