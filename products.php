<!doctype html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta charset="UTF-8" />
  <?php include 'includes/head-fonts.php'; ?>
  <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>" />
  <title>Demo Software Products | Digital Creatorss</title>
  <meta name="description"
    content="Ready-made gym, ecommerce, CRM, school, and portal software demos. Customize UI/UX for your brand with Digital Creatorss." />
</head>

<body class="bg-bg-primary text-text-primary font-sans antialiased overflow-x-hidden site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary">
    <?php include 'components/navbar.php'; ?>

    <main class="w-full pt-32 pb-20">
      <?php
      require_once __DIR__ . '/includes/demo_products.php';
      $demo_products = get_demo_products();
      $featured = get_featured_demo_product();
      ?>

      <!-- Hero -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 mb-10 sm:mb-12 relative">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-primary/10 rounded-full blur-[120px] -z-10 pointer-events-none"></div>
        <div class="text-center max-w-3xl mx-auto pt-6 sm:pt-8">
          <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15 mb-5">
            Ready Products
          </span>
          <h1 class="page-hero-title mb-4">
            Demo <span class="text-primary">Software</span> Products
          </h1>
          <p class="text-base text-text-secondary leading-relaxed font-light max-w-2xl mx-auto">
            Pre-built business platforms you can preview live. We customize UI/UX, branding, and workflows for your company — gym, ecommerce, CRM, school, tuition, and more.
          </p>
        </div>
      </section>

      <?php if ($featured): ?>
        <!-- Featured sample -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 mb-12 sm:mb-16" id="featured-product">
          <article class="product-featured grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-stretch border border-slate-200 bg-white rounded-2xl sm:rounded-3xl overflow-hidden shadow-sm">
            <div class="lg:col-span-6 relative min-h-[220px] sm:min-h-[280px] lg:min-h-0">
              <img
                src="<?php echo htmlspecialchars($featured['image']); ?>"
                alt="<?php echo htmlspecialchars($featured['title']); ?>"
                class="absolute inset-0 w-full h-full object-cover"
                loading="eager"
                decoding="async"
              />
              <span class="absolute top-4 left-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/95 border border-slate-200 text-[10px] font-bold uppercase tracking-wider text-primary">
                Live demo
              </span>
            </div>
            <div class="lg:col-span-6 p-5 sm:p-8 lg:p-10 flex flex-col justify-center">
              <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">
                <?php echo htmlspecialchars($featured['category_label']); ?>
              </span>
              <h2 class="font-headings text-2xl sm:text-3xl font-bold text-text-primary mb-3 tracking-tight">
                <?php echo htmlspecialchars($featured['title']); ?>
              </h2>
              <p class="text-sm sm:text-base text-text-secondary font-light leading-relaxed mb-5">
                <?php echo htmlspecialchars($featured['summary']); ?>
              </p>
              <?php if (!empty($featured['features'])): ?>
                <ul class="space-y-2 mb-6">
                  <?php foreach ($featured['features'] as $feature): ?>
                    <li class="flex items-start gap-2 text-sm text-text-primary">
                      <i data-lucide="check-circle-2" class="w-4 h-4 text-primary shrink-0 mt-0.5"></i>
                      <span><?php echo htmlspecialchars($feature); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
              <div class="flex flex-col sm:flex-row flex-wrap gap-3">
                <?php if (!empty($featured['demo_url'])): ?>
                  <a href="<?php echo htmlspecialchars($featured['demo_url']); ?>" target="_blank" rel="noopener noreferrer"
                    class="btn-primary justify-center">
                    View Demo <i data-lucide="external-link" class="w-4 h-4"></i>
                  </a>
                <?php endif; ?>
                <?php if (!empty($featured['guide_url'])): ?>
                  <a href="<?php echo htmlspecialchars($featured['guide_url']); ?>" target="_blank" rel="noopener noreferrer"
                    class="btn-secondary justify-center">
                    User Guide <i data-lucide="book-open" class="w-4 h-4"></i>
                  </a>
                <?php endif; ?>
                <a href="contact.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-primary hover:underline">
                  Customize for your brand
                </a>
              </div>
            </div>
          </article>
        </section>
      <?php endif; ?>

      <!-- Filters -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 mb-8 flex flex-col md:flex-row items-center justify-between gap-4 z-20 relative">
        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3" id="product-filters">
          <button type="button" class="product-filter-btn active" data-filter="all">All</button>
          <button type="button" class="product-filter-btn" data-filter="gym">Fitness</button>
          <button type="button" class="product-filter-btn" data-filter="ecommerce">E-commerce</button>
          <button type="button" class="product-filter-btn" data-filter="news">Publishing</button>
          <button type="button" class="product-filter-btn" data-filter="crm">CRM</button>
          <button type="button" class="product-filter-btn" data-filter="school">School</button>
          <button type="button" class="product-filter-btn" data-filter="tuition">Tuition</button>
        </div>
        <div class="relative w-full md:w-72">
          <input
            type="search"
            id="product-search"
            placeholder="Search products..."
            class="w-full px-5 py-2.5 rounded-full bg-white border border-slate-200 text-text-primary placeholder-slate-500 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-sm"
          />
          <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
        </div>
      </div>

      <!-- Product grid (includes featured in filterable set via data attributes on all) -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6 mb-14">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6" id="product-grid">
          <?php foreach ($demo_products as $product):
            $isLive = ($product['status'] ?? '') === 'live';
            $searchBlob = strtolower($product['title'] . ' ' . $product['summary'] . ' ' . $product['category_label']);
            ?>
            <article
              class="product-card group flex flex-col border border-slate-200 bg-white rounded-2xl overflow-hidden shadow-sm hover:border-primary/25 hover:shadow-md transition-all duration-300"
              data-category="<?php echo htmlspecialchars($product['category']); ?>"
              data-search="<?php echo htmlspecialchars($searchBlob); ?>"
              data-id="<?php echo htmlspecialchars($product['id']); ?>">
              <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                <img
                  src="<?php echo htmlspecialchars($product['image']); ?>"
                  alt="<?php echo htmlspecialchars($product['title']); ?>"
                  class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                  loading="lazy"
                  decoding="async"
                />
                <span class="absolute top-3 left-3 inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $isLive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'; ?>">
                  <?php echo $isLive ? 'Live demo' : 'Uploading soon'; ?>
                </span>
              </div>
              <div class="flex flex-col flex-1 p-4 sm:p-5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                  <?php echo htmlspecialchars($product['category_label']); ?>
                </span>
                <h3 class="font-headings text-lg font-bold text-text-primary mb-2 leading-snug">
                  <?php echo htmlspecialchars($product['title']); ?>
                </h3>
                <p class="text-sm text-text-secondary font-light leading-relaxed mb-4 flex-1">
                  <?php echo htmlspecialchars($product['summary']); ?>
                </p>
                <div class="flex flex-wrap gap-2 mt-auto">
                  <?php if ($isLive && !empty($product['demo_url'])): ?>
                    <a href="<?php echo htmlspecialchars($product['demo_url']); ?>" target="_blank" rel="noopener noreferrer"
                      class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition-colors">
                      View Demo <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    </a>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold cursor-default">
                      Demo uploading soon
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($product['guide_url'])): ?>
                    <a href="<?php echo htmlspecialchars($product['guide_url']); ?>" target="_blank" rel="noopener noreferrer"
                      class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-slate-200 text-text-secondary text-xs font-semibold hover:border-primary/40 hover:text-primary transition-colors">
                      User Guide
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <p id="product-empty" class="hidden text-center text-sm text-text-secondary py-12">
          No products match your filter. Try another category or search term.
        </p>
      </section>

      <!-- CTA -->
      <section class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="product-cta-panel rounded-2xl sm:rounded-3xl border border-slate-200 bg-slate-50 p-6 sm:p-10 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
          <div>
            <h2 class="font-headings text-xl sm:text-2xl font-bold text-text-primary mb-2">
              Need this customized for your brand?
            </h2>
            <p class="text-sm text-text-secondary font-light max-w-xl">
              We restyle UI/UX, add your workflows, and host on your domain — starting from these ready demos (150+ product templates available).
            </p>
          </div>
          <div class="flex flex-col sm:flex-row gap-3 shrink-0">
            <a href="contact.php" class="btn-primary justify-center">Talk to us</a>
            <button type="button" onclick="openContactPopup()" class="btn-secondary justify-center">Let’s Talk</button>
          </div>
        </div>
      </section>
    </main>

    <?php include 'components/footer.php'; ?>
    <?php include 'components/contact-popup.php'; ?>
  </div>

  <script src="assets/vendor/lucide.min.js"></script>
  <script>
    if (window.lucide) lucide.createIcons();

    (function () {
      const cards = Array.from(document.querySelectorAll('.product-card'));
      const empty = document.getElementById('product-empty');
      const searchInput = document.getElementById('product-search');
      const filterBtns = Array.from(document.querySelectorAll('.product-filter-btn'));
      let activeFilter = 'all';

      function applyFilters() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        let visible = 0;
        cards.forEach((card) => {
          const cat = card.getAttribute('data-category') || '';
          const hay = card.getAttribute('data-search') || '';
          const matchCat = activeFilter === 'all' || cat === activeFilter;
          const matchQ = !q || hay.includes(q);
          const show = matchCat && matchQ;
          card.classList.toggle('hidden', !show);
          if (show) visible += 1;
        });
        if (empty) empty.classList.toggle('hidden', visible > 0);
      }

      filterBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
          activeFilter = btn.getAttribute('data-filter') || 'all';
          filterBtns.forEach((b) => b.classList.toggle('active', b === btn));
          applyFilters();
        });
      });

      if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
      }
    })();
  </script>
</body>

</html>
