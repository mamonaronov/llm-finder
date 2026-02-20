FROM python:3.10-slim

WORKDIR /app

# Установка зависимостей
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Копирование кода
COPY search_service.py .
COPY index_docs.py .

# Порт для FastAPI
EXPOSE 8000

# Запуск сервиса
CMD ["uvicorn", "search_service:app", "--host", "0.0.0.0", "--port", "8000"]
