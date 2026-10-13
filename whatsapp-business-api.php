<!doctype html>
<html lang="en">

<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  require_once __DIR__ . '/includes/content.php';

  $pagePath = 'whatsapp-business-api.php';
  $pageTitle = 'WhatsApp Business API Integration & Automation | Digital Creatorss';
  $pageDesc = 'WhatsApp Cloud API setup for Indian businesses: verified business number, secure webhooks, admin inbox, template messages, lead capture, and enquiry automation. Noida & pan-India.';

  $jsonLd = [
    seo_organization_graph(),
    [
      '@context' => 'https://schema.org',
      '@type' => 'WebPage',
      'name' => $pageTitle,
      'description' => $pageDesc,
      'url' => seo_absolute_url($pagePath),
      'isPartOf' => ['@type' => 'WebSite', 'name' => 'Digital Creatorss', 'url' => site_base_url() . '/'],
    ],
    [
      '@context' => 'https://schema.org',
      '@type' => 'Service',
      'name' => 'WhatsApp Business API Integration',
      'description' => $pageDesc,
      'provider' => ['@type' => 'Organization', 'name' => 'Digital Creatorss', 'url' => site_base_url() . '/'],
      'areaServed' => ['@type' => 'Country', 'name' => 'India'],
      'serviceType' => 'WhatsApp Cloud API, webhook development, business messaging automation',
    ],
    [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => seo_absolute_url('index.php')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Services', 'item' => seo_absolute_url('services.php')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => 'WhatsApp Business API', 'item' => seo_absolute_url($pagePath)],
      ],
    ],
    [
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => [
        [
          '@type' => 'Question',
          'name' => 'What is WhatsApp Business API (Cloud API)?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'It is Meta\'s official API for businesses to send and receive WhatsApp messages at scale — with verified numbers, templates, delivery tracking, and automation — instead of only using the WhatsApp Business app on one phone.',
          ],
        ],
        [
          '@type' => 'Question',
          'name' => 'Do you set up webhooks and admin inbox for clients?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. Digital Creatorss configures secure webhooks, stores conversations in your database, and provides an admin portal to reply, send approved templates, and track leads — the same stack we use for our own production WhatsApp line.',
          ],
        ],
        [
          '@type' => 'Question',
          'name' => 'Can you automate enquiry replies and website form confirmations?',
          'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. We implement template-based form confirmations, optional welcome flows within Meta\'s 24-hour service window, opt-out (STOP) handling, and staff alerts — configured to your compliance and branding requirements.',
          ],
        ],
      ],
    ],
  ];

  render_seo_head([
    'title' => $pageTitle,
    'description' => $pageDesc,
    'path' => $pagePath,
    'og_type' => 'website',
    'json_ld' => $jsonLd,
  ]);
  $waDigits = whatsapp_number();
  ?>
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary">
    <?php include 'components/navbar.php'; ?>

    <main class="w-full pt-28 pb-24">
      <section class="site-container max-w-4xl mx-auto px-6 pt-8 pb-12 text-center relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-[480px] h-[240px] bg-emerald-500/10 rounded-full blur-[100px] -z-10" aria-hidden="true"></div>
        <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-600/20 text-emerald-800 mb-6">
          <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
          WhatsApp for Business
        </span>
        <h1 class="section-title mb-5">
          WhatsApp Business API <span class="text-primary">Integration &amp; Automation</span>
        </h1>
        <p class="text-base text-text-secondary leading-relaxed font-light max-w-2xl mx-auto mb-8">
          Connect your verified WhatsApp business number to your website and CRM: secure Cloud API webhooks, team inbox, template messaging, and enquiry automation — built for Indian SMEs, agencies, and growing brands.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
          <a href="contact.php" class="btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm">
            Get a proposal <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </a>
          <a href="https://wa.me/<?= htmlspecialchars($waDigits) ?>?text=<?= rawurlencode('Hi Digital Creatorss, I am interested in WhatsApp Business API integration for my business.') ?>"
            target="_blank" rel="noopener noreferrer"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm border border-emerald-600/30 bg-emerald-50 text-emerald-900 hover:bg-emerald-100 transition-colors">
            <i data-lucide="message-square" class="w-4 h-4"></i> Chat on WhatsApp
          </a>
        </div>
      </section>

      <section class="site-container max-w-6xl mx-auto px-6 mb-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php
          $features = [
            ['icon' => 'shield-check', 'title' => 'Production-ready webhooks', 'text' => 'Signature verification, idempotent message storage, delivery status tracking, and safe logging — no secrets in your public code.'],
            ['icon' => 'inbox', 'title' => 'Team inbox & replies', 'text' => 'Admin portal for chats, templates, and outbound sends aligned with Meta\'s 24-hour customer care window.'],
            ['icon' => 'zap', 'title' => 'Enquiry automation', 'text' => 'Website form → WhatsApp template confirmation, lead capture, optional staff alerts, and smart cooldowns so chats do not feel robotic.'],
            ['icon' => 'file-check', 'title' => 'Template messaging', 'text' => 'Help with approved templates (utility/marketing), variables, and sending outside the service window where policy allows.'],
            ['icon' => 'users', 'title' => 'Same stack we run live', 'text' => 'We dogfood this on digitalcreatorss.com — the integration you buy is battle-tested on our own business line.'],
            ['icon' => 'globe', 'title' => 'Website + API together', 'text' => 'Combine with our web, app, and cloud services so your forms, hosting, and WhatsApp line are one coherent project.'],
          ];
          foreach ($features as $f): ?>
          <article class="feature-card p-6 rounded-2xl border border-slate-200 bg-white/90 hover:border-primary/25 transition-colors">
            <span class="icon-advanced icon-advanced-md mb-4 text-emerald-700"><i data-lucide="<?= htmlspecialchars($f['icon']) ?>"></i></span>
            <h2 class="font-headings text-lg font-bold mb-2"><?= htmlspecialchars($f['title']) ?></h2>
            <p class="text-sm text-text-secondary leading-relaxed"><?= htmlspecialchars($f['text']) ?></p>
          </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="site-container max-w-4xl mx-auto px-6 mb-20">
        <h2 class="font-headings text-2xl font-bold text-center mb-10">How we deliver WhatsApp API projects</h2>
        <ol class="space-y-6">
          <?php
          $steps = [
            'Discovery — use cases (support, sales, notifications), volume, and compliance (opt-in, templates).',
            'Meta setup — Business Manager, WhatsApp Business Account, phone number, and Cloud API credentials.',
            'Engineering — webhook URL, database, admin inbox, template sends, and optional website form integration.',
            'Testing — sandbox numbers, template approval checks, and staff training on the admin portal.',
            'Go-live & support — monitoring, template updates, and iteration on automation rules.',
          ];
          foreach ($steps as $i => $step): ?>
          <li class="flex gap-4 items-start">
            <span class="flex-shrink-0 w-9 h-9 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center text-sm"><?= $i + 1 ?></span>
            <p class="text-text-secondary leading-relaxed pt-1"><?= htmlspecialchars($step) ?></p>
          </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <section class="site-container max-w-4xl mx-auto px-6 mb-20">
        <h2 class="font-headings text-2xl font-bold text-center mb-8">Who this service is for</h2>
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-text-secondary">
          <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600 mt-0.5"></i> E-commerce &amp; D2C brands needing order updates on WhatsApp</li>
          <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600 mt-0.5"></i> Service businesses capturing leads from website forms</li>
          <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600 mt-0.5"></i> Agencies white-labeling WhatsApp automation for clients</li>
          <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-emerald-600 mt-0.5"></i> SaaS &amp; CRM products adding WhatsApp as a channel</li>
        </ul>
      </section>

      <section class="site-container max-w-3xl mx-auto px-6 mb-16" id="faq">
        <h2 class="font-headings text-2xl font-bold text-center mb-8">Frequently asked questions</h2>
        <div class="space-y-4">
          <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer">Is this different from the free WhatsApp Business app?</summary>
            <p class="mt-3 text-sm text-text-secondary leading-relaxed">Yes. The Cloud API lets multiple agents, systems, and automations talk to customers programmatically — with templates, webhooks, and integration into your website or CRM.</p>
          </details>
          <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer">Do you help with Meta Business verification and templates?</summary>
            <p class="mt-3 text-sm text-text-secondary leading-relaxed">We guide you through WABA setup, display name, and template creation/submission. Final approval is always Meta&apos;s decision; we align payload and variables to reduce rejections.</p>
          </details>
          <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="font-semibold cursor-pointer">Can you replicate your own WhatsApp setup for my brand?</summary>
            <p class="mt-3 text-sm text-text-secondary leading-relaxed">Yes — that is the point of this offering. We implement a tailored version: your number, your templates, your branding, and your automation rules on secure hosting.</p>
          </details>
        </div>
      </section>

      <section class="site-container max-w-3xl mx-auto px-6 text-center">
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-8 shadow-sm">
          <h2 class="font-headings text-xl font-bold mb-3">Ready to add WhatsApp API to your business?</h2>
          <p class="text-sm text-text-secondary mb-6">Tell us your industry, message volume, and whether you need website integration. We will share scope, timeline, and pricing.</p>
          <a href="contact.php" class="btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm">Contact our team</a>
        </div>
      </section>
    </main>

    <?php include 'components/footer.php'; ?>
  </div>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>if (window.lucide) lucide.createIcons();</script>
</body>

</html>
