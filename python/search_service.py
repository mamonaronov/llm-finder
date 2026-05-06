from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from qdrant_client import QdrantClient
import os
from typing import List, Optional
from qdrant_client.models import Distance, PointStruct, VectorParams

# эта библиотека используется для работы тестовой нейронки так что когда будет нормальная, ее можно будет удалить
from sentence_transformers import SentenceTransformer

app = FastAPI()

COLLECTION_NAME = os.getenv("COLLECTION_NAME", "the_test_name")
QDRANT_URL = os.getenv("QDRANT_URL", "http://localhost:6333")
client = QdrantClient(url=QDRANT_URL)

# Проверим существование коллекции при старте и создадим, если нужно
try:
    client.get_collection(COLLECTION_NAME)
except Exception:
    # Если коллекции нет, выведем ошибку
    print(f"нет такой коллекции: {COLLECTION_NAME}.")

# тестовая временная модель
model = SentenceTransformer("microsoft/harrier-oss-v1-0.6b")


# Модель запроса
class SearchRequest(BaseModel):
    query: str
    top_k: Optional[int] = 5


# Модель ответа
class SearchResult(BaseModel):
    id: str
    text: str
    source: str
    score: float


@app.post("/search", response_model=List[SearchResult])
async def search(request: SearchRequest):
    try:
        results = []
        # вычисление вектора временной моделью
        vector = model.encode(request.query)

        query_results = client.query_points(
            collection_name=COLLECTION_NAME,
            query=vector.tolist(),
            limit=request.top_k
        )

        for point in query_results.points:
            results.append(SearchResult(
                id=str(point.id),
                text=point.payload.get("text", ""),
                source=point.payload.get("link", "unknown"),
                score=point.score
            ))

        return results
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/health")
async def health():
    return {"status": "ok"}