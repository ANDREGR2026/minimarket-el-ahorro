document.querySelectorAll('input[type="color"]').forEach(input => {
    input.addEventListener('input', () => { input.nextElementSibling.textContent = input.value; });
});
document.getElementById('colores-originales').addEventListener('click', () => {
    Object.entries({color_principal: '#257aeb', color_oscuro: '#0f172b'}).forEach(([id, value]) => {
        const input = document.getElementById(id);
        input.value = value;
        input.dispatchEvent(new Event('input'));
    });
});
let logoUrl;
document.getElementById('logo').addEventListener('change', event => {
    if (logoUrl) URL.revokeObjectURL(logoUrl);
    const file = event.target.files[0];
    const nombreEl = document.getElementById('logo-nombre');
    if (file) {
        nombreEl.textContent = file.name;
        if (['image/png', 'image/jpeg', 'image/webp'].includes(file.type) && file.size <= 2097152) {
            logoUrl = URL.createObjectURL(file);
            document.getElementById('logo-preview').src = logoUrl;
        }
    } else {
        nombreEl.textContent = 'Ningún archivo seleccionado';
    }
});
