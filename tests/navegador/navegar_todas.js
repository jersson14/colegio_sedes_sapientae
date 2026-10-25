// Abre vistas del sistema en una sola sesión de Chrome headless y reporta errores de JavaScript por vista.
// Uso: PUERTO=9340 node tests/navegador/navegar_todas.js <PHPSESSID> <carpeta_temporal> <vista1> <vista2> ...
// Las vistas son rutas relativas a view/ (p. ej. alumnos/view_alumnos.php). No se cuentan las fotos faltantes (datos).
const { spawn } = require('child_process');
const path = require('path');
const [sesion, salida, ...vistas] = process.argv.slice(2);
const PUERTO = Number(process.env.PUERTO || 9335);
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',
  ['--headless=new', '--remote-debugging-port=' + PUERTO, '--user-data-dir=' + path.join(salida, 'perfil_' + PUERTO), '--window-size=1400,900', 'about:blank'], { stdio: 'ignore' });
const espera = ms => new Promise(r => setTimeout(r, ms));
(async () => {
  let ws;
  for (let i = 0; i < 40 && !ws; i++) {
    try {
      const p = (await (await fetch(`http://127.0.0.1:${PUERTO}/json`)).json()).find(t => t.type === 'page');
      if (p) { ws = new WebSocket(p.webSocketDebuggerUrl); await new Promise(r => ws.addEventListener('open', r)); break; }
    } catch (e) {}
    await espera(250);
  }
  let id = 0; const pend = {}; let errores = [];
  ws.addEventListener('message', ev => {
    const m = JSON.parse(ev.data);
    if (m.id && pend[m.id]) { pend[m.id](m); delete pend[m.id]; }
    if (m.method === 'Runtime.exceptionThrown') errores.push('EXCEPCION: ' + (m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text).split('\n')[0]);
    if (m.method === 'Log.entryAdded' && m.params.entry.level === 'error' && !/fotos\/|Fotos\/|admin\.png/i.test(m.params.entry.url || '')) {
      errores.push('log: ' + m.params.entry.text + ' ' + (m.params.entry.url || ''));
    }
    // Un SweetAlert abierto por error (403/400) también se reporta
  });
  const cdp = (method, params = {}) => new Promise(r => { const i = ++id; pend[i] = r; ws.send(JSON.stringify({ id: i, method, params })); });
  const evaluar = async e => (await cdp('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true })).result?.result?.value;
  await cdp('Runtime.enable'); await cdp('Log.enable'); await cdp('Page.enable'); await cdp('Network.enable');
  await cdp('Network.setCookie', { name: 'PHPSESSID', value: sesion, domain: 'localhost', path: '/' });
  await cdp('Page.navigate', { url: 'http://localhost/_public_html/view/index.php' });
  await espera(3500);
  console.log('index: ' + (errores.length ? errores.join(' | ') : 'ok'));
  let total = errores.length;
  for (const v of vistas) {
    errores = [];
    await evaluar(`Swal.close && Swal.close(); $('.modal').modal('hide'); cargar_contenido('contenido_principal','${v}')`);
    await espera(2500);
    const alerta = await evaluar("$('.swal2-popup:visible .swal2-title').text()");
    if (alerta) { errores.push('alerta: ' + alerta); }
    // ninguna tabla debe quedarse en "Procesando..." ni mostrar el aviso de error de carga
    const colgadas = await evaluar("$('.dataTables_processing:visible').map(function(){return this.id}).get().join(',')");
    if (colgadas) { errores.push('tabla en Procesando: ' + colgadas); }
    const fallidas = await evaluar("$('td.dataTables_empty.text-danger:visible').closest('table').map(function(){return this.id}).get().join(',')");
    if (fallidas) { errores.push('tabla sin poder cargar: ' + fallidas); }
    console.log(v + ': ' + (errores.length ? 'ERRORES ' + errores.join(' | ') : 'ok'));
    total += errores.length;
  }
  console.log('TOTAL ERRORES: ' + total);
  ws.close(); chrome.kill(); process.exit(total ? 1 : 0);
})().catch(e => { console.error(e); chrome.kill(); process.exit(2); });
