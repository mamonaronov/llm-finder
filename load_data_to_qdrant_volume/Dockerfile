FROM python:3.9-slim
WORKDIR /app
COPY requirements.txt .
# RUN pip install --no-cache-dir -r requirements.txt
RUN --mount=type=cache,target=/root/.cache/pip \
    pip install -r requirements.txt


COPY index_docs.py /app/
CMD ["python", "index_docs.py"]
