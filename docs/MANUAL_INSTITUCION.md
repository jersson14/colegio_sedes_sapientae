# Manual para el administrador de la institución

> Para la dirección o la persona que administra el sistema en el colegio. No hace falta saber de
> informática: aquí está lo que conviene saber además del uso diario de cada pantalla.

## Entrar por primera vez

1. Abre la dirección que te enviamos (por ejemplo `https://micolegio.plataforma.pe`).
2. Entra con el usuario `admin` y la contraseña inicial que recibiste.
3. **Cambia la contraseña** desde tu perfil. La inicial solo se envió una vez y nadie más la conoce.

## Que el sistema lleve el nombre y los colores del colegio

En el panel, tabla **«Datos de la institución»**:

- **Editar**: nombre, correo, código modular, teléfono y dirección. Es lo que aparece en los reportes y en la
  página pública.
- **Cambiar foto**: el logo del colegio. Sale en el acceso, en el panel y en los PDF.
- **Personalizar**: el color principal y los textos de la **página pública**: lema, bienvenida, «Nosotros»,
  niveles, valores, cifras y horario. Escribe solo lo que sea cierto: una sección vacía no se muestra.

## Configuración académica

En «Datos de la institución» → **Configuración académica**: el periodo de evaluación (bimestre, trimestre,
cuatrimestre o semestre), la nota mínima aprobatoria, si el promedio se pondera por créditos, si los datos del
apoderado son obligatorios y si la matrícula es por aula o por unidad didáctica. Vienen ya puestos según el tipo
de institución (colegio o instituto); cámbialos solo si tu reglamento dice otra cosa.

## Plan de estudios y matrícula por unidades (institutos)

Con la matrícula «por unidad didáctica» aparece el menú **Plan de estudios**:

- **Programas y unidades**: cada programa de estudios tiene módulos formativos, y cada módulo sus unidades
  didácticas, con código, créditos, horas teóricas y prácticas y el periodo académico (I, II…). En
  **Prerrequisitos** indicas qué unidades hay que aprobar antes. El sistema no deja crear un ciclo, por ejemplo
  que A pida B y B pida A.
- **Matrícula por unidades**: eliges al alumno, el programa y el periodo, y ves cada unidad como aprobada,
  matriculada, disponible o bloqueada (y por qué prerrequisito). Solo se puede matricular en lo disponible, o
  en lo que desaprobó para volver a llevarlo.

En la misma pantalla se registran las notas:

- **Calificar** registra la nota final de una unidad en curso. Con la nota mínima o más, queda aprobada; si no,
  desaprobada.
- **Recuperación** aparece si la nota desaprobada está dentro del rango de recuperación (por ejemplo de 10 a 12
  con mínima 13). Se rinde una sola vez.
- Una unidad desaprobada queda como **cargo pendiente**: en un periodo siguiente se matricula solo esa unidad, sin
  repetir el resto.
- Arriba se ven el **promedio** (ponderado por créditos en institutos), los créditos aprobados y los cargos.

Debajo están los documentos en PDF:

- **Récord académico**: todas las unidades del programa con su nota (con «(R)» si la aprobó en recuperación),
  la situación y el periodo en que la llevó. Sale siempre.
- **Certificado modular**: aparece por cada módulo con todas sus unidades aprobadas.
- **Constancia de egreso**: aparece cuando aprobó todas las unidades del programa.

El título técnico oficial no lo genera el sistema: se tramita en el formato y el registro del MINEDU.

En **Docentes por unidad** eliges el programa y el periodo y asignas el docente de cada unidad. Ese docente ve
la unidad en **Mis unidades didácticas** y registra la nota final (y la de recuperación) solo de quienes están
matriculados en ella en ese periodo.

Lo que ya tiene alumnos o historial no se borra: se desactiva.

## Usuarios y permisos

Cada persona entra con su propia cuenta. Los roles disponibles son administrador, docente, auxiliar,
enfermera, psicóloga y estudiante, y cada uno ve solo su parte. En un instituto existe además la
**secretaría académica** (se crea en Personal administrativo con el rol SECRETARIA): maneja el plan de
estudios, la matrícula por unidades, las notas, el récord y los certificados, sin acceso a pagos, usuarios ni
configuración. Desactiva las cuentas de quien deja el
colegio: así su acceso se corta en el momento.

## Facturación electrónica (si tu plan la incluye)

Emite la boleta o factura de cada ingreso (pensión, matrícula o ingreso diverso) con **tu propia cuenta de
Nubefact**.

1. En Nubefact, «API (Integración)», copia la **ruta** y el **token**, y pégalos en **Facturación electrónica →
   Cuenta de Nubefact**. Indica tus series (por ejemplo B001 y F001) y, si ya emitías con ellas, el último número
   usado: el sistema continúa desde ahí. Elige cómo se trata el IGV (los servicios educativos suelen estar
   exonerados; confírmalo con tu contador).
2. En **Ingresos sin comprobante**, pulsa **Boleta** o **Factura**. El cliente viene sugerido (el apoderado o el
   alumno); la factura exige RUC y dirección. Una boleta de S/ 700 o más exige el documento del cliente.
3. En **Comprobantes emitidos** ves el estado y el PDF. Las boletas quedan «Emitido» hasta que la SUNAT las acepta
   en el resumen diario: pulsa **Consultar** para actualizarlas. Si Nubefact rechaza uno, corrige los datos y
   reenvíalo (conserva su número). Para anular, indica el motivo.

El token se guarda cifrado y nunca se vuelve a mostrar.

## Auditoría

En **Configuración → Auditoría** ves quién guardó, modificó o eliminó algo, cuándo, desde qué IP y con qué
resultado (también los intentos rechazados), además de los inicios y cierres de sesión y las exportaciones de
datos. Filtra por fechas, usuario o módulo. Las contraseñas nunca quedan registradas. Los registros se conservan
dos años.

## Tus datos

- **Exportar datos** (en «Datos de la institución»): descarga todo en un archivo `.zip`. Incluye una planilla
  por tabla, que se abre con Excel, y las fotos y documentos. No incluye contraseñas. Guárdalo en un lugar
  seguro: contiene datos personales de menores.
- Hacemos copias de seguridad todos los días.
- Si algún día dejas el servicio, te entregamos tus datos completos y, pasado el plazo acordado, los borramos.
  Recibirás una constancia del borrado.

## Plan y avisos

| Aviso en el panel | Qué significa |
|---|---|
| «Versión de prueba hasta…» | Periodo de prueba: todo funciona y los PDF llevan una marca de agua |
| «Próximo pago» / «Pago vencido» | Hay un cobro por vencer o vencido |
| «Hay un pago pendiente» | Puedes consultar e imprimir todo (boletas, notas), pero no registrar altas ni matrículas hasta regularizarlo |
| «Se alcanzó el límite… del plan» | Tu plan admite ese número de alumnos, usuarios o espacio de archivos: escríbenos para ampliarlo |
| «El servicio está suspendido» | Solo el administrador puede entrar, y solo para descargar los datos durante el plazo indicado |

En ningún caso se borran tus datos por un pago atrasado.

## Soporte

Escríbenos indicando el nombre del colegio, qué intentabas hacer y, si puedes, una captura de pantalla.
**Nunca te pediremos tu contraseña.**
