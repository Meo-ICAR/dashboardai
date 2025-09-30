<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard AI Charting</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; margin: 24px; }
        .row { display: flex; gap: 16px; flex-wrap: wrap; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .w-100 { width: 100%; }
        .w-50 { width: 48%; }
        label { font-weight: 600; display: block; margin-bottom: 8px; }
        input[type="text"], input[type="date"] { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        button { padding: 8px 12px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer; }
        button.secondary { background: #6b7280; }
        ul { list-style: none; padding-left: 0; }
        li { padding: 6px 0; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
        small { color: #6b7280; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.baseURL = '{{ url('/') }}';
    </script>
</head>
<body>
    <h1>Dashboard AI Charting</h1>
    <div class="row">
        <div class="card w-50">
            <label for="prompt">Richiesta (linguaggio naturale o JSON config)</label>
            <input id="prompt" type="text" placeholder="Esempio: Mostrami l'andamento giornaliero dei lead negli ultimi 30 giorni" />
            <div style="margin-top:8px; display:flex; gap:8px;">
                <button id="generateBtn">Genera Grafico</button>
                <button id="saveBtn" class="secondary">Salva Grafico</button>
            </div>
        </div>
        <div class="card w-50">
            <label>Report Salvati</label>
            <ul id="savedList"></ul>
            <div style="display:flex; gap:8px; margin-top:8px;">
                <input id="startDate" type="date" />
                <input id="endDate" type="date" />
                <button id="rerunBtn" class="secondary">Riesegui</button>
            </div>
        </div>
    </div>

    <div class="card w-100" style="margin-top:16px;">
        <h3 id="chartTitle" style="margin-top:0;">Grafico</h3>
        <small id="chartDesc"></small>
        <canvas id="chartCanvas" height="120"></canvas>
    </div>

    <script>
        let currentChart = null;
        let currentConfig = null;
        let selectedSavedChartId = null;

        async function listSaved() {
            const { data } = await axios.get('/api/saved-charts');
            const ul = document.getElementById('savedList');
            ul.innerHTML = '';
            data.forEach(item => {
                const li = document.createElement('li');
                const left = document.createElement('div');
                const right = document.createElement('div');
                left.innerHTML = `<strong>${item.title}</strong><br><small>${item.description || ''}</small>`;
                const btn = document.createElement('button');
                btn.textContent = 'Seleziona';
                btn.onclick = () => { selectedSavedChartId = item.id; };
                right.appendChild(btn);
                li.appendChild(left);
                li.appendChild(right);
                ul.appendChild(li);
            });
        }

        function renderChart(payload) {
            document.getElementById('chartTitle').textContent = payload.options?.title || '';
            document.getElementById('chartDesc').textContent = payload.options?.description || '';
            const ctx = document.getElementById('chartCanvas').getContext('2d');
            if (currentChart) currentChart.destroy();
            currentChart = new Chart(ctx, {
                type: payload.chart_type || 'bar',
                data: payload.data,
                options: {
                    responsive: true,
                    onClick: async (evt, elements) => {
                        if (!elements.length) return;
                        const index = elements[0].index;
                        const label = payload.data.labels[index];
                        const { data: detail } = await axios.post('/api/drill-down', {
                            current_config: currentConfig,
                            drill_value: label
                        });
                        currentConfig = detail.config;
                        renderChart(detail);
                    },
                    plugins: { legend: { display: true } }
                }
            });
        }

        document.getElementById('generateBtn').onclick = async () => {
            const prompt = document.getElementById('prompt').value;
            try {
                const { data } = await axios.post('/api/generate-chart', { prompt });
                currentConfig = data.config;
                renderChart(data);
            } catch (e) {
                console.error(e);
                alert('Errore durante la generazione del grafico. Verifica la console e l\'API.');
            }
        };

        document.getElementById('saveBtn').onclick = async () => {
            if (!currentConfig) { alert('Genera prima un grafico.'); return; }
            const title = prompt('Titolo del report:');
            if (!title) return;
            const description = prompt('Descrizione breve:') || '';
            await axios.post('/api/save-chart', { title, description, ai_configuration: currentConfig });
            await listSaved();
            alert('Salvato.');
        };

        document.getElementById('rerunBtn').onclick = async () => {
            if (!selectedSavedChartId) { alert('Seleziona un report salvato.'); return; }
            const start = document.getElementById('startDate').value || null;
            const end = document.getElementById('endDate').value || null;
            const { data } = await axios.post(`/api/rerun-chart/${selectedSavedChartId}`, { start_date: start, end_date: end });
            currentConfig = data.config;
            renderChart(data);
        };

        listSaved();
    </script>
</body>
</html>
