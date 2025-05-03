</div>
</div>

<!-- Alert container for notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

<!-- Load JavaScript at the end of the body for better performance -->
    <script>
// Immediately start fading out the loading overlay
document.addEventListener('DOMContentLoaded', function() {
    // Force remove loading overlay
    var loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        // Immediately hide the overlay
        loadingOverlay.style.opacity = '0';
        loadingOverlay.style.visibility = 'hidden';
        
        // After transition, remove from DOM
        setTimeout(function() {
            loadingOverlay.style.display = 'none';
        }, 300);
    }
    
    // Initialize tooltips if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Load notifications asynchronously
    loadNotifications();
    
    // Fix for links not working
    document.querySelectorAll('a:not([data-bs-toggle])').forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Don't show loading for same-page links or external links
            if (this.getAttribute('href') && (this.getAttribute('href').startsWith('#') || 
                this.getAttribute('href').startsWith('http') || 
                this.getAttribute('target') === '_blank')) {
                return;
            }
        });
    });
    
    // Fix for image loading errors
    document.querySelectorAll('img').forEach(function(img) {
        img.addEventListener('error', function() {
            if (!this.src.includes('default-user.jpg') && !this.src.includes('placeholder.jpg')) {
                if (this.src.includes('users')) {
                    this.src = '../assets/images/users/default-user.jpg';
                } else {
                    this.src = '../assets/images/placeholder.jpg';
                }
            }
        });
    });
});

// Remove any window.onload handlers that might be interfering
window.onload = null;
</script>
    
    // Load notifications asynchronously
    function loadNotifications() {
        if (!document.getElementById('notificationPlaceholder')) return;
        
        fetch('get-notifications.php')
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(data) {
                const container = document.getElementById('notificationsDropdown').nextElementSibling;
                if (container) {
                    // Clear loading placeholder
                    container.innerHTML = '<li><h6 class="dropdown-header">Notifications</h6></li>';
                    
                    if (data.length === 0) {
                        container.innerHTML += '<li><span class="dropdown-item text-center">No notifications</span></li>';
                    } else {
                        data.forEach(function(notification) {
                            const isRead = notification.is_read ? '' : 'fw-bold';
                            container.innerHTML += `<li><a class="dropdown-item ${isRead}" href="notifications.php?id=${notification.notification_id}">${notification.title}</a></li>`;
                        });
                        container.innerHTML += '<li><hr class="dropdown-divider"></li>';
                        container.innerHTML += '<li><a class="dropdown-item text-center" href="notifications.php">View All</a></li>';
                    }
                }
            })
            .catch(function(error) {
                console.error('Error loading notifications:', error);
                const container = document.getElementById('notificationsDropdown').nextElementSibling;
                if (container) {
                    container.innerHTML = '<li><h6 class="dropdown-header">Notifications</h6></li>';
                    container.innerHTML += '<li><span class="dropdown-item text-center">Unable to load notifications</span></li>';
                }
            });
    }
    
    // Function to show alerts
    window.showAlert = function(type, message) {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) {
            const container = document.createElement('div');
            container.id = 'alert-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1060';
            document.body.appendChild(container);
        }
        
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        document.getElementById('alert-container').insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    const bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                } else {
                    alertElement.remove();
                }
            }
        }, 5000);
    };
    </script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/user.js"></script>
    
    <!-- Prevent JavaScript from showing in the page -->
    <script>
        // Immediately remove any script tags that might be showing in the page content
        document.addEventListener('DOMContentLoaded', function() {
            const scriptTags = document.querySelectorAll('pre, code');
            scriptTags.forEach(function(tag) {
                if (tag.textContent.includes('function loadNotifications()') || 
                    tag.textContent.includes('window.showAlert')) {
                    tag.parentNode.removeChild(tag);
                }
            });
        });
    </script>
</body>
</html>
</div><!-- End of main-content -->
  </div><!-- End of dashboard-container -->

  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom JavaScript -->
  <script src="../assets/js/user.js"></script>
</body>
</html>
</div><!-- End of main content -->
</div><!-- End of dashboard container -->

<!-- Alert container for notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="../assets/js/user.js"></script>

<!-- Prevent JavaScript from showing in the page -->
<script>
    // Immediately remove any script tags that might be showing in the page content
    document.addEventListener('DOMContentLoaded', function() {
        const scriptTags = document.querySelectorAll('pre, code');
        scriptTags.forEach(function(tag) {
            if (tag.textContent.includes('function loadNotifications()') || 
                tag.textContent.includes('window.showAlert')) {
                tag.parentNode.removeChild(tag);
            }
        });
    });
</script>
</body>
</html>
</div> <!-- End of main-content -->
</div> <!-- End of dashboard-container -->

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="../assets/js/user.js"></script>

<!-- Alert container for dynamic alerts -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

</body>
</html>
</div> <!-- End of main-content -->
</div> <!-- End of dashboard-container -->

<!-- Alert container for notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1060;"></div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="../assets/js/user.js"></script>

<!-- Prevent JavaScript from showing in the page -->
<script>
    // Immediately remove any script tags that might be showing in the page content
    document.addEventListener('DOMContentLoaded', function() {
        const scriptTags = document.querySelectorAll('pre, code');
        scriptTags.forEach(function(tag) {
            if (tag.textContent.includes('function loadNotifications()') || 
                tag.textContent.includes('window.showAlert')) {
                tag.parentNode.removeChild(tag);
            }
        });
    });
</script>
</body>
</html>
