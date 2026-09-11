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
<section id="services" class="relative site-section site-section-tight-top site-section-band services-section services-section-tint w-full overflow-hidden border-t border-slate-200/80">
  <div class="site-container relative z-10">
    <!-- Header -->
    <div class="services-header section-header-center flex flex-col items-center text-center max-w-3xl mx-auto">
      <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15 opacity-0">
        <?php echo media_icon_img('layers', '', 'media-icon-inline'); ?>
        What We Do
      </span>
      <h2 class="section-title mt-3 mb-2 opacity-0">
        Our <span class="text-primary">Expertise</span> in Action
      </h2>
      <p class="section-desc opacity-0 mx-auto">
        We combine engineering excellence with reliable infrastructure — secure hosting, managed servers,
        and software that stays fast, scalable, and ready for growth.
      </p>
    </div>

    <!-- Service cards -->
    <div class="services-expertise-grid grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
      <?php foreach ($services_data as $index => $srv):
        $icon = !empty($srv['icon']) ? $srv['icon'] : $default_icons[$index % count($default_icons)];
        $isFeatured = ($index === 0);
        $image = !empty($srv['image']) ? $srv['image'] : '';
        if ($image) {
          $imgAbs = dirname(__DIR__) . '/' . $image;
          $imageSrc = $image . (is_file($imgAbs) ? ('?v=' . filemtime($imgAbs)) : '');
        } else {
          $imageSrc = '';
        }
        ?>
        <article
          class="service-expertise-card group opacity-0 <?php echo $isFeatured ? 'service-expertise-card-featured md:col-span-2' : ''; ?>"
          data-index="<?php echo $index; ?>">

          <?php if ($isFeatured): ?>
          <div class="service-expertise-featured-layout">
            <div class="service-expertise-card-inner flex flex-col gap-3">
              <div class="flex items-start gap-4 flex-1 min-w-0">
                <?php echo media_icon_html($icon, $srv['title'], 'md'); ?>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-2">
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

              <div class="services-featured-highlights flex flex-wrap gap-2">
                <span class="services-highlight-pill"><?php echo media_icon_img('globe-2', '', 'media-icon-inline'); ?> Web &amp; Portals</span>
                <span class="services-highlight-pill"><?php echo media_icon_img('smartphone', '', 'media-icon-inline'); ?> App Development</span>
                <span class="services-highlight-pill"><?php echo media_icon_img('cloud', '', 'media-icon-inline'); ?> Cloud &amp; SaaS</span>
                <span class="services-highlight-pill"><?php echo media_icon_img('server', '', 'media-icon-inline'); ?> Hosting &amp; DevOps</span>
              </div>

              <a href="contact.php"
                class="service-expertise-link inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-text-primary transition-colors">
                <span>Inquire about this service</span>
                <i data-lucide="arrow-up-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"></i>
              </a>
            </div>
            <?php if ($imageSrc): ?>
            <div class="service-expertise-featured-media">
              <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($srv['title']); ?>" loading="lazy" decoding="async" />
            </div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="service-expertise-card-inner flex flex-col gap-3">
            <?php if ($imageSrc): ?>
            <div class="service-expertise-thumb">
              <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($srv['title']); ?>" loading="lazy" decoding="async" />
            </div>
            <?php endif; ?>
            <div class="flex items-start gap-4 flex-1 min-w-0">
              <?php echo media_icon_html($icon, $srv['title'], 'md'); ?>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-2">
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

            <a href="contact.php"
              class="service-expertise-link inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-text-primary transition-colors">
              <span>Inquire about this service</span>
              <i data-lucide="arrow-up-right" class="w-4 h-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"></i>
            </a>
          </div>
          <?php endif; ?>
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
