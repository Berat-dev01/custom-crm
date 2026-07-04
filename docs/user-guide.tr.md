# CRM Kullanıcı Kılavuzu

Bu kılavuz, CRM'i günlük işinde kullanan satış ve operasyon ekipleri içindir. Teknik kurulum için `installation.md`'ye bakın.
> **Not:** İki faktörlü doğrulama, webhook ve takvim aboneliği opsiyonel özelliklerdir; kurulumunuzda görünmüyorsa yöneticiniz tarafından etkinleştirilmemiştir.

## 1. Giriş ve Hesap Güvenliği

- Panele `https://crm.firmaniz.com/admin` adresinden e-posta + parola ile girersiniz.
- **İki faktörlü doğrulama (önerilir):** Sağ menüden **System → Security** açın, *Enable two-factor* deyin, QR kodu Google Authenticator/1Password ile tarayın ve 6 haneli kodu girin. Gösterilen **kurtarma kodlarını** güvenli bir yere kaydedin — yalnızca bir kez gösterilir.
- Girişte 5 hatalı denemeden sonra 1 dakika beklemeniz gerekir.

## 2. Panel (Dashboard)

Açılış ekranında dönem seçimine göre: açık pipeline, kazanılan/kaybedilen fırsatlar, geciken görevler, teklif durum dağılımı, yaklaşan görevler ve son aktiviteler görünür. Yönetici rolleri tüm ekibi, satış rolü yalnızca kendi kayıtlarını görür.

## 3. Kişiler ve Şirketler

- **Yeni kayıt:** Liste ekranındaki *New* düğmesi. Kişiler bir şirkete bağlanabilir ve yaşam döngüsü aşaması (lead → müşteri) taşır.
- **Filtre ve arama:** Üstteki kompakt filtre çubuğu + *Advanced* panel. Sık kullandığınız filtreleri **Saved Filters** ile kaydedin; ekip geneli paylaşabilirsiniz.
- **Import:** Liste ekranında *Import* → CSV/XLSX yükleyin → önizleme adımında eşleşmeleri kontrol edin → onaylayın. Sonuç bildirim olarak düşer; hatalı satır raporu indirilebilir.
- **Export:** *Export* düğmesi seçili kolonlarla CSV verir.
- **Etiketler:** Kayıtlara renkli etiketler ekleyin; listelerde etikete göre filtreleyin. Toplu etiketleme için kayıtları seçip alttaki çubuğu kullanın.

## 4. Fırsatlar (Deals)

- **Kanban:** Fırsatları aşamalar arasında sürükleyip bırakın; aşama toplamları anında güncellenir. Klavye ile çalışıyorsanız fırsatı açıp düzenleme formundan aşamayı değiştirebilirsiniz.
- **Kazanıldı / Kaybedildi:** Fırsat detayında *Close Won / Close Lost*. Kaybedilende neden sorulur. Fırsat sahibine bildirim (ve tercihe göre e-posta) gider.
- Fırsat detayından görev, aktivite ve teklif ekleyebilirsiniz; hepsi zaman çizelgesinde görünür.

## 5. Görevler

- Görevlere öncelik, vade ve hatırlatma verin; hatırlatmalar bildirim + e-posta olarak gelir.
- **Takvim aboneliği:** **System → Security → Calendar feed** bölümünden özel ICS bağlantınızı oluşturun ve Google Takvim/Outlook'a "URL ile abone ol" yöntemiyle ekleyin. Bağlantıyı yenilerseniz eski bağlantı geçersiz olur.

## 6. Teklifler (Quotes)

- Teklif kalemleri, KDV ve iskonto sunucu tarafında hesaplanır; PDF önizleme ve indirme her zaman açıktır.
- **Gönderim:** *Send* dediğinizde teklif "sent" olur ve müşterinin e-postasına PDF ekli, **onay bağlantılı** bir e-posta gider.
- **Müşteri onayı:** Müşteri bağlantıdan teklifi görür, *Accept* veya gerekçeyle *Decline* der. Sonuç size bildirim olarak düşer ve teklif geçmişine işlenir.
- **Durum kuralları:** Kabul edilmiş/reddedilmiş teklif düzenlenemez; değişiklik için *Duplicate* ile taslak kopya oluşturun. Süresi dolan teklif yeniden gönderilebilir.

## 7. Bildirimler ve E-posta Tercihleri

- Zil simgesi son bildirimleri gösterir; **Notifications** sayfasında tümü listelenir.
- Aynı sayfanın **Email preferences** bölümünden hangi olayların e-posta ile de geleceğini kişisel olarak seçersiniz. Uygulama içi bildirimler her zaman açıktır.

## 8. Arama

Üst çubuktaki global arama; kişi, şirket, fırsat, görev ve tekliflerde birlikte arar.

## 9. Yönetici İşlemleri (System)

*(crm.settings.manage yetkisi gerektirir)*

- **Settings:** Firma bilgileri, logo, para birimi/KDV, teklif prefix'i, bildirim anahtarları, AI ayarları.
- **Users:** Kullanıcı ve rol yönetimi (owner/manager/sales/support/viewer).
- **API Tokens:** Dış entegrasyonlar için bearer token üretin; token yalnızca bir kez gösterilir, gerektiğinde iptal edin.
- **Webhooks:** CRM olaylarını dış sistemlere (Zapier/Make dahil) iletin; teslimat geçmişi aynı ekranda.
- **Audit Log:** Kim, neyi, ne zaman değiştirdi — alan bazında eski→yeni değerlerle.
- **Trash:** Silinen kişi/şirket/fırsat/teklifleri geri yükleyin veya kalıcı silin.

## 10. Sık Sorulanlar

**E-posta gelmiyor?** Önce kendi tercihlerinizi (Notifications → Email preferences), sonra yöneticinizle global e-posta anahtarını ve SMTP ayarını kontrol edin.

**Yanlışlıkla kayıt sildim.** Yöneticiniz **System → Trash** ekranından geri yükleyebilir.

**Teklifte fiyat değişecek ama teklif kabul edilmiş.** Kabul edilen teklif kilitlidir; *Duplicate* ile yeni taslak açın, düzenleyip yeniden gönderin.
