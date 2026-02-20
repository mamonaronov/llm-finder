<?php // Этот файл должен называться, например, index.html (или .php, но тогда уберите теги <?php)
// Если это чистый HTML-файл, удалите строку <?php и используйте расширение .html
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Поиск по документам (RAG)</title>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    background-color: #f8f9fa;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}
.container {
    width: 100%;
    max-width: 700px;
    text-align: center;
}
h1 {
    color: #333;
    margin-bottom: 30px;
}
.search-form {
    margin-bottom: 30px;
    display: flex;
    justify-content: center;
}
.search-form input[type="text"] {
    flex: 1;
    padding: 12px 20px;
    font-size: 16px;
    border: 2px solid #ddd;
    border-radius: 24px 0 0 24px;
    outline: none;
    transition: border-color 0.3s;
    box-sizing: border-box;
}
.search-form input[type="text"]:focus {
    border-color: #007bff;
}
.search-form button {
    padding: 12px 24px;
    font-size: 16px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 0 24px 24px 0;
    cursor: pointer;
    transition: background-color 0.3s;
}
.search-form button:hover {
    background-color: #0056b3;
}
.search-form button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}
.results {
    text-align: left;
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    width: 100%;
}
.result-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
}
.result-item:last-child {
    border-bottom: none;
}
.result-item h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}
.result-item h3 a {
    color: #007bff;
    text-decoration: none;
}
.result-item h3 a:hover {
    text-decoration: underline;
}
.result-item .source {
    color: #28a745;
    font-size: 14px;
    margin: 3px 0;
    word-break: break-all;
}
.result-item .snippet {
    margin: 8px 0 5px 0;
    color: #555;
    font-size: 14px;
    line-height: 1.5;
}
.result-item .score {
    font-size: 13px;
    color: #888;
    margin-top: 5px;
}
.no-results {
    text-align: center;
    color: #777;
    padding: 20px;
}
.stats {
    margin-bottom: 15px;
    font-size: 14px;
    color: #777;
}
.error {
    color: #dc3545;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 12px;
    margin-bottom: 15px;
}
.loader {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 10px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</head>
<body>
<div class="container">
<h1>🔍 Поиск по документам</h1>
<div class="search-form">
<input type="text" id="queryInput" placeholder="Введите запрос..." autofocus>
<button type="submit" id="searchBtn">Поиск</button>
</div>
<div class="results" id="resultsContainer">
<!-- Сюда будут выводиться результаты или подсказка -->
<div class="no-results">Введите запрос и нажмите "Поиск"</div>
</div>
</div>

<script>
const searchBtn = document.getElementById('searchBtn');
const queryInput = document.getElementById('queryInput');
const resultsContainer = document.getElementById('resultsContainer');

// Вспомогательная функция для обрезки текста
function truncateText(text, maxLength = 200) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '…';
}

// Функция для экранирования HTML (чтобы избежать XSS)
function escapeHtml(unsafe) {
    return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// Функция отображения результатов
function renderResults(data, query) {
    if (!query.trim()) {
        resultsContainer.innerHTML = '<div class="no-results">Введите запрос в поле выше</div>';
        return;
    }

    // Если данные не являются массивом или пусты
    if (!Array.isArray(data) || data.length === 0) {
        resultsContainer.innerHTML = `<div class="no-results">По запросу «${escapeHtml(query)}» ничего не найдено</div>`;
        return;
    }

    // Статистика
    const stats = `<div class="stats">Найдено документов: ${data.length}</div>`;

    // Формируем список результатов
    const items = data.map(item => {
        // Безопасное экранирование полей
        const text = escapeHtml(item.text || '');
        const source = escapeHtml(item.source || 'документ');
        const score = item.score ? item.score.toFixed(3) : '?';
    // Предполагаем, что файлы лежат в папке /documents/ (доступной по HTTP)
    const fileUrl = `/documents/${encodeURIComponent(source)}`;
    // Имя файла без пути (уже source содержит имя)
    const fileName = source;

    // Обрезаем текст для краткого описания
    const snippet = truncateText(text, 200);

    return `
    <div class="result-item">
    <h3><a href="${fileUrl}" target="_blank" title="Открыть исходный файл">${fileName}</a></h3>
    <div class="source">${fileUrl}</div>
    <div class="snippet">${snippet}</div>
    <div class="score">Релевантность: ${score}</div>
    </div>
    `;
    }).join('');

    resultsContainer.innerHTML = stats + items;
}

// Функция для отображения ошибки
function showError(message) {
    resultsContainer.innerHTML = `<div class="error">${escapeHtml(message)}</div>`;
}

// Функция для показа загрузки
function setLoading(isLoading) {
    if (isLoading) {
        searchBtn.disabled = true;
        searchBtn.innerHTML = 'Поиск <span class="loader"></span>';
    } else {
        searchBtn.disabled = false;
        searchBtn.innerHTML = 'Поиск';
    }
}

// Основная функция поиска (отправка запроса на PHP)
async function search(query) {
    if (!query.trim()) {
        renderResults([], query);
        return;
    }

    setLoading(true);

    try {
        const response = await fetch('search.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ query: query, top_k: 10 }) // можно регулировать количество
        });

        if (!response.ok) {
            // Попытка прочитать текст ошибки из ответа
            let errorText = 'Ошибка сервера';
            try {
                const errorData = await response.json();
                errorText = errorData.error || errorText;
            } catch (e) {}
            throw new Error(errorText);
        }

        const data = await response.json();
        renderResults(data, query);
    } catch (error) {
        console.error('Search error:', error);
        showError('Произошла ошибка при поиске: ' + error.message);
        // Очищаем результаты при ошибке
        resultsContainer.innerHTML = '<div class="no-results">Повторите попытку позже</div>';
    } finally {
        setLoading(false);
    }
}

// Обработчик отправки формы (по нажатию кнопки или Enter)
searchBtn.addEventListener('click', (e) => {
    e.preventDefault();
    const query = queryInput.value;
    search(query);
});

queryInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchBtn.click();
    }
});

// Инициализация: фокус на поле ввода
window.addEventListener('load', () => {
    queryInput.focus();
});
</script>
</body>
</html>
