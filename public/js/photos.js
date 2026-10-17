// public/js/photos.js — mejoras del formulario de fotos de la etapa 4.
// Corre en Publicar y en Editar (lo incluye la página sólo cuando hay
// recuadros vacíos donde elegir fotos).
//
// 1) Señala que hay JavaScript: las piezas que sólo tienen sentido con él
//    (la pista "Cambiar foto" sobre la única foto, el archivo oculto del
//    reemplazo y su envío automático) se muestran o esconden según esta
//    clase en <html>, igual que el carrusel con la suya. Sin JS no se
//    carga este archivo y queda la versión manual.
// 2) Vista previa: al elegir un archivo en un recuadros vacío se muestra
//    la imagen en lugar del ícono, sin recargar la página. Es una mejora
//    de comodidad; el formulario funciona igual sin este script.

document.documentElement.classList.add('js');

// Vista previa de los recuadros vacíos (el ícono 📷 se reemplaza por la
// foto elegida en cuanto el navegador la termina de leer).
document.querySelectorAll('.photo-slot .photo-slot-input').forEach(bindSlotPreview);

// Reemplazar la única foto sin botón a la vista: al elegir el archivo en
// el recuadros (que es un <label> de este input) se "aprieta" el botón
// "Reemplazar" del mismo formulario y se envía el cambio de inmediato.
// Sin JS este listener no existe: el botón visible cumple ese rol.
document.querySelectorAll('.slot-swap-input').forEach(bindSwapSubmit);

function bindSlotPreview(input) {
    const slot = input.closest('.photo-slot');
    const preview = slot ? slot.querySelector('.photo-slot-preview') : null;

    if (!preview) {
        return; // Recuadro sin foto de vista previa: nada que hacer.
    }

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];

        if (!file) {
            return; // Se cerró el selector sin elegir nada.
        }

        const reader = new FileReader();

        // Cuando el navegador termina de leer el archivo se dibuja la
        // miniatura y se marca el recuadros como lleno (le pone un borde
        // verde, definido en style.css).
        reader.addEventListener('load', () => {
            preview.src = String(reader.result);
            preview.classList.add('is-visible');
            slot.classList.add('is-filled');
        });

        reader.readAsDataURL(file);
    });
}

function bindSwapSubmit(input) {
    input.addEventListener('change', () => {
        // El botón está escondido con CSS (clase .js): apretarlo
        // programáticamente envía el formulario igual que a mano.
        const button = input.form ? input.form.querySelector('[name="replace_photo_id"]') : null;

        if (button) {
            button.click();
        }
    });
}
