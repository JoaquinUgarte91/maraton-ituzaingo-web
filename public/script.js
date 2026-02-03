// ========================================
// MARATÓN ITUZAINGÓ 2026 - SCRIPT COMPLETO
// ========================================

// Se ejecuta cuando la página termina de cargar
document.addEventListener('DOMContentLoaded', () => {
    inicializarFormulario();
    inicializarPestanasRecorridos();
    inicializarBotonScroll();
});

// ========================================
// FORMULARIO DE INSCRIPCIÓN
// ========================================

// Configura el formulario
function inicializarFormulario() {
    const carreraSelect = document.getElementById('carrera');
    if (carreraSelect) {
        carreraSelect.addEventListener('change', (e) => {
            actualizarTalles(e.target.value);
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

// Muestra talles según si es Kids o adulto
function actualizarTalles(carrera) {
    const select = document.getElementById('talle_remera');
    if (!select) return;

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
    const fechaInput = document.getElementById('fecha_nacimiento');
    const carreraSelect = document.getElementById('carrera');
    
    if (!fechaInput || !carreraSelect) return true;
    
    const fecha = fechaInput.value;
    const carrera = carreraSelect.value;
    
    if (!fecha || !carrera) return true;

    const hoy = new Date();
    const nac = new Date(fecha);
    let edad = hoy.getFullYear() - nac.getFullYear();
    if (hoy.getMonth() < nac.getMonth() || (hoy.getMonth() === nac.getMonth() && hoy.getDate() < nac.getDate())) edad--;

    let error = document.getElementById('edad-error');
    if (!error) {
        error = document.createElement('div');
        error.id = 'edad-error';
        error.style.cssText = `
            color: red;
            font-size: 0.9em;
            margin-top: 0.3rem;
            font-weight: bold;
        `;
        fechaInput.parentNode.appendChild(error);
    }

    if (carrera === 'Kids' && edad > 12) {
        error.textContent = '⚠️ Kids es solo para menores de 13 años';
        error.style.display = 'block';
        return false;
    }
    if (carrera !== 'Kids' && edad < 13) {
        error.textContent = '⚠️ Carreras adultas son para mayores de 12 años';
        error.style.display = 'block';
        return false;
    }
    error.style.display = 'none';
    return true;
}

// ========================================
// CAPTCHA - VALIDACIÓN ANTES DE ENVIAR
// ========================================

function validarCaptcha() {
    const captchaResponse = grecaptcha.getResponse();
    const captchaError = document.getElementById('captcha-error');
    
    if (!captchaResponse || captchaResponse.length === 0) {
        if (captchaError) {
            captchaError.textContent = '⚠️ Por favor, verifica que no eres un robot';
            captchaError.style.display = 'block';
        }
        mostrarMensaje('Completa el CAPTCHA', 'warning');
        return false;
    } else {
        if (captchaError) captchaError.style.display = 'none';
    }
    
    return true;
}

// Envía los datos al servidor
async function enviarFormulario(e) {
    e.preventDefault();
    
    // Validar edad primero
    if (!validarEdad()) return;
    
    // Validar CAPTCHA
    if (!validarCaptcha()) return;

    const formData = new FormData(document.getElementById('formulario'));
    const data = Object.fromEntries(formData);

    // Agregar el token del CAPTCHA a los datos
    data.captcha_token = grecaptcha.getResponse();

    const loading = document.getElementById('loading');
    const qrContainer = document.getElementById('qr-container');
    
    if (loading) loading.classList.remove('hidden');
    if (qrContainer) qrContainer.classList.add('hidden');

    try {
        const res = await fetch('../api/inscripcion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }

        const result = await res.json();

        if (result.success) {
            mostrarQR(result.qr_token, result.numero_corredor);
            mostrarMensaje('¡Inscripción exitosa!', 'success');
            
            // ✅ Resetear el CAPTCHA después de enviar
            grecaptcha.reset();
        } else {
            mostrarMensaje(result.message || 'Error desconocido', 'error');
            
            // ✅ Resetear el CAPTCHA si hay error
            grecaptcha.reset();
        }
    } catch (err) {
        console.error('Error detallado:', err);
        mostrarMensaje('Error de conexión con el servidor', 'error');
        
        // ✅ Resetear el CAPTCHA si hay error de conexión
        grecaptcha.reset();
    } finally {
        if (loading) loading.classList.add('hidden');
    }
}

// Muestra el QR en la página
function mostrarQR(datos, numero) {
    const numeroCorredorEl = document.getElementById('numero-corredor');
    const qrContainerEl = document.getElementById('qr');
    
    if (numeroCorredorEl) numeroCorredorEl.textContent = numero;
    if (qrContainerEl) qrContainerEl.innerHTML = '';
    
    // Verifica que QRCode esté cargado
    if (typeof QRCode === 'undefined') {
        if (qrContainerEl) {
            qrContainerEl.innerHTML = '<p style="color:red; font-weight:bold;">Error: librería QR no cargada.</p>';
        }
        return;
    }

    if (qrContainerEl) {
        new QRCode(qrContainerEl, {
            text: datos,
            width: 200,
            height: 200,
            correctLevel: QRCode.CorrectLevel.H
        });
    }
    
    const qrContainer = document.getElementById('qr-container');
    if (qrContainer) {
        qrContainer.classList.remove('hidden');
        
        // Mostrar información adicional después de unos segundos
        setTimeout(() => {
            const infoAdicional = qrContainer.querySelector('.info-adicional');
            const downloadBtn = document.getElementById('download-btn');
            if (infoAdicional) infoAdicional.style.display = 'block';
            if (downloadBtn) downloadBtn.style.display = 'inline-block';
        }, 1000);
    }
}

// Muestra mensajes temporales (éxito/error)
function mostrarMensaje(texto, tipo = 'info') {
    let msg = document.getElementById('mensaje-global');
    if (!msg) {
        msg = document.createElement('div');
        msg.id = 'mensaje-global';
        msg.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            background-color: #2196f3;
            z-index: 10000;
            max-width: 350px;
            font-family: Arial, sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
        `;
        document.body.appendChild(msg);
    }
    
    // Estilos según el tipo
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

// ========================================
// PESTAÑAS DE RECORRIDOS (10K, 3K, 1K)
// ========================================

function inicializarPestanasRecorridos() {
    const tabButtons = document.querySelectorAll('.tab-button');
    
    if (tabButtons.length === 0) return; // Si no hay botones, salir

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // 1. Remover clase "active" de todos los botones
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // 2. Agregar clase "active" al botón clickeado
            button.classList.add('active');
            
            // 3. Ocultar todos los paneles de contenido
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // 4. Mostrar el panel correspondiente
            const targetId = button.getAttribute('data-tab');
            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
}

// ========================================
// BOTÓN DE SCROLL HACIA ARRIBA
// ========================================

function inicializarBotonScroll() {
    const btnTop = document.getElementById('btn-top');
    if (!btnTop) return;

    // Mostrar/ocultar botón según scroll
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            btnTop.style.display = 'block';
        } else {
            btnTop.style.display = 'none';
        }
    });

    // Scroll suave al hacer clic
    btnTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ========================================
// AGREGAR AL CALENDARIO DE GOOGLE
// ========================================

window.agregarACalendario = function() {
    const fecha = '20260308T073000'; // 8 de marzo de 2026, 7:30 AM
    const titulo = '13° Maratón "Corremos Por Más Derechos y Más Igualdad"';
    const detalles = '13° Edición Maratón Ituzaingó 2026 - Corremos por más derechos y más igualdad';
    const ubicacion = 'Plaza 20 de febrero, Las Heras y Zufriategui, Ituzaingó';
    
    const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(titulo)}&dates=${fecha}/${fecha}&details=${encodeURIComponent(detalles)}&location=${encodeURIComponent(ubicacion)}`;
    
    window.open(url, '_blank', 'width=600,height=600');
};

// ========================================
// ANIMACIONES CSS (para mensajes)
// ========================================

// Inyectar estilos CSS para animaciones si no existen
(function() {
    const styleId = 'animaciones-mensajes';
    if (document.getElementById(styleId)) return;

    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
})();

// ========================================
// VALIDACIÓN EN TIEMPO REAL DE FORMULARIO
// ========================================

// Validar DNI mientras se escribe
document.addEventListener('DOMContentLoaded', () => {
    const dniInput = document.getElementById('dni');
    if (dniInput) {
        dniInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 8) {
                this.value = this.value.slice(0, 8);
            }
        });
    }

    // Validar teléfono de emergencia
    const telefonoInput = document.getElementById('telefono_emergencia');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9+()-]/g, '');
        });
    }

    // Validar número de afiliado
    const afiliadoInput = document.getElementById('numero_afiliado');
    if (afiliadoInput) {
        afiliadoInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});