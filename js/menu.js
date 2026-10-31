/* Menú lateral: buscador de opciones y opción activa. Sirve para todos los roles. */
var Menu = (function () {
  function normalizar(t) {
    return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').trim();
  }

  // Opciones finales del menú (las que abren una pantalla), sin los grupos desplegables.
  function opciones($menu) {
    return $menu.find('li.nav-item').filter(function () {
      return !$(this).hasClass('menu-buscar') && $(this).children('ul.nav-treeview').length === 0;
    });
  }

  function filtrar($menu, texto) {
    var q = normalizar(texto);
    var $items = $menu.find('li.nav-item').not('.menu-buscar');
    var $cabeceras = $menu.find('li.nav-header');
    $items.removeClass('menu-oculto');
    $cabeceras.removeClass('menu-oculto');
    if (!q) {
      $('#menu_sin_resultados').prop('hidden', true);
      return;
    }
    var visibles = 0;
    opciones($menu).each(function () {
      var $li = $(this);
      var grupo = normalizar($li.closest('ul.nav-treeview').closest('li.nav-item').children('a').text());
      var coincide = normalizar($li.children('a').text()).indexOf(q) !== -1 || (grupo && grupo.indexOf(q) !== -1);
      $li.toggleClass('menu-oculto', !coincide);
      if (coincide) visibles++;
    });
    // Grupos: visibles y abiertos si alguna opción interna coincide.
    $menu.find('li.nav-item').has('> ul.nav-treeview').each(function () {
      var $g = $(this);
      var hay = $g.find('> ul.nav-treeview > li.nav-item').not('.menu-oculto').length > 0;
      $g.toggleClass('menu-oculto', !hay);
      if (hay) { $g.addClass('menu-open'); $g.children('ul.nav-treeview').show(); }
    });
    // Títulos de sección: solo si debajo queda algo visible.
    $cabeceras.each(function () {
      var hay = $(this).nextUntil('li.nav-header').filter('li.nav-item').not('.menu-oculto, .menu-buscar').length > 0;
      $(this).toggleClass('menu-oculto', !hay);
    });
    $('#menu_sin_resultados').prop('hidden', visibles > 0);
  }

  function marcarActiva($a) {
    var $menu = $a.closest('.nav-sidebar');
    $menu.find('.nav-link.active').removeClass('active');
    $a.addClass('active');
    $a.closest('ul.nav-treeview').closest('li.nav-item').children('a.nav-link').addClass('active');
  }

  function iniciar() {
    var $menu = $('.main-sidebar .nav-sidebar');
    if (!$menu.length) return;

    // Pantallas que la dirección quitó a este rol: fuera del menú, de los accesos del inicio y de la barra inferior
    (window.PANTALLAS_QUITADAS || []).forEach(function (v) {
      var sel = "'" + v + "'";
      $('a, button').filter(function () { return (this.getAttribute('onclick') || '').indexOf(sel) !== -1; }).each(function () {
        var li = $(this).closest('li.nav-item');
        (li.length ? li : $(this)).remove();
      });
    });
    // grupos del menú que quedaron vacíos
    $menu.find('li.nav-item').has('> ul.nav-treeview').filter(function () { return !$(this).find('ul.nav-treeview li').length; }).remove();

    $('#menu_buscar').on('input', function () {
      filtrar($menu, this.value);
    }).on('keydown', function (e) {
      if (e.key === 'Escape') {
        this.value = '';
        filtrar($menu, '');
      } else if (e.key === 'Enter') {
        e.preventDefault();
        var $primera = opciones($menu).not('.menu-oculto').children('a').first();
        if ($primera.length) $primera[0].click();
      }
    });

    $menu.on('click', 'a.nav-link[onclick*="cargar_contenido"]', function (e) {
      e.preventDefault();
      marcarActiva($(this));
      // En el celular el menú tapa la pantalla: se cierra al elegir una opción.
      if (window.innerWidth < 992 && $('body').hasClass('sidebar-open')) {
        $('[data-widget="pushmenu"]').first().trigger('click');
      }
    });
  }

  $(iniciar);
  return { normalizar: normalizar, filtrar: filtrar };
})();

// Barra inferior del celular (Fase 13, paso 12): marca el acceso elegido
function Barra_Activa(a) {
  $('.barra-inferior a').removeClass('activo');
  $(a).addClass('activo');
  window.scrollTo(0, 0);
}
