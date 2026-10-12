<?php
/**
 * @var string $legalTitle
 * @var string $legalUpdated
 * @var string $legalBodyHtml Raw HTML sections (already escaped where built in caller)
 */
?>
<main class="w-full pt-32 pb-24">
  <section class="max-w-3xl mx-auto px-6">
    <h1 class="page-hero-title mb-3"><?= htmlspecialchars($legalTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="text-sm text-text-secondary mb-10">Last updated: <?= htmlspecialchars($legalUpdated, ENT_QUOTES, 'UTF-8') ?></p>
    <div class="legal-prose space-y-8 text-text-secondary leading-relaxed font-light text-sm md:text-base">
      <?= $legalBodyHtml ?>
    </div>
  </section>
</main>
