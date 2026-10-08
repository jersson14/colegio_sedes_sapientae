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
    check((await registrar({ matri: '1500' })).texto === '0' && !(await de(anio)),
      'un monto mayor a 999.99 se rechaza → 0 (antes se recortaba a 999.99)');
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
} finally {
  await browser.close();
}
console.log(`\n${ok} comprobaciones correctas, ${fallos} fallos`);
process.exitCode = fallos ? 1 : 0;
