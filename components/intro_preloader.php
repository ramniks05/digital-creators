<!-- Intro Preloader — clean corporate loading -->
<div id="intro-preloader" class="fixed inset-0 z-[99999] flex flex-col items-center justify-center select-none"
  style="background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);">
  <div class="flex flex-col items-center w-full max-w-sm px-6">
    <div id="loader-elements" class="flex flex-col items-center w-full">
      <img src="assets/images/logo-white.webp" alt="Digital Creatorss" width="160" height="40"
        class="brightness-0 h-10 w-auto object-contain mb-8" />
      <div class="w-full flex flex-col items-center">
        <div class="w-full flex justify-between items-center text-xs text-text-muted mb-2 uppercase tracking-wider">
          <span class="intro-status-text">Loading</span>
          <span id="intro-percentage" class="font-semibold" style="color: #007bff;">0%</span>
        </div>
        <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
          <div id="intro-progress-bar" class="h-full w-0 rounded-full"
            style="background: linear-gradient(90deg, #007bff, #17a2b8, #fd7e14);"></div>
        </div>
      </div>
    </div>

    <div id="brand-zoom-container" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none opacity-0">
      <div class="intro-brand-wrapper flex items-center gap-4">
        <img src="assets/images/logo-white.webp" alt="Digital Creatorss" class="brightness-0 h-12 w-auto object-contain" />
        <div class="flex flex-col text-left leading-[1.1] font-headings">
          <span class="text-text-primary text-2xl font-bold tracking-tight">Digital</span>
          <span class="text-sm font-semibold tracking-wide uppercase" style="color: #fd7e14;">Creatorss</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Independent safety release: the page must never remain blocked if an
  // animation CDN, sessionStorage, or the main animation script fails.
  (() => {
    const revealPage = () => {
      const loader = document.getElementById('intro-preloader');
      if (loader) {
        loader.style.transition = 'opacity 300ms ease';
        loader.style.opacity = '0';
        loader.style.pointerEvents = 'none';
        window.setTimeout(() => loader.remove(), 320);
      }

      document.body.classList.remove('overflow-hidden');
      const revealSelectors = [
        '.hero-badge',
        '.hero-title span',
        '.hero-desc',
        '.hero-cta-group',
        '.hero-features li',
        '.hero-stats'
      ];
      document.querySelectorAll(revealSelectors.join(',')).forEach((element) => {
        element.style.opacity = '1';
        element.style.transform = 'none';
      });

      if (window.lenis && typeof window.lenis.start === 'function') {
        window.lenis.start();
      }
    };

    window.releaseIntroPreloader = revealPage;
    window.introPreloaderSafetyTimer = window.setTimeout(revealPage, 6000);
  })();
</script>
