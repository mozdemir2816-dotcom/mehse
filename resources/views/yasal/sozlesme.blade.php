{{-- Kaynak: kullanıcının verdiği Mehse_com_Hizmet_Sozlesmesi_ve_KVKK.docx. 01.11.2026'da kullanıcının
     talimatıyla yer tutucular dolduruldu (sürüm 1.3). Metin değişince config
     isg.kayit.yasal_metinler.sozlesme.revizyon'u güncelleyin. Sağlayıcı kimliği: config isg.kayit.saglayici;
     boşsa sarı taslak işareti görünür. --}}
@php
    $saglayici = config('isg.kayit.saglayici');
    $bos = fn (string $t) => new \Illuminate\Support\HtmlString('<mark class="mehse-yasal-bos">'.e($t).'</mark>');
    // Müşteri (md.1): giriş yapmış kullanıcı Fatura & Profil'de ünvan veya vergi/TC no girdiyse onun bilgileri;
    // değilse (kayıt/başvuru ekranı, misafir) genel tanım.
    $u = auth()->user();
    $musteri = null;
    if ($u && (filled($u->fatura_unvan) || filled($u->vergi_no))) {
        $vd = trim((string) $u->vergi_dairesi);
        if ($vd !== '' && ! str_contains(mb_strtolower($vd), 'vergi dairesi')) {
            $vd .= ' Vergi Dairesi';
        }
        $musteri = [
            'ad' => $u->fatura_unvan ?: $u->name,
            'vergi' => filled($u->vergi_no) ? ($vd !== '' ? $vd.', ' : '').'VKN/TCKN: '.$u->vergi_no : null,
            'adres' => trim(implode(' ', array_filter([$u->fatura_adresi, collect([$u->fatura_ilce, $u->fatura_il])->filter()->implode(' / ')]))) ?: null,
            'eposta' => $u->email,
        ];
        $musteri['kunye'] = $musteri['ad'].($musteri['vergi'] ? ' ('.$musteri['vergi'].')' : '').'.'
            .($musteri['adres'] ? ' Adres: '.$musteri['adres'].'.' : '')
            .' Bildirim e-postası: '.$musteri['eposta'].'.';
    }
@endphp
<p>İnternet sitesi: Mehse.com  |  Revizyon: 01.11.2026  |  Sürüm: 1.3</p>
<p>Bu sözleşme, Mehse tarafından sunulan İSG yönetim platformunun kullanımını düzenler. Pakete özgü ticari koşullar (ücret, ödeme ve abonelik dönemi) müşteriye ayrıca yazılı olarak bildirilir ve müşterinin kabulüyle uygulanır.</p>
<h3>1 Taraflar</h3>
<p>Sağlayıcı: Mehse adıyla faaliyet gösteren {{ $saglayici['unvan'] ?: $bos('[gerçek kişi adı veya tam ticaret unvanı]') }}. Adres: {{ $saglayici['adres'] }}. Vergi dairesi ve numarası: {{ $saglayici['vergi_dairesi'] }} / {{ $saglayici['vergi_no'] ?: $bos('[vergi numarası]') }}. Bildirim e-postası: {{ $saglayici['eposta'] }}. Telefon: {{ $saglayici['telefon'] }}.</p>
@if ($musteri)
<p>Müşteri: {{ $musteri['kunye'] }} Bu bilgiler müşterinin Fatura &amp; Profil sayfasında beyan ettiği bilgilerdir; müşteri bu bilgilerin doğruluğundan ve güncel tutulmasından sorumludur.</p>
@else
<p>Müşteri: Başvuru veya kayıt formunda kimlik ve iletişim bilgilerini beyan eden OSGB, işyeri ya da bireysel uzmandır. Müşterinin unvanı veya adı, adresi, vergi bilgileri, yetkili kişisi ve bildirim e-postası başvuru kaydındaki bilgilerdir; müşteri bu bilgilerin doğruluğundan ve güncel tutulmasından sorumludur.</p>
@endif
<p>Mehse firma adı, Mehse.com ise internet sitesi adresi olarak kullanılır; sözleşmenin tarafı yukarıda kimliği belirtilen Sağlayıcıdır.</p>
<h3>2 Konu ve kapsam</h3>
<p>Platform; işyeri ve çalışan kayıtları, görev ve saha ziyaretleri, risk değerlendirmesi, eğitim, acil durum, belge ve süre takibi gibi İSG süreçlerinin dijital ortamda yönetimine yardımcı olur. Kullanıma açılan modüller satın alınan pakette belirtilir. Yazılım kullanımı, ayrıca sözleşme yapılmadıkça iş güvenliği uzmanlığı, işyeri hekimliği, OSGB hizmeti, resmî onay veya hukuki danışmanlık hizmeti oluşturmaz. Hatırlatmalar ve örnek belgeler, müşterinin mevzuata uygunluk kontrolünün ve yetkili kişilerin incelemesinin yerine geçmez. Platform, işyeri, görevlendirme, saha ziyareti ve performans gibi operasyonel kayıtların tutulmasına imkân sağlar. Resmî kurumlarla entegrasyon veya resmî bildirim hizmeti, yalnızca pakette açıkça belirtilmişse kapsama dahildir.</p>
<h3>3 Başvuru deneme ve abonelik</h3>
<p>Müşteri, başvuru bilgilerinin doğru ve güncel olduğunu ve temsil ettiği kurum adına işlem yapmaya yetkili bulunduğunu beyan eder. Başvurunun Sağlayıcı tarafından kabulü ve sözleşmenin müşteri tarafından onaylanması üzerine kullanıcı hesabı açılır. Deneme süresi ve başlangıç tarihi başvuru ekranında gösterilir; deneme süresi en fazla 90 gündür. Deneme tanımlanmamışsa ücretli paket, müşterinin ayrıca kabul ettiği koşullarla başlatılır.</p>
<p>Paket ve modüller: Risk Değerlendirmesi, Kontrol Merkezi, Eğitim & Sertifikasyon, İSG Kurulu, Periyodik Kontrol ve Acil Durum Planı modülleri. Kullanıcı / işyeri / depolama sınırı: seçilen paket kotası ve yetki tanımları kapsamında. Deneme süresi: OSGB başvurularında 90 gün ücretsiz kurumsal deneme. Abonelik başlangıcı ve bitişi, hizmet bedeli ve para birimi, KDV durumu, ödeme dönemi ve vadesi: ücretli pakete geçişte müşteriye ayrıca yazılı olarak bildirilir. Destek kanalı ve saatleri: hafta içi 09:00 - 18:00 (e-posta ve WhatsApp kurumsal destek).</p>
<p>Deneme sonunda ücretli aboneliğe geçiş müşterinin ayrıca onayına bağlıdır. Yenileme ve yeni dönem fiyatı önceden bildirilir; müşterinin açık kabulü olmadan yeni dönem için ücret doğurulmaz. Paket dışı hizmetler ayrıca fiyatlandırılır ve onay alınmadan uygulanmaz.</p>
<p>Kullanım yalnızca müşterinin kendi faaliyetleri ve yetkili olduğu işyerleriyle sınırlıdır. Bireysel uzman, işyeri ve OSGB hesaplarında erişim, tanımlanan rol ve paket yetkilerine göre sağlanır.</p>
<h3>4 Müşterinin yükümlülükleri</h3>
<p>Müşteri; hesapları yetkili kişilerle sınırlar, erişim bilgilerini korur, ayrılan kişilerin yetkisini kaldırır ve şüpheli erişimi gecikmeden bildirir. Girilen verilerin doğruluğunu, yükleme ve işleme yetkisini, belgelerin güncelliğini ve yasal işlemlerin zamanında tamamlanmasını sağlar. Platforma zararlı yazılım yükleyemez, yetkisiz erişim deneyemez ve başkalarının haklarını ihlal edemez. Kanunen izin verilen hâller saklı kalmak üzere yazılımı kopyalayamaz, yeniden satamaz veya tersine mühendislik yapamaz. Müşteri, 6331 sayılı Kanun ve ilgili mevzuattan kaynaklanan kendi yükümlülüklerini yerine getirir. Çalışan ve diğer üçüncü kişilerin verilerini işlerken gerekli hukuki şartları sağlar ve kendi aydınlatma yükümlülüğünü yerine getirir.</p>
<h3>5 Hizmetin sunulması bakım ve destek</h3>
<p>Sağlayıcı, kararlaştırılan modülleri sunar; erişim yetkileri, gizlilik, bakım ve güvenlik için uygun teknik ve idari tedbirleri uygular. Planlı bakım ve önemli kesintileri mümkün olduğunda önceden bildirir. Kesintisiz veya hatasız çalışma garantisi verilmez; bu hüküm Sağlayıcının sözleşmeden ve kanundan doğan sorumluluğunu kaldırmaz. Yedekleme ve veri iletimi: günlük otomatik veritabanı yedeklemesi, 30 günlük geriye dönük saklama ve SSL/TLS şifreli veri iletimi. Destek, kabul edilen paket kapsamı ve bildirilen çalışma saatlerinde sunulur. Müşteri kaynaklı internet, cihaz ve erişim sorunları müşterinin kontrol alanındadır. Taraflar hizmet kesintisinin etkisini azaltmak için iş birliği yapar.</p>
<h4 class="mehse-yasal-bolum">Veri Güvenliği ve Sorumluluk</h4>
<h3>6 Kişisel veriler ve gizlilik</h3>
<p>Taraflar, kişisel verileri yalnızca belirli ve hukuka uygun amaçlarla, gerekli ölçüde işler. Sağlayıcı, başvuru, hesap ve faturalama verileri bakımından kendi faaliyetleri ölçüsünde veri sorumlusudur. Müşterinin platforma yüklediği çalışan ve işyeri verilerindeki roller, işleme amaçlarını ve araçlarını belirleyen tarafa göre değerlendirilir. Sağlayıcı, müşteri adına veri işlediği işlemlerde müşterinin belgelenmiş hukuka uygun talimatlarıyla hareket eder.</p>
<p>İşlenen veri türleri, ilgili kişi grupları, alt hizmet sağlayıcılar, erişim yetkileri, ihlal bildirimi, veri iadesi ve imhası, veri işleme ekinde somutlaştırılır. Sağlık verileri gibi özel nitelikli veriler, uygun hukuki şart ve erişim tedbirleri sağlanmadan yüklenmez. Gizli bilgiler hizmetin ifası ve hukuki zorunluluklar dışında açıklanmaz. Bir veri ihlalinin öğrenilmesi hâlinde taraflar gecikmeden birbirini bilgilendirir ve kanuni bildirim süreçlerinde iş birliği yapar. Taraflar, öğrendikleri ticari sırları diğer tarafın izni veya hukuki zorunluluk olmadan açıklamaz. Başvuru ve hesap süreçlerinin ayrıntıları ekli aydınlatma metninde (mehse.com/kvkk), internet sitesinde ve platformda kullanılan çerezler Çerez Politikasında (mehse.com/cerez-politikasi) açıklanır. Yurt dışı aktarım varsa ayrıca uygulanabilir hukuki koşullar sağlanır.</p>
<h3>7 Fikrî mülkiyet ve veri sahipliği</h3>
<p>Platformun yazılımına ve tasarımına ilişkin haklar Sağlayıcıya veya ilgili hak sahibine aittir. Müşterinin yüklediği içerikler üzerindeki hakları korunur. Sağlayıcı bu içerikleri hizmeti sunmak için gereken ölçüde kullanabilir; ayrıca hukuki dayanak olmadan reklam, pazarlama veya başka müşterilerin yararına kullanamaz. Müşteriye, abonelik süresince paket sınırları içinde sınırlı, devredilemez ve münhasır olmayan kullanım hakkı tanınır. Yazılımın kullanımı fikrî mülkiyet haklarının devri anlamına gelmez.</p>
<p>Müşteri, sona ermeden itibaren 30 gün içinde kayıtlarının mevcut dışa aktarım formatlarında teslimini isteyebilir. Format ve teslim yöntemi: platformun sunduğu Excel ve PDF dışa aktarımları, e-posta veya indirme bağlantısıyla. Yasal saklama yükümlülüğü bulunmayan kayıtlar bu sürenin ardından belirlenen imha takvimine göre silinir veya anonimleştirilir. Yedeklerden silinme süresi: en geç 30 gün. Saklanması zorunlu verilerin erişimi amaçla sınırlandırılır. Fesih, müşterinin kendi yasal kayıt saklama yükümlülüğünü ortadan kaldırmaz.</p>
<h3>8 Sorumluluğun kapsamı ve sınırı</h3>
<p>Platform bir yönetim ve takip aracıdır. Mevzuata uyum, resmî bildirim, risk değerlendirmesi, eğitim ve sağlık gözetimi gibi yükümlülüklerde müşterinin ve ilgili görevlilerin kanuni sorumlulukları devam eder. Hatırlatmanın ulaşmaması veya örnek belgenin kullanılması, bu yükümlülükleri ortadan kaldırmaz.</p>
<p>Sağlayıcı; müşterinin hatalı veri girişi, yetkisiz işlemleri veya kendi yükümlülüklerini yerine getirmemesi nedeniyle doğan sonuçlardan, kendisine yüklenebilen kusur bulunmadıkça sorumlu tutulamaz. Sağlayıcının kendi kusuru ve kanunen üstlendiği güvenlik, gizlilik ve hizmet yükümlülükleri saklıdır.</p>
<p>Kanunen sınırlandırılmasına izin verilen ve ayrıca müzakere edilerek kabul edilen hafif kusura dayalı sözleşmesel zararlar bakımından, Sağlayıcının toplam tazminat sorumluluğu zarara yol açan olaydan önceki 12 ayda bu hizmet için müşterinin ödediği toplam bedelle sınırlıdır. Bu kayıt ayrıca kabul edilmemişse genel sorumluluk hükümleri uygulanır. Bedelsiz denemede sıfır tutarlı bir sorumluluk sınırı uygulanmaz. Kasıt, ağır kusur, kişisel veri ve gizlilik ihlalleri ile kanunen sınırlandırılamayan sorumluluklar bu sınırın dışındadır. Bu sözleşme, kamu kurumlarının ve üçüncü kişilerin kanuni haklarını sınırlandırmaz.</p>
<h3>9 Askıya alma ve fesih</h3>
<p>Ödeme veya diğer sözleşme ihlallerinde, acil güvenlik ve hukuka aykırılık hâlleri dışında, müşteriye yazılı bildirimle 15 gün düzeltme süresi verilir. İhlalin giderilmemesi hâlinde ilgili hizmet askıya alınabilir veya sözleşme feshedilebilir. Acil tedbirler gerekçesiyle müşteriye bildirilir ve risk ortadan kalktığında gözden geçirilir. Esaslı ihlal nedeniyle haklı fesih hakkı her iki taraf için saklıdır. Sağlayıcının kusurundan kaynaklanan sona ermede, sunulmayan döneme ait peşin bedel iade edilir; diğer iadeler emredici hükümler ve kabul edilen paket koşullarına göre belirlenir. Taraflar, kabul edilen paket koşullarındaki ihbar süresine uyarak aboneliği yazılı bildirimle sona erdirebilir. Olağan fesih ihbar süresi: 30 gün. Fesih; doğmuş alacakları, gizlilik yükümlülüklerini ve hukuken devam etmesi gereken hükümleri ortadan kaldırmaz. Veri teslimi ve silme işlemlerinde 7 nci madde uygulanır.</p>
<h4 class="mehse-yasal-bolum">Yürürlük ve Tarafların Onayı</h4>
<h3>10 Uygulanacak hukuk ve uyuşmazlık</h3>
<p>Bu sözleşmeye Türkiye Cumhuriyeti hukuku uygulanır. Taraflar uyuşmazlıkları öncelikle iyi niyetli görüşmelerle çözmeye çalışır; bu süreç kanuni başvuru sürelerini durdurmaz. Çözüm sağlanamazsa kanunen görevli ve yetkili mahkeme ve icra dairelerine başvurulur. Zorunlu arabuluculuk ve diğer zorunlu başvuru yolları saklıdır. Tarafların hukuki statüsü ve kanuni koşullar doğrulanmadan belirli bir şehir mahkemesi için münhasır yetki öngörülmez.</p>
<h3>11 Yürürlük bildirimler ve değişiklikler</h3>
<p>Müşteri, başvuru ekranında “Mehse Hizmet ve Kullanım Sözleşmesini okudum ve kabul ediyorum” seçeneğini işaretleyerek veya sözleşmeyi imzalayarak kabulünü bildirir. Sözleşme, müşterinin kabulü ve Sağlayıcının başvuruyu onaylamasıyla yürürlüğe girer. Elektronik onayda kabul edilen metnin sürümü, tarih ve onay kaydı saklanır; müşterinin metne erişmesi sağlanır.</p>
<p>Sağlayıcı, güncellemeleri platform veya kayıtlı e-posta üzerinden bildirir. Ücret, kapsam, sorumluluk ve fesih koşullarındaki esaslı değişiklikler müşterinin açık kabulüyle uygulanır; sessiz kalma tek başına kabul sayılmaz. Geçmiş işlemlere geriye dönük koşul değişikliği uygulanmaz. Kanunen zorunlu değişikliklerin uygulanması saklıdır.</p>
<p>Taraflar iletişim bilgilerini güncel tutar. Kanunen özel şekle bağlı bildirimler uygun yöntemle yapılır. Bu sözleşmeye eklenen paket ve hizmet koşulları, taraflarca kabul edildiği ölçüde sözleşmenin parçasıdır. KVKK aydınlatma metni bilgilendirme amacı taşır ve açık rıza yerine geçmez.</p>
<h3>Paket ve eklerin teyidi</h3>
<p>Paket / sipariş numarası, kabul edilen ücret ve ödeme planı, hizmet başlangıç ve bitiş tarihi: ücretli pakete geçişte müşteriye ayrıca yazılı olarak bildirilir.</p>
<p>Fesih ihbar süresi: 30 gün.</p>
<p>Destek ve yedekleme koşulları: bu sözleşmenin 3 üncü ve 5 inci maddeleri.</p>
<p>Veri işleme eki: Mehse KVKK Aydınlatma Metni ve müşteri talebiyle düzenlenen veri işleme eki.</p>
<p>8 inci maddedeki müzakere edilen sorumluluk sınırı: ayrıca yazılı olarak kabul edilmedikçe uygulanmaz; genel sorumluluk hükümleri geçerlidir.</p>
<p>Ayrı kabul tarihi: elektronik onay kaydındaki tarih.</p>
<p>Hizmet ve Kullanım Sözleşmesini ve yukarıda belirtilen doldurulmuş paket koşullarını okudum ve kabul ediyorum.</p>
<p>Sağlayıcı: Mehse</p>
<p>Yetkili adı ve soyadı: __________________________</p>
<p>Tarih ve imza: _________________________________</p>
<p>Müşteri unvanı veya adı: {{ $musteri['ad'] ?? '_______________________' }}</p>
<p>Yetkili adı ve soyadı: __________________________</p>
<p>Tarih ve imza: _________________________________</p>
