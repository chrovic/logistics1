// Logistic1/assets/js/plt.js

function openCreateProjectModal() {
    document.getElementById('projectForm').reset();
    document.getElementById('projectModalIcon').setAttribute('data-lucide', 'folder-plus');
    document.getElementById('projectModalTitleText').innerText = 'Create New Project';
    document.getElementById('projectModalSubtitle').innerText = 'Create a new logistics project for tracking.';
    document.getElementById('formAction').value = 'create_project';
    // Clear supplier checkbox selections
    Array.from(document.querySelectorAll('input[name="assigned_suppliers[]"]')).forEach(checkbox => checkbox.checked = false);
    if (window.openModal) {
        window.openModal(document.getElementById('projectModal'));
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
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

// Make function globally accessible for onclick handlers
window.openCreateProjectModal = openCreateProjectModal;

function openEditProjectModal(project, allSuppliers) {
    document.getElementById('projectForm').reset();
    document.getElementById('projectModalIcon').setAttribute('data-lucide', 'square-pen');
    document.getElementById('projectModalTitleText').innerText = 'Edit Project';
    document.getElementById('projectModalSubtitle').innerText = 'Update existing project details and resource assignments.';
    document.getElementById('formAction').value = 'update_project';
    document.getElementById('projectId').value = project.id;
    document.getElementById('project_name').value = project.project_name;
    document.getElementById('description').value = project.description;
    document.getElementById('status').value = project.status;
    document.getElementById('start_date').value = project.start_date;
    document.getElementById('end_date').value = project.end_date;
    
    // Pre-check the assigned supplier checkboxes
    const assigned = project.assigned_suppliers ? project.assigned_suppliers.split(', ') : [];
    Array.from(document.querySelectorAll('input[name="assigned_suppliers[]"]')).forEach(checkbox => {
        const supplier = allSuppliers.find(s => s.id == checkbox.value);
        if (supplier && assigned.includes(supplier.supplier_name)) {
            checkbox.checked = true;
        } else {
            checkbox.checked = false;
        }
    });

    if (window.openModal) {
        window.openModal(document.getElementById('projectModal'));
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
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

async function confirmDeleteProject(projectId) {
    const confirmed = await window.confirmDelete('this project');
    
    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'project_logistics_tracker.php';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_project">
            <input type="hidden" name="project_id" value="${projectId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

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
    initLucideIcons();
});