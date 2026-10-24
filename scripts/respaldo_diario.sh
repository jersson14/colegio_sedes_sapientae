#!/usr/bin/env bash
# Respaldo diario de la base del colegio (servidor Linux / hosting con cron). Conserva los últimos 30 días.
# Programar con "crontab -e":   0 23 * * * /ruta/al/sistema/scripts/respaldo_diario.sh
# Las credenciales van en ~/.my.cnf (sección [mysqldump] user=... password=...), no en este archivo.
# La carpeta de respaldos debe estar FUERA de la carpeta pública o protegida (respaldos/.htaccess).
set -euo pipefail
BASE=${BASE:-colegio2}
CARPETA=${CARPETA:-"$(cd "$(dirname "$0")/.." && pwd)/respaldos"}
FECHA=$(date +%Y%m%d_%H%M)
mkdir -p "$CARPETA"
mysqldump --default-character-set=utf8mb4 --routines --triggers --single-transaction "$BASE" | gzip > "$CARPETA/${BASE}_${FECHA}.sql.gz"
echo "$(date '+%F %T') respaldo ${BASE}_${FECHA}.sql.gz" >> "$CARPETA/respaldos.log"
find "$CARPETA" -name "${BASE}_????????_????.sql.gz" -mtime +30 -delete
