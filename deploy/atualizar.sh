#!/bin/sh
# Atualiza o PDVLoja em produção: puxa o codigo, rebuilda o painel (Vue) e
# reinicia o container do PHP.
#
# O reinicio do "app" e necessario mesmo quando so o backend mudou: o
# opcache.ini roda com validate_timestamps=0 (perf), entao o PHP-FPM fica
# servindo o bytecode antigo em cache ate o container reiniciar, mesmo
# depois do git pull trazer o codigo novo.
set -e
cd "$(dirname "$0")/.."

echo "==> git pull"
git pull

echo "==> build do painel (admin/dist)"
sh deploy/build-frontend.sh

echo "==> reiniciando o PHP (app) pra recarregar o opcache"
docker compose -f docker-compose.prod.yml restart app

echo "Atualizado."
