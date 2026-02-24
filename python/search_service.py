from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import os
from typing import List, Optional

app = FastAPI()

COLLECTION_NAME = os.getenv("COLLECTION_NAME", "my_docs")




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


        ## пока что тут заглушка

        results = []
        results.append(SearchResult(
            id='121',
            text='пример текста',
            source='example_link_of_dockument',
            score=0.613))


        return results
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/health")
async def health():
    return {"status": "ok"}
