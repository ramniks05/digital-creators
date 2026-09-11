<!doctype html>
<html lang="en">

<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  render_seo_head([
    'title' => 'Our Services | Web, App, Cloud & DevOps | Digital Creatorss',
    'description' => 'Web development, custom software, cloud hosting, server management, DevOps, and ongoing maintenance from Digital Creatorss.',
    'path' => 'services.php',
  ]);
  ?>
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary">
    
    <!-- Navbar Section -->
    <?php include 'components/navbar.php'; ?>

    <!-- Main Content -->
    <main class="w-full pt-32 pb-24">
      
      <!-- Services Hero Header -->
      <section class="max-w-7xl mx-auto px-6 mb-10 relative">
        <!-- Ambient decorative glows -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-primary/10 rounded-full blur-[120px] -z-10 pointer-events-none"></div>

        <div class="text-center max-w-4xl mx-auto pt-8">
          <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15 mb-6">
            Our Services
          </span>
          <h1 class="section-title mb-6">
            Engineering the Future of <br class="hidden sm:inline" />
            <span class="text-primary">Web & Cloud Systems</span>
          </h1>
          <p class="text-base text-text-secondary leading-relaxed font-light max-w-2xl mx-auto">
            Corporate websites, mobile apps, portals, CRM, and SaaS solutions — plus cloud hosting, server management, and DevOps built to scale.
          </p>
        </div>
      </section>

      <?php
      require_once __DIR__ . '/includes/content.php';
      require_once __DIR__ . '/includes/media_icons.php';
      try {
        $services_data = get_services();
      } catch (Throwable $e) {
        $services_data = [];
      }
?>

      <!-- Scroll Pinned Showcase Section -->
      <section id="services-showcase" class="w-full bg-bg-primary text-text-primary relative py-8">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 relative">
          
          <!-- Left Column: Pinned Visual Showcase (Sticky on desktop) -->
          <div class="hidden lg:flex lg:col-span-6 sticky top-28 lg:top-32 lg:h-[calc(100vh-200px)] items-center justify-center z-30 order-1 lg:order-1 select-none self-start desktop-only">
            
            <!-- Picture Frame Container -->
            <div class="w-full max-w-md aspect-[16/10] rounded-[28px] bg-white border border-slate-200 relative overflow-hidden shadow-[0_25px_60px_-15px_rgba(15,23,42,0.2)] flex items-center justify-center p-3">
              
              <!-- Ambient background glows behind the card -->
              <div class="absolute -top-24 -left-24 w-48 h-48 bg-primary/10 rounded-full blur-2xl pointer-events-none"></div>
              <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-orange-500/10 rounded-full blur-2xl pointer-events-none"></div>

              <!-- Overlapping Visual Content Cards for each service -->
              <?php foreach ($services_data as $index => $srv): 
                $spot = $srv['spotlight'];
              ?>
                <div class="visual-card absolute inset-3 rounded-[22px] overflow-hidden bg-white transition-all duration-700 transform <?php echo $index === 0 ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-8 pointer-events-none'; ?>" data-index="<?php echo $index; ?>">
                  <!-- Background Image -->
                  <img src="<?php echo htmlspecialchars($srv['image']); ?>" alt="<?php echo htmlspecialchars($srv['title']); ?>" class="w-full h-full object-contain" />
                  
                  <!-- Soft bottom label bar (does not cover the artwork) -->
                  <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 via-black/35 to-transparent p-4 pt-10 flex flex-col justify-end pointer-events-none">
                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary/90 border border-primary/30 text-white w-fit mb-2 shadow-sm">
                      <?php echo htmlspecialchars($spot['tag']); ?>
                    </span>
                    
                    <h3 class="font-headings text-lg font-bold text-white mb-1"><?php echo htmlspecialchars($srv['title']); ?></h3>
                    
                    <!-- Technologies -->
                    <div class="flex flex-wrap gap-1.5 mt-1">
                      <?php foreach ($spot['stack'] as $tech): ?>
                        <span class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-white/90 border border-slate-200 text-slate-700 backdrop-blur-sm">
                          <?php echo htmlspecialchars($tech); ?>
                        </span>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>

            </div>
          </div>

          <!-- Right Column: Scroll Content Sections -->
          <div class="lg:col-span-6 order-2 lg:order-2 flex flex-col">
            <?php foreach ($services_data as $index => $srv): 
              $numStr = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
              $spot = $srv['spotlight'];
            ?>
              <div class="hidden lg:flex service-scroll-section lg:min-h-[52vh] flex-col justify-center py-8 lg:py-6 border-b border-slate-200 last:border-b-0 desktop-only" data-index="<?php echo $index; ?>">
                <div class="flex items-center gap-3 mb-3">
                  <span class="font-headings text-base font-semibold text-primary"><?php echo $numStr; ?></span>
                  <div class="h-px w-8 bg-primary/30"></div>
                  <span class="text-xs font-semibold uppercase tracking-wider text-text-secondary"><?php echo htmlspecialchars($srv['metric']); ?></span>
                </div>

                <h2 class="section-title mb-3">
                  <?php echo htmlspecialchars($srv['title']); ?>
                </h2>
                
                <p class="text-base sm:text-lg text-text-secondary font-light leading-relaxed mb-5 max-w-[500px]">
                  <?php echo htmlspecialchars($srv['desc']); ?>
                </p>

                <div class="flex flex-col sm:flex-row items-start gap-4">
                  <span onclick="scrollToSection('contact')" class="btn-primary cursor-pointer">
                    Inquire About Service <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- Style override for webkit scrollbar hiding -->
            <style>
              .scrollbar-none {
                scrollbar-width: none;
              }
              .scrollbar-none::-webkit-scrollbar {
                display: none;
              }
            </style>

            <!-- Mobile Custom Tabbed Showcase (visible on screens < lg) -->
            <div class="lg:hidden flex flex-col gap-4 w-full mt-2 mobile-only">
              <!-- Horizontally Scrollable Pills Tracker -->
              <div class="flex items-center gap-2 overflow-x-auto pb-4 scrollbar-none relative z-20">
                <?php foreach ($services_data as $index => $srv): ?>
                  <button 
                    class="mobile-service-tab-btn flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-semibold border transition-all duration-300 cursor-pointer <?php echo $index === 0 ? 'bg-primary text-white border-primary shadow-md shadow-primary/20' : 'bg-slate-50 text-text-secondary border-slate-200'; ?>"
                    data-target-idx="<?php echo $index; ?>">
                    <?php echo media_icon_img($srv['icon'], $srv['title'], 'media-icon-inline'); ?>
                    <span><?php echo htmlspecialchars($srv['title']); ?></span>
                  </button>
                <?php endforeach; ?>
              </div>

              <!-- Active Service Display Panel -->
              <div class="relative w-full overflow-hidden bg-white/80 border border-slate-200 rounded-[32px] p-5 sm:p-6 text-left" id="mobile-service-display">
                <?php foreach ($services_data as $index => $srv): 
                  $spot = $srv['spotlight'];
                  $numStr = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                ?>
                  <div 
                    class="mobile-service-content-pane flex flex-col gap-4 transition-all duration-500 transform <?php echo $index === 0 ? 'opacity-100 translate-x-0 relative pointer-events-auto' : 'opacity-0 translate-x-8 absolute inset-x-5 sm:inset-x-6 top-5 sm:top-6 pointer-events-none'; ?>"
                    data-pane-idx="<?php echo $index; ?>">
                    
                    <!-- Card Image Frame -->
                    <div class="w-full rounded-[20px] border border-slate-200 overflow-hidden relative shadow-md bg-white p-2">
                      <img src="<?php echo htmlspecialchars($srv['image']); ?>" alt="<?php echo htmlspecialchars($srv['title']); ?>" class="w-full h-auto object-contain" />
                      <!-- Spotlight tag -->
                      <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-white/90 border border-slate-200 text-text-secondary backdrop-blur-md">
                        <?php echo htmlspecialchars($spot['tag']); ?>
                      </span>
                    </div>

                    <!-- Metadata Header -->
                    <div class="flex items-center gap-3">
                      <span class="font-headings text-lg font-bold text-primary"><?php echo $numStr; ?></span>
                      <div class="h-px w-8 bg-primary/40"></div>
                      <span class="text-[10px] font-bold uppercase tracking-wider text-text-secondary"><?php echo htmlspecialchars($srv['metric']); ?></span>
                    </div>

                    <!-- Title & Description -->
                    <div>
                      <h3 class="card-title mb-2">
                        <?php echo htmlspecialchars($srv['title']); ?>
                      </h3>
                      <p class="text-xs sm:text-sm text-text-secondary font-light leading-relaxed">
                        <?php echo htmlspecialchars($srv['desc']); ?>
                      </p>
                    </div>

                    <!-- Tech stack & CTA -->
                    <div class="flex flex-col gap-4 mt-1 border-t border-slate-200 pt-4">
                      <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($spot['stack'] as $tech): ?>
                          <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-50 border border-slate-200 text-text-secondary">
                            <?php echo htmlspecialchars($tech); ?>
                          </span>
                        <?php endforeach; ?>
                      </div>

                      <span onclick="scrollToSection('contact')" class="inline-flex items-center justify-center gap-2 w-full px-5 py-3.5 rounded-xl bg-primary hover:bg-secondary text-white font-semibold text-xs transition-all duration-200 cursor-pointer shadow-md hover:shadow-primary/20 text-center">
                        Inquire About Service <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                      </span>
                    </div>

                  </div>
                <?php endforeach; ?>
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

      if (window.lenis) {
        window.lenis.on('scroll', (e) => {
          updateProgress(e.scroll, e.progress);
        });
      }
      
      progressContainer.addEventListener('click', () => {
        window.scrollToTop();
      });
    });

    // --- Interactive Pinned Scroll Animation ---
    document.addEventListener('DOMContentLoaded', () => {
      const sections = document.querySelectorAll('.service-scroll-section');
      const cards = document.querySelectorAll('.visual-card');
      
      let mm = gsap.matchMedia();

      mm.add("(min-width: 1024px)", () => {
        sections.forEach((section, idx) => {
          ScrollTrigger.create({
            trigger: section,
            start: "top 60%",
            end: "bottom 40%",
            onToggle: (self) => {
              if (self.isActive) {
                updateActiveService(idx);
              }
            }
          });
        });

        function updateActiveService(activeIndex) {
          // Toggle active visual card details
          cards.forEach((card, idx) => {
            if (idx === activeIndex) {
              card.classList.remove('opacity-0', 'scale-95', 'translate-y-8', 'pointer-events-none');
              card.classList.add('opacity-100', 'scale-100', 'translate-y-0');
            } else {
              card.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
              card.classList.add('opacity-0', 'scale-95', 'translate-y-8', 'pointer-events-none');
            }
          });
        }

        // Initialize
        updateActiveService(0);
      });
    });

    // --- Mobile Services Tab Switching ---
    document.addEventListener('DOMContentLoaded', () => {
      const tabBtns = document.querySelectorAll('.mobile-service-tab-btn');
      const contentPanes = document.querySelectorAll('.mobile-service-content-pane');

      tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          const targetIdx = parseInt(btn.dataset.targetIdx);

          // Update active buttons
          tabBtns.forEach(b => {
            b.classList.remove('bg-primary', 'text-white', 'border-primary', 'shadow-md', 'shadow-primary/20');
            b.classList.add('bg-slate-50', 'text-text-secondary', 'border-slate-200');
          });
          btn.classList.remove('bg-slate-50', 'text-text-secondary', 'border-slate-200');
          btn.classList.add('bg-primary', 'text-white', 'border-primary', 'shadow-md', 'shadow-primary/20');

          contentPanes.forEach(pane => {
            const paneIdx = parseInt(pane.dataset.paneIdx);
            if (paneIdx === targetIdx) {
              pane.classList.remove('opacity-0', 'translate-x-8', 'absolute', 'inset-x-5', 'sm:inset-x-6', 'top-5', 'sm:top-6', 'pointer-events-none');
              pane.classList.add('opacity-100', 'translate-x-0', 'relative', 'pointer-events-auto');
            } else {
              pane.classList.remove('opacity-100', 'translate-x-0', 'relative', 'pointer-events-auto');
              pane.classList.add('opacity-0', 'translate-x-8', 'absolute', 'inset-x-5', 'sm:inset-x-6', 'top-5', 'sm:top-6', 'pointer-events-none');
            }
          });
        });
      });
    });
  </script>
</body>

</html>
