const searchBtn = document.getElementById('searchBtn');
const queryInput = document.getElementById('queryInput');
const presentation = document.getElementById('presentation');
const container = document.getElementById('container');
const resultsContainer = document.getElementById('resultsContainer');
const error_message = document.getElementById('error_message');
const hello_message = document.getElementById('hello_message');
const result_background = document.getElementById('result_background');
const search_form_container = document.getElementById('search_form_container');
var error_check = false;
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
                        <div class="snippet">${snippet}</div>
                        <h3><a href="${linkUrl}" target="_blank" title="Открыть статью">${fileName}</a></h3>
                        <div class="score">Релевантность: ${score}</div>
                    </div>
                `;
            }).join('');

            resultsContainer.innerHTML = items;
        }

        /*function showError(message) {
            resultsContainer.innerHTML = `<div class="error">${escapeHtml(message)}</div>`;
        }*/

        function setLoading(isLoading) {
            if (isLoading) {
                searchBtn.disabled = true;
                searchBtn.innerHTML = '<svg width="36px" height="36px" viewBox="0 0 200 200"><radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)"><stop offset="0" stop-color="#ffffff"></stop><stop offset=".3" stop-color="#ffffff" stop-opacity=".9"></stop><stop offset=".6" stop-color="#ffffff" stop-opacity=".6"></stop><stop offset=".8" stop-color="#ffffff" stop-opacity=".3"></stop><stop offset="1" stop-color="#ffffff" stop-opacity="0"></stop></radialGradient><circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100" cy="100" r="70"><animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform></circle><circle transform-origin="center" fill="none" opacity=".2" stroke="#ffffff" stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle></svg>';
            } else {
                if (error_check){
                    searchBtn.innerHTML = '<img src="misc/icons8-search.svg" alt="search"/>';
                    queryInput.value = "";
                    error_check = false;
                    searchBtn.disabled = false;
                    presentation.setAttribute("style","display: none;");
                    error_message.setAttribute("style", "dsiplay: flex")
                }
                else{
                    searchBtn.disabled = false;
                    presentation.setAttribute("style","display: flex;");
                    presentation.setAttribute("result","");
                    hello_message.setAttribute("style","display: none;");
                    result_background.setAttribute("result","");
                    search_form_container.setAttribute("result","");
                    searchBtn.innerHTML = '<img src="misc/icons8-search.svg" alt="search"/>';
                    resultsContainer.setAttribute("style","display: flex;");
                }
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
                error_check = true;
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