<!-- Intro Preloader — clean corporate loading -->
<div id="intro-preloader" class="fixed inset-0 z-[99999] bg-white flex flex-col items-center justify-center select-none">
  <div class="flex flex-col items-center w-full max-w-sm px-6">
    <div id="loader-elements" class="flex flex-col items-center w-full">
      <img src="assets/images/logo-white.webp" alt="Digital Creatorss" width="160" height="40"
        class="brightness-0 h-10 w-auto object-contain mb-8" />
      <div class="w-full flex flex-col items-center">
        <div class="w-full flex justify-between items-center text-xs text-text-muted mb-2 uppercase tracking-wider">
          <span class="intro-status-text">Loading</span>
          <span id="intro-percentage" class="text-primary font-semibold">0%</span>
        </div>
        <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
          <div id="intro-progress-bar" class="h-full w-0 bg-primary rounded-full transition-all duration-150"></div>
        </div>
      </div>
    </div>

    <div id="brand-zoom-container" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none opacity-0">
      <div class="intro-brand-wrapper flex items-center gap-4">
        <img src="assets/images/logo-white.webp" alt="Digital Creatorss" class="brightness-0 h-12 w-auto object-contain" />
        <div class="flex flex-col text-left leading-[1.1] font-headings">
          <span class="text-text-primary text-2xl font-bold tracking-tight">Digital</span>
          <span class="text-primary text-sm font-semibold tracking-wide uppercase">Creatorss</span>
        </div>
      </div>
    </div>
  </div>
</div>
