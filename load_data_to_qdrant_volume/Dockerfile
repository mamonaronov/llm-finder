FROM python:3.9-slim as base
WORKDIR /app
COPY requirements.txt .

FROM base as with-cache
RUN --mount=type=cache,target=/root/.cache/pip \
    pip install -r requirements.txt

FROM base as without-cache
RUN pip install --no-cache-dir -r requirements.txt

# Общий финальный этап, использующий результат предыдущего
FROM ${TARGET:-with-cache} as final
COPY index_docs.py .
CMD ["python", "index_docs.py"]
