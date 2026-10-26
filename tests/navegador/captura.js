// Captura la página principal (view/index.php) con una sesión, en escritorio y celular, página completa.
// Uso: [PUERTO=9420] [JS="código a ejecutar antes"] node tests/navegador/captura.js <PHPSESSID> <carpeta> [ancho1,ancho2]
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const [sesion, salida, anchos = '1440,390'] = process.argv.slice(2);
const PUERTO = Number(process.env.PUERTO || 9420);
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',
  ['--headless=new', '--remote-debugging-port=' + PUERTO, '--user-data-dir=' + path.join(salida, 'perfil_' + PUERTO), '--hide-scrollbars', 'about:blank'], { stdio: 'ignore' });
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
    if (m.method === 'Runtime.exceptionThrown') errores.push('EXCEPCION: ' + (m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text).split('\n')[0]);
    if (m.method === 'Log.entryAdded' && m.params.entry.level === 'error' && !/fotos|FOTOS/.test(m.params.entry.url || '')) errores.push('log: ' + m.params.entry.text + ' ' + (m.params.entry.url || ''));
  });
  const cdp = (method, params = {}) => new Promise(r => { const i = ++id; pend[i] = r; ws.send(JSON.stringify({ id: i, method, params })); });
  await cdp('Runtime.enable'); await cdp('Log.enable'); await cdp('Page.enable');
  await cdp('Network.enable');
  await cdp('Network.setCookie', { name: 'PHPSESSID', value: sesion, domain: 'localhost', path: '/' });
  for (const ancho of anchos.split(',').map(Number)) {
    await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: ancho < 600 ? 844 : 900, deviceScaleFactor: 1, mobile: ancho < 600 });
    await cdp('Page.navigate', { url: 'http://localhost/_public_html/view/index.php' });
    await espera(3500);
    if (process.env.JS) { const r = await cdp('Runtime.evaluate', { expression: process.env.JS, awaitPromise: true, returnByValue: true }); console.log('JS: ' + JSON.stringify(r.result.result.value)); await espera(1500); }
    const alto = (await cdp('Runtime.evaluate', { expression: 'Math.max(document.body.scrollHeight, document.documentElement.scrollHeight)', returnByValue: true })).result.result.value;
    // ventana del alto de la página y recarga: AdminLTE arma el diseño para ese tamaño (redimensionar después lo desarma)
    await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: Math.min(alto, 6000), deviceScaleFactor: 1, mobile: ancho < 600 });
    await cdp('Page.navigate', { url: 'http://localhost/_public_html/view/index.php' });
    await espera(3500);
    if (process.env.JS) { await cdp('Runtime.evaluate', { expression: process.env.JS, awaitPromise: true }); await espera(1500); }
    const img = await cdp('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(salida, `inicio_${ancho}.png`), Buffer.from(img.result.data, 'base64'));
    console.log(`captura ${ancho}px (${alto}px de alto)`);
  }
  console.log('ERRORES (' + errores.length + '):\n' + errores.join('\n'));
  ws.close(); chrome.kill(); process.exit(0);
})().catch(e => { console.error(e); chrome.kill(); process.exit(1); });
