<?php
require_once __DIR__ . '/../includes/demo_products.php';
$teaserProducts = array_slice(get_demo_products(), 0, 3);
?>
<!-- Ready products teaser -->
<section id="ready-products" class="relative site-section site-section-band site-section-tight-top home-section-tint-blue w-full overflow-hidden border-t border-slate-200/80 bg-transparent">
  <div class="site-container relative z-10">
    <div class="section-header-center flex flex-col items-center text-center max-w-3xl mx-auto mb-8">
      <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15">
        Ready Products
      </span>
      <h2 class="section-title mt-5 mb-3">
        Demo software you can <span class="text-primary">customize</span>
      </h2>
      <p class="section-desc mx-auto">
        Gym, ecommerce, CRM, school, and more — preview demos, then we brand and tailor the UI for your business.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-5 max-w-5xl mx-auto mb-8">
      <?php foreach ($teaserProducts as $product):
        $isLive = ($product['status'] ?? '') === 'live';
        ?>
        <a href="products.php<?php echo !empty($product['featured']) ? '#featured-product' : ''; ?>"
          class="group flex flex-col border border-slate-200 bg-white rounded-2xl overflow-hidden hover:border-primary/30 hover:shadow-md transition-all duration-300">
          <div class="overflow-hidden bg-white border-b border-slate-100 p-2">
            <img src="<?php echo htmlspecialchars($product['image']); ?>?v=<?php echo @filemtime(__DIR__ . '/../' . $product['image']) ?: time(); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>"
              class="w-full h-auto object-contain group-hover:scale-[1.015] transition-transform duration-500" loading="lazy" decoding="async" />
          </div>
          <div class="p-4">
            <span class="text-[10px] font-bold uppercase tracking-wider <?php echo $isLive ? 'text-primary' : 'text-slate-500'; ?>">
              <?php echo $isLive ? 'Live demo' : 'Uploading soon'; ?>
            </span>
            <h3 class="font-headings text-base font-bold text-text-primary mt-1 leading-snug">
              <?php echo htmlspecialchars($product['title']); ?>
            </h3>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="flex justify-center">
      <a href="products.php" class="btn-primary">
        View all products <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</section>
