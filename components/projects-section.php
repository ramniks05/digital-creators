<?php
require_once __DIR__ . '/../includes/content.php';
$projects = [];
try {
    foreach (get_projects() as $row) {
        $img = htmlspecialchars($row['image'] ?? '', ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $projects[] = [
            'title' => $row['title'],
            'category' => $row['category_label'] ?: $row['category'],
            'description' => $row['description'],
            'link' => $row['link'],
            'image' => $img,
            'image_alt' => $title,
            'bg_color' => '#ffffff',
        ];
    }
} catch (Throwable $e) {
    $projects = [];
}
?>
<section class="relative w-full border-t border-slate-200/80 overflow-hidden font-sans py-10 sm:py-14 md:py-0 md:h-screen bg-transparent site-section-band home-section-tint-slate" id="work">

    <!-- Ambient blue glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] rounded-full pointer-events-none z-0"
        style="background: radial-gradient(ellipse, rgba(14,116,144,0.1) 0%, rgba(234,88,12,0.05) 50%, transparent 70%);" aria-hidden="true"></div>

    <!-- Centered big text overlay that sits under the cards on scroll -->
    <div id="work-intro-text"
        class="relative md:absolute md:inset-0 flex flex-col items-center justify-center pointer-events-none px-6 text-center select-none mb-10 md:mb-0 z-30 section-header-center">
        <span class="section-eyebrow mb-3">Creative Portfolio</span>
        <h2 class="section-title max-w-2xl">
            Our Work of <span class="text-primary">Creation</span>
        </h2>
    </div>

    <!-- Centered Stacking Container: Expanded to allow wider cards -->
    <div
        class="w-full h-full flex flex-col md:flex-row items-center justify-center px-4 sm:px-8 md:px-12 relative z-10">

        <!-- Stacking Deck Container: Centered at max-w-[1360px] stretching to 80% viewport height -->
        <div
            class="project-deck-container relative w-full max-w-[1360px] min-h-0 h-auto md:h-[80vh] flex flex-col md:block items-center justify-center gap-8 md:gap-0 md:opacity-0 md:scale-[0.96]">
            <?php
            $featured_projects = array_slice($projects, 0, 5);
            foreach ($featured_projects as $index => $project):
                $mobileHide = $index >= 3 ? ' hidden md:block' : '';
                ?>
                <div class="project-stack-card project-deck-card relative md:absolute md:inset-0 w-full min-h-0 aspect-[16/10] md:aspect-auto md:h-full<?php echo $mobileHide; ?>"
                    style="z-index: <?php echo ($index + 1) * 10; ?>;" data-index="<?php echo $index; ?>"
                    data-bg="<?php echo $project['bg_color']; ?>">
                    <!-- Clickable Stack Card -->
                    <a href="<?php echo htmlspecialchars($project['link']); ?>" target="_blank" rel="noopener noreferrer"
                        class="project-deck-card-link absolute inset-0 bg-bg-card border border-slate-200 shadow-2xl rounded-[24px] sm:rounded-[32px] md:rounded-[40px] overflow-hidden block origin-center transition-shadow duration-300 hover:shadow-[0_20px_50px_rgba(37,99,235,0.12)] group cursor-pointer">

                        <!-- Project screenshot — fills card without overflow -->
                        <div class="project-deck-media">
                            <img src="<?php echo $project['image']; ?>" alt="<?php echo $project['image_alt']; ?>"
                                loading="lazy" decoding="async" />
                        </div>

                        <!-- Card Darkening Depth Overlay -->
                        <div
                            class="card-overlay absolute inset-0 bg-transparent transition-colors duration-300 z-10 pointer-events-none">
                        </div>

                        <!-- Category Badge (Top-Right) -->
                        <div
                            class="absolute top-3 right-3 sm:top-6 sm:right-6 md:top-8 md:right-8 px-3 py-1.5 sm:px-4 sm:py-2 bg-white/95 backdrop-blur-sm rounded-full shadow-sm flex items-center gap-2 border border-slate-200 transition-transform duration-300 group-hover:scale-105 z-20">
                            <span class="text-[10px] sm:text-xs font-bold text-slate-700 tracking-wider uppercase">
                                <?php echo htmlspecialchars($project['category']); ?>
                            </span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>

            <!-- Mobile CTA -->
            <div class="w-full md:hidden flex justify-center pt-2 pb-1">
                <a href="work.php"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-semibold text-sm shadow-lg shadow-primary/20">
                    Explore All Projects <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            <!-- Final CTA Card in the Stack -->
            <div class="project-stack-card project-deck-card hidden md:block md:absolute md:inset-0 w-full min-h-0 md:h-full aspect-[16/10] md:aspect-auto"
                style="z-index: 60;" data-index="5" data-bg="#ffffff">
                <a href="work.php"
                    class="project-deck-card-link project-deck-cta absolute inset-0 bg-gradient-to-br from-bg-card via-bg-secondary to-bg-primary border border-slate-200 shadow-2xl rounded-[32px] sm:rounded-[40px] overflow-hidden p-8 sm:p-12 group cursor-pointer">
                    <div class="project-deck-cta-inner z-10">
                        <span class="text-xs sm:text-sm font-bold text-primary tracking-[0.3em] uppercase mb-4">
                            DISCOVER MORE
                        </span>
                        <h3
                            class="font-headings text-3xl sm:text-5xl font-black text-text-primary tracking-tight leading-tight mb-4">
                            Want to see more of our <span
                                class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-accent">Creations</span>?
                        </h3>
                        <p class="text-text-secondary text-xs sm:text-sm font-light leading-relaxed mb-6 max-w-md mx-auto">
                            We've built full-stack corporate portals, non-profit platforms, e-commerce engines, and
                            cloud-hosted web systems. Explore the complete collection.
                        </p>
                        <div
                            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-primary group-hover:bg-secondary text-white font-semibold text-xs sm:text-sm transition-all duration-300 shadow-lg shadow-primary/20 group-hover:scale-105">
                            Explore All Projects <i data-lucide="arrow-right"
                                class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // GSAP Stacking Cards Animation
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {

            const mm = gsap.matchMedia();

            mm.add("(min-width: 768px)", () => {
                const cards = gsap.utils.toArray('.project-stack-card');

                // Set initial states: first card is active (yPercent: 0), next cards start off-screen below (yPercent: 100)
                cards.forEach((card, index) => {
                    if (index > 0) {
                        gsap.set(card, {
                            yPercent: 100
                        });
                    }
                });

                // Create scroll timeline with pinning
                const tl = gsap.timeline({
                    scrollTrigger: {
                        trigger: "#work",
                        start: "top top",
                        // This adds 80% of viewport height for every card + 1 for intro text fade-out
                        end: () => `+=${(cards.length + 1) * 80}%`,
                        scrub: 0.2, // Instant response to scroll
                        pin: true,
                        anticipatePin: 1,
                        invalidateOnRefresh: true
                    }
                });

                // 1. Intro Animation: translate and scale the big text, fade in the deck container
                tl.to('#work-intro-text', {
                    opacity: 0,
                    scale: 1.08,
                    yPercent: -15,
                    duration: 0.6,
                    ease: "none"
                }, 0)
                    .to('.project-deck-container', {
                        opacity: 1,
                        scale: 1,
                        duration: 0.2,
                        ease: "none"
                    }, 0);

                // 2. Card transitions (offset by 1)
                cards.forEach((card, index) => {
                    if (index < cards.length - 1) {
                        const nextCard = cards[index + 1];

                        // Previous card translates back, scales down and darkens
                        tl.to(card, {
                            scale: 0.93,
                            yPercent: -5,
                            duration: 1,
                            ease: "none"
                        }, index + 1)
                            .to(card.querySelector('.card-overlay'), {
                                backgroundColor: 'rgba(0, 0, 0, 0.35)',
                                duration: 1,
                                ease: "none"
                            }, index + 1)

                            // Next card slides up in sync
                            .to(nextCard, {
                                yPercent: 0,
                                duration: 1,
                                ease: "none"
                            }, index + 1);
                    }
                });
            });
        }
    });
</script>