@extends('admin.layouts.hesap')

@section('baslik', 'Süreniz doldu')

@section('icerik')
    @php
        $deneme = $user->abonelik_plani === 'deneme';
        $mesaj = rawurlencode('Merhaba, Mehse İSG '.($deneme ? 'deneme' : 'abonelik').' süremi uzatmak istiyorum. Hesabım: '.$user->email);
    @endphp
    <div class="h-kart h-ortala">
        <div class="h-ikon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        @if ($user->hasActiveTrial())
            <h1 class="h-baslik">Sayın {{ $user->name }}, hesabınız etkin</h1>
            <p class="h-alt-baslik">Panelinize kaldığınız yerden devam edebilirsiniz.</p>
            <div class="h-dugmeler">
                <a href="{{ url('/admin') }}" class="h-dugme h-dugme-mavi">Panele Dön</a>
            </div>
        @else
            <h1 class="h-baslik">Sayın {{ $user->name }}, {{ $deneme ? 'deneme süreniz' : 'abonelik süreniz' }} doldu</h1>
            <p class="h-alt-baslik">
                {{ $deneme ? 'Ücretsiz deneme' : 'Abonelik' }} süreniz {{ $user->abonelik_bitis?->format('d.m.Y') }} tarihinde sona erdi.
                Panele yeniden erişmek için size uygun paketi seçin veya bizimle iletişime geçin.
            </p>
            <div class="h-guven">
                <strong>Verileriniz güvende.</strong> Firmalarınız, risk değerlendirmeleriniz, eğitim kayıtlarınız ve belgeleriniz silinmedi.
                Süreniz uzatıldığı anda kaldığınız yerden devam edersiniz.
            </div>
            <div class="h-dugmeler">
                <a href="{{ route('filament.admin.paketler') }}" class="h-dugme h-dugme-mavi">Paketleri İncele</a>
                <a href="https://wa.me/{{ $waNo }}?text={{ $mesaj }}" target="_blank" rel="noopener" class="h-dugme h-dugme-yesil">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg>
                    WhatsApp ile Yazın
                </a>
                <a href="tel:+905064754855" class="h-dugme h-dugme-acik">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
                    0506 475 48 55
                </a>
            </div>
            <p class="h-not">Hafta içi 09:00 - 18:00 arasında dönüş yapıyoruz.</p>
        @endif
    </div>
@endsection
