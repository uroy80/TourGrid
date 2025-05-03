import { Chart } from "@/components/ui/chart"
// Admin Dashboard JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Initialize popovers
  var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  var popoverList = popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))

  // Check for chart elements
  const salesChartCanvas = document.getElementById("salesChart")
  if (salesChartCanvas) {
    renderSalesChart(salesChartCanvas)
  }

  const bookingsChartCanvas = document.getElementById("bookingsChart")
  if (bookingsChartCanvas) {
    renderBookingsChart(bookingsChartCanvas)
  }

  // Delete confirmation
  const deleteButtons = document.querySelectorAll(".btn-delete")
  if (deleteButtons.length > 0) {
    deleteButtons.forEach((button) => {
      button.addEventListener("click", (event) => {
        if (!confirm("Are you sure you want to delete this item? This action cannot be undone.")) {
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

  // Image preview for file uploads
  const imageInputs = document.querySelectorAll(".image-upload")
  if (imageInputs.length > 0) {
    imageInputs.forEach((input) => {
      input.addEventListener("change", function () {
        const file = this.files[0]
        const preview = document.getElementById(this.getAttribute("data-preview"))

        if (preview && file) {
          const reader = new FileReader()

          reader.onload = (e) => {
            preview.src = e.target.result
            preview.style.display = "block"
          }

          reader.readAsDataURL(file)
        }
      })
    })
  }
})

// Render sales chart
function renderSalesChart(canvas) {
  // Sample data - in a real application, this would come from the server
  const salesData = {
    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    datasets: [
      {
        label: "Sales ($)",
        backgroundColor: "rgba(78, 115, 223, 0.05)",
        borderColor: "rgba(78, 115, 223, 1)",
        pointBackgroundColor: "rgba(78, 115, 223, 1)",
        pointBorderColor: "#fff",
        pointHoverBackgroundColor: "#fff",
        pointHoverBorderColor: "rgba(78, 115, 223, 1)",
        data: [10000, 15000, 12000, 18000, 22000, 25000, 30000, 28000, 26000, 32000, 35000, 40000],
      },
    ],
  }

  new Chart(canvas, {
    type: "line",
    data: salesData,
    options: {
      maintainAspectRatio: false,
      scales: {
        x: {
          grid: {
            display: false,
          },
        },
        y: {
          ticks: {
            callback: (value) => "$" + value,
          },
        },
      },
      plugins: {
        legend: {
          display: false,
        },
      },
    },
  })
}

// Render bookings chart
function renderBookingsChart(canvas) {
  // Sample data - in a real application, this would come from the server
  const bookingsData = {
    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    datasets: [
      {
        label: "Bookings",
        backgroundColor: "rgba(28, 200, 138, 0.2)",
        borderColor: "rgba(28, 200, 138, 1)",
        pointBackgroundColor: "rgba(28, 200, 138, 1)",
        pointBorderColor: "#fff",
        pointHoverBackgroundColor: "#fff",
        pointHoverBorderColor: "rgba(28, 200, 138, 1)",
        data: [50, 65, 70, 80, 95, 110, 120, 115, 100, 85, 90, 105],
      },
    ],
  }

  new Chart(canvas, {
    type: "bar",
    data: bookingsData,
    options: {
      maintainAspectRatio: false,
      scales: {
        x: {
          grid: {
            display: false,
          },
        },
        y: {
          ticks: {
            beginAtZero: true,
          },
        },
      },
      plugins: {
        legend: {
          display: false,
        },
      },
    },
  })
}
