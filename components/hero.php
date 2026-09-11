<?php
require_once __DIR__ . '/../includes/media_icons.php';

$heroSlides = [
  [
    'src' => 'assets/service/custom_software.png',
    'fallback' => 'assets/service/custom_software.webp',
    'label' => 'Custom Software',
    'alt' => 'Custom Software — enterprise systems by Digital Creatorss',
  ],
  [
    'src' => 'assets/service/webdevelopment.png',
    'fallback' => 'assets/images/web_design_showcase.webp',
    'label' => 'Website Development',
    'alt' => 'Website Development — fast secure mobile-first sites',
  ],
  [
    'src' => 'assets/service/AppDevelopment.png',
    'fallback' => 'assets/service/app_development.png',
    'label' => 'App Development',
    'alt' => 'App Development — iOS and Android apps',
  ],
  [
    'src' => 'assets/service/web_applications.png',
    'fallback' => '',
    'label' => 'Web Applications',
    'alt' => 'Web Applications — cloud native SaaS platforms',
  ],
  [
    'src' => 'assets/service/cloud_hosting.png',
    'fallback' => '',
    'label' => 'Cloud Hosting',
    'alt' => 'Cloud Hosting — scalable infrastructure and uptime',
  ],
  [
    'src' => 'assets/service/server_management.png',
    'fallback' => '',
    'label' => 'Server Management',
    'alt' => 'Server Management — 24/7 operations and security',
  ],
  [
    'src' => 'assets/service/devops_infrastructure.png',
    'fallback' => '',
    'label' => 'DevOps & Infrastructure',
    'alt' => 'DevOps & Infrastructure — CI/CD and automation',
  ],
];

// Keep only slides whose image file exists
$heroSlides = array_values(array_filter($heroSlides, static function (array $slide): bool {
  $root = dirname(__DIR__) . '/';
  if (is_file($root . $slide['src'])) {
    return true;
  }
  return !empty($slide['fallback']) && is_file($root . $slide['fallback']);
}));

foreach ($heroSlides as &$slide) {
  $abs = dirname(__DIR__) . '/' . $slide['src'];
  if (!is_file($abs) && !empty($slide['fallback']) && is_file(dirname(__DIR__) . '/' . $slide['fallback'])) {
    $slide['src'] = $slide['fallback'];
  }
  $slide['ver'] = is_file(dirname(__DIR__) . '/' . $slide['src'])
    ? ('?v=' . filemtime(dirname(__DIR__) . '/' . $slide['src']))
    : '';
}
unset($slide);
?>
<!-- Hero Section -->
<section id="hero"
  class="relative flex flex-col justify-start px-4 sm:px-6 overflow-hidden bg-transparent text-text-primary site-section-band">
  <div class="hero-bottom-fade absolute bottom-0 left-0 right-0 pointer-events-none z-0" aria-hidden="true"></div>
  <canvas id="hero-particles-canvas" class="absolute inset-0 w-full h-full pointer-events-none z-0 opacity-[0.06]"></canvas>

  <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] pointer-events-none -z-10"
    style="background: radial-gradient(circle, rgba(108,117,125,0.06) 0%, transparent 70%);">
  </div>

  <div class="w-full max-w-7xl mx-auto z-10 desktop-only">
    <div class="hero-unified flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 lg:gap-3 xl:gap-5">
      <div class="hero-content flex flex-col items-start lg:w-[44%] xl:w-[42%] lg:pl-6 xl:pl-10 lg:pr-2">
      <span class="section-eyebrow hero-badge opacity-0 mb-2.5 sm:mb-3 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15">
        <?php echo media_icon_img('code-2', '', 'media-icon-inline'); ?>
        Web, Cloud &amp; Software
      </span>

      <p class="hero-title hero-display-title mb-2.5 sm:mb-3 flex flex-col items-start">
        <span class="title-block opacity-0">Web &amp; App Development</span>
        <span class="title-block text-primary opacity-0">Cloud &amp; SaaS</span>
      </p>

      <p class="hero-desc text-[0.95rem] text-text-secondary max-w-[480px] mb-4 sm:mb-5 leading-relaxed font-light opacity-0">
        Websites, apps, CRM, school ERP, and SaaS — with hosting and servers.
      </p>

      <div class="hero-cta-group flex flex-col sm:flex-row gap-3 mb-4 sm:mb-5 w-full sm:w-auto justify-start opacity-0">
        <a href="contact.php" class="btn-primary">
          Start Your Project <i data-lucide="arrow-right" class="w-[18px] h-[18px]"></i>
        </a>
        <a href="services.php" class="btn-secondary">
          Explore Services
        </a>
      </div>

      <ul class="hero-stats hero-features flex flex-wrap gap-x-5 gap-y-3 list-none p-0 justify-start">
        <li class="icon-pill-advanced opacity-0 !shadow-none">
          <?php echo media_icon_html('folder-kanban', 'Projects', 'sm'); ?>
          <span><span class="font-headings font-bold text-primary">350+</span> Projects</span>
        </li>
        <li class="icon-pill-advanced opacity-0 !shadow-none">
          <?php echo media_icon_html('building-2', 'Industries', 'sm'); ?>
          <span><span class="font-headings font-bold text-primary">10+</span> Industries</span>
        </li>
        <li class="icon-pill-advanced opacity-0 !shadow-none">
          <?php echo media_icon_html('calendar-clock', 'Years', 'sm'); ?>
          <span><span class="font-headings font-bold text-primary">5+</span> Years</span>
        </li>
      </ul>
      </div>

      <div class="hero-visual flex flex-col items-start shrink-0 lg:w-[56%] xl:w-[58%] lg:-ml-4 xl:-ml-8">
        <div class="hero-slider-shell relative w-full max-w-[620px] xl:max-w-[680px]">
          <div class="hero-visual-frame hero-slider" data-hero-slider>
            <div class="hero-slider-track">
              <?php foreach ($heroSlides as $i => $slide): ?>
                <figure class="hero-slide<?php echo $i === 0 ? ' is-active' : ''; ?>" data-slide-index="<?php echo $i; ?>">
                  <img
                    src="<?php echo htmlspecialchars($slide['src'] . $slide['ver']); ?>"
                    alt="<?php echo htmlspecialchars($slide['alt']); ?>"
                    class="hero-slide-img"
                    width="1280"
                    height="720"
                    <?php echo $i === 0 ? 'loading="eager"' : 'loading="lazy"'; ?>
                    decoding="async"
                  />
                  <figcaption class="hero-slide-caption"><?php echo htmlspecialchars($slide['label']); ?></figcaption>
                </figure>
              <?php endforeach; ?>
            </div>
            <div class="hero-slider-dots" role="tablist" aria-label="Hero service slides">
              <?php foreach ($heroSlides as $i => $slide): ?>
                <button type="button"
                  class="hero-slider-dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                  data-slide-to="<?php echo $i; ?>"
                  aria-label="<?php echo htmlspecialchars($slide['label']); ?>"
                  aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"></button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="hero-mobile w-full max-w-7xl mx-auto z-10 flex flex-col text-start mobile-only">
    <span class="section-eyebrow hero-badge opacity-0">Web, Cloud &amp; Software</span>
    <h1 class="hero-title hero-display-title flex flex-col items-start">
      <span class="title-block opacity-0">Web &amp; App Development</span>
      <span class="title-block text-primary opacity-0">Cloud &amp; SaaS</span>
    </h1>
    <p class="hero-desc text-sm sm:text-[0.95rem] text-text-secondary leading-relaxed font-light opacity-0">
      Websites, apps, CRM, ERP, and SaaS — with hosting and servers.
    </p>

    <div class="hero-visual w-full">
      <div class="hero-slider-shell relative w-full mx-auto">
        <div class="hero-visual-frame hero-slider mx-auto" data-hero-slider>
          <div class="hero-slider-track">
            <?php foreach ($heroSlides as $i => $slide): ?>
              <figure class="hero-slide<?php echo $i === 0 ? ' is-active' : ''; ?>" data-slide-index="<?php echo $i; ?>">
                <img
                  src="<?php echo htmlspecialchars($slide['src'] . $slide['ver']); ?>"
                  alt="<?php echo htmlspecialchars($slide['alt']); ?>"
                  class="hero-slide-img"
                  width="1280"
                  height="720"
                  <?php echo $i === 0 ? 'loading="eager"' : 'loading="lazy"'; ?>
                  decoding="async"
                />
                <figcaption class="hero-slide-caption"><?php echo htmlspecialchars($slide['label']); ?></figcaption>
              </figure>
            <?php endforeach; ?>
          </div>
          <div class="hero-slider-dots" role="tablist" aria-label="Hero service slides">
            <?php foreach ($heroSlides as $i => $slide): ?>
              <button type="button"
                class="hero-slider-dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                data-slide-to="<?php echo $i; ?>"
                aria-label="<?php echo htmlspecialchars($slide['label']); ?>"
                aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="hero-cta-group flex flex-col sm:flex-row gap-2.5 sm:gap-3 w-full opacity-0">
      <a href="contact.php" class="btn-primary justify-center py-3">Start Your Project</a>
      <a href="services.php" class="btn-secondary justify-center py-3">Explore Services</a>
    </div>

    <ul class="hero-stats hero-features flex flex-wrap gap-2 list-none p-0">
      <li class="icon-pill-advanced opacity-0 !shadow-none text-xs">
        <?php echo media_icon_html('folder-kanban', 'Projects', 'sm'); ?>
        <span><span class="font-headings font-bold text-primary">350+</span> Projects</span>
      </li>
      <li class="icon-pill-advanced opacity-0 !shadow-none text-xs">
        <?php echo media_icon_html('building-2', 'Industries', 'sm'); ?>
        <span><span class="font-headings font-bold text-primary">10+</span> Industries</span>
      </li>
      <li class="icon-pill-advanced opacity-0 !shadow-none text-xs">
        <?php echo media_icon_html('calendar-clock', 'Years', 'sm'); ?>
        <span><span class="font-headings font-bold text-primary">5+</span> Years</span>
      </li>
    </ul>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Hero core-service slideshow (one-by-one)
    document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
      const slides = Array.from(slider.querySelectorAll('.hero-slide'));
      const dots = Array.from(slider.querySelectorAll('.hero-slider-dot'));
      if (slides.length < 2) return;

      let index = 0;
      let timer = null;
      const INTERVAL = 2600;

      const show = (next) => {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
        dots.forEach((dot, i) => {
          const active = i === index;
          dot.classList.toggle('is-active', active);
          dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
      };

      const start = () => {
        stop();
        timer = window.setInterval(() => show(index + 1), INTERVAL);
      };
      const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
      };

      dots.forEach((dot) => {
        dot.addEventListener('click', () => {
          const to = Number(dot.getAttribute('data-slide-to') || 0);
          show(to);
          start();
        });
      });

      slider.addEventListener('mouseenter', stop);
      slider.addEventListener('mouseleave', start);
      slider.addEventListener('focusin', stop);
      slider.addEventListener('focusout', start);

      start();
    });

    const canvas = document.getElementById('hero-particles-canvas');
    if (!canvas) return;
    // Skip particle animation on phones/tablets for smoother scrolling
    if (window.matchMedia('(max-width: 1023px)').matches) {
      canvas.style.display = 'none';
      return;
    }
    const ctx = canvas.getContext('2d');

    let width = 0;
    let height = 0;
    let particles = [];
    let mouseX = -1000;
    let mouseY = -1000;
    const repulsionRadius = 110;
    const forceFactor = 3.5;
    const colors = ['rgba(52, 58, 64, ', 'rgba(108, 117, 125, ', 'rgba(173, 181, 189, '];

    class Particle {
      constructor() { this.reset(); }
      reset() {
        this.x = Math.random() * width;
        this.y = Math.random() * height;
        this.radius = Math.random() * 1.4 + 0.5;
        this.baseColor = colors[Math.floor(Math.random() * colors.length)];
        this.alpha = Math.random() * 0.35 + 0.1;
        this.vx = (Math.random() - 0.5) * 0.2;
        this.vy = (Math.random() - 0.5) * 0.2;
      }
      update() {
        let dx = this.x - mouseX;
        let dy = this.y - mouseY;
        let distance = Math.sqrt(dx * dx + dy * dy);
        if (distance < repulsionRadius) {
          const force = (repulsionRadius - distance) / repulsionRadius;
          const angle = Math.atan2(dy, dx);
          this.vx += Math.cos(angle) * force * forceFactor * 0.12;
          this.vy += Math.sin(angle) * force * forceFactor * 0.12;
        }
        this.vx *= 0.95;
        this.vy *= 0.95;
        this.x += this.vx;
        this.y += this.vy;
        if (this.x < 0 || this.x > width || this.y < 0 || this.y > height) this.reset();
      }
      draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.fillStyle = this.baseColor + this.alpha + ')';
        ctx.fill();
      }
    }

    function resize() {
      const parent = canvas.parentElement || document.getElementById('hero');
      width = parent.clientWidth;
      height = parent.clientHeight;
      canvas.width = width;
      canvas.height = height;
      const count = Math.min(70, Math.floor((width * height) / 18000));
      particles = Array.from({ length: count }, () => new Particle());
    }

    function animate() {
      ctx.clearRect(0, 0, width, height);
      particles.forEach((p) => { p.update(); p.draw(); });
      requestAnimationFrame(animate);
    }

    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', (e) => {
      const rect = canvas.getBoundingClientRect();
      mouseX = e.clientX - rect.left;
      mouseY = e.clientY - rect.top;
    });
    resize();
    animate();
  });
</script>
