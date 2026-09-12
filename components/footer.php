<?php
require_once __DIR__ . '/../includes/content.php';
$footerServices = array_slice(get_services(), 0, 6);
$email = setting('email', 'info@digitalcreatorss.com');
$address = setting('address', 'C-84, C Block, Sec-2, Noida, Uttar Pradesh');
$whatsapp = whatsapp_number();

$quickLinks = [
    ['href' => 'index.php', 'label' => 'Home', 'icon' => 'home'],
    ['href' => 'services.php', 'label' => 'Services', 'icon' => 'layers'],
    ['href' => 'products.php', 'label' => 'Products', 'icon' => 'package'],
    ['href' => 'work.php', 'label' => 'Portfolio', 'icon' => 'briefcase'],
    ['href' => 'why-us.php', 'label' => 'Why Us', 'icon' => 'award'],
    ['href' => 'team.php', 'label' => 'Our Team', 'icon' => 'users'],
    ['href' => 'blog.php', 'label' => 'Blog', 'icon' => 'newspaper'],
    ['href' => 'contact.php', 'label' => 'Contact', 'icon' => 'send'],
];

$capabilityPills = [
    ['label' => 'Web Development', 'icon' => 'globe'],
    ['label' => 'App Development', 'icon' => 'smartphone'],
    ['label' => 'Cloud Hosting', 'icon' => 'cloud'],
    ['label' => 'CRM & SaaS', 'icon' => 'boxes'],
    ['label' => 'Server Management', 'icon' => 'server'],
];
?>
<!-- Footer -->
<footer class="site-footer site-footer-dark relative border-t border-white/10 overflow-hidden" aria-label="Site footer">
  <!-- Ambient background -->
  <div class="site-footer-bg pointer-events-none select-none" aria-hidden="true">
    <div class="site-footer-grid"></div>
    <div class="site-footer-glow site-footer-glow--primary"></div>
    <div class="site-footer-glow site-footer-glow--accent"></div>
    <div class="site-footer-topline"></div>
  </div>

  <div class="site-container relative z-10 pt-12 pb-0 md:pt-14">
    <!-- CTA band -->
    <div class="footer-cta-panel mb-10 md:mb-12">
      <div class="footer-cta-panel-inner">
        <div class="footer-cta-content">
          <span class="section-eyebrow inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 border border-primary/15 mb-4">
            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
            Get Started
          </span>
          <h3 class="font-headings text-xl md:text-2xl lg:text-[1.65rem] font-bold text-white leading-tight mb-2">
            Ready to build your next <span class="text-primary">web or cloud</span> project?
          </h3>
          <p class="text-sm text-gray-400 font-light leading-relaxed max-w-xl">
            Talk to our engineering team about development, mobile apps, managed hosting, and production infrastructure.
          </p>
          <div class="footer-cta-stats flex flex-wrap gap-3 mt-5">
            <span class="footer-cta-stat">
              <i data-lucide="heart-handshake" class="w-3.5 h-3.5"></i>
              98% Client Retention
            </span>
            <span class="footer-cta-stat">
              <i data-lucide="activity" class="w-3.5 h-3.5"></i>
              99% Uptime Target
            </span>
            <span class="footer-cta-stat">
              <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
              10+ Industries
            </span>
          </div>
        </div>
        <div class="footer-cta-actions">
          <a href="contact.php" class="btn-primary footer-cta-btn">
            <i data-lucide="send" class="w-4 h-4"></i>
            Start a Project
          </a>
          <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp); ?>" target="_blank" rel="noopener noreferrer"
            class="btn-secondary footer-cta-btn">
            <i data-lucide="message-circle" class="w-4 h-4"></i>
            WhatsApp Us
          </a>
          <a href="services.php" class="footer-cta-link">
            Explore services
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Capability strip -->
    <div class="footer-cap-strip mb-10 md:mb-12" aria-label="Core capabilities">
      <?php foreach ($capabilityPills as $pill): ?>
        <span class="footer-cap-pill">
          <span class="icon-advanced icon-advanced-sm">
            <i data-lucide="<?php echo htmlspecialchars($pill['icon']); ?>" class="w-3.5 h-3.5"></i>
          </span>
          <?php echo htmlspecialchars($pill['label']); ?>
        </span>
      <?php endforeach; ?>
    </div>

    <!-- Main grid -->
    <div class="footer-main-grid mb-10 md:mb-12">
      <!-- Brand -->
      <div class="footer-brand-panel">
        <a href="index.php" class="footer-brand-link group" aria-label="Digital Creatorss Home">
          <img src="assets/images/logo-white.webp" alt="" width="160" height="40" decoding="async"
            class="h-9 w-auto object-contain group-hover:opacity-90 transition-opacity" />
          <div class="flex flex-col text-left leading-[1.1]">
            <span class="text-white text-sm font-black tracking-wide uppercase">Digital</span>
            <span class="text-primary text-[0.65rem] font-bold tracking-[0.14em] uppercase flex items-center gap-1">
              Creatorss<span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
            </span>
          </div>
        </a>
        <p class="footer-brand-desc">
          <?php echo htmlspecialchars(setting('footer_blurb', 'We engineer high-performance web applications, mobile apps, cloud hosting, and managed server infrastructure that keeps your business online and scalable.')); ?>
        </p>
        <div class="footer-social-row">
          <a href="https://linkedin.com/in/digitalcreatorss" target="_blank" rel="noopener noreferrer"
            aria-label="LinkedIn" class="footer-social-advanced group/social">
            <span class="icon-advanced icon-advanced-sm">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
            </span>
          </a>
          <a href="https://www.youtube.com/@DigitalCreators-neekita" target="_blank" rel="noopener noreferrer"
            aria-label="YouTube" class="footer-social-advanced group/social">
            <span class="icon-advanced icon-advanced-sm">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"></path><path d="m10 15 5-3-5-3z"></path></svg>
            </span>
          </a>
          <a href="https://www.instagram.com/digitalcreatorss_software/" target="_blank" rel="noopener noreferrer"
            aria-label="Instagram" class="footer-social-advanced group/social">
            <span class="icon-advanced icon-advanced-sm">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </span>
          </a>
          <a href="https://github.com" target="_blank" rel="noopener noreferrer"
            aria-label="GitHub" class="footer-social-advanced group/social">
            <span class="icon-advanced icon-advanced-sm">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
            </span>
          </a>
        </div>
      </div>

      <!-- Explore -->
      <nav class="footer-nav-col" aria-label="Quick links">
        <div class="footer-col-header">
          <span class="icon-advanced icon-advanced-sm">
            <i data-lucide="compass" class="w-3.5 h-3.5"></i>
          </span>
          <h4 class="footer-heading">Explore</h4>
        </div>
        <ul class="footer-link-list">
          <?php foreach ($quickLinks as $link): ?>
            <li>
              <a href="<?php echo htmlspecialchars($link['href']); ?>" class="footer-link-advanced">
                <i data-lucide="<?php echo htmlspecialchars($link['icon']); ?>" class="footer-link-icon w-3.5 h-3.5"></i>
                <span><?php echo htmlspecialchars($link['label']); ?></span>
                <i data-lucide="arrow-up-right" class="footer-link-arrow w-3.5 h-3.5"></i>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <!-- Services -->
      <nav class="footer-nav-col" aria-label="Services">
        <div class="footer-col-header">
          <span class="icon-advanced icon-advanced-sm">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
          </span>
          <h4 class="footer-heading">Core Services</h4>
        </div>
        <ul class="footer-link-list">
          <?php if ($footerServices): ?>
            <?php foreach ($footerServices as $svc): ?>
              <li>
                <a href="services.php" class="footer-link-advanced">
                  <i data-lucide="chevron-right" class="footer-link-icon w-3.5 h-3.5"></i>
                  <span><?php echo htmlspecialchars($svc['title']); ?></span>
                  <i data-lucide="arrow-up-right" class="footer-link-arrow w-3.5 h-3.5"></i>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="services.php" class="footer-link-advanced"><i data-lucide="chevron-right" class="footer-link-icon w-3.5 h-3.5"></i><span>Website Development</span><i data-lucide="arrow-up-right" class="footer-link-arrow w-3.5 h-3.5"></i></a></li>
            <li><a href="services.php" class="footer-link-advanced"><i data-lucide="chevron-right" class="footer-link-icon w-3.5 h-3.5"></i><span>App Development</span><i data-lucide="arrow-up-right" class="footer-link-arrow w-3.5 h-3.5"></i></a></li>
            <li><a href="services.php" class="footer-link-advanced"><i data-lucide="chevron-right" class="footer-link-icon w-3.5 h-3.5"></i><span>Cloud Hosting</span><i data-lucide="arrow-up-right" class="footer-link-arrow w-3.5 h-3.5"></i></a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <!-- Connect -->
      <div class="footer-connect-panel">
        <div class="footer-col-header">
          <span class="icon-advanced icon-advanced-sm">
            <i data-lucide="headphones" class="w-3.5 h-3.5"></i>
          </span>
          <h4 class="footer-heading">Connect</h4>
        </div>
        <div class="footer-connect-stack">
          <a href="tel:<?php echo htmlspecialchars(phone_tel()); ?>" class="footer-contact-advanced group">
            <span class="icon-advanced icon-advanced-md group-hover:translate-y-[-2px] transition-transform">
              <i data-lucide="phone" class="w-4 h-4"></i>
            </span>
            <span class="footer-contact-body">
              <span class="footer-contact-label">Phone</span>
              <span class="footer-contact-value"><?php echo htmlspecialchars(phones_display()); ?></span>
            </span>
          </a>
          <a href="mailto:<?php echo htmlspecialchars($email); ?>" class="footer-contact-advanced group">
            <span class="icon-advanced icon-advanced-md group-hover:translate-y-[-2px] transition-transform">
              <i data-lucide="mail" class="w-4 h-4"></i>
            </span>
            <span class="footer-contact-body">
              <span class="footer-contact-label">Email</span>
              <span class="footer-contact-value break-all"><?php echo htmlspecialchars($email); ?></span>
            </span>
          </a>
          <div class="footer-contact-advanced footer-contact-advanced--static">
            <span class="icon-advanced icon-advanced-md">
              <i data-lucide="map-pin" class="w-4 h-4"></i>
            </span>
            <span class="footer-contact-body">
              <span class="footer-contact-label">Office</span>
              <span class="footer-contact-value leading-snug"><?php echo htmlspecialchars($address); ?></span>
            </span>
          </div>
          <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp); ?>" target="_blank" rel="noopener noreferrer"
            class="footer-wa-card group">
            <span class="icon-advanced icon-advanced-md group-hover:translate-y-[-2px] transition-transform">
              <i data-lucide="message-circle" class="w-4 h-4"></i>
            </span>
            <span class="footer-contact-body">
              <span class="footer-contact-label">WhatsApp</span>
              <span class="footer-contact-value">Chat with our team</span>
            </span>
            <i data-lucide="arrow-up-right" class="footer-wa-arrow w-4 h-4"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Bottom bar -->
    <div class="footer-bottom-bar">
      <div class="footer-bottom-left">
        <p class="footer-copyright">
          &copy; <?php echo date('Y'); ?> Digital Creatorss
        </p>
        <span class="footer-crafted">
          <i data-lucide="map-pin" class="w-3 h-3"></i>
          Crafted with precision in India
        </span>
      </div>
      <ul class="footer-legal-row">
        <li><a href="#" class="footer-legal-pill">Privacy Policy</a></li>
        <li><a href="#" class="footer-legal-pill">Terms of Service</a></li>
        <li><a href="contact.php" class="footer-legal-pill">Support</a></li>
      </ul>
      <button type="button" class="footer-back-top" aria-label="Back to top"
        onclick="if (window.scrollToTop) { window.scrollToTop(); } else { window.scrollTo({ top: 0, behavior: 'smooth' }); }">
        <i data-lucide="arrow-up" class="w-4 h-4"></i>
        <span>Top</span>
      </button>
    </div>
  </div>
</footer>
