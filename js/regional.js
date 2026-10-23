// Configuración regional del colegio: moneda, documento de identidad y país.
// view/index.php la define en window.REGIONAL (sale de Configuración > Datos regionales).
var REGIONAL = window.REGIONAL || {
  pais: 'PE', simbolo: 'S/', decimales: 2, sep_miles: ',', sep_decimal: '.', simbolo_antes: 1,
  doc_nombre: 'DNI', doc_min: 8, doc_max: 8, doc_regex: '^[0-9]{8}$', doc_ejemplo: '12345678',
  permite_doc_extranjero: 1, prefijo_tel: '+51'
};

// 1500.5 -> "1,500.50" (Perú) / "1.500,50" (Argentina) / "1.501" (Chile). Igual que formato_moneda() en PHP.
function formatoNumero(monto) {
  var n = Number(String(monto === null || monto === undefined ? 0 : monto).replace(/[^0-9.-]/g, '')) || 0;
  var partes = Math.abs(n).toFixed(REGIONAL.decimales).split('.');
  var entero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, REGIONAL.sep_miles);
  return (n < 0 ? '-' : '') + entero + (partes[1] ? REGIONAL.sep_decimal + partes[1] : '');
}

function formatoMoneda(monto) {
  var texto = formatoNumero(monto);
  return REGIONAL.simbolo_antes == 1 ? REGIONAL.simbolo + ' ' + texto : texto + ' ' + REGIONAL.simbolo;
}

function normalizarDocumento(valor) {
  return String(valor || '').trim().replace(/[ .]/g, '').toUpperCase();
}

// Mismas reglas que documento_valido() en utilitario/regional.php
function documentoValido(valor) {
  var v = normalizarDocumento(valor);
  if (new RegExp(REGIONAL.doc_regex).test(v)) {
    return true;
  }
  return REGIONAL.permite_doc_extranjero == 1 && /^[A-Z0-9-]{4,20}$/.test(v) && (/[A-Z]/.test(v) || v.length > REGIONAL.doc_max);
}

// Adapta una vista recién cargada: cambia "DNI" por el documento del país en textos y placeholders,
// y quita la restricción "solo números" de los campos de documento cuando el documento admite letras.
function aplicarRegional(raiz) {
  raiz = raiz || document.body;
  var doc = REGIONAL.doc_nombre;
  if (doc !== 'DNI') {
    var walker = document.createTreeWalker(raiz, NodeFilter.SHOW_TEXT, {
      acceptNode: function(n) {
        var p = n.parentNode && n.parentNode.nodeName;
        return (p === 'SCRIPT' || p === 'STYLE' || p === 'TEXTAREA') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
      }
    });
    var nodos = [];
    while (walker.nextNode()) {
      if (/\bDNI\b/.test(walker.currentNode.nodeValue)) { nodos.push(walker.currentNode); }
    }
    nodos.forEach(function(n) { n.nodeValue = n.nodeValue.replace(/\bDNI\b/g, doc); });
    $(raiz).find('input[placeholder*="DNI"]').each(function() {
      this.placeholder = this.placeholder.replace(/\bDNI\b/g, doc);
    });
  }
  var admite_letras = /[A-Za-z]/.test(REGIONAL.doc_regex.replace(/\\[dDsSwW]/g, '')) || REGIONAL.permite_doc_extranjero == 1;
  $(raiz).find('input[id*="dni"]').not('[readonly]').each(function() {
    if (admite_letras && /soloNumeros/.test(this.getAttribute('onkeypress') || '')) {
      this.removeAttribute('onkeypress');
    }
    this.setAttribute('maxlength', REGIONAL.permite_doc_extranjero == 1 ? 20 : REGIONAL.doc_max + 3);
    if (!this.placeholder || /ingrese/i.test(this.placeholder)) {
      this.title = doc + ' (ej. ' + REGIONAL.doc_ejemplo + ')';
    }
  });
}

// Marca en rojo un documento mal escrito al salir del campo (el servidor también lo valida)
$(document).off('blur.regional', 'input[id*="dni"]').on('blur.regional', 'input[id*="dni"]', function() {
  var v = this.value.trim();
  $(this).toggleClass('is-invalid', v !== '' && !documentoValido(v));
});
