# src/ — código nuevo (namespace `App\`, PSR-4)

Destino del refactor por estrangulamiento (Fase 3 del plan). Todo código nuevo vive aquí,
con tipos estrictos, PHPStan y pruebas. Lo heredado (`controller/`, `model/`, `view/`,
`core/`) se migra módulo por módulo; no se copia código heredado sin su prueba de caracterización.

Estructura objetivo: `Core/`, `Http/Controllers/`, `Http/Middleware/`, `Domain/`,
`Services/`, `Repositories/`, `Support/` (ver docs/PLAN_DE_TRABAJO.md §1.2).
