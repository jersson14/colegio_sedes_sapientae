#!/usr/bin/env bash
# Corre todas las suites HTTP, cada una sobre colegio2_test recién reiniciada.
# Uso: bash tests/ejecutar_todo.sh   (necesita config/config.local.php con ['db' => ['nombre' => 'colegio2_test'], 'modo_demo' => false])
R=$(cd "$(dirname "$0")/.." && pwd)
grep -q colegio2_test "$R/config/config.local.php" 2>/dev/null || { echo "Falta config/config.local.php apuntando a colegio2_test"; exit 1; }
total=0
for s in "$R"/tests/pruebas_fase*.sh; do
  bash "$R/tests/reset_test_db.sh" > /dev/null 2>&1 || { echo "No se pudo reiniciar colegio2_test"; exit 1; }
  r=$(bash "$s" 2>&1)
  echo "$r" | grep -E "FALLA" | sed "s/^/$(basename "$s"): /"
  echo "$(basename "$s"): $(echo "$r" | tail -1)"
  echo "$r" | tail -1 | grep -q ", 0 fallas" || total=$((total+1))
done
bash "$R/tests/reset_test_db.sh" > /dev/null 2>&1
echo "Suites con fallas: $total"
[ "$total" -eq 0 ]
