
<?php
  // Detect active page for nav highlighting
  $current_uri = $_SERVER['REQUEST_URI'];
  $current_page = basename(parse_url($current_uri, PHP_URL_PATH), '.php');
  // Normalise: index or empty → 'index'
  if ($current_page === '' || $current_page === 'DC') $current_page = 'index';
?>
<!-- Navbar Component -->
<style>
  /* Unifying transitions on the main navigation bar */
  #main-nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    max-width: 100%;
    margin-left: auto;
    margin-right: auto;
    height: 5.5rem;
    background-color: rgba(255, 255, 255, 0.92);
    border: none;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0;
    box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);
    backdrop-filter: blur(10px);
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  }

  /* Scrolled state — inner pages keep subtle compact bar */
  #main-nav.scrolled {
    height: 4.25rem;
    background-color: rgba(255, 255, 255, 0.96);
    border-bottom: 1px solid rgba(15, 23, 42, 0.1);
    box-shadow: 0 4px 18px -10px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(12px);
  }

  /* Transparent state overrides (when NOT scrolled) across all pages */
  #main-nav:not(.scrolled) .text-text-primary {
    color: #0f172a !important;
  }
  #main-nav:not(.scrolled) .text-text-secondary {
    color: #334155 !important;
  }
  #main-nav:not(.scrolled) .text-text-secondary:hover {
    color: #0f172a !important;
  }
  #main-nav:not(.scrolled) #mobile-menu-btn {
    color: #0f172a !important;
  }

  /* Scrolled active state overrides across all pages */
  #main-nav.scrolled .text-text-primary {
    color: #0f172a !important;
  }
  #main-nav.scrolled .text-text-secondary {
    color: #334155 !important;
  }
  #main-nav.scrolled .text-text-secondary:hover {
    color: #0f172a !important;
  }
  #main-nav.scrolled #mobile-menu-btn {
    color: #0f172a !important;
  }

  /* Active nav link: full-width underline + dark text */
  .nav-link.nav-active {
    color: #0f172a !important;
  }
  .nav-link.nav-active::after {
    width: 100% !important;
  }
  .nav-link-mobile.nav-active {
    color: #007bff !important;
  }
</style>
<nav id="main-nav" class="z-[1000] flex items-center">
  <div class="relative z-[999] w-full max-w-7xl mx-auto px-6 flex justify-between items-center">
    <!-- Logo -->
    <a href="index.php" aria-label="Digital Creatorss Home Page" class="font-headings cursor-pointer flex items-center gap-3 select-none">
      <img src="assets/images/logo-white.webp" alt="Digital Creatorss Logo" width="180" height="44" fetchpriority="high" decoding="async" class="h-10 sm:h-11 w-auto object-contain brightness-0" />
      <div class="flex flex-col text-left leading-[1.1]">
        <span class="text-text-primary text-[1.1rem] sm:text-[1.25rem] font-black tracking-wide uppercase">Digital</span>
        <span class="text-primary text-[0.8rem] sm:text-[0.9rem] font-bold tracking-[0.12em] uppercase flex items-center gap-0.5">
          Creatorss<span class="w-1.5 h-1.5 bg-primary rounded-full inline-block"></span>
        </span>
      </div>
    </a>

    <!-- Desktop Nav Links -->
    <div class="hidden lg:flex items-center gap-6 xl:gap-10">
      <?php $base_classes = "nav-link text-[1.05rem] font-semibold text-text-secondary cursor-pointer hover:text-text-primary transition-all duration-200 relative py-1.5 after:content-[''] after:absolute after:bottom-0 after:left-1/2 after:-translate-x-1/2 after:w-0 after:h-[2px] after:bg-primary after:transition-all after:duration-200 hover:after:w-full"; ?>
      <a href="services.php" class="<?= $base_classes ?> <?= $current_page === 'services' ? 'nav-active' : '' ?>">Services</a>
      <a href="work.php" class="<?= $base_classes ?> <?= $current_page === 'work' ? 'nav-active' : '' ?>">Portfolio</a>
      <a href="why-us.php" class="<?= $base_classes ?> <?= $current_page === 'why-us' ? 'nav-active' : '' ?>">Why Us</a>
      <a href="team.php" class="<?= $base_classes ?> <?= $current_page === 'team' ? 'nav-active' : '' ?>">Our Team</a>
      <a href="contact.php" class="<?= $base_classes ?> <?= $current_page === 'contact' ? 'nav-active' : '' ?>">Contact</a>
      <button onclick="openContactPopup()" aria-label="Open Contact Form" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 font-headings text-sm font-bold rounded-full bg-primary hover:bg-primary/90 text-white transition-all duration-200 cursor-pointer shadow-md hover:shadow-primary/20">
        Let's Talk <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
      </button>
    </div>
 
    <!-- Mobile Menu Icon -->
    <div id="mobile-menu-btn" role="button" tabindex="0" aria-label="Toggle Navigation Menu" class="lg:hidden text-text-primary cursor-pointer hover:opacity-80 transition-opacity">
      <i data-lucide="menu" id="menu-icon" class="w-[24px] h-[24px]"></i>
      <i data-lucide="x" id="close-icon" class="w-[24px] h-[24px] hidden"></i>
    </div>
  </div>
</nav>

  <!-- Mobile Navigation Drawer -->
  <div id="mobile-drawer" class="lg:hidden fixed inset-0 overflow-hidden flex flex-col items-center justify-center transition-all duration-300 opacity-0 pointer-events-none z-[997]" style="background-color: rgba(255, 255, 255, 0.98); position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 997;">
    <div class="flex flex-col items-center w-full -mt-12">
      <?php $mob_classes = "nav-link-mobile w-full text-center font-headings text-3xl font-bold text-text-secondary cursor-pointer hover:text-text-primary transition-all duration-200 py-4 border-b border-slate-200"; ?>
      <a href="services.php" class="<?= $mob_classes ?> <?= $current_page === 'services' ? 'nav-active' : '' ?>">Services</a>
      <a href="work.php" class="<?= $mob_classes ?> <?= $current_page === 'work' ? 'nav-active' : '' ?>">Portfolio</a>
      <a href="why-us.php" class="<?= $mob_classes ?> <?= $current_page === 'why-us' ? 'nav-active' : '' ?>">Why Us</a>
      <a href="team.php" class="<?= $mob_classes ?> <?= $current_page === 'team' ? 'nav-active' : '' ?>">Our Team</a>
      <a href="contact.php" class="<?= $mob_classes ?> <?= $current_page === 'contact' ? 'nav-active' : '' ?>">Contact</a>
      <div class="mt-8 w-full flex justify-center">
        <button onclick="openContactPopup()" class="w-4/5 max-w-[280px] inline-flex items-center justify-center gap-2 px-5 py-3.5 font-headings text-base font-semibold rounded-xl bg-primary text-white hover:bg-primary/90 transition-all duration-200 cursor-pointer shadow-md">
          Let's Talk <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
        </button>
  </div>
</div>
</div>

<script>
  // Ensure basic navbar interactions are initialized globally
  function initNavbar() {
    const nav = document.getElementById('main-nav');
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const menuIcon = document.getElementById('menu-icon');
    const closeIcon = document.getElementById('close-icon');
    const mobileDrawer = document.getElementById('mobile-drawer');
    let isOpen = false;

    function updateNavbar() {
      if (nav) {
        if (window.scrollY > 20 && !isOpen) {
          nav.classList.add('scrolled');
        } else {
          nav.classList.remove('scrolled');
        }
      }
    }

    window.addEventListener('scroll', updateNavbar, { passive: true });
    updateNavbar(); // run once initial

    if (mobileBtn && !mobileBtn.dataset.initialized) {
      mobileBtn.dataset.initialized = "true";
      mobileBtn.addEventListener('click', () => {
        isOpen = !isOpen;
        const currentMenuIcon = document.getElementById('menu-icon');
        const currentCloseIcon = document.getElementById('close-icon');
        if (isOpen) {
          if (currentMenuIcon) currentMenuIcon.classList.add('hidden');
          if (currentCloseIcon) currentCloseIcon.classList.remove('hidden');
          if (mobileDrawer) {
            mobileDrawer.classList.remove('opacity-0', 'pointer-events-none');
            mobileDrawer.classList.add('opacity-100', 'pointer-events-auto');
          }
        } else {
          if (currentCloseIcon) currentCloseIcon.classList.add('hidden');
          if (currentMenuIcon) currentMenuIcon.classList.remove('hidden');
          if (mobileDrawer) {
            mobileDrawer.classList.remove('opacity-100', 'pointer-events-auto');
            mobileDrawer.classList.add('opacity-0', 'pointer-events-none');
          }
        }
        updateNavbar();
      });
    }

    // Ensure scrollToSection helper exists globally
    window.scrollToSection = function (id) {
      // Close mobile drawer if open
      if (isOpen && mobileBtn) {
        // Trigger click if open
        mobileBtn.click();
      }

      // Check current page name
      const path = window.location.pathname;
      const currentPageName = path.substring(path.lastIndexOf('/') + 1).replace('.php', '') || 'index';

      // Mapping IDs to pages
      const pageMapping = {
        'services-page': 'services.php',
        'work-page': 'work.php',
        'why-us-page': 'why-us.php',
        'team-page': 'team.php',
        'contact-page': 'contact.php'
      };

      if (pageMapping[id]) {
        const targetPage = pageMapping[id];
        const targetPageBase = targetPage.replace('.php', '');
        
        if (currentPageName === targetPageBase) {
          // Scroll to top of current page
          if (window.lenis) {
            window.lenis.scrollTo(0);
          } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }
        } else {
          // Redirect to target page
          window.location.href = targetPage;
        }
        return;
      }

      const element = document.getElementById(id);
      if (element) {
        if (window.lenis) {
          window.lenis.scrollTo(element);
        } else {
          const offset = 80;
          const bodyRect = document.body.getBoundingClientRect().top;
          const elementRect = element.getBoundingClientRect().top;
          const elementPosition = elementRect - bodyRect;
          const offsetPosition = elementPosition - offset;

          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
          });
        }
      } else {
        // Fallback redirect to index page with hash
        window.location.href = 'index.php#' + id;
      }
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavbar);
  } else {
    initNavbar();
  }
</script>

<?php include __DIR__ . '/contact-popup.php'; ?>
