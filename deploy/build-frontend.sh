#!/bin/sh
# Builda o painel Vue (admin/) para arquivos estáticos, usando um container
# Node descartável — não precisa ter Node instalado no servidor.
set -e
cd "$(dirname "$0")/.."
docker run --rm -v "$(pwd)/admin:/app" -w /app node:20-alpine sh -c "npm ci && npm run build"
echo "Build gerado em admin/dist/"
