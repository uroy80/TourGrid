// Main Website JavaScript

// Wait for the DOM to be fully loaded
document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Tour date picker - ensure future dates only
  const tourDateInputs = document.querySelectorAll(".tour-date-picker")
  if (tourDateInputs.length > 0) {
    tourDateInputs.forEach((input) => {
      // Set min date to tomorrow
      const tomorrow = new Date()
      tomorrow.setDate(tomorrow.getDate() + 1)
      const tomorrowFormatted = tomorrow.toISOString().split("T")[0]
      input.setAttribute("min", tomorrowFormatted)
    })
  }

  // Number of people input - calculate total price
  const peopleInputs = document.querySelectorAll(".people-input")
  if (peopleInputs.length > 0) {
    peopleInputs.forEach((input) => {
      input.addEventListener("change", function () {
        calculateTotalPrice(this)
      })
    })
  }

  // Calculate total price based on number of people
  function calculateTotalPrice(input) {
    const pricePerPerson = Number.parseFloat(input.getAttribute("data-price"))
    const numberOfPeople = Number.parseInt(input.value)
    const totalPriceElement = document.getElementById("total-price")

    if (totalPriceElement && !isNaN(pricePerPerson) && !isNaN(numberOfPeople)) {
      const totalPrice = pricePerPerson * numberOfPeople
      totalPriceElement.textContent = totalPrice.toFixed(2)

      // Update hidden input for form submission
      const totalAmountInput = document.getElementById("total_amount")
      if (totalAmountInput) {
        totalAmountInput.value = totalPrice.toFixed(2)
      }
    }
  }

  // Search form validation
  const searchForm = document.querySelector(".search-section form")
  if (searchForm) {
    searchForm.addEventListener("submit", function (event) {
      let isValid = false
      const inputs = this.querySelectorAll("select")

      inputs.forEach((input) => {
        if (input.value !== "") {
          isValid = true
        }
      })

      if (!isValid) {
        event.preventDefault()
        alert("Please select at least one search criteria.")
      }
    })
  }

  // Newsletter form validation
  const newsletterForm = document.querySelector(".newsletter form")
  if (newsletterForm) {
    newsletterForm.addEventListener("submit", function (event) {
      event.preventDefault()
      const emailInput = this.querySelector('input[type="email"]')

      if (emailInput.value.trim() === "") {
        alert("Please enter your email address.")
        return
      }

      // Simple email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(emailInput.value)) {
        alert("Please enter a valid email address.")
        return
      }

      // Here you would typically send the form data to the server
      alert("Thank you for subscribing to our newsletter!")
      emailInput.value = ""
    })
  }
})
