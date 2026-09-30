{{--
    Panel logosu (AdminPanelProvider ->brandLogo): sidebar/topbar başlığı ve giriş
    ekranı. İki sürüm basılır, hangisinin görüneceğine zemin rengine göre CSS karar
    verir (tasarim.css "Marka"): koyu zemin (klasik/saha üst çubuk + sidebar) →
    beyaz+açık turkuaz, açık zemin → renkli. Görseller public/images/marka/
    (kaynak: kullanıcının verdiği MEHSE logosu, GD ile saydamlaştırıldı).
--}}
<span class="mehse-marka">
    <img src="{{ asset('images/marka/mehse-logo.png') }}" alt="MEHSE İş Sağlığı ve Güvenliği" class="mehse-marka-renkli">
    <img src="{{ asset('images/marka/mehse-logo-acik.png') }}" alt="MEHSE İş Sağlığı ve Güvenliği" class="mehse-marka-acik">
</span>
