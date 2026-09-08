<!-- Branding & Packaging Gallery Component -->
<section id="branding-gallery-section"
  class="relative w-full bg-bg-primary border-t border-border-brand/40 overflow-hidden py-24">
  <!-- Subtle tech pattern background -->
  <div class="absolute inset-0 opacity-[0.02] pointer-events-none select-none"
    style="background-image: radial-gradient(rgba(255,255,255,0.15) 1px, transparent 1px); background-size: 20px 20px;">
  </div>

  <div class="max-w-7xl mx-auto px-6 mb-16 relative z-10">
    <!-- Header with split layout (Title/Desc on left, button on right) -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
      <div class="max-w-xl text-left">
        <span class="text-xs font-headings font-bold text-primary tracking-[0.25em] uppercase mb-3 block">
          CREATIVE PORTFOLIO
        </span>
        <h2 class="font-headings text-4xl sm:text-5xl font-black text-text-primary tracking-tight leading-tight mb-4">
          Branding & <span
            class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-400 to-indigo-500">Packaging</span>
        </h2>
        <p class="text-text-secondary font-light text-sm sm:text-base leading-relaxed">
          Crafting memorable visual identities, luxurious physical product packaging, and cohesive design languages that
          define premium brands.
        </p>
      </div>
      <div>
        <a href="work.php"
          class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full bg-slate-50 border border-slate-200 hover:border-primary/50 text-text-primary font-semibold text-sm transition-all duration-300 group hover:shadow-[0_0_15px_rgba(37,99,235,0.15)]">
          <span>See Details</span>
          <i data-lucide="arrow-right" class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Parallax Scrolling Rows (Desktop Only) -->
  <div class="relative w-full flex flex-col gap-6 overflow-hidden py-4 select-none desktop-only">
    <!-- Row 1: Moves Left to Right on Scroll -->
    <div class="branding-gallery-row-1 flex gap-6 w-max px-6"
      style="will-change: transform; transform: translate3d(0, 0, 0); backface-visibility: hidden;">
      <?php for ($i = 1; $i <= 6; $i++): ?>
        <div
          class="branding-img-card relative w-[280px] sm:w-[360px] aspect-[4/5] rounded-[24px] sm:rounded-[32px] overflow-hidden bg-slate-50 border border-slate-200 group hover:border-primary/30 transition-all duration-300 shadow-2xl">
          <img src="assets/images/branding-<?php echo $i; ?>.webp" alt="Branding & Packaging Showcase <?php echo $i; ?>"
            loading="lazy" decoding="async"
            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
          <div
            class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 p-6 flex flex-col justify-end text-left">
            <span class="text-[10px] font-mono text-primary uppercase tracking-wider mb-1">Branding Showcase</span>
            <h4 class="font-headings text-lg font-bold text-text-primary">Visual Design Identity</h4>
          </div>
        </div>
      <?php endfor; ?>
    </div>

    <!-- Row 2: Moves Right to Left on Scroll -->
    <div class="branding-gallery-row-2 flex gap-6 w-max px-6"
      style="will-change: transform; transform: translate3d(0, 0, 0); backface-visibility: hidden;">
      <?php for ($i = 7; $i <= 12; $i++): ?>
        <div
          class="branding-img-card relative w-[280px] sm:w-[360px] aspect-[4/5] rounded-[24px] sm:rounded-[32px] overflow-hidden bg-slate-50 border border-slate-200 group hover:border-primary/30 transition-all duration-300 shadow-2xl">
          <img src="assets/images/branding-<?php echo $i; ?>.webp" alt="Branding & Packaging Showcase <?php echo $i; ?>"
            loading="lazy" decoding="async"
            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
          <div
            class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 p-6 flex flex-col justify-end text-left">
            <span class="text-[10px] font-mono text-primary uppercase tracking-wider mb-1">Packaging Showcase</span>
            <h4 class="font-headings text-lg font-bold text-text-primary">Tactile Product Case</h4>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Horizontal Slider (Mobile Only) -->
  <div class="relative w-full overflow-hidden py-4 select-none mobile-only">
    <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory px-6 scrollbar-none pb-4">
      <?php for ($i = 1; $i <= 12; $i++): ?>
        <div
          class="branding-img-card flex-shrink-0 w-[240px] aspect-[4/5] rounded-[24px] overflow-hidden bg-slate-50 border border-slate-200 snap-center relative group">
          <img src="assets/images/branding-<?php echo $i; ?>.webp" alt="Branding & Packaging Showcase <?php echo $i; ?>"
            loading="lazy" decoding="async"
            class="w-full h-full object-cover" />
          <div
            class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent p-4 flex flex-col justify-end text-left">
            <span class="text-[8px] font-mono text-primary uppercase tracking-wider mb-0.5">
              <?php echo $i <= 6 ? 'Branding Showcase' : 'Packaging Showcase'; ?>
            </span>
            <h4 class="font-headings text-sm font-bold text-text-primary">
              <?php echo $i <= 6 ? 'Visual Design Identity' : 'Tactile Product Case'; ?>
            </h4>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- GSAP Parallax Animation -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
      let mm = gsap.matchMedia();

      // Only run parallax scrolling on desktop (min-width: 1024px)
      mm.add("(min-width: 1024px)", () => {
        // Row 1 (Upper): sliding left to right
        gsap.fromTo('.branding-gallery-row-1',
          { x: '-15%' },
          {
            x: '0%',
            ease: 'none',
            force3D: true,
            scrollTrigger: {
              trigger: '#branding-gallery-section',
              start: 'top bottom',
              end: 'bottom top',
              scrub: 1
            }
          }
        );

        // Row 2 (Lower): sliding right to left
        gsap.fromTo('.branding-gallery-row-2',
          { x: '0%' },
          {
            x: '-15%',
            ease: 'none',
            force3D: true,
            scrollTrigger: {
              trigger: '#branding-gallery-section',
              start: 'top bottom',
              end: 'bottom top',
              scrub: 1
            }
          }
        );
      });
    }
  });
</script>