{{--
    Saha Yönetim & Denetim — otomatik firma bağlamı (29.10.2026). Seçili firma (App\Support\KomutaFirma, tüm saha
    sayfalarında ortak) için sistemden gelen bilgiler tek çubukta: NACE, tehlike sınıfı, gözetim yapan uzman +
    sertifika no, işveren vekili / sorumlu. Formlarda aynı bilgiler yeniden sorulmasın diye burada görünür.
--}}
@php
    $baglamFirmaId = \App\Support\KomutaFirma::oturumdaki();
    $baglamFirma = $baglamFirmaId
        ? \App\Models\Firma::with(['igu', 'user'])->where('user_id', \Filament\Facades\Filament::auth()->id())->find($baglamFirmaId)
        : null;
    $baglamUzman = $baglamFirma ? ($baglamFirma->igu?->ad_soyad ?: $baglamFirma->user?->name) : null;
    $baglamSertifika = $baglamFirma ? ($baglamFirma->igu ? $baglamFirma->igu->sertifika_no : $baglamFirma->user?->sertifika_no) : null;
    $baglamSorumlu = $baglamFirma ? ($baglamFirma->isveren_vekili ?: $baglamFirma->isveren_ad) : null;
@endphp

<div class="mehse-saha-baglam" role="status">
    @if ($baglamFirma)
        <div class="mehse-saha-baglam-ad">
            <x-filament::icon icon="heroicon-o-building-office-2" style="width:1.1rem;height:1.1rem"/>
            <strong>{{ $baglamFirma->unvan }}</strong>
        </div>
        <dl class="mehse-saha-baglam-liste">
            <div><dt>NACE</dt><dd>{{ $baglamFirma->nace_kodu ?: '—' }}</dd></div>
            <div><dt>Tehlike sınıfı</dt><dd>{{ $baglamFirma->tehlikeSinifiEtiketi() }}</dd></div>
            <div><dt>Gözetim yapan uzman</dt><dd>{{ $baglamUzman ?: '—' }}</dd></div>
            <div><dt>İSG sertifika no</dt><dd>{{ $baglamSertifika ?: '—' }}</dd></div>
            <div><dt>İşveren vekili / sorumlu</dt><dd>{{ $baglamSorumlu ?: '—' }}</dd></div>
        </dl>
    @else
        <div class="mehse-saha-baglam-ad" style="opacity:.75">
            <x-filament::icon icon="heroicon-o-building-office-2" style="width:1.1rem;height:1.1rem"/>
            Firma seçilmedi — aşağıdan bir firma seçince uzman, sertifika no, NACE ve sorumlu bilgileri burada otomatik gelir; tüm saha sekmelerinde geçerli olur.
        </div>
    @endif
</div>
