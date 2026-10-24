#!/usr/bin/env bash
# Reconstruye colegio2_test desde el respaldo inicial + todas las migraciones y deja claves de prueba (Prueba123).
# Nunca toca la base real colegio2.
set -e
R=$(cd "$(dirname "$0")/.." && pwd)
M="/c/xampp/mysql/bin/mysql.exe --default-character-set=utf8mb4 -uroot -P3307 -h127.0.0.1"
$M -e "DROP DATABASE IF EXISTS colegio2_test; CREATE DATABASE colegio2_test CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
$M colegio2_test < "$R/respaldos/colegio2_20261023_1545.sql"
for f in "$R"/database/migraciones/*.sql; do $M colegio2_test < "$f"; echo "  migración $(basename "$f")"; done
H=$(/c/xampp/php/php.exe -r 'echo password_hash("Prueba123",PASSWORD_DEFAULT,["cost"=>12]);')
$M colegio2_test -e "update usuario set usu_contra='$H' where usu_id in (9,22,10,34,47,50,55,56); delete from login_intentos;"
echo "colegio2_test listo"
