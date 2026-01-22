// Se ejecuta cuando la página termina de cargar
document.addEventListener('DOMContentLoaded', () => {
    inicializarFormulario();
    inicializarTabs();
    inicializarBotonScroll();
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
    
    // Inicializar talles si ya hay una carrera seleccionada
    const carreraSeleccionada = document.getElementById('carrera')?.value;
    if (carreraSeleccionada) {
        actualizarTalles(carreraSeleccionada);
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
    if (hoy.getMonth() < nac.getMonth() || 
        (hoy.getMonth() === nac.getMonth() && hoy.getDate() < nac.getDate())) {
        edad--;
    }

    let error = document.getElementById('edad-error');
    if (!error) {
        error = document.createElement('div');
        error.id = 'edad-error';
        error.style.cssText = `
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
            display: none;
        `;
        fechaInput.parentNode.appendChild(error);
    }

    if (carrera === 'Kids' && edad > 12) {
        error.textContent = 'Kids es solo para menores de 13 años';
        error.style.display = 'block';
        return false;
    }
    
    if (carrera !== 'Kids' && edad < 13) {
        error.textContent = 'Las carreras 3km y 10km son para mayores de 12 años';
        error.style.display = 'block';
        return false;
    }
    
    error.style.display = 'none';
    return true;
}

// Envía los datos al servidor
async function enviarFormulario(e) {
    e.preventDefault();
    
    // Validar edad antes de enviar
    if (!validarEdad()) {
        mostrarMensaje('Por favor corrige los errores de edad', 'error');
        return;
    }

    // Validar campos obligatorios
    const camposObligatorios = ['nombre', 'dni', 'email', 'carrera', 'talle_remera', 'cobertura_medica', 'numero_afiliado', 'telefono_emergencia'];
    const camposFaltantes = camposObligatorios.filter(campo => {
        const valor = document.getElementById(campo)?.value?.trim();
        return !valor;
    });

    if (camposFaltantes.length > 0) {
        mostrarMensaje(`Faltan campos obligatorios: ${camposFaltantes.join(', ')}`, 'error');
        return;
    }

    const formData = new FormData(document.getElementById('formulario'));
    const data = Object.fromEntries(formData);

    const loading = document.getElementById('loading');
    const qrContainer = document.getElementById('qr-container');
    
    if (loading) loading.classList.remove('hidden');
    if (qrContainer) qrContainer.classList.add('hidden');

    try {
        // ✅ Usar ruta correcta para el endpoint
        const baseUrl = window.location.origin + '/maraton-ituzaingo-web';
        const endpoint = baseUrl + '/api/inscripcion_create.php';
        
        console.log('🚀 Enviando a:', endpoint);

        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data),
            credentials: 'same-origin'
        });

        // ✅ Manejar errores HTTP
        if (!res.ok) {
            let errorData;
            try {
                errorData = await res.json();
            } catch (e) {
                throw new Error(`Error del servidor [${res.status}]: ${res.statusText}`);
            }
            throw new Error(errorData.message || `Error [${res.status}]: ${res.statusText}`);
        }

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await res.text();
            console.error('❌ Respuesta no es JSON:', textResponse.substring(0, 200));
            throw new Error('El servidor no devolvió una respuesta JSON válida');
        }

        const result = await res.json();

        if (result.success) {
            mostrarQR(result);
            mostrarMensaje('¡Inscripción exitosa!', 'success');
            
            // Limpiar formulario después de éxito
            document.getElementById('formulario').reset();
            
            // Desplazar hacia el QR
            if (qrContainer) {
                setTimeout(() => {
                    qrContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
        } else {
            throw new Error(result.message || 'Error desconocido en la inscripción');
        }
    } catch (err) {
        console.error('🚨 ERROR DETALLADO:', err);
        
        let mensajeError = err.message || 'No se pudo completar la inscripción';
        
        // Mensajes de error específicos
        if (mensajeError.includes('404') || mensajeError.includes('no encontrado')) {
            mensajeError = '❌ Archivo del servidor no encontrado. Contacta al administrador.';
        } else if (mensajeError.includes('NetworkError') || mensajeError.includes('Failed to fetch')) {
            mensajeError = '❌ Error de conexión. Verifica tu internet o intenta nuevamente.';
        }
        
        mostrarMensaje(mensajeError, 'error');
    } finally {
        if (loading) loading.classList.add('hidden');
    }
}

// ✅ FUNCIÓN CORREGIDA PARA MOSTRAR QR
function mostrarQR(data) {
    console.log('📊 Datos recibidos para mostrar QR:', data);
    
    // ✅ Verificar que el contenedor del QR exista
    const qrContainer = document.getElementById('qr-container');
    if (!qrContainer) {
        console.error('❌ Contenedor #qr-container no encontrado en el DOM');
        mostrarMensaje('Error: No se encontró el área para mostrar el QR', 'error');
        return;
    }
    
    // ✅ Verificar que el elemento para el QR exista
    const qrElement = document.getElementById('qr');
    if (!qrElement) {
        console.error('❌ Elemento #qr no encontrado en el DOM');
        mostrarMensaje('Error: No se encontró el elemento para el QR', 'error');
        return;
    }

    // ✅ Mostrar el contenedor principal
    qrContainer.classList.remove('hidden');
    qrContainer.style.display = 'block';
    
    // ✅ Establecer el número de corredor
    const numeroCorredor = document.getElementById('numero-corredor');
    if (numeroCorredor) {
        numeroCorredor.textContent = data.id || 'N/A';
    }

    // ✅ Limpiar el contenedor del QR
    qrElement.innerHTML = '';

    // ✅ Mostrar QR desde URL (método preferido)
    if (data.qr_url) {
        console.log('🖼️ Mostrando QR desde URL:', data.qr_url);
        
        const img = document.createElement('img');
        img.src = data.qr_url + '?_=' + new Date().getTime(); // Evitar caché
        img.alt = 'QR de inscripción - ID: ' + data.id;
        img.width = 200;
        img.height = 200;
        img.style.cssText = `
            display: block;
            margin: 0 auto 15px;
            border: 3px solid #0d6efd;
            border-radius: 12px;
            padding: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        img.onload = function() {
            console.log('✅ Imagen QR cargada exitosamente');
        };
        
        img.onerror = function(e) {
            console.error('❌ Error al cargar imagen QR:', e);
            qrElement.innerHTML = `
                <div style="text-align:center; padding:20px; color:#dc3545; background:#f8d7da; border-radius:8px; border:1px solid #f5c6cb;">
                    <p><strong>⚠️ Error al cargar el QR</strong></p>
                    <p>No se pudo cargar la imagen del QR. Tu inscripción fue exitosa.</p>
                    <p><strong>ID de inscripción:</strong> ${data.id}</p>
                    <button onclick="location.reload()" style="margin-top:10px; padding:6px 12px; background:#0d6efd; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">
                        <i class="fas fa-redo"></i> Recargar QR
                    </button>
                </div>
            `;
        };
        
        qrElement.appendChild(img);
        
        // ✅ Mostrar botón de descarga
        const downloadBtn = document.getElementById('download-btn');
        if (downloadBtn && data.qr_url) {
            downloadBtn.style.display = 'block';
            downloadBtn.onclick = function() {
                try {
                    const link = document.createElement('a');
                    link.href = data.qr_url;
                    link.download = `qr_inscripcion_${data.id}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    mostrarMensaje('QR descargado exitosamente', 'success');
                } catch (e) {
                    console.error('❌ Error al descargar QR:', e);
                    mostrarMensaje('Error al descargar el QR', 'error');
                }
            };
        }
        
        // ✅ Mostrar información adicional importante
        const infoAdicional = document.querySelector('.info-adicional');
        if (infoAdicional) {
            infoAdicional.style.display = 'block';
        }
        
        return;
    }

    // ✅ Mostrar QR desde base64 (fallback)
    if (data.qr_base64) {
        console.log('🖼️ Mostrando QR desde base64');
        
        const img = document.createElement('img');
        img.src = data.qr_base64;
        img.alt = 'QR de inscripción - ID: ' + data.id;
        img.width = 200;
        img.height = 200;
        img.style.cssText = `
            display: block;
            margin: 0 auto 15px;
            border: 3px solid #28a745;
            border-radius: 12px;
            padding: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        qrElement.appendChild(img);
        console.log('✅ QR base64 mostrado exitosamente');
        
        // ✅ Habilitar botón de descarga para base64
        const downloadBtn = document.getElementById('download-btn');
        if (downloadBtn) {
            downloadBtn.style.display = 'block';
            downloadBtn.textContent = 'Descargar QR';
            downloadBtn.onclick = function() {
                try {
                    // Convertir base64 a blob y descargar
                    const byteString = atob(data.qr_base64.split(',')[1]);
                    const mimeString = data.qr_base64.split(',')[0].split(':')[1].split(';')[0];
                    const ab = new ArrayBuffer(byteString.length);
                    const ia = new Uint8Array(ab);
                    for (let i = 0; i < byteString.length; i++) {
                        ia[i] = byteString.charCodeAt(i);
                    }
                    const blob = new Blob([ab], {type: mimeString});
                    const url = URL.createObjectURL(blob);
                    
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `qr_inscripcion_${data.id}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    
                    mostrarMensaje('QR descargado exitosamente', 'success');
                } catch (e) {
                    console.error('❌ Error al descargar QR base64:', e);
                    mostrarMensaje('Error al descargar el QR', 'error');
                }
            };
        }
        
        // ✅ Mostrar información adicional importante
        const infoAdicional = document.querySelector('.info-adicional');
        if (infoAdicional) {
            infoAdicional.style.display = 'block';
        }
        
        return;
    }

    // ✅ Fallback final - mostrar información básica
    console.warn('⚠️ No se encontró QR en la respuesta, mostrando información básica');
    
    qrElement.innerHTML = `
        <div style="text-align:center; padding:25px; background:#e7f3ff; border-radius:12px; border:2px solid #0d6efd;">
            <div style="font-size:48px; margin-bottom:15px;">ℹ️</div>
            <h3 style="color:#0d6efd; margin-bottom:10px;">¡Inscripción Exitosa!</h3>
            <p style="font-size:1.1em; margin-bottom:15px;"><strong>ID de participante:</strong> ${data.id}</p>
            <p style="background:white; padding:15px; border-radius:8px; margin:15px 0; font-family:monospace; font-size:1.2em; letter-spacing:2px;">
                ${data.kit_token?.substring(0, 12)}...
            </p>
            <p style="color:#6c757d; margin-bottom:20px;">
                Tu QR se generó correctamente. Si no se muestra, recarga la página o contacta al organizador.
            </p>
            <button onclick="location.reload()" style="padding:10px 25px; background:#0d6efd; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer; box-shadow:0 2px 5px rgba(13,110,253,0.3);">
                <i class="fas fa-redo"></i> Recargar Página
            </button>
        </div>
    `;
    
    // ✅ Mostrar información adicional importante
    const infoAdicional = document.querySelector('.info-adicional');
    if (infoAdicional) {
        infoAdicional.style.display = 'block';
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
            padding: 15px 25px;
            border-radius: 8px; 
            color: white; 
            z-index: 10000; 
            max-width: 350px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transform: translateX(400px);
            transition: transform 0.3s ease-out;
            font-weight: 500;
        `;
        document.body.appendChild(msg);
    }
    
    // Colores según el tipo
    let bgColor, icon;
    switch(tipo) {
        case 'success':
            bgColor = '#28a745';
            icon = '✅';
            break;
        case 'error':
            bgColor = '#dc3545';
            icon = '❌';
            break;
        case 'warning':
            bgColor = '#ffc107';
            icon = '⚠️';
            break;
        default:
            bgColor = '#17a2b8';
            icon = 'ℹ️';
    }
    
    msg.style.backgroundColor = bgColor;
    msg.innerHTML = `<span style="font-size: 1.2em; margin-right: 8px;">${icon}</span> ${texto}`;
    msg.style.display = 'block';
    
    // Animación de entrada
    msg.style.transform = 'translateX(0)';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        msg.style.transform = 'translateX(400px)';
        setTimeout(() => {
            msg.style.display = 'none';
        }, 300);
    }, 5000);
}

// Función para agregar al calendario
window.agregarACalendario = function() {
    const fechaInicio = '20260308T073000'; // 8 de marzo de 2026, 7:30 AM
    const fechaFin = '20260308T130000';    // 8 de marzo de 2026, 1:00 PM (aproximado)
    
    const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=13°%20Maratón%20Ituzaingó%202026&dates=${fechaInicio}/${fechaFin}&details=13°%20Maratón%20"Corremos%20Por%20Más%20Derechos%20y%20Más%20Igualdad"&location=Plaza%2020%20de%20febrero%2C%20Las%20Heras%20y%20Zufriategui%2C%20Ituzaingó&sf=true&output=xml`;
    
    const win = window.open(url, '_blank');
    if (!win) {
        mostrarMensaje('Por favor, permite las ventanas emergentes para agregar al calendario', 'warning');
    }
};

// Inicializar tabs de recorridos
function inicializarTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remover clase active de todos los botones y paneles
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Agregar clase active al botón y panel correspondiente
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
}

// Inicializar botón de scroll
function inicializarBotonScroll() {
    const btnTop = document.getElementById("btn-top");
    
    if (btnTop) {
        window.onscroll = function () {
            if (document.documentElement.scrollTop > 200) {
                btnTop.style.display = "block";
            } else {
                btnTop.style.display = "none";
            }
        };

        btnTop.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }
}