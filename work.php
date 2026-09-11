<!doctype html>
<html lang="en">

<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  render_seo_head([
    'title' => 'Our Work | Web & Software Portfolio | Digital Creatorss',
    'description' => 'Browse our portfolio of high-performance B2B web applications, e-commerce marketplaces, and custom digital products.',
    'path' => 'work.php',
  ]);
  ?>
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased overflow-x-hidden site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary">
    
    <!-- Navbar Section -->
    <?php include 'components/navbar.php'; ?>

    <!-- Main Content -->
    <main class="w-full pt-32 pb-24">
      
      <!-- Work Hero Section -->
      <section class="max-w-7xl mx-auto px-6 mb-12 relative">
        <!-- Ambient decorative glow -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-primary/10 rounded-full blur-[120px] -z-10 pointer-events-none"></div>

        <div class="text-center max-w-3xl mx-auto pt-8">
          <h1 class="page-hero-title mb-4">
            Our Digital <span class="text-primary">Portfolio</span>
          </h1>
          <p class="text-base text-text-secondary leading-relaxed font-light max-w-2xl mx-auto">
            A selection of websites, portals, CRM systems, and SaaS products delivered across multiple industries.
          </p>
        </div>
      </section>

            <?php
      require_once __DIR__ . '/includes/content.php';
      try {
        $projects = get_projects();
      } catch (Throwable $e) {
        $projects = [];
      }
?>

      <!-- Filter Controls & Search -->
      <div class="max-w-7xl mx-auto px-6 mb-12 flex flex-col md:flex-row items-center justify-between gap-6 z-30 relative">
        <!-- Filter Controls -->
        <div class="flex flex-wrap items-center justify-center gap-3">
          <button class="filter-btn px-5 py-2.5 rounded-full text-xs sm:text-sm font-semibold border border-slate-200 bg-slate-50 text-text-secondary hover:text-text-primary transition-all duration-300 cursor-pointer active" data-filter="all">
            All Works
          </button>
          <button class="filter-btn px-5 py-2.5 rounded-full text-xs sm:text-sm font-semibold border border-slate-200 bg-slate-50 text-text-secondary hover:text-text-primary transition-all duration-300 cursor-pointer" data-filter="webapp">
            Web Applications
          </button>
          <button class="filter-btn px-5 py-2.5 rounded-full text-xs sm:text-sm font-semibold border border-slate-200 bg-slate-50 text-text-secondary hover:text-text-primary transition-all duration-300 cursor-pointer" data-filter="ecommerce">
            E-Commerce
          </button>
          <button class="filter-btn px-5 py-2.5 rounded-full text-xs sm:text-sm font-semibold border border-slate-200 bg-slate-50 text-text-secondary hover:text-text-primary transition-all duration-300 cursor-pointer" data-filter="hosting">
            Cloud & Hosting
          </button>
          <button class="filter-btn px-5 py-2.5 rounded-full text-xs sm:text-sm font-semibold border border-slate-200 bg-slate-50 text-text-secondary hover:text-text-primary transition-all duration-300 cursor-pointer" data-filter="nonprofit">
            Non-Profit
          </button>
        </div>

        <!-- Search Bar -->
        <div class="relative w-full md:w-80">
          <input 
            type="text" 
            id="project-search" 
            placeholder="Search creations..." 
            class="w-full px-5 py-3 rounded-full bg-white border border-slate-200 text-text-primary placeholder-slate-500 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-xs shadow-lg" />
          <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
        </div>
      </div>

      <!-- Sticky portfolio stack -->
      <section class="w-full py-6 relative z-10 portfolio-stack-section" id="portfolio-stack-section">
        <!-- Ambient Decorative BG text -->
        <div id="giant-bg-text-container" class="absolute inset-0 hidden md:flex items-center justify-center pointer-events-none select-none opacity-5 overflow-hidden whitespace-nowrap z-0">
          <span id="giant-bg-text" class="text-[25vw] font-black uppercase tracking-tighter text-outline select-none transition-all duration-1000">CREATIONS</span>
        </div>

        <div class="portfolio-stack-container pb-24 relative z-10" id="portfolio-stack-container">
          <?php foreach ($projects as $index => $project): ?>
            <div 
              class="portfolio-stack-card cursor-pointer group transform-gpu will-change-transform"
              data-index="<?php echo $index; ?>"
              data-category="<?php echo $project['category']; ?>"
              data-project="<?php echo htmlspecialchars(json_encode($project), ENT_QUOTES, 'UTF-8'); ?>">
              
              <div class="portfolio-stack-media">
                <img 
                  src="<?php echo htmlspecialchars($project['image']); ?>" 
                  alt="<?php echo htmlspecialchars($project['title']); ?>" 
                  class="portfolio-stack-image"
                  loading="lazy"
                  decoding="async" />
                
                <!-- Gradient overlay for metadata -->
                <div class="portfolio-stack-overlay flex flex-col justify-end p-5 sm:p-8 md:p-10">
                  <div class="flex items-center gap-3 mb-2">
                    <span class="px-3 py-1 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-white/95 border border-slate-200 text-text-secondary">
                      <?php echo htmlspecialchars($project['category_label']); ?>
                    </span>
                    <span class="px-3 py-1 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-primary/90 border border-primary/30 text-white">
                      <?php echo htmlspecialchars($project['metric']); ?>
                    </span>
                  </div>
                  
                  <div class="flex items-baseline justify-between gap-4 sm:gap-6">
                    <h3 class="text-lg sm:text-2xl md:text-3xl font-black text-white leading-tight tracking-tight">
                      <?php echo htmlspecialchars($project['title']); ?>
                    </h3>
                    <div class="text-lg sm:text-2xl font-serif text-white/60 font-light group-hover:text-white/90 transition-opacity shrink-0">
                      <?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      </section>

    </main>

    <!-- Footer Section -->
    <?php include 'components/footer.php'; ?>
  </div>

  <!-- Project Details Slide-Over Drawer -->
  <div id="slide-over-overlay" class="fixed inset-0 bg-slate-50 z-[1010] opacity-0 pointer-events-none transition-opacity duration-500"></div>
  
  <div id="project-slide-over" data-lenis-prevent class="fixed inset-y-0 right-0 w-full sm:w-[600px] bg-white border-l border-slate-200 z-[1020] transform translate-x-full transition-transform duration-700 ease-[cubic-bezier(0.16,1,0.3,1)] flex flex-col shadow-[-30px_0_70px_rgba(0,0,0,0.8)]">
    
    <!-- Drawer Header -->
    <div class="flex items-center justify-between px-8 py-6 border-b border-slate-200 bg-slate-50">
      <div class="flex items-center gap-3">
        <span id="drawer-index" class="text-xl font-light font-serif text-primary">01</span>
        <span class="w-8 h-[1px] bg-primary/35"></span>
        <span id="drawer-category-label" class="text-[10px] font-extrabold tracking-widest text-slate-500 uppercase flex items-center">Web Development</span>
      </div>
      <button id="close-drawer" class="p-2 rounded-full bg-slate-100 border border-slate-200 text-text-secondary hover:text-text-primary hover:bg-slate-100 transition-all cursor-pointer">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <!-- Drawer Content (Scrollable) -->
    <div class="flex-1 overflow-y-auto custom-scrollbar px-8 py-8 space-y-8 select-text bg-slate-50" data-lenis-prevent>
      
      <!-- Project Banner Image -->
      <div class="portfolio-drawer-media w-full rounded-[24px] overflow-hidden border border-slate-200 shadow-2xl relative bg-slate-100">
        <img id="drawer-image" src="" alt="" class="w-full h-full object-contain object-center bg-slate-100" />
      </div>

      <!-- Title & Metric -->
      <div class="space-y-4">
        <h2 id="drawer-title" class="font-headings text-3xl sm:text-4xl font-black text-text-primary leading-tight">
          Project Title
        </h2>
        
        <!-- Metric Accent Panel -->
        <div class="bg-primary/10 border border-primary/20 rounded-2xl p-4 flex flex-col">
          <span id="drawer-metric" class="text-sm font-bold text-primary">Metric Value</span>
          <span id="drawer-metric-desc" class="text-[10px] text-text-secondary font-medium mt-0.5">Metric description context</span>
        </div>
      </div>

      <!-- Description -->
      <div class="space-y-3">
        <h4 class="text-xs font-bold text-text-secondary uppercase tracking-widest">About the project</h4>
        <p id="drawer-description" class="text-text-secondary font-light text-base leading-relaxed">
          Project details description text.
        </p>
      </div>

      <!-- Tech Stack -->
      <div class="space-y-3">
        <h4 class="text-xs font-bold text-text-secondary uppercase tracking-widest">Technologies Used</h4>
        <div id="drawer-stack" class="flex flex-wrap gap-2">
          <!-- Stack tags dynamic insertion -->
        </div>
      </div>

    </div>

    <!-- Drawer Footer -->
    <div class="px-8 py-6 border-t border-slate-200 bg-white/80">
      <a 
        id="drawer-link"
        href="#" 
        target="_blank" 
        rel="noopener noreferrer" 
        class="inline-flex items-center justify-center gap-2 w-full py-4 rounded-2xl bg-gradient-to-r from-primary to-secondary hover:from-secondary hover:to-primary text-white font-bold text-sm transition-all duration-300 shadow-lg shadow-primary/20 cursor-pointer">
        Launch Live Project Website <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
      </a>
    </div>

  </div>

  <!-- Premium Custom Cursor -->
  <div class="custom-cursor-dot hidden md:block"></div>
  <div class="custom-cursor-ring hidden md:block"></div>

  <!-- Scroll Progress Indicator -->
  <div id="scroll-progress" class="fixed bottom-8 right-8 z-50 w-12 h-12 cursor-pointer transition-all duration-300 hover:scale-110 opacity-0 pointer-events-none hover:shadow-[0_0_20px_rgba(37,99,235,0.3)] rounded-full">
    <div class="absolute inset-0 bg-slate-50 rounded-full flex items-center justify-center">
      <i data-lucide="arrow-up" class="w-5 h-5 text-primary"></i>
    </div>
    <svg class="absolute inset-0 w-full h-full transform -rotate-90 pointer-events-none" viewBox="0 0 100 100">
      <circle cx="50" cy="50" r="46" class="stroke-slate-200" stroke-width="8" fill="none" />
      <circle id="progress-ring" cx="50" cy="50" r="46" class="stroke-primary transition-[stroke-dashoffset] duration-75 ease-linear" stroke-width="8" fill="none" stroke-linecap="round" stroke-dasharray="289" stroke-dashoffset="289" />
    </svg>
  </div>

  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- GSAP, ScrollTrigger & Lenis CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
  <script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script>

  <!-- Script logics -->
  <script>
    // Initialize Lucide Icons
    lucide.createIcons();

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
        updateProgress(e.scroll, e.progress);
      });
      
      progressContainer.addEventListener('click', () => {
        window.scrollToTop();
      });
    });

    // --- Sticky portfolio stack ---
    document.addEventListener('DOMContentLoaded', () => {
      const cards = document.querySelectorAll('.portfolio-stack-card');
      const giantBgText = document.getElementById('giant-bg-text');
      const filterButtons = document.querySelectorAll('.filter-btn');
      const searchInput = document.getElementById('project-search');
      const stackSection = document.querySelector('.portfolio-stack-section');

      const getStickyOffset = () => {
        if (!stackSection) return 88;
        const top = getComputedStyle(stackSection).getPropertyValue('--portfolio-sticky-top').trim();
        if (top.endsWith('rem')) return parseFloat(top) * 16;
        if (top.endsWith('px')) return parseFloat(top);
        return 88;
      };

      // Slide-over elements
      const overlay = document.getElementById('slide-over-overlay');
      const slideOver = document.getElementById('project-slide-over');
      const closeBtn = document.getElementById('close-drawer');
      const dIndex = document.getElementById('drawer-index');
      const dCategory = document.getElementById('drawer-category-label');
      const dImage = document.getElementById('drawer-image');
      const dTitle = document.getElementById('drawer-title');
      const dMetric = document.getElementById('drawer-metric');
      const dMetricDesc = document.getElementById('drawer-metric-desc');
      const dDescription = document.getElementById('drawer-description');
      const dStack = document.getElementById('drawer-stack');
      const dLink = document.getElementById('drawer-link');

      let currentFilter = 'all';
      let searchQuery = '';

      // 1. GSAP ScrollTrigger to Scale Down cards as they are covered by subsequent sticky cards
      function initCardStackTriggers() {
        const stickyOffset = getStickyOffset();
        cards.forEach((card, index) => {
          if (index < cards.length - 1) {
            gsap.to(card, {
              scale: 0.96 - (cards.length - 1 - index) * 0.002,
              opacity: 0.72,
              ease: "none",
              scrollTrigger: {
                trigger: card,
                start: `top top+=${stickyOffset}px`,
                end: "bottom top",
                scrub: true,
                invalidateOnRefresh: true
              }
            });
          }
        });
      }

      initCardStackTriggers();

      // 2. Active Card Watermark Text Update on scroll
      function updateWatermark() {
        let activeCard = null;
        let minDistance = Infinity;
        const targetY = getStickyOffset() + 24;

        cards.forEach(card => {
          if (card.style.display === 'none') return;
          const rect = card.getBoundingClientRect();
          const distance = Math.abs(rect.top - targetY);
          if (distance < minDistance) {
            minDistance = distance;
            activeCard = card;
          }
        });

        if (activeCard && giantBgText) {
          try {
            const projectData = JSON.parse(activeCard.dataset.project);
            const cleanTitle = projectData.title.split('—')[0].split('-')[0].trim().split(' ')[0].toUpperCase();
            giantBgText.textContent = cleanTitle;
          } catch(e) {
            console.error(e);
          }
        }
      }

      // Hook scroll updates for active project watermarking
      if (window.lenis) {
        window.lenis.on('scroll', updateWatermark);
      }
      ScrollTrigger.addEventListener('refresh', updateWatermark);
      updateWatermark();

      // Hover dynamic elevation
      cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
          gsap.to(card, {
            y: -8,
            duration: 0.3,
            ease: "power2.out"
          });
        });
        card.addEventListener('mouseleave', () => {
          gsap.to(card, {
            y: 0,
            duration: 0.3,
            ease: "power2.out"
          });
        });
      });

      // 3. Open Details Slider Drawer
      cards.forEach(card => {
        card.addEventListener('click', () => {
          try {
            const projectData = JSON.parse(card.dataset.project);
            const indexVal = parseInt(card.dataset.index) + 1;
            const indexStr = String(indexVal).padStart(2, '0');

            dIndex.textContent = indexStr;
            dCategory.textContent = projectData.category_label;
            dImage.src = projectData.image;
            dImage.alt = projectData.title;
            dTitle.textContent = projectData.title;
            dMetric.textContent = projectData.metric;
            dMetricDesc.textContent = projectData.metric_desc;
            dDescription.textContent = projectData.description;
            dLink.href = projectData.link;

            dStack.innerHTML = '';
            projectData.stack.forEach(tech => {
              const tag = document.createElement('span');
              tag.className = 'px-3 py-1.5 rounded-lg text-[10px] font-extrabold tracking-wider bg-slate-100 border border-slate-200 text-text-secondary';
              tag.textContent = tech;
              dStack.appendChild(tag);
            });

            lucide.createIcons();

            gsap.to(slideOver, { x: 0, duration: 0.6, ease: "power4.out" });
            gsap.to(overlay, { opacity: 1, pointerEvents: "auto", duration: 0.4 });

            if (window.lenis) {
              window.lenis.stop();
            }
          } catch (e) {
            console.error("Error loading project details", e);
          }
        });
      });

      // 4. Close Drawer
      function closeDrawer() {
        gsap.to(slideOver, { x: "100%", duration: 0.5, ease: "power3.inOut" });
        gsap.to(overlay, { opacity: 0, pointerEvents: "none", duration: 0.3 });
        
        if (window.lenis) {
          window.lenis.start();
        }
      }

      if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
      if (overlay) overlay.addEventListener('click', closeDrawer);

      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDrawer();
      });

      // 5. Combined Search & Filtering Engine with dynamic top offsets
      function updateVisibility() {
        let visibleCount = 0;
        cards.forEach(card => {
          try {
            const projectData = JSON.parse(card.dataset.project);
            const category = card.dataset.category;

            const matchesCategory = (currentFilter === 'all' || category === currentFilter);

            const titleMatch = projectData.title.toLowerCase().includes(searchQuery);
            const descMatch = projectData.description.toLowerCase().includes(searchQuery);
            const stackMatch = projectData.stack.some(tech => tech.toLowerCase().includes(searchQuery));
            const categoryLabelMatch = projectData.category_label.toLowerCase().includes(searchQuery);
            const matchesSearch = titleMatch || descMatch || stackMatch || categoryLabelMatch;

            if (matchesCategory && matchesSearch) {
              card.style.display = 'block';
              gsap.to(card, { opacity: 1, scale: 1, duration: 0.4, ease: "power2.out" });
            } else {
              gsap.to(card, {
                opacity: 0,
                scale: 0.9,
                duration: 0.3,
                ease: "power2.in",
                onComplete: () => {
                  card.style.display = 'none';
                }
              });
            }
          } catch (e) {
            console.error(e);
          }
        });

        setTimeout(() => {
          ScrollTrigger.refresh();
          updateWatermark();
        }, 350);
      }

      filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          filterButtons.forEach(b => {
            b.classList.remove('active', 'bg-primary', 'text-white', 'border-primary');
            b.classList.add('bg-slate-50', 'text-text-secondary', 'border-slate-200');
          });
          btn.classList.remove('bg-slate-50', 'text-text-secondary', 'border-slate-200');
          btn.classList.add('active', 'bg-primary', 'text-white', 'border-primary');

          currentFilter = btn.dataset.filter;
          updateVisibility();
        });
      });

      if (searchInput) {
        searchInput.addEventListener('input', (e) => {
          searchQuery = e.target.value.toLowerCase().trim();
          updateVisibility();
        });
      }

    });
  </script>
</body>

</html>
