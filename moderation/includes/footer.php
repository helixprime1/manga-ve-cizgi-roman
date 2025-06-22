    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables (isteğe bağlı) -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js (isteğe bağlı) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 (güzel alert'ler için) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Özel JavaScript -->
    <script>
        // Notification sistemi
        function showNotification(message, type = 'info', duration = 5000) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: duration,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            const iconMap = {
                'success': 'success',
                'error': 'error',
                'warning': 'warning',
                'info': 'info'
            };

            Toast.fire({
                icon: iconMap[type] || 'info',
                title: message
            });
        }
        
        // Gelişmiş onay dialog'u
        function confirmAction(title, text, confirmButtonText = 'Evet', cancelButtonText = 'İptal') {
            return Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                reverseButtons: true
            });
        }
        
        // Loading göstergesi
        function showLoading(message = 'İşleniyor...') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        
        function hideLoading() {
            Swal.close();
        }
        
        // AJAX yardımcı fonksiyonu
        async function makeRequest(url, data = null, method = 'GET') {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }
            
            try {
                const response = await fetch(url, options);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return await response.json();
            } catch (error) {
                console.error('Request error:', error);
                showNotification('Bir hata oluştu: ' + error.message, 'error');
                throw error;
            }
        }
        
        // Form validasyonu
        function validateForm(formSelector) {
            const form = document.querySelector(formSelector);
            if (!form) return false;
            
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        }
        
        // Tablo sıralama (DataTables varsa)
        document.addEventListener('DOMContentLoaded', function() {
            // DataTables başlatma
            if (typeof $.fn.dataTable !== 'undefined') {
                $('.data-table').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
                    },
                    pageLength: 25,
                    responsive: true,
                    order: [[0, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: -1 } // Son sütun (işlemler) sıralanamaz
                    ]
                });
            }
            
            // Tooltip'leri etkinleştir
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Popover'ları etkinleştir
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Form submit'lerinde loading göster
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>İşleniyor...';
                        
                        // 10 saniye sonra butonu tekrar aktif et (timeout için)
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Gönder';
                        }, 10000);
                    }
                });
            });
            
            // Auto-refresh için (dashboard'da 5 dakikada bir)
            if (window.location.pathname.includes('index.php')) {
                setInterval(function() {
                    // Sadece kullanıcı aktifse refresh yap
                    if (document.hasFocus()) {
                        location.reload();
                    }
                }, 300000); // 5 dakika
            }
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+/ veya Cmd+/ ile arama kutusuna odaklan
                if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                    e.preventDefault();
                    const searchInput = document.querySelector('input[name="search"]');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }
                
                // Escape ile modal'ları kapat
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal.show');
                    modals.forEach(modal => {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                        }
                    });
                }
            });
            
            // Resim lazy loading
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
            
            // Sayfa yükleme animasyonu
            document.body.classList.add('loaded');
        });
        
        // Sidebar toggle fonksiyonu
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            sidebar.classList.toggle('show');
            
            // Local storage'da durumu sakla
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('show') ? 'false' : 'true');
        }
        
        // Sayfa yüklendiğinde sidebar durumunu kontrol et
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
            if (sidebarCollapsed === 'true' && window.innerWidth > 768) {
                document.querySelector('.sidebar').classList.add('collapsed');
                document.querySelector('.main-content').classList.add('expanded');
            }
        });
        
        // Gerçek zamanlı bildirim kontrolü (WebSocket yerine polling)
        function checkNotifications() {
            fetch('api/check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.hasNew) {
                        // Bildirim badge'ini güncelle
                        const badge = document.querySelector('.badge.rounded-pill.bg-danger');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = data.count > 0 ? 'inline' : 'none';
                        }
                    }
                })
                .catch(error => console.log('Notification check error:', error));
        }
        
        // Her 30 saniyede bir bildirim kontrolü
        setInterval(checkNotifications, 30000);
        
        // Sayfa görünürlük API'si ile aktif olmayan sekmelerde polling'i durdur
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Sayfa gizli, polling'i durdur
                clearInterval(window.notificationInterval);
            } else {
                // Sayfa görünür, polling'i başlat
                window.notificationInterval = setInterval(checkNotifications, 30000);
            }
        });
        
        // Çıkış onayı
        function confirmLogout() {
            confirmAction(
                'Çıkış Yapmak İstiyor Musunuz?',
                'Moderasyon panelinden çıkış yapacaksınız.',
                'Çıkış Yap',
                'İptal'
            ).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        }
        
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            showNotification('Beklenmeyen bir hata oluştu.', 'error');
        });
        
        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
            showNotification('Bir işlem başarısız oldu.', 'error');
        });
    </script>
</body>
</html> 