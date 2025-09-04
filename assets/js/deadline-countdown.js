/**
 * Real-time Deadline Countdown System
 * Handles countdown timers and automatic deadline enforcement for bidding
 * Timezone: Philippines (UTC+8)
 */

// Prevent duplicate initialization
if (window.DeadlineCountdown) {
    // Already loaded, just reinitialize if needed
    if (window.initDeadlineCountdown) {
        window.initDeadlineCountdown();
    }
} else {

window.DeadlineCountdown = class DeadlineCountdown {
    constructor() {
        this.countdownElements = [];
        this.updateInterval = null;
        this.expiredPOs = new Set();
        this.lastStatusCheck = 0;
        this.mutationObserver = null;
        
        // Ensure DOM is ready before initializing
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        this.findCountdownElements();
        this.startCountdown();
        this.bindEvents();
    }

    findCountdownElements() {
        this.countdownElements = [];
        const elements = document.querySelectorAll('.countdown-display[data-target]');
        
        elements.forEach(element => {
            const target = element.getAttribute('data-target');
            const timerDisplay = element.querySelector('.countdown-timer');
            const poId = element.closest('[data-po-id]')?.getAttribute('data-po-id');
            
            if (target && timerDisplay && poId) {
                this.countdownElements.push({
                    element,
                    timerDisplay,
                    target: new Date(target + ' UTC'), // Treat DB time as UTC
                    poId: parseInt(poId),
                    isExpired: false
                });
            }
        });
    }

    startCountdown() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }

        this.updateInterval = setInterval(() => {
            this.updateCountdowns();
        }, 1000); // Update every second

        // Initial update
        this.updateCountdowns();
    }

    updateCountdowns() {
        const now = new Date();
        const philippinesTime = new Date(now.getTime() + (8 * 60 * 60 * 1000)); // UTC+8
        
        let hasExpiredPOs = false;

        this.countdownElements.forEach(item => {
            const timeLeft = item.target.getTime() - philippinesTime.getTime();
            
            if (timeLeft <= 0 && !item.isExpired) {
                // Deadline expired
                this.handleExpiredDeadline(item);
                hasExpiredPOs = true;
            } else if (timeLeft > 0) {
                // Update countdown display
                this.updateCountdownDisplay(item, timeLeft);
            }
        });

        // Check for status updates every 30 seconds if we have expired POs
        if (hasExpiredPOs && (Date.now() - this.lastStatusCheck) > 30000) {
            this.checkAndUpdatePOStatuses();
            this.lastStatusCheck = Date.now();
        }
    }

    updateCountdownDisplay(item, timeLeft) {
        const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

        let countdownText = '';
        
        if (days > 0) {
            countdownText = `${days}d ${hours}h ${minutes}m left`;
        } else if (hours > 0) {
            countdownText = `${hours}h ${minutes}m ${seconds}s left`;
        } else if (minutes > 0) {
            countdownText = `${minutes}m ${seconds}s left`;
        } else {
            countdownText = `${seconds}s left`;
        }

        // Color coding based on time left
        let colorClass = 'text-[--text-color]';
        if (timeLeft < 60000) { // Less than 1 minute
            colorClass = 'text-red-600 font-semibold animate-pulse';
        } else if (timeLeft < 300000) { // Less than 5 minutes
            colorClass = 'text-red-500 font-semibold';
        } else if (timeLeft < 3600000) { // Less than 1 hour
            colorClass = 'text-yellow-600 font-medium';
        }

        item.timerDisplay.textContent = countdownText;
        item.timerDisplay.className = `countdown-timer text-xs mt-1 ${colorClass}`;
    }

    handleExpiredDeadline(item) {
        item.isExpired = true;
        item.timerDisplay.textContent = 'EXPIRED';
        item.timerDisplay.className = 'countdown-timer text-xs mt-1 text-red-600 font-bold';
        
        // Add expired class to the row
        const row = item.element.closest('tr');
        if (row) {
            row.classList.add('deadline-expired');
        }

        // Update status immediately in UI
        this.updateStatusInUI(item.poId);

        // Disable place bid buttons if on supplier page
        const placeCodedBtn = row?.querySelector('button[onclick*="openBidModal"]');
        if (placeCodedBtn) {
            placeCodedBtn.disabled = true;
            placeCodedBtn.textContent = 'Bidding Closed';
            placeCodedBtn.className = placeCodedBtn.className.replace('btn-primary', 'btn-disabled');
        }

        // Add to expired POs set
        this.expiredPOs.add(item.poId);

        // Trigger server-side status update
        this.updatePOStatus(item.poId);
    }

    async updatePOStatus(poId) {
        try {
            const formData = new FormData();
            formData.append('action', 'close_expired_bidding');
            formData.append('po_id', poId);

            const response = await fetch('../includes/ajax/deadline_handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                // Update status in UI if we're on admin page
                this.updateStatusInUI(poId);
            }
        } catch (error) {
            // Silent error handling
        }
    }

    updateStatusInUI(poId) {
        // Find the row containing this PO ID - try multiple approaches
        let row = document.querySelector(`[data-po-id="${poId}"]`);
        if (row) {
            row = row.closest('tr');
        }
        
        if (!row) {
            // Alternative: find by checking all table rows
            const allRows = document.querySelectorAll('tbody tr');
            allRows.forEach(tr => {
                const poIdElement = tr.querySelector('[data-po-id]');
                if (poIdElement && poIdElement.getAttribute('data-po-id') == poId) {
                    row = tr;
                }
            });
        }
        
        if (row) {
            // Find the status badge within this row - look for status badges
            const statusCell = row.querySelector('.bg-blue-100, .bg-yellow-100, .bg-green-100, .bg-red-100, .bg-gray-100');
            if (statusCell) {
                statusCell.textContent = 'Bidding Closed';
                statusCell.className = 'px-2 py-1 font-semibold leading-tight text-xs rounded-full bg-red-100 text-red-700';
            }
        }
    }

    async checkAndUpdatePOStatuses() {
        if (this.expiredPOs.size === 0) return;

        try {
            const formData = new FormData();
            formData.append('action', 'check_expired_pos');
            formData.append('po_ids', Array.from(this.expiredPOs).join(','));

            const response = await fetch('../includes/ajax/deadline_handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success && result.updated_pos) {
                result.updated_pos.forEach(poId => {
                    this.updateStatusInUI(poId);
                });
            }
        } catch (error) {
            // Silent error handling
        }
    }

    bindEvents() {
        // Reinitialize on page changes (for PJAX navigation)
        document.addEventListener('pjax:complete', () => {
            this.reinitialize();
        });

        // Reinitialize when DOM content changes
        try {
            if (typeof MutationObserver !== 'undefined' && document.body) {
                const observer = new MutationObserver((mutations) => {
                    let shouldReinit = false;
                    mutations.forEach(mutation => {
                        if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach(node => {
                                if (node.nodeType === 1 && 
                                    (node.classList?.contains('countdown-display') || 
                                     node.querySelector?.('.countdown-display'))) {
                                    shouldReinit = true;
                                }
                            });
                        }
                    });
                    
                    if (shouldReinit) {
                        setTimeout(() => this.reinitialize(), 100);
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                // Store observer reference for cleanup
                this.mutationObserver = observer;
            }
        } catch (error) {
            // MutationObserver not supported or failed, skip
        }
    }

    reinitialize() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        if (this.mutationObserver) {
            this.mutationObserver.disconnect();
            this.mutationObserver = null;
        }
        this.findCountdownElements();
        this.startCountdown();
        this.bindEvents();
    }

    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        if (this.mutationObserver) {
            this.mutationObserver.disconnect();
            this.mutationObserver = null;
        }
        this.countdownElements = [];
        this.expiredPOs.clear();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (!window.deadlineCountdown) {
        window.deadlineCountdown = new window.DeadlineCountdown();
    }
});

// Initialize countdown system globally
window.initDeadlineCountdown = function() {
    if (window.deadlineCountdown) {
        window.deadlineCountdown.reinitialize();
    } else {
        window.deadlineCountdown = new window.DeadlineCountdown();
    }
};

// Add CSS for expired rows (only once)
if (!document.getElementById('deadline-countdown-styles')) {
    const style = document.createElement('style');
    style.id = 'deadline-countdown-styles';
    style.textContent = `
        .deadline-expired {
            border-left: 4px solid var(--danger-color, #e74c3c) !important;
            opacity: 0.9;
        }
        
        .deadline-expired td {
            position: relative;
        }
        
        .deadline-expired td:first-child {
            border-left: none !important;
        }
        
        .deadline-expired .countdown-timer {
            color: var(--danger-color, #e74c3c) !important;
            font-weight: 700 !important;
        }
        
        .btn-disabled {
            background-color: var(--cancel-btn-bg, #e0e0e0) !important;
            color: var(--placeholder-color, #939ca5) !important;
            cursor: not-allowed !important;
            opacity: 0.7 !important;
            border: 1px solid var(--border-color, rgba(0, 0, 0, 0.1)) !important;
        }
        
        .btn-disabled:hover {
            background-color: var(--cancel-btn-bg, #e0e0e0) !important;
            transform: none !important;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .animate-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }
    `;
    document.head.appendChild(style);
}

} // End duplicate prevention check 