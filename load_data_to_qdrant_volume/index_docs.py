# этот скрипт должен брать готовые вектора и закидывать их в qdrant чтобы они оказались в volumes его контейнера
# это делается чтобы при запуске на проде qdrant просто запускался, брал базу данных из volumes и сервис был готов к работе
import uuid
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



with h5py.File("habr_embeddings.h5", 'r') as f:
    keys_struct = f['keys'][:]
    tensors = f['tensors'][:]

def decode_if_bytes(val):
    return val.decode('utf-8') if isinstance(val, bytes) else val

def point_generator(keys_struct, tensors):
    for i, (row, vec) in enumerate(zip(keys_struct, tensors)):
        point_id = uuid.uuid4()
        url = decode_if_bytes(row['url'])
        text = decode_if_bytes(row['text_markdown'])
        payload = {"text": text, "link": url}
        yield PointStruct(id=point_id, vector=vec.tolist(), payload=payload)


points_gen = point_generator(keys_struct, tensors)
points_list = list(points_gen)
# Загрузка батчами по умолчанию 64 точки
client.upload_points(
    collection_name=collection_name,
    points=point_generator(keys_struct, tensors),
    batch_size=5,  # можно настроить под свои задачи
    wait=True,  # дождаться подтверждения записи
)


total = len(points_list)  # предполагаем, что items — список или объект с длиной
print(f" Successfully uploaded {total} points to collection '{collection_name}'.")

# print(f"Upsert done: {len(points)} points into collection")
