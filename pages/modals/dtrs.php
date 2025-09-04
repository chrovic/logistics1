<!-- Mobile Upload Modal -->
<div id="mobileUploadModal" class="modal hidden lg:hidden">
  <div class="modal-content p-6 max-w-lg mx-4">
    <div class="flex justify-between items-center mb-4">
      <h2 class="modal-title flex items-center min-w-0 flex-1">
        <i data-lucide="file-plus-2" class="w-6 h-6 mr-3 flex-shrink-0"></i>
        <span class="truncate">Upload Document</span>
      </h2>
      <button type="button" class="close-button flex-shrink-0 ml-3" onclick="closeMobileUploadModal()">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>
    
    <form action="document_tracking_records.php" method="POST" enctype="multipart/form-data" id="mobileUploadForm">
      <div class="mb-4">
        <label for="mobileDocumentFile" class="block text-sm font-semibold mb-3 text-[var(--text-color)]">Document File</label>
        
        <!-- Enhanced Mobile Document Upload Component -->
        <div class="relative w-full">
          <!-- Hidden File Input -->
          <input type="file" 
                 name="documentFile" 
                 id="mobileDocumentFile" 
                 required 
                 class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                 accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt"
                 onchange="handleMobileFileSelect(this)">
          
          <!-- Upload Area -->
          <div id="mobileDropZone" class="relative w-full min-h-[120px] border-2 border-dashed border-[var(--input-border)] rounded-lg bg-[var(--input-bg)] transition-all duration-200 ease-in-out focus-within:border-blue-500 focus-within:ring-3 focus-within:ring-blue-500/10">
            
            <!-- Default Upload State -->
            <div id="mobileDocumentUploadPrompt" class="flex flex-col items-center justify-center py-8 px-4 text-center">
              <div class="w-12 h-12 rounded-full bg-white dark:bg-[var(--card-bg)] border border-[var(--card-border)] flex items-center justify-center mb-3 transition-colors duration-200">
                <i data-lucide="cloud-upload" class="w-6 h-6 text-gray-500 dark:text-gray-400"></i>
              </div>
              <p class="text-sm font-medium text-[var(--text-color)] mb-1">Tap to select your document</p>
              <p class="text-xs text-[var(--placeholder-color)]">PDF, DOC, XLSX, XLS, JPG, PNG, TXT up to 5MB</p>
            </div>
            
            <!-- Preview State - Centered like upload prompt -->
            <div id="mobileDocumentPreviewContainer" class="hidden flex flex-col items-center justify-center py-8 px-4 text-center relative">
              <!-- Document Preview - Centered -->
              <div class="relative mb-3">
                <div class="w-24 h-24 rounded-lg border border-[var(--card-border)] shadow-sm bg-blue-50 dark:bg-blue-950/20 flex items-center justify-center">
                  <i data-lucide="file-text" class="w-8 h-8 text-blue-500"></i>
                </div>
                <button type="button" 
                        data-action="clear-mobile-file"
                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200 shadow-md z-20">
                  <i data-lucide="x" class="w-3 h-3"></i>
                </button>
              </div>
              
              <!-- Document Info - Centered -->
              <div class="text-center">
                <div class="flex items-center justify-center gap-2 mb-1">
                  <i data-lucide="check-circle" class="w-4 h-4 text-green-500 flex-shrink-0"></i>
                  <span class="text-sm font-medium text-[var(--text-color)] truncate max-w-[200px]" id="mobileSelectedFileName">Document selected</span>
                </div>
                <div class="text-xs text-[var(--placeholder-color)]" id="mobileSelectedFileSize">0 KB</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="mb-4">
        <label for="mobileDocumentType" class="block text-sm font-semibold mb-2 text-[var(--text-color)]">Document Type</label>
        <input type="text" 
               name="document_type" 
               id="mobileDocumentType" 
               placeholder="e.g., Bill of Lading, Invoice" 
               required 
               class="w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]">
      </div>
      
      <div class="mb-4">
        <label for="mobileReferenceNumber" class="block text-sm font-semibold mb-2 text-[var(--text-color)]">Reference Number</label>
        <input type="text" 
               name="reference_number" 
               id="mobileReferenceNumber" 
               placeholder="e.g., INV-12345, BOL-ABCDE" 
               class="w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]">
      </div>
      
      <div class="mb-6">
        <label for="mobileExpiryDate" class="block text-sm font-semibold mb-2 text-[var(--text-color)]">Expiry Date (Optional)</label>
        <input type="date" 
               name="expiry_date" 
               id="mobileExpiryDate" 
               class="custom-datepicker-input w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" 
               data-placeholder="Select expiry date">
      </div>
      
      <div class="flex gap-3">
        <button type="button" 
                onclick="closeMobileUploadModal()" 
                class="flex-1 px-4 py-2.5 rounded-md border border-gray-300 font-semibold transition-colors bg-gray-100 text-gray-700 hover:bg-gray-200">
          Cancel
        </button>
        <button type="submit" class="flex-1 btn-primary flex items-center justify-center">
          <i data-lucide="upload" class="w-4 h-4 mr-2"></i>
          Upload
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Document Details Modal -->
<div id="documentDetailsModal" class="modal hidden">
  <div class="modal-content p-8 max-w-2xl">
    <div class="flex justify-between items-center mb-2">
      <h2 class="modal-title flex items-center min-w-0 flex-1">
        <i data-lucide="file-text" class="w-6 h-6 mr-3 flex-shrink-0"></i>
        <span class="truncate">Document Details</span>
      </h2>
      <button type="button" class="close-button flex-shrink-0 ml-3" onclick="closeModal(document.getElementById('documentDetailsModal'))">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>
    <p class="modal-subtitle">Detailed information about the document.</p>
    <div class="border-b border-[var(--card-border)] mb-5"></div>
    
    <!-- Document Details Content -->
    <div id="documentDetailsContent" class="space-y-4">
      <!-- Content will be populated by JavaScript -->
      </div>
      
    <div class="flex justify-end gap-3 mt-6 pt-4">
      <button type="button" 
              id="downloadDocumentBtn"
              class="btn-primary flex items-center">
        <i data-lucide="download" class="w-4 h-4 mr-2"></i>
        Download File
        </button>
      <button type="button" class="px-5 py-2.5 rounded-md border border-gray-300 cursor-pointer font-semibold transition-all duration-300 bg-gray-100 text-gray-700 hover:bg-gray-200" onclick="closeModal(document.getElementById('documentDetailsModal'))">
        Close
        </button>
      </div>
  </div>
</div>
