
# COLEGIO SEDES SAPIENTAE - SOTFWARE ACADÉMICO

Este sistema se creo con fines de investigación y posteriores mejoras.

### TECNOLOGIAS UTILIZADAS
- JS
- HTML Y CSS
- PHP
- PLANTILLA ADMIN LTE V 3.0.1
- BOOSTRAP
- MPDF PARA REPORTE 

### BASE DE DATOS
- MySQL

### AQUITECTURA O PATRON

Se utilizo el patron MVC (Modelo, Vista y Controlador)


### DOCUMENTACIÓN (mejoras 2026)
- `PLAN_MEJORAS.md`: plan por fases (0 a 9) con todo lo realizado, decisiones y pendientes.
- `docs/manual_contadora.md`: manual corto del trabajo diario y mensual de la contadora.
- `docs/despliegue.md`: instalación, migraciones, lista para producción, respaldos, pruebas y permisos.
- `database/migraciones/`: cambios de la base en orden (001 a 011). `database/generadores/`: scripts que generaron las migraciones que modifican procedimientos existentes.
- `tests/`: pruebas automáticas sobre la base `colegio2_test` (`bash tests/ejecutar_todo.sh`).
- `scripts/`: usuario restringido de la base y respaldos diarios.
