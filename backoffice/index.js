document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form');
  const errorBox = document.getElementById('error-message');

  if (!form) {
    console.error('No se encontró #login-form');
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.textContent = '';

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    if (!username || !password) {
      errorBox.textContent = 'Ingresá usuario y contraseña';
      return;
    }

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);

    try {
      const res = await fetch('../api/login.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });

      const data = await res.json();

      if (!res.ok || !data.ok) {
        errorBox.textContent = data.message || 'Usuario o contraseña incorrectos';
        return;
      }

      // ✅ Login correcto → panel
      window.location.href = './inicio.html';

    } catch (err) {
      console.error(err);
      errorBox.textContent = 'Error de conexión con el servidor';
    }
  });
});
