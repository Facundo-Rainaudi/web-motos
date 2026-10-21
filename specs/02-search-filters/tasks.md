# Tareas — Spec 02 (Búsqueda y filtros)

Una tarea por vez. Al cerrar cada una: `php -l` en los archivos tocados, sus
tests en verde y registro en `docs/changelog.md`. Ver `plan.md` para el porqué
de cada decisión y `spec.md` para los criterios de aceptación.

## Etapa 0 — Motor de filtros (RF-11, RF-12)

- [x] **0.1 Parseo de parámetros**: `src/filters.php` con
  `parse_feed_filters(array $get): array` (lista blanca por parámetro, `q`
  con trim + recorte a 80 con `mb_substr` + sin `%`/`_`, precios con
  `validate_price`, `min > max` descarta ambos) +
  `tests/Unit/FiltersTest.php`.
  RF: RF-11, RF-12, RNF-7.
  Hecho cuando: el test cubre texto vacío/solo espacios, recorte a 80,
  comodines eliminados, enums inválidos, precios con coma/punto y basura
  (letras, negativos, 3 decimales), rango invertido y parámetros
  desconocidos, y `composer test` queda en verde.
  ✅ Hecho (2026-10-20): `filters.php` + `FiltersTest` (16 tests / 37
  aserciones). Además de `%` y `_` se quita también `\` (comodín por
  defecto del LIKE de MySQL), y los parámetros que llegan como arreglo
  (`?q[]=x`) se ignoran.

- [x] **0.2 Listado y contador filtrados**: `list_bikes_feed()` gana
  `$filters = []` al frente (WHERE con consultas preparadas: `title LIKE
  :q OR brand LIKE :q`, condition, currency, price mín/máx, status) y
  `count_bikes_matching()` con el mismo WHERE +
  `tests/Integration/SearchTest.php`.
  RF: RF-11, RF-12.
  Hecho cuando: el test encuentra por título y por marca (con mayúsculas y
  acentos), aplica cada filtro solo y todos combinados, muestra vendidas por
  defecto y las excluye con `status=available`, devuelve vacío sin
  coincidencias, el contador coincide con el listado, y los tests de
  `FeedTest` (RF-4) siguen en verde sin cambios.
  ✅ Hecho (2026-10-20): helper `bikes_feed_where()` compartido entre
  `list_bikes_feed()` y `count_bikes_matching()` (contador y listado no
  pueden divergir); el LIKE usa dos parámetros (`:q_title`, `:q_brand`)
  porque PDO con prepares nativos no repite nombres. `SearchTest` (12
  tests) incluye la combinación filtros + `beforeId`. `index.php` y
  `api/bikes.php` pasan `[]` por ahora (sin cambio de comportamiento);
  se conectan en las etapas 1 y 2.

- [x] **0.3 Índices de filtros**: agregar a `sql/schema.sql` los KEY de bikes
  (`condition`, currency), (currency, price) y (status) y aplicarlos en
  desarrollo con `ALTER TABLE` directo (**nunca recargar schema.sql en
  `bike_center`**: vaciaría las tablas).
  RF: RNF-9.
  Hecho cuando: `SHOW INDEX FROM bikes` en desarrollo y en
  `bike_center_test` muestra los índices nuevos, los datos de desarrollo
  siguen intactos (29 motos) y la suite sigue en verde.
  ✅ Hecho (2026-10-20): KEY agregados a `sql/schema.sql` + `ALTER TABLE`
  en `bike_center` (verificado con `SHOW INDEX`: 29 motos intactas).
  `bike_center_test` se recrea del esquema en cada corrida de la suite y
  ya muestra los índices; suite en verde (119 tests / 279 aserciones).

## Etapa 1 — Pantalla de la vitrina (RF-11 a RF-13)

- [ ] **1.1 Barra de filtros**: `views/filter_bar.php` con formulario GET
  hacia `/`: campo `q`, selects de condición y moneda, dos inputs de precio,
  checkbox "Solo disponibles", botón "Aplicar filtros" y link "Limpiar
  filtros" a `/`; valores re-poblados desde el array parseado; labels con
  `for`/`id`.
  RF: RF-11, RF-12.
  Hecho cuando: con JS apagado la barra busca y filtra igual (submit
  normal), y recargar con parámetros muestra la selección re-poblada.

- [ ] **1.2 Index con selección**: `public/index.php` lee `$_GET` con
  `parse_feed_filters()`, pide el listado y el contador, y renderiza barra +
  contador ("N motos encontradas", singular/plural) + estados vacíos
  (sin resultados con su mensaje y limpiar; sitio vacío manda el del RF-4) y
  el centinela del scroll solo si hay más.
  RF: RF-11, RF-13.
  Hecho cuando: a mano, `/?q=ninja` lista solo las que coinciden, `/?q=zzz`
  muestra "sin resultados" con limpiar, `/?condition=new&currency=USD`
  combina filtros, `/?condition=banana&min=abc` se ve como la vitrina
  completa (basura ignorada) y la URL sin parámetros se ve igual que hoy.

- [ ] **1.3 Estilo de la barra**: `public/css/style.css` con la guía de la
  skill `web-design`: mobile-first (375 px campos apilados, ≥ 640 px fila),
  tokens existentes, foco visible, estados vacío/error coherentes con el
  sistema de diseño; `docs/design.md` registra lo nuevo si lo hay.
  RF: RNF-2 (aplicado), RF-13.
  Hecho cuando: la vitrina se ve sin scroll horizontal a 375 px y 1280 px,
  todos los campos tienen label visible y el foco se ve en Teclado.

- [ ] **1.4 Recorrido de pantalla**: verificar con curl y en navegador los
  criterios del RF-11 y RF-12, incluidos los casos límite de la spec
  (texto con `<script>` se muestra literal, texto de más de 80 recortado,
  rango invertido ignorado).
  RF: RF-11, RF-12.
  Hecho cuando: la checklist de casos límite correspondiente queda en verde
  y los resultados quedan anotados en `tasks.md` al estilo de la spec 01.

## Etapa 2 — Scroll filtrado (RF-13)

- [ ] **2.1 API con filtros**: `public/api/bikes.php` aplica el mismo
  `parse_feed_filters()` a `$_GET` y pasa los filtros a `list_bikes_feed()`;
  la respuesta JSON no cambia de forma.
  RF: RF-13, RNF-7.
  Hecho cuando: `curl '/api/bikes.php?q=...&condition=used'` devuelve solo
  la tanda filtrada con su `has_more` correcto.

- [ ] **2.2 Feed que reenvía la selección**: `public/js/feed.js` arma la URL
  de "ver más" conservando los parámetros actuales de la página
  (`URLSearchParams` de `location.search`) y agregando `before`.
  RF: RF-13.
  Hecho cuando: en navegador, filtrando y bajando el scroll, entran solo
  motos de la selección y el "limpiar" vuelve al listado completo.

- [ ] **2.3 Paginación filtrada con tests**: cubrir en
  `tests/Integration/SearchTest.php` que las tandas con filtros activos no
  repiten ni pierden motos aunque se publiquen nuevas en el medio.
  RF: RF-13, RNF-3.
  Hecho cuando: el test recorre dos tandas filtradas sin duplicados y la
  suite completa sigue en verde.

## Etapa 3 — Cierre de la spec (RF-11 a RF-13, RNF-7 a RNF-9)

- [ ] **3.1 Recorrido final**: checklist completo de los criterios del
  RF-11, RF-12 y RF-13 (combinando búsqueda, los cinco filtros, contador,
  limpiar, URL compartible y scroll), más los RNF-7 (contrato y topes),
  RNF-8 (XSS y SQL preparado, verificación de parámetros truchos) y RNF-9
  (tiempos de tanda filtrada).
  RF: todos.
  Hecho cuando: todos los criterios quedan anotados en verde en esta
  tarea, al estilo de la etapa 6 de la spec 01.

- [ ] **3.2 Sintaxis y suite**: `php -l` en todos los PHP tocados por la
  spec y `composer test` completo.
  RF: constitución, principio 4.
  Hecho cuando: cero errores de sintaxis y la suite entera en verde.

- [ ] **3.3 Registro en docs**: `docs/changelog.md` con la entrega de la
  spec 02 y `docs/decisions.md` con las decisiones nuevas de implementación;
  esta `tasks.md` con todas las tareas marcadas.
  RF: proceso.
  Hecho cuando: los tres documentos quedan al día con lo construido.
