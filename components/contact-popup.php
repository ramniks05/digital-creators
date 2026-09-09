<!-- Contact Popup Modal Component -->
<div id="contact-popup-modal"
  class="fixed inset-0 z-[99999] opacity-0 pointer-events-none transition-all duration-300 flex items-center justify-center p-3 sm:p-5">
  <!-- Backdrop Overlay -->
  <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm cursor-pointer" onclick="closeContactPopup()"></div>

  <!-- Modal Card -->
  <div
    class="contact-popup-card relative w-full max-w-4xl bg-white border border-slate-200 rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl scale-95 transition-transform duration-300 flex flex-col md:flex-row z-10">

    <!-- Left Column: Contact Details -->
    <div class="popup-info-panel hidden md:flex w-full md:w-5/12 p-7 lg:p-8 flex-col justify-between relative">
      <!-- Glow decoration -->
      <div class="absolute top-0 right-0 w-32 h-32 bg-slate-100 rounded-full blur-2xl pointer-events-none"></div>

      <div>
        <span class="popup-kicker">Digital Creatorss</span>
        <h2 class="font-headings text-2xl font-bold tracking-tight leading-tight mb-3">Let’s build something great.</h2>
        <p class="popup-info-copy font-light text-sm leading-relaxed mb-7">
          Tell us what you need and our team will get back to you within one business day.
        </p>

        <div class="flex flex-col gap-5">
          <!-- Phone Numbers -->
          <a href="tel:<?php require_once __DIR__ . '/../includes/content.php'; echo htmlspecialchars(phone_tel()); ?>" class="group flex items-start gap-4">
            <div
              class="p-2.5 bg-slate-100 border border-slate-300 rounded-xl group-hover:scale-105 transition-transform duration-300">
              <i data-lucide="phone" class="w-4 h-4 text-text-primary"></i>
            </div>
            <div>
              <span class="block text-[9px] font-bold text-text-primary/50 uppercase tracking-wider mb-0.5">Call Us</span>
              <span class="text-sm font-semibold text-text-primary group-hover:underline"><?php echo htmlspecialchars(phones_display()); ?></span>
            </div>
          </a>

          <!-- Email Address -->
          <a href="mailto:sales@digitalcreatorss.com" class="group flex items-start gap-4">
            <div
              class="p-2.5 bg-slate-100 border border-slate-300 rounded-xl group-hover:scale-105 transition-transform duration-300">
              <i data-lucide="mail" class="w-4 h-4 text-text-primary"></i>
            </div>
            <div>
              <span class="block text-[9px] font-bold text-text-primary/50 uppercase tracking-wider mb-0.5">Email</span>
              <span class="text-sm font-semibold text-text-primary group-hover:underline">sales@digitalcreatorss.com</span>
            </div>
          </a>

          <!-- Office Address -->
          <div class="flex items-start gap-4">
            <div class="p-2.5 bg-slate-100 border border-slate-300 rounded-xl">
              <i data-lucide="map-pin" class="w-4 h-4 text-text-primary"></i>
            </div>
            <div>
              <span class="block text-[9px] font-bold text-text-primary/50 uppercase tracking-wider mb-0.5">Address</span>
              <span class="text-sm font-semibold text-text-primary">C-84, C Block, Sec-2, Noida, Uttar Pardesh</span>
            </div>
          </div>

          <!-- WhatsApp Chat -->
          <a href="https://wa.me/<?php echo htmlspecialchars(whatsapp_number()); ?>?text=Hello%20Digital%20Creatorss%2C%20I%20would%20like%20to%20get%20in%20touch%20with%20you%20regarding%20your%20services."
            target="_blank" class="group flex items-start gap-4">
            <div
              class="p-2.5 bg-slate-100 border border-slate-300 rounded-xl group-hover:scale-105 transition-transform duration-300">
              <i data-lucide="message-square" class="w-4 h-4 text-text-primary"></i>
            </div>
            <div>
              <span class="block text-[9px] font-bold text-text-primary/50 uppercase tracking-wider mb-0.5">WhatsApp</span>
              <span class="text-sm font-semibold text-text-primary group-hover:underline">Message on Whatsapp</span>
            </div>
          </a>
        </div>
      </div>

      <!-- Social Media Links -->
      <div class="mt-12 md:mt-0">
        <span class="block text-[10px] font-bold text-text-primary/50 uppercase tracking-wider mb-3">Follow us on :</span>
        <div class="flex items-center gap-3">
          <a href="https://linkedin.com/in/digitalcreatorss" target="_blank" rel="noopener noreferrer"
            class="social-link" aria-label="Follow us on LinkedIn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
              <rect x="2" y="9" width="4" height="12"></rect>
              <circle cx="4" cy="4" r="2"></circle>
            </svg>
          </a>
          <a href="https://twitter.com/digitalcreatorss" target="_blank" rel="noopener noreferrer" class="social-link"
            aria-label="Follow us on Twitter">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <path
                d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z">
              </path>
            </svg>
          </a>
          <a href="https://instagram.com/digitalcreatorss" target="_blank" rel="noopener noreferrer" class="social-link"
            aria-label="Follow us on Instagram">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
              <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
            </svg>
          </a>
          <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-link"
            aria-label="Follow us on Facebook">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
            </svg>
          </a>
        </div>
      </div>
    </div>

    <!-- Right Column: Project Enquiry Form -->
    <div class="popup-form-panel w-full md:w-7/12 bg-white p-5 sm:p-7 lg:p-8 flex flex-col relative overflow-y-auto">
      <!-- Close Button -->
      <button type="button" onclick="closeContactPopup()" class="popup-close-btn" aria-label="Close contact form">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>

      <!-- Form Content -->
      <div id="popup-form-wrapper">
        <div class="popup-form-heading mb-5 pr-10">
          <span class="section-eyebrow !mb-1">Project enquiry</span>
          <h2 class="font-headings text-xl sm:text-2xl font-bold text-text-primary">Let’s Talk</h2>
        </div>

        <form id="popup-contact-form" class="flex flex-col gap-3">
          <!-- Full Name -->
          <div class="popup-field-group">
            <div class="popup-icon-container">
              <i data-lucide="user" class="w-4.5 h-4.5"></i>
            </div>
            <input type="text" id="popup-name" aria-label="Enter your full name" placeholder="Enter your full name" class="popup-input" />
            <span id="popup-error-name" class="popup-error-msg">
              <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Full Name is required</span>
            </span>
          </div>

          <!-- Email Address -->
          <div class="popup-field-group">
            <div class="popup-icon-container">
              <i data-lucide="mail" class="w-4.5 h-4.5"></i>
            </div>
            <input type="email" id="popup-email" aria-label="Enter your email address" placeholder="Enter your email address" class="popup-input" />
            <span id="popup-error-email" class="popup-error-msg">
              <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Email Address is required</span>
            </span>
          </div>

          <!-- Mobile Number -->
          <div class="popup-field-group">
            <div class="popup-icon-container">
              <i data-lucide="phone" class="w-4.5 h-4.5"></i>
            </div>
            <input type="tel" id="popup-phone" aria-label="Enter your mobile number" placeholder="Mobile Number" class="popup-input" />
            <span id="popup-error-phone" class="popup-error-msg">
              <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Mobile Number is required</span>
            </span>
          </div>

          <!-- Message -->
          <div class="popup-field-group">
            <div class="popup-icon-container">
              <i data-lucide="message-square" class="w-4.5 h-4.5"></i>
            </div>
            <textarea id="popup-message" aria-label="Enter your message details" rows="3" placeholder="Message" class="popup-textarea"></textarea>
            <span id="popup-error-message" class="popup-error-msg">
              <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Message is required</span>
            </span>
          </div>

          <!-- Select Services -->
          <div class="popup-field-group">
            <div class="popup-icon-container">
              <i data-lucide="briefcase" class="w-4.5 h-4.5"></i>
            </div>
            <select id="popup-service" class="popup-select" aria-label="Select a service">
              <option value="" disabled selected class="bg-white text-slate-500">Select Services</option>
              <option value="custom-software" class="bg-white text-text-primary font-body">Custom Software</option>
              <option value="website-dev" class="bg-white text-text-primary font-body">Website Development</option>
              <option value="web-app" class="bg-white text-text-primary font-body">Web Applications</option>
              <option value="cloud-hosting" class="bg-white text-text-primary font-body">Cloud Hosting</option>
              <option value="server-management" class="bg-white text-text-primary font-body">Server Management</option>
              <option value="devops" class="bg-white text-text-primary font-body">DevOps & Infrastructure</option>
              <option value="maintenance" class="bg-white text-text-primary font-body">Maintenance & Support</option>
            </select>
            <div
              class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-text-secondary z-10 flex items-center">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
            <span id="popup-error-service" class="popup-error-msg">
              <i data-lucide="alert-circle" class="w-3 h-3"></i> <span>Please select a service</span>
            </span>
          </div>

          <!-- Budget Range Slider -->
          <div class="flex flex-col gap-2 mt-2">
            <div class="flex justify-between items-center text-xs font-semibold text-text-secondary">
              <span>Select Amount (10k — 10 Lakh)</span>
              <span id="popup-budget-display"
                class="px-2.5 py-0.5 rounded-full bg-blue-500/15 border border-primary/20 text-primary font-bold">10K</span>
            </div>
            <div class="relative px-1 py-1">
              <input type="range" id="popup-budget-slider" min="10000" max="1000000" step="10000" value="10000"
                class="popup-range" oninput="updatePopupBudgetDisplay(this.value)"
                aria-label="Select project budget between 10 thousand and 10 lakh rupees" />
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" id="popup-submit-btn" class="popup-submit">
            <span>Submit Now</span>
          </button>
        </form>
      </div>

      <!-- Success Screen -->
      <div id="popup-success-screen"
        class="hidden flex-col items-center justify-center text-center py-12 px-6 animate-fadeIn">
        <div
          class="w-16 h-16 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-400 flex items-center justify-center mb-6 shadow-sm animate-pulse">
          <i data-lucide="check" class="w-7 h-7"></i>
        </div>
        <h3 class="font-headings text-2xl font-bold mb-3 text-text-primary">Registration Complete!</h3>
        <p class="text-sm text-text-secondary leading-relaxed mb-8 max-w-[340px] font-light">
          Thank you for reaching out. We have received your query, and our team will get in touch with you shortly.
        </p>
        <button onclick="closeContactPopup()"
          class="inline-flex items-center justify-center gap-2 px-6 py-2.5 font-headings text-xs font-semibold rounded-xl bg-slate-50 border border-slate-200 text-text-primary hover:bg-slate-100 transition-all duration-300 cursor-pointer">
          Done
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal CSS styling for range track and status transitions -->
<style>
  /* Fix Cursor Visibility in Modal */
  .custom-cursor-dot {
    z-index: 99999999 !important;
  }

  .custom-cursor-ring {
    z-index: 99999998 !important;
  }

  /* Close Button Custom Styling */
  .popup-close-btn {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    padding: 0.5rem;
    color: #94a3b8;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease, background-color 0.2s ease;
  }

  .popup-close-btn:hover {
    color: #ffffff;
    background-color: rgba(255, 255, 255, 0.08);
  }

  /* Social links container */
  .social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: #ffffff !important;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .social-link:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
  }

  /* Fallback SVG sizes inside modal */
  #contact-popup-modal svg {
    width: 1rem;
    height: 1rem;
    stroke: currentColor;
    stroke-width: 2;
    fill: none;
  }

  .popup-close-btn svg {
    width: 1.25rem !important;
    height: 1.25rem !important;
  }

  /* Make sure inputs and interactive elements allow native cursor indicators */
  #contact-popup-modal input,
  #contact-popup-modal textarea,
  #contact-popup-modal select {
    cursor: text !important;
  }

  #contact-popup-modal select,
  #contact-popup-modal button,
  #contact-popup-modal .popup-range,
  #contact-popup-modal a {
    cursor: pointer !important;
  }

  #contact-popup-modal.modal-active {
    opacity: 1 !important;
    pointer-events: auto !important;
  }

  #contact-popup-modal.modal-active>div:last-child {
    transform: scale(1) !important;
  }

  /* Custom Form Styling */
  .popup-field-group {
    position: relative;
    width: 100%;
  }

  .popup-icon-container {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #4b5563;
    /* slate-600 */
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: color 0.3s ease;
  }

  .popup-field-group textarea~.popup-icon-container {
    top: 1.35rem;
    transform: none;
  }

  .popup-input {
    width: 100%;
    height: 3.25rem;
    padding: 0 1.25rem 0 3.25rem;
    background: rgba(13, 19, 31, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    color: #ffffff;
    font-size: 0.9rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .popup-select {
    width: 100%;
    height: 3.25rem;
    padding: 0 2.5rem 0 3.25rem;
    background: rgba(13, 19, 31, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    color: #ffffff;
    font-size: 0.9rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
  }

  .popup-textarea {
    width: 100%;
    min-height: 6rem;
    padding: 1rem 1.25rem 1rem 3.25rem;
    background: rgba(13, 19, 31, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    color: #ffffff;
    font-size: 0.9rem;
    font-family: 'Inter', sans-serif;
    resize: none;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }

  /* Interactive States */
  .popup-input:focus,
  .popup-select:focus,
  .popup-textarea:focus {
    outline: none;
    background: rgba(13, 19, 31, 0.85);
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  }

  .popup-field-group:focus-within .popup-icon-container {
    color: #2563eb;
    /* Highlight icon on focus */
  }

  /* Placeholders styling */
  .popup-input::placeholder,
  .popup-textarea::placeholder {
    color: #4b5563;
    /* slate-600 */
    font-weight: 400;
  }

  /* Submit Button Styles */
  .popup-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2.5;
    background: #ffffff;
    color: #0f172a;
    font-family: 'Orbitron', 'Syncopate', sans-serif;
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: none;
    border-radius: 14px;
    padding: 0.95rem 1.5rem;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 20px rgba(255, 255, 255, 0.03);
  }

  .popup-submit:hover {
    background: #2563eb;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(37, 99, 235, 0.35);
  }

  .popup-submit:active {
    transform: translateY(0);
  }

  /* Error messages */
  .popup-error-msg {
    display: none;
    align-items: center;
    gap: 0.25rem;
    color: #f87171;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.35rem;
    padding-left: 0.5rem;
  }

  /* Custom Range Slider Styles — light theme */
  .popup-range {
    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    background: transparent;
    height: 28px;
    cursor: pointer;
    display: block;
  }

  .popup-range:focus {
    outline: none;
  }

  .popup-range::-webkit-slider-runnable-track {
    width: 100%;
    height: 6px;
    cursor: pointer;
    background: #cbd5e1;
    border-radius: 9999px;
    border: 1px solid #94a3b8;
  }

  .popup-range::-webkit-slider-thumb {
    height: 18px;
    width: 18px;
    border-radius: 9999px;
    background: #2563eb;
    cursor: pointer;
    -webkit-appearance: none;
    margin-top: -7px;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
    border: 2px solid #ffffff;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .popup-range::-webkit-slider-thumb:hover {
    transform: scale(1.15);
    background: #1e40af;
  }

  .popup-range::-moz-range-track {
    width: 100%;
    height: 6px;
    cursor: pointer;
    background: #cbd5e1;
    border-radius: 9999px;
    border: 1px solid #94a3b8;
  }

  .popup-range::-moz-range-thumb {
    height: 18px;
    width: 18px;
    border-radius: 9999px;
    background: #2563eb;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
    border: 2px solid #ffffff;
  }

  /* Current compact popup theme */
  .contact-popup-card {
    max-height: calc(100vh - 1.5rem);
    max-height: calc(100dvh - 1.5rem);
  }

  .popup-info-panel {
    color: #ffffff;
    background:
      radial-gradient(circle at 100% 0%, rgba(23, 162, 184, 0.2), transparent 34%),
      linear-gradient(155deg, #343a40 0%, #212529 100%);
    overflow-x: hidden;
    overflow-y: auto;
  }

  .popup-info-panel .text-text-primary,
  .popup-info-panel .text-text-primary\/80 {
    color: #ffffff !important;
  }

  .popup-info-panel .text-text-primary\/50 {
    color: rgba(255, 255, 255, 0.58) !important;
  }

  .popup-info-panel .bg-slate-100 {
    background: rgba(255, 255, 255, 0.1) !important;
  }

  .popup-info-panel .border-slate-300 {
    border-color: rgba(255, 255, 255, 0.16) !important;
  }

  .popup-info-copy {
    color: rgba(255, 255, 255, 0.72);
  }

  .popup-kicker {
    display: inline-flex;
    width: fit-content;
    margin-bottom: 1rem;
    padding: 0.35rem 0.65rem;
    border: 1px solid rgba(23, 162, 184, 0.35);
    border-radius: 9999px;
    background: rgba(23, 162, 184, 0.12);
    color: #67d8e8;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .popup-form-panel {
    min-height: 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(108, 117, 125, 0.4) transparent;
  }

  .popup-form-heading {
    text-align: left;
  }

  .popup-close-btn {
    top: 1rem;
    right: 1rem;
    color: #6c757d;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
  }

  .popup-close-btn:hover {
    color: #343a40;
    background: #e9ecef;
  }

  .popup-input,
  .popup-select {
    height: 2.8rem;
    padding-left: 2.85rem;
    border-radius: 0.7rem;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    color: #343a40;
    font-size: 0.85rem;
  }

  .popup-select {
    padding-right: 2.5rem;
  }

  .popup-textarea {
    min-height: 4.75rem;
    padding: 0.8rem 1rem 0.8rem 2.85rem;
    border-radius: 0.7rem;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    color: #343a40;
    font-size: 0.85rem;
  }

  .popup-icon-container {
    left: 1rem;
    color: #6c757d;
  }

  .popup-field-group:has(.popup-textarea) .popup-icon-container {
    top: 0.95rem;
    transform: none;
  }

  .popup-input::placeholder,
  .popup-textarea::placeholder {
    color: #6c757d;
  }

  .popup-input:focus,
  .popup-select:focus,
  .popup-textarea:focus {
    background: #ffffff;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.12);
  }

  .popup-field-group:focus-within .popup-icon-container {
    color: #007bff;
  }

  .popup-submit {
    min-height: 2.8rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.7rem;
    background: #007bff;
    color: #ffffff;
    font-family: 'Inter', sans-serif;
    font-size: 0.8rem;
    box-shadow: 0 8px 18px -10px rgba(0, 123, 255, 0.6);
  }

  .popup-submit:hover {
    background: #0056b3;
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 10px 22px -10px rgba(0, 123, 255, 0.65);
  }

  .popup-error-msg {
    color: #dc3545;
    font-size: 0.68rem;
    margin-top: 0.25rem;
  }

  @media (max-width: 767px) {
    .contact-popup-card {
      max-height: calc(100vh - 1rem);
      max-height: calc(100dvh - 1rem);
    }

    .popup-form-panel {
      width: 100%;
      padding: 1.25rem;
    }
  }

  @media (min-width: 768px) and (max-height: 760px) {
    .popup-info-panel {
      padding: 1.5rem;
    }

    .popup-form-panel {
      padding: 1.25rem 1.5rem;
    }

    #popup-contact-form {
      gap: 0.55rem;
    }

    .popup-form-heading {
      margin-bottom: 0.75rem;
    }

    .popup-info-panel .gap-5 {
      gap: 0.8rem;
    }
  }
</style>

<!-- Modal scripts for interactions and AJAX -->
<script>
  function openContactPopup() {
    // Close mobile menu if open
    const closeBtn = document.getElementById('close-icon');
    const mobileBtn = document.getElementById('mobile-menu-btn');
    if (closeBtn && !closeBtn.classList.contains('hidden') && mobileBtn) {
      mobileBtn.click();
    }

    const modal = document.getElementById('contact-popup-modal');
    if (modal) {
      modal.classList.add('modal-active');

      // Stop Lenis background scrolling
      if (window.lenis) {
        window.lenis.stop();
      }

      // Ensure icons are loaded inside popup
      if (window.lucide) {
        window.lucide.createIcons();
      }
    }
  }

  function closeContactPopup() {
    const modal = document.getElementById('contact-popup-modal');
    if (modal) {
      modal.classList.remove('modal-active');

      // Resume Lenis scroll
      if (window.lenis) {
        window.lenis.start();
      }

      // Reset form after short delay (let animation finish)
      setTimeout(() => {
        document.getElementById('popup-contact-form').reset();
        document.getElementById('popup-form-wrapper').classList.remove('hidden');
        document.getElementById('popup-success-screen').classList.add('hidden');
        document.getElementById('popup-success-screen').classList.remove('flex');
        updatePopupBudgetDisplay(10000);

        // Hide errors
        const errorIDs = ['name', 'email', 'phone', 'message', 'service'];
        errorIDs.forEach(id => {
          const errEl = document.getElementById('popup-error-' + id);
          if (errEl) {
            errEl.style.display = 'none';
          }
        });
      }, 300);
    }
  }

  // Format and show slider value
  function updatePopupBudgetDisplay(value) {
    const display = document.getElementById('popup-budget-display');
    if (!display) return;

    let text = '';
    if (value < 100000) {
      text = (value / 1000) + 'K';
    } else {
      const lakhs = value / 100000;
      text = lakhs === Math.floor(lakhs) ? lakhs + ' Lakh' : lakhs.toFixed(1) + ' Lakh';
    }
    display.textContent = text;
  }

  // Popup client-side validation and submit
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('popup-contact-form');
    const submitBtn = document.getElementById('popup-submit-btn');
    const formWrapper = document.getElementById('popup-form-wrapper');
    const successScreen = document.getElementById('popup-success-screen');

    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();

        // Clear all previous errors
        const errorIDs = ['name', 'email', 'phone', 'message', 'service'];
        errorIDs.forEach(id => {
          const errEl = document.getElementById('popup-error-' + id);
          if (errEl) {
            errEl.style.display = 'none';
          }
        });

        // Fetch values
        const name = document.getElementById('popup-name').value.trim();
        const email = document.getElementById('popup-email').value.trim();
        const phone = document.getElementById('popup-phone').value.trim();
        const message = document.getElementById('popup-message').value.trim();
        const service = document.getElementById('popup-service').value;
        const budget = document.getElementById('popup-budget-slider').value;

        let isValid = true;

        if (!name) {
          showError('name', 'Full Name is required');
          isValid = false;
        }
        if (!email) {
          showError('email', 'Email Address is required');
          isValid = false;
        } else if (!/\S+@\S+\.\S+/.test(email)) {
          showError('email', 'Email Address is invalid');
          isValid = false;
        }
        if (!phone) {
          showError('phone', 'Mobile Number is required');
          isValid = false;
        } else if (!/^\+?[\d\s-]{8,15}$/.test(phone)) {
          showError('phone', 'Mobile Number is invalid');
          isValid = false;
        }
        if (!message) {
          showError('message', 'Message is required');
          isValid = false;
        }
        if (!service) {
          showError('service', 'Please select a service');
          isValid = false;
        }

        if (!isValid) return;

        // Open blank tab synchronously to bypass popup blocker
        const newTab = window.open('', '_blank');

        // Submit AJAX to api/contact
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = `<div class="w-4 h-4 border-2 border-slate-900 border-t-transparent rounded-full animate-spin"></div><span>Submitting...</span>`;

        const payload = {
          name: name,
          email: email,
          phone: phone,
          message: message + ` [Budget Request: ${document.getElementById('popup-budget-display').textContent}]`,
          service: service,
          budget: budget
        };

        fetch('api/contact', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

            if (data.success) {
              formWrapper.classList.add('hidden');
              successScreen.classList.remove('hidden');
              successScreen.classList.add('flex');

              // Build pre-typed WhatsApp message
              const serviceLabels = {
                'custom-software': 'Custom Software',
                'website-dev': 'Website Development',
                'web-app': 'Web Applications',
                'cloud-hosting': 'Cloud Hosting',
                'server-management': 'Server Management',
                'devops': 'DevOps & Infrastructure',
                'maintenance': 'Maintenance & Support'
              };
              const serviceName = serviceLabels[service] || service;
              const budgetText = document.getElementById('popup-budget-display').textContent;

              const waMessage = `Hello Digital Creatorss,\n\nI have submitted a query through the website. Here are my details:\n• Name: ${name}\n• Email: ${email}\n• Phone: ${phone}\n• Service: ${serviceName}\n• Message: ${message}\n• Estimated Budget: ${budgetText}\n\nPlease get back to me.`;
              const whatsappUrl = `https://wa.me/<?php echo htmlspecialchars(whatsapp_number()); ?>?text=${encodeURIComponent(waMessage)}`;

              // Redirect the pre-opened tab to WhatsApp
              if (newTab) {
                newTab.location.href = whatsappUrl;
              }

              form.reset();
              // Trigger checkmark icon draw
              if (window.lucide) {
                window.lucide.createIcons();
              }
            } else if (data.errors) {
              if (newTab) newTab.close();
              Object.keys(data.errors).forEach(key => {
                showError(key, data.errors[key]);
              });
            }
          })
          .catch(err => {
            if (newTab) newTab.close();
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            console.error('Error submitting popup form:', err);
          });
      });
    }

    function showError(field, msg) {
      const errEl = document.getElementById('popup-error-' + field);
      if (errEl) {
        errEl.querySelector('span').textContent = msg;
        errEl.style.display = 'flex';
      }
    }
  });
</script>