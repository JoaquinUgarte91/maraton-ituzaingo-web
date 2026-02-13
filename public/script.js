// ========================================
// MARATÓN ITUZAINGÓ 2026 - SCRIPT FINAL
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    inicializarFormulario();
    inicializarPestanasRecorridos();
    inicializarBotonScroll();
    inicializarValidacionesRealTime();
});

// ========================================
// 1. FUNCIÓN DE DESCARGA MANUAL
// ========================================
window.descargarManual = function() {
    const qrDiv = document.getElementById('qr');
    const img = qrDiv ? qrDiv.querySelector('img') : null;

    if (img && img.src) {
        const link = document.createElement('a');
        link.href = img.src;
        const num = document.getElementById('numero-corredor')?.textContent || '2026';
        link.download = `QR_Maraton_Ituzaingo_${num}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } else {
        alert("El QR aún no se generó. Esperá un segundo y reintentá.");
    }
};

// ========================================
// 2. LÓGICA DEL FORMULARIO
// ========================================

function inicializarFormulario() {
    const formulario = document.getElementById('formulario');
    if (formulario) formulario.addEventListener('submit', enviarFormulario);

    const carreraSelect = document.getElementById('carrera');
    if (carreraSelect) {
        carreraSelect.addEventListener('change', (e) => {
            actualizarTalles(e.target.value);
            validarEdad();
        });
    }
}

function actualizarTalles(carrera) {
    const select = document.getElementById('talle_remera');
    if (!select) return;
    select.innerHTML = '<option value="">-- Seleccioná un talle --</option>';
    let opciones = (carrera === 'Kids') ? ['6', '8', '10', '12', '14'] : ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
    let prefijo = (carrera === 'Kids') ? 'Niño' : 'Adulto';
    opciones.forEach(t => select.innerHTML += `<option value="${prefijo} ${t}">${prefijo} ${t}</option>`);
}

function validarEdad() {
    const fechaInput = document.getElementById('fecha_nacimiento');
    const carreraSelect = document.getElementById('carrera');
    if (!fechaInput || !carreraSelect || !fechaInput.value || !carreraSelect.value) return true;

    const hoy = new Date();
    const nac = new Date(fechaInput.value);
    let edad = hoy.getFullYear() - nac.getFullYear();
    if (hoy.getMonth() < nac.getMonth() || (hoy.getMonth() === nac.getMonth() && hoy.getDate() < nac.getDate())) edad--;

    let errorDiv = document.getElementById('edad-error-msg');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'edad-error-msg';
        errorDiv.style.cssText = "color: red; font-size: 0.9em; margin-top: 5px; font-weight: bold;";
        fechaInput.parentNode.appendChild(errorDiv);
    }
    
    if (carreraSelect.value === 'Kids' && edad > 12) {
        errorDiv.textContent = '⚠️ Kids es solo para menores de 13 años';
        errorDiv.style.display = 'block';
        return false;
    }
    if (carreraSelect.value !== 'Kids' && edad < 13) {
        errorDiv.textContent = '⚠️ Carreras adultas son para mayores de 12 años';
        errorDiv.style.display = 'block';
        return false;
    }
    errorDiv.style.display = 'none';
    return true;
}

// ========================================
// 3. ENVÍO DE DATOS (REVISAR SINGULAR/PLURAL)
// ========================================

async function enviarFormulario(e) {
    e.preventDefault();

    if (!validarEdad()) {
        mostrarMensaje('Verificá la edad y la carrera', 'warning');
        return;
    }

    const captchaResponse = grecaptcha.getResponse();
    if (!captchaResponse && window.location.hostname !== 'localhost') {
        mostrarMensaje('Completa el CAPTCHA', 'warning');
        return;
    }

    const loading = document.getElementById('loading');
    const qrContainer = document.getElementById('qr-container');
    const btnSubmit = document.getElementById('submit-btn');
    
    if (loading) loading.classList.remove('hidden');
    if (qrContainer) qrContainer.classList.add('hidden');
    btnSubmit.disabled = true;

    const formData = new FormData(document.getElementById('formulario'));
    const data = Object.fromEntries(formData);
    data.captcha_token = captchaResponse || 'bypass';

    try {
        // AQUÍ ESTABA EL ERROR: Asegurate de que el archivo sea 'inscripcion.php'
        const response = await fetch('../api/inscripcion.php', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (response.status === 404) {
            // Este mensaje ahora coincide con la realidad para no confundirte
            throw new Error("Error 404: El servidor no encuentra '../api/inscripcion.php'.");
        }

        const textResponse = await response.text(); 
        let result;
        try {
            result = JSON.parse(textResponse);
        } catch (err) {
            throw new Error("El servidor no devolvió un JSON válido.");
        }

        if (response.ok && result.success) {
            mostrarQR(result.qr_image, result.numero_corredor, result.carrera);
            document.getElementById('formulario').reset();
            grecaptcha.reset();
            mostrarMensaje('¡Inscripción exitosa!', 'success');
        } else {
            mostrarMensaje(result.message || 'Error desconocido', 'error');
            grecaptcha.reset();
        }

    } catch (error) {
        console.error('Error:', error);
        mostrarMensaje(error.message || 'Error de conexión', 'error');
        grecaptcha.reset();
    } finally {
        if (loading) loading.classList.add('hidden');
        btnSubmit.disabled = false;
    }
}

// ========================================
// 4. UTILIDADES VISUALES
// ========================================

function mostrarQR(base64Image, numero, carrera) {
    const qrDiv = document.getElementById('qr');
    const numeroSpan = document.getElementById('numero-corredor');
    const container = document.getElementById('qr-container');
    
    if (numeroSpan) numeroSpan.textContent = numero;
    if (qrDiv) {
        qrDiv.innerHTML = `<img src="${base64Image}" style="width:200px; height:200px;">`;
    }
    
    if (container) {
        container.classList.remove('hidden');
        setTimeout(() => {
            document.querySelector('.info-adicional').style.display = 'block';
            document.getElementById('download-btn').style.display = 'inline-block';
            
            const btnDeslinde = document.getElementById('btn-deslinde');
            const btnAutorizacion = document.getElementById('btn-autorizacion');
            
            if (carrera === 'Kids') {
                if (btnDeslinde) btnDeslinde.style.display = 'none';
                if (btnAutorizacion) btnAutorizacion.style.display = 'inline-block';
            } else {
                if (btnDeslinde) btnDeslinde.style.display = 'inline-block';
                if (btnAutorizacion) btnAutorizacion.style.display = 'none';
            }
        }, 500);
    }
}

function mostrarMensaje(texto, tipo = 'info') {
    let msg = document.getElementById('mensaje-global');
    if (!msg) {
        msg = document.createElement('div');
        msg.id = 'mensaje-global';
        msg.style.cssText = `position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 8px; color: white; z-index: 10000; max-width: 350px; font-family: Arial, sans-serif; box-shadow: 0 4px 12px rgba(0,0,0,0.2);`;
        document.body.appendChild(msg);
    }
    const estilos = { success: '#4caf50', error: '#f44336', info: '#2196f3', warning: '#ff9800' };
    msg.style.backgroundColor = estilos[tipo] || estilos.info;
    msg.textContent = texto;
    msg.style.display = 'block';
    setTimeout(() => { msg.style.display = 'none'; }, 5000);
}

function inicializarPestanasRecorridos() {
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            const targetId = button.getAttribute('data-tab');
            document.getElementById(targetId)?.classList.add('active');
        });
    });
}

function inicializarBotonScroll() {
    const btnTop = document.getElementById('btn-top');
    if (!btnTop) return;
    window.addEventListener('scroll', () => {
        btnTop.style.display = (window.pageYOffset > 300) ? 'block' : 'none';
    });
    btnTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

function inicializarValidacionesRealTime() {
    const dniInput = document.getElementById('dni');
    if (dniInput) dniInput.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8); });
}

// ========================================
// 5. FUNCIÓN AGREGAR AL CALENDARIO

function agregarACalendario() {
    const titulo = "13° Maratón Ituzaingó 2026";
    const inicio = "20260308T080000"; 
    const fin = "20260308T120000";    
    const descripcion = "Maratón Corremos Por Más Derechos y Más Igualdad. ¡No te olvides tu QR!";
    const ubicacion = "Ituzaingó, Buenos Aires";

    const calendarioTexto = [
        "BEGIN:VCALENDAR",
        "VERSION:2.0",
        "METHOD:PUBLISH",
        "BEGIN:VEVENT",
        "CLASS:PUBLIC",
        `DTSTART:${inicio}`,
        `DTEND:${fin}`,
        `SUMMARY:${titulo}`,
        `DESCRIPTION:${descripcion}`,
        `LOCATION:${ubicacion}`,
        "TRANSP:TRANSPARENT",
        "END:VEVENT",
        "END:VCALENDAR"
    ].join("\n");

    // Creamos un Blob (archivo en memoria) con el tipo de contenido específico
    const blob = new Blob([calendarioTexto], { type: "text/calendar;charset=utf-8" });
    const url = window.URL.createObjectURL(blob);
    
    // Creamos el link de descarga
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", "maraton-ituzaingo-2026.ics");
    
    // Lo agregamos, lo clickeamos y lo borramos
    document.body.appendChild(link);
    link.click();
    
    // Limpieza de memoria
    setTimeout(() => {
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }, 100);
}