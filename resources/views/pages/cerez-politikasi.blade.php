@extends('layouts.yasal-sayfa')

@section('baslik', 'Çerez Politikası')
@section('aciklama', 'Mehse.com internet sitesinde ve Mehse İSG platformunda kullanılan çerezler hakkında bilgilendirme.')

{{-- 01.11.2026: kullanıcının onayıyla sitenin fiilen kullandığı çerezlere göre taslak olarak yazıldı.
     Yeni bir çerez (analitik, reklam, harici gömülü içerik) eklenirse bu metin ve tablo güncellenmelidir. --}}
@section('icerik')
    @php($saglayici = config('isg.kayit.saglayici'))
    <p>Revizyon: 01.11.2026  |  Sürüm: 1.0</p>
    <p>Bu politika, Mehse adıyla faaliyet gösteren {{ $saglayici['unvan'] }} tarafından işletilen Mehse.com internet sitesinde ve Mehse İSG platformunda kullanılan çerezler hakkında bilgi verir. Kişisel verilerinizin işlenmesine ilişkin ayrıntılar <a href="{{ route('kvkk') }}">KVKK Aydınlatma Metninde</a> yer alır.</p>

    <h3>1 Çerez nedir?</h3>
    <p>Çerezler, bir internet sitesini ziyaret ettiğinizde tarayıcınıza kaydedilen küçük metin dosyalarıdır. Sitenin güvenli ve doğru çalışması, oturumunuzun açık kalması gibi amaçlarla kullanılır.</p>

    <h3>2 Kullandığımız çerezler</h3>
    <p>Mehse.com yalnızca sitenin çalışması için <strong>zorunlu çerezleri</strong> kullanır. Ziyaretçi davranışını izleyen analitik çerezler, reklam veya pazarlama çerezleri ve üçüncü taraf takip çerezleri kullanılmaz.</p>
    <div class="yasal-tablo-kap">
        <table class="yasal-tablo">
            <thead>
                <tr><th>Çerez</th><th>Amaç</th><th>Süre</th></tr>
            </thead>
            <tbody>
                <tr><td>Oturum çerezi ({{ config('session.cookie') }})</td><td>Formların ve kullanıcı panelinin çalışması için oturumun tanınması</td><td>Son işlemden itibaren 2 saat</td></tr>
                <tr><td>XSRF-TOKEN</td><td>Formların sahte isteklere karşı korunması (güvenlik)</td><td>Son işlemden itibaren 2 saat</td></tr>
                <tr><td>remember_web_…</td><td>Yalnız panelde “Beni hatırla” seçilirse oturumun açık tutulması</td><td>Çıkış yapılana kadar</td></tr>
            </tbody>
        </table>
    </div>

    <h3>3 Hukuki sebep</h3>
    <p>Zorunlu çerezler, talep ettiğiniz hizmetin (sitenin, teklif formunun ve kullanıcı panelinin) sunulabilmesi için gereklidir. Bu çerezler, sözleşmenin kurulması veya ifası ve veri sorumlusunun meşru menfaati (6698 sayılı Kanun m.5/2-c ve m.5/2-f) hukuki sebeplerine dayanılarak kullanılır; açık rızanıza bağlı değildir.</p>

    <h3>4 Çerezleri nasıl yönetebilirsiniz?</h3>
    <p>Tarayıcınızın ayarlarından çerezleri silebilir veya engelleyebilirsiniz. Zorunlu çerezleri engellemeniz hâlinde teklif formu gönderilemeyebilir ve kullanıcı paneline giriş yapılamaz.</p>

    <h3>5 Değişiklikler ve iletişim</h3>
    <p>Sitede yeni bir çerez türü kullanılmaya başlanırsa bu politika güncellenir; analitik veya pazarlama çerezleri kullanılacaksa önceden açık rızanız alınır. Sorularınız için {{ $saglayici['eposta'] }} adresine yazabilir veya {{ $saglayici['telefon'] }} numarasından ulaşabilirsiniz.</p>
@endsection
