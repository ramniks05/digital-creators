<?php
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/media_icons.php';
try {
  $services_data = get_services();
} catch (Throwable $e) {
  $services_data = [];
}

// Keep homepage section short — full list stays on services.php
$services_data = array_slice($services_data, 0, 6);

$default_icons = ['code-2', 'layout-dashboard', 'cloud-cog', 'server', 'shield-check', 'headphones'];
?>

<!-- Services Section -->
<section id="services" class="relative site-section site-section-tight-top site-section-band services-section services-section-tint w-full overflow-hidden border-t border-slate-200/80">
  <div class="site-container relative z-10">
    <div class="services-header section-header-center flex flex-col items-center text-center max-w-3xl mx-auto">
      <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15 opacity-0">
        <?php echo media_icon_img('layers', '', 'media-icon-inline'); ?>
        What We Do
      </span>
      <h2 class="section-title mt-2.5 mb-1.5 opacity-0">
        Our <span class="text-primary">Expertise</span> in Action
      </h2>
      <p class="section-desc opacity-0 mx-auto">
        Web, apps, cloud hosting, and managed servers — built to stay fast and scalable.
      </p>
    </div>

    <div class="services-expertise-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 lg:gap-3">
      <?php foreach ($services_data as $index => $srv):
        $icon = !empty($srv['icon']) ? $srv['icon'] : $default_icons[$index % count($default_icons)];
        ?>
        <article class="service-expertise-card group opacity-0" data-index="<?php echo $index; ?>">
          <div class="service-expertise-card-inner flex flex-col gap-2">
            <div class="flex items-start gap-3 min-w-0">
              <?php echo media_icon_html($icon, $srv['title'], 'sm'); ?>
              <div class="min-w-0 flex-1">
                <span class="service-expertise-metric mb-1">
                  <?php echo htmlspecialchars($srv['metric']); ?>
                </span>
                <h3 class="card-title text-sm sm:text-base mb-1 group-hover:text-primary transition-colors">
                  <?php echo htmlspecialchars($srv['title']); ?>
                </h3>
                <p class="text-xs sm:text-[0.8125rem] text-text-secondary leading-snug font-light line-clamp-2">
                  <?php echo htmlspecialchars($srv['desc']); ?>
                </p>
              </div>
            </div>
            <a href="contact.php"
              class="service-expertise-link inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-text-primary transition-colors">
              <span>Inquire</span>
              <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"></i>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="flex justify-center mt-5 sm:mt-6">
      <a href="services.php" class="btn-secondary text-sm py-2.5 px-5">
        View all services <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</section>

<!-- Services marquee -->
<div class="section-marquee select-none hidden md:block">
  <div class="marquee-track flex whitespace-nowrap gap-10 text-sm font-semibold font-headings uppercase tracking-wide text-white/95">
    <div class="marquee-content flex gap-12 items-center animate-marquee transform-gpu will-change-transform">
      <span>Precision Engineering</span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
      <span>App Development</span>
      <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
      <span>Cloud Hosting</span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
      <span>Cloud Infrastructure</span>
      <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
      <span>Server Management</span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
      <span>Client-First Mindset</span>
      <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
    </div>
    <div class="marquee-content flex gap-12 items-center animate-marquee" aria-hidden="true">
      <span>Precision Engineering</span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
      <span>App Development</span>
      <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
      <span>Cloud Hosting</span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
      <span>Cloud Infrastructure</span>
      <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
      <span>Server Management</span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
      <span>Client-First Mindset</span>
      <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
    </div>
  </div>
</div>
