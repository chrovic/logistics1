<?php require_once '../includes/functions/auth.php'; ?>
<div class="logo">
  <img src="../assets/images/slate1.png" alt="SLATE Logo">
</div>
<div class="system-name">LOGISTICS 1</div>

  <?php if ($_SESSION['role'] === 'supplier'): ?>
    <a href="../pages/supplier_dashboard.php" class="sidebar-sub-item" data-tooltip="My Dashboard">
      <i data-lucide="gauge" class="sidebar-icon"></i> <span>My Dashboard</span>
    </a>
    <a href="../pages/supplier_dashboard.php" class="sidebar-sub-item" data-tooltip="Bidding Portal">
      <i data-lucide="gavel" class="sidebar-icon"></i> <span>Bidding Portal</span>
    </a>
  <?php else: ?>
    <div class="sidebar-category">Core Operations</div>
    
    <a href="../pages/dashboard.php" class="sidebar-sub-item" data-tooltip="Dashboard">
      <i data-lucide="layout-dashboard" class="sidebar-icon"></i> <span>Dashboard</span>
    </a>
    
    <?php if ($_SESSION['role'] === 'smart_warehousing' || $_SESSION['role'] === 'admin'): ?>
      <a href="../pages/smart_warehousing.php" class="sidebar-sub-item" data-tooltip="Smart Warehousing System">
        <i data-lucide="warehouse" class="sidebar-icon"></i> <span>Smart Warehousing System</span>
      </a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'procurement' || $_SESSION['role'] === 'admin'): ?>
      <a href="../pages/procurement_sourcing.php" class="sidebar-sub-item" data-tooltip="Procurement & Sourcing Management">
        <i data-lucide="network" class="sidebar-icon"></i> <span>Procurement & Sourcing Management</span>
      </a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'plt' || $_SESSION['role'] === 'admin'): ?>
      <a href="../pages/project_logistics_tracker.php" class="sidebar-sub-item" data-tooltip="Project Logistics Tracker">
        <i data-lucide="route" class="sidebar-icon"></i> <span>Project Logistics Tracker</span>
      </a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'alms' || $_SESSION['role'] === 'admin'): ?>
      <a href="../pages/asset_lifecycle_maintenance.php" class="sidebar-sub-item" data-tooltip="Asset Lifecycle & Maintenance">
        <i data-lucide="truck" class="sidebar-icon"></i> <span>Asset Lifecycle & Maintenance</span>
      </a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'dtrs' || $_SESSION['role'] === 'admin'): ?>
      <a href="../pages/document_tracking_records.php" class="sidebar-sub-item" data-tooltip="Document Tracking & Logistics Records">
        <i data-lucide="archive" class="sidebar-icon"></i> <span>Document Tracking & Logistics Records</span>
      </a>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'procurement'): ?>
      <div class="sidebar-category">Network</div>
      
      <a href="../pages/admin_verification.php" class="sidebar-sub-item network-separator" data-tooltip="Supplier Verification">
        <i data-lucide="file-user" class="sidebar-icon"></i> <span>Supplier Verification</span>
      </a>
    <?php endif; ?>
  <?php endif; ?>