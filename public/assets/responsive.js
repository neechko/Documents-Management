document.addEventListener('DOMContentLoaded', function () {
  function toggleMenu(btnId, menuId) {
    var btn = document.getElementById(btnId);
    var menu = document.getElementById(menuId);
    if (!btn || !menu) return;
    btn.addEventListener('click', function () {
      var isHidden = menu.classList.contains('hidden');
      if (isHidden) {
        menu.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
      } else {
        menu.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Common ids used in navbars
  toggleMenu('navToggle', 'mobileMenu');
});
