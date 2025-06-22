// Drag & Drop - Tek dosya
function setupSingleFileDragDrop(dropAreaId, inputId, previewId) {
    const dropArea = document.getElementById(dropAreaId);
    const fileInput = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!dropArea || !fileInput || !preview) return;
    
    dropArea.addEventListener('click', () => fileInput.click());
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragging'));
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragging'));
    });
    
    dropArea.addEventListener('drop', handleDrop);
    fileInput.addEventListener('change', function() {
        if (this.files[0]) handleFiles(this.files[0]);
    });
    
    function handleDrop(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFiles(files[0]);
        }
    }
    
    function handleFiles(file) {
        const maxSize = inputId === 'pdf-file' ? 52428800 : 10485760; // 50MB PDF, 10MB resim
        if (file.size > maxSize) {
            showNotification(`Dosya boyutu çok büyük! Maksimum ${maxSize/1024/1024}MB olmalıdır.`, 'error');
            fileInput.value = '';
            return;
        }
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = createFilePreview(file.name, file.size, e.target.result, inputId, previewId);
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = createFilePreview(file.name, file.size, null, inputId, previewId);
        }
    }
}

// Drag & Drop - Çoklu dosya
function setupMultiFileDragDrop(dropAreaId, inputId, previewId) {
    const dropArea = document.getElementById(dropAreaId);
    const fileInput = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!dropArea || !fileInput || !preview) return;
    
    dropArea.addEventListener('click', () => fileInput.click());
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragging'));
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragging'));
    });
    
    dropArea.addEventListener('drop', handleDrop);
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    function handleDrop(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFiles(files);
        }
    }
    
    function handleFiles(files) {
        preview.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            if (file.size > 10485760) {
                showNotification(`${file.name} dosyası çok büyük! Maksimum 10MB olmalıdır.`, 'error');
                return;
            }
            
            if (!file.type.startsWith('image/')) {
                showNotification(`${file.name} geçerli bir resim dosyası değil!`, 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewElement = createMultiFilePreview(file.name, file.size, e.target.result, index);
                preview.innerHTML += previewElement;
            };
            reader.readAsDataURL(file);
        });
        
        // PDF alanını temizle (resim seçilirse)
        if (files.length > 0) {
            const pdfInput = document.getElementById('pdf-file');
            const pdfPreview = document.getElementById('pdfPreview');
            if (pdfInput) {
                pdfInput.value = '';
                if (pdfPreview) pdfPreview.innerHTML = '';
            }
        }
    }
}

// Tek dosya önizleme HTML
function createFilePreview(fileName, fileSize, imageSrc, inputId, previewId) {
    const sizeText = (fileSize / 1024 / 1024).toFixed(2) + ' MB';
    
    if (imageSrc) {
        return `
            <div class="file-preview">
                <img src="${imageSrc}" alt="Önizleme" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                <div class="flex-grow-1 ms-3">
                    <h6 class="mb-1">${fileName}</h6>
                    <small class="text-muted">${sizeText}</small>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('${inputId}', '${previewId}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    } else {
        return `
            <div class="file-preview">
                <i class="fas fa-file-pdf fa-3x text-danger me-3"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${fileName}</h6>
                    <small class="text-muted">${sizeText}</small>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="clearFile('${inputId}', '${previewId}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }
}

// Çoklu dosya önizleme HTML
function createMultiFilePreview(fileName, fileSize, imageSrc, index) {
    const sizeText = (fileSize / 1024 / 1024).toFixed(2) + ' MB';
    
    return `
        <div class="file-preview mb-2" data-index="${index}">
            <img src="${imageSrc}" alt="Önizleme" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
            <div class="flex-grow-1 ms-3">
                <h6 class="mb-1">${fileName}</h6>
                <small class="text-muted">${sizeText}</small>
                <div class="mt-1">
                    <span class="badge bg-primary">Sayfa ${index + 1}</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(${index})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
}

// Dosya temizleme
function clearFile(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input) input.value = '';
    if (preview) preview.innerHTML = '';
    
    // Eğer PDF temizlenirse resim alanını aktif et
    if (inputId === 'pdf-file') {
        const contentImages = document.getElementById('content-images');
        if (contentImages) contentImages.required = true;
    }
    // Eğer resimler temizlenirse PDF alanını aktif et
    if (inputId === 'content-images') {
        const pdfFile = document.getElementById('pdf-file');
        if (pdfFile) pdfFile.required = false;
    }
}

// Çoklu dosyadan tek dosya kaldırma
function removeFile(index) {
    const fileInput = document.getElementById('content-images');
    if (!fileInput) return;
    
    const dt = new DataTransfer();
    
    Array.from(fileInput.files).forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    fileInput.files = dt.files;
    
    // Önizlemeyi yeniden oluştur
    if (fileInput.files.length > 0) {
        setupMultiFileDragDrop('contentDropArea', 'content-images', 'contentPreview');
        fileInput.dispatchEvent(new Event('change'));
    } else {
        const preview = document.getElementById('contentPreview');
        if (preview) preview.innerHTML = '';
    }
}

// Bildirim gösterme
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-info';
    const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas ${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
} 