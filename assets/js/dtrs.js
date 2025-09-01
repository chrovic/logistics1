// Logistic1/assets/js/dtrs.js

// --- Document Details Modal Functions ---
function openDocumentDetails(docData) {
    const modal = document.getElementById('documentDetailsModal');
    const content = document.getElementById('documentDetailsContent');
    const downloadBtn = document.getElementById('downloadDocumentBtn');
    
    if (!modal || !content) return;
    
    // Format expiry date
    const expiryDate = docData.expiry_date ? 
        new Date(docData.expiry_date).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        }) : 'N/A';
    
    // Format upload date
    const uploadDate = new Date(docData.upload_date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Get file extension for display
    const fileExtension = docData.file_name.split('.').pop().toUpperCase();
    
    // Determine file type icon and color (more visible in light mode)
    let bgColor = 'bg-gray-600 dark:bg-gray-700';
    let textColor = 'text-white font-bold dark:text-gray-300';
    
    switch(fileExtension.toLowerCase()) {
        case 'pdf':
            bgColor = 'bg-red-600 dark:bg-red-900/30';
            textColor = 'text-white font-bold dark:text-red-400';
            break;
        case 'doc':
        case 'docx':
            bgColor = 'bg-blue-600 dark:bg-blue-900/30';
            textColor = 'text-white font-bold dark:text-blue-400';
            break;
        case 'xls':
        case 'xlsx':
            bgColor = 'bg-green-600 dark:bg-green-900/30';
            textColor = 'text-white font-bold dark:text-green-400';
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
            bgColor = 'bg-purple-600 dark:bg-purple-900/30';
            textColor = 'text-white font-bold dark:text-purple-400';
            break;
    }
    
    // Populate the modal content
    content.innerHTML = `
        <div class="flex items-start space-x-6">
            <!-- File Icon -->
            <div class="flex-shrink-0">
                <div class="w-16 h-16 rounded-xl ${bgColor} flex items-center justify-center">
                    <i data-lucide="file-text" class="w-8 h-8 ${textColor}"></i>
                </div>
            </div>
            
            <!-- Document Information -->
            <div class="flex-1 min-w-0">
                <h3 class="text-xl font-semibold text-[var(--text-color)] mb-4">${docData.document_type}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-semibold text-[var(--text-color)] mb-1">File Name</label>
                            <p class="text-[var(--description-color)] break-all">${docData.file_name}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-[var(--text-color)] mb-1">Document Type</label>
                            <p class="text-[var(--description-color)]">${docData.document_type}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-[var(--text-color)] mb-1">File Type</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${bgColor} ${textColor}">
                                ${fileExtension}
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-semibold text-[var(--text-color)] mb-1">Reference Number</label>
                            <p class="text-[var(--description-color)]">${docData.reference_number || 'N/A'}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-[var(--text-color)] mb-1">Upload Date</label>
                            <p class="text-[var(--description-color)]">${uploadDate}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-[var(--text-color)] mb-1">Expiry Date</label>
                            <p class="text-[var(--description-color)]">${expiryDate}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Set up download button
    if (downloadBtn) {
        downloadBtn.onclick = function() {
            window.open('../' + docData.file_path, '_blank');
        };
    }
    
    // Reinitialize Lucide icons for the new content
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Open the modal
    if (window.openModal) {
        window.openModal(modal);
    }
}

// --- File Upload Drag & Drop Functions ---
function validateDocumentFile(file, sizeLimit = 50 * 1024 * 1024) {
    const sizeLimitText = sizeLimit === 5 * 1024 * 1024 ? '5MB' : '50MB';
    
    // Validate file size
    if (file.size > sizeLimit) {
        if (window.showCustomAlert) {
            showCustomAlert(`File size must be less than ${sizeLimitText}`, 'error', 4000, 'File Too Large');
        }
        return false;
    }
    
    // Validate file type
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'text/plain'];
    const allowedExtensions = ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png', '.txt'];
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
        if (window.showCustomAlert) {
            showCustomAlert('Please select a valid file (PDF, DOC, DOCX, JPG, PNG, TXT)', 'error', 4000, 'Invalid File Type');
        }
        return false;
    }
    
    return true;
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        if (validateDocumentFile(file)) {
            displaySelectedFile(file);
        } else {
            input.value = '';
        }
    }
}

function handleFileDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const dropZone = document.getElementById('dropZone');
    const dragOverlay = document.getElementById('documentDragOverlay');
    
    // Remove drag highlighting
    if (dropZone) {
        dropZone.classList.remove('border-blue-400', 'bg-blue-50/30', 'dark:bg-blue-500/10');
    }
    if (dragOverlay) {
        dragOverlay.classList.add('hidden');
    }
    
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const file = files[0];
        
        // Validate the file
        if (!validateDocumentFile(file)) {
            return;
        }
        
        // Update the file input with the dropped file
        const input = document.getElementById('documentFile');
        if (input) {
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            } catch (err) {
                // Fallback
                Object.defineProperty(input, 'files', {
                    value: files,
                    configurable: true
                });
            }
        }
        
        displaySelectedFile(file);
    }
}

function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
}

function handleDragEnter(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const dropZone = document.getElementById('dropZone');
    const dragOverlay = document.getElementById('documentDragOverlay');
    
    if (dropZone) {
        dropZone.classList.add('border-blue-400', 'bg-blue-50/30', 'dark:bg-blue-500/10');
    }
    if (dragOverlay) {
        dragOverlay.classList.remove('hidden');
    }
}

function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Only remove highlight if we're leaving the drop zone entirely
    if (!event.currentTarget.contains(event.relatedTarget)) {
        const dropZone = document.getElementById('dropZone');
        const dragOverlay = document.getElementById('documentDragOverlay');
        
        if (dropZone) {
            dropZone.classList.remove('border-blue-400', 'bg-blue-50/30', 'dark:bg-blue-500/10');
        }
        if (dragOverlay) {
            dragOverlay.classList.add('hidden');
        }
    }
}

function displaySelectedFile(file) {
    const previewContainer = document.getElementById('documentPreviewContainer');
    const fileName = document.getElementById('selectedFileName');
    const fileSize = document.getElementById('selectedFileSize');
    const uploadPrompt = document.getElementById('documentUploadPrompt');
    
    if (previewContainer && fileName) {
        fileName.textContent = file.name;
        if (fileSize) {
            fileSize.textContent = formatFileSize(file.size);
        }
        
        // Hide upload prompt and show preview
        if (uploadPrompt) {
            uploadPrompt.classList.add('hidden');
        }
        previewContainer.classList.remove('hidden');
        
        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function clearFileSelection() {
    const input = document.getElementById('documentFile');
    const previewContainer = document.getElementById('documentPreviewContainer');
    const uploadPrompt = document.getElementById('documentUploadPrompt');
    
    // Clear the file input
    if (input) {
        input.value = '';
    }
    
    // Hide preview and show upload prompt
    if (previewContainer) {
        previewContainer.classList.add('hidden');
    }
    if (uploadPrompt) {
        uploadPrompt.classList.remove('hidden');
    }
    
    // Reinitialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// --- Mobile Upload Modal Functions ---
function openMobileUploadModal() {
    const modal = document.getElementById('mobileUploadModal');
    const form = document.getElementById('mobileUploadForm');
    
    if (form) {
        form.reset();
        clearMobileFileSelection();
    }
    
    if (modal && window.openModal) {
        window.openModal(modal);
        
        // Reinitialize Lucide icons after modal opens
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function closeMobileUploadModal() {
    const modal = document.getElementById('mobileUploadModal');
    if (modal && window.closeModal) {
        window.closeModal(modal);
    }
}

function handleMobileFileSelect(input) {
    const file = input.files[0];
    if (file) {
        if (validateDocumentFile(file, 5 * 1024 * 1024)) { // 5MB limit for mobile
            displayMobileSelectedFile(file);
        } else {
            input.value = '';
        }
    }
}

function displayMobileSelectedFile(file) {
    const previewContainer = document.getElementById('mobileDocumentPreviewContainer');
    const fileName = document.getElementById('mobileSelectedFileName');
    const fileSize = document.getElementById('mobileSelectedFileSize');
    const uploadPrompt = document.getElementById('mobileDocumentUploadPrompt');
    
    if (previewContainer && fileName) {
        fileName.textContent = file.name;
        if (fileSize) {
            fileSize.textContent = formatFileSize(file.size);
        }
        
        // Hide upload prompt and show preview
        if (uploadPrompt) {
            uploadPrompt.classList.add('hidden');
        }
        previewContainer.classList.remove('hidden');
        
        // Reinitialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function clearMobileFileSelection() {
    const input = document.getElementById('mobileDocumentFile');
    const previewContainer = document.getElementById('mobileDocumentPreviewContainer');
    const uploadPrompt = document.getElementById('mobileDocumentUploadPrompt');
    
    // Clear the file input
    if (input) {
        input.value = '';
    }
    
    // Hide preview and show upload prompt
    if (previewContainer) {
        previewContainer.classList.add('hidden');
    }
    if (uploadPrompt) {
        uploadPrompt.classList.remove('hidden');
    }
    
    // Reinitialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Initialize DTRS page functionality
 */
function initDTRS() {
    // Initialize drag and drop functionality for desktop
    const fileInput = document.getElementById('documentFile');
    const dropZone = document.getElementById('dropZone');
    
    if (fileInput && dropZone) {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        
        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        // Handle dropped files
        dropZone.addEventListener('drop', handleFileDrop, false);
        
        // Add change event listener to file input to handle manual file selection
        fileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files.length > 0) {
                displaySelectedFile(e.target.files[0]);
            }
        });
    }
    
    // Initialize mobile upload button
    const mobileUploadBtn = document.getElementById('mobileUploadBtn');
    if (mobileUploadBtn) {
        mobileUploadBtn.addEventListener('click', openMobileUploadModal);
    }
    
    // Set up clear button event listeners
    document.addEventListener('click', function(e) {
        const clearButton = e.target.closest('button[data-action="clear-file"]');
        if (clearButton) {
            clearFileSelection();
        }
        
        const mobileClearButton = e.target.closest('button[data-action="clear-mobile-file"]');
        if (mobileClearButton) {
            clearMobileFileSelection();
        }
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight(e) {
        dropZone.classList.remove('border-[var(--card-border)]', 'bg-[var(--input-bg)]');
        dropZone.classList.add('!border-blue-400', '!bg-blue-50', 'dark:!bg-blue-900/20');
    }
    
    function unhighlight(e) {
        dropZone.classList.remove('!border-blue-400', '!bg-blue-50', 'dark:!bg-blue-900/20');
        dropZone.classList.add('border-[var(--card-border)]', 'bg-[var(--input-bg)]');
    }
}

// Make functions globally available
window.initDTRS = initDTRS;
window.openDocumentDetails = openDocumentDetails;
window.clearFileSelection = clearFileSelection;
window.openMobileUploadModal = openMobileUploadModal;
window.closeMobileUploadModal = closeMobileUploadModal;
window.handleMobileFileSelect = handleMobileFileSelect;
window.clearMobileFileSelection = clearMobileFileSelection;
window.handleFileSelect = handleFileSelect;
window.handleFileDrop = handleFileDrop;
window.handleDragOver = handleDragOver;
window.handleDragEnter = handleDragEnter;
window.handleDragLeave = handleDragLeave;

// Initialize Lucide Icons
function initLucideIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Make Lucide icon initialization globally available for this page
window.refreshLucideIcons = initLucideIcons;

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initDTRS();
    initLucideIcons();
}); 