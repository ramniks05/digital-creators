<!doctype html>
<html lang="en">

<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  $contactMapCss = <<<'CSS'
  <style>
    .dark-map-container iframe {
      filter: invert(90%) hue-rotate(180deg) brightness(85%) contrast(90%) grayscale(40%);
      transition: filter 0.5s ease;
    }
    .dark-map-container:hover iframe {
      filter: invert(90%) hue-rotate(180deg) brightness(95%) contrast(95%);
    }
  </style>
CSS;
  render_seo_head([
    'title' => 'Contact Us | Digital Creatorss',
    'description' => 'Get in touch with Digital Creatorss for custom software, website development, cloud hosting, and server management.',
    'path' => 'contact.php',
    'extra_head' => $contactMapCss,
  ]);
  ?>
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased overflow-x-hidden site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary overflow-x-hidden">

    <!-- Navbar Section -->
    <?php include 'components/navbar.php'; ?>

    <!-- Main Content -->
    <main class="w-full pt-32 pb-24">

      <!-- ── Contact Page Hero ── -->
      <section class="max-w-7xl mx-auto px-6 mb-16 relative">
        <div
          class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-primary/10 rounded-full blur-[140px] -z-10 pointer-events-none">
        </div>

        <div class="text-center max-w-3xl mx-auto pt-8">
          <span
            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-semibold uppercase tracking-wider mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-primary/70 animate-ping"></span>
            Let's Collaborate
          </span>
          <h1 class="page-hero-title mb-4">
            Start Your Next <br />
            <span class="text-primary">Digital Project</span>
          </h1>
          <p class="text-base sm:text-lg text-text-secondary leading-relaxed font-light max-w-xl mx-auto">
            Have an idea or project? Our engineering squad is ready to architect a custom web product suited to your
            business.
          </p>
        </div>
      </section>

      <!-- ── Contact Section Grid ── -->
      <section class="max-w-7xl mx-auto px-6 mb-24 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-stretch">

          <!-- Left side: Detail panels -->
          <div class="lg:col-span-5 flex flex-col gap-5">
            <h2 class="font-headings text-2xl sm:text-3xl font-bold text-text-primary mb-2">
              Connect Directly
            </h2>

            <!-- Phone Contact Card -->
            <a href="tel:<?php require_once __DIR__ . '/includes/content.php'; echo htmlspecialchars(phone_tel()); ?>"
              class="group flex items-start gap-4 p-5 rounded-2xl border border-slate-200 bg-white/80 hover:border-primary/30 hover:bg-white/80 transition-all duration-300">
              <div
                class="p-3 bg-primary/10 border border-primary/20 rounded-xl text-primary group-hover:scale-105 transition-transform duration-300">
                <i data-lucide="phone" class="w-5 h-5"></i>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Direct
                  Call</span>
                <span class="text-base font-semibold text-text-primary group-hover:text-primary transition-colors"><?php echo htmlspecialchars(phones_display()); ?></span>
              </div>
            </a>

            <!-- Email Contact Card -->
            <?php
            require_once __DIR__ . '/includes/content.php';
            $siteEmail = setting('email', 'info@digitalcreatorss.com');
            ?>
            <a href="mailto:<?php echo htmlspecialchars($siteEmail); ?>"
              class="group flex items-start gap-4 p-5 rounded-2xl border border-slate-200 bg-white/80 hover:border-primary/30 hover:bg-white/80 transition-all duration-300">
              <div
                class="p-3 bg-primary/10 border border-primary/20 rounded-xl text-primary group-hover:scale-105 transition-transform duration-300">
                <i data-lucide="mail" class="w-5 h-5"></i>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Email
                  Query</span>
                <span
                  class="text-base font-semibold text-text-primary group-hover:text-primary transition-colors"><?php echo htmlspecialchars($siteEmail); ?></span>
              </div>
            </a>

            <!-- Office Location Card -->
            <div class="flex items-start gap-4 p-5 rounded-2xl border border-slate-200 bg-white/80">
              <div class="p-3 bg-primary/10 border border-primary/20 rounded-xl text-primary">
                <i data-lucide="map-pin" class="w-5 h-5"></i>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Office
                  Location</span>
                <span class="text-base font-semibold text-text-primary">C-84, C Block, Sec-2, Noida, Uttar Pardesh</span>
              </div>
            </div>

            <!-- Interactive Office Map Card -->
            <div
              class="relative w-full h-[200px] rounded-2xl overflow-hidden border border-slate-200 bg-white/80 hover:border-primary/30 transition-all duration-300">
              <!-- Embed Google Map -->
              <iframe
                src="https://maps.google.com/maps?q=Digital%20Creatorss,%20C-84,%20C%20Block,%20Sec-2,%20Noida&amp;z=15&amp;output=embed"
                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin">
              </iframe>

              <!-- Transparent Clickable Overlay -->
              <a href="https://maps.google.com/?q=Digital+Creatorss+C-84+C+Block+Sec-2+Noida" target="_blank"
                class="absolute inset-0 z-10 bg-transparent flex items-end justify-start p-4 cursor-pointer group">
                <div
                  class="bg-white/90 backdrop-blur-sm border border-slate-200 px-3 py-1.5 rounded-lg text-text-primary text-[10px] font-semibold flex items-center gap-1.5 shadow-md group-hover:bg-primary group-hover:border-primary group-hover:text-white transition-all duration-300">
                  <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                  <span>Open in Google Maps</span>
                </div>
              </a>
            </div>
          </div>

          <!-- Right side: Modern Form Card -->
          <div class="lg:col-span-7">
            <div
              class="bg-white/80 border border-slate-200 hover:border-primary/20 transition-colors duration-300 shadow-2xl rounded-3xl p-5 sm:p-8 md:p-10 relative overflow-hidden min-h-0 md:min-h-[500px] flex flex-col justify-center">

              <!-- Form Container -->
              <form id="contact-form" class="flex flex-col gap-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                  <!-- Full Name -->
                  <div class="flex flex-col gap-1.5">
                    <label for="form-name" class="text-[10px] font-bold text-text-secondary uppercase tracking-wider">Full Name</label>
                    <input type="text" name="name" id="form-name" placeholder="Rahul Sharma"
                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-primary/50 focus:bg-white rounded-xl font-body text-sm text-text-primary placeholder:text-slate-500 focus:outline-none transition-all duration-300" />
                    <span id="error-name" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                      <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Full Name is required</span>
                    </span>
                  </div>

                  <!-- Email Address -->
                  <div class="flex flex-col gap-1.5">
                    <label for="form-email" class="text-[10px] font-bold text-text-secondary uppercase tracking-wider">Email Address</label>
                    <input type="email" name="email" id="form-email" placeholder="rahul@example.com"
                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-primary/50 focus:bg-white rounded-xl font-body text-sm text-text-primary placeholder:text-slate-500 focus:outline-none transition-all duration-300" />
                    <span id="error-email" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                      <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Email Address is required</span>
                    </span>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                  <!-- Phone Number -->
                  <div class="flex flex-col gap-1.5">
                    <label for="form-phone" class="text-[10px] font-bold text-text-secondary uppercase tracking-wider">Phone Number</label>
                    <input type="tel" name="phone" id="form-phone" placeholder="+91 9999086431"
                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-primary/50 focus:bg-white rounded-xl font-body text-sm text-text-primary placeholder:text-slate-500 focus:outline-none transition-all duration-300" />
                    <span id="error-phone" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                      <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Phone Number is required</span>
                    </span>
                  </div>

                  <!-- Service Needed -->
                  <div class="flex flex-col gap-1.5">
                    <label for="form-service" class="text-[10px] font-bold text-text-secondary uppercase tracking-wider">Service Needed</label>
                    <div class="relative">
                      <select name="service" id="form-service" aria-label="Select Service Needed"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-primary/50 focus:bg-white rounded-xl font-body text-sm text-text-primary focus:outline-none transition-all duration-300 appearance-none cursor-pointer pr-10">
                        <option value="" disabled selected class="bg-white text-slate-600">Select a service...
                        </option>
                        <?php
                          try {
                            foreach (get_services() as $svcOpt) {
                              $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $svcOpt['title']));
                              $slug = trim($slug, '-');
                              echo '<option value="' . htmlspecialchars($slug) . '" class="bg-white text-text-primary">' . htmlspecialchars($svcOpt['title']) . '</option>';
                            }
                          } catch (Throwable $e) {}
                        ?>
                      </select>
                      <div
                        class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-text-secondary flex items-center">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                      </div>
                    </div>
                    <span id="error-service" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                      <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Please select a service</span>
                    </span>
                  </div>
                </div>

                <!-- Budget Range Slider -->
                <div class="flex flex-col gap-2 mt-2">
                  <div class="flex justify-between items-center text-xs font-semibold text-text-secondary">
                    <span>Select Amount (10k — 10 Lakh)</span>
                    <span id="form-budget-display"
                      class="px-2.5 py-0.5 rounded-full bg-primary/15 border border-primary/20 text-primary font-bold">10K</span>
                  </div>
                  <div class="relative px-1 py-1">
                    <input type="range" id="form-budget-slider" aria-label="Project Budget Range Slider" min="10000" max="1000000" step="10000" value="10000"
                      class="popup-range" oninput="updateFormBudgetDisplay(this.value)" />
                  </div>
                </div>

                <!-- Project Details -->
                <div class="flex flex-col gap-1.5">
                  <label for="form-message" class="text-[10px] font-bold text-text-secondary uppercase tracking-wider">Project Details</label>
                  <textarea name="message" id="form-message" rows="4"
                    placeholder="Tell us about your project requirements..."
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-primary/50 focus:bg-white rounded-xl font-body text-sm text-text-primary placeholder:text-slate-500 focus:outline-none transition-all duration-300 resize-none"></textarea>
                  <span id="error-message" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                    <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Message details are required</span>
                  </span>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="form-submit-btn"
                  class="w-full mt-4 py-3.5 font-headings text-sm md:text-base font-bold text-white rounded-xl bg-gradient-to-r from-primary to-secondary hover:from-secondary hover:to-primary transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer shadow-[0_4px_20px_rgba(37,99,235,0.25)] hover:shadow-[0_4px_25px_rgba(37,99,235,0.45)] hover:scale-[1.01] transform active:scale-[0.99]">
                  <span>Send Message</span>
                  <i data-lucide="send" class="w-3.5 h-3.5"></i>
                </button>
              </form>

              <!-- Success Screen -->
              <div id="contact-success"
                class="flex-col items-center justify-center text-center py-12 px-6 hidden animate-fadeIn">
                <div
                  class="w-16 h-16 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-400 flex items-center justify-center mb-6 shadow-sm animate-pulse">
                  <i data-lucide="check" class="w-7 h-7"></i>
                </div>
                <h3 class="font-headings text-2xl font-bold mb-3 text-text-primary">
                  Message Sent!
                </h3>
                <p class="text-sm text-text-secondary leading-relaxed mb-8 max-w-[340px] font-light">
                  Thank you for reaching out. A Senior Engineer from our team will contact you shortly.
                </p>
                <button type="button" id="success-reset-btn"
                  class="inline-flex items-center justify-center gap-2 px-5 py-2.5 font-headings text-xs font-semibold rounded-xl bg-slate-50 border border-slate-200 text-text-primary hover:bg-slate-100 hover:border-slate-300 transition-all duration-300 cursor-pointer">
                  Send Another Message
                </button>
              </div>

            </div>
          </div>

        </div>
      </section>



    </main>

    <!-- Footer Section -->
    <?php include 'components/footer.php'; ?>
  </div>

  <!-- Premium Custom Cursor -->
  <div class="custom-cursor-dot hidden md:block"></div>
  <div class="custom-cursor-ring hidden md:block"></div>

  <!-- Scroll Progress Indicator -->
  <div id="scroll-progress"
    class="fixed bottom-8 right-8 z-50 w-12 h-12 cursor-pointer transition-all duration-300 hover:scale-110 opacity-0 pointer-events-none hover:shadow-[0_0_20px_rgba(37,99,235,0.3)] rounded-full">
    <div class="absolute inset-0 bg-slate-50 rounded-full flex items-center justify-center">
      <i data-lucide="arrow-up" class="w-5 h-5 text-primary"></i>
    </div>
    <svg class="absolute inset-0 w-full h-full transform -rotate-90 pointer-events-none" viewBox="0 0 100 100">
      <circle cx="50" cy="50" r="46" class="stroke-slate-200" stroke-width="8" fill="none" />
      <circle id="progress-ring" cx="50" cy="50" r="46"
        class="stroke-primary transition-[stroke-dashoffset] duration-75 ease-linear" stroke-width="8" fill="none"
        stroke-linecap="round" stroke-dasharray="289" stroke-dashoffset="289" />
    </svg>
  </div>

  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- GSAP, ScrollTrigger & Lenis CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
  <script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script>

  <!-- Interactive behaviors and animations script -->
  <script>
    window.DC_WHATSAPP = <?php echo json_encode(whatsapp_number()); ?>;
    // Initialize Lucide Icons
    lucide.createIcons();

    // Global function for form budget slider update
    window.updateFormBudgetDisplay = function (value) {
      const display = document.getElementById('form-budget-display');
      if (!display) return;

      let text = '';
      if (value < 100000) {
        text = (value / 1000) + 'K';
      } else {
        const lakhs = value / 100000;
        text = lakhs === Math.floor(lakhs) ? lakhs + ' Lakh' : lakhs.toFixed(1) + ' Lakh';
      }
      display.textContent = text;
    };

    // Register GSAP ScrollTrigger
    gsap.registerPlugin(ScrollTrigger);

    // Initialize Lenis Smooth Scroll
    const lenis = new Lenis({
      duration: 1.2,
      easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
      orientation: 'vertical',
      gestureOrientation: 'vertical',
      smoothWheel: true,
      smoothTouch: false,
    });

    lenis.on('scroll', ScrollTrigger.update);

    gsap.ticker.add((time) => {
      lenis.raf(time * 1000);
    });

    gsap.ticker.lagSmoothing(0);

    // Custom Cursor follower logic
    if (window.innerWidth >= 768) {
      const dot = document.querySelector('.custom-cursor-dot');
      const ring = document.querySelector('.custom-cursor-ring');

      if (dot && ring) {
        gsap.set([dot, ring], { xPercent: -50, yPercent: -50, x: -100, y: -100 });

        window.addEventListener('mousemove', (e) => {
          gsap.to(dot, { duration: 0.1, x: e.clientX, y: e.clientY, ease: "power2.out" });
          gsap.to(ring, { duration: 0.4, x: e.clientX, y: e.clientY, ease: "power2.out" });
        });

        document.addEventListener('mouseleave', () => {
          gsap.to([dot, ring], { opacity: 0, duration: 0.2 });
        });

        document.addEventListener('mouseenter', () => {
          gsap.to([dot, ring], { opacity: 1, duration: 0.2 });
        });

        const attachCursorHover = () => {
          const interactives = document.querySelectorAll('a, button, [role="button"], input, select, textarea, .cursor-pointer, iframe');
          interactives.forEach(el => {
            if (!el.dataset.cursorBound) {
              el.dataset.cursorBound = "true";
              el.addEventListener('mouseenter', () => {
                dot.classList.add('hovered');
                ring.classList.add('hovered');
              });
              el.addEventListener('mouseleave', () => {
                dot.classList.remove('hovered');
                ring.classList.remove('hovered');
              });
            }
          });
        };

        attachCursorHover();
        setInterval(attachCursorHover, 1000);
      }
    }

    // Scroll Progress Indicator logic
    document.addEventListener('DOMContentLoaded', () => {
      const progressContainer = document.getElementById('scroll-progress');
      const progressRing = document.getElementById('progress-ring');
      const circumference = 289;

      function updateProgress(scrollVal, progressVal) {
        const clampedProgress = Math.max(0, Math.min(1, progressVal));
        const offset = circumference - (clampedProgress * circumference);
        progressRing.style.strokeDashoffset = offset;

        if (scrollVal > 150) {
          progressContainer.classList.remove('opacity-0', 'pointer-events-none');
          progressContainer.classList.add('opacity-100', 'pointer-events-auto');
        } else {
          progressContainer.classList.add('opacity-0', 'pointer-events-none');
          progressContainer.classList.remove('opacity-100', 'pointer-events-auto');
        }
      }

      lenis.on('scroll', (e) => {
        const scrollVal = e.scroll;
        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        const progressVal = scrollVal / maxScroll;
        updateProgress(scrollVal, progressVal);
      });

      progressContainer.addEventListener('click', () => {
        lenis.scrollTo(0, { duration: 1.2 });
      });
    });

    // Form client-side validation and AJAX handling
    (function () {
      const form = document.getElementById('contact-form');
      const successScreen = document.getElementById('contact-success');
      const submitBtn = document.getElementById('form-submit-btn');
      const resetBtn = document.getElementById('success-reset-btn');

      const fields = ['name', 'email', 'phone', 'service', 'message'];

      function validate() {
        let isValid = true;

        fields.forEach(f => {
          const errEl = document.getElementById(`error-${f}`);
          if (errEl) {
            errEl.classList.add('hidden');
            errEl.classList.remove('flex');
          }
        });

        const nameVal = document.getElementById('form-name').value.trim();
        const emailVal = document.getElementById('form-email').value.trim();
        const phoneVal = document.getElementById('form-phone').value.trim();
        const serviceVal = document.getElementById('form-service').value;
        const messageVal = document.getElementById('form-message').value.trim();

        if (!nameVal) {
          showError('name', 'Full Name is required');
          isValid = false;
        }

        if (!emailVal) {
          showError('email', 'Email Address is required');
          isValid = false;
        } else if (!/\S+@\S+\.\S+/.test(emailVal)) {
          showError('email', 'Email Address is invalid');
          isValid = false;
        }

        if (!phoneVal) {
          showError('phone', 'Phone Number is required');
          isValid = false;
        } else if (!/^\+?[\d\s-]{8,15}$/.test(phoneVal)) {
          showError('phone', 'Phone Number is invalid');
          isValid = false;
        }

        if (!serviceVal) {
          showError('service', 'Please select a service');
          isValid = false;
        }

        if (!messageVal) {
          showError('message', 'Message details are required');
          isValid = false;
        }

        return isValid;
      }

      function showError(field, msg) {
        const errorEl = document.getElementById(`error-${field}`);
        if (errorEl) {
          errorEl.querySelector('span').textContent = msg;
          errorEl.classList.remove('hidden');
          errorEl.classList.add('flex');
        }
      }

      if (form) {
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          if (!validate()) return;

          // Open blank tab synchronously to bypass popup blocker
          const newTab = window.open('', '_blank');

          submitBtn.disabled = true;
          const originalHtml = submitBtn.innerHTML;
          submitBtn.innerHTML = `<div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div><span>Submitting...</span>`;

          const budget = document.getElementById('form-budget-slider').value;
          const budgetText = document.getElementById('form-budget-display').textContent;
          const service = document.getElementById('form-service').value;
          const name = document.getElementById('form-name').value;
          const email = document.getElementById('form-email').value;
          const phone = document.getElementById('form-phone').value;
          const message = document.getElementById('form-message').value;

          const formData = {
            name: name,
            email: email,
            phone: phone,
            service: service,
            message: message + ` [Budget Request: ${budgetText}]`,
            budget: budget
          };

          fetch('/api/contact', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
          })
            .then(res => res.json())
            .then(data => {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalHtml;

              if (data.success) {
                form.classList.add('hidden');
                successScreen.classList.remove('hidden');
                successScreen.classList.add('flex');

                // Build pre-typed WhatsApp message
                const serviceLabels = {
                  'custom-software': 'Custom Software',
                  'website-dev': 'Website Development',
                  'web-app': 'Web Applications',
                  'cloud-hosting': 'Cloud Hosting',
                  'server-management': 'Server Management',
                  'devops': 'DevOps & Infrastructure',
                  'maintenance': 'Maintenance & Support'
                };
                const serviceName = serviceLabels[service] || service;

                const waMessage = `Hello Digital Creatorss,\n\nI have submitted a query through the website. Here are my details:\n• Name: ${name}\n• Email: ${email}\n• Phone: ${phone}\n• Service: ${serviceName}\n• Message: ${message}\n• Estimated Budget: ${budgetText}\n\nPlease get back to me.`;
                const whatsappUrl = `https://wa.me/${window.DC_WHATSAPP || '918851613806'}?text=${encodeURIComponent(waMessage)}`;

                // Redirect the pre-opened tab to WhatsApp
                if (newTab) {
                  newTab.location.href = whatsappUrl;
                }

                form.reset();
                if (window.updateFormBudgetDisplay) {
                  window.updateFormBudgetDisplay(10000);
                }
              } else if (data.errors) {
                if (newTab) newTab.close();
                Object.keys(data.errors).forEach(f => {
                  showError(f, data.errors[f]);
                });
                if (data.errors.form) {
                  alert(data.errors.form);
                }
              } else {
                if (newTab) newTab.close();
                alert('Something went wrong. Please try again.');
              }
            })
            .catch(err => {
              if (newTab) newTab.close();
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalHtml;
              console.error('Submission error:', err);
              alert('Could not submit the form. Please try again.');
            });
        });

        if (resetBtn) {
          resetBtn.addEventListener('click', () => {
            successScreen.classList.remove('flex');
            successScreen.classList.add('hidden');
            form.classList.remove('hidden');
          });
        }
      }
    })();
  </script>
</body>

</html>