<?php
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/media_icons.php';
try {
  $services_data = get_services();
} catch (Throwable $e) {
  $services_data = [];
}

$default_icons = ['code-2', 'layout-dashboard', 'cloud-cog', 'server', 'shield-check', 'headphones'];
?>

<!-- Services Section -->
<section id="services" class="relative site-section site-section-tight-top site-section-band services-section w-full overflow-hidden border-t border-slate-200/80 bg-transparent">
  <div class="site-container relative z-10">
    <!-- Header -->
    <div class="services-header section-header-center flex flex-col items-center text-center max-w-3xl mx-auto">
      <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15 opacity-0">
        <?php echo media_icon_img('layers', '', 'media-icon-inline'); ?>
        What We Do
      </span>
      <h2 class="section-title mt-5 mb-4 opacity-0">
        Our <span class="text-primary">Expertise</span> in Action
      </h2>
      <p class="section-desc opacity-0 mx-auto">
        We combine engineering excellence with reliable infrastructure — secure hosting, managed servers,
        and software that stays fast, scalable, and ready for growth.
      </p>
    </div>

    <!-- Service cards -->
    <div class="services-expertise-grid grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-5">
      <?php foreach ($services_data as $index => $srv):
        $numStr = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
        $icon = !empty($srv['icon']) ? $srv['icon'] : $default_icons[$index % count($default_icons)];
        $isFeatured = ($index === 0);
        ?>
        <article
          class="service-expertise-card group opacity-0 <?php echo $isFeatured ? 'service-expertise-card-featured md:col-span-2' : ''; ?>"
          data-index="<?php echo $index; ?>">
          <div class="service-expertise-card-inner flex flex-col sm:flex-row sm:items-start gap-5 <?php echo $isFeatured ? 'sm:items-center' : ''; ?>">
            <div class="flex items-start gap-4 flex-1 min-w-0">
              <?php echo media_icon_html($icon, $srv['title'], 'lg'); ?>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-2">
                  <span class="service-expertise-index font-headings text-xs font-semibold text-text-muted tracking-wider">
                    <?php echo $numStr; ?>
                  </span>
                  <span class="service-expertise-metric">
                    <?php echo htmlspecialchars($srv['metric']); ?>
                  </span>
                </div>
                <h3 class="card-title text-base sm:text-lg mb-2 group-hover:text-primary transition-colors">
                  <?php echo htmlspecialchars($srv['title']); ?>
                </h3>
                <p class="text-sm text-text-secondary leading-relaxed font-light">
                  <?php echo htmlspecialchars($srv['desc']); ?>
                </p>
              </div>
            </div>

            <?php if ($isFeatured): ?>
            <div class="services-featured-highlights flex flex-wrap gap-2 sm:flex-col sm:items-end shrink-0">
              <span class="services-highlight-pill"><?php echo media_icon_img('globe-2', '', 'media-icon-inline'); ?> Web &amp; Portals</span>
              <span class="services-highlight-pill"><?php echo media_icon_img('smartphone', '', 'media-icon-inline'); ?> App Development</span>
              <span class="services-highlight-pill"><?php echo media_icon_img('cloud', '', 'media-icon-inline'); ?> Cloud &amp; SaaS</span>
              <span class="services-highlight-pill"><?php echo media_icon_img('server', '', 'media-icon-inline'); ?> Hosting &amp; DevOps</span>
            </div>
            <?php endif; ?>
          </div>

          <a href="contact.php"
            class="service-expertise-link inline-flex items-center gap-2 mt-5 text-sm font-semibold text-primary hover:text-text-primary transition-colors">
            <span>Inquire about this service</span>
            <i data-lucide="arrow-up-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"></i>
          </a>
        </article>
      <?php endforeach; ?>
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
