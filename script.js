document.getElementById("formulario").addEventListener("submit", function (e) {
    e.preventDefault();

    // Obtener los valores del formulario
    const nombre = document.getElementById("nombre").value;
    const dni = document.getElementById("dni").value;
    const email = document.getElementById("email").value;
    const carrera = document.getElementById("carrera").value;

    // Enviar los datos al servidor usando Fetch API
    fetch('procesar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nombre=${encodeURIComponent(nombre)}&dni=${encodeURIComponent(dni)}&email=${encodeURIComponent(email)}&carrera=${encodeURIComponent(carrera)}`
    })
    .then(response => response.json())
    .then(data => {
        // Generar el QR con los datos recibidos
        const qrDiv = document.getElementById("qr");
        qrDiv.innerHTML = ""; // Limpiar el contenedor

        new QRCode(qrDiv, {
            text: data.qr_data,
            width: 200,
            height: 200,
        });

        // Mostrar los datos en texto debajo del QR
    
    })
    .catch(error => console.error('Error:', error));
});