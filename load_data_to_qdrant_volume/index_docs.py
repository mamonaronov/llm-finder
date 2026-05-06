# этот скрипт должен брать готовые вектора и закидывать их в qdrant чтобы они оказались в volumes его контейнера
# это делается чтобы при запуске на проде qdrant просто запускался, брал базу данных из volumes и сервис был готов к работе
import json
import h5py
import os

from qdrant_client import QdrantClient

# from qdrant_client.conversions.conversion import payload_to_grpc
from qdrant_client.models import Distance, PointStruct, VectorParams
from sentence_transformers import SentenceTransformer

QDRANT_URL = os.getenv("QDRANT_URL", "http://localhost:6333") #Ссылка по которой запускается контейнер
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
    vectors_config=VectorParams(size=1024, distance=Distance.COSINE), #Параметры векторов - количество измерений,
    # метод рассчета расстояний
)


print(f"Коллекция '{collection_name}' создана.")
collections = client.get_collections()
print([c for c in collections])


# загрузка h5 файла
with h5py.File("habr_embeddings.h5", 'r') as f: #Файловы тип данных
    keys = f['keys'][:] #Индекс [:] означает что нужно выбрать все значения
    tensors = f['tensors'][:] #тип - numpy array
vectors = [tensor for tensor in tensors]
payloads = [{'text':key.decode('utf-8')} for key in keys]


# старый способ загрузки векторов в qdrant
"""points = []
for it, vec in zip(items, vectors):
    payload = dict(it)
    pid = payload.pop("id", "link")
    points.append(PointStruct(id=pid, vector=vec.tolist(), payload=payload))


client.upsert(collection_name="collection_name", points=points)
"""



# Генератор точек – не хранит все в памяти
def point_generator(payloads, vectors):
    i=0
    for pl, vec in zip(payloads, vectors):
        payload = dict(pl)
        # можно сразу возвращать PointStruct, а можно кортеж – upload_points сам поймёт
        yield PointStruct(id = i, vector=vec.tolist(), payload=payload)
        i+=1


# Загрузка батчами по умолчанию 64 точки
client.upload_points(
    collection_name=collection_name,
    points=point_generator(payloads, vectors),
    batch_size=5,  # можно настроить под свои задачи
    wait=True,  # дождаться подтверждения записи
)


total = len(payloads)  # предполагаем, что items — список или объект с длиной
print(f" Successfully uploaded {total} points to collection '{collection_name}'.")

# print(f"Upsert done: {len(points)} points into collection")
