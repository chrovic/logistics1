// Sidebar Tooltip System
document.addEventListener('DOMContentLoaded', function() {
    let tooltip = null;

    function createTooltip() {
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.className = 'sidebar-tooltip';
            document.body.appendChild(tooltip);
        }
        return tooltip;
    }

    function showTooltip(element, text, x, y) {
        const tooltipEl = createTooltip();
        tooltipEl.textContent = text;
        tooltipEl.style.left = x + 'px';
        tooltipEl.style.top = y + 'px';
        tooltipEl.style.transform = 'translateY(-50%)'; // Center vertically
        
        // Small delay to ensure positioning is set before showing
        requestAnimationFrame(() => {
            tooltipEl.classList.add('show');
        });
    }

    function hideTooltip() {
        if (tooltip) {
            tooltip.classList.remove('show');
        }
    }

    function initSidebarTooltips() {
        const sidebarLinks = document.querySelectorAll('.sidebar-sub-item[data-tooltip]');
        
        sidebarLinks.forEach(link => {
            link.addEventListener('mouseenter', function(e) {
                const sidebar = document.getElementById('sidebar');
                
                // Only show tooltip when sidebar is collapsed
                if (sidebar && (sidebar.classList.contains('collapsed') || sidebar.classList.contains('initial-collapsed'))) {
                    const tooltipText = this.getAttribute('data-tooltip');
                    const rect = this.getBoundingClientRect();
                    const x = rect.right + 20; // Position further to the right, outside sidebar
                    const y = rect.top + (rect.height / 2); // Center vertically
                    
                    showTooltip(this, tooltipText, x, y);
                }
            });

            link.addEventListener('mouseleave', function() {
                hideTooltip();
            });
        });
    }

    // Initialize tooltips
    initSidebarTooltips();

    // Reinitialize after PJAX navigation
    document.addEventListener('pjaxComplete', initSidebarTooltips);
    
    // Also reinitialize after a short delay to handle timing issues
    setTimeout(initSidebarTooltips, 500);
}); 