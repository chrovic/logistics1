// Logistic1/assets/js/alms.js

// --- Schedule Maintenance Modal Functions ---
function openScheduleMaintenanceModal() {
    const modal = document.getElementById('scheduleMaintenanceModal');
    const form = document.getElementById('scheduleMaintenanceForm');
    
    if (form) {
        form.reset();
    }
    
    if (modal && window.openModal) {
        window.openModal(modal);
        
        // Initialize custom components in the modal
        requestAnimationFrame(() => {
            if (window.reinitializeCustomDropdowns) {
                window.reinitializeCustomDropdowns();
            }
            if (window.reinitializeCustomDatepickers) {
                window.reinitializeCustomDatepickers();
            }
        });
    }
}

/**
 * Toggle asset action dropdown with smart positioning
 */
function toggleAssetDropdown(assetId) {
    const dropdown = document.getElementById(`asset-dropdown-${assetId}`);
    const allDropdowns = document.querySelectorAll('.action-dropdown');
    
    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d.id !== `asset-dropdown-${assetId}`) {
            d.classList.add('hidden');
        }
    });
    
    // Toggle current dropdown
    if (dropdown) {
        if (dropdown.classList.contains('hidden')) {
            // Show dropdown with smart positioning
            positionDropdownSmart(dropdown, assetId);
            dropdown.classList.remove('hidden');
        } else {
            dropdown.classList.add('hidden');
        }
    }
}

/**
 * Position dropdown smartly based on available viewport space
 */
function positionDropdownSmart(dropdown, itemId) {
    const button = dropdown.previousElementSibling; // The ellipsis button
    const buttonRect = button.getBoundingClientRect();
    const dropdownHeight = 120; // More conservative estimate for dropdown height
    const viewportHeight = window.innerHeight;
    const buffer = 30; // Larger buffer to ensure dropdown isn't clipped
    
    // Reset classes and inline styles first
    dropdown.classList.remove('dropdown-above', 'dropdown-below');
    dropdown.style.top = '';
    dropdown.style.bottom = '';
    dropdown.style.left = '';
    dropdown.style.right = '';
    
    // Calculate position relative to viewport
    const spaceBelow = viewportHeight - buttonRect.bottom;
    const spaceAbove = buttonRect.top;
    
    // Position the dropdown with fixed positioning
    dropdown.style.right = (window.innerWidth - buttonRect.right) + 'px';
    dropdown.style.width = '128px'; // w-32 equivalent
    
    // Be very aggressive - if less than required space, position above
    if (spaceBelow < (dropdownHeight + buffer) && spaceAbove > (dropdownHeight + buffer)) {
        // Position above
        dropdown.style.bottom = (viewportHeight - buttonRect.top + 10) + 'px';
        dropdown.classList.add('dropdown-above');
    } else {
        // Position below (default)
        dropdown.style.top = (buttonRect.bottom + 10) + 'px';
        dropdown.classList.add('dropdown-below');
    }
}

/**
 * Close all dropdowns when clicking outside
 */
function closeAllAssetDropdowns() {
    const allDropdowns = document.querySelectorAll('.action-dropdown');
    allDropdowns.forEach(d => d.classList.add('hidden'));
}

/**
 * Initialize asset dropdown functionality
 */
function initAssetDropdowns() {
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.action-dropdown-btn') && !event.target.closest('.action-dropdown')) {
            closeAllAssetDropdowns();
        }
    });
    
    // Recalculate dropdown positions on window resize
    window.addEventListener('resize', function() {
        const openDropdowns = document.querySelectorAll('.action-dropdown:not(.hidden)');
        openDropdowns.forEach(dropdown => {
            const itemId = dropdown.id.replace('asset-dropdown-', '');
            positionDropdownSmart(dropdown, itemId);
        });
    });
    
    // Also recalculate on scroll
    window.addEventListener('scroll', function() {
        const openDropdowns = document.querySelectorAll('.action-dropdown:not(.hidden)');
        openDropdowns.forEach(dropdown => {
            const itemId = dropdown.id.replace('asset-dropdown-', '');
            positionDropdownSmart(dropdown, itemId);
        });
    });
}

/**
 * Initialize ALMS page functionality
 */
function initALMS() {
    const scheduleTaskBtn = document.getElementById('scheduleTaskBtn');
    
    if (scheduleTaskBtn) {
        // Remove existing listener to prevent duplicates
        scheduleTaskBtn.removeEventListener('click', openScheduleMaintenanceModal);
        scheduleTaskBtn.addEventListener('click', openScheduleMaintenanceModal);
    }
    
    // Initialize dropdown functionality
    initAssetDropdowns();
    
    // Initialize tabs functionality
    initALMSTabs();
    
    // Initialize enhanced image upload with drag and drop
    initImageUploadDragDrop();
}

// --- Tab Functionality ---
/**
 * Switch between tabs for ALMS
 */
function switchALMSTab(tabName, withAnimation = false) {
    // Remove active class from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // Remove active class from all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active', 'switching');
    });
    
    // Add active class to clicked tab button
    const activeTabButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeTabButton) {
        activeTabButton.classList.add('active');
    }
    
    // Show corresponding tab content
    const activeTabContent = document.getElementById(`${tabName}-tab`);
    if (activeTabContent) {
        activeTabContent.classList.add('active');
        
        // Only add switching animation if explicitly requested (user click)
        if (withAnimation) {
            activeTabContent.classList.add('switching');
            
            // Remove switching class after animation completes
            setTimeout(() => {
                activeTabContent.classList.remove('switching');
            }, 300);
        }
    }
    
    // Refresh Lucide icons after tab switch
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Initialize tabs functionality for ALMS
 */
function initALMSTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchALMSTab(tabName, true); // Enable animation for user clicks
        });
    });
    
    // Set default active tab on load without animation
    switchALMSTab('asset-registry', false);
}

// Make tab function globally accessible
window.switchALMSTab = switchALMSTab;

// Make the initializer globally available for PJAX
window.initALMS = initALMS;

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
    initALMS();
    initLucideIcons();
});

// --- Asset Modal Functions ---
function openCreateAssetModal() {
    document.getElementById('assetForm').reset();
    document.getElementById('assetModalIcon').setAttribute('data-lucide', 'file-box');
    document.getElementById('assetModalTitleText').innerText = 'Register New Asset';
    document.getElementById('assetModalSubtitle').innerText = 'Add a new logistics asset to the registry.';
    document.getElementById('formAction').value = 'create_asset';
    clearImagePreview();
    hideCurrentImage();
    showUploadPrompt();
    hideImageError();
    if (window.openModal) {
        window.openModal(document.getElementById('assetModal'));
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

// Make functions globally accessible for onclick handlers
window.openCreateAssetModal = openCreateAssetModal;
window.clearImagePreview = clearImagePreview;
window.previewAssetImage = previewAssetImage;

function openEditAssetModal(asset) {
    document.getElementById('assetForm').reset();
    document.getElementById('assetModalIcon').setAttribute('data-lucide', 'square-pen');
    document.getElementById('assetModalTitleText').innerText = 'Edit Asset';
    document.getElementById('assetModalSubtitle').innerText = 'Update existing asset information and details.';
    document.getElementById('formAction').value = 'update_asset';
    document.getElementById('assetId').value = asset.id;
    document.getElementById('asset_name').value = asset.asset_name;
    document.getElementById('asset_type').value = asset.asset_type;
    document.getElementById('purchase_date').value = asset.purchase_date;
    document.getElementById('status').value = asset.status;
    
    // Handle existing image
    clearImagePreview();
    hideImageError();
    if (asset.image_path && asset.image_path.trim() !== '') {
        showCurrentImage(asset.image_path);
    } else {
        hideCurrentImage();
        showUploadPrompt();
    }
    
    if (window.openModal) {
        window.openModal(document.getElementById('assetModal'));
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

async function confirmDeleteAsset(assetId) {
    const confirmed = await window.confirmDelete('this asset');
    
    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'asset_lifecycle_maintenance.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_asset">
            <input type="hidden" name="asset_id" value="${assetId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// --- Enhanced Image Handling Functions ---
function previewAssetImage(input) {
    const file = input.files[0];
    
    if (file) {
        if (validateImageFile(file)) {
            showImagePreview(file);
            hideImageError();
            hideCurrentImage();
            hideUploadPrompt();
        } else {
            clearImagePreview();
        }
    }
}

function validateImageFile(file) {
    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        showImageError('File too large', 'File size must be less than 5MB');
        if (window.showCustomAlert) {
            showCustomAlert('File size must be less than 5MB', 'error', 4000, 'File Too Large');
        }
        return false;
    }
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showImageError('Invalid file type', 'Please select a valid image file (JPG, PNG, or GIF)');
        if (window.showCustomAlert) {
            showCustomAlert('Please select a valid image file (JPG, PNG, or GIF)', 'error', 4000, 'Invalid File Type');
        }
        return false;
    }
    
    return true;
}

function showImagePreview(file) {
    const previewContainer = document.getElementById('imagePreviewContainer');
    const previewImg = document.getElementById('imagePreview');
    const fileName = document.getElementById('imageFileName');
    const fileSize = document.getElementById('imageFileSize');
    
    // Update file info
    if (fileName) fileName.textContent = file.name;
    if (fileSize) fileSize.textContent = formatFileSize(file.size);
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        previewImg.src = e.target.result;
        previewContainer.classList.remove('hidden');
        
        // Hide upload prompt when showing preview
        hideUploadPrompt();
        
        // Reinitialize Lucide icons for the new elements
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };
    reader.readAsDataURL(file);
}

function clearImagePreview(event) {
    // Prevent event bubbling to avoid triggering file input
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    const input = document.getElementById('asset_image');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const previewImg = document.getElementById('imagePreview');
    
    if (input) input.value = '';
    if (previewImg) previewImg.src = '';
    if (previewContainer) previewContainer.classList.add('hidden');
    showUploadPrompt();
    hideImageError();
    
    return false;
}

function showCurrentImage(imagePath) {
    const currentImageContainer = document.getElementById('currentImageContainer');
    const currentImg = document.getElementById('currentImage');
    
    if (imagePath && imagePath.trim() !== '') {
        currentImg.src = '../' + imagePath;
        currentImageContainer.classList.remove('hidden');
        // Hide upload prompt when showing current image
        hideUploadPrompt();
    } else {
        hideCurrentImage();
        showUploadPrompt();
    }
}

function hideCurrentImage() {
    const currentImageContainer = document.getElementById('currentImageContainer');
    if (currentImageContainer) {
        currentImageContainer.classList.add('hidden');
    }
}

function showUploadPrompt() {
    const uploadPrompt = document.getElementById('uploadPrompt');
    if (uploadPrompt) {
        uploadPrompt.classList.remove('hidden');
    }
}

function hideUploadPrompt() {
    const uploadPrompt = document.getElementById('uploadPrompt');
    if (uploadPrompt) {
        uploadPrompt.classList.add('hidden');
    }
}

function showImageError(title, message) {
    const errorContainer = document.getElementById('imageErrorContainer');
    const errorTitle = document.getElementById('imageErrorTitle');
    const errorMessage = document.getElementById('imageErrorMessage');
    
    if (errorContainer && errorTitle && errorMessage) {
        errorTitle.textContent = title;
        errorMessage.textContent = message;
        errorContainer.classList.remove('hidden');
    }
}

function hideImageError() {
    const errorContainer = document.getElementById('imageErrorContainer');
    if (errorContainer) {
        errorContainer.classList.add('hidden');
    }
}

// --- Global drag and drop handlers for ALMS ---
// Use window property to avoid redeclaration errors during PJAX
if (typeof window.dragDropInitialized === 'undefined') {
    window.dragDropInitialized = false;
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlightUploadArea() {
    const area = document.getElementById('imageUploadArea');
    if (area) {
        area.classList.remove('border-[var(--card-border)]', 'bg-[var(--input-bg)]');
        area.classList.add('!border-blue-400', '!bg-blue-50/30', 'dark:!bg-blue-500/10');
    }
    
    const dragOverlay = document.getElementById('dragOverlay');
    if (dragOverlay) {
        dragOverlay.classList.remove('hidden');
    }
}

function unhighlightUploadArea() {
    const area = document.getElementById('imageUploadArea');
    if (area) {
        area.classList.remove('!border-blue-400', '!bg-blue-50/30', 'dark:!bg-blue-500/10');
        area.classList.add('border-[var(--card-border)]', 'bg-[var(--input-bg)]');
    }
    
    const dragOverlay = document.getElementById('dragOverlay');
    if (dragOverlay) {
        dragOverlay.classList.add('hidden');
    }
}

function handleDragOver(e) {
    preventDefaults(e);
    highlightUploadArea();
}

function handleDragEnter(e) {
    preventDefaults(e);
    highlightUploadArea();
}

function handleDragLeave(e) {
    preventDefaults(e);
    
    const area = document.getElementById('imageUploadArea');
    // Only remove highlight if we're leaving the upload container entirely
    if (area && !area.contains(e.relatedTarget)) {
        unhighlightUploadArea();
    }
}

function handleDrop(e) {
    preventDefaults(e);
    
    // Remove highlight
    unhighlightUploadArea();
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const file = files[0];
        
        // Update the file input with the dropped file
        const fileInput = document.getElementById('asset_image');
        if (fileInput) {
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
                
                // Manually trigger change event to ensure validation happens
                const changeEvent = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(changeEvent);
            } catch (err) {
                // Fallback: process the file directly if DataTransfer fails
                if (validateImageFile(file)) {
                    showImagePreview(file);
                    hideImageError();
                    hideCurrentImage();
                    hideUploadPrompt();
                } else {
                    clearImagePreview();
                }
            }
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

// Initialize drag and drop functionality
function initImageUploadDragDrop() {
    // Prevent multiple initializations
    if (window.dragDropInitialized) return;
    
    const uploadArea = document.getElementById('imageUploadArea');
    const fileInput = document.getElementById('asset_image');
    
    if (!uploadArea || !fileInput) return;
    
    // Remove any existing listeners to prevent duplicates
    uploadArea.removeEventListener('dragover', handleDragOver);
    uploadArea.removeEventListener('dragenter', handleDragEnter);
    uploadArea.removeEventListener('dragleave', handleDragLeave);
    uploadArea.removeEventListener('drop', handleDrop);
    
    fileInput.removeEventListener('dragover', handleDragOver);
    fileInput.removeEventListener('dragenter', handleDragEnter);
    fileInput.removeEventListener('dragleave', handleDragLeave);
    fileInput.removeEventListener('drop', handleDrop);
    
    // Prevent default drag behaviors on document and upload area
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Add visual feedback for drag events
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlightUploadArea, false);
    });
    
    // Handle drag leave separately
    uploadArea.addEventListener('dragleave', handleDragLeave, false);
    
    // Handle dropped files and remove highlight
    uploadArea.addEventListener('drop', handleDrop, false);
    
    // Also handle events on the file input for better coverage
    fileInput.addEventListener('dragover', handleDragOver);
    fileInput.addEventListener('dragenter', handleDragEnter);
    fileInput.addEventListener('dragleave', handleDragLeave);
    fileInput.addEventListener('drop', handleDrop);
    
    window.dragDropInitialized = true;
}

// Reset drag drop initialization flag when navigating away
function resetDragDropInitialization() {
    window.dragDropInitialized = false;
}

// Expose reset function globally
window.resetDragDropInitialization = resetDragDropInitialization;

// --- Image Modal Functions ---
function showImageModal(imagePath, assetName) {
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const imageModalTitle = document.getElementById('imageModalTitle');
    
    modalImage.src = imagePath;
    modalImage.alt = assetName;
    imageModalTitle.textContent = assetName + ' - Image';
    
    if (window.openModal) {
        window.openModal(imageModal);
    }
}

// Make image functions globally accessible
window.previewAssetImage = previewAssetImage;
window.clearImagePreview = clearImagePreview;
window.showCurrentImage = showCurrentImage;
window.hideCurrentImage = hideCurrentImage;
window.showImageModal = showImageModal;