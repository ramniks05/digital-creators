<!doctype html>
<html lang="en" class="site-canvas-root">

<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  render_seo_head([
    'title' => 'Digital Creatorss | Web Development, Hosting & Server Management',
    'description' => 'Digital Creatorss builds websites, apps, CRM, school ERP, and SaaS — with cloud hosting and server management.',
    'path' => '/',
    'json_ld' => [seo_organization_graph(), seo_website_graph()],
  ]);
  ?>
</head>

<body class="home-page text-text-primary font-sans antialiased overflow-x-hidden site-canvas">
  <?php include 'components/site_background.php'; ?>
  <div class="relative z-[1] w-full min-h-screen overflow-x-hidden">
    <!-- Intro Preloader Section -->
    <?php include 'components/intro_preloader.php'; ?>

    <!-- Navbar Section -->
    <?php include 'components/navbar.php'; ?>

    <main class="w-full overflow-x-hidden">
      <!-- Hero Section -->
      <?php include 'components/hero.php'; ?>

      <!-- Ready demo products -->
      <?php include 'components/products_teaser.php'; ?>

      <!-- Services Section -->
      <?php include 'components/services.php'; ?>

      <!-- Industries & Solutions -->
      <?php include 'components/industries.php'; ?>

      <!-- Why Choose Us Section -->
      <?php include 'components/why_choose_us.php'; ?>

       <!-- Video Showcase Section -->
      
      <?php include 'components/projects-section.php'; ?>

      <!-- Spotlight Reels Section -->
      <?php include 'components/reels_showcase.php'; ?>

      <!-- Testimonials Section -->
      <?php include 'components/testimonials.php'; ?>

      <!-- Contact Form Section -->
      <?php include 'components/contact_form.php'; ?>
    </main>

    <!-- Footer Section -->
    <?php include 'components/footer.php'; ?>
  </div>

  <!-- Local animation libraries prevent CDN failures from blocking the page. -->
  <script src="assets/vendor/lucide.min.js"></script>
  <script src="assets/vendor/gsap.min.js"></script>
  <script src="assets/vendor/ScrollTrigger.min.js"></script>
  <script src="assets/vendor/lenis.min.js"></script>

  <!-- Interactive behaviors and animations script -->
  <script>
    window.DC_WHATSAPP = <?php require_once __DIR__ . '/includes/content.php'; echo json_encode(whatsapp_number()); ?>;
    // Initialize Lucide Icons
    if (window.lucide) {
      window.lucide.createIcons();
    }

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
    window.lenis = lenis;

    lenis.on('scroll', ScrollTrigger.update);

    gsap.ticker.add((time) => {
      lenis.raf(time * 1000);
    });

    gsap.ticker.lagSmoothing(0);

    // Navbar Toggle and Scroll logic handled globally in components/navbar.php
    window.scrollToTop = function () {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    };

    // --- 2. GSAP Animations & ScrollTriggers ---
    window.addEventListener('DOMContentLoaded', () => {
      // Hero Section entrance animation (paused by default, played on loader completion)
      const heroTl = gsap.timeline({ paused: true, defaults: { ease: 'power4.out' } });

      let position = 0;
      if (document.querySelector('.hero-badge')) {
        heroTl.fromTo('.hero-badge', { opacity: 0, scale: 0.95, y: -10 }, { opacity: 1, scale: 1, y: 0, duration: 0.8 });
        position = '-=0.6';
      }
      if (document.querySelector('.hero-title span')) {
        heroTl.fromTo('.hero-title span', { y: 60, opacity: 0 }, { y: 0, opacity: 1, duration: 1, stagger: 0.15 }, position);
        position = '-=0.6';
      }
      if (document.querySelector('.hero-desc')) {
        heroTl.fromTo('.hero-desc', { opacity: 0, y: 15 }, { opacity: 1, y: 0, duration: 0.8 }, position);
        position = '-=0.6';
      }
      if (document.querySelector('.hero-visual')) {
        heroTl.fromTo('.desktop-only .hero-visual', { x: 32, scale: 0.97 }, { x: 0, scale: 1, duration: 0.9, ease: 'power3.out' }, position);
        heroTl.fromTo('.mobile-only .hero-visual', { y: 16 }, { y: 0, duration: 0.7, ease: 'power2.out' }, position);
      }
      if (document.querySelector('.hero-cta-group')) {
        heroTl.fromTo('.hero-cta-group', { opacity: 0, y: 15 }, { opacity: 1, y: 0, duration: 0.8 }, position);
        position = '-=0.6';
      }
      if (document.querySelector('.hero-features li')) {
        heroTl.fromTo('.hero-features li', { opacity: 0, x: -10 }, { opacity: 1, x: 0, duration: 0.6, stagger: 0.1 }, '-=0.4');
      }
      if (document.querySelector('.hero-stats')) {
        heroTl.fromTo('.hero-stats', { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.7 }, '-=0.3');
      }

      // --- Intro Preloader Logic ---
      const preloader = document.getElementById('intro-preloader');
      if (preloader) {
        // Session storage to show loader only once per session
        if (sessionStorage.getItem('intro_seen_v2')) {
          preloader.remove();
          heroTl.play();
        } else {
          sessionStorage.setItem('intro_seen_v2', 'true');

          // Prevent user scroll initially
          if (window.lenis) window.lenis.stop();
          document.body.classList.add('overflow-hidden');

          const percentageEl = document.getElementById('intro-percentage');
          const progressBarEl = document.getElementById('intro-progress-bar');
          const statusTextEl = document.querySelector('.intro-status-text');

          const progressObj = { val: 0 };
          const introTl = gsap.timeline();

          // 1. Animate the progress bar and percentage value (0% to 100%)
          introTl.to(progressObj, {
            val: 100,
            duration: 0.65,
            ease: 'power1.inOut',
            onUpdate: () => {
              const p = Math.floor(progressObj.val);
              if (percentageEl) percentageEl.textContent = p.toString().padStart(2, '0') + '%';
              if (progressBarEl) progressBarEl.style.width = p + '%';

              // Change loading status text dynamically
              if (statusTextEl) {
                if (p < 50) {
                  statusTextEl.textContent = 'Loading';
                } else if (p < 90) {
                  statusTextEl.textContent = 'Almost ready';
                } else {
                  statusTextEl.textContent = 'Welcome';
                }
              }
            }
          });

          introTl.to('#loader-elements', {
            opacity: 0,
            duration: 0.12,
            ease: 'power2.inOut'
          });

          // Reveal the brand mark before transitioning into the hero.
          introTl.fromTo('#brand-zoom-container',
            { opacity: 0 },
            { opacity: 1, duration: 0.18, ease: 'power2.out' }
          );

          introTl.fromTo('.intro-brand-wrapper',
            { opacity: 0, scale: 0.82, y: 12 },
            { opacity: 1, scale: 1, y: 0, duration: 0.3, ease: 'back.out(1.7)' },
            '<'
          );

          introTl.to('.intro-brand-wrapper', {
            scale: 1.04,
            duration: 0.12,
            ease: 'power1.inOut'
          });

          introTl.to(preloader, {
            opacity: 0,
            duration: 0.2,
            ease: 'power2.out',
            onComplete: () => {
              window.clearTimeout(window.introPreloaderSafetyTimer);
              preloader.remove();
              if (window.lenis) window.lenis.start();
              document.body.classList.remove('overflow-hidden');
              heroTl.play();
            }
          }, '-=0.08');
        }
      } else {
        // If preloader element not present, run entrance animation immediately
        heroTl.play();
      }



      // Services header scroll animation
      if (document.querySelector('.services-header')) {
        gsap.fromTo('.services-header > *',
          { y: 20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#services',
              start: 'top 80%',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: 'power3.out'
          }
        );
      }

      // Industries grid entrance
      if (document.querySelector('.industry-bar')) {
        gsap.fromTo('.industry-bar',
          { y: 24, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#industries',
              start: 'top 80%',
            },
            y: 0,
            opacity: 1,
            duration: 0.5,
            stagger: 0.06,
            ease: 'power2.out'
          }
        );
      }

      // Services accordion items entrance
      if (document.querySelector('.service-expertise-card')) {
        gsap.fromTo('.service-expertise-card',
          { y: 28, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.services-expertise-grid',
              start: 'top 85%',
            },
            y: 0,
            opacity: 1,
            duration: 0.55,
            stagger: 0.08,
            ease: 'power2.out'
          }
        );
      }

      // Why Choose Us: header
      if (document.querySelector('.why-header')) {
        gsap.fromTo('.why-header > *',
          { y: 20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#why-choose-us',
              start: 'top 80%',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.12,
            ease: 'power3.out'
          }
        );
      }

      // Why Choose Us: benefit cards
      if (document.querySelector('.benefit-card')) {
        gsap.fromTo('.benefit-card',
          { y: 24, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.benefits-grid',
              start: 'top 85%',
            },
            y: 0,
            opacity: 1,
            duration: 0.55,
            stagger: 0.1,
            ease: 'power2.out'
          }
        );
      }

      // Why Choose Us: stats panel
      if (document.querySelector('.why-stats-panel')) {
        gsap.fromTo('.why-stats-panel',
          { y: 20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.why-stats-panel',
              start: 'top 88%',
            },
            y: 0,
            opacity: 1,
            duration: 0.7,
            ease: 'power2.out'
          }
        );
      }

      // Why Choose Us: stats panel entrance (stat cards inside)
      if (document.querySelector('.why-stats-panel .stat-card')) {
        gsap.fromTo('.why-stats-panel .stat-card',
          { scale: 0.95, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.why-stats-panel',
              start: 'top 85%',
            },
            scale: 1,
            opacity: 1,
            duration: 0.6,
            stagger: 0.1,
            ease: 'back.out(1.4)',
            delay: 0.15
          }
        );
      }

      // Why Choose Us: Stats Numbers count-up
      const statItems = gsap.utils.toArray('.stat-num');
      statItems.forEach((item) => {
        const targetVal = parseInt(item.getAttribute('data-target'), 10);
        gsap.fromTo(item,
          { textContent: '0' },
          {
            textContent: targetVal,
            duration: 2.5,
            ease: 'power2.out',
            scrollTrigger: {
              trigger: item,
              start: 'top 90%',
              toggleActions: 'play none none none'
            },
            snap: { textContent: 1 },
            onUpdate: function () {
              item.innerHTML = Math.ceil(item.textContent).toLocaleString();
            }
          }
        );
      });

      // Director Profiles: Header
      if (document.querySelector('.leaders-header')) {
        gsap.fromTo('.leaders-header > *',
          { y: 20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#leaders',
              start: 'top 80%',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: 'power3.out'
          }
        );
      }

      // Director Profiles: Cards
      if (document.querySelector('.profile-card')) {
        gsap.fromTo('.profile-card',
          { y: 30, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.profiles-container',
              start: 'top 85%',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: 'power2.out'
          }
        );
      }

      // Spotlight Reels: Header
      if (document.querySelector('.reels-header')) {
        gsap.fromTo('.reels-header > *',
          { y: 20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#reels',
              start: 'top 80%',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: 'power3.out'
          }
        );
      }

      // Spotlight Reels: Cards
      if (document.querySelector('.reel-card')) {
        gsap.fromTo('.reel-card',
          { y: 50, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#reels',
              start: 'top 75%',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: 'power3.out'
          }
        );
      }

      // Testimonials: Header
      if (document.querySelector('.test-header')) {
        gsap.fromTo('.test-header > *',
          { y: 20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#testimonials',
              start: 'top 80%',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: 'power3.out'
          }
        );
      }

      // Testimonials: Carousel container entrance
      if (document.querySelector('.test-carousel')) {
        gsap.fromTo('.test-carousel',
          { scale: 0.98, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.test-carousel',
              start: 'top 85%',
            },
            scale: 1,
            opacity: 1,
            duration: 0.8,
            ease: 'power2.out'
          }
        );
      }

      // Contact details text: Left (header elements)
      if (document.querySelector('.contact-left')) {
        gsap.fromTo('.contact-left > *:not(.contact-list-wrapper)',
          { x: -20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#contact',
              start: 'top 80%',
            },
            x: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.12,
            ease: 'power3.out'
          }
        );
      }

      // Contact details items: Left (individual items)
      if (document.querySelector('.contact-item')) {
        gsap.fromTo('.contact-item',
          { x: -20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '.contact-list-wrapper',
              start: 'top 85%',
            },
            x: 0,
            opacity: 1,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power2.out'
          }
        );
      }

      // Contact form card: Right
      if (document.querySelector('.contact-right')) {
        gsap.fromTo('.contact-right',
          { x: 20, opacity: 0 },
          {
            scrollTrigger: {
              trigger: '#contact',
              start: 'top 80%',
            },
            x: 0,
            opacity: 1,
            duration: 1,
            ease: 'power3.out'
          }
        );
      }
    });

    // --- 3. Testimonials Carousel functionality ---
    (function () {
      const slides = document.querySelectorAll('.test-slide');
      const dots = document.querySelectorAll('.test-dot');
      const prevBtn = document.getElementById('test-prev');
      const nextBtn = document.getElementById('test-next');
      let activeIdx = 0;
      let autoplayTimer = null;

      function updateCarousel() {
        const slideWrap = document.getElementById('test-slides');
        slides.forEach((slide, idx) => {
          if (idx === activeIdx) {
            slide.classList.remove('opacity-0', 'scale-98', 'pointer-events-none', 'z-0', 'absolute');
            slide.classList.add('opacity-100', 'scale-100', 'pointer-events-auto', 'relative', 'z-10');
          } else {
            slide.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto', 'relative', 'z-10');
            slide.classList.add('opacity-0', 'scale-98', 'pointer-events-none', 'absolute', 'z-0');
          }
        });

        if (slideWrap && slides[activeIdx]) {
          // Let active slide define height so long reviews don't clip on mobile
          slideWrap.style.minHeight = Math.max(slides[activeIdx].offsetHeight, 220) + 'px';
        }

        dots.forEach((dot, idx) => {
          if (idx === activeIdx) {
            dot.classList.remove('bg-slate-300', 'w-2');
            dot.classList.add('test-dot-active', 'w-6');
          } else {
            dot.classList.remove('test-dot-active', 'w-6');
            dot.classList.add('bg-slate-300', 'w-2');
          }
        });
      }

      function startAutoplay() {
        stopAutoplay();
        autoplayTimer = setInterval(() => {
          activeIdx = (activeIdx + 1) % slides.length;
          updateCarousel();
        }, 6000);
      }

      function stopAutoplay() {
        if (autoplayTimer) {
          clearInterval(autoplayTimer);
        }
      }

      if (slides.length > 0) {
        updateCarousel();
        startAutoplay();

        if (prevBtn) {
          prevBtn.addEventListener('click', () => {
            stopAutoplay();
            activeIdx = (activeIdx - 1 + slides.length) % slides.length;
            updateCarousel();
            startAutoplay();
          });
        }

        if (nextBtn) {
          nextBtn.addEventListener('click', () => {
            stopAutoplay();
            activeIdx = (activeIdx + 1) % slides.length;
            updateCarousel();
            startAutoplay();
          });
        }

        dots.forEach((dot, idx) => {
          dot.addEventListener('click', () => {
            stopAutoplay();
            activeIdx = idx;
            updateCarousel();
            startAutoplay();
          });
        });

        // Touch events for mobile swipe navigation
        const carouselContainer = document.querySelector('.test-carousel');
        if (carouselContainer) {
          let touchStartX = 0;
          let touchEndX = 0;

          carouselContainer.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
          }, { passive: true });

          carouselContainer.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
          }, { passive: true });

          function handleSwipe() {
            const swipeThreshold = 50; // Minimum drag/swipe distance in pixels
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
              stopAutoplay();
              if (diff > 0) {
                // Swiped left -> next slide
                activeIdx = (activeIdx + 1) % slides.length;
              } else {
                // Swiped right -> prev slide
                activeIdx = (activeIdx - 1 + slides.length) % slides.length;
              }
              updateCarousel();
              startAutoplay();
            }
          }
        }
      }
    })();

    // --- 4. AJAX Contact Form Submission and Client Validation ---
    (function () {
      // Global function for form budget slider update
      window.updateFormBudgetDisplay = function(value) {
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

      const form = document.getElementById('contact-form');
      const successScreen = document.getElementById('contact-success');
      const submitBtn = document.getElementById('form-submit-btn');
      const resetBtn = document.getElementById('success-reset-btn');

      const fields = ['name', 'email', 'phone', 'service', 'message'];

      function validate() {
        let isValid = true;

        // Clear error states
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

          // Update submit button visual state
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

    // Premium Custom Cursor Logic using GSAP
    document.addEventListener('DOMContentLoaded', () => {
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
            const interactives = document.querySelectorAll('a, button, [role="button"], input, select, textarea, .cursor-pointer, dotlottie-player');
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
    });
  </script>

  <!-- Premium Custom Cursor -->
  <div class="custom-cursor-dot hidden md:block"></div>
  <div class="custom-cursor-ring hidden md:block"></div>


  <div id="scroll-progress" class="fixed bottom-4 right-4 md:bottom-8 md:right-8 z-50 w-11 h-11 md:w-12 md:h-12 cursor-pointer transition-all duration-300 hover:scale-110 opacity-0 pointer-events-none hover:shadow-[0_0_20px_rgba(0,123,255,0.35)] rounded-full hidden sm:block">
    <div class="absolute inset-0 bg-slate-50 rounded-full flex items-center justify-center">
      <i data-lucide="arrow-up" class="w-5 h-5 text-primary"></i>
    </div>
    <svg class="absolute inset-0 w-full h-full transform -rotate-90 pointer-events-none" viewBox="0 0 100 100">
      <circle cx="50" cy="50" r="46" class="stroke-slate-200" stroke-width="8" fill="none" />
      <circle id="progress-ring" cx="50" cy="50" r="46" class="stroke-primary transition-[stroke-dashoffset] duration-75 ease-linear" stroke-width="8" fill="none" stroke-linecap="round" stroke-dasharray="289" stroke-dashoffset="289" />
    </svg>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const progressContainer = document.getElementById('scroll-progress');
      const progressRing = document.getElementById('progress-ring');
      const circumference = 289; 

      function updateProgress(scrollVal, progressVal) {
        // Clamp progress percentage between 0 and 1
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

      if (window.lenis) {
        window.lenis.on('scroll', (e) => {
          updateProgress(e.scroll, e.progress);
        });
        // Initial setup
        updateProgress(window.lenis.scroll, window.lenis.progress || 0);
      } else {
        function handleNativeScroll() {
          const scrollTop = window.scrollY || document.documentElement.scrollTop;
          const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
          const scrollPercentage = scrollHeight > 0 ? (scrollTop / scrollHeight) : 0;
          updateProgress(scrollTop, scrollPercentage);
        }
        window.addEventListener('scroll', handleNativeScroll, { passive: true });
        handleNativeScroll();
      }

      progressContainer.addEventListener('click', () => {
        if (window.lenis) {
          window.lenis.scrollTo(0);
        } else {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
      
      // Re-initialize Lucide icon for the newly added arrow
      if(window.lucide) { lucide.createIcons(); }
    });
  </script>
</body>

</html>