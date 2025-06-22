// Bildirim sistemi JavaScript dosyası

class NotificationSystem {
    constructor() {
        this.updateInterval = null;
        this.notificationDropdown = document.getElementById('notificationDropdown');
        this.notificationBadge = document.getElementById('notificationBadge');
        this.notificationList = document.getElementById('notificationList');
        
        this.init();
    }
    
    init() {
        // Sayfa yüklendiğinde bildirimleri getir
        this.updateNotificationCount();
        this.loadRecentNotifications();
        
        // Periyodik güncelleme başlat (30 saniyede bir)
        this.startPeriodicUpdate();
        
        // Event listener'ları ekle
        this.bindEvents();
    }
    
    bindEvents() {
        // Bildirim dropdown'ı açıldığında
        if (this.notificationDropdown) {
            this.notificationDropdown.addEventListener('show.bs.dropdown', () => {
                this.loadRecentNotifications();
            });
        }
        
        // Tümünü okundu işaretle butonu
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="mark-all-read"]')) {
                e.preventDefault();
                this.markAllAsRead();
            }
            
            // Tek bildirim okundu işaretle
            if (e.target.matches('[data-action="mark-read"]')) {
                e.preventDefault();
                const notificationId = e.target.dataset.notificationId;
                this.markAsRead(notificationId);
            }
            
            // Bildirim sil
            if (e.target.matches('[data-action="delete-notification"]')) {
                e.preventDefault();
                const notificationId = e.target.dataset.notificationId;
                this.deleteNotification(notificationId);
            }
        });
        
        // Sayfa görünürlük değiştiğinde
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateNotificationCount();
            }
        });
    }
    
    async updateNotificationCount() {
        try {
            const response = await fetch('api/notifications.php?action=get_count');
            const data = await response.json();
            
            if (data.success) {
                this.updateBadge(data.count);
            }
        } catch (error) {
            console.error('Bildirim sayısı güncellenirken hata:', error);
        }
    }
    
    async loadRecentNotifications() {
        try {
            const response = await fetch('api/notifications.php?action=get_recent&limit=10');
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.notifications);
            }
        } catch (error) {
            console.error('Bildirimler yüklenirken hata:', error);
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notification_id', notificationId);
            
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // UI'ı güncelle
                const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.remove('unread');
                    notificationElement.classList.add('read');
                }
                
                this.updateNotificationCount();
            }
        } catch (error) {
            console.error('Bildirim okundu işaretlenirken hata:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Tüm bildirimleri okundu olarak işaretle
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                    item.classList.add('read');
                });
                
                this.updateBadge(0);
                this.showToast('Tüm bildirimler okundu olarak işaretlendi.', 'success');
            }
        } catch (error) {
            console.error('Tüm bildirimler okundu işaretlenirken hata:', error);
        }
    }
    
    async deleteNotification(notificationId) {
        if (!confirm('Bu bildirimi silmek istediğinizden emin misiniz?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('notification_id', notificationId);
            
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // UI'dan kaldır
                const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.remove();
                }
                
                this.updateNotificationCount();
                this.showToast('Bildirim silindi.', 'success');
            }
        } catch (error) {
            console.error('Bildirim silinirken hata:', error);
        }
    }
    
    updateBadge(count) {
        if (this.notificationBadge) {
            if (count > 0) {
                this.notificationBadge.textContent = count > 99 ? '99+' : count;
                this.notificationBadge.style.display = 'inline-block';
                this.notificationBadge.classList.add('animate-pulse');
            } else {
                this.notificationBadge.style.display = 'none';
                this.notificationBadge.classList.remove('animate-pulse');
            }
        }
        
        // Sayfa başlığını güncelle
        this.updatePageTitle(count);
    }
    
    updatePageTitle(count) {
        const baseTitle = document.title.replace(/^\(\d+\) /, '');
        if (count > 0) {
            document.title = `(${count}) ${baseTitle}`;
        } else {
            document.title = baseTitle;
        }
    }
    
    renderNotifications(notifications) {
        if (!this.notificationList) return;
        
        if (notifications.length === 0) {
            this.notificationList.innerHTML = `
                <div class="dropdown-item text-center py-3">
                    <i class="fas fa-bell-slash text-muted fa-2x mb-2"></i>
                    <p class="text-muted mb-0">Henüz bildiriminiz yok</p>
                </div>
            `;
            return;
        }
        
        const notificationHTML = notifications.map(notification => `
            <div class="dropdown-item notification-item ${notification.is_read ? 'read' : 'unread'}" 
                 data-notification-id="${notification.id}">
                <div class="d-flex align-items-start">
                    <div class="notification-icon me-3">
                        <i class="${notification.icon}"></i>
                    </div>
                    <div class="notification-content flex-grow-1">
                        <h6 class="notification-title mb-1">${notification.title}</h6>
                        <p class="notification-message mb-1">${notification.message}</p>
                        <small class="notification-time text-muted">${notification.created_at}</small>
                    </div>
                    <div class="notification-actions">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                ${!notification.is_read ? `
                                    <li>
                                        <a class="dropdown-item" href="#" data-action="mark-read" data-notification-id="${notification.id}">
                                            <i class="fas fa-check me-2"></i>Okundu İşaretle
                                        </a>
                                    </li>
                                ` : ''}
                                <li>
                                    <a class="dropdown-item text-danger" href="#" data-action="delete-notification" data-notification-id="${notification.id}">
                                        <i class="fas fa-trash me-2"></i>Sil
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                ${notification.link ? `
                    <div class="notification-link mt-2">
                        <a href="${notification.link}" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt me-1"></i>Görüntüle
                        </a>
                    </div>
                ` : ''}
            </div>
        `).join('');
        
        // Tümünü okundu işaretle butonu ekle
        const unreadCount = notifications.filter(n => !n.is_read).length;
        const actionButtons = unreadCount > 0 ? `
            <div class="dropdown-divider"></div>
            <div class="dropdown-item text-center">
                <button class="btn btn-sm btn-success" data-action="mark-all-read">
                    <i class="fas fa-check-double me-2"></i>Tümünü Okundu İşaretle
                </button>
            </div>
        ` : '';
        
        this.notificationList.innerHTML = notificationHTML + actionButtons;
    }
    
    startPeriodicUpdate() {
        // Önceki interval'ı temizle
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        
        // 30 saniyede bir güncelle
        this.updateInterval = setInterval(() => {
            this.updateNotificationCount();
        }, 30000);
    }
    
    stopPeriodicUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
    
    showToast(message, type = 'info') {
        // Bootstrap toast göster
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'primary'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        // Toast container'ı oluştur veya bul
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Toast'ı ekle
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        // Toast'ı göster
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        // Toast kapandıktan sonra DOM'dan kaldır
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    // Yeni bildirim geldiğinde animasyon
    showNewNotificationAnimation() {
        if (this.notificationBadge) {
            this.notificationBadge.classList.add('animate-bounce');
            setTimeout(() => {
                this.notificationBadge.classList.remove('animate-bounce');
            }, 1000);
        }
    }
    
    // Sayfa kapatılırken temizlik
    destroy() {
        this.stopPeriodicUpdate();
    }
}

// CSS animasyonları ekle
const style = document.createElement('style');
style.textContent = `
    .notification-item {
        border-left: 3px solid transparent;
        transition: all 0.3s ease;
    }
    
    .notification-item.unread {
        background-color: #f8f9fa;
        border-left-color: #007bff;
    }
    
    .notification-item:hover {
        background-color: #e9ecef;
    }
    
    .notification-icon {
        width: 32px;
        text-align: center;
    }
    
    .notification-title {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .notification-message {
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .notification-time {
        font-size: 0.75rem;
    }
    
    .animate-pulse {
        animation: pulse 2s infinite;
    }
    
    .animate-bounce {
        animation: bounce 1s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    #notificationBadge {
        animation: none;
        transition: all 0.3s ease;
    }
    
    .toast-container {
        z-index: 9999;
    }
`;
document.head.appendChild(style);

// Sayfa yüklendiğinde bildirim sistemini başlat
document.addEventListener('DOMContentLoaded', () => {
    // Sadece giriş yapmış kullanıcılar için
    if (document.getElementById('notificationDropdown')) {
        window.notificationSystem = new NotificationSystem();
    }
});

// Sayfa kapatılırken temizlik
window.addEventListener('beforeunload', () => {
    if (window.notificationSystem) {
        window.notificationSystem.destroy();
    }
}); 