<?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'alms'): ?>
<div id="assetModal" class="modal hidden">
    <div class="modal-content p-8 max-w-2xl">
        <div class="flex justify-between items-center mb-2">
            <h2 class="modal-title flex items-center min-w-0 flex-1" id="assetModalTitle">
                <i data-lucide="package" class="w-6 h-6 mr-3 flex-shrink-0" id="assetModalIcon"></i>
                <span id="assetModalTitleText" class="truncate">Register New Asset</span>
            </h2>
            <button type="button" class="close-button flex-shrink-0 ml-3" onclick="closeModal('assetModal')"><i data-lucide="x"></i></button>
        </div>
        <p class="modal-subtitle" id="assetModalSubtitle">Add a new logistics asset to the registry.</p>
        <div class="border-b border-[var(--card-border)] mb-5"></div>
        <form id="assetForm" method="POST" action="asset_lifecycle_maintenance.php" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="asset_id" id="assetId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="asset_name" class="block text-sm font-semibold mb-2">Asset Name</label>
                    <input type="text" name="asset_name" id="asset_name" required class="w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" placeholder="e.g., Forklift Unit 01, Delivery Truck A1, Warehouse Scanner">
                </div>
                <div>
                    <label for="asset_type" class="block text-sm font-semibold mb-2">Asset Type</label>
                    <input type="text" name="asset_type" id="asset_type" class="w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" placeholder="e.g., Vehicle, Equipment, Technology, Infrastructure">
                </div>
                <div>
                    <label for="purchase_date" class="block text-sm font-semibold mb-2">Purchase Date</label>
                    <input type="date" name="purchase_date" id="purchase_date" class="custom-datepicker-input w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" data-placeholder="Select purchase date">
                </div>
                <div>
                    <label for="status" class="block text-sm font-semibold mb-2">Status</label>
                    <select name="status" id="status" class="custom-dropdown-select w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" data-placeholder="Select Status">
                        <option>Operational</option>
                        <option>Under Maintenance</option>
                        <option>Decommissioned</option>
                    </select>
                </div>
            </div>
            <div class="mt-5">
                <label for="asset_image" class="block text-sm font-semibold mb-2">Asset Image</label>
                
                <!-- Enhanced Image Upload Component -->
                <div class="relative w-full">
                    <!-- Hidden File Input -->
                    <input type="file" name="asset_image" id="asset_image" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewAssetImage(this)">
                    
                    <!-- Upload Area -->
                    <div id="imageUploadArea" class="relative w-full min-h-[120px] border-2 border-dashed border-[var(--input-border)] rounded-lg bg-[var(--input-bg)] transition-all duration-200 ease-in-out focus-within:border-blue-500 focus-within:ring-3 focus-within:ring-blue-500/10">
                        
                        <!-- Default Upload State -->
                        <div id="uploadPrompt" class="flex flex-col items-center justify-center py-8 px-4 text-center">
                            <div class="w-12 h-12 rounded-full bg-white dark:bg-[var(--card-bg)] border border-[var(--card-border)] flex items-center justify-center mb-3 transition-colors duration-200">
                                <i data-lucide="image-plus" class="w-6 h-6 text-gray-500 dark:text-gray-400"></i>
                            </div>
                            <p class="text-sm font-medium text-[var(--text-color)] mb-1">Drop your image here or click to browse</p>
                            <p class="text-xs text-[var(--placeholder-color)]">JPG, PNG, GIF up to 5MB</p>
                        </div>
                        
                        <!-- Preview State - Centered like upload prompt -->
                        <div id="imagePreviewContainer" class="hidden flex flex-col items-center justify-center py-8 px-4 text-center relative">
                            <!-- Image Preview - Centered -->
                            <div class="relative mb-3">
                                <img id="imagePreview" class="w-24 h-24 object-cover rounded-lg border border-[var(--card-border)] shadow-sm" alt="Preview">
                                <button type="button" onclick="clearImagePreview(event)" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200 shadow-md z-20">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                            </div>
                            
                            <!-- Image Info - Centered -->
                            <div class="text-center">
                                <div class="flex items-center justify-center gap-2 mb-1">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-green-500 flex-shrink-0"></i>
                                    <span class="text-sm font-medium text-[var(--text-color)] truncate max-w-[200px]" id="imageFileName">Image uploaded</span>
                                </div>
                                <div class="text-xs text-[var(--placeholder-color)]" id="imageFileSize">0 KB</div>
                            </div>
                        </div>
                        
                        <!-- Current Image State (for edit mode) - Centered like other states -->
                        <div id="currentImageContainer" class="hidden flex flex-col items-center justify-center py-8 px-4 text-center relative">
                            <!-- Current Image Preview - Centered -->
                            <div class="relative mb-3">
                                <img id="currentImage" class="w-24 h-24 object-cover rounded-lg border border-[var(--card-border)] shadow-sm" alt="Current">
                                <div class="absolute -top-2 -right-2 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center shadow-md">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                </div>
                            </div>
                            
                            <!-- Current Image Info - Centered -->
                            <div class="text-center">
                                <div class="flex items-center justify-center gap-2 mb-1">
                                    <i data-lucide="image" class="w-4 h-4 text-blue-500 flex-shrink-0"></i>
                                    <span class="text-sm font-medium text-[var(--text-color)]">Current image</span>
                                </div>
                                <p class="text-xs text-[var(--placeholder-color)]">Upload a new image to replace</p>
                            </div>
                        </div>
                        
                        <!-- Drag Overlay -->
                        <div id="dragOverlay" class="hidden absolute inset-0 bg-blue-500/10 rounded-lg flex items-center justify-center backdrop-blur-sm">
                            <div class="text-center">
                                <div class="w-16 h-16 rounded-full bg-blue-500/20 flex items-center justify-center mb-3 mx-auto">
                                    <i data-lucide="upload" class="w-8 h-8 text-blue-600"></i>
                                </div>
                                <p class="text-sm font-semibold text-blue-600">Drop image to upload</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Error State -->
                    <div id="imageErrorContainer" class="hidden mt-2 p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-md">
                        <div class="flex items-start gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-red-800 dark:text-red-200" id="imageErrorTitle">Upload failed</p>
                                <p class="text-xs text-red-600 dark:text-red-300" id="imageErrorMessage">Please try again</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <button type="button" class="px-5 py-2.5 rounded-md border" onclick="closeModal(document.getElementById('assetModal'))">Cancel</button>
                <button type="submit" class="btn-primary">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<div id="scheduleMaintenanceModal" class="modal hidden">
    <div class="modal-content p-8 max-w-lg">
        <div class="flex justify-between items-center mb-2">
            <h2 class="modal-title flex items-center min-w-0 flex-1">
                <i data-lucide="calendar-plus" class="w-6 h-6 mr-3 flex-shrink-0"></i>
                <span class="truncate">Schedule Maintenance Task</span>
            </h2>
            <button type="button" class="close-button flex-shrink-0 ml-3" onclick="closeModal('scheduleMaintenanceModal')"><i data-lucide="x"></i></button>
        </div>
        <p class="modal-subtitle">Schedule a maintenance task for a logistics asset.</p>
        <div class="border-b border-[var(--card-border)] mb-5"></div>
        <form id="scheduleMaintenanceForm" method="POST" action="asset_lifecycle_maintenance.php">
            <input type="hidden" name="action" value="schedule_maintenance">
            <div class="mb-5">
                <label for="asset_id_maint" class="block text-sm font-semibold mb-2">Asset</label>
                <select name="asset_id_maint" id="asset_id_maint" required class="custom-dropdown-select w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" data-placeholder="-- Select Asset --">
                    <option value="">-- Select Asset --</option>
                    <?php foreach($assets as $asset): ?>
                        <option value="<?php echo $asset['id']; ?>"><?php echo htmlspecialchars($asset['asset_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-5">
                <label for="task_description" class="block text-sm font-semibold mb-2">Task Description</label>
                <textarea name="task_description" id="task_description" placeholder="e.g., Replace the battery, Check the brakes, Clean the scanner" rows="3" required class="w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]"></textarea>
            </div>
            <div class="mb-6">
                <label for="scheduled_date" class="block text-sm font-semibold mb-2">Scheduled Date</label>
                <input type="date" name="scheduled_date" id="scheduled_date" required class="custom-datepicker-input w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" data-placeholder="Select scheduled date">
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <button type="button" class="px-5 py-2.5 rounded-md border" onclick="closeModal(document.getElementById('scheduleMaintenanceModal'))">Cancel</button>
                <button type="submit" class="btn-primary">Schedule Task</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
