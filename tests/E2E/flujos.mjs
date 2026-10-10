// Flujos E2E críticos (Fase 2.4 del plan), en Chrome real contra la app con datos de prueba.
//
// Login y navegación se hacen por la interfaz. Las escrituras se envían desde la sesión del
// navegador (cookie + token CSRF del panel) con los mismos parámetros que arma el JS de cada
// pantalla, y se comprueba su efecto leyendo por los mismos endpoints que usa la interfaz.
//
// MODIFICA la BD: ejecutar después de la caracterización, o recargar DatosPrueba antes de repetir.
// Uso: BASE_URL=http://127.0.0.1:8099/ node flujos.mjs
import { chromium } from 'playwright-core';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8099/';
const CLAVE = 'Prueba.2026';
// Escenario del dataset de prueba (ver tests/E2E/README.md).
const ESC = {
  admin: 'usuario9', docente: 'usuario10', auxiliar: 'usuario22', estudiante: 'usuario62', inactivo: 'usuario33',
  cursoDocente: 20, aula: 5, criterio: 1, otroCriterio: 8, periodo: 44, matriculaEstudiante: 40,
  pago: { matricula: 31, pension: 36 }, alumnoNuevo: 18, otroAlumnoNuevo: 19, anioEscolar: 5,
  matriculaOtraAula: 33,
  // Docente que ningún otro flujo usa: se renombra, cambia de contraseña y se desactiva.
  cuenta: { id: '11', rol: '2', correo: 'usuario11@example.com' },
  // Alumnos: 70000001 tiene matrícula; 70000014 (id 20) no, y ningún otro flujo lo usa.
  alumnoMatriculadoDni: '70000001', alumnoSinMatriculaDni: '70000014',
  // Matrícula §10: el alumno 20 (sin matrícula) en el año 5 (2025) y en el 2 (2024); 6 es otro alumno.
  matriculaFlujo: { alumno: '20', dni: '70000014', anio: '5', otroAnio: '2', aula: '5' }, alumnoAjeno: '6',
  // Notas §11 (administrador): matrícula 40 en el periodo 12, que §5 no usa.
  notasFlujo: { matricula: '40', periodo: '12', criterio: '1', otroCriterio: '8' },
  // Asistencia §12 (auxiliar): un día pasado, distinto del de §7.
  asistenciaFlujo: { matricula: '40', aula: '5', fecha: '2025-12-01' },
  // Horarios §14: el aula 5 tiene toda la semana ocupada; la asignatura 2 tiene docente asignado.
  horarioFlujo: { aula: '5', enUso: '2', celda: { hora: '41', dia: 'LUNES', curso: '20', otroCurso: '21' } },
  // Pagos §15: la pensión 36 (nivel 1, marzo 2026) tiene pagos; el §3 paga una más hoy.
  pagosFlujo: { pensionPagada: '36', nivel: '1' },
  // Salud §17: usuario32 (id 32) es la psicóloga y usuario50 (id 50) la enfermera.
  salud: { psicologa: { usuario: 'usuario32', id: '32' }, enfermera: { usuario: 'usuario50', id: '50' } },
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

/**
 * Petición desde la sesión del navegador. datos: objeto (urlencoded), o multipart si trae
 * __archivo (un PDF en archivos[]) o __foto (un PNG de 1×1 válido en el campo foto).
 */
async function pedir(page, ruta, datos = null, metodo = 'POST') {
  return page.evaluate(async ({ url, datos, metodo }) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let body;
    const headers = { 'X-CSRF-Token': token };
    if (datos && (datos.__archivo || datos.__foto)) {
      body = new FormData();
      for (const [k, v] of Object.entries(datos)) if (!k.startsWith('__')) body.append(k, v);
      if (datos.__archivo) body.append('archivos[]', new Blob(['%PDF-1.4 prueba E2E'], { type: 'application/pdf' }), datos.__archivo);
      if (datos.__foto) {
        const png = Uint8Array.from(atob('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='), (c) => c.charCodeAt(0));
        body.append('foto', new Blob([png], { type: 'image/png' }), datos.__foto);
      }
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
    const registros = [{ id_matri: ESC.matriculaEstudiante, fecha: '2025-12-26', esta: 'PRESENTE', obse: '' }];
    const r = await pedir(page, 'controller/asistencias/controlador_registro_asistencias.php', { registros: JSON.stringify(registros) });
    check(r.texto === '1', 'el auxiliar registra la asistencia', `(${r.texto.slice(0, 80)})`);
    const otra = await pedir(page, 'controller/asistencias/controlador_registro_asistencias.php', { registros: JSON.stringify(registros) });
    check(otra.texto === '2', 'registrar la misma asistencia otra vez responde 2 (no duplica)');
    await ctx.close();
  }

  console.log('8. Cuentas de usuario (administrador)');
  {
    const entra = async (usuario, clave) => {
      const s = await login(browser, usuario, clave);
      await s.page.waitForURL('**/view/index.php', { timeout: 15000 }).catch(() => {});
      const dentro = s.page.url().endsWith('view/index.php');
      await s.ctx.close();
      return dentro;
    };
    const { id, rol, correo } = ESC.cuenta;
    const adm = await entrar(browser, ESC.admin);
    const modificar = (usu) => pedir(adm.page, 'controller/usuario/controlador_modificar_usuario.php', { id, usu, rol, correo });

    check((await modificar('cuenta.e2e')).texto === '1', 'el administrador renombra una cuenta');
    check(await entra('cuenta.e2e', CLAVE), 'la cuenta entra con su nuevo nombre');
    check((await modificar(ESC.admin)).texto === '2', 'no se puede renombrar a un usuario que ya existe → 2');
    check((await modificar('u'.repeat(30))).texto === '1', 'un nombre de 30 caracteres se guarda');
    check(await entra('u'.repeat(30), CLAVE), 'y entra con el nombre completo (no truncado)');

    const contra = await pedir(adm.page, 'controller/usuario/controlador_modificar_usuario_contra.php', { id, con: 'Otra.Clave&2026' });
    check(contra.texto === '1', 'el administrador cambia la contraseña');
    check(await entra('u'.repeat(30), 'Otra.Clave&2026'), 'entra con la contraseña nueva (con caracteres especiales)');
    check(!(await entra('u'.repeat(30), CLAVE)), 'la contraseña anterior ya no sirve');

    const estatus = (e) => pedir(adm.page, 'controller/usuario/controlador_modificar_usuario_estatus.php', { id, estatus: e });
    check((await estatus('INACTIVO')).texto === '1', 'el administrador desactiva la cuenta');
    check(!(await entra('u'.repeat(30), 'Otra.Clave&2026')), 'la cuenta desactivada no entra');
    check((await estatus('CUALQUIERA')).texto === '0', 'un estado inválido se rechaza → 0');
    check((await estatus('ACTIVO')).texto === '1', 'el administrador la reactiva');
    check(await entra('u'.repeat(30), 'Otra.Clave&2026'), 'la cuenta reactivada entra');
    await adm.ctx.close();

    const doc = await entrar(browser, ESC.docente);
    check((await pedir(doc.page, 'controller/usuario/controlador_modificar_usuario_estatus.php', { id, estatus: 'INACTIVO' })).estado === 403,
      'un docente no puede cambiar el estado de una cuenta → 403');
    await doc.ctx.close();
  }

  console.log('9. Alumnos (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    const ruta = (r) => `controller/alumnos/controlador_${r}.php`;
    const alumnos = async () => filas(await pedir(adm.page, ruta('listar_alumnos')));
    const alumno = async (dni) => (await alumnos()).find((a) => a.alum_dni === dni);
    const ficha = (dni, extra = {}) => ({
      dni, nombre: 'Prueba', apepa: 'Flujo', apema: 'Nuevo', sexo: 'FEMENINO', fechanaci: '2015-03-04', telf: '900000001',
      direc: 'Calle E2E 1', nombrefoto: '', dnipa: '40000001', nompa: 'Padre E2E', celpa: '911111111',
      dnima: '40000002', nomma: 'Madre E2E', celma: '922222222', ...extra,
    });

    check((await pedir(adm.page, ruta('registrar_alumno'), ficha('79999991'))).texto === '1', 'registra un alumno con sus padres');
    const nuevo = await alumno('79999991');
    check(nuevo?.alum_nombre === 'PRUEBA' && nuevo?.Datos_mama === 'MADRE E2E', 'el alumno y sus padres aparecen en el listado (en mayúsculas)');
    check((await pedir(adm.page, ruta('registrar_alumno'), ficha('79999991'))).texto === '2', 'un DNI repetido responde 2');
    check((await pedir(adm.page, ruta('registrar_alumno'), ficha('123456789'))).texto === '0', 'un DNI de más de 8 caracteres se rechaza → 0 (antes se truncaba)');
    check(!(await alumno('12345678')), 'y no queda guardado truncado');
    const conFoto = await pedir(adm.page, ruta('registrar_alumno'), { ...ficha('79999992', { nombrefoto: 'x.png' }), __foto: 'x.png' });
    check(conFoto.texto === '1' && /^controller\/alumnos\/fotos\/.+\.png$/.test((await alumno('79999992'))?.alum_fotoperfil ?? ''),
      'registra con foto: el servidor genera el nombre', `(${conFoto.texto.slice(0, 80)})`);
    // Fase 4.6: la foto va al almacén de la institución y la misma URL de siempre la sirve con sesión.
    const rutaFoto = (await alumno('79999992'))?.alum_fotoperfil ?? '';
    check(rutaFoto !== '' && !existsSync(fileURLToPath(new URL('../../' + rutaFoto, import.meta.url))),
      'la foto no queda en la carpeta pública de antes', rutaFoto);
    const verFoto = await adm.page.evaluate(async (u) => {
      const r = await fetch(u);
      return { estado: r.status, tipo: r.headers.get('content-type') };
    }, BASE + rutaFoto);
    check(verFoto.estado === 200 && verFoto.tipo === 'image/png', 'el panel la ve en su URL de siempre', JSON.stringify(verFoto));
    const anonimo = await (await fetch(BASE + rutaFoto)).status;
    check(anonimo === 401, 'sin sesión no se ve (401)', `(${anonimo})`);

    // Modificar: idpa apunta a los padres de OTRO alumno (como si el formulario trajera un id ajeno).
    const victima = await alumno(ESC.alumnoMatriculadoDni);
    const objetivo = await alumno(ESC.alumnoSinMatriculaDni);
    const cambio = await pedir(adm.page, ruta('modificar_alumno'), {
      ...ficha(ESC.alumnoSinMatriculaDni, { direc: 'Nueva Direccion 9', nompa: 'Padre Cambiado' }),
      id: String(objetivo.Id_alumno), idpa: String(victima.id_papas), fotoactual: objetivo.alum_fotoperfil,
    });
    check(cambio.texto === '1', 'modifica los datos del alumno');
    check((await alumno(ESC.alumnoSinMatriculaDni))?.Datos_papa === 'PADRE CAMBIADO', 'los padres del alumno se actualizan');
    check((await alumno(ESC.alumnoMatriculadoDni))?.Datos_papa === victima.Datos_papa,
      'los padres de otro alumno no cambian aunque llegue su id');
    check((await alumno(ESC.alumnoSinMatriculaDni))?.alum_fotoperfil === objetivo.alum_fotoperfil, 'sin foto nueva se conserva la actual');
    const dniAjeno = await pedir(adm.page, ruta('modificar_alumno'), {
      ...ficha(ESC.alumnoMatriculadoDni), id: String(objetivo.Id_alumno), idpa: String(objetivo.id_papas), fotoactual: '',
    });
    check(dniAjeno.texto === '2', 'no se puede poner el DNI de otro alumno → 2');

    const borrarMatriculado = await pedir(adm.page, ruta('eliminar_alumnos'), { id: ESC.alumnoMatriculadoDni });
    check(borrarMatriculado.texto === '0' && !!(await alumno(ESC.alumnoMatriculadoDni)),
      'un alumno con matrícula no se elimina y responde 0 (el panel muestra el aviso)', `(${borrarMatriculado.estado} ${borrarMatriculado.texto.slice(0, 60)})`);
    check((await pedir(adm.page, ruta('eliminar_alumnos'), { id: '79999991' })).texto === '1' && !(await alumno('79999991')),
      'un alumno sin matrícula se elimina');
    await adm.ctx.close();

    const doc = await entrar(browser, ESC.docente);
    check((await pedir(doc.page, ruta('eliminar_alumnos'), { id: '79999992' })).estado === 403, 'un docente no puede eliminar alumnos → 403');
    await doc.ctx.close();

    // El estudiante cambia SU foto aunque envíe el DNI de otro (IDOR).
    const antes = victima; // leído antes con la sesión del administrador (ya cerrada)
    const est = await entrar(browser, ESC.estudiante);
    const foto = await pedir(est.page, ruta('modificar_foto_estudiante'), { id: ESC.alumnoMatriculadoDni, nombrefoto: 'yo.png', __foto: 'yo.png' });
    await est.ctx.close();
    const adm2 = await entrar(browser, ESC.admin);
    const lista = filas(await pedir(adm2.page, ruta('listar_alumnos')));
    await adm2.ctx.close();
    const propio = lista.find((a) => /^controller\/alumnos\/fotos\/IMG.+\.png$/.test(a.alum_fotoperfil) && a.alum_dni !== '79999992');
    check(foto.texto === '1' && !!propio, 'el estudiante cambia su propia foto', `(${foto.texto.slice(0, 60)})`);
    check(lista.find((a) => a.alum_dni === ESC.alumnoMatriculadoDni)?.alum_fotoperfil === antes.alum_fotoperfil,
      'y no la de otro alumno aunque envíe su DNI');
  }

  console.log('10. Matrícula: registrar, modificar y eliminar (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    const ruta = (r) => `controller/matricula/controlador_${r}.php`;
    const { alumno, dni, anio, otroAnio, aula } = ESC.matriculaFlujo;
    const matriculas = async () => filas(await pedir(adm.page, ruta('listar_matriculas'))).filter((m) => m.alum_dni === dni);
    const de = async (a) => (await matriculas()).find((m) => String(m['id_año']) === String(a));
    const registrar = (extra) => pedir(adm.page, ruta('registro_matriculas'), {
      estu: alumno, 'año': anio, aula, admi: '0', nuevo: '0', matri: '0', proce: 'X', pro: 'X', depa: 'X',
      usu: 'nuevo20e2e', contra: 'Clave.Mat20', correo: 'n20@example.com', ...extra,
    });
    const modificar = (m, extra) => pedir(adm.page, ruta('modificar_matrícula'), {
      id: String(m.id_matricula), estu: alumno, 'año': String(m['id_año']), aula: String(m.id_aula),
      admi: '0', nuevo: '0', matri: '0', proce: 'X', pro: 'X', depa: 'X', ...extra,
    });
    const eliminar = (id) => pedir(adm.page, ruta('eliminar_matricula'), { id: String(id) });
    const entra = async (u, c) => {
      const s = await login(browser, u, c);
      await s.page.waitForURL('**/view/index.php', { timeout: 15000 }).catch(() => {});
      const dentro = s.page.url().endsWith('view/index.php');
      await s.ctx.close();
      return dentro;
    };

    check((await registrar({ usu: ESC.admin })).texto === '3' && !(await de(anio)),
      'con un usuario que ya existe responde 3 y no matricula (antes duplicaba la cuenta)');
    check((await registrar({ matri: '123456789' })).texto === '0' && !(await de(anio)),
      'un monto que no cabe en la columna se rechaza → 0 (no se recorta)');
    check((await registrar()).texto === '1', 'matricula a un alumno nuevo');
    check(await entra('nuevo20e2e', 'Clave.Mat20'), 'el alumno entra con la cuenta creada');
    check((await registrar({ 'año': otroAnio })).texto === '1', 'lo matricula también en otro año (ya es ANTIGUO)');

    const delOtroAnio = await de(otroAnio);
    check((await modificar(delOtroAnio, { 'año': anio })).texto === '2', 'no se puede mover una matrícula a un año donde el alumno ya está → 2');
    check((await modificar(delOtroAnio, { estu: ESC.alumnoAjeno })).texto === '1' && !!(await de(otroAnio)),
      'modificar no cambia el alumno de la matrícula aunque llegue otro id');

    check((await eliminar(ESC.matriculaEstudiante)).texto === '2',
      'una matrícula con notas y asistencias no se elimina → 2 (antes se borraban en cascada)');
    check((await eliminar(delOtroAnio.id_matricula)).texto === '1' && (await de(anio))?.tipo_alum === 'ANTIGUO',
      'una matrícula sin registros ni ingresos se elimina; con otra matrícula sigue siendo ANTIGUO');
    check((await eliminar((await de(anio)).id_matricula)).texto === '1', 'elimina su última matrícula');
    const alumnos = filas(await pedir(adm.page, 'controller/alumnos/controlador_listar_alumnos.php'));
    check(alumnos.find((a) => a.alum_dni === dni)?.tipo_alum === 'NUEVO', 'sin matrículas, el alumno vuelve a ser NUEVO');
    check(!(await entra('nuevo20e2e', 'Clave.Mat20')), 'y su cuenta, que quedó sin uso, se elimina');
    check((await registrar({ contra: 'Otra.Mat20', admi: '100' })).texto === '1' && await entra('nuevo20e2e', 'Otra.Mat20'),
      'puede volver a matricularse como NUEVO con el mismo usuario');
    check((await eliminar((await de(anio)).id_matricula)).texto === '2',
      'una matrícula con ingresos válidos no se elimina → 2 (antes se borraban los ingresos)');
    await adm.ctx.close();
  }

  console.log('11. Notas: registro, edición y notas de los padres (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    const ruta = (r) => `controller/notas/controlador_${r}.php`;
    const { matricula, periodo, criterio, otroCriterio } = ESC.notasFlujo;
    const enviar = (r, registros) => pedir(adm.page, ruta(r), { registros: JSON.stringify(registros) });
    const notas = async () => filas(await pedir(adm.page, ruta('listar_criterios_notas_mostrar'), { matri: matricula, bime: periodo }));
    const deCriterio = async (c) => (await notas()).find((n) => String(n.id_criterio) === String(c));
    const xss = '<img src=x onerror=alert(1)>';

    const r1 = await enviar('registro_notas', [{ id_matri: matricula, perio: periodo, cri: criterio, nota: '15', conclu: xss }]);
    check(r1.json?.status === 1, 'registra una nota con su conclusión', `(${r1.texto.slice(0, 80)})`);
    const guardada = await deCriterio(criterio);
    check(!!guardada && !String(guardada.conclusiones).includes('<img'),
      'la conclusión se guarda escapada: no se ejecuta al mostrarla (XSS)', `(${JSON.stringify(guardada ?? {}).slice(0, 160)})`);
    const r2 = await enviar('registro_notas', [{ id_matri: matricula, perio: periodo, cri: otroCriterio, nota: 'XYZ', conclu: '' }]);
    check(r2.json?.status === 0 && !(await deCriterio(otroCriterio)), 'una nota fuera de la escala (0–20, AD/A/B/C) se rechaza');

    const largo = await enviar('editar_notas', [{ id_nota_bole: guardada?.id_nota_bole, nota: '16', conclusiones: 'x'.repeat(300) }]);
    check(largo.json?.status === 2 && (await deCriterio(criterio))?.nota?.trim() === '15',
      'una conclusión de más de 255 caracteres se rechaza (antes se truncaba)', `(${largo.texto.slice(0, 120)})`);
    const bien = await enviar('editar_notas', [{ id_nota_bole: guardada?.id_nota_bole, nota: 'ad', conclusiones: 'Logro destacado' }]);
    check(bien.json?.status === 1 && (await deCriterio(criterio))?.nota?.trim() === 'AD', 'edita la nota (en mayúsculas)');
    const inexistente = await enviar('editar_notas', [{ id_nota_bole: 99999999, nota: '10', conclusiones: '' }]);
    check(inexistente.json?.status === 2 && !/SQLSTATE|base de datos/i.test(inexistente.texto),
      'una nota inexistente informa el error sin detalles de la BD', `(${inexistente.texto.slice(0, 120)})`);

    const padres = [{ id_matri: matricula, perio: periodo, competencia: 'Responsabilidad E2E', nota: 'A' }];
    check((await enviar('registro_notas_padres', padres)).json?.status === 1, 'registra la nota de los padres');
    await enviar('registro_notas_padres', [{ ...padres[0], nota: 'B' }]);
    const dePadres = filas(await pedir(adm.page, ruta('listar_criterios_notas_mostrar_padres'), { matri: matricula, bime: periodo }))
      .filter((n) => n.criterio === 'RESPONSABILIDAD E2E' || n.criterio === 'Responsabilidad E2E');
    check(dePadres.length === 1 && dePadres[0].nota?.trim() === 'B',
      'guardarla otra vez la actualiza, no la duplica', `(${dePadres.length} filas: ${JSON.stringify(dePadres).slice(0, 160)})`);
    await adm.ctx.close();

    const doc = await entrar(browser, ESC.docente);
    check((await pedir(doc.page, ruta('editar_notas'), { registros: '[]' })).estado === 403, 'un docente no puede editar notas → 403');
    await doc.ctx.close();
  }

  console.log('12. Asistencia: fechas pasadas, edición y observaciones (auxiliar)');
  {
    const aux = await entrar(browser, ESC.auxiliar);
    const ruta = (r) => `controller/asistencias/controlador_${r}.php`;
    const { matricula, aula, fecha } = ESC.asistenciaFlujo;
    const enviar = (r, registros) => pedir(aux.page, ruta(r), { registros: JSON.stringify(registros) });
    const delDia = async (f = fecha) => filas(await pedir(aux.page, ruta('listar_alumnos_asistencia'), { fecha: f, aula }))
      .filter((a) => String(a.id_matricula) === matricula && a.id_asistencia);
    const xss = '<img src=x onerror=alert(1)>';

    const r1 = await enviar('registro_asistencias', [{ id_matri: matricula, fecha, esta: 'TARDE', obse: xss }]);
    check(r1.texto === '1', 'registra la asistencia de un día pasado');
    const r2 = await enviar('registro_asistencias', [{ id_matri: matricula, fecha, esta: 'PRESENTE', obse: '' }]);
    const dia = await delDia();
    check(r2.texto === '2' && dia.length === 1,
      'registrar otra vez ese día responde 2 y no duplica (antes comparaba con la fecha de registro)', `(${r2.texto}, ${dia.length} filas)`);
    check(dia.length > 0 && !String(dia[0].observacion ?? '').includes('<img'),
      'la observación se guarda escapada (XSS)', `(${JSON.stringify(dia[0] ?? {}).slice(0, 160)})`);
    check((await enviar('registro_asistencias', [{ id_matri: matricula, fecha: '2025-12-02', esta: 'ASISTIO', obse: '' }])).texto === '0'
      && (await delDia('2025-12-02')).length === 0, 'un estado fuera de la lista se rechaza → 0 (antes se guardaba vacío)');

    const editada = await enviar('editar_asistencia', [{ id_asis: dia[0]?.id_asistencia, fecha: '2025-12-03', esta: 'JUSTIFICADO', obse: 'Cita médica' }]);
    const tras = await delDia();
    check(editada.texto === '1' && tras.length === 1 && tras[0].estado === 'JUSTIFICADO',
      'edita el estado; la fecha no cambia aunque llegue otra', `(${editada.texto}, ${JSON.stringify(tras[0] ?? {}).slice(0, 120)})`);
    check((await enviar('editar_asistencia', [{ id_asis: 99999999, fecha, esta: 'PRESENTE', obse: '' }])).texto === '2',
      'editar una asistencia inexistente responde 2');
    await aux.ctx.close();
  }

  console.log('13. Anulación de un ingreso (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    const ingresos = async () => filas(await pedir(adm.page, 'controller/ingresos/controlador_listar_ingresos_pensiones.php'));
    // Uno de la matrícula del §10 (ADMISION…), no el de la pensión del §3, que el §15 edita y anula.
    const valido = (await ingresos()).find((i) => i.estado === 'VALIDO' && i.observacion !== 'PENSION');
    const anular = (extra) => pedir(adm.page, 'controller/ingresos/controlador_anular_ingreso.php', { id: String(valido?.id_ingreso), obser: 'Cobro duplicado', usu: '22', ...extra });

    check(!!valido, 'hay un ingreso válido del día para anular', `(${JSON.stringify((await ingresos())[0] ?? {}).slice(0, 160)})`);
    check((await anular({ obser: '  ' })).texto === '0', 'sin motivo no se anula → 0');
    check((await anular()).texto === '1', 'anula el ingreso');
    const despues = (await ingresos()).find((i) => i.id_ingreso === valido?.id_ingreso);
    check(despues?.estado === 'ANULADO' && String(despues?.id_user) === String(valido?.id_user),
      'queda anulado y conserva quién lo cobró aunque el formulario envíe otro usuario', `(${JSON.stringify(despues ?? {}).slice(0, 160)})`);
    check((await anular({ obser: 'Otra vez' })).texto === '0', 'un ingreso ya anulado no se vuelve a anular → 0');
    await adm.ctx.close();
  }

  console.log('14. Asignaturas y horarios (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    const asig = (r, datos) => pedir(adm.page, `controller/asignaturas/controlador_${r}.php`, datos);
    const horario = (componentes) => pedir(adm.page, 'controller/horarios/controlador_registro_horario_aula.php', { componentes: JSON.stringify(componentes) });
    const { aula, enUso, celda } = ESC.horarioFlujo;

    check((await asig('registro_asignaturas', { asigna: 'Robótica E2E', grado: aula, obse: '' })).texto === '1', 'registra una asignatura');
    check((await asig('registro_asignaturas', { asigna: 'robótica e2e', grado: aula, obse: '' })).texto === '2', 'la misma asignatura en el aula responde 2');
    const nueva = filas(await asig('listar_asignaturas')).find((a) => String(a.nombre_asig).startsWith('ROB') && String(a.nombre_asig).includes('E2E'));
    const enUsoResp = await asig('eliminar_asignatura', { id: enUso });
    check(enUsoResp.texto === '0', 'una asignatura con docente asignado no se elimina → 0 (antes daba 500)', `(${enUsoResp.estado} ${enUsoResp.texto.slice(0, 60)})`);
    check(!!nueva && (await asig('eliminar_asignatura', { id: String(nueva.Id_asignatura) })).texto === '1', 'una asignatura sin uso se elimina');

    const ocupada = await horario([{ idhora: celda.hora, idasig: celda.otroCurso, dia: celda.dia }]);
    check(ocupada.texto === '3', 'no se pone un curso en una hora y día que ya tiene otro curso del aula → 3', `(${ocupada.texto})`);
    check((await horario([{ idhora: celda.hora, idasig: celda.curso, dia: celda.dia }])).texto === '2', 'el mismo curso en la misma celda responde 2');
    check((await horario([{ idhora: celda.hora, idasig: celda.curso, dia: 'SABADO' }])).texto === '0', 'un día fuera de lunes a viernes se rechaza → 0');
    await adm.ctx.close();
  }

  console.log('15. Pensiones, pagos e ingresos diversos (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    const p = (r, datos) => pedir(adm.page, `controller/${r}.php`, datos);
    const { pensionPagada, nivel } = ESC.pagosFlujo;
    const diversos = async () => filas(await p('ingresos/controlador_listar_ingresos_diversos'));
    const dePensiones = async () => filas(await p('ingresos/controlador_listar_ingresos_pensiones'));

    // Ingresos diversos: el indicador debe ser de ingresos y el responsable sale de la sesión.
    check((await p('ingresos/controlador_registrar_ingresos', { indi: '2', cantidad: '1', monto: '50', obse: 'X', usu: '22' })).texto === '0',
      'un ingreso con un indicador de gastos se rechaza → 0');
    check((await p('ingresos/controlador_registrar_ingresos', { indi: '4', cantidad: '2', monto: '50', obse: 'Uniformes E2E', usu: '22' })).texto === '1',
      'registra un ingreso diverso');
    const ingreso = (await diversos()).find((i) => i.observacion === 'UNIFORMES E2E');
    check(String(ingreso?.id_user) === '9', 'queda a nombre de quien cobra (la sesión), no del «usu» del formulario', `(${JSON.stringify(ingreso ?? {}).slice(0, 140)})`);
    const editado = await p('ingresos/controlador_modificar_ingreso', { id: String(ingreso?.id_ingreso), indi: '4', cantidad: '2', monto: '60', obser: 'Uniformes E2E', usu: '22' });
    const tras = (await diversos()).find((i) => i.id_ingreso === ingreso?.id_ingreso);
    check(editado.texto === '1' && String(tras?.id_user) === '9' && Number(tras?.monto) === 60,
      'editar cambia el monto pero no quién cobró', `(${editado.texto} ${JSON.stringify(tras ?? {}).slice(0, 140)})`);

    // Pensiones: una por nivel, mes y AÑO; con pagos no se elimina.
    check((await p('pensiones/controlador_registro_pensiones', { nivel, mes: 'MARZO', fecha: '2027-03-31', precio: '150', mora: '5' })).texto === '1',
      'registra la pensión de marzo del año siguiente (antes: «ya existe» para siempre)');
    const borrar = await p('pensiones/controlador_eliminar_pensiones', { id: pensionPagada });
    check(borrar.texto === '0', 'una pensión con pagos no se elimina → 0 (antes daba 500)', `(${borrar.estado} ${borrar.texto.slice(0, 60)})`);

    // Pago de la pensión del §3: editar su monto y anularlo.
    const pagosMatricula = filas(await p('pago_pension/controlador_listar_tabla_pagos', { id: ESC.pago.matricula }));
    const delParagrafo3 = pagosMatricula.find((x) => String(x.id_pension ?? x[3]) === String(ESC.pago.pension));
    const pago = { id_pago_pension: delParagrafo3?.id_pago_pension ?? delParagrafo3?.[0] };
    const nuevoMonto = await p('pago_pension/controlador_modificar_pago_solo', { id: String(pago?.id_pago_pension), monto: '95', descrip: 'Descuento' });
    const ingresoDelPago = (await dePensiones()).find((i) => i.id_pago_pension === pago?.id_pago_pension);
    check(nuevoMonto.texto === '1' && Number(ingresoDelPago?.monto) === 95, 'editar el monto de un pago actualiza su ingreso', `(${JSON.stringify(ingresoDelPago ?? {}).slice(0, 140)})`);
    check((await p('pago_pension/controlador_eliminar_pago_pension', { id: String(pago?.id_pago_pension) })).texto === '1', 'anula el pago');
    // Desligado del pago, el ingreso sigue en la caja del día (listado general) con el motivo.
    const anulado = (await diversos()).find((i) => i.id_ingreso === ingresoDelPago?.id_ingreso);
    check(anulado?.estado === 'ANULADO' && String(anulado?.motivo_anulacion ?? '').startsWith('PAGO ANULADO'),
      'su ingreso queda ANULADO en caja con el motivo (antes se borraba)', `(${JSON.stringify(anulado ?? {}).slice(0, 200)})`);
    const otraVez = await pedir(adm.page, 'controller/pago_pension/controlador_detalle_pago_pension.php',
      { id_matri: ESC.pago.matricula, concepto: 'PENSION', id_pension: ESC.pago.pension, monto: '100.00' });
    check(otraVez.texto === '1', 'la pensión anulada se puede volver a cobrar');
    await adm.ctx.close();
  }

  console.log('16. Tareas y exámenes: plazos, calificación y estados');
  {
    const t = (r) => `controller/tareas/controlador_${r}.php`;
    const tareaDelAlumno = async (page, tema) => filas(await pedir(page, t('listar_tareas_estudiante_solo'), { id: 0 }))
      .find((x) => JSON.stringify(x).includes(tema));

    // El docente publica una tarea vigente y otra ya vencida (la fecha del sistema es 2025-12-26).
    const doc = await entrar(browser, ESC.docente);
    for (const [tema, fecha] of [['VIGENTE E2E', '2025-12-31'], ['VENCIDA E2E', '2025-12-20']]) {
      await pedir(doc.page, t('registro_tareas'), { __archivo: 'enunciado.pdf', asig: ESC.cursoDocente, tema, fecha, descrip: 'x' });
    }
    await doc.ctx.close();

    const est = await entrar(browser, ESC.estudiante);
    const vigente = await tareaDelAlumno(est.page, 'VIGENTE E2E');
    const vencida = await tareaDelAlumno(est.page, 'VENCIDA E2E');
    const enviar = (iddetalle, r = 'registro_tareas_estudiante') => pedir(est.page, t(r), { __archivo: 'entrega.pdf', iddetalle, archivoactual: '' });
    check((await enviar(vigente?.id_detalle_tarea)).texto === '1', 'el alumno entrega una tarea vigente → 1');
    check((await enviar(vencida?.id_detalle_tarea)).texto === '0', 'no se entrega una tarea vencida → 0');
    await est.ctx.close();

    const doc2 = await entrar(browser, ESC.docente);
    const calificar = (nota) => pedir(doc2.page, t('registro_calificacion'), { id: String(vigente?.id_detalle_tarea), nota, obser: 'bien' });
    check((await calificar('25')).texto === '0', 'una calificación fuera de 0–20 se rechaza → 0');
    check((await calificar('18')).texto === '1', 'el docente califica la entrega');
    check((await pedir(doc2.page, t('modificar_estado_tarea'), { id: vigente?.id_tarea, estatus: 'PENDIENTE' })).texto === '0',
      'solo se puede finalizar una tarea (otro estado → 0; antes calificaba con 5 a los pendientes)');
    check((await pedir(doc2.page, t('eliminar_tarea'), { id: vigente?.id_tarea })).texto === '0',
      'una tarea con entregas calificadas no se elimina → 0 (antes se borraban en cascada)');
    await doc2.ctx.close();

    const est2 = await entrar(browser, ESC.estudiante);
    const reenvio = await pedir(est2.page, t('modificar_tareas_estudiante'), { __archivo: 'otra.pdf', iddetalle: vigente?.id_detalle_tarea, archivoactual: '' });
    check(reenvio.texto === '0', 'una entrega ya calificada no se reemplaza → 0 (antes volvía a «ENVIADO»)', `(${reenvio.texto.slice(0, 60)})`);
    await est2.ctx.close();

    const adm = await entrar(browser, ESC.admin);
    const ex = (r, datos) => pedir(adm.page, `controller/examenes/controlador_${r}.php`, datos);
    check((await ex('registro_examenes', { asig: ESC.cursoDocente, tema: 'EXAMEN E2E', fecha: '2025-12-30T10:00', descrip: 'x' })).texto === '1', 'registra un examen con hora');
    const examen = filas(await ex('listar_examenes')).find((e) => e.tema_examen === 'EXAMEN E2E');
    const editado = await ex('modificar_examen', { id: examen?.id_examen, asig: ESC.cursoDocente, tema: 'EXAMEN E2E 2', fecha: '2025-12-30T10:00', descrip: 'x' });
    check(editado.texto === '1', 'editar solo el tema de un examen con hora responde 1 (antes «ya existe»)', `(${editado.texto})`);
    check((await ex('modificar_estado_examen', { id: examen?.id_examen, estatus: 'CUALQUIERA' })).texto === '0', 'un estado de examen inválido se rechaza → 0');
    await adm.ctx.close();
  }

  console.log('17. Atenciones de salud y comunicados');
  {
    const { psicologa, enfermera } = ESC.salud;
    const atencion = (motivo, extra = {}) => ({ estu: ESC.matriculaEstudiante, motivo, diagno: 'D', observa: 'O', idusu: '22', ...extra });
    const psicologicas = async (page) => filas(await pedir(page, 'controller/atencion_psicologica/controlador_listar_atención_psicologica.php'));

    const psi = await entrar(browser, psicologa.usuario);
    check((await pedir(psi.page, 'controller/atencion_psicologica/controlador_registro_psicologia.php', atencion('Ansiedad E2E'))).texto === '1',
      'la psicóloga registra una atención');
    const registrada = (await psicologicas(psi.page)).find((a) => a.motivo_consulta === 'ANSIEDAD E2E');
    check(String(registrada?.id_usuario) === psicologa.id,
      'la atención queda a nombre de quien atendió (la sesión), no del «idusu» del formulario', `(${JSON.stringify(registrada ?? {}).slice(0, 120)})`);
    await psi.ctx.close();

    const enf = await entrar(browser, enfermera.usuario);
    const ajena = await pedir(enf.page, 'controller/atencion_enfermeria/controlador_modificar_atencion_enferme.php',
      atencion('Cambiado por enfermeria', { id: String(registrada?.id_atencion) }));
    check(ajena.texto === '0', 'la enfermera no puede modificar una atención psicológica → 0 (antes la reescribía)', `(${ajena.texto})`);
    await enf.ctx.close();

    const psi2 = await entrar(browser, psicologa.usuario);
    const intacta = (await psicologicas(psi2.page)).find((a) => a.id_atencion === registrada?.id_atencion);
    check(intacta?.motivo_consulta === 'ANSIEDAD E2E', 'la atención psicológica sigue intacta');
    await psi2.ctx.close();

    const adm = await entrar(browser, ESC.admin);
    const comunicado = await pedir(adm.page, 'controller/comunicados/controlador_registro_comunicados.php', {
      tipo: 'GENERAL', grado: '5', titulo: 'Aviso E2E', descripcion: 'Reunión', nombrefoto: 'aviso.png', usu: '22', __foto: 'aviso.png',
    });
    const publicado = filas(await pedir(adm.page, 'controller/comunicados/controlador_listar_comunicados.php')).find((c) => c.titulo === 'AVISO E2E');
    check(comunicado.texto === '1' && String(publicado?.id_usuario) === '9',
      'el comunicado queda a nombre de quien lo publica (la sesión)', `(${comunicado.texto} ${JSON.stringify(publicado ?? {}).slice(0, 120)})`);
    await adm.ctx.close();
  }

  console.log('18. Personalización: color y página pública (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    // Por la interfaz: el botón de la tabla de Empresa abre el formulario con lo guardado.
    await adm.page.waitForSelector('#tabla_empresa .personalizar', { timeout: 20000 });
    await adm.page.click('#tabla_empresa .personalizar');
    await adm.page.waitForSelector('#modal_personalizar.show', { timeout: 10000 });
    check(await adm.page.inputValue('#txt_pers_color') === '', 'el formulario abre sin color propio');
    await adm.page.fill('#txt_pers_color', '#2a6f3b');
    await adm.page.fill('#txt_pers_lema', 'Lema E2E');
    await adm.page.fill('#txt_pers_cifras', '123 | Alumnos E2E');
    await adm.page.click('#modal_personalizar .btn-success');
    await adm.page.waitForSelector('.swal2-popup', { timeout: 10000 });
    check((await adm.page.textContent('.swal2-popup')).includes('guardada'), 'se guarda desde el panel');
    const publica = await (await fetch(BASE + 'landing.html')).text();
    check(publica.includes('Lema E2E') && publica.includes('data-target="123"') && publica.includes('#2a6f3b'),
      'la página pública pasa a ser la de la institución, con su color');
    const acceso = await (await fetch(BASE + 'index.php')).text();
    check(acceso.includes('--color-institucion:#2a6f3b'), 'el acceso lleva su color');
    const docente = await entrar(browser, ESC.docente);
    const ajeno = await pedir(docente.page, 'controller/empresa/controlador_modificar_personalizacion.php', { color: '#000000' });
    check(ajeno.estado === 403, 'solo el administrador personaliza (403)', `(${ajeno.estado})`);
    await docente.ctx.close();
    // Se deja como estaba: sin textos propios, en modo único vuelve la página de siempre.
    const quitar = await pedir(adm.page, 'controller/empresa/controlador_modificar_personalizacion.php', { color: '' });
    const deSiempre = await (await fetch(BASE + 'landing.html')).text();
    check(quitar.texto === '1' && deSiempre.includes('Colegio Diocesano') && !deSiempre.includes('Lema E2E'),
      'sin textos propios, en modo único vuelve la página de siempre');
    await adm.ctx.close();
  }

  console.log('19. Configuración académica (administrador)');
  {
    const adm = await entrar(browser, ESC.admin);
    const inicial = await adm.page.evaluate(() => window.INSTITUCION);
    check(inicial?.tipo === 'COLEGIO' && inicial?.etiquetaPeriodo === 'Bimestre' && inicial?.apoderadoObligatorio === true,
      'el panel conoce la configuración de la institución (un colegio, por defecto)', JSON.stringify(inicial));
    await adm.page.waitForSelector('#tabla_empresa .configuracion', { timeout: 20000 });
    await adm.page.click('#tabla_empresa .configuracion');
    await adm.page.waitForSelector('#modal_configuracion.show', { timeout: 10000 });
    check(await adm.page.inputValue('#cfg_evaluacion_nota_minima') === '11', 'el formulario muestra la nota mínima de un colegio (11)');
    await adm.page.selectOption('#cfg_periodo_tipo', 'SEMESTRE');
    await adm.page.selectOption('#cfg_apoderado_obligatorio', '0');
    await adm.page.click('#modal_configuracion .btn-success');
    await adm.page.waitForSelector('.swal2-popup', { timeout: 10000 });
    await adm.page.click('.swal2-confirm');
    await adm.page.waitForLoadState('networkidle').catch(() => {});
    const cambiada = await adm.page.evaluate(() => ({ ...window.INSTITUCION, etiqueta: etiquetaPeriodo() }));
    check(cambiada.etiquetaPeriodo === 'Semestre' && cambiada.etiqueta === 'semestre' && cambiada.apoderadoObligatorio === false,
      'tras guardar, el panel habla de semestres y el apoderado es opcional', JSON.stringify(cambiada));
    const invalida = await pedir(adm.page, 'controller/institucion/controlador_modificar_configuracion.php', { evaluacion_nota_minima: '25' });
    check(invalida.estado === 422, 'una nota mínima fuera de la escala se rechaza (422)', `(${invalida.estado})`);
    const tipo = await pedir(adm.page, 'controller/institucion/controlador_modificar_configuracion.php', { institucion_tipo: 'INSTITUTO' });
    check(tipo.texto === '1' && (await adm.page.evaluate(async () => (await (await fetch('../controller/institucion/controlador_obtener_configuracion.php')).json()).opciones['institucion.tipo'].valor)) === 'COLEGIO',
      'el administrador no puede cambiar el tipo de institución (lo cambia soporte)');
    const docente = await entrar(browser, ESC.docente);
    check((await pedir(docente.page, 'controller/institucion/controlador_modificar_configuracion.php', { periodo_tipo: 'BIMESTRE' })).estado === 403,
      'solo el administrador configura (403)');
    await docente.ctx.close();
    // Se deja como estaba (vacío = valor por defecto del tipo).
    const restaurar = await pedir(adm.page, 'controller/institucion/controlador_modificar_configuracion.php', { periodo_tipo: '', apoderado_obligatorio: '' });
    check(restaurar.texto === '1', 'volver a los valores por defecto');
    await adm.ctx.close();
  }

  console.log('20. Plan de estudios y matrícula por unidades (instituto)');
  {
    const adm = await entrar(browser, ESC.admin);
    const cfg = 'controller/institucion/controlador_modificar_configuracion.php';
    check(!(await adm.page.isVisible('#menu_instituto')), 'en un colegio no aparece el menú del plan de estudios');
    // Reglas de instituto: mínima 13, recuperación desde 10, promedio por créditos.
    await pedir(adm.page, cfg, { matricula_modo: 'POR_UNIDAD', evaluacion_nota_minima: '13', evaluacion_recuperacion_desde: '10', evaluacion_ponderacion: 'POR_CREDITOS' });
    await adm.page.reload();
    await adm.page.waitForLoadState('networkidle').catch(() => {});
    check(await adm.page.isVisible('#menu_instituto'), 'con matrícula por unidades aparece «Plan de estudios»');

    const plan = 'controller/plan_estudios/controlador_plan.php';
    const sufijo = String(Date.now()).slice(-6);
    const programa = (await pedir(adm.page, plan, { accion: 'guardar_programa', id: 0, codigo: `P${sufijo}`, nombre: 'Computación E2E', estado: 'ACTIVO' })).json?.id;
    const modulo = (await pedir(adm.page, plan, { accion: 'guardar_modulo', id: 0, programa, nombre: 'Soporte técnico', orden: 1 })).json?.id;
    const unidad = async (codigo, periodo) => (await pedir(adm.page, plan, {
      accion: 'guardar_unidad', id: 0, modulo, codigo, nombre: `Unidad ${codigo}`, periodo_academico: periodo, creditos: '3', horas_teoricas: 32, horas_practicas: 32, estado: 'ACTIVO',
    })).json?.id;
    const u1 = await unidad(`A${sufijo}`, 1);
    const u2 = await unidad(`B${sufijo}`, 2);
    check(programa > 0 && modulo > 0 && u1 > 0 && u2 > 0, 'se crean el programa, el módulo y dos unidades');
    check((await pedir(adm.page, plan, { accion: 'agregar_requisito', unidad: u2, requisito: u1 })).json?.ok === true, 'B pide A aprobada');
    const ciclo = await pedir(adm.page, plan, { accion: 'agregar_requisito', unidad: u1, requisito: u2 });
    check(ciclo.estado === 422 && /ciclo/.test(ciclo.json?.error ?? ''), 'A pidiendo B formaría un ciclo: se rechaza', `(${ciclo.estado})`);
    const invalida = await pedir(adm.page, plan, { accion: 'guardar_unidad', id: 0, modulo, codigo: `C${sufijo}`, nombre: 'Sin horas', periodo_academico: 1, creditos: '0', horas_teoricas: 0, horas_practicas: 0 });
    check(invalida.estado === 422 && /créditos/.test(invalida.json?.error ?? ''), 'una unidad sin créditos ni horas se rechaza con el motivo');

    await adm.page.click('#menu_instituto > a');
    await adm.page.click('#menu_plan_estudios');
    await adm.page.waitForSelector(`#plan_contenido tr[data-unidad="${u2}"]`, { timeout: 15000 });
    check((await adm.page.textContent(`#plan_contenido tr[data-unidad="${u2}"]`)).includes(`A${sufijo}`), 'la pantalla del plan muestra la unidad con su prerrequisito');

    await adm.page.click('#menu_matricula_unidades');
    await adm.page.waitForSelector('#mu_programa option', { state: 'attached', timeout: 15000 });
    const alumno = (await pedir(adm.page, 'controller/matricula_unidades/controlador_matricula_unidades.php', null, 'GET')).json?.alumnos?.[0]?.id;
    await adm.page.selectOption('#mu_alumno', String(alumno));
    await adm.page.selectOption('#mu_programa', String(programa));
    await adm.page.dispatchEvent('#mu_periodo', 'change');
    await adm.page.waitForSelector(`#tabla_matricula_unidades tr[data-unidad="${u2}"]`, { timeout: 15000 });
    check(await adm.page.getAttribute(`#tabla_matricula_unidades tr[data-unidad="${u2}"]`, 'data-situacion') === 'FALTA_REQUISITO',
      'B aparece bloqueada: le falta A');
    await adm.page.click(`#tabla_matricula_unidades tr[data-unidad="${u1}"] .matricular`);
    await adm.page.waitForSelector('.swal2-popup', { timeout: 10000 });
    check((await adm.page.textContent('.swal2-popup')).includes('Matriculado'), 'se matricula en A desde la pantalla');
    await adm.page.click('.swal2-confirm');
    const directo = await pedir(adm.page, 'controller/matricula_unidades/controlador_matricula_unidades.php',
      { accion: 'matricular', alumno, unidad: u2, periodo: await adm.page.inputValue('#mu_periodo') });
    check(directo.json?.codigo === 4, 'tampoco por la API: el procedimiento exige A aprobada (4)', JSON.stringify(directo.json));
    await adm.page.waitForSelector(`#tabla_matricula_unidades tr[data-unidad="${u1}"][data-situacion="MATRICULADO"] .retirar`, { timeout: 10000 });
    await adm.page.click(`#tabla_matricula_unidades tr[data-unidad="${u1}"] .retirar`);
    await adm.page.waitForSelector('.swal2-popup', { timeout: 10000 });
    check((await adm.page.textContent('.swal2-popup')).includes('Retirado'), 'y se retira de A');
    await adm.page.click('.swal2-confirm');

    // Fase 5.5 y 5.6: nota, recuperación, cargo y promedio ponderado.
    const fila = (u) => `#tabla_matricula_unidades tr[data-unidad="${u}"]`;
    const conNota = async (boton, u, nota) => {
      await adm.page.waitForSelector(`${fila(u)} ${boton}`, { timeout: 10000 });
      await adm.page.click(`${fila(u)} ${boton}`);
      await adm.page.waitForSelector('.swal2-input', { timeout: 10000 });
      await adm.page.fill('.swal2-input', nota);
      await adm.page.click('.swal2-confirm');
      await adm.page.waitForSelector('.swal2-popup:not(:has(.swal2-input:visible))', { timeout: 10000 });
      const texto = await adm.page.textContent('.swal2-popup');
      await adm.page.click('.swal2-confirm');
      return texto;
    };
    await adm.page.click(`${fila(u1)} .matricular`);
    await adm.page.waitForSelector('.swal2-popup', { timeout: 10000 });
    check((await adm.page.textContent('.swal2-popup')).includes('Matriculado'), 'quien se retiró puede volver a la unidad en el mismo periodo');
    await adm.page.click('.swal2-confirm');
    check((await conNota('.calificar', u1, '12')).includes('Nota registrada'), 'A se califica con 12');
    await adm.page.waitForSelector(`${fila(u1)}[data-situacion="DESAPROBADO"]`, { timeout: 10000 });
    check(true, '12 < 13: A queda desaprobada');
    check((await conNota('.recuperar', u1, '14')).includes('Aprobada en la evaluación de recuperación'), 'con 12 tiene recuperación: 14 la aprueba');
    await adm.page.waitForSelector(`${fila(u2)}[data-situacion="DISPONIBLE"] .matricular`, { timeout: 10000 });
    check(true, 'aprobada A, B deja de estar bloqueada');
    await adm.page.click(`${fila(u2)} .matricular`);
    await adm.page.waitForSelector('.swal2-popup', { timeout: 10000 });
    await adm.page.click('.swal2-confirm');
    await conNota('.calificar', u2, '9');
    await adm.page.waitForSelector(`${fila(u2)}[data-situacion="DESAPROBADO"]`, { timeout: 10000 });
    check(!(await adm.page.isVisible(`${fila(u2)} .recuperar`)), 'con 9 no hay recuperación (fuera del rango 10–12)');
    await adm.page.waitForFunction(() => /11\.5/.test(document.querySelector('#mu_record')?.textContent || ''), null, { timeout: 10000 }).catch(() => {});
    const record = await adm.page.textContent('#mu_record');
    check(/ponderado/i.test(record) && record.includes('11.5') && (await adm.page.textContent('#mu_cargos')).includes(`B${sufijo}`),
      'el récord muestra el promedio ponderado (14 y 9, 3 créditos cada una → 11.5) y B como cargo', record);

    // Fase 5.8: récord académico en PDF; certificados solo con lo aprobado.
    const pdf = (url) => adm.page.evaluate(async (u) => {
      const r = await fetch(u);
      const texto = await r.text();
      return { estado: r.status, inicio: texto.slice(0, 4) };
    }, url);
    const recordPdf = await pdf(await adm.page.getAttribute('#mu_record_pdf', 'href'));
    check(recordPdf.estado === 200 && recordPdf.inicio === '%PDF', 'el récord académico sale en PDF', JSON.stringify(recordPdf));
    const base = `../view/MPDF/REPORTE/certificado_instituto.php?alumno=${alumno}&programa=${programa}`;
    check((await pdf(`${base}&modulo=${modulo}`)).estado === 422, 'con B pendiente no hay certificado del módulo (422)');
    const actual = await adm.page.inputValue('#mu_periodo');
    const otroPeriodo = (await pedir(adm.page, 'controller/matricula_unidades/controlador_matricula_unidades.php', null, 'GET')).json.periodos
      .map((x) => String(x.id)).find((id) => id !== actual);
    await pedir(adm.page, 'controller/matricula_unidades/controlador_matricula_unidades.php', { accion: 'matricular', alumno, unidad: u2, periodo: otroPeriodo });
    const enCurso = (await pedir(adm.page, `controller/matricula_unidades/controlador_matricula_unidades.php?alumno=${alumno}&programa=${programa}&periodo=${otroPeriodo}`, null, 'GET'))
      .json.find((x) => String(x.id_unidad) === String(u2));
    await pedir(adm.page, 'controller/matricula_unidades/controlador_matricula_unidades.php', { accion: 'calificar', id: enCurso.id_matricula_unidad, nota: '15' });
    const modular = await pdf(`${base}&modulo=${modulo}`);
    const egreso = await pdf(base);
    check(modular.estado === 200 && modular.inicio === '%PDF' && egreso.estado === 200 && egreso.inicio === '%PDF',
      'B llevada de nuevo y aprobada: certificado modular y constancia de egreso', JSON.stringify([modular, egreso]));
    const docente = await entrar(browser, ESC.docente);
    check((await pedir(docente.page, plan, null, 'GET')).estado === 403, 'el plan de estudios es del administrador (403)');
    await docente.ctx.close();
    await pedir(adm.page, cfg, { matricula_modo: '', evaluacion_nota_minima: '', evaluacion_recuperacion_desde: '', evaluacion_ponderacion: '' });
    await adm.ctx.close();
  }
} finally {
  await browser.close();
}
console.log(`\n${ok} comprobaciones correctas, ${fallos} fallos`);
process.exitCode = fallos ? 1 : 0;
