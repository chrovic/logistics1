<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform scale-95 transition-transform duration-200">
        <div class="px-10 py-8 text-center">
            <div class="w-56 h-56 md:w-68 md:h-68 mx-auto mb-2 flex items-center justify-center">
                <img src="../assets/images/logout.png" alt="Logout" class="w-full h-full object-contain">
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Sign Out?</h2>
            <p class="text-sm text-gray-600 leading-relaxed">Are you sure you want to sign out of your account? You&apos;ll need to log in again to access your supplier dashboard.</p>
        </div>
        <div class="px-6 pb-6 flex gap-3 justify-center mt-4">
            <button type="button" class="px-4 py-2.5 bg-gray-50 text-gray-700 border border-gray-200 rounded-md font-medium text-sm hover:bg-gray-100 hover:text-gray-900 transition-colors duration-200 flex-1 max-w-[120px]" onclick="closeLogoutModal()">
                Cancel
            </button>
            <button type="button" class="px-4 py-2.5 bg-red-600 text-white rounded-md font-medium text-sm hover:bg-red-700 transition-colors duration-200 flex-1 max-w-[120px]" onclick="confirmLogout()">
                Sign Out
            </button>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div id="resetModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform scale-95 transition-transform duration-200">
        <div class="px-6 py-6 text-center">
            <div class="w-12 h-12 mx-auto mb-6 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center">
                <i data-lucide="rotate-ccw" class="w-6 h-6"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Reset All Changes?</h2>
            <p class="text-sm text-gray-600 leading-relaxed">Are you sure you want to reset all changes? This will reload the form with the original values and any unsaved changes will be lost.</p>
        </div>
        <div class="px-6 pb-6 flex gap-3 justify-center mt-4">
            <button type="button" class="px-4 py-2.5 bg-gray-50 text-gray-700 border border-gray-200 rounded-md font-medium text-sm hover:bg-gray-100 hover:text-gray-900 transition-colors duration-200 flex-1 max-w-[120px]" onclick="closeResetModal()">
                Cancel
            </button>
            <button type="button" class="px-4 py-2.5 bg-red-600 text-white rounded-md font-medium text-sm hover:bg-red-700 transition-colors whitespace-nowrap duration-200 flex-1 max-w-[140px]" onclick="confirmReset()">
                Reset Changes
            </button>
        </div>
    </div>
</div>

<!-- Bid Modal -->
<div id="bidModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <!-- Bid Form Content -->
        <div id="bidFormContent">
            <h2 class="text-2xl font-bold mb-4 text-gray-800" id="bidModalTitle">Place Your Bid</h2>
            <form method="POST" id="bidForm">
                <input type="hidden" name="action" value="place_bid">
                <input type="hidden" name="po_id" id="po_id_input">
                
                <div class="mb-4">
                    <label for="bid_amount" class="form-label">Bid Amount (₱)</label>
                    <input type="number" name="bid_amount" id="bid_amount" step="0.01" placeholder="Enter your bid amount" class="form-input" required>
                </div>

                <div class="mb-4">
                     <label for="notes" class="form-label">Notes (Optional)</label>
                    <textarea name="notes" id="notes" placeholder="Include any notes for the procurement team..." rows="3" class="form-input"></textarea>
                </div>

                <!-- Terms & Conditions Checkbox -->
                <div class="mb-6">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" id="terms_checkbox" name="terms_agreement" required class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 flex-shrink-0">
                        <label for="terms_checkbox" class="text-sm text-gray-700 leading-relaxed">
                        By checking this box, I acknowledge that I have read, understood, and agree to be bound by all the <button type="button" id="termsLink" class="text-blue-600 underline hover:text-blue-800 font-medium">Terms & Conditions</button>.
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeBidModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Submit Bid
                    </button>
                </div>
            </form>
        </div>

        <!-- Terms & Conditions Content -->
        <div id="termsContent" class="hidden">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Terms & Conditions</h2>
                <button type="button" id="backToBid" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="max-h-[500px] overflow-y-auto text-sm text-gray-700 space-y-5">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">1. Bid Commitment & Pricing</h3>
                    <p class="leading-relaxed">By submitting this bid, the supplier agrees that: all bids are binding and cannot be withdrawn once submitted; bid pricing remains valid for 30 days; the supplier commits to honor the quoted price and delivery terms; this agreement becomes legally binding upon acceptance.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">2. Delivery & Quality Standards</h3>
                    <p class="leading-relaxed">The supplier agrees to: deliver goods/services within agreed timeframe as per purchase order specifications; maintain quality standards and technical requirements; provide proper documentation and certifications; ensure goods are delivered in proper condition to specified location; replace defective items within 5 business days at supplier's cost.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">3. Payment & Financial Terms</h3>
                    <p class="leading-relaxed">Payment conditions: payment processed within 30 days of successful delivery and acceptance; all prices inclusive of applicable taxes unless specified otherwise; payment methods include bank transfer and corporate checks; invoices must include proper documentation and reference numbers.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">4. Legal Compliance & Confidentiality</h3>
                    <p class="leading-relaxed">Both parties must: comply with all applicable local, state, and federal regulations; maintain confidentiality of all bidding information and business processes; adhere to environmental and safety standards; follow ethical business practices and anti-corruption requirements; obtain proper insurance coverage during contract period.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">5. Liability & Risk Management</h3>
                    <p class="leading-relaxed">Risk allocation: supplier assumes responsibility for damages from non-compliance with agreed terms; both parties acknowledge force majeure provisions for unforeseeable circumstances; limitation of liability as specified in purchase order terms; dispute resolution through proper legal channels.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">6. Agreement Modification & Termination</h3>
                    <p class="leading-relaxed">Agreement terms: either party may terminate with 30 days written notice; early termination penalties as specified in purchase order; all outstanding obligations must be fulfilled upon termination; agreement amendments require written consent from both parties; contract governed by applicable business law.</p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                    <p class="text-center font-medium text-blue-900">By submitting this bid, both parties acknowledge they have read, understood, and agree to be bound by all terms and conditions stated above.</p>
                </div>
            </div>
            <div class="mt-6 flex justify-center">
                <button type="button" id="acceptTerms" class="btn-primary">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    Accept & Continue
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global modal functions for supplier portal
window.showLogoutModal = function() {
    const modal = document.getElementById('logoutModal');
    const content = modal.querySelector('.transform');
    
    modal.classList.remove('opacity-0', 'invisible');
    modal.classList.add('opacity-100', 'visible');
    
    setTimeout(() => {
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);
    
    // Close any open dropdown menus
    const dropdownMenu = document.getElementById('supplierDropdownMenu');
    if (dropdownMenu) {
        dropdownMenu.style.display = 'none';
    }
};

window.closeLogoutModal = function() {
    const modal = document.getElementById('logoutModal');
    const content = modal.querySelector('.transform');
    
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.remove('opacity-100', 'visible');
        modal.classList.add('opacity-0', 'invisible');
    }, 200);
};

window.confirmLogout = function() {
    closeLogoutModal();
    window.location.href = '?action=logout';
};

window.showResetModal = function() {
    const modal = document.getElementById('resetModal');
    const content = modal.querySelector('.transform');
    
    modal.classList.remove('opacity-0', 'invisible');
    modal.classList.add('opacity-100', 'visible');
    
    setTimeout(() => {
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);
    
    // Re-initialize icons in the modal
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
};

window.closeResetModal = function() {
    const modal = document.getElementById('resetModal');
    const content = modal.querySelector('.transform');
    
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.remove('opacity-100', 'visible');
        modal.classList.add('opacity-0', 'invisible');
    }, 200);
};

window.confirmReset = function() {
    closeResetModal();
    location.reload();
};

// Bid Modal Functions
window.openBidModal = function(po_id, item_name) {
    document.getElementById('bidModalTitle').innerText = `Place Your Bid for "${item_name}"`;
    document.getElementById('po_id_input').value = po_id;
    
    // Reset form and show bid content
    document.getElementById('bidFormContent').classList.remove('hidden');
    document.getElementById('termsContent').classList.add('hidden');
    document.getElementById('bidForm').reset();
    document.getElementById('terms_checkbox').checked = false;
    
    document.getElementById('bidModal').classList.remove('hidden');
    // Reinitialize Lucide icons after modal is shown
    lucide.createIcons();
};

window.closeBidModal = function() {
    document.getElementById('bidModal').classList.add('hidden');
};

// Terms & Conditions functionality
document.addEventListener('DOMContentLoaded', function() {
    const termsLink = document.getElementById('termsLink');
    const backToBid = document.getElementById('backToBid');
    const acceptTerms = document.getElementById('acceptTerms');
    const bidFormContent = document.getElementById('bidFormContent');
    const termsContent = document.getElementById('termsContent');
    const termsCheckbox = document.getElementById('terms_checkbox');
    const bidForm = document.getElementById('bidForm');

    // Show terms content
    if (termsLink) {
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            bidFormContent.classList.add('hidden');
            termsContent.classList.remove('hidden');
            lucide.createIcons();
        });
    }

    // Go back to bid form
    if (backToBid) {
        backToBid.addEventListener('click', function() {
            termsContent.classList.add('hidden');
            bidFormContent.classList.remove('hidden');
            lucide.createIcons();
        });
    }

    // Accept terms and go back
    if (acceptTerms) {
        acceptTerms.addEventListener('click', function() {
            termsCheckbox.checked = true;
            termsContent.classList.add('hidden');
            bidFormContent.classList.remove('hidden');
            lucide.createIcons();
        });
    }

    // Form submission validation
    if (bidForm) {
        bidForm.addEventListener('submit', function(e) {
            if (!termsCheckbox.checked) {
                e.preventDefault();
                alert('Please accept the Terms & Conditions to continue.');
                return false;
            }
        });
    }
    
    // Close bid modal when clicking outside
    const bidModal = document.getElementById('bidModal');
    if (bidModal) {
        bidModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeBidModal();
            }
        });
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (logoutModal && !logoutModal.classList.contains('invisible')) {
                closeLogoutModal();
            }
            if (resetModal && !resetModal.classList.contains('invisible')) {
                closeResetModal();
            }
            if (bidModal && !bidModal.classList.contains('hidden')) {
                closeBidModal();
            }
        }
    });
});
</script>

<!-- Custom Confirmation Modal -->
<div id="customConfirmModal" class="modal">
    <div class="modal-content">
        <div id="confirmModalIcon" style="width: 8rem; height: 8rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="message-square-warning w-24 h-24 text-[--text-color]"></i>
        </div>
        <h2 id="confirmModalTitle" style="font-size: 1.25rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem;">Confirm Action</h2>
        <p id="confirmModalMessage" style="font-size: 1rem; line-height: 1.5; margin-bottom: 1rem;">Are you sure you want to continue?</p>
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 8px;">
            <button type="button" id="confirmModalCancel" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;">Cancel</button>
            <button type="button" id="confirmModalConfirm" style="background: #dc2626; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);">Clear All</button>
        </div>
    </div>
</div>
