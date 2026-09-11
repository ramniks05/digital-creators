<!doctype html>
<html lang="en">

<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  render_seo_head([
    'title' => 'Insights & Industry Codes | Digital Creatorss Blog',
    'description' => 'Articles on web engineering, cloud hosting, server management, DevOps, and custom UI/UX — curated by Digital Creatorss.',
    'path' => 'blog.php',
  ]);
  ?>
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased overflow-x-hidden site-canvas">

  <!-- Custom Cursor Elements -->
  <div class="custom-cursor-dot fixed w-1.5 h-1.5 bg-primary rounded-full pointer-events-none z-[99999] opacity-0 transition-opacity duration-300"></div>
  <div class="custom-cursor-ring fixed w-8 h-8 border border-primary/45 rounded-full pointer-events-none z-[99998] opacity-0 transition-opacity duration-300"></div>

  <!-- Scroll Progress Indicator -->
  <div id="scroll-progress" class="fixed bottom-8 right-8 w-14 h-14 bg-white/80 border border-slate-200 rounded-full flex items-center justify-center cursor-pointer z-50 transition-all duration-300 opacity-0 pointer-events-none hover:border-primary hover:scale-105 shadow-[0_0_20px_rgba(0,0,0,0.4)]">
    <svg class="w-10 h-10 transform -rotate-90">
      <circle cx="20" cy="20" r="17" stroke="rgba(15,23,42,0.08)" stroke-width="2.5" fill="transparent" />
      <circle id="progress-ring" cx="20" cy="20" r="17" stroke="var(--color-primary, #007bff)" stroke-width="2.5" fill="transparent"
        stroke-dasharray="107" stroke-dashoffset="107" stroke-linecap="round" class="transition-all duration-75" />
    </svg>
    <div class="absolute inset-0 flex items-center justify-center">
      <svg class="w-4 h-4 text-text-primary" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
      </svg>
    </div>
  </div>

  <div class="w-full min-h-screen relative bg-bg-primary overflow-x-hidden">

    <!-- Navbar Section -->
    <?php include 'components/navbar.php'; ?>

    <!-- Main Content -->
    <main class="w-full pt-32 pb-24">

      <!-- ── Blog Hero Header ── -->
      <section class="max-w-7xl mx-auto px-6 mb-16 relative">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-primary/10 rounded-full blur-[140px] -z-10 pointer-events-none"></div>

        <div class="text-center max-w-3xl mx-auto pt-8">
          <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-semibold uppercase tracking-wider mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
            The Creator's Log
          </span>
          <h1 class="page-hero-title mb-4">
            Insights & <br />
            <span class="text-primary">Industry Knowledge</span>
          </h1>
          <p class="text-base sm:text-lg text-text-secondary leading-relaxed font-light max-w-2xl mx-auto">
            Deep dives into custom software architectures, cloud hosting, server management, and performance engineering written by our builders.
          </p>
        </div>
      </section>

      <!-- ── Category Navigation Tabs ── -->
      <section class="max-w-7xl mx-auto px-6 mb-12">
        <div class="flex flex-wrap items-center justify-center gap-3">
          <button data-filter="all" class="filter-tab px-6 py-2.5 rounded-full text-xs font-bold tracking-wider uppercase border transition-all duration-300 bg-primary/10 border-primary text-primary shadow-[0_0_15px_rgba(37,99,235,0.15)] cursor-pointer">
            All Articles
          </button>
          <button data-filter="engineering" class="filter-tab px-6 py-2.5 rounded-full text-xs font-bold tracking-wider uppercase border border-slate-200 text-text-secondary hover:text-text-primary hover:border-slate-300 transition-all duration-300 cursor-pointer">
            Engineering
          </button>
          <button data-filter="hosting" class="filter-tab px-6 py-2.5 rounded-full text-xs font-bold tracking-wider uppercase border border-slate-200 text-text-secondary hover:text-text-primary hover:border-slate-300 transition-all duration-300 cursor-pointer">
            Hosting & DevOps
          </button>
          <button data-filter="uiux" class="filter-tab px-6 py-2.5 rounded-full text-xs font-bold tracking-wider uppercase border border-slate-200 text-text-secondary hover:text-text-primary hover:border-slate-300 transition-all duration-300 cursor-pointer">
            UI/UX Design
          </button>
        </div>
      </section>

      <!-- ── Blog Articles Grid ── -->
      <section class="max-w-7xl mx-auto px-6 mb-24">
        <div id="blog-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php
          require_once __DIR__ . '/includes/content.php';
          $categoryLabels = [
            'engineering' => 'Engineering',
            'hosting' => 'Hosting & DevOps',
            'uiux' => 'UI/UX Design',
          ];
          try {
            $blog_posts = get_blog_posts();
          } catch (Throwable $e) {
            $blog_posts = [];
          }
          foreach ($blog_posts as $post):
            $cat = $post['category'] ?? 'engineering';
            $label = $categoryLabels[$cat] ?? ucfirst($cat);
            $dateLabel = !empty($post['published_at']) ? date('F d, Y', strtotime($post['published_at'])) : '';
          ?>
          <article class="blog-card flex flex-col bg-bg-card/40 border border-slate-200 rounded-[32px] overflow-hidden group hover:border-primary/20 transition-all duration-300 shadow-2xl" data-category="<?php echo htmlspecialchars($cat); ?>">
            <div class="relative h-56 w-full overflow-hidden bg-slate-50">
              <img src="<?php echo htmlspecialchars($post['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div class="absolute inset-0 bg-gradient-to-t from-bg-primary/85 via-transparent to-transparent"></div>
            </div>
            <div class="p-8 flex flex-col flex-grow text-left">
              <div class="flex items-center gap-3 text-[10px] font-mono tracking-wider text-slate-500 uppercase">
                <span class="text-primary font-bold"><?php echo htmlspecialchars($label); ?></span>
                <span>&bull;</span>
                <span><?php echo htmlspecialchars($post['read_time'] ?? ''); ?></span>
                <?php if ($dateLabel): ?>
                <span>&bull;</span>
                <span><?php echo htmlspecialchars($dateLabel); ?></span>
                <?php endif; ?>
              </div>
              <h3 class="font-headings text-xl font-bold text-text-primary mt-4 mb-3 group-hover:text-primary transition-colors duration-200 line-clamp-2">
                <?php echo htmlspecialchars($post['title'] ?? ''); ?>
              </h3>
              <p class="text-sm text-text-secondary leading-relaxed font-light line-clamp-3 mb-6 flex-grow">
                <?php echo htmlspecialchars($post['excerpt'] ?? ''); ?>
              </p>
              <div class="inline-flex items-center gap-1.5 text-xs font-bold text-primary tracking-wider uppercase group/link cursor-pointer mt-auto">
                <span>Read Article</span>
                <svg class="w-3.5 h-3.5 transform group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                </svg>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
</div>
      </section>

      <!-- ── Newsletter Subscription Block ── -->
      <section class="max-w-5xl mx-auto px-6">
        <div class="relative rounded-[40px] bg-gradient-to-br from-bg-card to-bg-secondary border border-slate-200 p-8 sm:p-12 md:p-16 text-center overflow-hidden shadow-2xl">
          <!-- Background accent glow -->
          <div class="absolute -right-20 -top-20 w-80 h-80 bg-primary/10 rounded-full blur-[100px] pointer-events-none"></div>
          
          <div class="relative z-10 max-w-2xl mx-auto">
            <h3 class="font-headings text-2xl sm:text-4xl font-black text-text-primary tracking-tight mb-4">
              Subscribe to the <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary via-sky-500 to-accent">Creators Matrix</span>
            </h3>
            <p class="text-text-secondary text-sm sm:text-base font-light leading-relaxed mb-8">
              Receive advanced, code-level analysis, hosting tips, and infrastructure insights directly to your inbox. No spam, ever.
            </p>
            
            <form onsubmit="event.preventDefault(); alert('Subscribed successfully!');" class="flex flex-col sm:flex-row gap-3 items-stretch justify-center max-w-lg mx-auto">
              <input type="email" placeholder="Enter your business email" required
                class="px-6 py-4 rounded-2xl bg-white border border-slate-200 text-text-primary placeholder-slate-500 text-sm focus:outline-none focus:border-primary/50 transition-colors flex-grow" />
              <button type="submit" 
                class="px-8 py-4 rounded-2xl bg-primary hover:bg-primary/90 text-white font-bold text-sm tracking-wider uppercase transition-all duration-200 cursor-pointer shadow-lg shadow-primary/20">
                Join Matrix
              </button>
            </form>
          </div>
        </div>
      </section>

    </main>

    <!-- Footer Section -->
    <?php include 'components/footer.php'; ?>
  </div>

  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- GSAP, ScrollTrigger & Lenis CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
  <script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script>

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

    // Fade-in cards with ScrollTrigger
    gsap.fromTo('.blog-card',
      { y: 30, opacity: 0 },
      {
        scrollTrigger: {
          trigger: '#blog-grid',
          start: 'top 90%',
        },
        y: 0,
        opacity: 1,
        duration: 0.8,
        stagger: 0.12,
        ease: 'power2.out'
      }
    );

    // --- Interactive Category Filtration ---
    const filterTabs = document.querySelectorAll('.filter-tab');
    const blogCards = document.querySelectorAll('.blog-card');

    filterTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        // Update active class
        filterTabs.forEach(t => {
          t.classList.remove('bg-primary/10', 'border-primary', 'text-primary', 'shadow-[0_0_15px_rgba(37,99,235,0.15)]');
          t.classList.add('border-slate-200', 'text-text-secondary');
        });
        tab.classList.add('bg-primary/10', 'border-primary', 'text-primary', 'shadow-[0_0_15px_rgba(37,99,235,0.15)]');
        tab.classList.remove('border-slate-200', 'text-text-secondary');

        const filterValue = tab.getAttribute('data-filter');

        // GSAP transition for cards
        gsap.to(blogCards, {
          opacity: 0,
          y: 20,
          duration: 0.25,
          stagger: 0.05,
          onComplete: () => {
            blogCards.forEach(card => {
              const cardCat = card.getAttribute('data-category');
              if (filterValue === 'all' || cardCat === filterValue) {
                card.style.display = 'flex';
              } else {
                card.style.display = 'none';
              }
            });

            // Reveal matching cards
            const visibleCards = Array.from(blogCards).filter(c => c.style.display !== 'none');
            gsap.fromTo(visibleCards,
              { y: 20, opacity: 0 },
              { y: 0, opacity: 1, duration: 0.4, stagger: 0.08, ease: 'power2.out' }
            );
          }
        });
      });
    });

    // Premium Custom Cursor Logic using GSAP
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
          const interactives = document.querySelectorAll('a, button, [role="button"], input, select, textarea, .cursor-pointer');
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
      const circumference = 107;

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
  </script>
</body>

</html>
