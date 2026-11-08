@php
    $kutu = 'border:1px solid rgb(107 114 128 / .3);border-radius:.75rem;padding:1rem';
    $girdi = 'margin-top:.3rem;width:100%;padding:.55rem .75rem;border-radius:.5rem;border:1px solid rgb(107 114 128 / .35);background:transparent';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.acil-slate-stil')
    <p style="font-size:.85rem;color:rgb(107 114 128);margin-top:-.5rem">
        Bir eğitim paketini firmadaki çalışanlara atayın; sistem portal giriş bilgisini (e-posta + geçici şifre)
        üretir. E-postası olmayan çalışanlara otomatik bir geçici kullanıcı kodu oluşturulur. Çalışanlar
        <strong>{{ url('/egitim') }}</strong> adresinden girer. Paketleri
        <strong>Uzaktan Eğitim Paketleri</strong> ekranından oluşturursunuz.
    </p>
    <p style="font-size:.78rem;color:rgb(107 114 128);margin-top:-.4rem;line-height:1.5">
        Yönetmelik (RG 02.04.2026) uyumu: giriş–çıkış ve fiili izleme süresi kaydedilir, ileri sarma engellenir, sekme değişince video durur,
        {{ config('isg.uzaktan_egitim.yoklama_dk') }} dakikada bir aktif katılım sorusu çıkar; eğitim öncesi seviye tespit testi,
        sınavda en az {{ config('isg.uzaktan_egitim.gecme_puani_alt_sinir') }} puan ve {{ config('isg.uzaktan_egitim.sinav_hakki') }} hak (dolarsa eğitim baştan).
        Tehlikeli / çok tehlikeli işyerinde 4. konu başlığı (işe ve işyerine özgü riskler) yüz yüze verilir. İşe başlama eğitimi uzaktan atanamaz.
    </p>

    <x-filament::section icon="heroicon-o-academic-cap" icon-color="primary">
        <x-slot name="heading">Atama</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            <div>
                <label style="font-weight:600;font-size:.82rem">Firma <span style="color:#dc2626">*</span></label>
                <select wire:model.live="firmaId" style="{{ $girdi }}">
                    <option value="">— Firma seçin —</option>
                    @foreach ($this->firmalar as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Paketi <span style="color:#dc2626">*</span></label>
                <select wire:model.live="paketId" style="{{ $girdi }}">
                    <option value="">— Paket seçin —</option>
                    @foreach ($this->paketler as $id => $ad)<option value="{{ $id }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Eğitim Türü</label>
                <select wire:model.live="egitimTuru" style="{{ $girdi }}">
                    @foreach (config('isg.uzaktan_egitim.egitim_turleri') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:.82rem">Son Tarih (opsiyonel)</label>
                <input type="date" wire:model="sonTarih" style="{{ $girdi }}">
            </div>
        </div>

        @if ($k = $this->sureKontrolu)
            <div style="{{ $kutu }};margin-top:1rem;font-size:.82rem;line-height:1.5;{{ $k['yeterli'] ? 'border-color:rgb(21 128 61 / .45)' : 'border-color:rgb(217 119 6 / .6);background:rgb(217 119 6 / .06)' }}">
                <strong>{{ $k['yeterli'] ? '✓ Süre yeterli' : '⚠ Süre yetersiz olabilir' }}</strong> —
                {{ $this->firma->tehlikeSinifiEtiketi() }}, {{ config('isg.uzaktan_egitim.egitim_turleri.'.$egitimTuru) }}: toplam en az {{ $k['saat'] }} ders saati
                — online kısım için {{ $k['gereken_dk'] }} dk video gerekir; pakette {{ $k['paket_dk'] }} dk var.
                @if ($k['yuz_yuze_saat']) Kalan {{ $k['yuz_yuze_saat'] }} ders saati (4. konu dahil) yüz yüze verilir. @endif
                @if ($k['suresiz_ders'] > 0)<span style="color:rgb(107 114 128)"> {{ $k['suresiz_ders'] }} dersin süresi henüz bilinmiyor (ilk izlemede otomatik dolar).</span>@endif
                @if ($k['dorduncu_yuz_yuze'])<div style="color:rgb(180 83 9)">Bu eğitim tamamlansa da temel eğitim, 4. konu yüz yüze verilip Eğitim Katılım formu girilince tamamlanmış sayılır.</div>@endif
            </div>
        @endif

        @if ($this->firma)
            <div style="{{ $kutu }};margin-top:1rem">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <span style="font-weight:600;font-size:.85rem">Çalışanlar ({{ count($secilenCalisanlar) }} seçili)</span>
                    <x-filament::button size="xs" color="gray" wire:click="tumunuSec">Tümünü Seç</x-filament::button>
                </div>
                @if ($this->calisanlar->isEmpty())
                    <p style="font-size:.82rem;color:rgb(107 114 128)">Bu firmada aktif çalışan yok.</p>
                @else
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.4rem">
                        @foreach ($this->calisanlar as $c)
                            <label style="display:flex;gap:.5rem;align-items:flex-start;font-size:.85rem;cursor:pointer;padding:.3rem">
                                <input type="checkbox" wire:click="calisanToggle({{ $c->id }})" @checked(in_array($c->id, $secilenCalisanlar))>
                                <span>
                                    {{ $c->ad_soyad }}
                                    @if (blank($c->eposta))<span style="color:#b45309;font-size:.75rem"> (e-posta yok — kullanıcı kodu üretilecek)</span>@endif
                                    @if (filled($c->sifre))<span style="color:rgb(21 128 61);font-size:.75rem"> ✓ hesap var</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <div style="margin-top:.8rem">
                    <x-filament::button wire:click="ata" icon="heroicon-o-paper-airplane">Ata + Giriş Bilgisi Üret</x-filament::button>
                </div>
            </div>
        @endif

        @if ($sonUretilenGiris)
            <div style="{{ $kutu }};margin-top:1rem;border-color:rgb(21 128 61 / .5);background:rgb(21 128 61 / .05)">
                <div style="font-weight:600;font-size:.85rem;margin-bottom:.4rem">Giriş Bilgileri — çalışanlara iletin ({{ url('/egitim') }})</div>
                <table style="width:100%;font-size:.82rem;border-collapse:collapse">
                    <tr style="text-align:left;color:rgb(107 114 128)"><th style="padding:.3rem">Ad Soyad</th><th style="padding:.3rem">E-posta / Kullanıcı Kodu</th><th style="padding:.3rem">Geçici Şifre</th><th style="padding:.3rem"></th></tr>
                    @foreach ($sonUretilenGiris as $g)
                        <tr style="border-top:1px solid rgb(107 114 128 / .15)">
                            <td style="padding:.3rem">{{ $g['ad_soyad'] }}</td>
                            <td style="padding:.3rem;font-family:monospace">
                                {{ $g['eposta'] }}
                                @unless (str_contains($g['eposta'], '@'))<span style="color:#b45309;font-size:.75rem"> (kod)</span>@endunless
                            </td>
                            <td style="padding:.3rem;font-family:monospace">{{ $g['sifre'] ?? '(mevcut şifre korunuyor)' }}</td>
                            <td style="padding:.3rem"><a href="{{ $this->whatsappLinki($g) }}" target="_blank" rel="noopener" style="background:#16a34a;color:#fff;padding:.2rem .6rem;border-radius:.4rem;font-size:.75rem;text-decoration:none;white-space:nowrap">📲 WhatsApp ile İlet</a></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </x-filament::section>

    <div style="font-size:.82rem;color:rgb(107 114 128)">
        Atanan eğitimlerin ilerlemesi, giriş–çıkış kayıtları, İGU onayı ve sertifikalar
        <a href="{{ \App\Filament\Pages\UzaktanEgitimTakip::getUrl($this->firmaId ? ['firma' => $this->firmaId] : []) }}" style="color:rgb(79 70 229);font-weight:600">Takip</a> sekmesindedir.
    </div>
</x-filament-panels::page>
