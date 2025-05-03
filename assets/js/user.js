// User Dashboard JavaScript - Improved performance

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM Content Loaded - Initializing dashboard")

  // Check if Bootstrap is loaded
  let bootstrap
  try {
    bootstrap = window.bootstrap // Assign bootstrap if it exists
  } catch (e) {
    console.warn("Bootstrap is not loaded.")
  }

  // Initialize tooltips if Bootstrap is loaded
  if (typeof bootstrap !== "undefined" && bootstrap && bootstrap.Tooltip) {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
      new bootstrap.Tooltip(tooltipTriggerEl)
    })
  }

  // Initialize lazy loading for images
  initLazyLoading()

  // Remove loading overlay when page is fully loaded
  window.addEventListener("load", () => {
    const loadingOverlay = document.getElementById("loadingOverlay")
    if (loadingOverlay) {
      loadingOverlay.style.opacity = "0"
      setTimeout(() => {
        loadingOverlay.style.visibility = "hidden"
        loadingOverlay.style.display = "none"
      }, 300)
    }
    document.body.classList.add("loaded")
  })

  // Toggle sidebar functionality
  const sidebarToggleBtn = document.getElementById("sidebarToggleBtn")
  console.log("Sidebar toggle button:", sidebarToggleBtn)

  if (sidebarToggleBtn) {
    sidebarToggleBtn.addEventListener("click", toggleSidebar)
  }

  // Cancel booking confirmation
  const cancelButtons = document.querySelectorAll(".btn-cancel-booking")
  if (cancelButtons.length > 0) {
    cancelButtons.forEach((button) => {
      button.addEventListener("click", (event) => {
        if (!confirm("Are you sure you want to cancel this booking? This action cannot be undone.")) {
          event.preventDefault()
        }
      })
    })
  }

  // Form validation for required fields
  const forms = document.querySelectorAll(".needs-validation")
  if (forms.length > 0) {
    Array.from(forms).forEach((form) => {
      form.addEventListener(
        "submit",
        (event) => {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
          form.classList.add("was-validated")
        },
        false,
      )
    })
  }

  // Profile image preview
  const profileImageInput = document.getElementById("profile-image-upload")
  if (profileImageInput) {
    profileImageInput.addEventListener("change", function () {
      const file = this.files[0]
      const preview = document.getElementById("profile-image-preview")

      if (preview && file) {
        // Prevent multiple rapid changes
        if (this._timeout) clearTimeout(this._timeout)

        this._timeout = setTimeout(() => {
          const reader = new FileReader()

          reader.onload = (e) => {
            // Apply the new image in one go to prevent flickering
            preview.style.opacity = "0"
            setTimeout(() => {
              preview.src = e.target.result
              preview.style.display = "block"
              preview.style.opacity = "1"
            }, 50)
          }

          reader.readAsDataURL(file)
        }, 100)
      }
    })
  }

  // Star rating system for reviews
  const ratingInputs = document.querySelectorAll(".rating-input")
  if (ratingInputs.length > 0) {
    ratingInputs.forEach((input) => {
      input.addEventListener("change", function () {
        const stars = this.parentElement.querySelectorAll(".rating-star")
        const rating = Number.parseInt(this.value)

        stars.forEach((star, index) => {
          if (index < rating) {
            star.classList.add("active")
          } else {
            star.classList.remove("active")
          }
        })
      })
    })
  }

  // Password strength meter
  const passwordInput = document.getElementById("new-password")
  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      const password = this.value
      const meter = document.getElementById("password-strength-meter")

      if (meter) {
        // Simple password strength calculation
        let strength = 0

        if (password.length >= 8) strength += 1
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1
        if (password.match(/\d/)) strength += 1
        if (password.match(/[^a-zA-Z\d]/)) strength += 1

        // Update meter
        meter.value = strength

        // Update text description
        const strengthText = document.getElementById("password-strength-text")
        if (strengthText) {
          const descriptions = ["Weak", "Fair", "Good", "Strong", "Very Strong"]
          strengthText.textContent = descriptions[strength]

          // Update color
          const colors = ["#dc3545", "#ffc107", "#fd7e14", "#20c997", "#198754"]
          meter.style.setProperty("--strength-color", colors[strength])
        }
      }
    })
  }

  // Handle wishlist toggle
  const wishlistToggles = document.querySelectorAll(".wishlist-toggle")
  if (wishlistToggles.length > 0) {
    wishlistToggles.forEach((toggle) => {
      toggle.addEventListener("click", function (e) {
        e.preventDefault()
        const tourId = this.dataset.tourId
        const icon = this.querySelector("i")

        // Send AJAX request to toggle wishlist status
        fetch("wishlist-toggle.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: "tour_id=" + tourId,
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error("Network response was not ok")
            }
            return response.json()
          })
          .then((data) => {
            if (data.success) {
              // Toggle icon class
              if (data.added) {
                icon.classList.remove("far")
                icon.classList.add("fas")
                icon.classList.add("text-danger")
              } else {
                icon.classList.remove("fas")
                icon.classList.remove("text-danger")
                icon.classList.add("far")
              }

              // Show message
              showAlert("success", data.message)
            } else {
              showAlert("danger", data.message || "An error occurred")
            }
          })
          .catch((error) => {
            console.error("Error:", error)
            showAlert("danger", "An unexpected error occurred")
          })
      })
    })
  }

  // Load notifications asynchronously
  loadNotifications()

  // Function to initialize lazy loading for images
  function initLazyLoading() {
    // Disable lazy loading to prevent flickering
    const lazyImages = document.querySelectorAll("img[data-src]")
    lazyImages.forEach((img) => {
      img.src = img.dataset.src
      img.removeAttribute("data-src")
    })
  }
})

// Function to toggle sidebar
function toggleSidebar() {
  console.log("Sidebar toggle called")

  // Toggle body class first
  document.body.classList.toggle("sidebar-collapsed")

  // Toggle sidebar
  const sidebar = document.getElementById("sidebar")
  if (sidebar) {
    sidebar.classList.toggle("collapsed")
    console.log("Sidebar classes:", sidebar.className)
  }

  // Toggle content
  const content = document.querySelector(".main-content")
  if (content) {
    content.classList.toggle("expanded")
  }

  // Save state to cookie
  const isCollapsed = document.body.classList.contains("sidebar-collapsed")
  document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000` // 1 year

  console.log("Sidebar toggled:", isCollapsed)
}

// Function to load notifications asynchronously
function loadNotifications() {
  if (!document.getElementById("notificationPlaceholder")) return

  fetch("get-notifications.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok")
      }
      return response.json()
    })
    .then((data) => {
      const container = document.getElementById("notificationsDropdown").nextElementSibling
      if (container) {
        // Clear loading placeholder
        container.innerHTML = '<li class="dropdown-header">Notifications</li>'

        if (data.length === 0) {
          container.innerHTML += '<li><span class="dropdown-item text-center">No notifications</span></li>'
        } else {
          data.forEach((notification) => {
            const isRead = notification.is_read ? "" : "fw-bold"
            container.innerHTML +=
              '<li><a class="dropdown-item ' +
              isRead +
              '" href="notifications.php?id=' +
              notification.notification_id +
              '">' +
              notification.title +
              "</a></li>"
          })

          container.innerHTML += '<li><hr class="dropdown-divider"></li>'
          container.innerHTML += '<li><a class="dropdown-item text-center" href="notifications.php">View All</a></li>'
        }
      }
    })
    .catch((error) => {
      console.error("Error loading notifications:", error)
      const container = document.getElementById("notificationsDropdown").nextElementSibling
      if (container) {
        container.innerHTML = '<li class="dropdown-header">Notifications</li>'
        container.innerHTML += '<li><span class="dropdown-item text-center">Unable to load notifications</span></li>'
      }
    })
}

// Function to handle AJAX form submissions
function handleAjaxForm(formElement, successCallback, errorCallback) {
  if (!formElement) return

  formElement.addEventListener("submit", (e) => {
    e.preventDefault()

    const submitBtn = formElement.querySelector('[type="submit"]')
    const originalText = submitBtn ? submitBtn.innerHTML : ""

    if (submitBtn) {
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...'
      submitBtn.disabled = true
    }

    const formData = new FormData(formElement)

    fetch(formElement.action, {
      method: formElement.method,
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok")
        }
        return response.json()
      })
      .then((data) => {
        if (submitBtn) {
          submitBtn.innerHTML = originalText
          submitBtn.disabled = false
        }

        if (typeof successCallback === "function") {
          successCallback(data)
        } else if (data.success) {
          showAlert("success", data.message)
          if (data.redirect) {
            setTimeout(() => {
              window.location.href = data.redirect
            }, 1500)
          }
        } else {
          showAlert("danger", data.message || "An error occurred")
        }
      })
      .catch((error) => {
        console.error("Error:", error)

        if (submitBtn) {
          submitBtn.innerHTML = originalText
          submitBtn.disabled = false
        }

        if (typeof errorCallback === "function") {
          errorCallback(error)
        } else {
          showAlert("danger", "An unexpected error occurred")
        }
      })
  })
}

// Initialize AJAX forms
document.addEventListener("DOMContentLoaded", () => {
  const ajaxForms = document.querySelectorAll(".ajax-form")
  if (ajaxForms.length > 0) {
    ajaxForms.forEach((form) => {
      handleAjaxForm(form)
    })
  }
})

// Function to display alerts
window.showAlert = (type, message) => {
  const alertContainer = document.getElementById("alert-container")
  if (!alertContainer) {
    const container = document.createElement("div")
    container.id = "alert-container"
    container.className = "position-fixed top-0 end-0 p-3"
    container.style.zIndex = "1060"
    document.body.appendChild(container)
  }

  const alertId = "alert-" + Date.now()
  const alertHtml =
    '<div id="' +
    alertId +
    '" class="alert alert-' +
    type +
    ' alert-dismissible fade show" role="alert">' +
    message +
    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
    "</div>"

  document.getElementById("alert-container").insertAdjacentHTML("beforeend", alertHtml)

  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    const alertElement = document.getElementById(alertId)
    if (alertElement) {
      if (typeof bootstrap !== "undefined" && bootstrap && bootstrap.Alert) {
        const bsAlert = new bootstrap.Alert(alertElement)
        bsAlert.close()
      } else {
        alertElement.remove()
      }
    }
  }, 5000)
}

// Make toggleSidebar globally available
window.toggleSidebar = toggleSidebar

// Add a direct toggle function for debugging
window.directToggleSidebar = () => {
  const sidebar = document.getElementById("sidebar")
  if (sidebar) {
    sidebar.classList.toggle("collapsed")
    console.log("Direct sidebar toggle, classes:", sidebar.className)
  }
}
