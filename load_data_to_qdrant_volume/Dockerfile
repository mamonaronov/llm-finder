FROM python:3.9-slim as base
ENV PYTHONUNBUFFERED=1
WORKDIR /app
COPY requirements.txt .

FROM base as with-cache
RUN --mount=type=cache,target=/root/.cache/pip \
    pip install -r requirements.txt

FROM base as without-cache
RUN pip install --no-cache-dir -r requirements.txt

# Общий финальный этап, использующий результат предыдущего
FROM ${TARGET:-with-cache} as final
ENV PYTHONUNBUFFERED=1
COPY index_docs.py .
CMD ["python", "index_docs.py"]
