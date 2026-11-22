@php
    /** @var ?\App\Models\Firma $firma */
    /** @var ?array $rapor */
    $tl = fn (int $n) => number_format($n, 0, ',', '.').' ₺';
@endphp
<div>
    @if (! $firma || ! $rapor)
        <p style="font-size:.86rem;color:#b45309">Sanal Müfettiş taraması için önce bir firma seçin.</p>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.75rem;margin-bottom:1rem">
            <div style="border:1px solid #cbd5e1;border-radius:12px;padding:.75rem;border-left:4px solid {{ $rapor['renk'] }}">
                <div style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase">Denetim Hazırlık Skoru</div>
                <div style="font-size:1.6rem;font-weight:800;color:{{ $rapor['renk'] }}">{{ $rapor['puan'] }} / 100</div>
                <div style="font-size:.74rem;font-weight:600;color:{{ $rapor['renk'] }}">{{ $rapor['seviye'] }}</div>
            </div>
            <div style="border:1px solid #fecaca;background:#fef2f2;border-radius:12px;padding:.75rem;border-left:4px solid #dc2626">
                <div style="font-size:.72rem;color:#991b1b;font-weight:700;text-transform:uppercase">Tahmini 6331 İPC Riski</div>
                <div style="font-size:1.15rem;font-weight:800;color:#dc2626;margin-top:.2rem">
                    {{ $rapor['max_ceza_tl'] > 0 ? $tl($rapor['min_ceza_tl']).' – '.$tl($rapor['max_ceza_tl']) : '0 ₺ (Risk Yok)' }}
                </div>
                <div style="font-size:.72rem;color:#991b1b">Kademe çarpanı: ×{{ $rapor['carpan'] }} ({{ $firma->tehlikeSinifiEtiketi() }} · {{ $firma->calisan_sayisi ?: 1 }} kişi)</div>
            </div>
            <div style="border:1px solid #cbd5e1;border-radius:12px;padding:.75rem">
                <div style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase">Tespit Özeti</div>
                <div style="font-size:1.4rem;font-weight:800;color:#0f172a">{{ $rapor['eksik_sayisi'] }} Eksik / {{ $rapor['toplam_kriter'] }} Kriter</div>
                <div style="font-size:.72rem;color:#64748b">{{ $firma->secimEtiketi() }}</div>
            </div>
        </div>

        <div style="overflow-x:auto;max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:10px">
            <table style="width:100%;border-collapse:collapse;font-size:.78rem">
                <thead>
                    <tr style="background:#f8fafc;text-align:left;border-bottom:1px solid #e2e8f0;color:#475569">
                        <th style="padding:.45rem .6rem">Kod</th>
                        <th style="padding:.45rem .6rem">Yasal Kriter &amp; Dayanak</th>
                        <th style="padding:.45rem .6rem">Durum</th>
                        <th style="padding:.45rem .6rem;text-align:right">Tahmini İPC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rapor['bulgular'] as $b)
                        <tr style="border-bottom:1px solid #f1f5f9">
                            <td style="padding:.45rem .6rem;font-family:monospace;font-weight:700">{{ $b['kod'] }}</td>
                            <td style="padding:.45rem .6rem">
                                <div style="font-weight:700">{{ $b['baslik'] }}</div>
                                <div style="font-size:.72rem;color:#64748b">{{ $b['dayanak'] }} — {{ $b['aksiyon'] }}</div>
                            </td>
                            <td style="padding:.45rem .6rem;white-space:nowrap">
                                @if ($b['durum'] === 'uyumlu')
                                    <span style="background:#dcfce7;color:#15803d;font-weight:700;padding:.15rem .5rem;border-radius:999px;font-size:.7rem">✓ Uyumlu</span>
                                @else
                                    <span style="background:#fee2e2;color:#dc2626;font-weight:700;padding:.15rem .5rem;border-radius:999px;font-size:.7rem">✗ Eksik</span>
                                @endif
                            </td>
                            <td style="padding:.45rem .6rem;text-align:right;white-space:nowrap;font-weight:700;color:{{ $b['max_tl'] > 0 ? '#dc2626' : '#15803d' }}">
                                {{ $b['max_tl'] > 0 ? $tl($b['min_tl']).' – '.$tl($b['max_tl']) : '0 ₺' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="font-size:.71rem;color:#64748b;margin-top:.5rem">
            * Tutarlar 6331 sayılı Kanun m.26 kademeli idari para cezası katsayılarına göre bilgilendirme amaçlı yaklaşık simülasyondur; kesin yaptırım ÇSGB iş müfettişi tutanağına tabidir.
        </p>
    @endif
</div>
