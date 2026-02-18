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
// NUEVA FUNCIÓN: MENÚ HAMBURGUESA
// ========================================
function toggleMenu() {
    const nav = document.getElementById('nav-menu');
    nav.classList.toggle('active');
}

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

async function actualizarTalles(carrera) {
    const select = document.getElementById('talle_remera');
    if (!select) return;

    // Buscamos el contenedor padre (.form-group) para ocultar también el Label
    const contenedorTalle = select.closest('.form-group');

    // Estado de carga
    select.disabled = true;

    try {
        // Consultamos al stock en tiempo real
        const response = await fetch('../api/stock.php?v=' + new Date().getTime());
        const data = await response.json();

        if (!data.success) throw new Error("Error al consultar stock");

        // Obtenemos el stock (si es undefined asumimos 0 por seguridad)
        const stockDisponible = data.stock[carrera] !== undefined ? data.stock[carrera] : 0;

        // Limpiamos opciones anteriores
        select.innerHTML = '';

        // === LÓGICA PRINCIPAL ===
        if (stockDisponible > 0) {
            // CASO A: HAY STOCK
            // 1. Mostramos el campo
            if (contenedorTalle) contenedorTalle.style.display = 'block';
            
            // 2. Lo hacemos obligatorio y habilitamos
            select.required = true;
            select.disabled = false;
            
            // 3. Generamos las opciones
            select.innerHTML = '<option value="">-- Seleccioná un talle --</option>';
            
            let opciones = [];
            let prefijo = "";

            if (carrera === 'Kids') {
                opciones = ['8', '12'];
                prefijo = 'Niños/as';
            } else {
                opciones = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
                prefijo = 'Adulto/a';
            }

            opciones.forEach(t => {
                select.innerHTML += `<option value="${prefijo} ${t}">${prefijo} Talle ${t}</option>`;
            });

        } else {
            // CASO B: NO HAY STOCK (CUPOS AGOTADOS)
            // 1. Ocultamos todo el campo (Label + Select)
            if (contenedorTalle) contenedorTalle.style.display = 'none';

            // 2. Le quitamos el 'required'
            select.required = false;
            select.disabled = false; // Habilitado para enviar valor oculto

            // 3. Asignamos un valor por defecto oculto
            select.innerHTML = '<option value="Sujeto a disponibilidad" selected>Sujeto a disponibilidad</option>';
        }

    } catch (error) {
        console.error("Error stock:", error);
        // Fallback: Si falla, mostramos el campo genérico
        if (contenedorTalle) contenedorTalle.style.display = 'block';
        select.disabled = false;
        select.innerHTML = '<option value="Sujeto a disponibilidad">Talle sujeto a disponibilidad</option>';
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
// 3. ENVÍO DE DATOS
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
        const response = await fetch('../api/inscripcion.php', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (response.status === 404) {
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
            // Pasamos si tiene remera o no a la función mostrarQR
            mostrarQR(result.qr_image, result.numero_corredor, result.carrera, result.remera_asignada);
            
            document.getElementById('formulario').reset();
            grecaptcha.reset();
            
            // === CAMBIO 1: MENSAJE FLOTANTE DE ÉXITO MÁS CLARO ===
            mostrarMensaje('¡Inscripción exitosa! Revisá tu casilla de SPAM o Correo No Deseado.', 'success');
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

function mostrarQR(base64Image, numero, carrera, remeraAsignada) {
    const qrDiv = document.getElementById('qr');
    const numeroSpan = document.getElementById('numero-corredor');
    const container = document.getElementById('qr-container');
    
    if (numeroSpan) numeroSpan.textContent = numero;
    if (qrDiv) {
        qrDiv.innerHTML = `<img src="${base64Image}" style="width:200px; height:200px;">`;
    }
    
    // === ALERTA SI NO HAY STOCK DE REMERA ===
    // Borramos alertas viejas por si acaso
    const alertaPrevia = document.getElementById('alerta-remera');
    if (alertaPrevia) alertaPrevia.remove();

    if (!remeraAsignada) {
        const alertaDiv = document.createElement('div');
        alertaDiv.id = 'alerta-remera';
        alertaDiv.style.cssText = `
            background-color: #ffebee; 
            color: #c62828; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 20px; 
            border: 1px solid #ef9a9a;
            font-weight: bold;
            text-align: center;
        `;
        alertaDiv.innerHTML = `
            ⚠️ SU INSCRIPCIÓN HA SUPERADO EL NÚMERO LÍMITE DE KITS.<br>
            Cualquier consulta comuníquese al <a href="tel:46240898" style="color:#c62828; text-decoration:underline;">4624-0898</a>.
        `;
        
        // Insertamos la alerta después del QR
        if (qrDiv && qrDiv.parentNode) {
            qrDiv.parentNode.insertBefore(alertaDiv, qrDiv.nextSibling);
        }
    }
    
    // === CAMBIO 2: AVISO DE SPAM FIJO ===
    // Borramos aviso anterior si existe
    const avisoSpamPrevio = document.getElementById('aviso-spam');
    if (avisoSpamPrevio) avisoSpamPrevio.remove();

    const avisoSpam = document.createElement('div');
    avisoSpam.id = 'aviso-spam';
    avisoSpam.style.cssText = `
        margin-top: 15px;
        background-color: #fff3e0;
        color: #e65100;
        padding: 10px;
        border-radius: 8px;
        font-weight: bold;
        border: 1px solid #ffe0b2;
        text-align: center;
        font-size: 0.95rem;
    `;
    avisoSpam.innerHTML = `<i class="fas fa-envelope-open-text"></i> ATENCIÓN: El mail de confirmación puede llegar a tu carpeta de SPAM o "Correo No Deseado".`;

    // Lo insertamos antes de la sección de "Información Importante"
    const infoAdicional = document.querySelector('.info-adicional');
    if (infoAdicional && infoAdicional.parentNode) {
        infoAdicional.parentNode.insertBefore(avisoSpam, infoAdicional);
    }
    // ========================================

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

            // Scroll suave para que el usuario vea la alerta
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });

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
// 5. FUNCIÓN AGREGAR AL CALENDARIO (NATIVA/GOOGLE)
// ========================================

function agregarACalendario() {
    // 1. Definimos los datos del evento
    const titulo = "13° Maratón Ituzaingó 2026";
    const descripcion = "Maratón Corremos Por Más Derechos y Más Igualdad. ¡No te olvides tu QR!";
    const ubicacion = "Ituzaingó, Buenos Aires";
    
    // Fechas en formato YYYYMMDDTHHmmss
    const inicio = "20260308T080000"; 
    const fin = "20260308T120000";      

    // 2. Detectamos si el usuario está en un iPhone/iPad
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    if (isIOS) {
        // === MODO IPHONE (iOS) ===
        const calendarioTexto = [
            "BEGIN:VCALENDAR",
            "VERSION:2.0",
            "PRODID:-//Municipalidad Ituzaingo//Maraton//ES",
            "BEGIN:VEVENT",
            "UID:" + Date.now() + "@maratonituzaingo.gov.ar",
            "DTSTAMP:" + inicio + "Z", 
            "DTSTART:" + inicio,
            "DTEND:" + fin,
            "SUMMARY:" + titulo,
            "DESCRIPTION:" + descripcion,
            "LOCATION:" + ubicacion,
            "END:VEVENT",
            "END:VCALENDAR"
        ].join("\n");

        const blob = new Blob([calendarioTexto], { type: "text/calendar;charset=utf-8" });
        const url = window.URL.createObjectURL(blob);
        
        // TRUCO: Navegar al archivo hace que iOS abra el gestor de calendario
        window.location.assign(url);
        
        // Limpieza (opcional, con delay para dar tiempo a iOS)
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
        }, 2000);

    } else {
        // === MODO ANDROID Y PC (Google Calendar) ===
        const gTitle = encodeURIComponent(titulo);
        const gDesc = encodeURIComponent(descripcion);
        const gLoc = encodeURIComponent(ubicacion);
        
        const googleUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${gTitle}&dates=${inicio}/${fin}&details=${gDesc}&location=${gLoc}&sf=true&output=xml`;
        
        // Abrimos en una pestaña nueva
        window.open(googleUrl, '_blank');
    }
}