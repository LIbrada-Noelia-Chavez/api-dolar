<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard • Cotizaciones del Dólar</title>

  <!-- Tailwind + Chart.js via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    /* Micro detalles para un look más pulido */
    .card { @apply bg-slate-900/70 backdrop-blur rounded-2xl shadow-xl border border-slate-800; }
    .btn  { @apply inline-flex items-center justify-center px-4 py-2 rounded-xl font-semibold transition; }
    .btn-primary { @apply bg-indigo-500 hover:bg-indigo-600; }
    .btn-ghost   { @apply bg-slate-800 hover:bg-slate-700; }
    .input, select { @apply bg-slate-800 rounded-lg px-3 py-2 w-full outline-none border border-slate-700 focus:border-indigo-500; }
    .badge { @apply text-xs bg-indigo-500/15 text-indigo-300 px-2 py-1 rounded-md; }
  </style>
</head>

<body class="bg-slate-950 text-slate-100 min-h-screen">
  <div class="max-w-7xl mx-auto p-6 space-y-6">

    <!-- Header -->
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">📈 Cotizaciones del Dólar</h1>
        <p class="text-slate-400">Histórico, promedios mensuales y conversión rápida</p>
      </div>
      <div class="flex gap-2">
        <a href="/api/cotizaciones" target="_blank" class="btn btn-ghost">Ver JSON</a>
        <button id="btnSync" class="btn btn-primary">Sincronizar ahora</button>
      </div>
    </header>

    <!-- Filtros -->
    <section class="grid md:grid-cols-5 gap-4">
      <div class="card p-4 md:col-span-3">
        <div class="grid sm:grid-cols-5 gap-3">
          <div class="sm:col-span-2">
            <label class="block text-sm mb-1">Tipo</label>
            <select id="tipo" class="input">
              <option value="oficial">oficial</option>
              <option value="blue" selected>blue</option>
              <option value="mep">mep</option>
              <option value="ccl">ccl</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm mb-1">Tipo de valor</label>
            <select id="tipo_valor" class="input">
              <option value="venta" selected>venta</option>
              <option value="compra">compra</option>
            </select>
          </div>
          <div class="sm:col-span-1 flex items-end">
            <button id="btnFiltrar" class="btn btn-primary w-full">Aplicar</button>
          </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-3 mt-3">
          <div>
            <label class="block text-sm mb-1">Desde</label>
            <input id="from" type="date" class="input">
          </div>
          <div>
            <label class="block text-sm mb-1">Hasta</label>
            <input id="to" type="date" class="input">
          </div>
          <div class="flex items-end">
            <button id="btnLimpiar" class="btn btn-ghost w-full">Limpiar fechas</button>
          </div>
        </div>
      </div>

      <!-- Conversión rápida -->
      <div class="card p-4 md:col-span-2">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold">Conversión rápida</h2>
          <span class="badge" id="cotizacionBadge">—</span>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm mb-1">$ USD</label>
            <input id="monto" type="number" min="1" step="0.01" value="100" class="input">
          </div>
          <div class="flex items-end">
            <button id="btnConvertir" class="btn btn-primary w-full">Convertir</button>
          </div>
        </div>
        <div id="resultado" class="mt-3 text-lg font-semibold text-emerald-300">—</div>
      </div>
    </section>

    <!-- Chart -->
    <section class="card p-4">
      <div class="flex items-center justify-between mb-2">
        <h2 class="font-semibold">Promedio mensual</h2>
        <div id="chartLegend" class="text-sm text-slate-400"></div>
      </div>
      <canvas id="chart" height="110"></canvas>
    </section>

    <!-- Tabla -->
    <section class="card p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Histórico</h2>
        <div class="text-sm text-slate-400" id="totalReg">—</div>
      </div>

      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-800 text-slate-300">
            <tr>
              <th class="text-left p-2">Fecha</th>
              <th class="text-left p-2">Tipo</th>
              <th class="text-left p-2">Valor</th>
            </tr>
          </thead>
          <tbody id="tbody" class="divide-y divide-slate-800"></tbody>
        </table>
      </div>

      <div class="flex justify-end mt-3 gap-2">
        <button id="prev" class="btn btn-ghost">Anterior</button>
        <button id="next" class="btn btn-ghost">Siguiente</button>
      </div>
    </section>

    <!-- Toast -->
    <div id="toast" class="fixed bottom-4 right-4 hidden">
      <div class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 shadow-lg">
        <span id="toastMsg"></span>
      </div>
    </div>

  </div>

<script>
const $ = s => document.querySelector(s);
let chart, nextUrl=null, prevUrl=null;

function toast(msg){
  $('#toastMsg').textContent = msg;
  $('#toast').classList.remove('hidden');
  setTimeout(()=>$('#toast').classList.add('hidden'), 1800);
}

async function fetchJSON(url, opts={}) {
  const r = await fetch(url, opts);
  if(!r.ok) throw new Error(`HTTP ${r.status}`);
  return r.json();
}

function qsFromFilters(pageUrl = '/api/cotizaciones'){
  const tipo = $('#tipo').value;
  const tv   = $('#tipo_valor').value;
  const from = $('#from').value;
  const to   = $('#to').value;

  const p = new URLSearchParams();
  p.set('tipo', tipo);
  p.set('tipo_valor', tv);
  if(from) p.set('from', from);
  if(to)   p.set('to', to);

  return `${pageUrl}?${p.toString()}`;
}

async function loadPromedios(){
  const tipo = $('#tipo').value;
  const tv   = $('#tipo_valor').value;
  const url  = `/api/cotizaciones/promedios?tipo=${tipo}&tipo_valor=${tv}`;
  const data = await fetchJSON(url);

  const labels  = data.map(d => d.mes ?? d.MES ?? d.mes_anio ?? '—');
  const valores = data.map(d => Number(d.promedio));

  if(chart) chart.destroy();
  chart = new Chart($('#chart'), {
    type: 'line',
    data: { labels, datasets: [{ label: `${tipo.toUpperCase()} - ${tv}`, data: valores, tension:.3, fill:false }]},
    options: { responsive:true, plugins:{ legend:{ display:true }}, scales:{ y:{ beginAtZero:false } } }
  });
  $('#chartLegend').textContent = `Serie: ${tipo} / ${tv}`;
}

async function loadTabla(pageUrl){
  const url = qsFromFilters(pageUrl);
  const res = await fetchJSON(url);
  const rows = res.data ?? res; // soporta paginado Laravel o lista simple

  $('#tbody').innerHTML = (rows || []).map(r => `
    <tr>
      <td class="p-2">${new Date(r.obtenido_en).toLocaleString()}</td>
      <td class="p-2">${r.tipo} / ${r.tipo_valor}</td>
      <td class="p-2">$ ${Number(r.valor).toFixed(2)}</td>
    </tr>
  `).join('');

  $('#totalReg').textContent = `Mostrando ${rows?.length ?? 0} registros`;
  nextUrl = res.next_page_url ?? null;
  prevUrl = res.prev_page_url ?? null;

  $('#next').disabled = !nextUrl;
  $('#prev').disabled = !prevUrl;
}

async function convertir(){
  const usd = Number($('#monto').value || 0);
  const tipo = $('#tipo').value;
  if(usd<=0){ toast('Ingresá un monto mayor a 0'); return; }

  const data = await fetchJSON(`/api/convertir?valor=${usd}&tipo=${tipo}`);
  $('#resultado').textContent = `≈ $ ${data.resultado_en_pesos.toLocaleString('es-AR', {minimumFractionDigits:2})}`;
  $('#cotizacionBadge').textContent = `1 USD = $${Number(data.cotizacion).toFixed(2)} (${tipo})`;
  toast('Conversión realizada ✅');
}

$('#btnFiltrar').addEventListener('click', async ()=>{
  await loadPromedios();
  await loadTabla('/api/cotizaciones'); 
});

$('#btnLimpiar').addEventListener('click', async ()=>{
  $('#from').value=''; $('#to').value='';
  await loadPromedios();
  await loadTabla('/api/cotizaciones');
});

$('#btnSync').addEventListener('click', async ()=>{
  try{
    await fetchJSON('/api/cotizaciones/sync', { method:'POST' });
    toast('Sincronización lanzada');
    await loadPromedios(); await loadTabla('/api/cotizaciones');
  }catch(e){ toast('Error al sincronizar'); }
});

$('#btnConvertir').addEventListener('click', convertir);
$('#next').addEventListener('click', ()=> loadTabla(nextUrl || '/api/cotizaciones'));
$('#prev').addEventListener('click', ()=> loadTabla(prevUrl || '/api/cotizaciones'));

(async ()=>{
  await loadPromedios();
  await loadTabla('/api/cotizaciones');
})();
</script>
</body>
</html>
