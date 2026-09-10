<?php
$testimonials_data = [];
try {
  require_once __DIR__ . '/../includes/content.php';
  $testimonials_data = get_testimonials();
} catch (Throwable $e) {
  $testimonials_data = [];
}
?>

<!-- Testimonials Section -->
<section id="testimonials" class="relative site-section site-section-band testimonials-section home-section-tint-slate w-full overflow-hidden border-t border-slate-200/80 bg-transparent">

  <div class="site-container relative z-10">
    <div class="test-header section-header-center flex flex-col items-center">
      <span class="section-eyebrow testimonials-eyebrow opacity-0">Testimonials</span>
      <h2 class="section-title mb-4 opacity-0">
        What Our <span class="testimonials-accent">Clients Say</span>
      </h2>
      <p class="section-desc mx-auto opacity-0">
        High-quality development, cloud hosting, and server management — feedback from partners who trust us.
      </p>
    </div>

    <div
      class="relative w-full max-w-3xl mx-auto min-h-0 flex items-center justify-center z-10 test-carousel opacity-0 px-2 sm:px-4 md:px-16">
      <button id="test-prev" aria-label="Previous testimonial"
        class="test-nav-btn absolute left-0 top-1/2 -translate-y-1/2 p-2 bg-white border border-slate-200 rounded-lg text-text-secondary transition-all duration-200 z-20 hidden md:flex">
        <i data-lucide="chevron-left" class="w-[18px] h-[18px]"></i>
      </button>

      <!-- Testimonials Slides container -->
      <div id="test-slides" class="w-full relative min-h-[280px] md:min-h-[260px] flex items-stretch">
        <?php foreach ($testimonials_data as $idx => $t): ?>
          <div
            class="test-slide absolute inset-x-0 top-0 w-full transition-all duration-500 ease-in-out flex justify-center opacity-0 scale-98 pointer-events-none z-0"
            data-index="<?php echo $idx; ?>">
            <div class="testimonial-card w-full relative flex flex-col items-start group">

              <!-- Decorative Quote Icon -->
              <i data-lucide="quote"
                class="absolute right-5 md:right-12 top-5 md:top-8 w-10 h-10 md:w-12 md:h-12 testimonials-quote pointer-events-none"></i>

              <!-- Star Rating -->
              <div class="flex gap-1 mb-4 md:mb-6 testimonials-stars">
                <?php for ($i = 0; $i < $t['rating']; $i++): ?>
                  <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                <?php endfor; ?>
              </div>

              <!-- Review Text -->
              <p class="text-[0.95rem] sm:text-base md:text-lg leading-relaxed text-text-primary mb-6 md:mb-8 font-light">
                "<?php echo htmlspecialchars($t['review']); ?>"
              </p>

              <!-- Author Meta -->
              <div class="flex flex-col mt-auto">
                <span class="font-headings text-base sm:text-lg font-bold text-text-primary mb-0.5">
                  <?php echo htmlspecialchars($t['name']); ?>
                </span>
                <span class="text-xs testimonials-role">
                  <?php echo htmlspecialchars($t['role']); ?>
                </span>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Right Arrow Controls (Desktop Only) -->
      <button id="test-next" aria-label="Next testimonial"
        class="test-nav-btn absolute right-0 top-1/2 -translate-y-1/2 p-2 bg-white border border-slate-200 rounded-lg text-text-secondary transition-all duration-200 z-20 hidden md:flex">
        <i data-lucide="chevron-right" class="w-[18px] h-[18px]"></i>
      </button>
    </div>

    <!-- Dots (Mobile + Desktop indicators) -->
    <div class="flex justify-center gap-2 mt-8 z-10 relative" id="test-dots">
      <?php foreach ($testimonials_data as $idx => $t): ?>
        <button aria-label="Go to slide <?php echo $idx + 1; ?>"
          class="test-dot h-2 rounded cursor-pointer transition-all duration-200 bg-slate-300 w-2"
          data-index="<?php echo $idx; ?>"></button>
      <?php endforeach; ?>
    </div>
  </div><!-- /max-w-5xl -->
</section>