"""Auditoría de autorización: qué endpoints usa realmente cada rol vs. exigir_rol(...).

Uso (desde la raíz del proyecto):   python tools/analizar_roles.py . salida.json
Imprime por rol los PERMISOS DE MÁS (el código permite y la UI del rol no lo usa:
candidatos a quitar) y los PERMISOS DE MENOS (la UI lo usa y el código lo deniega:
pantallas que fallarán con 403).

Ejecútalo tras tocar menús, vistas o JS. Es un análisis estático: confirma en el
navegador antes de quitar un permiso. Ver docs/MATRIZ_ROLES.md.
Diferencias esperadas hoy: usuario/controlador_total_* (tarjetas solo para el
administrador) y descargar_tarea para ENFERMERA/PSICOLOGA (enlace no visible).
"""
import re, glob, os, sys, json, collections
os.chdir(sys.argv[1])
sys.stdout.reconfigure(encoding='utf-8')
SEP = chr(92)
ROLES = ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE', 'AUXILIAR', 'ENFERMERA', 'PSICOLOGA']
RX_EP = re.compile(r"((?:controller|MPDF/REPORTE)/[^'\"\s?#]+?\.php)")
RX_VIEW = re.compile(r"cargar_contenido\(\s*'[^']*'\s*,\s*'([^']+)'")
RX_SCRIPT = re.compile(r"src=\"(?:\.\./)?js/([^\"?]+\.js)")
RX_FUNC = re.compile(r"^[ \t]*(?:async\s+)?function\s+([A-Za-z_$][\w$]*)\s*\(", re.M)
RX_HANDLER = re.compile(r"^[ \t]*\$\(\s*['\"]#([\w-]+)[^)]*\)\s*\.\s*(?:on|click|change|submit|keyup)\b", re.M)

def leer(p): return open(p, encoding='utf-8', errors='replace').read()

def fin_llaves(s, i):
    prof, n = 0, len(s)
    while i < n:
        ch = s[i]
        if ch in '\'"`':
            q = ch; i += 1
            while i < n and s[i] != q: i += 2 if s[i] == SEP else 1
        elif s.startswith('//', i):
            i = s.find('\n', i); i = n if i < 0 else i
        elif s.startswith('/*', i):
            i = s.find('*/', i); i = n if i < 0 else i + 1
        elif ch == '{': prof += 1
        elif ch == '}':
            prof -= 1
            if prof == 0: return i + 1
        i += 1
    return n

FUNCS = {}      # archivo -> {nombre: cuerpo}
LIBRE = {}      # archivo -> código de nivel archivo (sin funciones ni manejadores)
HANDLERS = {}   # archivo -> [(id, cuerpo)]
for p in glob.glob('js/*.js'):
    s = leer(p); f = os.path.basename(p)
    defs, resto, pos = {}, [], 0
    for m in RX_FUNC.finditer(s):
        if m.start() < pos: continue
        end = fin_llaves(s, m.end())
        defs[m.group(1)] = s[m.start():end]
        resto.append(s[pos:m.start()]); pos = end
    resto.append(s[pos:])
    txt = ''.join(resto); libre, hs, pos = [], [], 0
    for m in RX_HANDLER.finditer(txt):
        if m.start() < pos: continue
        end = fin_llaves(txt, m.end())
        libre.append(txt[pos:m.start()]); pos = end
        hs.append((m.group(1), txt[m.start():end]))
    libre.append(txt[pos:])
    FUNCS[f], LIBRE[f], HANDLERS[f] = defs, ''.join(libre), hs
TODAS = set().union(*[set(d) for d in FUNCS.values()])
RX_CALL = re.compile(r"\b(" + "|".join(sorted(TODAS, key=len, reverse=True)) + r")\s*\(")

# Layout por rol
idx = leer('view/index.php')
RX_BLOQUE = re.compile(r"<\?php\s+if\s*\(([^{]*S_ROL[^{]*)\)\s*\{\s*\?>|<\?php\s*\}\s*\?>")
pila, por_rol, pos = [], collections.defaultdict(list), 0
for m in RX_BLOQUE.finditer(idx):
    for r in (pila[-1] if pila else ROLES): por_rol[r].append(idx[pos:m.start()])
    pos = m.end()
    if m.group(1): pila.append([r for r in ROLES if '"' + r + '"' in m.group(1)])
    elif pila: pila.pop()
for r in ROLES: por_rol[r].append(idx[pos:])
GLOBALES = list(dict.fromkeys(RX_SCRIPT.findall(idx)))

def resolver(nombre, archivo_actual, ambito):
    if archivo_actual and nombre in FUNCS.get(archivo_actual, {}):
        return archivo_actual
    for f in reversed(ambito):
        if nombre in FUNCS.get(f, {}): return f
    return None

def analizar_rol(rol):
    eps, rastro = set(), {}
    docs_vistos, cola_docs = set(), [('LAYOUT', '\n'.join(por_rol[rol]))]
    while cola_docs:
        nombre_doc, texto = cola_docs.pop()
        if nombre_doc in docs_vistos: continue
        docs_vistos.add(nombre_doc)
        propios = list(dict.fromkeys(RX_SCRIPT.findall(texto)))
        ambito = GLOBALES + [f for f in propios if f not in GLOBALES] if nombre_doc != 'LAYOUT' else GLOBALES
        # código ejecutado al cargar el documento
        semillas = [(None, texto, nombre_doc)]
        ejecutados = propios if nombre_doc != 'LAYOUT' else GLOBALES
        for f in ejecutados:
            semillas.append((f, LIBRE.get(f, ''), f'{nombre_doc}>{f}'))
            for el, cod in HANDLERS.get(f, []):
                if re.search(r"id\s*=\s*\\?['\"]" + re.escape(el) + r"\\?['\"]", texto):
                    semillas.append((f, cod, f'{nombre_doc}>{f}>#{el}'))
        vistos_fn = set()
        pend = list(semillas)
        while pend:
            arch, cuerpo, origen = pend.pop()
            for ep in RX_EP.findall(cuerpo):
                if ep not in eps: eps.add(ep); rastro[ep] = origen
            for v in RX_VIEW.findall(cuerpo):
                pv = 'view/' + v
                if os.path.exists(pv): cola_docs.append((v, leer(pv)))
            for llamada in set(RX_CALL.findall(cuerpo)):
                f = resolver(llamada, arch, ambito)
                if f and (f, llamada) not in vistos_fn:
                    vistos_fn.add((f, llamada))
                    pend.append((f, FUNCS[f][llamada], f'{origen}>{llamada}()'))
    return eps, rastro, docs_vistos

AB = dict(zip(ROLES, 'ADEXNP'))
resultado, rastros = {}, {}
for r in ROLES:
    resultado[r], rastros[r], docs = analizar_rol(r)
# Endpoints que no se llaman desde el JS sino por URL de imagen: las fotos subidas (Fase 4.6) se
# sirven por controlador_ver_archivo.php, y la foto de perfil del encabezado aparece en todos los roles.
for r in ROLES:
    resultado[r].add('controller/archivo/controlador_ver_archivo.php')
    rastros[r].setdefault('controller/archivo/controlador_ver_archivo.php', 'URL de imagen (foto de perfil del encabezado)')

# Comparación con el código
codigo = {}
for p in sorted(glob.glob('controller/**/*.php', recursive=True) + glob.glob('view/MPDF/REPORTE/*.php')):
    p = p.replace(SEP, '/')
    if '/tareas/controller/' in p: continue
    s = leer(p)
    if 'core/guard.php' not in s: continue
    m = re.search(r"exigir_rol\(([^)]*)\)", s)
    codigo[p] = set(re.findall(r"'(\w+)'", m.group(1))) if m else set(ROLES)
def clave(p): return p.replace('controller/', 'controller/', 1).replace('view/MPDF/', 'MPDF/', 1)

de_mas, de_menos = collections.defaultdict(list), collections.defaultdict(list)
for p, permitidos in codigo.items():
    k = clave(p)
    for r in ROLES:
        usa = k in resultado[r]
        if r in permitidos and not usa and r != 'ADMINISTRADOR': de_mas[r].append(k)
        if usa and r not in permitidos: de_menos[r].append((k, rastros[r][k]))
json.dump({r: sorted(resultado[r]) for r in ROLES}, open(sys.argv[2], 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
for r in ROLES:
    print(f'\n##### {r}: usa {len(resultado[r])} endpoints')
    print(f'  PERMISOS DE MÁS ({len(de_mas[r])}):')
    for k in sorted(de_mas[r]): print('    -', k)
    print(f'  PERMISOS DE MENOS ({len(de_menos[r])}):')
    for k, o in sorted(de_menos[r]): print('    !', k, '   <=', o[:160])

# --estricto (CI): falla si aparece un permiso de más, o uno de menos que no esté
# en la lista de diferencias aceptadas (documentadas en docs/MATRIZ_ROLES.md).
ESPERADOS_DE_MENOS = {
    # Las tarjetas de totales solo existen en el panel del administrador.
    *[(r, f'controller/usuario/controlador_total_{t}.php')
      for r in ROLES if r != 'ADMINISTRADOR'
      for t in ('administrativos', 'docentes', 'egresos', 'enfermeria', 'estudiantes',
                'ingresos', 'psicologia', 'usuarios')],
    # listar_tareas_menu() corre en el panel común, pero su tabla solo existe para el docente.
    ('ENFERMERA', 'controller/tareas/controlador_descargar_tarea.php'),
    ('PSICOLOGA', 'controller/tareas/controlador_descargar_tarea.php'),
}
if '--estricto' in sys.argv:
    problemas = [f'DE MÁS   {r}: {k}' for r in ROLES for k in de_mas[r]]
    problemas += [f'DE MENOS {r}: {k}' for r in ROLES for k, _ in de_menos[r] if (r, k) not in ESPERADOS_DE_MENOS]
    print('\n' + ('\n'.join(problemas) if problemas else 'Matriz de roles alineada con la interfaz.'))
    sys.exit(1 if problemas else 0)
