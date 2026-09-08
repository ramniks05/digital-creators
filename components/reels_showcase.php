<?php
$reels = [
    ['video' => 'assets/videos/robot.mp4'],
    ['video' => 'assets/videos/kulfi1.mp4'],
    ['video' => 'assets/videos/reel3rd.mp4'],
];
?>

<!-- Spotlight Reels Section -->
<section id="reels" class="w-full bg-transparent text-text-primary site-section site-section-band home-section-tint-slate border-t border-slate-200/80 overflow-hidden relative z-10">

    <div class="site-container relative z-10">
        <div class="reels-header section-header-center flex flex-col items-center">
            <span class="section-eyebrow opacity-0">Showcase Reels</span>
            <h2 class="section-title mb-3 opacity-0">
                Proof of Our <span class="text-primary">Engineering</span>
            </h2>
            <p class="section-desc mx-auto opacity-0">
                Development workflows, interface design, and product demos from our web and software projects.
            </p>
        </div>

        <div class="flex md:grid overflow-x-auto md:overflow-x-visible snap-x snap-mandatory md:snap-none md:grid-cols-3 gap-5 lg:gap-6 max-w-5xl mx-auto pb-4 md:pb-0 scrollbar-none px-2 md:px-0">
            <?php foreach ($reels as $index => $reel): ?>
                <div class="reel-card flex-shrink-0 w-[280px] md:w-auto snap-center relative aspect-[9/16] rounded-2xl overflow-hidden bg-bg-card border border-slate-200 group cursor-default shadow-md hover:border-primary/30 hover:shadow-lg transition-all duration-300 opacity-0"
                    data-index="<?php echo $index; ?>">
                    <video loop muted autoplay playsinline
                        class="w-full h-full object-cover pointer-events-none">
                        <source src="<?php echo htmlspecialchars($reel['video']); ?>" type="video/mp4" />
                    </video>
                    <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-all duration-300 z-10"></div>
                    <div class="absolute bottom-4 left-4 z-20 flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/90 border border-slate-200 text-xs font-medium text-text-secondary shadow-sm">
                        <span class="icon-advanced icon-advanced-sm !shadow-none">
                            <i data-lucide="play"></i>
                        </span>
                        Project reel
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.reel-card video').forEach((video) => {
            video.muted = true;
            video.play().catch(() => {});
        });
    });
</script>
