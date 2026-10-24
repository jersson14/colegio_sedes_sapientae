// Abre una vista en Chrome headless con una sesión, ejecuta pasos (expresiones JS) y guarda capturas.
// Uso: [PRE="js antes de cargar la vista"] node tests/navegador/navegar_vista.js <PHPSESSID> <carpeta> <vista> [<js paso1> ...]
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const [sesion, salida, vista, ...pasos] = process.argv.slice(2);
const PUERTO = Number(process.env.PUERTO || 9334);
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',
  ['--headless=new', '--remote-debugging-port=' + PUERTO, '--user-data-dir=' + path.join(salida, 'perfil_' + PUERTO), '--window-size=1500,1000', 'about:blank'], { stdio: 'ignore' });
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
  let id = 0; const pend = {}; const errores = [];
  ws.addEventListener('message', ev => {
    const m = JSON.parse(ev.data);
    if (m.id && pend[m.id]) { pend[m.id](m); delete pend[m.id]; }
    if (m.method === 'Runtime.exceptionThrown') errores.push('EXCEPCION: ' + (m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text));
    if (m.method === 'Log.entryAdded' && m.params.entry.level === 'error') errores.push('log: ' + m.params.entry.text + ' ' + (m.params.entry.url || ''));
  });
  const cdp = (method, params = {}) => new Promise(r => { const i = ++id; pend[i] = r; ws.send(JSON.stringify({ id: i, method, params })); });
  const evaluar = async e => (await cdp('Runtime.evaluate', { expression: e, returnByValue: true, awaitPromise: true })).result?.result?.value;
  const captura = async n => fs.writeFileSync(path.join(salida, n + '.png'), Buffer.from((await cdp('Page.captureScreenshot', { format: 'png' })).result.data, 'base64'));
  await cdp('Runtime.enable'); await cdp('Log.enable'); await cdp('Page.enable'); await cdp('Network.enable');
  await cdp('Network.setCookie', { name: 'PHPSESSID', value: sesion, domain: 'localhost', path: '/' });
  await cdp('Page.navigate', { url: 'http://localhost/_public_html/view/index.php' });
  await espera(3500);
  if (process.env.PRE) { console.log('pre: ' + JSON.stringify(await evaluar(process.env.PRE))); }
  await evaluar(`cargar_contenido('contenido_principal','${vista}')`);
  await espera(3000);
  const base = path.basename(vista, '.php');
  await captura(base + '_0');
  for (let i = 0; i < pasos.length; i++) {
    const r = await evaluar(pasos[i]);
    await espera(1500);
    console.log('paso ' + (i + 1) + ': ' + JSON.stringify(r));
    await captura(base + '_' + (i + 1));
  }
  console.log('ERRORES (' + errores.length + '):\n' + errores.join('\n'));
  ws.close(); chrome.kill(); process.exit(0);
})().catch(e => { console.error(e); chrome.kill(); process.exit(1); });
