<x-filament-widgets::widget>
    @php
        $saatDk = fn (int $dk) => \App\Support\GorevDurumu::saatDk($dk);
        $kapasiteSaat = (int) config('isg.ana_sayfa.aylik_kapasite_saat', 195);
        $asim = $sure['yuzde'] > 100;
        $katipIpucu = 'Kullanılan süre = aktif işyerlerinde çalışan sayısı × aylık süre (az tehlikeli '.config('isg.igu_aylik_dk.az_tehlikeli')
            .', tehlikeli '.config('isg.igu_aylik_dk.tehlikeli').', çok tehlikeli '.config('isg.igu_aylik_dk.cok_tehlikeli').' dk). '
            .'Kalan = '.$kapasiteSaat.' saatlik aylık kapasite − kullanılan süre.';
    @endphp

    {{-- Kokpit şeridi: selamlama + unvan · hızlı işlemler · evrak uyumu halkası --}}
    <section class="mehse-hosgeldin mehse-kokpit">
        <div class="mehse-hosgeldin-sol">
            <div class="mehse-kokpit-baslik-satir">
                <h2 class="mehse-hosgeldin-baslik">{{ $selam }}, {{ $ad }}</h2>
                @if ($unvan)
                    <span class="mehse-kokpit-rozet">{{ $unvan }}</span>
                @endif
            </div>
            <p class="mehse-hosgeldin-alt">
                {{ $tarih }}
                @if ($firma > 0)
                    · <strong>{{ $sure['isyeri'] }}</strong> aktif işyeri
                @else
                    · Henüz firma eklemediniz — ilk firmanızı ekleyerek başlayın.
                @endif
            </p>

            <div class="mehse-hosgeldin-eylemler">
                @foreach ($eylemler as $i => $e)
                    <a href="{{ $e['url'] }}" class="mehse-hosgeldin-eylem @if ($i === 0) mehse-hosgeldin-eylem-birincil @endif">
                        <x-filament::icon :icon="$e['ikon']" class="mehse-hosgeldin-eylem-ikon" />
                        <span>{{ $e['ad'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if ($firma > 0)
            <a href="{{ $kontrolUrl }}" class="mehse-hosgeldin-halka" title="Eksik evrakları Kontrol Merkezi'nde göster"
               style="--yuzde: {{ max(0, min(100, $uyum)) }}">
                <span class="mehse-hosgeldin-halka-deger">%{{ $uyum }}</span>
                <span class="mehse-hosgeldin-halka-etiket">Evrak uyumu</span>
            </a>
        @endif
    </section>

    {{-- Tek satır 4 kart (telefonda gizli: telefonda yalnız evrak uyumu + ziyaret takvimi) --}}
    <div class="mehse-kokpit-kartlar mehse-mobilde-gizle">
        <a href="{{ $portfoy['url'] }}" class="mehse-kokpit-kart" style="--ton:#0284C7">
            <span class="mehse-kokpit-kart-etiket"><x-filament::icon icon="heroicon-o-building-office-2" />Aktif Portföy</span>
            <span class="mehse-kokpit-kart-deger">{{ $portfoy['firma'] }} <small>firma</small> <span class="mehse-kokpit-ayrac">/</span> {{ $portfoy['calisan'] }} <small>çalışan</small></span>
            <span class="mehse-kokpit-kart-alt">Firma listesine git →</span>
        </a>

        <div class="mehse-kokpit-kart" style="--ton:{{ $asim ? '#E11D48' : '#0284C7' }}" title="{{ $katipIpucu }}">
            <span class="mehse-kokpit-kart-etiket"><x-filament::icon icon="heroicon-o-clock" />İSG-KATİP Kapasitesi
                <span class="mehse-kokpit-bilgi" aria-label="{{ $katipIpucu }}">ⓘ</span></span>
            <span class="mehse-kokpit-kart-deger">{{ $saatDk($sure['kullanilan_dk']) }} <small>/ {{ $kapasiteSaat }} sa</small>
                <span class="mehse-kokpit-hap">%{{ $sure['yuzde'] }}</span></span>
            <span class="mehse-kokpit-cubuk"><span style="width:{{ min(100, max(0, $sure['yuzde'])) }}%"></span></span>
            <span class="mehse-kokpit-kart-alt">
                <span class="mehse-kokpit-hap {{ $asim ? 'kotu' : 'iyi' }}">{{ $asim ? 'Aşım' : 'Kalan' }}: {{ $saatDk(abs($sure['kullanilan_dk'] - $sure['kapasite_dk'])) }}</span>
                @if ($sure['sinif_uygunsuz'])
                    <span class="mehse-kokpit-hap kotu" title="Belge sınıfınızın görev alamayacağı tehlike sınıfında işyeri: {{ implode(', ', $sure['sinif_uygunsuz']) }}">⚠ {{ count($sure['sinif_uygunsuz']) }} sınıf dışı işyeri</span>
                @endif
            </span>
        </div>

        <a href="{{ $sozlesme['url'] }}" class="mehse-kokpit-kart" style="--ton:{{ $sozlesme['geciken'] ? '#E11D48' : '#059669' }}">
            <span class="mehse-kokpit-kart-etiket"><x-filament::icon icon="heroicon-o-document-text" />Sözleşme Durumu</span>
            <span class="mehse-kokpit-kart-deger">{{ $sozlesme['geciken'] }} <small>geciken sözleşme</small></span>
            <span class="mehse-kokpit-kart-alt">
                <span class="mehse-kokpit-hap {{ $sozlesme['geciken'] ? 'kotu' : 'iyi' }}">{{ $sozlesme['geciken'] ? 'Yenileme gerekli' : 'Güncel' }}</span>
                @if ($sozlesme['yaklasan'])<span class="mehse-kokpit-hap uyari">{{ $sozlesme['yaklasan'] }} tanesi 30 gün içinde bitiyor</span>@endif
            </span>
        </a>

        <a href="{{ $dof['url'] }}" class="mehse-kokpit-kart" style="--ton:{{ $dof['geciken'] ? '#E11D48' : ($dof['yaklasan'] ? '#D97706' : '#059669') }}">
            <span class="mehse-kokpit-kart-etiket"><x-filament::icon icon="heroicon-o-exclamation-triangle" />Kritik DÖF / Saha</span>
            <span class="mehse-kokpit-kart-deger">{{ $dof['geciken'] }} <small>günü geçen</small> <span class="mehse-kokpit-ayrac">/</span> {{ $dof['yaklasan'] }} <small>yaklaşan</small></span>
            <span class="mehse-kokpit-kart-alt">{{ $dof['geciken'] || $dof['yaklasan'] ? 'DÖF takip listesine git →' : 'Açık termin sorunu yok' }}</span>
        </a>
    </div>
</x-filament-widgets::widget>
