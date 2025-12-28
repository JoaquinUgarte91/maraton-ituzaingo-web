import { LISTAR_URL, esc } from './common.js';

export async function cargarInscripciones(main, { q = '', page = 1, pageSize = 20 } = {}) {
  const offset = (page - 1) * pageSize;
  main.innerHTML = `<h2>Usuarios Registrados</h2><p>Cargando...</p>`;

  try {
    const url = `${LISTAR_URL}?limit=${pageSize}&offset=${offset}&q=${encodeURIComponent(q)}&sort=fecha_inscripcion&order=desc`;
    const res = await fetch(url);
    const ct = res.headers.get('content-type') || '';
    const data = ct.includes('application/json') ? await res.json() : { success:false, message: await res.text() };
    if (!res.ok || !data.success) throw new Error(data.message || 'No se pudo obtener inscripciones');

    const filas = (data.items || []).map(u => `
      <tr>
        <td>${esc(u.nombre)}</td>
        <td>${esc(u.dni)}</td>
        <td>${esc(u.email)}</td>
        <td>${esc(u.carrera)}</td>
        <td>${new Date(u.fecha_inscripcion).toLocaleString()}</td>
      </tr>
    `).join('');

    main.innerHTML = `
      <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap">
        <h2 style="margin:0">Usuarios Registrados</h2>
        <input id="buscar" type="search" placeholder="Buscar por nombre/DNI/email"
               style="padding:8px; border-radius:8px; border:1px solid #ddd; min-width:260px" value="${esc(q)}"/>
        <span style="opacity:.7">Total: ${data.total}</span>
      </div>
      <table>
        <thead><tr><th>Nombre</th><th>DNI</th><th>Email</th><th>Distancia</th><th>Fecha inscripción</th></tr></thead>
        <tbody>${filas || `<tr><td colspan="5" style="text-align:center; padding:20px;">Sin datos</td></tr>`}</tbody>
      </table>
    `;

    document.getElementById('buscar').addEventListener('change', () =>
      cargarInscripciones(main, { q: document.getElementById('buscar').value, page: 1, pageSize })
    );

  } catch (err) {
    main.innerHTML = `<h2>Usuarios Registrados</h2><p style="color:#b00020">Error: ${esc(err.message)}</p>`;
  }
}
