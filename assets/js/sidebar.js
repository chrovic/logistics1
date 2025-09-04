window.addEventListener('load', () => {
    document.documentElement.classList.remove('preload');
  });
  
document.addEventListener('DOMContentLoaded', () => {

    // --- Centralized DOM Element Declarations ---
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburger');
    const barsIcon = document.getElementById('barsIcon');
    const xmarkIcon = document.getElementById('xmarkIcon');
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    const mainContentWrapper = document.getElementById('mainContentWrapper');

    // Clear sidebar session state on fresh page load (not PJAX)
    if (!window.__sidebarSessionCleared) {
        try {
            sessionStorage.removeItem('sidebarUserToggled');
            sessionStorage.removeItem('sidebarCollapsed');
            window.__sidebarSessionCleared = true;
        } catch(e) {}
    }

    // --- Sidebar State Management ---
    function applySidebarState(targetSidebar = null, targetWrapper = null, targetBarsIcon = null, targetXmarkIcon = null) {
        try {
            const currentSidebar = targetSidebar || document.getElementById('sidebar');
            const currentWrapper = targetWrapper || document.getElementById('mainContentWrapper');
            const currentBarsIcon = targetBarsIcon || document.getElementById('barsIcon');
            const currentXmarkIcon = targetXmarkIcon || document.getElementById('xmarkIcon');
            
            if (!currentSidebar || !currentWrapper) return;
            
            // Always start maximized on fresh page loads (sessionStorage is cleared)
            const shouldCollapse = false;
            
            // Clean up any existing sidebar state classes first
            currentSidebar.classList.remove('collapsed', 'initial-collapsed');
            currentWrapper.classList.remove('expanded', 'initial-expanded');
            
            // Apply the correct state
            if (shouldCollapse) {
                currentSidebar.classList.add('collapsed');
                currentWrapper.classList.add('expanded');
                document.body.classList.remove('sidebar-active');
            } else {
                document.body.classList.add('sidebar-active');
            }
            
            // Set icon state
            if (currentBarsIcon && currentXmarkIcon) {
                if (shouldCollapse) {
                    currentBarsIcon.classList.add('hidden');
                    currentXmarkIcon.classList.remove('hidden');
                } else {
                    currentBarsIcon.classList.remove('hidden');
                    currentXmarkIcon.classList.add('hidden');
                }
            }
        } catch (_) { 
            // Fallback: always show maximized
            try {
                document.body.classList.add('sidebar-active');
            } catch (_) {}
        }
    }

    // Make applySidebarState globally available
    window.applySidebarState = applySidebarState;

    // --- Lightweight PJAX to keep sidebar persistent ---
    if (!window.__loadedScriptSrcs) {
        window.__loadedScriptSrcs = new Set();
    }

    function isSameOriginAbsoluteUrl(url) {
        try { const u = new URL(url, window.location.href); return u.origin === window.location.origin; } catch (_) { return false; }
    }

    function updateActiveSidebarLink(url) {
        try {
            const u = new URL(url, window.location.href);
            const currentFileName = u.pathname.substring(u.pathname.lastIndexOf('/') + 1);
            document.querySelectorAll('.sidebar a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#') return;
                const linkFileName = new URL(link.href).pathname.split('/').pop();
                if (linkFileName === currentFileName) link.classList.add('active');
            });
            // Keep only the dropdown that contains the active link open
            const activeLink = Array.from(document.querySelectorAll('.sidebar a')).find(a => a.classList.contains('active'));
            const dropdownContents = document.querySelectorAll('.sidebar-dropdown-content');
            if (activeLink) {
                let keepId = null;
                dropdownContents.forEach(content => {
                    if (content.contains(activeLink)) {
                        keepId = content.id;
                    }
                });
                dropdownContents.forEach(content => {
                    if (content.id === keepId) {
                        content.classList.add('show');
                        const t = document.querySelector(`.sidebar-dropdown-toggle[data-target="${content.id}"]`);
                        if (t) t.classList.add('active');
                    } else {
                        content.classList.remove('show');
                        const t = document.querySelector(`.sidebar-dropdown-toggle[data-target="${content.id}"]`);
                        if (t) t.classList.remove('active');
                    }
                });
            } else {
                // No active link in dropdowns: close all
                dropdownContents.forEach(content => {
                    content.classList.remove('show');
                    const t = document.querySelector(`.sidebar-dropdown-toggle[data-target="${content.id}"]`);
                    if (t) t.classList.remove('active');
                });
            }
        } catch (_) { /* no-op */ }
    }

    async function executeScriptsFrom(containerNode, baseUrl) {
        const scriptNodes = Array.from(containerNode.querySelectorAll('script'));
        // Remove original script nodes to avoid duplicate DOM
        scriptNodes.forEach(s => s.parentNode && s.parentNode.removeChild(s));
        // Sequentially load scripts to preserve order
        for (const scriptNode of scriptNodes) {
            const newScript = document.createElement('script');
            // Copy attributes
            for (const attr of scriptNode.attributes) {
                newScript.setAttribute(attr.name, attr.value);
            }
            if (scriptNode.src) {
                const absSrc = new URL(scriptNode.getAttribute('src'), baseUrl).href;
                if (window.__loadedScriptSrcs.has(absSrc)) {
                    continue; // skip already loaded external scripts
                }
                newScript.src = absSrc;
                await new Promise((resolve, reject) => {
                    newScript.onload = () => { window.__loadedScriptSrcs.add(absSrc); resolve(); };
                    newScript.onerror = () => resolve(); // fail silently; page should still render
                    document.body.appendChild(newScript);
                });
            } else {
                newScript.text = scriptNode.textContent || '';
                document.body.appendChild(newScript);
            }
        }
    }

    async function pjaxNavigate(url, addToHistory = true) {
        if (!isSameOriginAbsoluteUrl(url)) { window.location.href = url; return; }
        document.body.classList.add('pjax-loading');
        
        // Add loading state to prevent FOUC during transition
        document.documentElement.classList.add('loading');
        
        // Clean up dashboard resources when navigating away
        if (typeof window.cleanupDashboard === 'function') {
            try {
                window.cleanupDashboard();
            } catch (e) {
                // Ignore cleanup errors
            }
        }
        
        // Clean up ALMS drag drop initialization when navigating away
        if (typeof window.resetDragDropInitialization === 'function') {
            try {
                window.resetDragDropInitialization();
            } catch (e) {
                // Ignore cleanup errors
            }
        }
        
        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            const htmlText = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            const newWrapper = doc.querySelector('#mainContentWrapper');
            const currentWrapper = document.getElementById('mainContentWrapper');
            if (newWrapper && currentWrapper) {
                // Handle page-specific styles
                try {
                    // Remove existing page-specific styles and stylesheets
                    const existingPageStyles = document.querySelectorAll('style[data-page-specific]');
                    existingPageStyles.forEach(style => style.remove());
                    const existingPageLinks = document.querySelectorAll('link[data-page-specific]');
                    existingPageLinks.forEach(link => link.remove());
                    
                    // Extract and apply new page-specific inline styles
                    const pageStyles = doc.querySelectorAll('head style');
                    pageStyles.forEach(style => {
                        const newStyle = document.createElement('style');
                        newStyle.textContent = style.textContent;
                        newStyle.setAttribute('data-page-specific', 'true');
                        document.head.appendChild(newStyle);
                    });
                    
                    // Extract and apply new page-specific external stylesheets
                    const pageLinks = doc.querySelectorAll('head link[rel="stylesheet"]:not([href*="styles.css"]):not([href*="sidebar.css"]):not([href*="font-awesome"]):not([href*="tailwindcss"])');
                    pageLinks.forEach(link => {
                        const newLink = document.createElement('link');
                        newLink.rel = 'stylesheet';
                        newLink.href = link.href;
                        newLink.setAttribute('data-page-specific', 'true');
                        document.head.appendChild(newLink);
                    });
                } catch (_) {}

                // Only replace the content inside mainContentWrapper, not the wrapper itself
                // This preserves sidebar state completely
                const imported = document.importNode(newWrapper, true);
                currentWrapper.innerHTML = imported.innerHTML;
                
                // Copy any classes from the new wrapper to the current one
                if (imported.className) {
                    // Preserve sidebar-related classes, copy page-specific classes
                    const currentClasses = currentWrapper.className.split(' ');
                    const newClasses = imported.className.split(' ');
                    const sidebarClasses = currentClasses.filter(cls => 
                        cls.includes('expanded') || cls.includes('initial-expanded')
                    );
                    const pageClasses = newClasses.filter(cls => 
                        !cls.includes('expanded') && !cls.includes('initial-expanded')
                    );
                    currentWrapper.className = [...sidebarClasses, ...pageClasses].join(' ');
                }

                // Replace page-specific modals outside the wrapper
                try {
                    // Remove existing page modals (keep global alert)
                    document.querySelectorAll('body > .modal').forEach(m => {
                        if (m.id !== 'customAlert') m.remove();
                    });
                    // Remove any transient shared UI overlays to prevent cross-page handler conflicts
                    const staleDatepicker = document.getElementById('shared-datepicker');
                    if (staleDatepicker) staleDatepicker.remove();
                    const staleSelect = document.getElementById('shared-select-options');
                    if (staleSelect) staleSelect.remove();
                    // Append new modals from fetched doc that are not inside wrapper
                    doc.querySelectorAll('body > .modal').forEach(m => {
                        if (m.id !== 'customAlert') {
                            document.body.appendChild(document.importNode(m, true));
                        }
                    });
                    
                    // Reinitialize custom components after modals are replaced
                    if (typeof window.reinitializeCustomDropdowns === 'function') {
                        window.reinitializeCustomDropdowns();
                    }
                    if (typeof window.reinitializeCustomDatepickers === 'function') {
                        window.reinitializeCustomDatepickers();
                    }
                } catch (_) {}

                // Execute all scripts discovered in fetched document (external loaded once)
                await executeScriptsFrom(doc, url);

                // Sidebar is preserved during PJAX, no need to reapply state

                // Ensure FontAwesome is loaded after PJAX navigation
                setTimeout(() => {
                    const testIcon = document.createElement('i');
                    testIcon.className = 'fas fa-home';
                    testIcon.style.display = 'none';
                    document.body.appendChild(testIcon);
                    
                    const computedStyle = window.getComputedStyle(testIcon, ':before');
                    const hasContent = computedStyle.content !== 'none' && computedStyle.content !== '';
                    
                    if (!hasContent) {
                        // Force reload FontAwesome if not working
                        const existingFA = document.querySelector('link[href*="font-awesome"]');
                        if (existingFA) {
                            const newFA = document.createElement('link');
                            newFA.rel = 'stylesheet';
                            newFA.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css';
                            newFA.crossOrigin = 'anonymous';
                            document.head.appendChild(newFA);
                        } else {
                            // Add FontAwesome if it doesn't exist
                            const faLink = document.createElement('link');
                            faLink.rel = 'stylesheet';
                            faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css';
                            faLink.crossOrigin = 'anonymous';
                            document.head.appendChild(faLink);
                        }
                        
                        // Try fallback after short delay
                        setTimeout(() => {
                            const testIcon2 = document.createElement('i');
                            testIcon2.className = 'fas fa-home';
                            testIcon2.style.display = 'none';
                            document.body.appendChild(testIcon2);
                            
                            const computedStyle2 = window.getComputedStyle(testIcon2, ':before');
                            const hasContent2 = computedStyle2.content !== 'none' && computedStyle2.content !== '';
                            
                            if (!hasContent2) {
                                const fallbackFA = document.createElement('link');
                                fallbackFA.rel = 'stylesheet';
                                fallbackFA.href = 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css';
                                fallbackFA.crossOrigin = 'anonymous';
                                document.head.appendChild(fallbackFA);
                            }
                            
                            document.body.removeChild(testIcon2);
                        }, 500);
                    }
                    
                    document.body.removeChild(testIcon);
                }, 200);

                // Re-init global UI and mark active link
                if (typeof window.initGlobalUI === 'function') {
                    window.initGlobalUI();
                }
                
                // Reinitialize custom components after PJAX navigation
                requestAnimationFrame(() => {
                    if (typeof window.reinitializeCustomDropdowns === 'function') {
                        window.reinitializeCustomDropdowns();
                    }
                    if (typeof window.reinitializeCustomDatepickers === 'function') {
                        window.reinitializeCustomDatepickers();
                    }
                    
                    // Reinitialize admin notifications AFTER DOM is ready
                    if (typeof window.initAdminNotifications === 'function') {
                        window.initAdminNotifications();
                    }
                    
                    // Reinitialize deadline countdown system
                    if (typeof window.initDeadlineCountdown === 'function') {
                        window.initDeadlineCountdown();
                    }
                });
                // Call page-specific initializer based on target URL (run defensively twice for timing)
                try {
                    const page = new URL(url, window.location.href).pathname.split('/').pop();
                    const pageInitMap = {
                        'dashboard.php': 'initDashboard',
                        'smart_warehousing.php': 'initSmartWarehousing',
                        'procurement_sourcing.php': 'initProcurement',
                        'asset_lifecycle_maintenance.php': 'initALMS',
                        'document_tracking_records.php': 'initDTRS',
                        // References to deleted modules removed
                    };
                    const initName = pageInitMap[page];
                    if (initName && typeof window[initName] === 'function') {
                        window[initName]();
                        requestAnimationFrame(() => { try { window[initName](); } catch (_) {} });
                        setTimeout(() => { try { window[initName](); } catch (_) {} }, 0);
                    }
                    
                    // Final custom component initialization after page-specific initializers
                    requestAnimationFrame(() => {
                        if (typeof window.reinitializeCustomDropdowns === 'function') {
                            window.reinitializeCustomDropdowns();
                        }
                        if (typeof window.reinitializeCustomDatepickers === 'function') {
                            window.reinitializeCustomDatepickers();
                        }
                    });
                } catch (_) {}
                updateActiveSidebarLink(url);

                // Update title
                if (doc.title) { document.title = doc.title; }

                if (addToHistory) {
                    history.pushState({ url }, '', url);
                }

                window.scrollTo({ top: 0 });
            } else {
                // Fallback to full navigation
                window.location.href = url;
            }
        } catch (e) {
            window.location.href = url;
        } finally {
            document.body.classList.remove('pjax-loading');
            
            // Remove loading state and ensure content is visible
            document.documentElement.classList.remove('loading');
            document.documentElement.classList.add('loaded');
            
            // Brief delay to ensure styles are applied
            setTimeout(() => {
                document.documentElement.classList.remove('preload');
            }, 50);
        }
    }

    // Apply persisted sidebar state on initial load
    applySidebarState(sidebar, mainContentWrapper, barsIcon, xmarkIcon);

    // Sidebar Toggle with delegated handler (so it works after PJAX)
    function syncSidebarStateClasses() {
        if (!(sidebar && mainContentWrapper)) return;
        if (sidebar.classList.contains('collapsed')) {
            document.body.classList.remove('sidebar-active');
            mainContentWrapper.classList.add('expanded');
        } else {
            document.body.classList.add('sidebar-active');
            mainContentWrapper.classList.remove('expanded');
        }
    }
    syncSidebarStateClasses();

    if (!window.__hamburgerDelegated) {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#hamburger');
            if (!btn) return;
            const wrapper = document.getElementById('mainContentWrapper');
            const barsIcon = document.getElementById('barsIcon');
            const xmarkIcon = document.getElementById('xmarkIcon');
            if (!(sidebar && wrapper)) return;
            
            // Clean up any existing state classes first
            sidebar.classList.remove('collapsed', 'initial-collapsed');
            wrapper.classList.remove('expanded', 'initial-expanded');
            
            // Mark that user has toggled in this session
            try {
                sessionStorage.setItem('sidebarUserToggled', 'true');
            } catch(_) {}
            
            // Toggle to opposite state (use sessionStorage instead of localStorage)
            const wasCollapsed = sessionStorage.getItem('sidebarCollapsed') === 'true';
            const nowCollapsed = !wasCollapsed;
            
            if (nowCollapsed) {
                sidebar.classList.add('collapsed');
                wrapper.classList.add('expanded');
                document.body.classList.remove('sidebar-active');
            } else {
                document.body.classList.add('sidebar-active');
            }
            
            // Toggle icons
            if (nowCollapsed) {
                barsIcon.classList.add('hidden');
                xmarkIcon.classList.remove('hidden');
            } else {
                barsIcon.classList.remove('hidden');
                xmarkIcon.classList.add('hidden');
            }
            
            // Save state to sessionStorage only (not across logins)
            try { 
                sessionStorage.setItem('sidebarCollapsed', nowCollapsed);
            } catch(_) {}
        });
        window.__hamburgerDelegated = true;
    }

    // Active Sidebar Link Highlighting + PJAX intercept
    if (sidebarLinks.length > 0) {
        updateActiveSidebarLink(window.location.href);
        sidebarLinks.forEach(link => {
            if (link.getAttribute('href') === '#') return;
            link.addEventListener('click', (e) => {
                if (link.textContent && link.textContent.trim() === 'Logout') return;
                e.preventDefault();
                pjaxNavigate(link.href, true);
            });
        });
        window.addEventListener('popstate', (e) => {
            const targetUrl = (e.state && e.state.url) ? e.state.url : window.location.href;
            pjaxNavigate(targetUrl, false);
        });
    }
});
