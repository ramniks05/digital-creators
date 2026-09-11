<!doctype html>
<html lang="en">

<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  render_seo_head([
    'title' => 'Why Choose Digital Creatorss | Web & Cloud Experts',
    'description' => 'High-performance code, custom UI/UX, and conversion-focused delivery — see why teams choose Digital Creatorss for web, cloud, and software.',
    'path' => 'why-us.php',
  ]);
  ?>
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary">

    <!-- Navbar Section -->
    <?php include 'components/navbar.php'; ?>

    <!-- Main Content -->
    <main class="w-full pt-32 pb-24">

      <!-- Why Us Hero -->
      <section class="max-w-7xl mx-auto px-6 mb-16 relative">
        <!-- Ambient decorative glow -->
        <div
          class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-primary/10 rounded-full blur-[120px] -z-10 pointer-events-none">
        </div>

        <div class="text-center max-w-3xl mx-auto pt-8">
          <span
            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-semibold uppercase tracking-wider mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-primary/70 animate-pulse"></span>
            Wanna know why Us?
          </span>
          <h1 class="page-hero-title mb-4">
            Why Businesses Choose <span class="text-primary">Digital Creatorss</span>
          </h1>
          <p class="text-base text-text-secondary leading-relaxed font-light max-w-2xl mx-auto">
            Websites, portals, CRM, and SaaS products — built with clear communication, secure architecture, and long-term support.
          </p>
        </div>
      </section>

      <!-- Three Pillars Grid -->
      <section class="max-w-7xl mx-auto px-6 mb-24">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

          <!-- Pillar 1 -->
          <div
            class="rounded-3xl border border-slate-200 bg-white/80 p-8 backdrop-blur-sm relative group hover:border-primary/30 transition-all duration-300">
            <div
              class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            </div>
            <div
              class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
              <i data-lucide="cpu" class="w-6 h-6"></i>
            </div>
            <h3 class="font-headings text-xl font-bold text-text-primary mb-3">Actually Clean Code</h3>
            <p class="text-sm text-text-secondary leading-relaxed font-light">
              We write real, production-ready code. No bloated WordPress templates, no slow builders, and zero logic
              that breaks the moment your server looks at it wrong. It's built to run so fast it'll make your head spin.
            </p>
          </div>

          <!-- Pillar 2 -->
          <div
            class="rounded-3xl border border-slate-200 bg-white/80 p-8 backdrop-blur-sm relative group hover:border-primary/30 transition-all duration-300">
            <div
              class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            </div>
            <div
              class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
              <i data-lucide="sparkles" class="w-6 h-6"></i>
            </div>
            <h3 class="font-headings text-xl font-bold text-text-primary mb-3">Premium Eye-Candy</h3>
            <p class="text-sm text-text-secondary leading-relaxed font-light">
              Bespoke UI/UX curated specifically for humans, not robots. We design layouts so clean and interfaces so
              satisfying that your competitors will probably start shamelessly copy-pasting your UI.
            </p>
          </div>

          <!-- Pillar 3 -->
          <div
            class="rounded-3xl border border-slate-200 bg-white/80 p-8 backdrop-blur-sm relative group hover:border-primary/30 transition-all duration-300">
            <div
              class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            </div>
            <div
              class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
              <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
            <h3 class="font-headings text-xl font-bold text-text-primary mb-3">Reliable Delivery</h3>
            <p class="text-sm text-text-secondary leading-relaxed font-light">
              Clear milestones, staged deployments, and documented handovers. We ship on schedule with hosting,
              monitoring, and support ready from day one.
            </p>
          </div>

        </div>
      </section>

      <!-- ── Animated Product & Systems Showcase (The Proof) ── -->
      <section class="max-w-7xl mx-auto px-6 mb-24 relative z-10">
        <div class="text-center mb-16">
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 block">EXHIBIT A: OUR
            WORK</span>
          <h2 class="font-headings text-3xl sm:text-4xl font-extrabold text-text-primary tracking-tight">
            See these builds? <span
              class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent">Yeah, we made
              them.</span>
          </h2>
          <p class="text-sm text-text-secondary max-w-xl mx-auto mt-3 font-light">
            We don't just write code — we ship polished digital products, hosted platforms, and interfaces that look and perform premium.
          </p>
        </div>

        <!-- 3D-effect Hover Card Deck -->
        <div id="proof-deck" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
          <!-- Card 1: Dal Makhani -->
          <div
            class="proof-card-item group relative rounded-3xl border border-slate-200 bg-white/80 overflow-hidden hover:border-primary/40 transition-all duration-500 shadow-2xl">
            <div class="aspect-[4/5] overflow-hidden relative">
              <img src="assets/images/dal_makhani_mockup.webp" alt="Dal Makhani Packaging"
                class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent opacity-90">
              </div>

              <div class="absolute bottom-6 left-6 right-6">
                <span class="text-[10px] font-bold text-primary uppercase tracking-widest mb-1.5 block">Premium Food
                  Packaging</span>
                <h4 class="font-headings text-lg font-bold text-text-primary mb-2">Dal Makhani Luxury Pack</h4>
                <p class="text-xs text-text-secondary font-light">Sophisticated illustration-heavy packaging built for
                  premium culinary brands.</p>
              </div>
            </div>
          </div>

          <!-- Card 2: Headphone Box -->
          <div
            class="proof-card-item group relative rounded-3xl border border-slate-200 bg-white/80 overflow-hidden hover:border-primary/40 transition-all duration-500 shadow-2xl">
            <div class="aspect-[4/5] overflow-hidden relative">
              <img src="assets/images/branding_headphone_box.webp" alt="Headphone Packaging"
                class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent opacity-90">
              </div>

              <div class="absolute bottom-6 left-6 right-6">
                <span class="text-[10px] font-bold text-primary uppercase tracking-widest mb-1.5 block">Tech Brand
                  Box</span>
                <h4 class="font-headings text-lg font-bold text-text-primary mb-2">SonicBox Headphones</h4>
                <p class="text-xs text-text-secondary font-light">Minimalist packaging designed to scream luxury and high
                  build quality on the shelf.</p>
              </div>
            </div>
          </div>

          <!-- Card 3: Pasta Pack -->
          <div
            class="proof-card-item group relative rounded-3xl border border-slate-200 bg-white/80 overflow-hidden hover:border-primary/40 transition-all duration-500 shadow-2xl sm:col-span-2 lg:col-span-1">
            <div class="aspect-[4/5] overflow-hidden relative">
              <img src="assets/images/pasta_mockup.webp" alt="Pasta Packaging"
                class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent opacity-90">
              </div>

              <div class="absolute bottom-6 left-6 right-6">
                <span class="text-[10px] font-bold text-primary uppercase tracking-widest mb-1.5 block">Eco Artisan
                  Packs</span>
                <h4 class="font-headings text-lg font-bold text-text-primary mb-2">Organic Pasta Selection</h4>
                <p class="text-xs text-text-secondary font-light">Rustic styling meets modern retail aesthetics for premium
                  organic products.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- The Sarcastic Punchline -->
        <div
          class="text-center max-w-2xl mx-auto py-8 px-6 rounded-3xl border border-slate-200 bg-slate-50 backdrop-blur-sm relative overflow-hidden">
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-0.5 bg-gradient-to-r from-transparent via-primary to-transparent">
          </div>
          <h3 class="font-headings text-2xl sm:text-3xl font-extrabold text-text-primary mb-4 leading-tight">
            Yeah... that's why you should <span
              class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent">definitely choose
              us</span>.
          </h3>
          <p class="text-sm text-text-secondary mb-6 font-light max-w-md mx-auto">
            No templates, no shortcuts. Just custom development, solid hosting, and interfaces that stop users dead in their tracks. Let's
            make yours this strong.
          </p>
          <a href="contact"
            class="inline-flex items-center gap-2 px-6 py-3 font-headings text-xs font-bold text-white bg-primary rounded-xl hover:bg-secondary shadow-lg hover:shadow-primary/25 transition-all duration-300 transform active:scale-95 cursor-pointer">
            <span>Make Yours Look Exceptional</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </a>
        </div>
      </section>

      <?php
      $phases = [
        [
          "title" => "Planning",
          "subtitle" => "Without Guessing",
          "phase_num" => "01",
          "description" => "Before writing a single line of code, we map out sitemaps, goals, and architecture so the build stays clear and measurable.",
          "image" => "assets/images/process_phase1_planning.png",
          "deliverables" => ["Business Discovery", "Technical Architecture", "Interactive Wireframes", "UX Sitemapping"]
        ],
        [
          "title" => "Designing",
          "subtitle" => "Figma Wizardry",
          "phase_num" => "02",
          "description" => "Pixel-perfect interactive mockups in Figma. You review every layout detail before we write production code.",
          "image" => "assets/images/process_phase2_designing.png",
          "deliverables" => ["Custom Moodboards", "Hi-Fi Figma Design", "Interactive Prototypes", "Component System"]
        ],
        [
          "title" => "Engineering",
          "subtitle" => "Real Custom Code",
          "phase_num" => "03",
          "description" => "We write real custom code — no slow builders, no fragile plugins, and no boilerplate that breaks under growth.",
          "image" => "assets/images/process_phase3_engineering.png",
          "deliverables" => ["Pixel-Perfect Templates", "System Logic", "API Integrations", "Database Security"]
        ],
        [
          "title" => "Tuning",
          "subtitle" => "Whiplash Speed",
          "phase_num" => "04",
          "description" => "We tune speed, harden security, launch cleanly, and stay with you after go-live — not disappear once it ships.",
          "image" => "assets/images/process_phase4_tuning.png",
          "deliverables" => ["Speed Tuning", "Hosting & DNS", "Testing Log", "Security Audits"]
        ]
      ];
      ?>

      <!-- Work process -->
      <section id="process-showcase-container" class="process-section relative w-full site-section border-t border-slate-200/80 overflow-hidden">
        <div class="site-container relative z-10">
          <div class="section-header-center flex flex-col items-center text-center max-w-3xl mx-auto mb-8 md:mb-10">
            <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15">
              Our Actual Work Process
            </span>
            <h2 class="section-title mt-5 mb-3">
              How We Actually <span class="text-primary">Build Stuff</span>
            </h2>
            <p class="section-desc mx-auto">
              Four clear milestones from discovery to launch — planned, designed, engineered, then tuned for real production.
            </p>
          </div>

          <div id="process-scroll-track" class="process-grid">
            <?php foreach ($phases as $index => $phase): ?>
              <article class="process-card group">
                <div class="process-card-media">
                  <img
                    src="<?php echo htmlspecialchars($phase['image']); ?>?v=<?php echo @filemtime(__DIR__ . '/' . $phase['image']) ?: time(); ?>"
                    alt="<?php echo htmlspecialchars($phase['title']); ?>"
                    loading="lazy"
                    decoding="async"
                  />
                  <span class="process-card-badge">Phase <?php echo htmlspecialchars($phase['phase_num']); ?></span>
                </div>

                <div class="process-card-body">
                  <div class="process-card-meta">
                    <span class="process-card-step"><?php echo htmlspecialchars($phase['phase_num']); ?></span>
                    <span class="process-card-subtitle"><?php echo htmlspecialchars($phase['subtitle']); ?></span>
                  </div>
                  <h3 class="process-card-title"><?php echo htmlspecialchars($phase['title']); ?></h3>
                  <p class="process-card-desc"><?php echo htmlspecialchars($phase['description']); ?></p>

                  <div class="process-card-tags">
                    <?php foreach ($phase['deliverables'] as $item): ?>
                      <span><?php echo htmlspecialchars($item); ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <!-- Trust Metrics Section -->
      <section class="max-w-7xl mx-auto px-6 py-12">
        <div class="rounded-3xl border border-slate-200 bg-white/80 p-8 md:p-12 backdrop-blur-sm relative">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
              <span class="block font-headings text-2xl md:text-3xl font-bold text-primary mb-2">99.9%</span>
              <span class="text-xs md:text-sm font-semibold uppercase tracking-wider text-text-secondary">Uptime Score</span>
            </div>
            <div>
              <span class="block font-headings text-2xl md:text-3xl font-bold text-primary mb-2">100/100</span>
              <span class="text-xs md:text-sm font-semibold uppercase tracking-wider text-text-secondary">PageSpeed
                Performance</span>
            </div>
            <div>
              <span class="block font-headings text-2xl md:text-3xl font-bold text-primary mb-2">3.5x</span>
              <span class="text-xs md:text-sm font-semibold uppercase tracking-wider text-text-secondary">Faster Page
                Loads</span>
            </div>
            <div>
              <span class="block font-headings text-2xl md:text-3xl font-bold text-primary mb-2">100%</span>
              <span class="text-xs md:text-sm font-semibold uppercase tracking-wider text-text-secondary">Custom
                Codebases</span>
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

    // Process cards — soft entrance (grid layout, no horizontal pin)
    document.addEventListener('DOMContentLoaded', () => {
      const cards = document.querySelectorAll('.process-card');
      if (!cards.length || !window.gsap || !window.ScrollTrigger) return;

      cards.forEach((card, i) => {
        gsap.fromTo(card, {
          opacity: 0,
          y: 28
        }, {
          opacity: 1,
          y: 0,
          duration: 0.55,
          delay: i * 0.06,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: card,
            start: 'top 88%',
            toggleActions: 'play none none reverse'
          }
        });
      });
    });

    // --- The Sarcastic Showcase (The Proof) Card Animations ---
    document.addEventListener('DOMContentLoaded', () => {
      const cards = document.querySelectorAll('.proof-card-item');
      if (cards.length > 0) {
        gsap.fromTo(cards,
          { opacity: 0, y: 40 },
          {
            opacity: 1,
            y: 0,
            duration: 0.4,
            stagger: 0.10,
            ease: "power2.out",
            scrollTrigger: {
              trigger: "#proof-deck",
              start: "top 95%",
              toggleActions: "play none none reverse"
            }
          }
        );
      }
    });
  </script>
</body>

</html>