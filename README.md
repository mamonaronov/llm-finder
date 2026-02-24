# llm-finder
 это наш общий репозиторий с которым мы будем работать.

в папке "общая инфа" лежат:  
  1. техническое задание
  2. файл "архитектура.drawio"
     
     это схема архитектуры (этот файл можно открыть на сайте https://app.diagrams.net/)



 вот так выглядит файловая структура репозитория:

    📁 llm-finder/
    ├── 📄 docker-compose.yml
    ├── 📁 documents/
    ├── 📄 LICENSE
    ├── 📁 php/
    │   ├── 📄 Dockerfile
    │   ├── 📁 documents/
    │   ├── 📄 index.php
    │   ├── 📄 php.ini
    │   └── 📄 search.php
    ├── 📁 python/
    │   ├── 📄 Dockerfile
    │   ├── 📄 index_docs.py
    │   ├── 📄 requirements.txt
    │   └── 📄 search_service.py
    ├── 📄 README.md
    └── 📁 общая инфа/
        ├── 📄 архитектура.drawio
        └── 📄 техническое задание.txt

        
  для локального запуска надо зайти в папку где лежит файл "docker-compose.yml"
  ввести команды:
  
  docker-compose build
  
  docker-compose up
  
  и потом в браузере можно открыть http://localhost:8080/index.php
