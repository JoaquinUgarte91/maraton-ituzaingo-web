// URLs y utilidades compartidas
export const LISTAR_URL = '/api/listar_inscripciones.php';

export const esc = (s) => {
  const div = document.createElement('div');
  div.textContent = s ?? '';
  return div.innerHTML;
};

export const hoyYYYYMMDD = () => {
  const d = new Date(), p = n => String(n).padStart(2,'0');
  return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`;
};
