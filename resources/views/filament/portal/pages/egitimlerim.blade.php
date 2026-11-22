<x-filament-panels::page>
    <p class="portal-muted" style="font-size:.88rem;margin-top:-.5rem;line-height:1.5">
        Aşağıdaki eğitimleri sırayla izleyip final sınavına girin. Tüm dersleri izlemeden sınav açılmaz.
        Sınavı geçince katılım belgenizi indirebilirsiniz.
    </p>

    @if ($this->atamalar->isEmpty())
        <div class="portal-card" style="text-align:center;padding:3rem 1.25rem">
            <div style="font-size:2rem;line-height:1">🎓</div>
            <div class="portal-muted" style="margin-top:.6rem">Size atanmış bir uzaktan eğitim bulunmuyor.</div>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:.85rem">
            @foreach ($this->atamalar as $a)
                @php
                    $yuzde = $a->ilerlemeYuzdesi();
                    [$badgeClass, $barColor] = match ($a->durum) {
                        'tamamlandi' => ['portal-badge-success', 'var(--p-success-text)'],
                        'onay_bekliyor', 'ih_onay_bekliyor' => ['portal-badge-warning', 'var(--p-warning-text)'],
                        'basarisiz' => ['portal-badge-danger', 'var(--p-danger-text)'],
                        'devam' => ['portal-badge-warning', 'var(--p-warning-text)'],
                        default => ['portal-badge-neutral', 'var(--p-text-label)'],
                    };
                @endphp
                <div class="portal-card portal-card-pad">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
                        <div>
                            <div class="portal-heading" style="font-weight:700;font-size:1.05rem">{{ $a->paket->ad }}</div>
                            <div class="portal-muted" style="font-size:.82rem;margin-top:.25rem">
                                {{ $a->paket->dersler->count() }} ders · ~{{ $a->paket->onlineSureDk() }} dk (sınavlar dahil)
                                @if ($a->son_tarih) · Son tarih: {{ $a->son_tarih->format('d.m.Y') }} @endif
                            </div>
                        </div>
                        <span class="portal-badge {{ $badgeClass }}">{{ $a->durumEtiketi() }}</span>
                    </div>

                    <div class="portal-progress-track" style="margin-top:.9rem">
                        <div class="portal-progress-fill" style="width:{{ $yuzde }}%;background:{{ $barColor }}"></div>
                    </div>
                    <div class="portal-label" style="font-size:.76rem;margin-top:.3rem">
                        {{ $a->izlenenDersSayisi() }}/{{ $a->toplamDersSayisi() }} ders izlendi
                        @if ($a->sonSinav()) · Son sınav: %{{ $a->sonSinav()->puan }} @endif
                    </div>

                    <div style="margin-top:1rem">
                        <x-filament::button
                            tag="a"
                            :href="\App\Filament\Portal\Pages\EgitimIzle::getUrl(['atama' => $a->id])"
                            style="width:100%;justify-content:center"
                        >
                            {{ $a->durum === 'tamamlandi' ? 'Görüntüle' : ($a->izlenenDersSayisi() > 0 ? 'Devam Et' : 'Başla') }}
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- K5: BANA ATANAN DÖF / SAHA AKSİYONLARI --}}
    @if ($this->atananDofler->isNotEmpty())
        <div class="portal-card portal-card-pad" style="margin-top:1.25rem;border-left:4px solid #dc2626">
            <div class="portal-heading" style="font-weight:700;font-size:1rem;margin-bottom:.25rem">📋 Bana Atanan DÖF &amp; Saha Aksiyonları ({{ $this->atananDofler->count() }})</div>
            <div class="portal-muted" style="font-size:.8rem;margin-bottom:.75rem">Giderdiğiniz uygunsuzlukların fotoğrafını gönderin; İSG uzmanınız onayladığında kapatılır.</div>
            @foreach ($this->atananDofler as $b)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;padding:.55rem 0;border-top:1px solid rgb(107 114 128 / .15)">
                    <div style="flex:1;min-width:200px">
                        <div style="font-weight:600;font-size:.86rem">{{ $b->bulgu_no }} · {{ \Illuminate\Support\Str::limit($b->uygunsuzluk, 110) }}</div>
                        <div class="portal-muted" style="font-size:.75rem">Termin: {{ $b->termin?->format('d.m.Y') ?: 'Belirtilmedi' }}@if ($b->isveren_kapanis_talebi_at) · <strong style="color:#b45309">⏳ Uzman onayı bekleniyor</strong>@endif</div>
                    </div>
                    <x-filament::button size="xs" wire:click="mountAction('calisanDofKanit', { id: {{ $b->id }} })">
                        {{ $b->isveren_kapanis_talebi_at ? 'Kanıtı Güncelle' : 'Düzeltme Fotoğrafı Gönder' }}
                    </x-filament::button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- K5: KKD TALEPLERİM --}}
    <div class="portal-card portal-card-pad" style="margin-top:1.25rem">
        <div class="portal-heading" style="font-weight:700;font-size:1rem;margin-bottom:.25rem">🦺 KKD (Kişisel Koruyucu Donanım) Talebi</div>
        <div class="portal-muted" style="font-size:.8rem;margin-bottom:.8rem">Yıpranan, kaybolan veya yeni görev için gereken koruyucu ekipmanı buradan talep edin.</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.6rem;align-items:end">
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.2rem">KKD Türü</label>
                <input type="text" wire:model="kkdTuru" placeholder="Baret, iş ayakkabısı, eldiven..." style="width:100%;padding:.45rem .6rem;border:1px solid #cbd5e1;border-radius:8px;font-size:.83rem">
            </div>
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.2rem">Beden / Numara</label>
                <input type="text" wire:model="kkdBeden" placeholder="Örn: 42 / L" style="width:100%;padding:.45rem .6rem;border:1px solid #cbd5e1;border-radius:8px;font-size:.83rem">
            </div>
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.2rem">Talep Nedeni</label>
                <select wire:model="kkdNeden" style="width:100%;padding:.45rem .6rem;border:1px solid #cbd5e1;border-radius:8px;font-size:.83rem">
                    @foreach (\App\Models\KkdTalebi::NEDENLER as $k => $ad)<option value="{{ $k }}">{{ $ad }}</option>@endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:.2rem">Not (opsiyonel)</label>
                <input type="text" wire:model="kkdAciklama" placeholder="Kısa açıklama" style="width:100%;padding:.45rem .6rem;border:1px solid #cbd5e1;border-radius:8px;font-size:.83rem">
            </div>
            <div><x-filament::button size="sm" wire:click="kkdTalepGonder">KKD Talep Et</x-filament::button></div>
        </div>

        @if ($this->kkdTalepleri->isNotEmpty())
            <div style="margin-top:.85rem;border-top:1px solid rgb(107 114 128 / .15);padding-top:.55rem">
                @foreach ($this->kkdTalepleri as $kt)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;padding:.35rem 0;font-size:.8rem">
                        <span><strong>{{ $kt->kkd_turu }}</strong>@if ($kt->beden) ({{ $kt->beden }})@endif · {{ $kt->created_at?->format('d.m.Y') }}</span>
                        <span style="font-weight:700;color:{{ $kt->durum === 'teslim_edildi' ? '#15803d' : ($kt->durum === 'reddedildi' ? '#dc2626' : '#b45309') }}">
                            {{ \App\Models\KkdTalebi::DURUMLAR[$kt->durum] ?? $kt->durum }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
