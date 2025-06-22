// Modern JavaScript - MangaÇizgiRoman
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Site URL'ini al
    const siteUrl = window.location.origin;
    
    // Navbar Scroll Efekti (Header içinde olduğu için burada tekrar etmiyoruz)
    
    // Lazy Loading for Images
    const lazyImages = document.querySelectorAll('img[data-lazy]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.lazy;
                    img.classList.add('fade-in');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    }
    
    // Dynamic Search with Debounce
    const searchInputs = document.querySelectorAll('.search-input');
    let searchTimeout;
    
    function debounce(func, wait) {
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(searchTimeout);
                func(...args);
            };
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(later, wait);
        };
    }
    
    searchInputs.forEach(input => {
        const searchResults = document.createElement('div');
        searchResults.className = 'search-results position-absolute w-100 bg-white shadow-lg rounded-bottom border border-top-0 d-none';
        searchResults.style.maxHeight = '400px';
        searchResults.style.overflowY = 'auto';
        searchResults.style.zIndex = '1050';
        input.parentElement.appendChild(searchResults);
        
        const performSearch = debounce(async (query) => {
            if (query.length < 2) {
                searchResults.classList.add('d-none');
                return;
            }
            
            try {
                const response = await fetch(`${siteUrl}/api/search.php?q=${encodeURIComponent(query)}`);
                if (!response.ok) throw new Error('Arama başarısız');
                
                const data = await response.json();
                
                if (data.length > 0) {
                    let html = '<div class="list-group list-group-flush">';
                    data.forEach(item => {
                        html += `
                            <a href="${siteUrl}/view.php?id=${item.id}" class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-center">
                                    <img src="${siteUrl}/uploads/covers/${item.cover_image}" 
                                         class="rounded me-3" width="40" height="40" 
                                         style="object-fit: cover;" alt="${item.title}">
                                    <div>
                                        <h6 class="mb-0">${item.title}</h6>
                                        <small class="text-muted">${item.type === 'manga' ? 'Manga' : 'Çizgi Roman'}</small>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                    searchResults.innerHTML = html;
                } else {
                    searchResults.innerHTML = '<div class="p-3 text-center text-muted">Sonuç bulunamadı</div>';
                }
                
                searchResults.classList.remove('d-none');
            } catch (error) {
                console.error('Arama hatası:', error);
                searchResults.innerHTML = '<div class="p-3 text-center text-danger">Arama yapılırken bir hata oluştu</div>';
                searchResults.classList.remove('d-none');
            }
        }, 300);
        
        input.addEventListener('input', (e) => performSearch(e.target.value));
        
        // Dışarı tıklandığında sonuçları gizle
        document.addEventListener('click', (e) => {
            if (!input.parentElement.contains(e.target)) {
                searchResults.classList.add('d-none');
            }
        });
    });
    
    // File Upload Preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Dosya boyutu kontrolü (10MB)
            if (file.size > 10485760) {
                showNotification('Dosya boyutu çok büyük! Maksimum 10MB olmalıdır.', 'error');
                input.value = '';
                return;
            }
            
            // Resim önizleme
            if (file.type.startsWith('image/') && input.id === 'cover-image') {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('preview-container');
                    if (previewContainer) {
                        previewContainer.innerHTML = `
                            <div class="position-relative">
                                <img src="${e.target.result}" class="img-fluid rounded shadow" alt="Önizleme">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" 
                                        onclick="clearFileInput('${input.id}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Clear file input function
    window.clearFileInput = function(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            const previewContainer = document.getElementById('preview-container');
            if (previewContainer) {
                previewContainer.innerHTML = '';
            }
        }
    };
    
    // Like/Favorite Toggle
    const actionButtons = document.querySelectorAll('[data-action]');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const action = this.dataset.action;
            const contentId = this.dataset.contentId;
            
            try {
                const response = await fetch(`${siteUrl}/api/${action}.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ content_id: contentId })
                });
                
                if (!response.ok) throw new Error('İşlem başarısız');
                
                const data = await response.json();
                
                if (data.success) {
                    this.classList.toggle('active');
                    const icon = this.querySelector('i');
                    
                    if (action === 'like') {
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                        
                        // Like sayısını güncelle
                        const likeCount = this.querySelector('.like-count');
                        if (likeCount) {
                            likeCount.textContent = data.likes;
                        }
                    } else if (action === 'favorite') {
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                        icon.classList.toggle('text-danger');
                    }
                    
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message || 'Bir hata oluştu', 'error');
                }
            } catch (error) {
                console.error('İşlem hatası:', error);
                showNotification('İşlem yapılırken bir hata oluştu', 'error');
            }
        });
    });
    
    // Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
    
    // Character Counter for Textareas
    const textareas = document.querySelectorAll('textarea[maxlength]');
    
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('div');
        counter.className = 'form-text text-end';
        counter.innerHTML = `<small><span class="char-count">0</span> / ${maxLength}</small>`;
        textarea.parentElement.appendChild(counter);
        
        const updateCounter = () => {
            const currentLength = textarea.value.length;
            counter.querySelector('.char-count').textContent = currentLength;
            
            if (currentLength > maxLength * 0.8) {
                counter.classList.add('text-warning');
            } else {
                counter.classList.remove('text-warning');
            }
        };
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
    
    // Smooth Scroll for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            
            if (target) {
                const headerOffset = 100;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Image Loading Error Handler
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            this.src = 'https://via.placeholder.com/300x400?text=Resim+Yüklenemedi';
            this.classList.add('img-error');
        });
    });
    
    // Copy to Clipboard
    const copyButtons = document.querySelectorAll('[data-copy]');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const textToCopy = this.dataset.copy;
            
            try {
                await navigator.clipboard.writeText(textToCopy);
                showNotification('Kopyalandı!', 'success');
                
                // Buton metnini geçici olarak değiştir
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            } catch (error) {
                console.error('Kopyalama hatası:', error);
                showNotification('Kopyalama başarısız', 'error');
            }
        });
    });
    
    // Notification System
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        
        const icons = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        
        notification.innerHTML = `
            <i class="${icons[type] || icons['info']} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Otomatik kaldır
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        }, 5000);
    }
    
    // Export functions for global use
    window.showNotification = showNotification;
    
    // Page Load Animation
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
        
        // Remove preloader if exists
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.style.opacity = '0';
            setTimeout(() => preloader.remove(), 300);
        }
    });
    
    // Console Easter Egg
    console.log('%c🚀 MangaÇizgiRoman %c v1.0 ', 
        'background: #6366f1; color: white; padding: 5px 10px; border-radius: 3px 0 0 3px;',
        'background: #8b5cf6; color: white; padding: 5px 10px; border-radius: 0 3px 3px 0;'
    );
    console.log('%cManga ve çizgi roman tutkunları için geliştirildi 💜', 'color: #6366f1; font-style: italic;');
}); 