document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form');
  const errorBox = document.getElementById('error-message');

  if (!form) {
    console.error('No se encontrÃ³ #login-form');
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.textContent = '';

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    if (!username || !password) {
      errorBox.textContent = 'IngresÃ¡ usuario y contraseÃ±a';
      return;
    }

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);

    try {
      const res = await fetch('/Maraton/api/login.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });

      const data = await res.json();

      if (!res.ok || !data.ok) {
        errorBox.textContent = data.message || 'Usuario o contraseÃ±a incorrectos';
        return;
      }

      // âœ… Login correcto â†’ panel
      window.location.href = './inicio.html';

    } catch (err) {
      console.error(err);
      errorBox.textContent = 'Error de conexiÃ³n con el servidor';
    }
  });
});
