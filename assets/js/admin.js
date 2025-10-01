// assets/js/admin.js - Clean, Fixed Version
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle functionality
    initializeMobileSidebar();
    
    // Form validation
    initializeFormValidation();
    
    // Input validation feedback
    initializeInputFeedback();
});

// Mobile sidebar functionality
function initializeMobileSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.getElementById('sidebarMenu');
    
    if (sidebarToggle && sidebar) {
        // Toggle sidebar
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
        
        // Close sidebar when clicking on sidebar links (mobile)
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    closeSidebar();
                }
            });
        });
    }
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebarMenu');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        
        if (window.innerWidth < 768 && 
            sidebar && 
            sidebar.classList.contains('active') && 
            !sidebar.contains(e.target) && 
            e.target !== sidebarToggle && 
            !sidebarToggle.contains(e.target)) {
            closeSidebar();
        }
    });
    
    // Close sidebar on window resize to desktop
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebarMenu');
        if (window.innerWidth >= 768 && sidebar) {
            sidebar.classList.remove('active');
            removeOverlay();
        }
    });
}

// Toggle sidebar function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    if (sidebar) {
        const isActive = sidebar.classList.contains('active');
        if (isActive) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }
}

// Open sidebar function
function openSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    if (sidebar) {
        sidebar.classList.add('active');
        createOverlay();
    }
}

// Close sidebar function
function closeSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    if (sidebar) {
        sidebar.classList.remove('active');
        removeOverlay();
    }
}

// Create overlay
function createOverlay() {
    // Remove any existing overlays
    removeOverlay();
    
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    overlay.addEventListener('click', function() {
        closeSidebar();
    });
    
    // Trigger reflow to enable transition
    overlay.offsetHeight;
    overlay.classList.add('active');
}

// Remove overlay
function removeOverlay() {
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields.', 'warning');
            }
        });
    });
}

// Input validation feedback
function initializeInputFeedback() {
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        // Validation on blur
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Remove invalid class on input
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });
    });
}

// Show alert function
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.custom-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show custom-alert`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the beginning of the body or main content
    const container = document.querySelector('main') || document.body;
    if (container.firstChild) {
        container.insertBefore(alertDiv, container.firstChild);
    } else {
        container.appendChild(alertDiv);
    }
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}