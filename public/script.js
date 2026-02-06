// ========================================
// MARATÓN ITUZAINGÓ 2026 - SCRIPT COMPLETO
// ========================================

// Detectar ruta base si es necesaria, pero usaremos ruta relativa ../api
const BASE_URL = window.location.pathname.split('/public/')[0].replace(/\/$/, '');

document.addEventListener('DOMContentLoaded', () => {
    inicializarFormulario();
    inicializarPestanasRecorridos();
    inicializarBotonScroll();
    inicializarValidacionesRealTime();
});

// ========================================
// 1. LÓGICA DEL FORMULARIO
// ========================================

function inicializarFormulario() {
    const carreraSelect = document.getElementById('carrera');
    if (carreraSelect) {
        carreraSelect.addEventListener('change', (e) => {
            actualizarTalles(e.target.value);
            validarEdad(); // Re-validar si cambia carrera
        });
    }

    const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
    if (fechaNacimientoInput) {
        fechaNacimientoInput.addEventListener('change', () => {
            validarEdad();
        });
    }

    const formulario = document.getElementById('formulario');
    if (formulario) {
        formulario.addEventListener('submit', enviarFormulario);
    }
}

function actualizarTalles(carrera) {
    const select = document.getElementById('talle_remera');
    if (!select) return;

    select.innerHTML = '<option value="">-- Seleccioná un talle --</option>';
    
    let opciones = [];
    if (carrera === 'Kids') {
        opciones = ['6', '8', '10', '12', '14'];
        opciones.forEach(t => select.innerHTML += `<option value="Niño ${t}">Niño ${t}</option>`);
    } else {
        opciones = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
        opciones.forEach(t => select.innerHTML += `<option value="Adulto ${t}">Adulto ${t}</option>`);
    }
}

function validarEdad() {
    const fechaInput = document.getElementById('fecha_nacimiento');
    const carreraSelect = document.getElementById('carrera');
    
    if (!fechaInput || !carreraSelect || !fechaInput.value || !carreraSelect.value) return true;

    const hoy = new Date();
    const nac = new Date(fechaInput.value);
    let edad = hoy.getFullYear() - nac.getFullYear();
    if (hoy.getMonth() < nac.getMonth() || (hoy.getMonth() === nac.getMonth() && hoy.getDate() < nac.getDate())) edad--;

    // Buscar o crear div de error
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
// 2. ENVÍO DE DATOS
// ========================================

async function enviarFormulario(e) {
    e.preventDefault();

    // Validaciones Cliente
    if (!validarEdad()) {
        mostrarMensaje('Por favor verifica la fecha de nacimiento y la carrera', 'warning');
        return;
    }

    const captchaResponse = grecaptcha.getResponse();
    const captchaError = document.getElementById('captcha-error');
    
    if (!captchaResponse || captchaResponse.length === 0) {
        if(captchaError) {
            captchaError.textContent = "⚠️ Por favor confirma que no eres un robot.";
            captchaError.style.display = 'block';
        }
        mostrarMensaje('Completa el CAPTCHA', 'warning');
        return;
    }
    if(captchaError) captchaError.style.display = 'none';

    // UI Loading
    const loading = document.getElementById('loading');
    const qrContainer = document.getElementById('qr-container');
    const btnSubmit = document.getElementById('submit-btn');
    
    if (loading) loading.classList.remove('hidden');
    if (qrContainer) qrContainer.classList.add('hidden');
    btnSubmit.disabled = true;

    const formData = new FormData(document.getElementById('formulario'));
    const data = Object.fromEntries(formData);
    data.captcha_token = captchaResponse;

    try {
        // ✅ RUTA API CORREGIDA: Salir de public y entrar a api
        const response = await fetch('../api/inscripcion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        // Intentar parsear JSON
        let result;
        const textResponse = await response.text(); 
        
        try {
            result = JSON.parse(textResponse);
        } catch (err) {
            console.error("Respuesta no JSON:", textResponse);
            throw new Error("Error del servidor: Respuesta inválida (no JSON).");
        }

        if (response.ok && result.success) {
            // ÉXITO
            mostrarQR(result.qr_token, result.numero_corredor, result.carrera);
            document.getElementById('formulario').reset();
            grecaptcha.reset();
            mostrarMensaje('¡Inscripción exitosa!', 'success');
        } else {
            // ERROR CONTROLADO
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
// 3. UTILIDADES VISUALES (QR, Mensajes, Tabs)
// ========================================

function mostrarQR(token, numero, carrera) {
    const qrDiv = document.getElementById('qr');
    const numeroSpan = document.getElementById('numero-corredor');
    const container = document.getElementById('qr-container');
    
    if (numeroSpan) numeroSpan.textContent = numero;
    if (qrDiv) {
        qrDiv.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            new QRCode(qrDiv, {
                text: token,
                width: 200,
                height: 200,
                correctLevel: QRCode.CorrectLevel.H
            });
        } else {
            qrDiv.innerHTML = 'QR Generado (Librería no cargada)';
        }
    }
    
    if (container) {
        container.classList.remove('hidden');
        
        // Manejar botones de descarga condicionales
        setTimeout(() => {
            const infoAdicional = container.querySelector('.info-adicional');
            const downloadBtn = document.getElementById('download-btn');
            if (infoAdicional) infoAdicional.style.display = 'block';
            if (downloadBtn) downloadBtn.style.display = 'inline-block';
            
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

// TU FUNCIÓN ORIGINAL DE MENSAJES (Restaurada)
function mostrarMensaje(texto, tipo = 'info') {
    let msg = document.getElementById('mensaje-global');
    if (!msg) {
        msg = document.createElement('div');
        msg.id = 'mensaje-global';
        msg.style.cssText = `
            position: fixed; top: 20px; right: 20px; padding: 15px 20px;
            border-radius: 8px; color: white; z-index: 10000;
            max-width: 350px; font-family: Arial, sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2); animation: slideIn 0.3s ease;
        `;
        document.body.appendChild(msg);
    }
    
    const estilos = {
        success: '#4caf50',
        error: '#f44336',
        info: '#2196f3',
        warning: '#ff9800'
    };
    
    msg.style.backgroundColor = estilos[tipo] || estilos.info;
    msg.textContent = texto;
    msg.style.display = 'block';

    setTimeout(() => {
        msg.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            msg.style.display = 'none';
            msg.style.animation = '';
        }, 300);
    }, 5000);
}

function inicializarPestanasRecorridos() {
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons.length === 0) return;

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            const targetId = button.getAttribute('data-tab');
            const targetPane = document.getElementById(targetId);
            if (targetPane) targetPane.classList.add('active');
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
    if (dniInput) {
        dniInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8);
        });
    }
}

// Inyectar estilos de animación si no existen
(function() {
    if (document.getElementById('animaciones-mensajes')) return;
    const style = document.createElement('style');
    style.id = 'animaciones-mensajes';
    style.textContent = `
        @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
    `;
    document.head.appendChild(style);
})();