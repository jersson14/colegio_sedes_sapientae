// Flujos E2E críticos (Fase 2.4 del plan), en Chrome real contra la app con datos de prueba.
//
// Login y navegación se hacen por la interfaz. Las escrituras se envían desde la sesión del
// navegador (cookie + token CSRF del panel) con los mismos parámetros que arma el JS de cada
// pantalla, y se comprueba su efecto leyendo por los mismos endpoints que usa la interfaz.
//
// MODIFICA la BD: ejecutar después de la caracterización, o recargar DatosPrueba antes de repetir.
// Uso: BASE_URL=http://127.0.0.1:8099/ node flujos.mjs
import { chromium } from 'playwright-core';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8099/';
const CLAVE = 'Prueba.2026';
// Escenario del dataset de prueba (ver tests/E2E/README.md).
const ESC = {
  admin: 'usuario9', docente: 'usuario10', auxiliar: 'usuario22', estudiante: 'usuario62', inactivo: 'usuario33',
  cursoDocente: 20, aula: 5, criterio: 1, otroCriterio: 8, periodo: 44, matriculaEstudiante: 40,
  pago: { matricula: 31, pension: 36 }, alumnoNuevo: 18, otroAlumnoNuevo: 19, anioEscolar: 5,
  matriculaOtraAula: 33,
};

let ok = 0, fallos = 0;
const check = (cond, desc, detalle = '') => {
  if (cond) { ok++; console.log(`  ok    ${desc}`); return; }
  fallos++;
  console.log(`  FALLO ${desc} ${detalle}`);
  // En GitHub Actions, como anotación: se lee sin permisos de administrador (los logs no).
  if (process.env.GITHUB_ACTIONS) console.log(`::error title=Flujo E2E::${desc} ${detalle}`.replace(/\r?\n/g, ' '));
};

async function login(browser, usuario, clave = CLAVE) {
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await page.goto(BASE);
  await page.fill('#txt_usuario', usuario);
  await page.fill('#txt_contra', clave);
  await page.click('#entrar');
  return { ctx, page };
}

async function entrar(browser, usuario) {
  const s = await login(browser, usuario);
  await s.page.waitForURL('**/view/index.php', { timeout: 20000 });
  await s.page.waitForLoadState('networkidle').catch(() => {});
  return s;
}

/** Petición desde la sesión del navegador. datos: objeto (urlencoded) o {archivos: true, ...} (multipart). */
async function pedir(page, ruta, datos = null, metodo = 'POST') {
  return page.evaluate(async ({ url, datos, metodo }) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let body;
    const headers = { 'X-CSRF-Token': token };
    if (datos && datos.__archivo) {
      body = new FormData();
      for (const [k, v] of Object.entries(datos)) if (k !== '__archivo') body.append(k, v);
      body.append('archivos[]', new Blob(['%PDF-1.4 prueba E2E'], { type: 'application/pdf' }), datos.__archivo);
    } else if (datos) {
      body = new URLSearchParams(datos);
      headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
    }
    const r = await fetch(url, { method: metodo, headers, body, credentials: 'same-origin' });
    const texto = await r.text();
    let json = null; try { json = JSON.parse(texto); } catch { /* texto plano */ }
    return { estado: r.status, tipo: (r.headers.get('content-type') || '').split(';')[0], texto: texto.trim(), json };
  }, { url: BASE + ruta, datos, metodo });
}
const filas = (r) => (Array.isArray(r.json) ? r.json : r.json?.data ?? r.json?.aaData ?? []);

const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
  console.log('1. Login');
  {
    const { ctx, page } = await entrar(browser, ESC.admin);
    check(page.url().endsWith('view/index.php'), 'credenciales correctas llevan al panel');
    await ctx.close();
  }
  for (const [usuario, clave, esperado, desc] of [
    [ESC.admin, 'clave-incorrecta', 'Incorrectos', 'contraseña incorrecta muestra el error y no entra'],
    [ESC.inactivo, CLAVE, 'inactivo', 'usuario inactivo no entra'],
  ]) {
    const { ctx, page } = await login(browser, usuario, clave);
    const msg = await page.locator('.swal2-html-container').textContent({ timeout: 10000 }).catch(() => '');
    check(msg.includes(esperado) && !page.url().endsWith('view/index.php'), desc, `(mensaje: "${msg}")`);
    await ctx.close();
  }

  console.log('2. Autorización');
  {
    const anon = await (await browser.newContext()).newPage();
    await anon.goto(BASE);
    check((await pedir(anon, 'controller/alumnos/controlador_listar_alumnos.php')).estado === 401, 'anónimo → 401');
    const { ctx, page } = await entrar(browser, ESC.docente);
    check((await pedir(page, 'controller/alumnos/controlador_listar_alumnos.php')).estado === 403, 'docente en endpoint de administrador → 403');
    check((await pedir(page, 'controller/usuario/controlador_listar_usuario.php')).estado === 403, 'docente no lista usuarios → 403');
    await ctx.close();
    const est = await entrar(browser, ESC.estudiante);
    const ajeno = await pedir(est.page, `view/MPDF/REPORTE/kardex.php?codigo=${ESC.pago.matricula}`, null, 'GET');
    check(ajeno.estado === 403, 'estudiante no ve el kardex de otra matrícula → 403');
    const propio = await pedir(est.page, `view/MPDF/REPORTE/kardex.php?codigo=${ESC.matriculaEstudiante}`, null, 'GET');
    check(propio.estado === 200 && propio.tipo === 'application/pdf', 'estudiante ve su propio kardex (PDF)',
      `(${propio.estado} ${propio.tipo} ${String(propio.texto ?? '').slice(0, 200)})`);
    await est.ctx.close();
  }

  console.log('3. Pago de pensión y boleta');
  {
    const { ctx, page } = await entrar(browser, ESC.admin);
    const datos = { id_matri: ESC.pago.matricula, concepto: 'PENSION', id_pension: ESC.pago.pension, monto: '100.00' };
    check((await pedir(page, 'controller/pago_pension/controlador_detalle_pago_pension.php', datos)).texto === '1', 'registrar el pago responde 1');
    check((await pedir(page, 'controller/pago_pension/controlador_detalle_pago_pension.php', datos)).texto === '2', 'repetir el mismo pago responde 2 (no se cobra dos veces)');
    const pagos = filas(await pedir(page, 'controller/pago_pension/controlador_listar_tabla_pagos.php', { id: ESC.pago.matricula }));
    const pago = pagos.find((p) => String(p.id_pension ?? p[3]) === String(ESC.pago.pension) || JSON.stringify(p).includes('"PENSION"'));
    check(Boolean(pago), 'el pago aparece en la tabla de pagos de la matrícula');
    const idPago = pago?.id_pago_pension ?? pago?.[0];
    const boleta = await pedir(page, `view/MPDF/REPORTE/boleta_pago.php?codigo=${ESC.pago.matricula}&idpagopen=${idPago}`, null, 'GET');
    check(boleta.estado === 200 && boleta.tipo === 'application/pdf', 'la boleta del pago se genera en PDF');
    await ctx.close();
  }

  console.log('4. Matrícula de un alumno nuevo');
  {
    const { ctx, page } = await entrar(browser, ESC.admin);
    const r = await pedir(page, 'controller/matricula/controlador_registro_matriculas.php', {
      estu: ESC.alumnoNuevo, año: ESC.anioEscolar, aula: ESC.aula, admi: '50', nuevo: '30', matri: '150',
      proce: 'COLEGIO X', pro: 'LIMA', depa: 'LIMA', usu: 'mat18e2e', contra: 'Clave.Nueva1', correo: 'm18@example.com',
    });
    check(r.texto === '1', 'matricular responde 1', `(${r.texto.slice(0, 80)})`);
    const otra = await pedir(page, 'controller/matricula/controlador_registro_matriculas.php', {
      estu: ESC.alumnoNuevo, año: ESC.anioEscolar, aula: ESC.aula, admi: '50', nuevo: '30', matri: '150',
      proce: 'X', pro: 'X', depa: 'X', usu: 'otro18', contra: 'x', correo: 'x@example.com',
    });
    check(otra.texto === '2', 'no se matricula dos veces en el mismo año');
    await ctx.close();
    // El usuario entra tal como se escribió (antes: la matrícula lo guardaba en mayúsculas y el login
    // distinguía mayúsculas, así que «mat18e2e» solo entraba escribiendo «MAT18E2E»).
    for (const escrito of ['mat18e2e', 'MAT18E2E']) {
      const nuevo = await login(browser, escrito, 'Clave.Nueva1');
      await nuevo.page.waitForURL('**/view/index.php', { timeout: 20000 }).catch(() => {});
      check(nuevo.page.url().endsWith('view/index.php'), `el alumno matriculado entra con su cuenta escrita como «${escrito}»`);
      await nuevo.ctx.close();
    }

    // Un usuario de más de 8 caracteres se guarda completo (antes se truncaba sin aviso).
    const adm = await entrar(browser, ESC.admin);
    await pedir(adm.page, 'controller/matricula/controlador_registro_matriculas.php', {
      estu: ESC.otroAlumnoNuevo, año: ESC.anioEscolar, aula: ESC.aula, admi: '50', nuevo: '30', matri: '150',
      proce: 'X', pro: 'X', depa: 'X', usu: 'usuariolargo19', contra: 'Clave.Nueva1', correo: 'l19@example.com',
    });
    await adm.ctx.close();
    const largo = await login(browser, 'usuariolargo19', 'Clave.Nueva1');
    await largo.page.waitForURL('**/view/index.php', { timeout: 20000 }).catch(() => {});
    check(largo.page.url().endsWith('view/index.php'), 'un usuario de 14 caracteres entra con el nombre completo');
    await largo.ctx.close();
  }

  console.log('5. Notas (docente)');
  {
    const { ctx, page } = await entrar(browser, ESC.docente);
    const registros = [{ id_matri: ESC.matriculaEstudiante, perio: ESC.periodo, cri: ESC.criterio, nota: '18', conclu: 'E2E' }];
    const r = await pedir(page, 'controller/notas/controlador_registro_notas.php', { registros: JSON.stringify(registros) });
    check(r.json?.status === 1, 'el docente registra una nota de su curso', `(${r.texto.slice(0, 80)})`);
    const ajena = await pedir(page, 'controller/notas/controlador_registro_notas.php',
      { registros: JSON.stringify([{ ...registros[0], id_matri: ESC.matriculaOtraAula }]) });
    check(ajena.estado === 403, 'el docente no puede poner notas a un alumno que no es de su curso → 403');
    // Lote mixto: la nota anterior ya existe y no se duplica; solo cuenta la nueva → status 2 (parcial).
    const mixto = await pedir(page, 'controller/notas/controlador_registro_notas.php', { registros: JSON.stringify([
      registros[0], { ...registros[0], cri: ESC.otroCriterio, nota: '15' },
    ]) });
    check(mixto.json?.status === 2 && Number(mixto.json?.inserted_count) === 1,
      'un lote con una nota ya registrada informa 1 insertada de 2', `(${mixto.texto.slice(0, 80)})`);
    await ctx.close();
  }

  console.log('6. Tarea: el docente publica y el alumno entrega');
  {
    const doc = await entrar(browser, ESC.docente);
    const pub = await pedir(doc.page, 'controller/tareas/controlador_registro_tareas.php', {
      __archivo: 'enunciado.pdf', asig: ESC.cursoDocente, tema: 'TAREA E2E', fecha: '2025-12-30', descrip: 'Prueba automática',
    });
    check(pub.estado === 200 && pub.texto === '1', 'el docente publica la tarea con un PDF', `(${pub.estado} ${pub.texto.slice(0, 80)})`);
    const malo = await pedir(doc.page, 'controller/tareas/controlador_registro_tareas.php', {
      __archivo: 'shell.php', asig: ESC.cursoDocente, tema: 'X', fecha: '2025-12-30', descrip: 'X',
    });
    check(malo.estado === 422, 'un .php como enunciado se rechaza → 422');
    await doc.ctx.close();

    const est = await entrar(browser, ESC.estudiante);
    const tareas = filas(await pedir(est.page, 'controller/tareas/controlador_listar_tareas_estudiante_solo.php', { id: 0 }));
    const tarea = tareas.find((t) => JSON.stringify(t).includes('TAREA E2E'));
    check(Boolean(tarea), 'el alumno ve la tarea publicada');
    const iddetalle = tarea?.id_detalle_tarea;
    const entrega = await pedir(est.page, 'controller/tareas/controlador_registro_tareas_estudiante.php', {
      __archivo: 'mi_entrega.pdf', iddetalle, archivoactual: '',
    });
    check(entrega.estado === 200, 'el alumno entrega la tarea', `(${entrega.estado} ${entrega.texto.slice(0, 80)})`);
    await est.ctx.close();

    const doc2 = await entrar(browser, ESC.docente);
    const envios = filas(await pedir(doc2.page, 'controller/tareas/controlador_listar_tabla_envio_tareas.php', { id: tarea?.id_tarea }));
    const envio = envios.find((e) => String(e.id_detalle_tarea) === String(iddetalle));
    check(Boolean(envio?.archivo_evnio_tarea), 'el docente ve la entrega del alumno');
    if (envio?.archivo_evnio_tarea) {
      const lista = await pedir(doc2.page, `controller/tareas/controlador_descargar_tarea.php?carpeta=${encodeURIComponent(envio.archivo_evnio_tarea)}`, null, 'GET');
      check(lista.estado === 200 && lista.texto.includes('MI_ENTREGA.PDF'), 'el docente puede descargar el archivo entregado');
    }
    await doc2.ctx.close();
  }

  console.log('7. Asistencia (auxiliar)');
  {
    const { ctx, page } = await entrar(browser, ESC.auxiliar);
    const registros = [{ id_matri: ESC.matriculaEstudiante, fecha: '2025-12-26', esta: 'ASISTIO', obse: '' }];
    const r = await pedir(page, 'controller/asistencias/controlador_registro_asistencias.php', { registros: JSON.stringify(registros) });
    check(r.texto === '1', 'el auxiliar registra la asistencia', `(${r.texto.slice(0, 80)})`);
    const otra = await pedir(page, 'controller/asistencias/controlador_registro_asistencias.php', { registros: JSON.stringify(registros) });
    check(otra.texto === '2', 'registrar la misma asistencia otra vez responde 2 (no duplica)');
    await ctx.close();
  }
} finally {
  await browser.close();
}
console.log(`\n${ok} comprobaciones correctas, ${fallos} fallos`);
process.exitCode = fallos ? 1 : 0;
