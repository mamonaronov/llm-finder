#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

QDRANT_CONTAINER="qdrant-temp"
PYTHON_CONTAINER="index-docs-temp"
NETWORK_NAME="temp-net"

cleanup() {
    echo "Очистка: остановка и удаление контейнеров..."
    docker stop "$QDRANT_CONTAINER" 2>/dev/null || true
    docker rm "$QDRANT_CONTAINER" 2>/dev/null || true
    docker stop "$PYTHON_CONTAINER" 2>/dev/null || true
    docker rm "$PYTHON_CONTAINER" 2>/dev/null || true
    docker network rm "$NETWORK_NAME" 2>/dev/null || true
}
trap cleanup EXIT

# Создаём изолированную сеть
docker network create "$NETWORK_NAME"

# Запускаем Qdrant с монтированием volume'а
docker run -d \
    --name "$QDRANT_CONTAINER" \
    --network "$NETWORK_NAME" \
    -v "$SCRIPT_DIR/../qdrant_storage:/qdrant/storage" \
    qdrant/qdrant

echo "Ожидание запуска Qdrant..."

MAX_RETRIES=30
RETRY_INTERVAL=2
RETRIES=0

# Проверка готовности через контейнер с curl в той же сети
until docker run --rm --network "$NETWORK_NAME" curlimages/curl:latest -s -f "http://$QDRANT_CONTAINER:6333/readyz" > /dev/null; do
    RETRIES=$((RETRIES + 1))
    if [ $RETRIES -ge $MAX_RETRIES ]; then
        echo "❌ Qdrant не запустился за отведённое время, прерывание."
        exit 1
    fi
    echo "Попытка $RETRIES из $MAX_RETRIES: Qdrant ещё не готов, ждём ${RETRY_INTERVAL}с..."
    sleep $RETRY_INTERVAL
done

echo "✅ Qdrant готов."

# Сборка образа для Python
echo "Сборка образа index-docs-img..."
docker build -t index-docs-img .

# Запуск Python‑скрипта
echo "Запуск index_docs.py..."
docker run --rm \
    --name "$PYTHON_CONTAINER" \
    --network "$NETWORK_NAME" \
    -e QDRANT_URL="http://$QDRANT_CONTAINER:6333" \
    -v "$SCRIPT_DIR/data.json:/app/data.json" \
    index-docs-img



echo "✅ Данные успешно загружены в Qdrant volume."
