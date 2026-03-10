// Tosrifa Industries Ltd Products Gallery - JavaScript

document.addEventListener('DOMContentLoaded', function () {
  // Auto-dismiss alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert')
  alerts.forEach((alert) => {
    if (!alert.classList.contains('alert-permanent')) {
      setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert)
        bsAlert.close()
      }, 5000)
    }
  })

  // Thumbnail click handler for product gallery
  const thumbnails = document.querySelectorAll('.thumbnail-image')
  thumbnails.forEach((thumbnail, index) => {
    thumbnail.addEventListener('click', function () {
      // Remove active class from all thumbnails
      thumbnails.forEach((t) => t.classList.remove('active'))

      // Add active class to clicked thumbnail
      this.classList.add('active')
    })
  })

  // Image preview for file uploads
  const imageInputs = document.querySelectorAll(
    'input[type="file"][accept*="image"]',
  )
  imageInputs.forEach((input) => {
    input.addEventListener('change', function (e) {
      const file = e.target.files[0]
      if (file) {
        const reader = new FileReader()
        reader.onload = function (e) {
          // Create or update preview
          let preview = input.parentElement.querySelector('.image-preview')
          if (!preview) {
            preview = document.createElement('img')
            preview.className = 'image-preview'
            input.parentElement.appendChild(preview)
          }
          preview.src = e.target.result
        }
        reader.readAsDataURL(file)
      }
    })
  })

  // Form validation enhancement
  const forms = document.querySelectorAll('form[method="POST"]')
  forms.forEach((form) => {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault()
        e.stopPropagation()
      }
      form.classList.add('was-validated')
    })
  })

  // Auto-carousel for product images (optional - can be enabled)
  const carousel = document.querySelector('#productCarousel')
  if (carousel) {
    // Uncomment to enable auto-play
    // const bsCarousel = new bootstrap.Carousel(carousel, {
    //     interval: 3000,
    //     ride: 'carousel'
    // });
  }

  // Smooth scroll to top
  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth',
    })
  }

  // Add scroll-to-top button if page is long
  if (document.body.scrollHeight > window.innerHeight * 2) {
    const scrollBtn = document.createElement('button')
    scrollBtn.innerHTML = '<i class="bi bi-arrow-up"></i>'
    scrollBtn.className = 'btn btn-primary position-fixed bottom-0 end-0 m-4'
    scrollBtn.style.display = 'none'
    scrollBtn.style.zIndex = '1000'
    scrollBtn.onclick = scrollToTop
    document.body.appendChild(scrollBtn)

    window.addEventListener('scroll', () => {
      if (window.pageYOffset > 300) {
        scrollBtn.style.display = 'block'
      } else {
        scrollBtn.style.display = 'none'
      }
    })
  }

  // Confirm delete actions
  const deleteLinks = document.querySelectorAll('a[href*="delete"]')
  deleteLinks.forEach((link) => {
    if (!link.hasAttribute('onclick')) {
      link.addEventListener('click', function (e) {
        if (!confirm('Are you sure you want to delete this item?')) {
          e.preventDefault()
        }
      })
    }
  })

  // Dynamic search/filter functionality (for dashboard)
  const searchInput = document.querySelector('#productSearch')
  if (searchInput) {
    searchInput.addEventListener('keyup', function () {
      const searchTerm = this.value.toLowerCase()
      const rows = document.querySelectorAll('tbody tr')

      rows.forEach((row) => {
        const text = row.textContent.toLowerCase()
        row.style.display = text.includes(searchTerm) ? '' : 'none'
      })
    })
  }

  // Price formatting
  const priceInputs = document.querySelectorAll(
    'input[type="number"][name="price"]',
  )
  priceInputs.forEach((input) => {
    input.addEventListener('blur', function () {
      if (this.value !== '') {
        this.value = parseFloat(this.value).toFixed(2)
      }
    })
  })

  // Character counter for textareas
  const textareas = document.querySelectorAll('textarea')
  textareas.forEach((textarea) => {
    const maxLength = textarea.getAttribute('maxlength')
    if (maxLength) {
      const counter = document.createElement('div')
      counter.className = 'form-text text-end'
      counter.innerHTML = `<span class="current">0</span> / ${maxLength}`
      textarea.parentElement.appendChild(counter)

      textarea.addEventListener('input', function () {
        counter.querySelector('.current').textContent = this.value.length
        if (this.value.length > maxLength * 0.9) {
          counter.classList.add('text-warning')
        } else {
          counter.classList.remove('text-warning')
        }
      })
    }
  })

  // Loading state for forms
  forms.forEach((form) => {
    form.addEventListener('submit', function () {
      const submitBtn = form.querySelector('button[type="submit"]')
      if (submitBtn && form.checkValidity()) {
        submitBtn.disabled = true
        const originalText = submitBtn.innerHTML
        submitBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'

        // Re-enable after 10 seconds as fallback
        setTimeout(() => {
          submitBtn.disabled = false
          submitBtn.innerHTML = originalText
        }, 10000)
      }
    })
  })

  // Lazy loading for images
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target
          if (img.dataset.src) {
            img.src = img.dataset.src
            img.classList.remove('lazy')
            imageObserver.unobserve(img)
          }
        }
      })
    })

    const lazyImages = document.querySelectorAll('img.lazy')
    lazyImages.forEach((img) => imageObserver.observe(img))
  }

  console.log('Tosrifa Industries Ltd Products Gallery loaded successfully!')
})

// Utility function to format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}

// Utility function to slugify text
function slugify(text) {
  return text
    .toString()
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-')
}
