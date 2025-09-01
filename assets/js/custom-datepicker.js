/**
 * Custom Datepicker Component
 * Replaces standard date inputs with a custom calendar interface
 */

// Prevent multiple declarations
if (typeof window.CustomDatepicker === 'undefined') {

class CustomDatepicker {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            placeholder: options.placeholder || 'Pick a date',
            format: options.format || 'yyyy-mm-dd',
            ...options
        };
        
        this.selectedDate = null;
        this.currentDate = new Date();
        this.viewDate = new Date();
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        this.createDatepickerHTML();
        this.bindEvents();
        
        // Hide original input
        this.element.style.display = 'none';
        this.element.classList.remove('initializing');
        
        // Set initial value if input has one
        if (this.element.value) {
            this.setDate(this.element.value);
        }
    }
    
    createDatepickerHTML() {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-datepicker';
        wrapper.innerHTML = `
            <div class="custom-datepicker-trigger" tabindex="0">
                <input type="text" class="custom-datepicker-input" placeholder="${this.options.placeholder}" readonly>
                <i data-lucide="calendar" class="custom-datepicker-icon"></i>
            </div>
            <div class="custom-datepicker-calendar">
                <div class="custom-datepicker-header">
                    <button type="button" class="custom-datepicker-nav" data-nav="prev">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="custom-datepicker-title">
                        <select class="custom-datepicker-month"></select>
                        <select class="custom-datepicker-year"></select>
                    </div>
                    <button type="button" class="custom-datepicker-nav" data-nav="next">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
                <div class="custom-datepicker-weekdays">
                    <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                </div>
                <div class="custom-datepicker-days"></div>
                <div class="custom-datepicker-footer">
                    <button type="button" class="custom-datepicker-clear">Clear</button>
                    <button type="button" class="custom-datepicker-today">Today</button>
                </div>
            </div>
        `;
        
        this.element.parentNode.insertBefore(wrapper, this.element);
        this.wrapper = wrapper;
        this.trigger = wrapper.querySelector('.custom-datepicker-trigger');
        this.input = wrapper.querySelector('.custom-datepicker-input');
        this.calendar = wrapper.querySelector('.custom-datepicker-calendar');
        this.monthSelect = wrapper.querySelector('.custom-datepicker-month');
        this.yearSelect = wrapper.querySelector('.custom-datepicker-year');
        this.daysContainer = wrapper.querySelector('.custom-datepicker-days');
        
        this.populateMonthYear();
        this.renderCalendar();
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    populateMonthYear() {
        // Populate months with abbreviated names
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        this.monthSelect.innerHTML = '';
        months.forEach((month, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = month;
            this.monthSelect.appendChild(option);
        });
        
        // Populate years (current year ± 10)
        const currentYear = new Date().getFullYear();
        this.yearSelect.innerHTML = '';
        for (let year = currentYear - 10; year <= currentYear + 10; year++) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            this.yearSelect.appendChild(option);
        }
        
        // Set current values
        this.monthSelect.value = this.viewDate.getMonth();
        this.yearSelect.value = this.viewDate.getFullYear();
    }
    
    renderCalendar() {
        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();
        
        // First day of the month and how many days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();
        
        this.daysContainer.innerHTML = '';
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < startingDayOfWeek; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'custom-datepicker-day empty';
            this.daysContainer.appendChild(emptyDay);
        }
        
        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'custom-datepicker-day';
            dayElement.textContent = day;
            dayElement.setAttribute('data-date', `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`);
            
            // Check if this is today
            const today = new Date();
            if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                dayElement.classList.add('today');
            }
            
            // Check if this is the selected date
            if (this.selectedDate) {
                const selectedDateStr = this.formatDate(this.selectedDate);
                const dayDateStr = dayElement.getAttribute('data-date');
                if (selectedDateStr === dayDateStr) {
                    dayElement.classList.add('selected');
                }
            }
            
            this.daysContainer.appendChild(dayElement);
        }
    }
    
    bindEvents() {
        // Toggle calendar
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });
        
        // Month/year navigation
        this.calendar.addEventListener('click', (e) => {
            const nav = e.target.closest('.custom-datepicker-nav');
            if (nav) {
                e.stopPropagation();
                const direction = nav.getAttribute('data-nav');
                if (direction === 'prev') {
                    this.previousMonth();
                } else if (direction === 'next') {
                    this.nextMonth();
                }
            }
        });
        
        // Month/year select change
        this.monthSelect.addEventListener('change', () => {
            this.viewDate.setMonth(parseInt(this.monthSelect.value));
            this.renderCalendar();
        });
        
        this.yearSelect.addEventListener('change', () => {
            this.viewDate.setFullYear(parseInt(this.yearSelect.value));
            this.renderCalendar();
        });
        
        // Day selection
        this.daysContainer.addEventListener('click', (e) => {
            const day = e.target.closest('.custom-datepicker-day:not(.empty)');
            if (day) {
                const dateStr = day.getAttribute('data-date');
                this.setDate(dateStr);
                this.close();
            }
        });
        
        // Footer buttons
        this.calendar.addEventListener('click', (e) => {
            if (e.target.matches('.custom-datepicker-clear')) {
                this.clearDate();
                this.close();
            } else if (e.target.matches('.custom-datepicker-today')) {
                this.setToday();
                this.close();
            }
        });
        
        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) {
                this.close();
            }
        });
        
        // Keyboard navigation
        this.trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggle();
            } else if (e.key === 'Escape') {
                this.close();
            }
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        // Close other datepickers
        this.closeOtherDatepickers();
        
        this.isOpen = true;
        this.trigger.classList.add('active');
        this.calendar.classList.add('show');
        
        // Position calendar
        this.positionCalendar();
        
        // Add scroll listener for repositioning
        if (!this.repositionHandler) {
            this.repositionHandler = () => {
                if (this.isOpen) {
                    this.positionCalendar();
                }
            };
        }
        window.addEventListener('scroll', this.repositionHandler, true);
        window.addEventListener('resize', this.repositionHandler);
    }
    
    close() {
        this.isOpen = false;
        this.trigger.classList.remove('active');
        this.calendar.classList.remove('show');
        
        // Remove positioning event listeners
        if (this.repositionHandler) {
            window.removeEventListener('scroll', this.repositionHandler, true);
            window.removeEventListener('resize', this.repositionHandler);
        }
    }
    
    closeOtherDatepickers() {
        document.querySelectorAll('.custom-datepicker-trigger.active').forEach(trigger => {
            if (trigger !== this.trigger) {
                trigger.classList.remove('active');
                trigger.parentNode.querySelector('.custom-datepicker-calendar').classList.remove('show');
            }
        });
    }
    
    positionCalendar() {
        const rect = this.trigger.getBoundingClientRect();
        const calendarHeight = 320; // Approximate calendar height
        const spaceBelow = window.innerHeight - rect.bottom;
        const buffer = 20;
        
        // Set width and horizontal position
        this.calendar.style.left = rect.left + 'px';
        this.calendar.style.width = Math.max(rect.width, 280) + 'px';
        
        // Determine vertical position
        if (spaceBelow < calendarHeight + buffer && rect.top > calendarHeight + buffer) {
            // Position above
            this.calendar.style.top = (rect.top - calendarHeight - 4) + 'px';
            this.calendar.style.bottom = 'auto';
        } else {
            // Position below (default)
            this.calendar.style.top = (rect.bottom + 4) + 'px';
            this.calendar.style.bottom = 'auto';
        }
    }
    
    previousMonth() {
        this.viewDate.setMonth(this.viewDate.getMonth() - 1);
        this.populateMonthYear();
        this.renderCalendar();
    }
    
    nextMonth() {
        this.viewDate.setMonth(this.viewDate.getMonth() + 1);
        this.populateMonthYear();
        this.renderCalendar();
    }
    
    setDate(dateStr) {
        if (!dateStr) {
            this.selectedDate = null;
            this.input.value = '';
            this.element.value = '';
        } else {
            this.selectedDate = new Date(dateStr + 'T00:00:00');
            this.input.value = this.formatDisplayDate(this.selectedDate);
            this.element.value = this.formatDate(this.selectedDate);
            
            // Update view to show selected month
            this.viewDate = new Date(this.selectedDate);
            this.populateMonthYear();
        }
        
        this.renderCalendar();
        
        // Trigger change event on original input
        const event = new Event('change', { bubbles: true });
        this.element.dispatchEvent(event);
    }
    
    setToday() {
        const today = new Date();
        const todayStr = this.formatDate(today);
        this.setDate(todayStr);
    }
    
    clearDate() {
        this.setDate('');
    }
    
    formatDate(date) {
        if (!date) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    formatDisplayDate(date) {
        if (!date) return '';
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        };
        return date.toLocaleDateString('en-US', options);
    }
    
    getValue() {
        return this.element.value;
    }
    
    setValue(value) {
        this.setDate(value);
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

// Make CustomDatepicker available globally
window.CustomDatepicker = CustomDatepicker;

/**
 * Initialize custom datepickers for all date inputs with the custom-datepicker class
 */
function initCustomDatepickers() {
    const dateInputs = document.querySelectorAll('input[type="date"].custom-datepicker-input');
    
    dateInputs.forEach(input => {
        // Skip if already initialized
        if (input.nextElementSibling && 
            input.nextElementSibling.classList.contains('custom-datepicker')) {
            return;
        }
        
        // Clean up any partial initialization
        const existingWrapper = input.nextElementSibling;
        if (existingWrapper && existingWrapper.classList.contains('custom-datepicker')) {
            existingWrapper.remove();
        }
        
        const placeholder = input.getAttribute('data-placeholder') || 
                          input.getAttribute('placeholder') || 
                          'Pick a date';
        
        try {
            // Temporarily add initializing class in case of failure
            input.classList.add('initializing');
            
            new CustomDatepicker(input, {
                placeholder: placeholder
            });
            
            // Remove initializing class on success
            input.classList.remove('initializing');
        } catch (error) {
            console.warn('Failed to initialize custom datepicker:', error);
            // Show original input if initialization fails
            input.classList.add('initializing');
        }
    });
}

/**
 * Clean up orphaned custom datepickers
 */
function cleanupOrphanedDatepickers() {
    // Find custom datepicker wrappers without corresponding date inputs
    const datepickerWrappers = document.querySelectorAll('.custom-datepicker');
    datepickerWrappers.forEach(wrapper => {
        const input = wrapper.previousElementSibling;
        if (!input || input.type !== 'date' || !input.classList.contains('custom-datepicker-input')) {
            wrapper.remove();
        }
    });
    
    // Find date inputs that should be custom but don't have wrappers
    const inputsWithoutWrappers = document.querySelectorAll('input[type="date"].custom-datepicker-input');
    inputsWithoutWrappers.forEach(input => {
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('custom-datepicker')) {
            // Remove any leftover initializing classes
            input.classList.remove('initializing');
        }
    });
}

/**
 * Global function to reinitialize datepickers
 */
function reinitializeCustomDatepickers() {
    cleanupOrphanedDatepickers();
    initCustomDatepickers();
}

window.reinitializeCustomDatepickers = reinitializeCustomDatepickers;

/**
 * Close all custom datepickers
 */
function closeAllCustomDatepickers() {
    document.querySelectorAll('.custom-datepicker-trigger.active').forEach(trigger => {
        trigger.classList.remove('active');
        trigger.parentNode.querySelector('.custom-datepicker-calendar').classList.remove('show');
    });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initCustomDatepickers);

// Re-initialize when modals are opened
document.addEventListener('click', (e) => {
    if (e.target.matches('[data-modal-trigger]') || e.target.closest('[data-modal-trigger]')) {
        requestAnimationFrame(reinitializeCustomDatepickers);
    }
});

// Re-initialize after PJAX navigation
window.addEventListener('focus', () => {
    setTimeout(reinitializeCustomDatepickers, 50);
});

window.addEventListener('popstate', () => {
    requestAnimationFrame(reinitializeCustomDatepickers);
});

// Close the CustomDatepicker class declaration guard
} 