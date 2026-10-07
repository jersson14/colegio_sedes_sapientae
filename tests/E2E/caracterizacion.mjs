// Pruebas de caracterización (Fase 2.3 del plan).
//
// grabar:    por cada rol, inicia sesión por el formulario, recorre todas las vistas de su menú
//            y guarda cada petición de lectura a los controladores con su respuesta.
// verificar: repite exactamente esas peticiones con la misma sesión de rol y exige respuestas
//            idénticas. Congela el comportamiento actual (incluidas las columnas por índice
//            que lee el JS) antes de refactorizar.
//
// Requisitos: la app servida en BASE_URL contra una BD con database/seeders/datos_prueba.sql y
// la fecha congelada (APP_ENTORNO=prueba, DB_FECHA_PRUEBA=2025-12-26 12:00:00).
// Uso: BASE_URL=http://127.0.0.1:8099/ node caracterizacion.mjs grabar|verificar
import { chromium } from 'playwright-core';
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { isDeepStrictEqual } from 'node:util';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8099/';
const ARCHIVO = fileURLToPath(new URL('../Caracterizacion/grabacion.json', import.meta.url));
const CLAVE = 'Prueba.2026';
// Un usuario por rol del dataset de prueba (usuarioN, con N = usu_id).
const USUARIOS = [
  ['ADMINISTRADOR', 'usuario9'], ['DOCENTE', 'usuario10'], ['AUXILIAR', 'usuario22'],
  ['PSICOLOGA', 'usuario32'], ['ENFERMERA', 'usuario50'], ['ESTUDIANTE', 'usuario55'],
];
// Nunca se graban ni repiten escrituras ni el login/logout.
const ESCRITURA = /(registr|modific|elimin|actualiz|subir|guardar|calific|iniciar_sesion|cerrar_sesion|anular)/i;

const modo = process.argv[2];
if (!['grabar', 'verificar'].includes(modo)) {
  console.error('Uso: node caracterizacion.mjs grabar|verificar');
  process.exit(2);
}

const interpretar = (texto) => {
  try { return { json: JSON.parse(texto) }; } catch { return { texto: texto.trim() }; }
};

// Las filas se comparan sin importar su orden: varios SP ordenan por columnas con empates
// (p. ej. ORDER BY created_at con fecha sin hora) y la propia app no garantiza ese orden.
// El CONTENIDO (filas, columnas, índices y valores) debe ser idéntico.
const sinOrden = (r) => {
  if (!r.json) return r;
  const ord = (a) => (Array.isArray(a) ? a.map((x) => JSON.stringify(x)).sort() : a);
  const j = r.json;
  if (Array.isArray(j)) return { json: ord(j) };
  if (j && typeof j === 'object') return { json: Object.fromEntries(Object.entries(j).map(([k, v]) => [k, ord(v)])) };
  return r;
};
const iguales = (a, b) => isDeepStrictEqual(sinOrden(a), sinOrden(b));

async function pedir(page, caso) {
  const real = await page.evaluate(async ({ base, caso }) => {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const headers = { 'X-CSRF-Token': token };
    if (caso.tipo) headers['Content-Type'] = caso.tipo;
    const r = await fetch(base + caso.ruta, {
      method: caso.metodo, headers, body: caso.metodo === 'GET' ? undefined : caso.cuerpo, credentials: 'same-origin',
    });
    return { estado: r.status, texto: await r.text() };
  }, { base: BASE, caso });
  return { estado: real.estado, respuesta: interpretar(real.texto) };
}

async function iniciarSesion(browser, usuario) {
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await page.goto(BASE);
  await page.fill('#txt_usuario', usuario);
  await page.fill('#txt_contra', CLAVE);
  await page.click('#entrar');
  await page.waitForURL('**/view/index.php', { timeout: 20000 });
  await page.waitForLoadState('networkidle').catch(() => {});
  return { ctx, page };
}

async function grabar(browser) {
  const grabacion = [];
  for (const [rol, usuario] of USUARIOS) {
    const vistos = new Set();
    const { ctx, page } = await iniciarSesion(browser, usuario);
    const pendientes = [];
    page.on('requestfinished', (req) => pendientes.push((async () => {
      const ruta = req.url().replace(BASE, '').split('#')[0];
      if (!ruta.startsWith('controller/') || !ruta.split('?')[0].endsWith('.php') || ESCRITURA.test(ruta)) return;
      const cuerpo = req.postData() ?? '';
      const clave = `${req.method()} ${ruta} ${cuerpo}`;
      if (vistos.has(clave)) return;
      vistos.add(clave);
      const resp = await req.response();
      grabacion.push({
        rol, metodo: req.method(), ruta, tipo: req.headers()['content-type'] ?? '', cuerpo,
        estado: resp.status(), respuesta: interpretar(await resp.text()),
      });
    })()));
    // El panel ya hizo sus peticiones al cargar; se recorre después cada vista del menú.
    await page.reload();
    await page.waitForLoadState('networkidle').catch(() => {});
    const vistas = await page.$$eval('a[onclick*="cargar_contenido"]', (as) => [...new Set(as.map(
      (a) => (a.getAttribute('onclick').match(/cargar_contenido\('[^']*'\s*,\s*'([^']+)'/) || [])[1]).filter(Boolean))]);
    for (const v of vistas) {
      await page.evaluate((x) => cargar_contenido('contenido_principal', x), v);
      await page.waitForLoadState('networkidle').catch(() => {});
      await page.waitForTimeout(1200);
    }
    await Promise.all(pendientes);
    await ctx.close();
    console.log(`${rol.padEnd(14)} ${vistas.length} vistas, ${vistos.size} peticiones grabadas`);
  }
  // Estabilizar: cada petición se repite desde sesiones nuevas; si el contenido varía entre
  // peticiones idénticas, se marca inestable y no se verifica.
  for (let ronda = 0; ronda < 3; ronda++) {
    for (const [rol, usuario] of USUARIOS) {
      const { ctx, page } = await iniciarSesion(browser, usuario);
      for (const caso of grabacion.filter((g) => g.rol === rol && !g.inestable)) {
        for (let i = 0; i < 2; i++) {
          const r = await pedir(page, caso);
          if (r.estado === caso.estado && iguales(r.respuesta, caso.respuesta)) continue;
          console.log(`INESTABLE [${rol}] ${caso.ruta}: el contenido varía entre peticiones idénticas`);
          caso.inestable = true;
          break;
        }
      }
      await ctx.close();
    }
  }
  // Filas en orden canónico: la verificación ignora el orden, y así el diff de grabacion.json en un PR
  // solo muestra cambios de contenido (no los reordenamientos de filas empatadas).
  const canonico = (a) => (Array.isArray(a) ? [...a].sort((x, y) => JSON.stringify(x).localeCompare(JSON.stringify(y))) : a);
  for (const g of grabacion) {
    const j = g.respuesta.json;
    if (Array.isArray(j)) g.respuesta.json = canonico(j);
    else if (j && typeof j === 'object') for (const k of Object.keys(j)) j[k] = canonico(j[k]);
  }
  grabacion.sort((a, b) => `${a.rol}${a.ruta}${a.cuerpo}`.localeCompare(`${b.rol}${b.ruta}${b.cuerpo}`));
  writeFileSync(ARCHIVO, JSON.stringify(grabacion, null, 1) + '\n');
  const inestables = grabacion.filter((g) => g.inestable).length;
  console.log(`\n${grabacion.length} peticiones → tests/Caracterizacion/grabacion.json`
    + ` (${inestables} inestables excluidas)`);
}

async function verificar(browser) {
  const grabacion = JSON.parse(readFileSync(ARCHIVO, 'utf8'));
  let fallos = 0;
  for (const [rol, usuario] of USUARIOS) {
    const casos = grabacion.filter((g) => g.rol === rol);
    const { ctx, page } = await iniciarSesion(browser, usuario);
    for (const caso of casos) {
      if (caso.inestable) continue;
      const real = await pedir(page, caso);
      const respuesta = real.respuesta;
      if (real.estado !== caso.estado || !iguales(respuesta, caso.respuesta)) {
        fallos++;
        console.log(`CAMBIO  [${rol}] ${caso.ruta} ${caso.cuerpo.slice(0, 60)}\n`
          + `        esperado ${caso.estado} ${JSON.stringify(caso.respuesta).slice(0, 160)}\n`
          + `        obtenido ${real.estado} ${JSON.stringify(respuesta).slice(0, 160)}`);
        if (process.env.GITHUB_ACTIONS) {
          // Anotación visible en el PR y por la API pública (los logs requieren permisos de administrador).
          const limpio = (s) => s.replace(/%/g, '%25').replace(/\r/g, '%0D').replace(/\n/g, '%0A');
          console.log(`::error title=Caracterización [${rol}] ${caso.ruta}::`
            + limpio(`cuerpo: ${caso.cuerpo.slice(0, 80)}\nesperado ${caso.estado}: ${JSON.stringify(caso.respuesta).slice(0, 300)}`
            + `\nobtenido ${real.estado}: ${JSON.stringify(respuesta).slice(0, 300)}`));
        }
      }
    }
    await ctx.close();
    console.log(`${rol.padEnd(14)} ${casos.length} peticiones verificadas`);
  }
  console.log(fallos ? `\n${fallos} respuestas cambiaron respecto a la grabación.` : '\nComportamiento idéntico a la grabación.');
  process.exitCode = fallos ? 1 : 0;
}

const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
  await (modo === 'grabar' ? grabar(browser) : verificar(browser));
} finally {
  await browser.close();
}
