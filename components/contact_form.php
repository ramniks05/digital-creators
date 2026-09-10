<!-- Contact Section -->
<section id="contact"
  class="relative w-full bg-transparent text-text-primary border-t border-slate-200/80 site-section site-section-band home-section-tint-indigo overflow-hidden z-10">
  <!-- Soft ambient glow -->
  <div class="absolute bottom-[-10%] right-[-10%] w-[600px] h-[600px] pointer-events-none z-0"
    style="background: radial-gradient(circle, rgba(234,88,12,0.1) 0%, rgba(2,132,199,0.06) 40%, transparent 70%);" aria-hidden="true"></div>

  <div class="site-container relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
    <!-- Left Side: Contact details -->
    <div class="contact-left lg:col-span-5 flex flex-col items-start text-left">
      <span class="section-eyebrow opacity-0">Get in Touch</span>
      <h2 class="section-title mb-4 opacity-0">
        Let's Build Something <span class="text-primary">Exceptional</span>
      </h2>
      <p class="section-desc mb-8 opacity-0">
        Ready to bring your digital vision to life? Fill out the form, and our engineering team will get back to you
        within 24 hours to schedule a free strategic call.
      </p>

      <div class="contact-list-wrapper flex flex-col gap-4 w-full">
        <!-- Phone -->
        <div class="contact-item feature-card group opacity-0 cursor-pointer !p-4">
          <span class="icon-advanced icon-advanced-md group-hover:translate-y-[-2px] transition-transform">
            <i data-lucide="phone-call"></i>
          </span>
          <div class="flex flex-col">
            <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Call Us</span>
            <a href="tel:<?php require_once __DIR__ . '/../includes/content.php'; echo htmlspecialchars(phone_tel()); ?>"
              class="text-base font-semibold text-text-primary group-hover:text-primary transition-colors duration-300 break-words">
              <?php echo htmlspecialchars(phones_display()); ?>
            </a>
          </div>
        </div>

        <!-- Email -->
        <div class="contact-item feature-card group opacity-0 cursor-pointer !p-4">
          <span class="icon-advanced icon-advanced-md group-hover:translate-y-[-2px] transition-transform">
            <i data-lucide="mail"></i>
          </span>
          <div class="flex flex-col">
            <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Email Support</span>
            <a href="mailto:<?php echo htmlspecialchars(setting('email', 'info@digitalcreatorss.com')); ?>"
              class="text-base font-semibold text-text-primary group-hover:text-primary transition-colors duration-300">
              <?php echo htmlspecialchars(setting('email', 'info@digitalcreatorss.com')); ?>
            </a>
          </div>
        </div>

        <!-- Address -->
        <div class="contact-item feature-card opacity-0 !p-4">
          <span class="icon-advanced icon-advanced-md">
            <i data-lucide="map-pinned"></i>
          </span>
          <div class="flex flex-col">
            <span class="text-[0.7rem] font-bold text-slate-500 uppercase tracking-wider mb-0.5">Location</span>
            <span class="text-base font-semibold text-text-primary">
              <?php echo htmlspecialchars(setting('address', 'C-84, C Block, Sec-2, Noida, Uttar Pradesh')); ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side: Form Card -->
    <div class="contact-right lg:col-span-7 w-full opacity-0">
      <div
        class="bg-slate-50 border border-slate-200 shadow-xl rounded-2xl p-5 sm:p-8 md:p-10 hover:border-slate-200 transition-all duration-300 relative overflow-hidden min-h-0 md:min-h-[500px] flex flex-col justify-center">

        <!-- Form Container -->
        <form id="contact-form" class="flex flex-col gap-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Full Name -->
            <div class="flex flex-col gap-1.5">
              <label class="text-[0.7rem] font-bold text-text-secondary uppercase tracking-wider">Full Name</label>
              <input type="text" name="name" id="form-name" placeholder="e.g. Rahul Sharma"
                class="w-full px-4 py-3 bg-white/80 border border-slate-200 rounded-lg font-body text-[0.92rem] text-text-primary placeholder:text-slate-500 focus:outline-none focus:border-primary/80 focus:bg-white focus:ring-1 focus:ring-primary/20 transition-all duration-300" />
              <span id="error-name" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Full Name is required</span>
              </span>
            </div>

            <!-- Email Address -->
            <div class="flex flex-col gap-1.5">
              <label class="text-[0.7rem] font-bold text-text-secondary uppercase tracking-wider">Email Address</label>
              <input type="email" name="email" id="form-email" placeholder="e.g. rahul@example.com"
                class="w-full px-4 py-3 bg-white/80 border border-slate-200 rounded-lg font-body text-[0.92rem] text-text-primary placeholder:text-slate-500 focus:outline-none focus:border-primary/80 focus:bg-white focus:ring-1 focus:ring-primary/20 transition-all duration-300" />
              <span id="error-email" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Email Address is required</span>
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Phone Number -->
            <div class="flex flex-col gap-1.5">
              <label class="text-[0.7rem] font-bold text-text-secondary uppercase tracking-wider">Phone Number</label>
              <input type="tel" name="phone" id="form-phone" placeholder="e.g. +91 9999086431"
                class="w-full px-4 py-3 bg-white/80 border border-slate-200 rounded-lg font-body text-[0.92rem] text-text-primary placeholder:text-slate-500 focus:outline-none focus:border-primary/80 focus:bg-white focus:ring-1 focus:ring-primary/20 transition-all duration-300" />
              <span id="error-phone" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Phone Number is required</span>
              </span>
            </div>

            <!-- Service Needed -->
            <div class="flex flex-col gap-1.5">
              <label class="text-[0.7rem] font-bold text-text-secondary uppercase tracking-wider">Service Needed</label>
              <div class="relative">
                <select name="service" id="form-service"
                  class="w-full px-4 py-3 bg-white/80 border border-slate-200 rounded-lg font-body text-[0.92rem] text-text-primary focus:outline-none focus:border-primary/80 focus:bg-white focus:ring-1 focus:ring-primary/20 transition-all duration-300 appearance-none cursor-pointer pr-10">
                  <option value="" disabled selected class="bg-white text-slate-600">Select a service...</option>
                  <?php
                    try {
                      if (!function_exists('get_services')) {
                        require_once __DIR__ . '/../includes/content.php';
                      }
                      foreach (get_services() as $svcOpt) {
                        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $svcOpt['title']));
                        $slug = trim($slug, '-');
                        echo '<option value="' . htmlspecialchars($slug) . '" class="bg-slate-50 text-text-primary">' . htmlspecialchars($svcOpt['title']) . '</option>';
                      }
                    } catch (Throwable $e) {
                      echo '<option value="custom-software" class="bg-slate-50 text-text-primary">Custom Software</option>';
                    }
                  ?>
                </select>
                <div
                  class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-text-secondary flex items-center">
                  <i data-lucide="chevron-down" class="w-4 h-4"></i>
                </div>
              </div>
              <span id="error-service" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
                <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Please select a service</span>
              </span>
            </div>
          </div>

          <!-- Budget Range Slider -->
          <div class="flex flex-col gap-2 mt-2">
            <div class="flex justify-between items-center text-xs font-semibold text-text-secondary">
              <span>Select Amount (10k — 10 Lakh)</span>
              <span id="form-budget-display"
                class="px-2.5 py-0.5 rounded-full bg-blue-500/15 border border-primary/20 text-primary font-bold">10K</span>
            </div>
            <div class="relative px-1 py-1">
              <input type="range" id="form-budget-slider" min="10000" max="1000000" step="10000" value="10000"
                class="popup-range" oninput="updateFormBudgetDisplay(this.value)" />
            </div>
          </div>

          <!-- Project Details -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[0.7rem] font-bold text-text-secondary uppercase tracking-wider">Project Details</label>
            <textarea name="message" id="form-message" rows="4" placeholder="Tell us about your project requirements..."
              class="w-full px-4 py-3 bg-white/80 border border-slate-200 rounded-lg font-body text-[0.92rem] text-text-primary placeholder:text-slate-500 focus:outline-none focus:border-primary/80 focus:bg-white focus:ring-1 focus:ring-primary/20 transition-all duration-300 resize-none"></textarea>
            <span id="error-message" class="text-xs text-red-400 mt-1 font-semibold hidden items-center gap-1">
              <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Message details are required</span>
            </span>
          </div>

          <!-- Submit Button -->
          <button type="submit" id="form-submit-btn"
            class="w-full mt-4 py-3.5 font-headings text-sm md:text-base font-bold text-white rounded-lg bg-gradient-to-r from-primary to-info hover:from-info hover:to-indigo transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer shadow-[0_4px_20px_rgba(0,123,255,0.25)] hover:shadow-[0_4px_25px_rgba(253,126,14,0.35)] hover:scale-[1.01] transform active:scale-[0.99]">
            <span>Send Message</span>
            <i data-lucide="send" class="w-3.5 h-3.5"></i>
          </button>
        </form>

        <!-- Submission Success Screen -->
        <div id="contact-success"
          class="flex-col items-center justify-center text-center py-12 px-6 hidden animate-fadeIn">
          <div
            class="w-16 h-16 rounded-lg bg-green-500/10 border border-green-500/20 text-green-600 flex items-center justify-center mb-6 shadow-sm animate-[pulse_2s_infinite]">
            <i data-lucide="check" class="w-7 h-7"></i>
          </div>
          <h3 class="font-headings text-2xl font-bold mb-3 text-text-primary">
            Message Sent!
          </h3>
          <p class="text-sm text-text-secondary leading-relaxed mb-8 max-w-[340px] font-light">
            Thank you for reaching out. A Senior Engineer from our team will contact you shortly.
          </p>
          <button type="button" id="success-reset-btn"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 font-headings text-xs font-semibold rounded-lg bg-slate-50 border border-slate-200 text-text-primary hover:bg-slate-100 hover:border-slate-300 transition-all duration-300 cursor-pointer">
            Send Another Message
          </button>
        </div>

      </div>
    </div>
  </div>
</section>