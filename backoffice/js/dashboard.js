import { esc, hoyYYYYMMDD } from './common.js';

// Demo data (ajustá a gusto)
const DEMO = {
  total: 342, k5: 210, k2: 132, hoy: 8, growth7dPct: 12,
  serie7d: [
    { d:'Lun', v:42 },{ d:'Mar', v:51 },{ d:'Mié', v:39 },
    { d:'Jue', v:47 },{ d:'Vie', v:35 },{ d:'Sáb', v:58 },{ d:'Dom', v:70 },
  ],
  ultimas: [
    { nombre:'Ana González', dni:'12345678', email:'ana@example.com', carrera:'5K', fecha:'2025-09-20 10:21:00' },
    // ...
  ]
};

export function renderDashboard(main){
  main.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
      <h2 style="margin:0">Dashboard (demo)</h2>
      <span style="opacity:.7;font-size:12px">Actualizado: ${hoyYYYYMMDD()}</span>
    </div>
    <!-- aquí pega el mismo HTML que ya usamos para KPIs, gráficos y tabla -->
    <div id="dash-root"></div>
  `;

  // … pega acá tu construcción del dashboard (kpis, canvases, etc.)
  // Si ya tenías funciones como drawBars/drawDonut, colócalas aquí o impórtalas.
}
