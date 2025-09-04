<?php
require_once '../includes/functions/auth.php';
require_once '../includes/functions/document.php';
require_once '../includes/functions/notifications.php'; // For admin notifications
requireLogin();

// Handle AJAX request to mark admin notifications as read
if (isset($_GET['mark_admin_notifications_as_read']) && $_GET['mark_admin_notifications_as_read'] === 'true') {
    header('Content-Type: application/json');
    $user_id = getUserIdByUsername($_SESSION['username']);
    if ($user_id && markAllAdminNotificationsAsRead($user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Handle AJAX request to clear admin notifications
if (isset($_GET['clear_admin_notifications']) && $_GET['clear_admin_notifications'] === 'true') {
    header('Content-Type: application/json');
    $user_id = getUserIdByUsername($_SESSION['username']);
    if ($user_id && canReceiveAdminNotifications($_SESSION['role'] ?? '') && clearAllAdminNotifications($user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Role check
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'dtrs') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$message_type = '';

// Handle file upload submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documentFile'])) {
    $metadata = [
        'document_type'    => $_POST['document_type'] ?? '',
        'reference_number' => $_POST['reference_number'] ?? '',
        'expiry_date'      => $_POST['expiry_date'] ?? ''
    ];

    $result = uploadDocument($_FILES['documentFile'], $metadata);

    if (strpos($result, 'Success') === 0) {
        $message_type = 'success';
    } else {
        $message_type = 'error';
    }
    $message = $result;
}

$documents = getAllDocuments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <script>document.documentElement.classList.add('preload', 'loading');</script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logistics 1 - DTRS</title>
  <link rel="icon" href="../assets/images/slate2.png" type="image/png">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha384-nRgPTkuX86pH8yjPJUAFuASXQSSl2/bBUiNV47vSYpKFxHJhbcrGnmlYpYJMeD7a" crossorigin="anonymous">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="sidebar-active">
  <div class="sidebar" id="sidebar"> <?php include '../partials/sidebar.php'; ?> </div>
  <div class="main-content-wrapper" id="mainContentWrapper">
    <div class="content" id="mainContent">
      <script>
        // Apply persisted sidebar state immediately after elements exist
        (function() {
          // Skip if this is PJAX navigation - sidebar state is already preserved
          if (window.__sidebarSessionCleared) {
            return;
          }
          
          // Use centralized function if available, otherwise fallback to inline logic
          if (window.applySidebarState) {
            window.applySidebarState();
          } else {
                          // Fallback for when main sidebar.js hasn't loaded yet
              try {
                // Clear any existing session state - always start maximized on page load
                sessionStorage.removeItem('sidebarUserToggled');
                sessionStorage.removeItem('sidebarCollapsed');
                const shouldCollapse = false; // Always start maximized
              
              var sidebar = document.getElementById('sidebar');
              var wrapper = document.getElementById('mainContentWrapper');
              
              if (sidebar && wrapper) {
                sidebar.classList.remove('collapsed', 'initial-collapsed');
                wrapper.classList.remove('expanded', 'initial-expanded');
                
                if (shouldCollapse) {
                  sidebar.classList.add('initial-collapsed');
                  wrapper.classList.add('initial-expanded');
                  document.body.classList.remove('sidebar-active');
                } else {
                  document.body.classList.add('sidebar-active');
                }
              }
            } catch (e) {}
          }
        })();
        
        <?php if ($message && !empty(trim($message))): ?>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.showCustomAlert) {
                showCustomAlert(<?php echo json_encode($message); ?>, <?php echo json_encode($message_type); ?>);
            } else {
                // Fallback - strip HTML for plain alert
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = <?php echo json_encode($message); ?>;
                alert(tempDiv.textContent || tempDiv.innerText || '');
            }
        });
        <?php endif; ?>
      </script>
      <?php include '../partials/header.php'; ?>
      <h1 class="font-semibold page-title">Document Tracking & Records</h1>
      
      <!-- Two Column Layout (Mobile: Single Column, Desktop: Two Columns) -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
                <!-- Left Column - Document Records -->
        <div class="col-span-1 lg:col-span-2">
          <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-semibold text-[var(--text-color)]">Document Records</h2>
              <!-- Mobile Upload Button -->
              <button type="button" 
                      id="mobileUploadBtn"
                      class="lg:hidden btn-primary flex items-center"
                      title="Upload Document">
                <i data-lucide="cloud-upload" class="w-5 h-5"></i>
              </button>
            </div>
            
            <!-- Document Cards -->
            <div class="space-y-3">
              <?php if (empty($documents)): ?>
                <div class="text-center py-8 text-[var(--placeholder-color)]">
                  <i data-lucide="file-x" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                  <p>No documents uploaded yet</p>
                </div>
              <?php else: ?>
                <?php foreach($documents as $doc): ?>
                  <?php 
                    $fileExtension = pathinfo($doc['file_name'], PATHINFO_EXTENSION);
                    $fileTypeDisplay = strtoupper($fileExtension);
                    
                    // Determine which SVG icon to use based on file extension
                    $svgIcon = '';
                    switch(strtolower($fileExtension)) {
                      case 'pdf':
                        $svgIcon = '../assets/icons/pdf.svg';
                        break;
                      case 'html':
                      case 'htm':
                        // HTML files (Terms Agreements) should be treated as PDF
                        $svgIcon = '../assets/icons/pdf.svg';
                        $fileTypeDisplay = 'PDF'; // Display as PDF instead of HTML
                        break;
                      case 'doc':
                      case 'docx':
                        $svgIcon = '../assets/icons/doc.svg';
                        break;
                      case 'xls':
                      case 'xlsx':
                        $svgIcon = '../assets/icons/excel.svg';
                        break;
                      case 'jpg':
                      case 'jpeg':
                      case 'png':
                      case 'gif':
                      case 'bmp':
                      case 'webp':
                        $svgIcon = '../assets/icons/img.svg';
                        break;
                      case 'txt':
                        $svgIcon = '../assets/icons/txt.svg';
                        break;
                      default:
                        // Fallback for other file types - use a generic document icon
                        $svgIcon = '../assets/icons/doc.svg';
                        break;
                    }
                  ?>
                  <div class="flex items-center p-4 bg-[var(--card-bg)] border border-[var(--card-border)] rounded-lg hover:shadow-md transition-shadow group">
                    <!-- File Type Icon -->
                    <div class="flex-shrink-0 mr-4">
                      <img src="<?php echo htmlspecialchars($svgIcon); ?>" 
                           alt="<?php echo htmlspecialchars($fileTypeDisplay); ?> file" 
                           class="w-12 h-12 object-contain">
                    </div>
                    
                    <!-- Document Info -->
                    <div class="flex-1 min-w-0">
                      <h3 class="text-lg font-semibold text-[var(--text-color)] truncate">
                        <?php echo htmlspecialchars($doc['document_type']); ?>
                      </h3>
                      <p class="text-sm text-[var(--placeholder-color)] mt-1">
                        <?php echo $fileTypeDisplay; ?> • <?php echo date('M d, Y', strtotime($doc['upload_date'])); ?>
                      </p>
                      <p class="text-xs text-[var(--placeholder-color)] mt-1 truncate">
                        <?php echo htmlspecialchars($doc['file_name']); ?>
                      </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex-shrink-0 ml-4 flex items-center space-x-2">
                      <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" 
                         class="p-3 text-[var(--placeholder-color)] hover:text-[var(--text-color)] hover:bg-[var(--input-bg)] rounded-lg transition-colors"
                         title="Download file">
                        <i data-lucide="download" class="w-5 h-5"></i>
                      </a>
                      <button type="button" 
                              onclick="openDocumentDetails(<?php echo htmlspecialchars(json_encode($doc)); ?>)"
                              class="p-3 text-[var(--placeholder-color)] hover:text-[var(--text-color)] hover:bg-[var(--input-bg)] rounded-lg transition-colors"
                              title="View details">
                        <i data-lucide="eye" class="w-5 h-5"></i>
          </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Right Column - Upload Document Form (Hidden on Mobile) -->
        <div class="hidden lg:block lg:col-span-1">
          <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-xl p-6 shadow-sm sticky top-6">
            <h2 class="text-xl font-semibold text-[var(--text-color)] mb-6 flex items-center">
              Upload Document
            </h2>
            
            <form action="document_tracking_records.php" method="POST" enctype="multipart/form-data" id="uploadDocumentForm">
              <div class="mb-5">
                <label for="documentFile" class="block text-sm font-semibold mb-3 text-[var(--text-color)]">Document File</label>
                
                <!-- Enhanced Document Upload Component -->
                <div class="relative w-full">
                  <!-- Hidden File Input -->
                  <input type="file" 
                         name="documentFile" 
                         id="documentFile" 
                         required 
                         class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                         accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt"
                         onchange="handleFileSelect(this)"
                         ondrop="handleFileDrop(event)"
                         ondragover="handleDragOver(event)"
                         ondragenter="handleDragEnter(event)"
                         ondragleave="handleDragLeave(event)">
                  
                  <!-- Upload Area -->
                  <div id="dropZone" class="relative w-full min-h-[120px] border-2 border-dashed border-[var(--input-border)] rounded-lg bg-[var(--input-bg)] transition-all duration-200 ease-in-out focus-within:border-blue-500 focus-within:ring-3 focus-within:ring-blue-500/10">
                    
                    <!-- Default Upload State -->
                    <div id="documentUploadPrompt" class="flex flex-col items-center justify-center py-8 px-4 text-center">
                      <div class="w-12 h-12 rounded-full bg-white dark:bg-[var(--card-bg)] border border-[var(--card-border)] flex items-center justify-center mb-3 transition-colors duration-200">
                        <i data-lucide="cloud-upload" class="w-6 h-6 text-gray-500 dark:text-gray-400"></i>
                      </div>
                      <p class="text-sm font-medium text-[var(--text-color)] mb-1">Drop your document here or click to browse</p>
                      <p class="text-xs text-[var(--placeholder-color)]">PDF, DOC, XLSX, XLS, JPG, PNG, TXT up to 50MB</p>
                    </div>
                    
                    <!-- Preview State - Centered like upload prompt -->
                    <div id="documentPreviewContainer" class="hidden flex flex-col items-center justify-center py-8 px-4 text-center relative">
                      <!-- Document Preview - Centered -->
                      <div class="relative mb-3">
                        <div class="w-24 h-24 rounded-lg border border-[var(--card-border)] shadow-sm bg-blue-50 dark:bg-blue-950/20 flex items-center justify-center">
                          <i data-lucide="file-text" class="w-8 h-8 text-blue-500"></i>
                        </div>
                        <button type="button" 
                                data-action="clear-file"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200 shadow-md z-20">
                          <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                      </div>
                      
                      <!-- Document Info - Centered -->
                      <div class="text-center">
                        <div class="flex items-center justify-center gap-2 mb-1">
                          <i data-lucide="check-circle" class="w-4 h-4 text-green-500 flex-shrink-0"></i>
                          <span class="text-sm font-medium text-[var(--text-color)] truncate max-w-[200px]" id="selectedFileName">Document uploaded</span>
                        </div>
                        <div class="text-xs text-[var(--placeholder-color)]" id="selectedFileSize">0 KB</div>
                      </div>
                    </div>
                    
                    <!-- Drag Overlay -->
                    <div id="documentDragOverlay" class="hidden absolute inset-0 bg-blue-500/10 rounded-lg flex items-center justify-center backdrop-blur-sm">
                      <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-blue-500/20 flex items-center justify-center mb-3 mx-auto">
                          <i data-lucide="upload" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <p class="text-sm font-semibold text-blue-600">Drop document to upload</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="mb-5">
                <label for="document_type" class="block text-sm font-semibold mb-2 text-[var(--text-color)]">Document Type</label>
                <input type="text" 
                       name="document_type" 
                       id="document_type" 
                       placeholder="e.g., Bill of Lading, Invoice" 
                       required 
                       class="w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]">
              </div>
              
              <div class="mb-5">
                <label for="reference_number" class="block text-sm font-semibold mb-2 text-[var(--text-color)]">Reference Number</label>
                <input type="text" 
                       name="reference_number" 
                       id="reference_number" 
                       placeholder="e.g., INV-12345, BOL-ABCDE" 
                       class="w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]">
              </div>
              
              <div class="mb-6">
                <label for="expiry_date" class="block text-sm font-semibold mb-2 text-[var(--text-color)]">Expiry Date (Optional)</label>
                <input type="date" 
                       name="expiry_date" 
                       id="expiry_date" 
                       class="custom-datepicker-input w-full p-2.5 border border-[var(--input-border)] rounded-md bg-[var(--input-bg)] text-[var(--input-text)]" 
                       data-placeholder="Select expiry date">
              </div>
              
              <button type="submit" class="w-full btn-primary flex items-center justify-center">
                <i data-lucide="upload" class="w-5 h-5 mr-2"></i>
                Upload Document
              </button>
            </form>
          </div>
        </div>
        
      </div>
    </div>
  </div>

  <?php include 'modals/dtrs.php'; ?>

  <script src="../assets/js/sidebar.js"></script>
  <script src="../assets/js/sidebar-tooltip.js"></script>
  <script src="../assets/js/script.js"></script>
  <script src="../assets/js/custom-datepicker.js"></script>
  <script src="../assets/js/dtrs.js"></script>
  <script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  </script>
</body>
</html>