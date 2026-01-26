// Se ejecuta cuando la página termina de cargar
document.addEventListener('DOMContentLoaded', () => {
    inicializarFormulario();
});

// Configura el formulario
function inicializarFormulario() {
    document.getElementById('carrera')?.addEventListener('change', (e) => {
        actualizarTalles(e.target.value);
    });

    document.getElementById('fecha_nacimiento')?.addEventListener('change', () => {
        validarEdad();
    });

    document.getElementById('formulario')?.addEventListener('submit', enviarFormulario);
}

// Muestra talles según si es Kids o adulto
function actualizarTalles(carrera) {
    const select = document.getElementById('talle_remera');
    select.innerHTML = '<option value="">-- Seleccioná un talle --</option>';

    if (carrera === 'Kids') {
        ['6','8','10','12','14'].forEach(t => {
            select.innerHTML += `<option value="Niño ${t}">Niño ${t}</option>`;
        });
    } else if (carrera === '10km' || carrera === '3km') {
        ['XS','S','M','L','XL','XXL','XXXL'].forEach(t => {
            select.innerHTML += `<option value="Adulto ${t}">Adulto ${t}</option>`;
        });
    }
}

// Valida que la edad coincida con la carrera
function validarEdad() {
    const fecha = document.getElementById('fecha_nacimiento').value;
    const carrera = document.getElementById('carrera').value;
    if (!fecha || !carrera) return true;

    const hoy = new Date();
    const nac = new Date(fecha);
    let edad = hoy.getFullYear() - nac.getFullYear();
    if (hoy.getMonth() < nac.getMonth() || (hoy.getMonth() === nac.getMonth() && hoy.getDate() < nac.getDate())) edad--;

    let error = document.getElementById('edad-error');
    if (!error) {
        error = document.createElement('div');
        error.id = 'edad-error';
        error.style.color = 'red';
        error.style.fontSize = '0.9em';
        error.style.marginTop = '0.3rem';
        document.getElementById('fecha_nacimiento').parentNode.appendChild(error);
    }

    if (carrera === 'Kids' && edad > 12) {
        error.textContent = 'Kids es solo para menores de 13 años';
        error.style.display = 'block';
        return false;
    }
    if (carrera !== 'Kids' && edad < 13) {
        error.textContent = 'Carreras adultas son para mayores de 12 años';
        error.style.display = 'block';
        return false;
    }
    error.style.display = 'none';
    return true;
}

// Envía los datos al servidor
async function enviarFormulario(e) {
    e.preventDefault();
    if (!validarEdad()) return;

    const formData = new FormData(document.getElementById('formulario'));
    const data = Object.fromEntries(formData);

    const loading = document.getElementById('loading');
    const qrContainer = document.getElementById('qr-container');
    loading.classList.remove('hidden');
    qrContainer.classList.add('hidden');

    try {
        const res = await fetch('../api/inscripcion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        // ✅ Verifica que la respuesta HTTP sea exitosa
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }

        const result = await res.json();

        if (result.success) {
            mostrarQR(result.qr_data, result.numero_corredor);
            mostrarMensaje('¡Inscripción exitosa!', 'success');
        } else {
            mostrarMensaje(result.message || 'Error desconocido', 'error');
        }
    } catch (err) {
        console.error('Error detallado:', err); // ← Verás el error real en la consola
        mostrarMensaje('Error de conexión', 'error');
    } finally {
        loading.classList.add('hidden');
    }
}

// Muestra el QR en la página
function mostrarQR(datos, numero) {
    document.getElementById('numero-corredor').textContent = numero;
    const qrContainer = document.getElementById('qr');
    qrContainer.innerHTML = '';
    
    // ✅ Verifica que QRCode esté cargado
    if (typeof QRCode === 'undefined') {
        qrContainer.innerHTML = '<p style="color:red;">Error: librería QR no cargada.</p>';
        return;
    }

    new QRCode(qrContainer, {
        text: datos,
        width: 200,
        height: 200,
        correctLevel: QRCode.CorrectLevel.H
    });
    document.getElementById('qr-container').classList.remove('hidden');
}

// Muestra mensajes temporales (éxito/error)
function mostrarMensaje(texto, tipo = 'info') {
    let msg = document.getElementById('mensaje-global');
    if (!msg) {
        msg = document.createElement('div');
        msg.id = 'mensaje-global';
        msg.style.cssText = `
            position: fixed; top: 20px; right: 20px; padding: 15px 20px;
            border-radius: 5px; color: white; z-index: 1000; max-width: 300px;
            font-family: Arial, sans-serif; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        document.body.appendChild(msg);
    }
    msg.style.backgroundColor = 
        tipo === 'success' ? '#4caf50' : 
        tipo === 'error' ? '#f44336' : '#2196f3';
    msg.textContent = texto;
    msg.style.display = 'block';

    setTimeout(() => {
        msg.style.display = 'none';
    }, 5000);
}

// Agrega el evento al calendario de Google
window.agregarACalendario = function() {
    const fecha = '20260308T073000'; // Formato básico para Google Calendar
    const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=Maratón Ituzaingó 2026&dates=${fecha}&details=13° Maratón "Corremos Por Más Derechos y Más Igualdad"&location=Plaza 20 de febrero, Las Heras y Zufriategui, Ituzaingó`;
    window.open(url, '_blank');
};