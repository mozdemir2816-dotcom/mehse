{{-- İşyeri Giriş Bilgileri modalı. Kopyalama: mehse.com HTTP'de çalıştığı için
     navigator.clipboard olmayabilir → execCommand('copy') yedeği. --}}
@php
    $metin = "Adres: {$adres}\nKullanıcı adı: {$hesap->eposta}\nŞifre: {$hesap->sifre_acik}";
    $satir = 'display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.7rem';
    $btn = 'font-size:.78rem;padding:.25rem .6rem;border-radius:.45rem;border:1px solid rgb(107 114 128 / .35);background:transparent;cursor:pointer';
    $kod = 'font-family:ui-monospace,monospace;font-size:.85rem;padding:.3rem .55rem;border-radius:.4rem;background:rgb(107 114 128 / .1);user-select:all';
@endphp

<div x-data="{
        kopyalandi: '',
        kopyala(deger, ad) {
            const bitti = () => { this.kopyalandi = ad; setTimeout(() => this.kopyalandi = '', 1800) };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(deger).then(bitti); return; }
            const t = document.createElement('textarea'); t.value = deger; t.style.position = 'fixed'; t.style.opacity = '0';
            document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); bitti();
        }
    }" style="font-size:.88rem">
    <p style="color:rgb(75 85 99)">
        Bu bilgiler <strong>kalıcıdır</strong>. İşyerine bir kez iletin; işveren her girişte aynı bilgileri kullanır.
        Şifre yalnızca siz <strong>"Şifreyi Sıfırla"</strong> derseniz değişir.
    </p>

    @unless ($hesap->aktif)
        <p style="margin-top:.6rem;color:#b91c1c;font-weight:600">Bu firmanın işyeri girişi şu an KAPALI.</p>
    @endunless

    <div style="{{ $satir }}"><strong>Firma:</strong> {{ $firma->unvan }}</div>

    <div style="{{ $satir }}">
        <strong>Giriş adresi:</strong> <span style="{{ $kod }}">{{ $adres }}</span>
    </div>

    <div style="{{ $satir }}">
        <strong>Kullanıcı adı:</strong> <span style="{{ $kod }}">{{ $hesap->eposta }}</span>
        <button type="button" style="{{ $btn }}" x-on:click="kopyala(@js($hesap->eposta), 'eposta')">Kopyala</button>
    </div>

    <div style="{{ $satir }}">
        <strong>Şifre:</strong> <span style="{{ $kod }}">{{ $hesap->sifre_acik }}</span>
        <button type="button" style="{{ $btn }}" x-on:click="kopyala(@js($hesap->sifre_acik), 'sifre')">Kopyala</button>
    </div>

    <div style="{{ $satir }}">
        <button type="button" style="{{ $btn }};background:#0d9488;color:#fff;border-color:#0d9488;padding:.45rem .8rem" x-on:click="kopyala(@js($metin), 'hepsi')">📋 Adres + kullanıcı adı + şifreyi kopyala</button>
        <span x-show="kopyalandi" x-transition style="color:#047857;font-weight:600">✓ Kopyalandı</span>
    </div>

    <p style="margin-top:.9rem;font-size:.8rem;color:rgb(107 114 128)">
        İşveren girince yalnız bu firmanın evraklarını, personel listesini ve personel dosyasını görür ve indirir; hiçbir şeyi değiştiremez.
        Son giriş: {{ $hesap->son_giris_at?->format('d.m.Y H:i') ?? 'henüz giriş yapılmadı' }}
    </p>
</div>
