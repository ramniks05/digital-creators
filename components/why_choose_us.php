<?php
require_once __DIR__ . '/../includes/media_icons.php';
$stats_data = [
  ['icon' => 'heart-handshake', 'value' => 98, 'suffix' => '%', 'label' => 'Client Retention'],
  ['icon' => 'server', 'value' => 99, 'suffix' => '%', 'label' => 'Uptime Target'],
  ['icon' => 'building-2', 'value' => 10, 'suffix' => '+', 'label' => 'Industry Verticals'],
  ['icon' => 'calendar-clock', 'value' => 5, 'suffix' => '+', 'label' => 'Years Experience'],
];

$benefits_data = [
  [
    'icon' => 'badge-check',
    'title' => 'Industry Expertise',
    'desc' => 'Senior engineers, cloud architects, and server administrators across web, CRM, and SaaS builds.',
  ],
  [
    'icon' => 'shield-check',
    'title' => 'Secure Architecture',
    'desc' => 'Modern security, authentication, and hosting practices for applications and production servers.',
  ],
  [
    'icon' => 'rocket',
    'title' => 'Fast Delivery',
    'desc' => 'Agile delivery for portals, HRM, school ERP, and custom software without compromising quality.',
  ],
  [
    'icon' => 'messages-square',
    'title' => 'Clear Communication',
    'desc' => 'Staging builds, milestone updates, and transparent timelines from kickoff to deployment.',
  ],
];
?>

<!-- Why Partner With Us Section -->
<section id="why-choose-us"
  class="w-full bg-transparent text-text-primary border-t border-slate-200/80 site-section site-section-band home-section-tint-indigo why-section overflow-hidden relative z-10">
  <div class="site-container relative z-10">
    <div class="why-header section-header-center max-w-3xl mx-auto">
      <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15 opacity-0">
        <?php echo media_icon_img('award', '', 'media-icon-inline'); ?>
        Why Partner With Us
      </span>
      <h2 class="section-title mt-5 mb-4 opacity-0">
        Trusted <span class="text-primary">Engineering Partner</span>
      </h2>
      <p class="section-desc opacity-0 mx-auto">
        We build websites, mobile apps, portals, CRM, HRM, school management, and SaaS platforms —
        and manage the hosting and servers that keep them running.
      </p>
    </div>

    <div class="benefits-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5 mb-8">
      <?php foreach ($benefits_data as $index => $benefit):
        $numStr = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
        ?>
        <article class="benefit-card group opacity-0">
          <div class="benefit-card-top flex items-center justify-between gap-3 mb-4">
            <?php echo media_icon_html($benefit['icon'], $benefit['title'], 'lg'); ?>
            <span class="benefit-card-index font-headings text-xs font-semibold text-text-muted tracking-wider">
              <?php echo $numStr; ?>
            </span>
          </div>
          <h3 class="card-title text-base mb-2 group-hover:text-primary transition-colors">
            <?php echo htmlspecialchars($benefit['title']); ?>
          </h3>
          <p class="text-sm text-text-secondary leading-relaxed font-light">
            <?php echo htmlspecialchars($benefit['desc']); ?>
          </p>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="why-stats-panel opacity-0">
      <div class="why-stats-grid grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-0">
        <?php foreach ($stats_data as $stat): ?>
          <div class="stat-card flex flex-col items-center justify-center text-center px-3 py-2 lg:py-1 lg:border-l first:lg:border-l-0 why-stat-cell">
            <span class="icon-advanced icon-advanced-sm mb-3 icon-advanced-media">
              <?php echo media_icon_img($stat['icon'], $stat['label'], 'media-icon-img'); ?>
            </span>
            <div class="flex items-baseline justify-center mb-1">
              <span class="stat-num font-headings text-2xl sm:text-3xl font-bold"
                data-target="<?php echo $stat['value']; ?>">0</span>
              <span class="font-headings text-lg font-bold text-primary ml-0.5">
                <?php echo htmlspecialchars($stat['suffix']); ?>
              </span>
            </div>
            <p class="text-xs sm:text-sm font-medium text-text-secondary">
              <?php echo htmlspecialchars($stat['label']); ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
