<x-filament-panels::page>
    <div class="portal-card" style="padding:1.25rem;max-width:760px;margin:0 auto">
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:.6rem">Kişisel Verilerin İşlenmesi Hakkında Aydınlatma Metni</h2>
        <div style="font-size:.88rem;line-height:1.6;max-height:260px;overflow-y:auto;padding:.75rem;border:1px solid var(--p-border-light, #e5e7eb);border-radius:.5rem">
            <p><strong>Veri sorumlusu:</strong> Çalıştığınız işyeri (işveren) ve eğitimi tanımlayan iş güvenliği uzmanı.</p>
            <p><strong>İşlenen veriler:</strong> Ad soyad, görev, T.C. kimlik numarası, eğitim giriş-çıkış zamanları, videoların izlenme süreleri,
                aktif katılım sorularına verdiğiniz cevaplar, sınav puanı ve cevaplarınız, IP adresiniz ile cihaz/tarayıcı bilginiz.</p>
            <p><strong>Amaç ve hukuki sebep:</strong> 6331 sayılı İş Sağlığı ve Güvenliği Kanunu ile Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin
                Usul ve Esasları Hakkında Yönetmelik uyarınca eğitimin verildiğinin, katılımınızın ve başarınızın kayıt altına alınması ve
                denetimlerde ispatlanması. Veriler, hukuki yükümlülüğün yerine getirilmesi (KVKK m.5/2-a, ç) ve bir hakkın tesisi ile korunması
                (KVKK m.5/2-e) sebeplerine dayanır.</p>
            <p><strong>Aktarım ve saklama:</strong> Veriler yetkili denetim makamlarına ve işyerinizin İSG hizmeti sağlayıcılarına yasal yükümlülük kapsamında
                aktarılabilir. Eğitim kayıtları, mevzuatta öngörülen saklama süreleri boyunca muhafaza edilir.</p>
            <p><strong>Haklarınız:</strong> KVKK m.11 uyarınca verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltilmesini isteme ve
                başvuru haklarına sahipsiniz. Başvurularınızı işvereninize iletebilirsiniz.</p>
        </div>

        <h2 style="font-size:1.1rem;font-weight:700;margin:1.1rem 0 .6rem">Eğitim Taahhüdü</h2>
        <p style="font-size:.88rem;line-height:1.6">
            Bu iş sağlığı ve güvenliği eğitimini bizzat kendim izleyeceğimi, kimseye izletmeyeceğimi, eğitim süresince video ve soruları
            gerçek katılımla tamamlayacağımı, bilgilerimin doğru olduğunu ve sistemdeki kayıtların denetim amacıyla kullanılabileceğini kabul ederim.
        </p>

        <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:1rem;font-size:.9rem">
            <label style="display:flex;gap:.55rem;align-items:flex-start"><input type="checkbox" wire:model.live="aydinlatma" style="margin-top:.2rem"> Aydınlatma metnini okudum ve anladım.</label>
            <label style="display:flex;gap:.55rem;align-items:flex-start"><input type="checkbox" wire:model.live="taahhut" style="margin-top:.2rem"> Eğitim taahhüdünü okudum ve kabul ediyorum.</label>
        </div>

        <div style="margin-top:1.1rem">
            <x-filament::button wire:click="onayla" :disabled="! ($aydinlatma && $taahhut)">Onaylıyorum, Eğitimlere Geç</x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
