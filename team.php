<!doctype html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <meta charset="UTF-8" />
  <!-- Preconnect for premium Google Fonts -->
  <?php include 'includes/head-fonts.php'; ?>

  <!-- Compiled Tailwind CSS style sheet -->
  <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>" />

  <!-- SEO Meta Tags -->
  <title>Our Team | Digital Creatorss</title>
  <meta name="description"
    content="Meet the digital architects, designers, and engineers behind Digital Creatorss. We combine visual luxury with elite code." />
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased overflow-x-hidden site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary overflow-x-hidden">

    <!-- Navbar Section -->
    <?php include 'components/navbar.php'; ?>

    <!-- Main Content -->
    <main class="w-full pt-32 pb-24">

      <!-- â”€â”€ Team Hero Header â”€â”€ -->
      <section class="max-w-7xl mx-auto px-6 mb-20 relative">
        <!-- Subtle ambient radial glow behind hero -->
        <div
          class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-primary/10 rounded-full blur-[140px] -z-10 pointer-events-none">
        </div>

        <div class="text-center max-w-3xl mx-auto pt-8">
          <span
            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-semibold uppercase tracking-wider mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
            Meet the Builders
          </span>
          <h1 class="page-hero-title mb-4">
            The Minds Behind <br />
            <span class="text-primary">Our Digital Products</span>
          </h1>
          <p class="text-base sm:text-lg text-text-secondary leading-relaxed font-light max-w-2xl mx-auto">
            We are a high-performance squad of software engineers, cloud architects, and server administrators
            committed to execution speed and code precision.
          </p>
        </div>
      </section>

      <!-- â”€â”€ Leadership Directors Section â”€â”€ -->
      <section class="max-w-7xl mx-auto px-6 mb-24 relative">

        <!-- Background Illustration: Subtle Constellation Lines -->
        <div class="absolute inset-0 pointer-events-none select-none z-0 overflow-hidden" aria-hidden="true">
          <svg class="absolute inset-0 w-full h-full" style="opacity: 0.5;" viewBox="0 0 1200 500" fill="none"
            xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <line x1="100" y1="80" x2="300" y2="200" stroke="#007bff" stroke-width="0.8" />
            <line x1="300" y1="200" x2="480" y2="120" stroke="#007bff" stroke-width="0.8" />
            <line x1="480" y1="120" x2="680" y2="240" stroke="#818cf8" stroke-width="0.8" />
            <line x1="680" y1="240" x2="880" y2="100" stroke="#60a5fa" stroke-width="0.8" />
            <line x1="880" y1="100" x2="1100" y2="200" stroke="#06b6d4" stroke-width="0.8" />

            <circle cx="100" cy="80" r="3.5" fill="#007bff" />
            <circle cx="300" cy="200" r="4.5" fill="#818cf8" />
            <circle cx="480" cy="120" r="3.5" fill="#007bff" />
            <circle cx="680" cy="240" r="5" fill="#818cf8" />
            <circle cx="880" cy="100" r="3" fill="#06b6d4" />
            <circle cx="1100" cy="200" r="4" fill="#06b6d4" />
          </svg>
        </div>

        <div class="text-center mb-16 relative z-10">
          <h2 class="font-headings text-2xl sm:text-3xl md:text-4xl font-bold text-text-primary mb-4">
            Our <span class="text-primary">Director</span>
          </h2>
          <div class="w-12 h-1 bg-primary mx-auto rounded-full"></div>
        </div>

        <!-- Director Profile -->
        <?php
        require_once __DIR__ . '/includes/content.php';
        try {
          $directors_data = get_team_members('director');
          foreach ($directors_data as &$d) {
            $d['linkedin'] = $d['linkedin'] ?? 'https://linkedin.com/in/digitalcreatorss';
            $d['position'] = $d['position'] ?? 'object-center';
          }
          unset($d);
        } catch (Throwable $e) {
          $directors_data = [];
        }
?>

        <div class="max-w-md mx-auto z-10 relative">
          <?php foreach ($directors_data as $dir): ?>
            <div
              class="team-card bg-white/80 border border-slate-200 rounded-2xl overflow-hidden flex flex-col group hover:border-primary/30 hover:bg-slate-50 transition-all duration-300 relative">

              <!-- Gradient Overlay on Hover -->
              <div
                class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
              </div>

              <!-- Image Container -->
              <div class="relative w-full h-[420px] overflow-hidden">
                <img src="<?php echo htmlspecialchars($dir['image']); ?>"
                  alt="<?php echo htmlspecialchars($dir['name']); ?>"
                  class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-800 <?php echo isset($dir['position']) ? htmlspecialchars($dir['position']) : 'object-center'; ?>" />
              </div>

              <!-- Profile Details -->
              <div class="p-8 flex flex-col flex-grow items-center text-center relative z-10">
                <h3
                  class="font-headings text-xl sm:text-2xl font-bold mb-1.5 text-text-primary group-hover:text-primary transition-colors">
                  <?php echo htmlspecialchars($dir['name']); ?>
                </h3>
                <p class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-4">
                  <?php echo htmlspecialchars($dir['role']); ?>
                </p>
                <p class="text-[0.92rem] text-text-secondary leading-relaxed mb-6 flex-grow max-w-[320px] font-light">
                  <?php echo htmlspecialchars($dir['bio']); ?>
                </p>

                <!-- Social Links -->
                <div class="flex gap-4 justify-center mt-auto w-full border-t border-slate-200 pt-5">
                  <a href="<?php echo htmlspecialchars($dir['linkedin']); ?>" target="_blank" rel="noopener noreferrer"
                    aria-label="LinkedIn"
                    class="p-2 bg-slate-50 border border-slate-200 rounded text-text-secondary hover:text-primary hover:bg-primary/10 hover:border-primary/20 transition-all duration-200 cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round">
                      <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                      <rect x="2" y="9" width="4" height="12"></rect>
                      <circle cx="4" cy="4" r="2"></circle>
                    </svg>
                  </a>
                  <a href="mailto:<?php echo htmlspecialchars($dir['email']); ?>" aria-label="Email"
                    class="p-2 bg-slate-50 border border-slate-200 rounded text-text-secondary hover:text-primary hover:bg-primary/10 hover:border-primary/20 transition-all duration-200 cursor-pointer">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                  </a>
                  <a href="tel:<?php echo htmlspecialchars($dir['phone']); ?>" aria-label="Phone"
                    class="p-2 bg-slate-50 border border-slate-200 rounded text-text-secondary hover:text-primary hover:bg-primary/10 hover:border-primary/20 transition-all duration-200 cursor-pointer">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                  </a>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- â”€â”€ Engineering & Creative Team Section â”€â”€ -->
      <section class="max-w-7xl mx-auto px-6 mb-24 relative">
        <div class="text-center mb-16 relative z-10">
          <h2 class="font-headings text-2xl sm:text-3xl md:text-4xl font-bold text-text-primary mb-4">
            Core <span class="text-primary">Engineers & Creators</span>
          </h2>
          <div class="w-12 h-1 bg-primary mx-auto rounded-full"></div>
        </div>

        <?php
        try {
          $core_team_data = get_team_members('core');
        } catch (Throwable $e) {
          $core_team_data = [];
        }
?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto z-10 relative">
          <?php foreach ($core_team_data as $member): ?>
            <div
              class="team-card bg-white/80 border border-slate-200 rounded-2xl overflow-hidden flex flex-col group hover:border-primary/30 hover:bg-slate-50 transition-all duration-300 relative">

              <!-- Ambient Hover Glow -->
              <div
                class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
              </div>

              <!-- Member Photo Container -->
              <div class="relative w-full h-[250px] overflow-hidden bg-slate-50 flex items-center justify-center">
                <?php if (!empty($member['image'])): ?>
                  <img src="<?php echo htmlspecialchars($member['image']); ?>"
                    alt="<?php echo htmlspecialchars($member['name']); ?>"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-102" />
                <?php else: ?>
                  <!-- Futuristic Monogram/HUD Placeholder for Blank Photo -->
                  <div class="absolute inset-0 flex flex-col items-center justify-center p-4">
                    <!-- Subtle Tech Grid Lines in background of placeholder -->
                    <div class="absolute inset-0 opacity-[0.08]"
                      style="background-image: radial-gradient(rgba(255,255,255,0.15) 1px, transparent 1px); background-size: 12px 12px;">
                    </div>
                    <!-- Monogram Circle -->
                    <div
                      class="w-16 h-16 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary font-headings text-xl font-bold tracking-wider relative group-hover:border-primary/45 group-hover:bg-primary/20 transition-all duration-300 shadow-[0_0_15px_rgba(37,99,235,0.1)]">
                      <?php
                      $words = explode(' ', $member['name']);
                      $monogram = '';
                      foreach ($words as $w) {
                        $monogram .= strtoupper(substr($w, 0, 1));
                      }
                      echo htmlspecialchars($monogram);
                      ?>
                      <!-- Outer scanning brackets -->
                      <div class="absolute -inset-1 border border-primary/15 rounded-full scale-105 pointer-events-none">
                      </div>
                    </div>
                    <span class="text-[9px] text-slate-500 font-mono mt-3 uppercase tracking-widest">[ Hologram ID:
                      <?php echo substr(md5($member['name']), 0, 6); ?> ]</span>
                  </div>
                <?php endif; ?>

                <div
                  class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent opacity-90 pointer-events-none">
                </div>

                <!-- Floated Icon Badge -->
                <div
                  class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-white/80 border border-slate-200 flex items-center justify-center text-primary backdrop-blur-sm">
                  <i data-lucide="<?php echo $member['icon']; ?>" class="w-4 h-4"></i>
                </div>

                <!-- Specialty overlay label -->
                <div
                  class="absolute bottom-3 left-4 px-2 py-0.5 bg-primary/10 border border-primary/20 rounded text-[9px] font-semibold text-primary">
                  <?php echo htmlspecialchars($member['specialty']); ?>
                </div>
              </div>

              <!-- Member Details -->
              <div class="p-6 flex flex-col flex-grow items-center text-center">
                <h3 class="font-headings text-base font-bold text-text-primary group-hover:text-primary transition-colors mb-1">
                  <?php echo htmlspecialchars($member['name']); ?>
                </h3>
                <p class="text-[11px] text-text-secondary font-medium mb-3 uppercase tracking-wider">
                  <?php echo htmlspecialchars($member['role']); ?>
                </p>
                <p class="text-xs text-text-secondary leading-relaxed font-light mb-2 flex-grow">
                  <?php echo htmlspecialchars($member['bio']); ?>
                </p>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- â”€â”€ Careers Call To Action â”€â”€ -->
      <section class="max-w-4xl mx-auto px-6">
        <div
          class="bg-gradient-to-r from-primary/10 via-slate-100/50 to-accent/10 border border-slate-200 rounded-3xl p-8 sm:p-12 text-center relative overflow-hidden group">
          <div
            class="absolute -top-24 left-1/2 -translate-x-1/2 w-80 h-80 bg-primary/10 rounded-full blur-3xl pointer-events-none -z-10">
          </div>

          <h2 class="font-headings text-2xl sm:text-3xl font-bold mb-4 text-text-primary">
            Want to build with us?
          </h2>
          <p class="text-sm text-text-secondary leading-relaxed max-w-lg mx-auto mb-8 font-light">
            We are always looking for passionate engineers, cloud specialists, and DevOps talent who value
            quality code and reliable infrastructure.
          </p>
          <a href="contact.php"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 font-headings text-sm font-semibold rounded-full bg-primary hover:bg-primary/90 text-white transition-all duration-200 cursor-pointer shadow-md hover:shadow-primary/20">
            Send Your Resume <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </a>
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

    lenis.on('scroll', ScrollTrigger.update);

    gsap.ticker.add((time) => {
      lenis.raf(time * 1000);
    });

    gsap.ticker.lagSmoothing(0);

    // Fade-in team cards with ScrollTrigger

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
  </script>
</body>

</html>
