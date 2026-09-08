<?php require_once __DIR__ . '/../includes/media_icons.php'; ?>
<!-- Hero Section -->
<section id="hero"
  class="min-h-[64vh] relative flex flex-col justify-start px-6 pt-[5.75rem] pb-6 overflow-hidden bg-transparent text-text-primary site-section-band">
  <div class="absolute bottom-0 left-0 right-0 h-32 pointer-events-none z-0"
    style="background: linear-gradient(to bottom, transparent, rgba(248,249,250,0.9));" aria-hidden="true"></div>
  <canvas id="hero-particles-canvas" class="absolute inset-0 w-full h-full pointer-events-none z-0 opacity-[0.06]"></canvas>

  <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] pointer-events-none -z-10"
    style="background: radial-gradient(circle, rgba(108,117,125,0.06) 0%, transparent 70%);">
  </div>

  <div class="w-full max-w-7xl mx-auto z-10 desktop-only">
    <div class="hero-unified flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 lg:gap-4 xl:gap-6">
      <div class="hero-content flex flex-col items-start lg:w-[44%] xl:w-[42%] lg:pl-6 xl:pl-10 lg:pr-2">
      <span class="section-eyebrow hero-badge opacity-0 mb-3 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15">
        <?php echo media_icon_img('code-2', '', 'media-icon-inline'); ?>
        Web, Cloud &amp; Software
      </span>

      <h1 class="hero-title hero-display-title mb-3 flex flex-col items-start">
        <span class="title-block opacity-0">Web &amp; App Development</span>
        <span class="title-block text-primary opacity-0">Cloud &amp; SaaS</span>
      </h1>

      <p class="hero-desc text-[0.95rem] text-text-secondary max-w-[480px] mb-5 leading-relaxed font-light opacity-0">
        Websites, apps, CRM, school ERP, and SaaS — with hosting and servers.
      </p>

      <div class="hero-cta-group flex flex-col sm:flex-row gap-3 mb-5 w-full sm:w-auto justify-start opacity-0">
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
        <div class="relative w-full max-w-[620px] xl:max-w-[680px]">
          <div class="hero-visual-frame">
            <img
              src="assets/images/high-tech-hero.svg"
              alt="Web development, CRM, e-commerce, and SaaS solutions by Digital Creatorss"
              class="hero-visual-svg w-full h-auto"
              width="1024"
              height="768"
              loading="eager"
              decoding="async"
              onerror="this.onerror=null;this.src='assets/images/software_engineering_mockup.webp';"
            />
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="w-full max-w-7xl mx-auto z-10 flex flex-col gap-3 text-start mobile-only">
    <span class="section-eyebrow hero-badge opacity-0">Web, Cloud &amp; Software</span>
    <h1 class="hero-title hero-display-title mb-1 flex flex-col items-start">
      <span class="title-block opacity-0">Web &amp; App Development</span>
      <span class="title-block text-primary opacity-0">Cloud &amp; SaaS</span>
    </h1>
    <p class="hero-desc text-sm text-text-secondary leading-relaxed font-light mb-2 opacity-0">
      Websites, apps, CRM, ERP, and SaaS — with hosting and servers.
    </p>

    <div class="hero-visual w-full mb-2">
      <div class="hero-visual-frame">
        <img
          src="assets/images/high-tech-hero.svg"
          alt="Web development, CRM, e-commerce, and SaaS solutions"
          class="hero-visual-svg w-full h-auto"
          width="1024"
          height="768"
          loading="eager"
          decoding="async"
          onerror="this.onerror=null;this.src='assets/images/software_engineering_mockup.webp';"
        />
      </div>
    </div>

    <div class="hero-cta-group flex flex-col gap-2 w-full opacity-0">
      <a href="contact.php" class="btn-primary justify-center py-3">Start Your Project</a>
      <a href="services.php" class="btn-secondary justify-center py-3">Explore Services</a>
    </div>

    <ul class="hero-stats hero-features flex flex-wrap gap-2 list-none p-0 mt-1">
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
    const canvas = document.getElementById('hero-particles-canvas');
    if (!canvas) return;
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
        this.x += this.vx;
        this.y += this.vy;
        const dx = this.x - mouseX;
        const dy = this.y - mouseY;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < repulsionRadius) {
          const force = (repulsionRadius - dist) / repulsionRadius;
          const angle = Math.atan2(dy, dx);
          this.x += Math.cos(angle) * force * forceFactor;
          this.y += Math.sin(angle) * force * forceFactor;
        }
        if (this.x < 0) this.x = width;
        if (this.x > width) this.x = 0;
        if (this.y < 0) this.y = height;
        if (this.y > height) this.y = 0;
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
      width = canvas.width = parent.clientWidth || window.innerWidth;
      height = canvas.height = parent.clientHeight || window.innerHeight;
      const count = Math.min(Math.floor(width / 10), 120);
      particles = [];
      for (let i = 0; i < count; i++) particles.push(new Particle());
    }

    function animate() {
      ctx.clearRect(0, 0, width, height);
      particles.forEach(p => { p.update(); p.draw(); });
      requestAnimationFrame(animate);
    }

    window.addEventListener('mousemove', (e) => {
      const rect = canvas.getBoundingClientRect();
      mouseX = e.clientX - rect.left;
      mouseY = e.clientY - rect.top;
    });
    window.addEventListener('mouseleave', () => { mouseX = -1000; mouseY = -1000; });

    resize();
    animate();
    window.addEventListener('resize', resize);
    window.addEventListener('load', resize);
  });
</script>
