# Moderasyon Paneli

Modern ve kullanıcı dostu moderasyon paneli. Bu panel, moderatörlerin içerik yönetimi, yorum moderasyonu, kullanıcı yönetimi ve sistem istatistiklerini kolayca yönetebilmesi için tasarlanmıştır.

## 📋 Özellikler

### 🎯 Ana Özellikler
- **Dashboard**: Genel sistem durumu ve hızlı eylemler
- **İçerik Yönetimi**: Bekleyen içerikleri onaylama/reddetme
- **Yorum Moderasyonu**: Bildirilen yorumları inceleme
- **Rapor Yönetimi**: Kullanıcı raporlarını değerlendirme
- **Kullanıcı Moderasyonu**: Ban/uyarı sistemi
- **Aktivite Logları**: Tüm moderasyon işlemlerinin kaydı
- **İstatistikler**: Detaylı raporlar ve grafikler

### 🎨 Tasarım Özellikleri
- **Modern UI**: Bootstrap 5 tabanlı responsive tasarım
- **Gradient Renkler**: Görsel çekicilik için özel renk paleti
- **Animasyonlar**: Smooth geçişler ve hover efektleri
- **Dark Mode Hazır**: Sistem tercihlerine uyumlu
- **Mobile Responsive**: Tüm cihazlarda mükemmel görünüm

### 🔧 Teknik Özellikler
- **CSS Variables**: Kolay tema özelleştirmesi
- **Lazy Loading**: Performans optimizasyonu
- **Accessibility**: WCAG uyumlu erişilebilirlik
- **Print Styles**: Yazdırma için optimize edilmiş stiller
- **Progressive Enhancement**: Temel işlevsellik her zaman çalışır

## 🚀 Kurulum

### Gereksinimler
- PHP 7.4+
- MySQL 5.7+
- Web sunucu (Apache/Nginx)
- Modern web tarayıcısı

### Kurulum Adımları
1. Veritabanı kurulumu için `setup.sql` dosyasını çalıştırın
2. İlk moderatör hesabı otomatik oluşturulur:
   - **Kullanıcı Adı**: `moderator`
   - **Şifre**: `moderator123`
3. Panel URL'si: `http://yoursite.com/moderation/`

## 📁 Dosya Yapısı

```
moderation/
├── index.php              # Dashboard
├── content.php            # İçerik yönetimi
├── comments.php           # Yorum moderasyonu
├── reports.php            # Rapor yönetimi
├── users.php              # Kullanıcı moderasyonu
├── logs.php               # Aktivite logları
├── statistics.php         # İstatistikler
├── profile.php            # Moderatör profili
├── assets/
│   └── css/
│       └── moderation.css # Ana CSS dosyası
├── includes/
│   ├── header.php         # Sayfa başlığı
│   ├── sidebar.php        # Yan menü
│   └── footer.php         # Sayfa altı
├── api/
│   ├── preview_content.php    # İçerik önizleme
│   ├── export_logs.php        # Log dışa aktarma
│   ├── export_stats.php       # İstatistik raporu
│   └── check_notifications.php # Bildirim kontrolü
└── setup.sql              # Veritabanı kurulum scripti
```

## 🎨 CSS Özellikleri

### Renk Paleti
```css
:root {
    --primary-color: #667eea;    /* Ana renk */
    --secondary-color: #764ba2;  /* İkincil renk */
    --success-color: #28a745;    /* Başarı */
    --danger-color: #dc3545;     /* Hata */
    --warning-color: #ffc107;    /* Uyarı */
    --info-color: #17a2b8;       /* Bilgi */
}
```

### Özel Sınıflar
- `.text-gradient` - Gradient metin efekti
- `.shadow-custom` - Özel gölge efekti
- `.user-avatar` - Kullanıcı avatar stilleri
- `.badge-notification` - Bildirim badge'i
- `.loading` - Yükleme durumu

### Animasyonlar
- `fadeInUp` - Yukarıdan fade in
- `slideInLeft` - Soldan slide in
- `pulse` - Nabız efekti
- `spin` - Döngü animasyonu

## 🔧 Özelleştirme

### Renk Değiştirme
CSS değişkenlerini düzenleyerek kolayca tema değiştirebilirsiniz:

```css
:root {
    --primary-color: #your-color;
    --secondary-color: #your-color;
}
```

### Sidebar Genişliği
```css
.sidebar {
    width: 300px; /* Varsayılan: 280px */
}

.main-content {
    margin-left: 300px;
}
```

### Animasyon Süresi
```css
:root {
    --transition: all 0.5s ease; /* Varsayılan: 0.3s */
}
```

## 📱 Responsive Tasarım

### Breakpoint'ler
- **Desktop**: 1200px+
- **Tablet**: 768px - 1199px
- **Mobile**: 767px ve altı

### Mobile Özellikler
- Collapsible sidebar
- Touch-friendly buttons
- Optimized table views
- Swipe gestures

## ⚡ Performans

### Optimizasyonlar
- CSS minification hazır
- Lazy loading images
- Efficient animations
- Reduced repaints/reflows

### Tarayıcı Desteği
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## 🛡️ Güvenlik

### Özellikler
- CSRF koruması
- XSS koruması
- SQL injection koruması
- Rol bazlı yetki kontrolü

## 📊 İstatistikler

### Desteklenen Grafikler
- Doughnut charts (İçerik durumu)
- Bar charts (Eylem dağılımı)
- Line charts (Zaman serisi)
- Progress bars (Moderatör aktivitesi)

## 🔄 Gerçek Zamanlı Özellikler

### Bildirimler
- 30 saniyede bir otomatik kontrol
- Yeni işlemler için badge güncellemesi
- Sayfa görünürlük API'si ile optimizasyon

## 📤 Dışa Aktarma

### Desteklenen Formatlar
- **CSV**: Aktivite logları
- **HTML**: İstatistik raporları
- **Print**: Yazdırma için optimize edilmiş görünüm

## 🎯 Gelecek Özellikler

- [ ] Dark/Light mode toggle
- [ ] Real-time WebSocket notifications
- [ ] Advanced filtering options
- [ ] Bulk operations
- [ ] Mobile app support
- [ ] API endpoints
- [ ] Plugin system

## 📝 Notlar

### Geliştirici Notları
- CSS Grid ve Flexbox kullanımı
- Modern CSS özelliklerinden yararlanma
- Progressive enhancement yaklaşımı
- Semantic HTML yapısı

### Bakım
- CSS dosyası düzenli olarak güncellenir
- Browser compatibility testleri yapılır
- Performance monitoring aktif

## 📞 Destek

Herhangi bir sorun yaşarsanız:
1. Browser console'u kontrol edin
2. CSS dosyasının doğru yüklendiğinden emin olun
3. JavaScript hatalarını kontrol edin
4. Network sekmesini inceleyin

---

**Not**: Bu panel sürekli geliştirilmektedir. Yeni özellikler ve iyileştirmeler düzenli olarak eklenmektedir. 