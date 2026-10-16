// public/js/feed.js — scroll infinito de la vitrina (RNF-3, H10).
//
// Observa un centinela al final de la lista: cuando aparece en pantalla pide
// la siguiente tanda a /api/bikes.php con el id de la última moto (paginado
// por clave, así nada se repite ni se pierde) y agrega las tarjetas al
// listado. Se usa textContent y createElement, nunca innerHTML: un título o
// una marca con etiquetas HTML se muestra como texto literal.
(function () {
    'use strict';

    var list = document.getElementById('bike-list');
    if (!list) {
        return; // Página sin vitrina (o vacía): no hay nada que cargar.
    }

    var sentinel = document.getElementById('feed-sentinel');
    var status = document.getElementById('feed-status');
    var lastId = parseInt(list.getAttribute('data-last-id'), 10);
    var hasMore = list.getAttribute('data-has-more') === '1';
    var loading = false;

    // Mismo formato de precio que src/format.php en PHP: punto de miles y
    // dos decimales solo cuando el precio tiene fracción.
    function formatPrice(price) {
        var value = parseFloat(price);
        var decimals = value % 1 === 0 ? 0 : 2;
        return value.toLocaleString('es-AR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // Construye la misma tarjeta que imprime views/bike_card.php.
    function buildCard(bike) {
        var card = document.createElement('article');
        card.className = 'bike-card';

        // Foto principal: misma guarda que en PHP (si no hay, tarjeta sin imagen).
        if (bike.photo) {
            var img = document.createElement('img');
            img.className = 'bike-photo';
            img.src = '/uploads/' + bike.photo;
            img.alt = ''; // decorativa: el título de abajo identifica la moto
            img.loading = 'lazy';
            card.appendChild(img);
        }

        var title = document.createElement('h2');
        var link = document.createElement('a');
        link.href = bike.url;
        link.textContent = bike.title;
        title.appendChild(link);

        var brand = document.createElement('p');
        brand.className = 'bike-brand';
        brand.textContent = bike.brand;

        var price = document.createElement('p');
        price.className = 'bike-price';
        price.textContent = bike.currency + ' ' + formatPrice(bike.price);

        var sold = bike.status === 'sold';
        var badge = document.createElement('p');
        badge.className = 'badge ' + (sold ? 'badge-sold' : 'badge-available');
        badge.textContent = sold ? 'Vendida' : 'Disponible';

        card.appendChild(title);
        card.appendChild(brand);
        card.appendChild(price);
        card.appendChild(badge);
        return card;
    }

    function setMessage(text) {
        if (status) {
            status.textContent = text;
        }
    }

    function loadMore() {
        if (loading || !hasMore || isNaN(lastId)) {
            return;
        }

        loading = true;
        setMessage('Cargando más motos…');

        fetch('/api/bikes.php?before=' + lastId)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                data.bikes.forEach(function (bike) {
                    list.appendChild(buildCard(bike));
                });

                if (data.bikes.length > 0) {
                    lastId = data.bikes[data.bikes.length - 1].id;
                }
                hasMore = data.has_more === true;
                setMessage(hasMore ? '' : 'No hay más motos por ver.');
            })
            .catch(function () {
                setMessage('No se pudieron cargar más motos. Probá recargar la página.');
            })
            .finally(function () {
                loading = false; // Libera para la próxima tanda.
            });
    }

    if (sentinel && 'IntersectionObserver' in window) {
        // Cada vez que el centinela entra en pantalla se pide otra tanda.
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadMore();
                }
            });
        });
        observer.observe(sentinel);
    }
})();
