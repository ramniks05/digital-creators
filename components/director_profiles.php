<?php
require_once __DIR__ . '/../includes/content.php';
try {
  $directors_data = get_team_members('director');
} catch (Throwable $e) {
  $directors_data = [];
}
?>

<!-- Leadership Section -->
<section id="leaders" class="relative py-24 px-6 max-w-7xl mx-auto overflow-hidden">
  <div class="absolute inset-0 pointer-events-none select-none z-0 overflow-hidden">
    <div class="absolute inset-0 opacity-45"
      style="background-image: radial-gradient(#94a3b8 1.2px, transparent 1.2px); background-size: 24px 24px;"></div>
    <div class="absolute top-1/4 right-[-10%] w-[600px] h-[600px] pointer-events-none -z-10"
      style="background: radial-gradient(circle, rgba(37,99,235,0.05) 0%, rgba(37,99,235,0) 70%);"></div>
    <div class="absolute bottom-1/4 left-[-10%] w-[600px] h-[600px] pointer-events-none -z-10"
      style="background: radial-gradient(circle, rgba(34,211,238,0.05) 0%, rgba(34,211,238,0) 70%);"></div>
  </div>

  <div class="text-center mb-16 relative z-10">
    <span class="font-headings text-xs font-bold text-primary uppercase tracking-widest mb-3 block">Leadership</span>
    <h2 class="font-headings text-3xl sm:text-4xl md:text-5xl font-bold text-text-primary mb-4">
      Meet Our <span class="text-primary">Director</span>
    </h2>
    <div class="w-12 h-1 bg-primary mx-auto rounded-full"></div>
  </div>

  <div class="max-w-md mx-auto z-10 relative">
    <?php foreach ($directors_data as $dir): ?>
      <div class="team-card bg-white/80 border border-slate-200 rounded-2xl overflow-hidden flex flex-col group hover:border-primary/30 hover:bg-slate-50 transition-all duration-300 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
        <div class="relative w-full h-[420px] overflow-hidden">
          <img src="<?php echo htmlspecialchars($dir['image'] ?? ''); ?>"
            alt="<?php echo htmlspecialchars($dir['name'] ?? ''); ?>"
            class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-800 object-top" />
        </div>
        <div class="p-8 flex flex-col flex-grow items-center text-center relative z-10">
          <h3 class="font-headings text-xl sm:text-2xl font-bold mb-1.5 text-text-primary group-hover:text-primary transition-colors">
            <?php echo htmlspecialchars($dir['name'] ?? ''); ?>
          </h3>
          <p class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-4">
            <?php echo htmlspecialchars($dir['role'] ?? ''); ?>
          </p>
          <p class="text-[0.92rem] text-text-secondary leading-relaxed mb-6 flex-grow max-w-[320px] font-light">
            <?php echo htmlspecialchars($dir['bio'] ?? ''); ?>
          </p>
          <div class="flex gap-4 justify-center mt-auto w-full border-t border-slate-200 pt-5">
            <?php if (!empty($dir['email'])): ?>
              <a href="mailto:<?php echo htmlspecialchars($dir['email']); ?>" aria-label="Email"
                class="p-2 bg-slate-50 border border-slate-200 rounded text-text-secondary hover:text-primary hover:bg-primary/10 hover:border-primary/20 transition-all duration-200 cursor-pointer">
                <i data-lucide="mail" class="w-4 h-4"></i>
              </a>
            <?php endif; ?>
            <?php if (!empty($dir['phone'])): ?>
              <a href="tel:<?php echo htmlspecialchars($dir['phone']); ?>" aria-label="Phone"
                class="p-2 bg-slate-50 border border-slate-200 rounded text-text-secondary hover:text-primary hover:bg-primary/10 hover:border-primary/20 transition-all duration-200 cursor-pointer">
                <i data-lucide="phone" class="w-4 h-4"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
