<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Cotizaciones</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    body { background:#0f172a; color:#f1f5f9; }
    .card { background:#1e293b; border:1px solid #334155; border-radius:12px; padding:16px; }
    .btn  { background:#3b82f6; color:#fff; padding:.5rem 1rem; border-radius:.5rem; font-weight:600; }
    .btn:hover{ background:#2563eb; }
    .btn-ghost{ background:#0f172a; border:1px solid #334155; }
    .btn-ghost:hover{ background:#111827; }
    .input{ background:#1e293b; color:#f1f5f9; border:1px solid #334155; border-radius:.5rem; padding:.5rem; width:100%; }
    /* 🔧 selects legibles en dark */
    select, option{ background:#1e293b !important; color:#f1f5f9 !important; }
    table{ width:100%; border-collapse:collapse; margin-top:.5rem;}
    th,td{ padding:.6rem; border-bottom:1px solid #334155; text-align:left; }
    th{ background:#111827; color:#cbd5e1; }
    tr:hover{ background:#1f2937; }
  </style>
</head>
<body class="p-6">

  <h1 class="text-2xl font-bold mb-2">📊 Cotizaciones del Dólar</h1>
  <p class="text-slate-400 mb-6">Histórico, promedio mensual y conversión rápida</p>

  <div class="card mb-6">
    <!-- Filtros -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div>
        <label class="text-sm block mb-1">Tipo</label>
        <select id="tipo" class="input">
          <option value="oficial">Oficial</option>
          <option value="blue" selected>Blue</option>
          <option value="mep">MEP</option>
          <option value="ccl">CCL</option>
        </select>
      </div>
      <div>
        <label class="text-sm block mb-1">Tipo de valor</label>
        <select id="tipo_valor" class="input">
          <option value="venta" selected>Venta</option>
          <option value="compra">Compra</option>
        </select>
      </div>
      <div>
        <label class="text-sm block mb-1">Desde</label>
        <input id="desde" type="date" class="input">
      </div>
      <div>
        <label class="text-sm block mb-1">Hasta</label>
        <input id="hasta" type="date" class="input">
      </div>
      <div class="flex items-end gap-2">
        <button id="btnAplicar" class="btn w-full">Aplicar</button>
      </div>
    </div>

    <div class="mt-3 flex gap-2">
      <button id="btnLimpiar" class="btn btn-ghost">Limpiar fechas</button>
      <button id="btnSync" class="btn btn-ghost">Sincronizar ahora</button>
      <a href="/api/cotizaciones" target="_blank" class="btn btn-ghost">Ver JSON</a>
    </div>
  </div>

  <!-- Conversión rápida -->
  <div class="card mb-6">
    <h2 class="text-lg font-semibold mb-3">Conversión rápida</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
      <div>
        <label class="text-sm block mb-1">$ USD</label>
        <input id="usd" type="number" min="1" step="0.01" value="100" class="input">
      </div>
      <div>
        <label class="text-sm block mb-1">Tipo (usa el del filtro)</label>
        <div class="text-slate-300 text-sm">Seleccionado: <span id="tipoActual" class="font-semibold">blue</span></div>
      </div>
      <div>
        <button id="btnConvertir" class="btn w-full">Convertir</button>
      </div>
    </div>
    <div id="resultado" class="mt-3 text-xl font-semibold text-emerald-300">—</div>
    <div id="cotizacion" class="text-slate-400 text-sm">Cotización usada: —</div>
  </div>

  <!-- Promedio mensual -->
  <div class="card mb-6">
    <h2 class="text-lg font-semibold mb-2">Promedio mensual</h2>
    <div id="promedio" class="text-2xl font-bold">—</div>
    <div id="promedioMeta" class="text-slate-400 text-sm mt-1">—</div>
  </div>

  <!-- Histórico -->
  <div class="card">
    <div class="flex items-center justify-between mb-2">
      <h2 class="text-lg font-semibold">Histórico</h2>
      <div class="text-slate-400 text-sm" id="totalReg">—</div>
    </div>
    <div class="overflow-auto">
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Valor</th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
    </div>
  </div>

<script>
// Helpers
const $ = s => document.querySelector(s);
const fmtMoney = n => Number(n ?? 0).toLocaleString('es-AR', { minimumFractionDigits:2, maximumFractionDigits:2 });

function buildQuery(base){
  const p = new URLSearchParams();
  const tipo = $('#tipo').value;
  const tv   = $('#tipo_valor').value;
  const d    = $('#desde').value;
  const h    = $('#hasta').value;

  p.set('tipo', tipo);
  p.set('tipo_valor', tv);
  if (d) p.set('from', d);
  if (h) p.set('to', h);

  return `${base}?${p.toString()}`;
}

async function jget(url, opts={}) {
  const r = await fetch(url, opts);
  if (!r.ok) throw new Error(`HTTP ${r.status}`);
  return r.json();
}

// Carga histórico (lista)
async function cargarHistorico(){
  const url = buildQuery('/api/cotizaciones');
  const res = await jget(url);
  const rows = res.data ?? res; // soporta paginate o lista simple

  $('#tbody').innerHTML = (rows || []).map(r => `
    <tr>
      <td>${new Date(r.obtenido_en).toLocaleString()}</td>
      <td>${r.tipo} / ${r.tipo_valor}</td>
      <td>$ ${fmtMoney(r.valor)}</td>
    </tr>
  `).join('');

  $('#totalReg').textContent = `Mostrando ${rows?.length ?? 0} registros`;
}

// Carga promedio mensual (para el mes actual)
async function cargarPromedio(){
  const tipo = $('#tipo').value;
  const tv   = $('#tipo_valor').value;

  const hoy  = new Date();
  const mes  = hoy.getMonth()+1; // 1..12
  const anio = hoy.getFullYear();

  const url = `/api/cotizaciones/promedio?tipo=${tipo}&tipo_valor=${tv}&mes=${mes}&anio=${anio}`;
  const data = await jget(url);

  $('#promedio').textContent = `$ ${fmtMoney(data.promedio)}`;
  $('#promedioMeta').textContent = `Tipo: ${data.tipo} / ${data.tipo_valor} — Mes ${data.mes} / ${data.anio}`;
}

// Conversión
async function convertir(){
  const usd  = Number($('#usd').value || 0);
  const tipo = $('#tipo').value;
  if (usd <= 0) { $('#resultado').textContent = 'Ingresá un monto mayor a 0'; return; }

  const data = await jget(`/api/convertir?valor=${usd}&tipo=${tipo}`);
  $('#resultado').textContent = `≈ $ ${fmtMoney(data.resultado_en_pesos)}`;
  $('#cotizacion').textContent = `Cotización usada: $ ${fmtMoney(data.cotizacion)} (${data.tipo})`;
}

// Sync
async function sync(){
  try{
    await jget('/api/cotizaciones/sync', { method:'POST' });
  }catch(e){ /* si tu ruta no existe/usa CSRF, ignoramos */ }
  await cargarPromedio();
  await cargarHistorico();
}

// Eventos
$('#btnAplicar').addEventListener('click', async ()=>{
  $('#tipoActual').textContent = $('#tipo').value;
  await cargarPromedio();
  await cargarHistorico();
});

$('#btnLimpiar').addEventListener('click', async ()=>{
  $('#desde').value=''; $('#hasta').value='';
  await cargarPromedio();
  await cargarHistorico();
});

$('#btnConvertir').addEventListener('click', convertir);
$('#btnSync').addEventListener('click', sync);

// Init
(async ()=>{
  $('#tipoActual').textContent = $('#tipo').value;
  await cargarPromedio();
  await cargarHistorico();
})();
</script>
</body>
</html>
