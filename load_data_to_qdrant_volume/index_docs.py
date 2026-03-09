# этот скрипт должен брать готовые вектора и закидывать их в qdrant чтобы они оказались в volumes его контейнера
# это делается чтобы при запуске на проде qdrant просто запускался, брал базу данных из volumes и сервис был готов к работе

import json
import os

from qdrant_client import QdrantClient

# from qdrant_client.conversions.conversion import payload_to_grpc
from qdrant_client.models import Distance, PointStruct, VectorParams
from sentence_transformers import SentenceTransformer

QDRANT_URL = os.getenv("QDRANT_URL", "http://localhost:6333")
client = QdrantClient(url=QDRANT_URL)


collection_name = "the_test_name"

# --- Проверка и удаление существующей коллекции ---
if client.collection_exists(collection_name):
    print(f"Коллекция '{collection_name}' уже существует. Удаляем...")
    client.delete_collection(collection_name)
    print("Коллекция удалена.")

# --- Создание новой коллекции ---
client.create_collection(
    collection_name=collection_name,
    vectors_config=VectorParams(size=384, distance=Distance.COSINE),
)


collections = client.get_collections()
print(f"Коллекция '{collection_name}' создана.")
print([c for c in collections])


# вычисление векторов
model = SentenceTransformer("sentence-transformers/all-MiniLM-L6-v2")

with open("data.json", "r", encoding="utf-8") as f:
    items = json.load(f)

texts = [it["text"] for it in items]
vectors = model.encode(texts, normalize_embeddings=True)


# старый способ загрузки
"""points = []
for it, vec in zip(items, vectors):
    payload = dict(it)
    pid = payload.pop("id", "link")
    points.append(PointStruct(id=pid, vector=vec.tolist(), payload=payload))


client.upsert(collection_name="collection_name", points=points)
"""


# Генератор точек – не хранит все в памяти
def point_generator(items, vectors):
    for it, vec in zip(items, vectors):
        payload = dict(it)
        pid = payload.pop("id", "link")
        # можно сразу возвращать PointStruct, а можно кортеж – upload_points сам поймёт
        yield PointStruct(id=pid, vector=vec.tolist(), payload=payload)


# Загрузка батчами по умолчанию 64 точки
client.upload_points(
    collection_name=collection_name,
    points=point_generator(items, vectors),
    batch_size=64,  # можно настроить под свои задачи
    wait=True,  # дождаться подтверждения записи
)


total = len(items)  # предполагаем, что items — список или объект с длиной
print(f" Successfully uploaded {total} points to collection '{collection_name}'.")

# print(f"Upsert done: {len(points)} points into collection")
