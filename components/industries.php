<?php
/**
 * Industries & solution types — showcased on homepage.
 */
require_once __DIR__ . '/../includes/media_icons.php';
$product_types = [
  ['icon' => 'globe-2', 'label' => 'Corporate Websites', 'tone' => 'primary'],
  ['icon' => 'smartphone', 'label' => 'App Development', 'tone' => 'teal'],
  ['icon' => 'layout-dashboard', 'label' => 'Business Portals', 'tone' => 'indigo'],
  ['icon' => 'users-round', 'label' => 'CRM Systems', 'tone' => 'orange'],
  ['icon' => 'cloud-cog', 'label' => 'SaaS Platforms', 'tone' => 'cyan'],
  ['icon' => 'shopping-bag', 'label' => 'E-Commerce', 'tone' => 'purple'],
  ['icon' => 'app-window', 'label' => 'Web Applications', 'tone' => 'green'],
];

$industries = [
  [
    'icon' => 'smartphone',
    'image' => 'assets/images/icons/icon-app.png',
    'accent' => 'app',
    'title' => 'Mobile App Development',
    'desc' => 'iOS and Android apps with secure APIs, push notifications, and polished cross-platform experiences.',
  ],
  [
    'icon' => 'user-cog',
    'image' => 'assets/images/icons/icon-software.png',
    'accent' => 'hrm',
    'title' => 'HRM & Payroll',
    'desc' => 'Employee records, attendance, leave, payroll, and workforce dashboards.',
  ],
  [
    'icon' => 'graduation-cap',
    'image' => 'assets/images/icons/icon-web.png',
    'accent' => 'school',
    'title' => 'School Management',
    'desc' => 'Admissions, fees, exams, parent portals, and academic administration.',
  ],
  [
    'icon' => 'chart-no-axes-combined',
    'title' => 'CRM & Sales',
    'desc' => 'Lead tracking, pipelines, customer communication, and reporting.',
  ],
  [
    'icon' => 'boxes',
    'title' => 'SaaS Products',
    'desc' => 'Multi-tenant apps with subscriptions, roles, and scalable architecture.',
  ],
  [
    'icon' => 'heart-pulse',
    'title' => 'Healthcare & Clinics',
    'desc' => 'Patient booking, records, billing, and clinic operations software.',
  ],
  [
    'icon' => 'warehouse',
    'title' => 'Inventory & ERP',
    'desc' => 'Stock, procurement, vendors, warehouses, and business workflows.',
  ],
  [
    'icon' => 'book-open-check',
    'title' => 'LMS & E-Learning',
    'desc' => 'Courses, assessments, student progress, and online training portals.',
  ],
  [
    'icon' => 'building',
    'title' => 'Real Estate Portals',
    'desc' => 'Property listings, lead capture, agent dashboards, and CRM integration.',
  ],
  [
    'icon' => 'truck',
    'title' => 'Logistics & Fleet',
    'desc' => 'Dispatch, tracking, vendor coordination, and operations dashboards.',
  ],
  [
    'icon' => 'wallet',
    'title' => 'Finance & Billing',
    'desc' => 'Invoicing, payment tracking, accounts, and compliance-ready workflows.',
  ],
];
?>

<!-- Industries & Solutions Section -->
<section id="industries" class="relative site-section site-section-band site-section-tight-top home-section-tint-blue w-full overflow-hidden border-t border-slate-200/80 bg-transparent">

  <div class="site-container relative z-10">
    <div class="industries-header section-header-center max-w-3xl mx-auto text-center">
      <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15">
        <?php echo media_icon_img('building-2', '', 'media-icon-inline'); ?>
        Industries &amp; Solutions
      </span>
      <h2 class="section-title mt-2.5 mb-1.5">
        Built for Every Industry
      </h2>
      <p class="section-desc mx-auto">
        Websites, portals, CRM &amp; SaaS for HRM, education, healthcare, logistics, finance, and more.
      </p>
    </div>

    <div class="flex flex-nowrap justify-start sm:justify-center gap-2.5 sm:gap-3 mb-5 lg:mb-6 industry-types overflow-x-auto scrollbar-none pb-1 -mx-1 px-1">
      <?php foreach ($product_types as $type): ?>
      <div class="icon-pill-advanced industry-type-<?php echo htmlspecialchars($type['tone'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo media_icon_html($type['icon'], $type['label'], 'sm'); ?>
        <span><?php echo htmlspecialchars($type['label']); ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="industry-bar-grid">
      <?php foreach ($industries as $ind):
        $imgSrc = htmlspecialchars($ind['image'] ?? media_icon_file($ind['icon']), ENT_QUOTES, 'UTF-8');
        ?>
      <article class="industry-bar">
        <span class="industry-bar-media">
          <img src="<?php echo $imgSrc; ?>" alt="" width="48" height="48" decoding="async" />
        </span>
        <div class="industry-bar-copy">
          <h3 class="industry-bar-title"><?php echo htmlspecialchars($ind['title']); ?></h3>
          <p class="industry-bar-desc"><?php echo htmlspecialchars($ind['desc']); ?></p>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="mt-8 p-5 md:p-6 industries-cta-panel flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex items-start gap-4">
        <?php echo media_icon_html('terminal', 'Custom development', 'lg'); ?>
        <div>
          <h3 class="card-title mb-1">Custom development for your industry</h3>
          <p class="text-sm text-text-secondary font-light max-w-xl">
            Need a portal, CRM, or SaaS product tailored to your workflow? We design, develop, host, and manage it end to end.
          </p>
        </div>
      </div>
      <a href="contact.php" class="btn-primary shrink-0 self-start md:self-center">
        Discuss Your Project
        <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</section>
