/**
 * Custom Dropdown Component
 * Replaces standard select elements with a custom dropdown that includes check icons
 */

// Prevent multiple declarations
if (typeof window.CustomDropdown === 'undefined') {
    
class CustomDropdown {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            placeholder: options.placeholder || 'Select an option',
            searchable: options.searchable || false,
            clearable: options.clearable || false,
            ...options
        };
        
        this.selectedValue = '';
        this.selectedText = '';
        this.isOpen = false;
        this.allOptions = [];
        this.filteredOptions = [];
        
        this.init();
    }
    
    init() {
        this.createDropdownHTML();
        this.bindEvents();
        this.populateOptions();
        
        // Hide original select (redundant with CSS but ensures it's hidden)
        this.element.style.display = 'none';
        this.element.classList.remove('initializing');
        
        // Set initial value if select has one
        const initialValue = this.element.value;
        if (initialValue) {
            this.selectOption(initialValue);
        }
    }
    
    createDropdownHTML() {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-dropdown';
        
        if (this.options.searchable) {
            wrapper.innerHTML = `
                <div class="custom-dropdown-trigger searchable" tabindex="0">
                    <input type="text" class="custom-dropdown-search" placeholder="${this.options.placeholder}" autocomplete="off">
                    <i data-lucide="chevron-down" class="custom-dropdown-icon"></i>
                </div>
                <div class="custom-dropdown-menu">
                    <!-- Options will be populated here -->
                </div>
            `;
            this.searchInput = wrapper.querySelector('.custom-dropdown-search');
        } else {
            wrapper.innerHTML = `
                <div class="custom-dropdown-trigger" tabindex="0">
                    <span class="custom-dropdown-text custom-dropdown-placeholder">${this.options.placeholder}</span>
                    <i data-lucide="chevron-down" class="custom-dropdown-icon"></i>
                </div>
                <div class="custom-dropdown-menu">
                    <!-- Options will be populated here -->
                </div>
            `;
            this.textElement = wrapper.querySelector('.custom-dropdown-text');
        }
        
        this.element.parentNode.insertBefore(wrapper, this.element);
        this.wrapper = wrapper;
        this.trigger = wrapper.querySelector('.custom-dropdown-trigger');
        this.menu = wrapper.querySelector('.custom-dropdown-menu');
        this.icon = wrapper.querySelector('.custom-dropdown-icon');
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    populateOptions() {
        const options = Array.from(this.element.options);
        
        // Store all options for filtering
        this.allOptions = options.filter(option => {
            return !(option.value === '' && option.textContent.trim().startsWith('--'));
        }).map(option => ({
            value: option.value,
            text: option.textContent,
            element: option
        }));
        
        this.filteredOptions = [...this.allOptions];
        this.renderOptions();
    }
    
    renderOptions(filter = '') {
        this.menu.innerHTML = '';
        
        // Filter options if search term provided
        let optionsToShow = this.filteredOptions;
        if (filter && this.options.searchable) {
            optionsToShow = this.allOptions.filter(option => 
                option.text.toLowerCase().includes(filter.toLowerCase()) ||
                option.value.toLowerCase().includes(filter.toLowerCase())
            );
        }
        
        // Show "No results" message if no options match
        if (optionsToShow.length === 0 && filter) {
            const noResultsElement = document.createElement('div');
            noResultsElement.className = 'custom-dropdown-no-results';
            noResultsElement.textContent = 'No items found';
            this.menu.appendChild(noResultsElement);
            return;
        }
        
        optionsToShow.forEach(option => {
            const optionElement = document.createElement('div');
            optionElement.className = 'custom-dropdown-option';
            optionElement.setAttribute('data-value', option.value);
            optionElement.innerHTML = `
                <span class="custom-dropdown-text">${option.text}</span>
                <i data-lucide="check" class="custom-dropdown-check"></i>
            `;
            
            this.menu.appendChild(optionElement);
        });
        
        // Initialize Lucide icons for new elements
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    bindEvents() {
        if (this.options.searchable && this.searchInput) {
            // Handle search input
            this.searchInput.addEventListener('input', (e) => {
                const filter = e.target.value;
                this.renderOptions(filter);
                if (!this.isOpen) {
                    this.open();
                }
            });
            
            this.searchInput.addEventListener('focus', () => {
                this.trigger.classList.add('active');
                this.open();
            });
            
            this.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.close();
                    this.searchInput.blur();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.focusFirstOption();
                }
            });
            
            this.searchInput.addEventListener('blur', (e) => {
                // Small delay to allow option selection
                setTimeout(() => {
                    if (!this.wrapper.contains(document.activeElement)) {
                        this.trigger.classList.remove('active');
                        this.close();
                    }
                }, 150);
            });
            
            // Handle clicking on the dropdown icon or anywhere in the trigger
            this.trigger.addEventListener('click', (e) => {
                // Don't interfere if clicking directly on the search input
                if (e.target === this.searchInput) {
                    return;
                }
                
                e.stopPropagation();
                if (this.isOpen) {
                    this.close();
                } else {
                    this.open();
                    this.searchInput.focus();
                }
            });
        } else {
            // Toggle dropdown for non-searchable
            this.trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });
            
            // Keyboard navigation for non-searchable
            this.trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggle();
                } else if (e.key === 'Escape') {
                    this.close();
                }
            });
        }
        
        // Option selection
        this.menu.addEventListener('click', (e) => {
            const option = e.target.closest('.custom-dropdown-option');
            if (option) {
                const value = option.getAttribute('data-value');
                const text = option.querySelector('.custom-dropdown-text').textContent;
                this.selectOption(value, text);
                this.close();
            }
        });
        
        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) {
                this.close();
            }
        });
    }
    
    focusFirstOption() {
        const firstOption = this.menu.querySelector('.custom-dropdown-option');
        if (firstOption) {
            firstOption.focus();
        }
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        // Close other dropdowns
        this.closeOtherDropdowns();
        
        this.isOpen = true;
        this.trigger.classList.add('active');
        this.menu.classList.add('show');
        
        // Position dropdown smartly
        this.positionDropdown();
        
        // Add scroll and resize listeners for repositioning
        if (!this.repositionHandler) {
            this.repositionHandler = () => {
                if (this.isOpen) {
                    this.positionDropdown();
                }
            };
        }
        window.addEventListener('scroll', this.repositionHandler, true);
        window.addEventListener('resize', this.repositionHandler);
    }
    
    close() {
        this.isOpen = false;
        this.trigger.classList.remove('active');
        this.menu.classList.remove('show');
        
        // Remove positioning event listeners
        if (this.repositionHandler) {
            window.removeEventListener('scroll', this.repositionHandler, true);
            window.removeEventListener('resize', this.repositionHandler);
        }
        
        // For searchable dropdowns, ensure the trigger loses focus styling
        if (this.options.searchable && this.searchInput) {
            // Only remove active if not currently focused
            if (document.activeElement !== this.searchInput) {
                this.trigger.classList.remove('active');
            }
        }
    }
    
    closeOtherDropdowns() {
        document.querySelectorAll('.custom-dropdown-trigger.active').forEach(trigger => {
            if (trigger !== this.trigger) {
                trigger.classList.remove('active');
                trigger.parentNode.querySelector('.custom-dropdown-menu').classList.remove('show');
            }
        });
    }
    
    positionDropdown() {
        const rect = this.trigger.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const menuHeight = Math.min(this.menu.scrollHeight, 200); // Max height is 200px
        const buffer = 20;
        
        // Set width and horizontal position
        this.menu.style.left = rect.left + 'px';
        this.menu.style.width = rect.width + 'px';
        
        // Determine vertical position
        if (spaceBelow < menuHeight + buffer && rect.top > menuHeight + buffer) {
            // Position above
            this.menu.style.top = (rect.top - menuHeight - 4) + 'px';
            this.menu.style.bottom = 'auto';
        } else {
            // Position below (default)
            this.menu.style.top = (rect.bottom + 4) + 'px';
            this.menu.style.bottom = 'auto';
        }
    }
    
    selectOption(value, text = null) {
        // Update the original select element
        this.element.value = value;
        
        // Trigger change event on original select
        const event = new Event('change', { bubbles: true });
        this.element.dispatchEvent(event);
        
        // Update visual state
        this.selectedValue = value;
        this.selectedText = text || this.getTextForValue(value);
        
        // Update display based on dropdown type
        if (this.options.searchable && this.searchInput) {
            this.searchInput.value = this.selectedText || '';
            // Clear any search filter
            this.renderOptions();
        } else if (this.textElement) {
            // Update trigger text for non-searchable dropdowns
            if (this.selectedText) {
                this.textElement.textContent = this.selectedText;
                this.textElement.className = 'custom-dropdown-text custom-dropdown-selected';
            } else {
                this.textElement.textContent = this.options.placeholder;
                this.textElement.className = 'custom-dropdown-text custom-dropdown-placeholder';
            }
        }
        
        // Update option states
        this.menu.querySelectorAll('.custom-dropdown-option').forEach(option => {
            const optionValue = option.getAttribute('data-value');
            if (optionValue === value) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    }
    
    getTextForValue(value) {
        const option = Array.from(this.element.options).find(opt => opt.value === value);
        return option ? option.textContent : '';
    }
    
    setValue(value) {
        this.selectOption(value);
    }
    
    getValue() {
        return this.selectedValue;
    }
    
    destroy() {
        // Clean up event listeners
        if (this.repositionHandler) {
            window.removeEventListener('scroll', this.repositionHandler, true);
            window.removeEventListener('resize', this.repositionHandler);
        }
        
        if (this.wrapper && this.wrapper.parentNode) {
            this.wrapper.parentNode.removeChild(this.wrapper);
        }
        this.element.style.display = '';
    }
}

// Make CustomDropdown available globally
window.CustomDropdown = CustomDropdown;

/**
 * Initialize custom dropdowns for all select elements with the custom-dropdown class
 */
function initCustomDropdowns() {
    const selects = document.querySelectorAll('select.custom-dropdown-select');
    
    selects.forEach(select => {
        // Skip if already initialized (check for custom dropdown wrapper)
        if (select.nextElementSibling && 
            select.nextElementSibling.classList.contains('custom-dropdown')) {
            return;
        }
        
        // Clean up any partial initialization
        const existingWrapper = select.nextElementSibling;
        if (existingWrapper && existingWrapper.classList.contains('custom-dropdown')) {
            existingWrapper.remove();
        }
        
        const placeholder = select.getAttribute('data-placeholder') || 
                          select.querySelector('option[value=""]')?.textContent || 
                          'Select an option';
        
        const isSearchable = select.hasAttribute('data-searchable') || 
                           select.classList.contains('searchable');
        
        try {
            // Temporarily add initializing class in case of failure
            select.classList.add('initializing');
            
            new CustomDropdown(select, {
                placeholder: placeholder,
                searchable: isSearchable
            });
            
            // Remove initializing class on success (redundant but safe)
            select.classList.remove('initializing');
        } catch (error) {
            console.warn('Failed to initialize custom dropdown:', error);
            // Show original select if initialization fails
            select.classList.add('initializing');
        }
    });
}

/**
 * Clean up orphaned custom dropdowns (useful after PJAX navigation)
 */
function cleanupOrphanedDropdowns() {
    // Find custom dropdown wrappers without corresponding select elements
    const dropdownWrappers = document.querySelectorAll('.custom-dropdown');
    dropdownWrappers.forEach(wrapper => {
        const select = wrapper.previousElementSibling;
        if (!select || select.tagName !== 'SELECT' || !select.classList.contains('custom-dropdown-select')) {
            wrapper.remove();
        }
    });
    
    // Find selects that should be custom but don't have wrappers
    const selectsWithoutWrappers = document.querySelectorAll('select.custom-dropdown-select');
    selectsWithoutWrappers.forEach(select => {
        if (!select.nextElementSibling || !select.nextElementSibling.classList.contains('custom-dropdown')) {
            // Remove any leftover initializing classes
            select.classList.remove('initializing');
        }
    });
}

/**
 * Global function to reinitialize dropdowns (useful for dynamically added content)
 */
function reinitializeCustomDropdowns() {
    cleanupOrphanedDropdowns();
    initCustomDropdowns();
}

window.reinitializeCustomDropdowns = reinitializeCustomDropdowns;

/**
 * Close all custom dropdowns
 */
function closeAllCustomDropdowns() {
    document.querySelectorAll('.custom-dropdown-trigger.active').forEach(trigger => {
        trigger.classList.remove('active');
        trigger.parentNode.querySelector('.custom-dropdown-menu').classList.remove('show');
    });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initCustomDropdowns);

// Re-initialize when modals are opened (since some modals might be dynamically loaded)
document.addEventListener('click', (e) => {
    if (e.target.matches('[data-modal-trigger]') || e.target.closest('[data-modal-trigger]')) {
        requestAnimationFrame(reinitializeCustomDropdowns);
    }
});

// Re-initialize on window focus (helps with browser back/forward navigation)
window.addEventListener('focus', () => {
    setTimeout(reinitializeCustomDropdowns, 50);
});

// Re-initialize after any history navigation
window.addEventListener('popstate', () => {
    requestAnimationFrame(reinitializeCustomDropdowns);
});

// Fallback: periodically check and reinitialize if needed (only in development)
if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
    setInterval(() => {
        const selectsWithoutDropdowns = document.querySelectorAll('select.custom-dropdown-select:not([style*="display: none"])');
        if (selectsWithoutDropdowns.length > 0) {
            reinitializeCustomDropdowns();
        }
    }, 2000);
}

// Close the CustomDropdown class declaration guard
} 