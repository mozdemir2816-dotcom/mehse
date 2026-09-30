{{--
    Giriş / kayıt / şifre ekranlarının sol tanıtım paneli (AdminPanelProvider
    SIMPLE_LAYOUT_START, yalnız oturum açılmamışken). isgsuite.tr giriş ekranındaki
    dönen tanıtım kartı referansı; renkler MEHSE logosundan (#0A345A / #099291).
    Stiller: tasarim.css "Giriş ekranı".
--}}
@guest
    @php
        $slaytlar = [
            ['ikon' => 'heroicon-o-sparkles', 'etiket' => 'Risk değerlendirmesi', 'baslik' => 'Risk analizini dakikalar içinde hazırlayın', 'metin' => 'Sektör şablonları, 5x5 Matris / Fine-Kinney ve yapay zekâ destekli önlem önerileriyle eksiksiz risk değerlendirmesi.'],
            ['ikon' => 'heroicon-o-squares-2x2', 'etiket' => 'Kontrol merkezi', 'baslik' => 'Tüm firmalarınızın evrak durumu tek ekranda', 'metin' => 'Hangi işyerinde hangi belgenin eksik olduğunu, vadesi yaklaşanları ve uyum yüzdesini anında görün.'],
            ['ikon' => 'heroicon-o-academic-cap', 'etiket' => 'Eğitimler', 'baslik' => 'Eğitimleri planlayın, sertifikayı tek tıkla basın', 'metin' => 'Katılım formları, ön/son test, uzaktan eğitim portalı ve geçerlilik takibi bir arada.'],
            ['ikon' => 'heroicon-o-users', 'etiket' => 'İSG kurulu', 'baslik' => 'Kurul toplantılarını yönetmeliğe uygun yürütün', 'metin' => 'Zorunlu üye kontrolü, tehlike sınıfına göre toplantı periyodu, gündem, kararlar ve resmî tutanak.'],
            ['ikon' => 'heroicon-o-wrench-screwdriver', 'etiket' => 'Periyodik kontrol', 'baslik' => 'Ekipman muayenelerini kaçırmayın', 'metin' => 'İş ekipmanı envanteri, periyodik kontrol tarihleri ve müfettiş teftiş paketi.'],
            ['ikon' => 'heroicon-o-fire', 'etiket' => 'Acil durum', 'baslik' => 'Acil durum planı ve afişler hazır', 'metin' => 'İşyerine göre otomatik seçilen konularla acil durum eylem planı, kapak sayfası ve afiş paketi.'],
        ];
    @endphp

    <aside class="mehse-giris-tanitim" aria-label="MEHSE tanıtım"
        x-data="{ i: 0, n: {{ count($slaytlar) }}, zaman: null,
            basla() { this.zaman = setInterval(() => this.i = (this.i + 1) % this.n, 6000) },
            git(k) { this.i = (k + this.n) % this.n; clearInterval(this.zaman); this.basla() } }"
        x-init="basla()">

        <div class="mehse-giris-tanitim-ust">
            <img src="{{ asset('images/marka/mehse-logo-acik.png') }}" alt="MEHSE İş Sağlığı ve Güvenliği" class="mehse-giris-tanitim-logo">
        </div>

        <div class="mehse-giris-tanitim-orta">
            <h2 class="mehse-giris-tanitim-baslik">İş sağlığı ve güvenliği süreçlerinizi tek panelden yönetin.</h2>
            <p class="mehse-giris-tanitim-alt">Firmalar, risk analizleri, eğitimler, kurullar ve resmî belgeler — iş güvenliği uzmanı için tasarlandı.</p>

            <div class="mehse-giris-kart">
                <div class="mehse-giris-kart-sayac">
                    <span><i></i> MEHSE</span>
                    <span x-text="String(i + 1).padStart(2, '0') + ' / ' + String(n).padStart(2, '0')">01 / {{ str_pad((string) count($slaytlar), 2, '0', STR_PAD_LEFT) }}</span>
                </div>

                <div class="mehse-giris-kart-icerik">
                    @foreach ($slaytlar as $k => $s)
                        <div x-show="i === {{ $k }}" @if ($k > 0) x-cloak @endif
                            x-transition:enter="mehse-giris-gecis" x-transition:enter-start="mehse-giris-gecis-bas" x-transition:enter-end="mehse-giris-gecis-son">
                            <div class="mehse-giris-kart-ikon">
                                <x-filament::icon :icon="$s['ikon']" class="h-7 w-7" />
                            </div>
                            <div class="mehse-giris-kart-etiket">{{ $s['etiket'] }}</div>
                            <div class="mehse-giris-kart-baslik">{{ $s['baslik'] }}</div>
                            <p class="mehse-giris-kart-metin">{{ $s['metin'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mehse-giris-kart-alt">
                    <div class="mehse-giris-noktalar">
                        @foreach ($slaytlar as $k => $s)
                            <button type="button" x-on:click="git({{ $k }})" :class="{ 'fi-active': i === {{ $k }} }" aria-label="{{ $s['etiket'] }}"></button>
                        @endforeach
                    </div>
                    <div class="mehse-giris-oklar">
                        <button type="button" x-on:click="git(i - 1)" aria-label="Önceki"><x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" /></button>
                        <button type="button" x-on:click="git(i + 1)" aria-label="Sonraki"><x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" /></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mehse-giris-tanitim-dip">© {{ now()->year }} MEHSE İş Sağlığı ve Güvenliği</div>
    </aside>
@endguest
