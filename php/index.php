<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск по документам (RAG) — каркас для вёрстки</title>
    <!-- Сюда фронтендер подключит свои стили -->
</head>
<body>
    <!-- Минимальная разметка, необходимая для работы JS.
         Все id оставлены, классы используются только в динамически
         создаваемых результатах (см. renderResults) и могут быть заменены. -->
    <div class="container">
        <h1>🔍 Поиск по документам</h1>
        <div class="search-form">
            <input type="text" id="queryInput" placeholder="Введите запрос..." autofocus>
            <button type="submit" id="searchBtn">Поиск</button>
        </div>
        <div class="results" id="resultsContainer">
            <!-- Сюда JS будет подставлять результаты -->
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

            if (!Array.isArray(data) || data.length === 0) {
                resultsContainer.innerHTML = `<div class="no-results">По запросу «${escapeHtml(query)}» ничего не найдено</div>`;
                return;
            }

            const stats = `<div class="stats">Найдено документов: ${data.length}</div>`;

            const items = data.map(item => {
                const text = escapeHtml(item.text || '');
                const source = escapeHtml(item.source || 'документ');
                const score = item.score ? item.score.toFixed(3) : '?';
                // Вместо const HabrUrl = `${encodeURIComponent(source)}`; делаем проверку
                let linkUrl;
                if (source.startsWith('http://') || source.startsWith('https://')) {
                    // Внешняя ссылка — используем как есть
                    linkUrl = source;
                } else {
                    // Локальный файл — кодируем только имя файла для безопасности
                    linkUrl = `/documents/${encodeURIComponent(source)}`;
                }
                const fileName = source;
                const snippet = truncateText(text, 200);

                return `
                    <div class="result-item">
                        <h3><a href="${linkUrl}" target="_blank" title="Открыть статью">${fileName}</a></h3>
                        <div class="snippet">${snippet}</div>
                        <div class="score">Релевантность: ${score}</div>
                    </div>
                `;
            }).join('');

            resultsContainer.innerHTML = stats + items;
        }

        function showError(message) {
            resultsContainer.innerHTML = `<div class="error">${escapeHtml(message)}</div>`;
        }

        function setLoading(isLoading) {
            if (isLoading) {
                searchBtn.disabled = true;
                searchBtn.innerHTML = 'Поиск <span class="loader"></span>';
            } else {
                searchBtn.disabled = false;
                searchBtn.innerHTML = 'Поиск';
            }
        }

        async function search(query) {
            if (!query.trim()) {
                renderResults([], query);
                return;
            }

            setLoading(true);

            try {
                const response = await fetch('search.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ query: query, top_k: 10 })
                });

                if (!response.ok) {
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
                resultsContainer.innerHTML = '<div class="no-results">Повторите попытку позже</div>';
            } finally {
                setLoading(false);
            }
        }

        searchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            search(queryInput.value);
        });

        queryInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchBtn.click();
            }
        });

        window.addEventListener('load', () => {
            queryInput.focus();
        });
    </script>
</body>
</html>
