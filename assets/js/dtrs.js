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
    
    // Determine which SVG icon to use and badge styling
    let svgIcon = '';
    let badgeClass = '';
    let displayType = fileExtension;
    
    switch(fileExtension.toLowerCase()) {
        case 'pdf':
            svgIcon = '../assets/icons/pdf.svg';
            badgeClass = 'bg-red-100 text-red-700';
            break;
        case 'html':
        case 'htm':
            svgIcon = '../assets/icons/pdf.svg';
            badgeClass = 'bg-red-100 text-red-700';
            displayType = 'PDF'; // Display as PDF for Terms Agreements
            break;
        case 'doc':
        case 'docx':
            svgIcon = '../assets/icons/doc.svg';
            badgeClass = 'bg-blue-100 text-blue-700';
            break;
        case 'xls':
        case 'xlsx':
            svgIcon = '../assets/icons/excel.svg';
            badgeClass = 'bg-green-100 text-green-700';
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
        case 'webp':
            svgIcon = '../assets/icons/img.svg';
            badgeClass = 'bg-purple-100 text-purple-700';
            break;
        case 'txt':
            svgIcon = '../assets/icons/txt.svg';
            badgeClass = 'bg-gray-100 text-gray-700';
            break;
        default:
            svgIcon = '../assets/icons/doc.svg';
            badgeClass = 'bg-gray-100 text-gray-700';
            break;
    }
    
    // Populate the modal content
    content.innerHTML = `
        <div class="flex items-start space-x-6">
            <!-- File Icon -->
            <div class="flex-shrink-0">
                <img src="${svgIcon}" alt="${displayType} file" class="w-16 h-16 object-contain">
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
                            <span class="px-2 py-1 font-semibold leading-tight text-xs rounded-full ${badgeClass}">
                                ${displayType}
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
    
    // No need to reinitialize icons since we're using SVG images
    
    // Open the modal
    openModal(modal);
}

// --- File Upload Functions ---

// Validate file type and size
function validateDocumentFile(file) {
    if (!file) return false;
    
    // Validate file size (50MB limit)
    if (file.size > 50000000) {
        if (window.showCustomAlert) {
            showCustomAlert('File size exceeds 50MB limit', 'error', 4000, 'File Too Large');
        }
        return false;
    }
    
    // Validate file type
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png', 'text/plain'];
    const allowedExtensions = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png', '.txt'];
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
        if (window.showCustomAlert) {
            showCustomAlert('Please select a valid file (PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, TXT)', 'error', 4000, 'Invalid File Type');
        }
        return false;
    }
    
    return true;
}

// Handle file selection for desktop upload
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (!validateDocumentFile(file)) {
        input.value = '';
        return;
    }
    
    updateDocumentPreview(file, false);
}

// Handle file selection for mobile upload
function handleMobileFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    if (!validateDocumentFile(file)) {
        input.value = '';
        return;
    }
    
    updateDocumentPreview(file, true);
}

// Update document preview
function updateDocumentPreview(file, isMobile = false) {
    if (isMobile) {
        const promptElement = document.getElementById('mobileDocumentUploadPrompt');
        const previewContainer = document.getElementById('mobileDocumentPreviewContainer');
        const fileNameElement = document.getElementById('mobileSelectedFileName');
        const fileSizeElement = document.getElementById('mobileSelectedFileSize');
        
        if (promptElement) promptElement.classList.add('hidden');
        if (previewContainer) previewContainer.classList.remove('hidden');
        
        if (fileNameElement) fileNameElement.textContent = file.name;
        if (fileSizeElement) fileSizeElement.textContent = formatFileSize(file.size);
    } else {
        const promptElement = document.getElementById('documentUploadPrompt');
        const previewContainer = document.getElementById('documentPreviewContainer');
        const fileNameElement = document.getElementById('selectedFileName');
        const fileSizeElement = document.getElementById('selectedFileSize');
        
        if (promptElement) promptElement.classList.add('hidden');
        if (previewContainer) previewContainer.classList.remove('hidden');
        
        if (fileNameElement) fileNameElement.textContent = file.name;
        if (fileSizeElement) fileSizeElement.textContent = formatFileSize(file.size);
    }
    
    // Add clear button functionality
    const clearButton = document.querySelector(`[data-action="${isMobile ? 'clear-mobile-file' : 'clear-file'}"]`);
    if (clearButton) {
        clearButton.onclick = function() {
            clearDocumentPreview(isMobile);
        };
    }
}

// Clear document preview
function clearDocumentPreview(isMobile = false) {
    if (isMobile) {
        const promptElement = document.getElementById('mobileDocumentUploadPrompt');
        const previewContainer = document.getElementById('mobileDocumentPreviewContainer');
        const fileInput = document.getElementById('mobileDocumentFile');
        
        if (promptElement) promptElement.classList.remove('hidden');
        if (previewContainer) previewContainer.classList.add('hidden');
        if (fileInput) fileInput.value = '';
    } else {
        const promptElement = document.getElementById('documentUploadPrompt');
        const previewContainer = document.getElementById('documentPreviewContainer');
        const fileInput = document.getElementById('documentFile');
        
        if (promptElement) promptElement.classList.remove('hidden');
        if (previewContainer) previewContainer.classList.add('hidden');
        if (fileInput) fileInput.value = '';
    }
}

// Format file size for display
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// --- Drag and Drop Functions ---

function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    showDragOverlay(false);
}

function handleDragEnter(event) {
    event.preventDefault();
    event.stopPropagation();
    showDragOverlay(false);
}

function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    // Only hide if we're leaving the drop zone completely
    if (!event.currentTarget.contains(event.relatedTarget)) {
        hideDragOverlay(false);
    }
}

function handleFileDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    hideDragOverlay(false);
    
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const fileInput = document.getElementById('documentFile');
        if (fileInput) {
            fileInput.files = files;
            handleFileSelect(fileInput);
        }
    }
}

function showDragOverlay(isMobile = false) {
    const overlayId = isMobile ? 'mobileDocumentDragOverlay' : 'documentDragOverlay';
    const overlay = document.getElementById(overlayId);
    if (overlay) overlay.classList.remove('hidden');
}

function hideDragOverlay(isMobile = false) {
    const overlayId = isMobile ? 'mobileDocumentDragOverlay' : 'documentDragOverlay';
    const overlay = document.getElementById(overlayId);
    if (overlay) overlay.classList.add('hidden');
}

// --- Mobile Modal Functions ---

function openMobileUploadModal() {
    const modal = document.getElementById('mobileUploadModal');
    if (modal) {
        openModal(modal);
    }
}

function closeMobileUploadModal() {
    const modal = document.getElementById('mobileUploadModal');
    if (modal) {
        closeModal(modal);
        clearDocumentPreview(true);
    }
}

// --- Event Listeners ---

document.addEventListener('DOMContentLoaded', function() {
    // Mobile upload button
    const mobileUploadBtn = document.getElementById('mobileUploadBtn');
    if (mobileUploadBtn) {
        mobileUploadBtn.addEventListener('click', openMobileUploadModal);
    }
    
    // Set up drag and drop for desktop upload area
    const dropZone = document.getElementById('dropZone');
    if (dropZone) {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        
        // Handle drag events
        dropZone.addEventListener('dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showDragOverlay(false);
        });
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showDragOverlay(false);
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Only hide if leaving the drop zone completely
            if (!dropZone.contains(e.relatedTarget)) {
                hideDragOverlay(false);
            }
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideDragOverlay(false);
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('documentFile');
                if (fileInput) {
                    fileInput.files = files;
                    handleFileSelect(fileInput);
                }
            }
        });
    }
    
    // Clear file buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-action="clear-file"]') || e.target.closest('[data-action="clear-file"]')) {
            clearDocumentPreview(false);
        }
        if (e.target.matches('[data-action="clear-mobile-file"]') || e.target.closest('[data-action="clear-mobile-file"]')) {
            clearDocumentPreview(true);
        }
    });
    
    // Form submissions
    const uploadForm = document.getElementById('uploadDocumentForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('documentFile');
            if (fileInput && fileInput.files.length === 0) {
                e.preventDefault();
                if (window.showCustomAlert) {
                    showCustomAlert('Please select a file to upload', 'error', 4000, 'No File Selected');
                }
                return;
            }
        });
    }
    
    const mobileUploadForm = document.getElementById('mobileUploadForm');
    if (mobileUploadForm) {
        mobileUploadForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('mobileDocumentFile');
            if (fileInput && fileInput.files.length === 0) {
                e.preventDefault();
                if (window.showCustomAlert) {
                    showCustomAlert('Please select a file to upload', 'error', 4000, 'No File Selected');
                }
                return;
            }
        });
    }
});

// --- Global Functions for Modal Integration ---

// Make functions globally available
window.openDocumentDetails = openDocumentDetails;
window.handleFileSelect = handleFileSelect;
window.handleMobileFileSelect = handleMobileFileSelect;
window.handleFileDrop = handleFileDrop;
window.handleDragOver = handleDragOver;
window.handleDragEnter = handleDragEnter;
window.handleDragLeave = handleDragLeave;
window.openMobileUploadModal = openMobileUploadModal;
window.closeMobileUploadModal = closeMobileUploadModal; 