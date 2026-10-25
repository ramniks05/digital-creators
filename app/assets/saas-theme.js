(function () {
  var btn = document.getElementById('crm-theme-toggle');
  if (!btn) {
    return;
  }
  btn.addEventListener('click', function () {
    var root = document.documentElement;
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try {
      localStorage.setItem('crm-theme', next);
    } catch (e) {}
  });
})();
