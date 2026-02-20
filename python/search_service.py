from fastapi import FastAPI, HTTPException
import os

app = FastAPI()

COLLECTION_NAME = os.getenv("COLLECTION_NAME", "my_docs")




@app.post("/search")
async def search():
    try:





        ## пока что тут заглушка







        results = 'ссылка на статью'
        return results
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/health")
async def health():
    return {"status": "ok"}
