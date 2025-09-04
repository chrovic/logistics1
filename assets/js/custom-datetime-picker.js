/**
 * Custom DateTime Picker Component
 * Extends the existing CustomDatepicker to include time selection
 */

// Prevent multiple declarations
if (typeof window.CustomDateTimePicker === 'undefined') {

class CustomDateTimePicker {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            placeholder: options.placeholder || 'Pick date & time',
            format: options.format || 'yyyy-mm-dd HH:MM',
            ...options
        };
        
        this.selectedDate = null;
        this.selectedTime = { hours: 12, minutes: 0 };
        this.currentDate = new Date();
        this.viewDate = new Date();
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        this.createDateTimePickerHTML();
        this.bindEvents();
        
        // Hide original input
        this.element.style.display = 'none';
        this.element.classList.remove('initializing');
        
        // Set initial value if input has one
        if (this.element.value) {
            this.setDateTime(this.element.value);
        }
    }
    
    createDateTimePickerHTML() {
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-datetime-picker';
        wrapper.innerHTML = `
            <div class="custom-datetime-picker-trigger" tabindex="0">
                <input type="text" class="custom-datetime-picker-input" placeholder="${this.options.placeholder}" readonly>
                <i data-lucide="calendar-clock" class="custom-datetime-picker-icon"></i>
            </div>
            <div class="custom-datetime-picker-calendar">
                <div class="custom-datetime-picker-header">
                    <button type="button" class="custom-datetime-picker-nav" data-nav="prev">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <div class="custom-datetime-picker-title">
                        <select class="custom-datetime-picker-month"></select>
                        <select class="custom-datetime-picker-year"></select>
                    </div>
                    <button type="button" class="custom-datetime-picker-nav" data-nav="next">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
                <div class="custom-datetime-picker-weekdays">
                    <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                </div>
                <div class="custom-datetime-picker-days"></div>
                <div class="custom-datetime-picker-time">
                    <div class="time-section">
                        <label>Time</label>
                        <div class="time-inputs">
                            <select class="time-hours">
                                ${Array.from({length: 24}, (_, i) => 
                                    `<option value="${i}">${String(i).padStart(2, '0')}</option>`
                                ).join('')}
                            </select>
                            <span>:</span>
                            <select class="time-minutes">
                                ${Array.from({length: 60}, (_, i) => 
                                    `<option value="${i}">${String(i).padStart(2, '0')}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="custom-datetime-picker-footer">
                    <button type="button" class="custom-datetime-picker-clear">Clear</button>
                    <button type="button" class="custom-datetime-picker-now">Now</button>
                </div>
            </div>
        `;
        
        this.element.parentNode.insertBefore(wrapper, this.element);
        this.wrapper = wrapper;
        this.trigger = wrapper.querySelector('.custom-datetime-picker-trigger');
        this.input = wrapper.querySelector('.custom-datetime-picker-input');
        this.calendar = wrapper.querySelector('.custom-datetime-picker-calendar');
        this.monthSelect = wrapper.querySelector('.custom-datetime-picker-month');
        this.yearSelect = wrapper.querySelector('.custom-datetime-picker-year');
        this.daysContainer = wrapper.querySelector('.custom-datetime-picker-days');
        this.hoursSelect = wrapper.querySelector('.time-hours');
        this.minutesSelect = wrapper.querySelector('.time-minutes');
        
        this.populateMonthYear();
        this.renderCalendar();
        
        // Set default time
        this.hoursSelect.value = this.selectedTime.hours;
        this.minutesSelect.value = this.selectedTime.minutes;
        
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
        for (let year = currentYear - 1; year <= currentYear + 10; year++) {
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
            emptyDay.className = 'custom-datetime-picker-day empty';
            this.daysContainer.appendChild(emptyDay);
        }
        
        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'custom-datetime-picker-day';
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
            const nav = e.target.closest('.custom-datetime-picker-nav');
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
            const day = e.target.closest('.custom-datetime-picker-day:not(.empty)');
            if (day) {
                const dateStr = day.getAttribute('data-date');
                this.selectedDate = new Date(dateStr + 'T00:00:00');
                this.updateDateTime();
                this.renderCalendar();
            }
        });
        
        // Time selection
        this.hoursSelect.addEventListener('change', () => {
            this.selectedTime.hours = parseInt(this.hoursSelect.value);
            this.updateDateTime();
        });
        
        this.minutesSelect.addEventListener('change', () => {
            this.selectedTime.minutes = parseInt(this.minutesSelect.value);
            this.updateDateTime();
        });
        
        // Footer buttons
        this.calendar.addEventListener('click', (e) => {
            if (e.target.matches('.custom-datetime-picker-clear')) {
                this.clearDateTime();
                this.close();
            } else if (e.target.matches('.custom-datetime-picker-now')) {
                this.setToNow();
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
        // Close other datetime pickers
        this.closeOtherDateTimePickers();
        
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
    
    closeOtherDateTimePickers() {
        document.querySelectorAll('.custom-datetime-picker-trigger.active').forEach(trigger => {
            if (trigger !== this.trigger) {
                trigger.classList.remove('active');
                trigger.parentNode.querySelector('.custom-datetime-picker-calendar').classList.remove('show');
            }
        });
    }
    
    positionCalendar() {
        const rect = this.trigger.getBoundingClientRect();
        const calendarHeight = 420; // Taller to accommodate time picker
        const spaceBelow = window.innerHeight - rect.bottom;
        const buffer = 20;
        
        // Set width and horizontal position
        this.calendar.style.left = rect.left + 'px';
        this.calendar.style.width = Math.max(rect.width, 300) + 'px';
        
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
    
    setDateTime(dateTimeStr) {
        if (!dateTimeStr) {
            this.selectedDate = null;
            this.selectedTime = { hours: 12, minutes: 0 };
            this.input.value = '';
            this.element.value = '';
        } else {
            const dateTime = new Date(dateTimeStr);
            this.selectedDate = dateTime;
            this.selectedTime = {
                hours: dateTime.getHours(),
                minutes: dateTime.getMinutes()
            };
            
            this.input.value = this.formatDisplayDateTime(dateTime);
            this.element.value = this.formatDateTime(dateTime);
            
            // Update view to show selected month
            this.viewDate = new Date(this.selectedDate);
            this.populateMonthYear();
            
            // Update time selects
            this.hoursSelect.value = this.selectedTime.hours;
            this.minutesSelect.value = this.selectedTime.minutes;
        }
        
        this.renderCalendar();
        
        // Trigger change event on original input
        const event = new Event('change', { bubbles: true });
        this.element.dispatchEvent(event);
    }
    
    updateDateTime() {
        if (this.selectedDate) {
            const newDateTime = new Date(this.selectedDate);
            newDateTime.setHours(this.selectedTime.hours, this.selectedTime.minutes, 0, 0);
            
            this.input.value = this.formatDisplayDateTime(newDateTime);
            this.element.value = this.formatDateTime(newDateTime);
            
            // Trigger change event on original input
            const event = new Event('change', { bubbles: true });
            this.element.dispatchEvent(event);
        }
    }
    
    setToNow() {
        const now = new Date();
        const nowStr = this.formatDateTime(now);
        this.setDateTime(nowStr);
    }
    
    clearDateTime() {
        this.setDateTime('');
    }
    
    formatDate(date) {
        if (!date) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    formatDateTime(date) {
        if (!date) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:00`;
    }
    
    formatDisplayDateTime(date) {
        if (!date) return '';
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
    }
    
    getValue() {
        return this.element.value;
    }
    
    setValue(value) {
        this.setDateTime(value);
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

// Make CustomDateTimePicker available globally
window.CustomDateTimePicker = CustomDateTimePicker;

/**
 * Initialize custom datetime pickers for all datetime-local inputs with the custom-datetime-picker class
 */
function initCustomDateTimePickers() {
    const dateTimeInputs = document.querySelectorAll('input[type="datetime-local"].custom-datetime-picker-input');
    
    dateTimeInputs.forEach(input => {
        // Skip if already initialized
        if (input.nextElementSibling && 
            input.nextElementSibling.classList.contains('custom-datetime-picker')) {
            return;
        }
        
        // Clean up any partial initialization
        const existingWrapper = input.nextElementSibling;
        if (existingWrapper && existingWrapper.classList.contains('custom-datetime-picker')) {
            existingWrapper.remove();
        }
        
        const placeholder = input.getAttribute('data-placeholder') || 
                          input.getAttribute('placeholder') || 
                          'Pick date & time';
        
        try {
            // Temporarily add initializing class in case of failure
            input.classList.add('initializing');
            
            new CustomDateTimePicker(input, {
                placeholder: placeholder
            });
            
            // Remove initializing class on success
            input.classList.remove('initializing');
        } catch (error) {
            console.warn('Failed to initialize custom datetime picker:', error);
            // Show original input if initialization fails
            input.classList.add('initializing');
        }
    });
}

/**
 * Clean up orphaned custom datetime pickers
 */
function cleanupOrphanedDateTimePickers() {
    // Find custom datetime picker wrappers without corresponding datetime inputs
    const datetimePickerWrappers = document.querySelectorAll('.custom-datetime-picker');
    datetimePickerWrappers.forEach(wrapper => {
        const input = wrapper.previousElementSibling;
        if (!input || input.type !== 'datetime-local' || !input.classList.contains('custom-datetime-picker-input')) {
            wrapper.remove();
        }
    });
    
    // Find datetime inputs that should be custom but don't have wrappers
    const inputsWithoutWrappers = document.querySelectorAll('input[type="datetime-local"].custom-datetime-picker-input');
    inputsWithoutWrappers.forEach(input => {
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('custom-datetime-picker')) {
            // Remove any leftover initializing classes
            input.classList.remove('initializing');
        }
    });
}

/**
 * Global function to reinitialize datetime pickers
 */
function reinitializeCustomDateTimePickers() {
    cleanupOrphanedDateTimePickers();
    initCustomDateTimePickers();
}

window.reinitializeCustomDateTimePickers = reinitializeCustomDateTimePickers;

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initCustomDateTimePickers);

// Re-initialize when modals are opened
document.addEventListener('click', (e) => {
    if (e.target.matches('[data-modal-trigger]') || e.target.closest('[data-modal-trigger]')) {
        requestAnimationFrame(reinitializeCustomDateTimePickers);
    }
});

// Re-initialize after PJAX navigation
window.addEventListener('focus', () => {
    setTimeout(reinitializeCustomDateTimePickers, 50);
});

window.addEventListener('popstate', () => {
    requestAnimationFrame(reinitializeCustomDateTimePickers);
});

// Re-initialize when any modal is shown (generic approach)
const initMutationObserver = () => {
    if (typeof MutationObserver !== 'undefined' && document.body) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Check if it's a modal or contains datetime inputs
                            if (node.querySelector && node.querySelector('input[type="datetime-local"].custom-datetime-picker-input')) {
                                requestAnimationFrame(reinitializeCustomDateTimePickers);
                            }
                        }
                    });
                }
            });
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
};

// Initialize observer when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMutationObserver);
} else {
    initMutationObserver();
}

// Close the CustomDateTimePicker class declaration guard
} 