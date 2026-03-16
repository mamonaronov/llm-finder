FROM python:3.10-slim

WORKDIR /app

# Установка зависимостей
COPY requirements.txt .

# тут две опции: (одна из них всегда должна быть закоментирована)
# либо мы ставим зависимости с кешированием 
RUN --mount=type=cache,target=/root/.cache/pip \
    pip install -r requirements.txt
# либо без 
# RUN pip install --no-cache-dir -r requirements.txt






# Копирование кода
COPY search_service.py .

# Порт для FastAPI
EXPOSE 8000

# Запуск сервиса
CMD ["uvicorn", "search_service:app", "--host", "0.0.0.0", "--port", "8000"]
