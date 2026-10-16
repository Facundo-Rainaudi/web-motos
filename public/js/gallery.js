// public/js/gallery.js — carrusel de fotos del detalle (RF-5, H5).
//
// Muestra una foto a la vez con botones ◀ ▶ (ida y vuelta: desde la última,
// la siguiente vuelve a la primera) y un contador "Foto N de M".
// Degradación sin JS: el servidor deja todas las fotos visibles apiladas;
// acá solo se agrega la clase .js al contenedor, que es la que el CSS usa
// para ocultar las inactivas y mostrar los botones. Con una sola foto no
// hace nada.
(function () {
    'use strict';

    var gallery = document.querySelector('.gallery');
    if (!gallery) {
        return; // Página sin galería.
    }

    var photos = gallery.querySelectorAll('.gallery-photo');
    if (photos.length < 2) {
        return; // Una foto: no hay nada que pasar, se queda tal cual.
    }

    var current = 0;
    var counter = gallery.querySelector('[data-gallery-current]');

    // Muestra la foto del índice (con ida y vuelta) y actualiza el contador.
    function show(index) {
        current = (index + photos.length) % photos.length;

        for (var i = 0; i < photos.length; i++) {
            photos[i].classList.toggle('is-active', i === current);
        }

        if (counter) {
            counter.textContent = String(current + 1);
        }
    }

    // Con esta clase el CSS oculta las fotos inactivas y habilita los botones.
    gallery.classList.add('js');
    show(0);

    var prev = gallery.querySelector('.gallery-prev');
    var next = gallery.querySelector('.gallery-next');

    if (prev) {
        prev.addEventListener('click', function () {
            show(current - 1);
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            show(current + 1);
        });
    }
})();
