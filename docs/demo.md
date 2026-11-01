# Base de demostración 2026

Un colegio completo con datos simulados del año escolar 2026, del 9 de marzo hasta el 1 de noviembre. Sirve para mostrar el sistema o para subirlo a un servidor de prueba. **Ningún nombre, documento ni teléfono es real.**

- Archivo para subir: `respaldos/colegio2_demo_2026.sql`. No crea la base: se importa dentro de una base vacía con cualquier nombre. Ya viene sin `DEFINER`, así que se importa con el usuario del hosting.
- Para volver a crearla en esta PC: `php scripts/demo/generar_demo.php`. Arma `colegio2_demo` desde cero y nunca toca `colegio2`. Siempre genera los mismos datos.

## Qué contiene

| | |
|---|---|
| Grados | 14: Inicial 3, 4 y 5 años; Primaria 1.° a 6.°; Secundaria 1.° a 5.° año (sección única) |
| Estudiantes | 243, entre 15 y 20 por grado, cada uno con papá, mamá y usuario |
| Docentes | 22: tutores de inicial y primaria y especialistas. Cada uno tiene sus cursos, su horario sin cruces y sus criterios de evaluación |
| Pensiones | Marzo a diciembre: Inicial S/ 280, Primaria S/ 320 y Secundaria S/ 360, con mora de S/ 20. Vencen el último día de cada mes |
| Pagos | 2080 recibos: la matrícula y las pensiones pagadas a tiempo, con atraso (con mora, a veces exonerada) o pendientes. 71 familias deben algo y 2 recibos están anulados |
| Asistencia | Todos los días de clase, sin feriados ni las vacaciones de medio año. También la asistencia de los docentes |
| Notas | Bimestres I, II y III cerrados; el IV en curso, con algunos cursos ya calificados. Inicial y primaria con letras (AD, A, B, C); secundaria de 0 a 20 |
| Aula virtual | 1443 tareas con entregas, calificaciones y comentarios, más 432 exámenes y 300 materiales de apoyo (enlaces) |
| Otros | 8 comunicados con sus lecturas, 105 atenciones de enfermería y psicología, 6 solicitudes de información, gastos de cada mes (sueldos, luz, agua, internet, materiales), venta de uniformes y depósitos de la caja y del Yape al banco |

Las tareas y el material traen solo enlaces y descripción, sin archivos adjuntos.

## Usuarios

Todos usan la contraseña **`Colegio2026`**. El administrador JERSSON123 conserva su contraseña de siempre.

| Rol | Usuario |
|---|---|
| Administrador (dirección) | `DIRECCION` |
| Contador | `CONTADOR` |
| Auxiliar | `AUXILIAR` |
| Enfermera | `ENFERMERA` |
| Psicóloga | `PSICOLOGA` |

**Docentes:**

| Usuario | Cursos |
|---|---|
| `AMAMANI` | Tutor de 3 años |
| `DPEREZ` | Tutor de 4 años |
| `PRAMIREZ` | Tutora de 5 años |
| `EPEREZ`, `SROJAS`, `FLOAYZA`, `EVILLANUEVA`, `LVILLANUEVA`, `GALARCON` | Tutoras de 1.° a 6.° de primaria |
| `JRIOS` | Psicomotricidad y Educación Física (inicial y primaria) |
| `KHERRERA` | Inglés (inicial y primaria) |
| `ERIOS` | Computación (primaria) y Educación para el Trabajo (secundaria) |
| `MHUAMAN` / `GPEREZ` | Matemática (1.° a 3.° / 4.° y 5.° de secundaria) |
| `MMEDINA` / `NLOPEZ` | Comunicación (1.° a 3.° / 4.° y 5.° de secundaria) |
| `NTAPIA` | Inglés (secundaria) |
| `BMENDOZA` | Ciencia y Tecnología (secundaria) |
| `LRIOS` | Ciencias Sociales y DPCC |
| `LPALOMINO` | Arte y Cultura y DPCC |
| `AVALDIVIA` | Educación Física (secundaria) |
| `RPONCE` | Educación Religiosa (secundaria) |

**Estudiantes:** el usuario es su DNI. Algunos ejemplos:

| Usuario | Estudiante | Situación |
|---|---|---|
| `72649915` | Thiago Benites, 5 años | Al día |
| `75463067` | Andrea Antonella Rodríguez, 1.° de primaria | Al día |
| `75448446` | Luciana Herrera, 1.° de primaria | Debe 6 meses |
| `70619008` | Martín Herrera, 2.° de primaria | Debe 1 mes |
| `77368344` | Micaela Quispe, 1.° de secundaria | Al día |
| `78510898` | Sebastián Torres, 1.° de secundaria | Debe 1 mes |

## Subirla al servidor

1. Crear una base vacía con cotejamiento `utf8mb4_unicode_ci` e importar `colegio2_demo_2026.sql`, desde phpMyAdmin o con `mysql nombre_base < colegio2_demo_2026.sql`.
2. En `config/config.local.php` del servidor, poner el nombre de esa base y su usuario y contraseña.
3. Entrar con `DIRECCION` / `Colegio2026`. Si es un servidor real, cambiar después las contraseñas en **Usuarios**.
