# Changelog

## 2026-10-15
- Carrusel de fotos en el detalle (`bike.php`, pedido del usuario): en lugar
  de apilar las fotos, se muestra una por vez con botones ◀ ▶ (navegación ida
  y vuelta) y contador "Foto N de M". Actualiza RF-5 ("todas sus fotos, una a
  la vez, con botones") y la tarea 3.5. Nuevo `public/js/gallery.js` con
  degradación sin JavaScript (todas las fotos quedan visibles apiladas y los
  botones ocultos hasta que el JS agrega la clase `.js`), botones y script
  solo cuando hay más de una foto, y estilos `.gallery` en el CSS. Verificado
  con curl: 2 fotos → 2 imgs + 2 botones + contador; 1 foto → sin botones ni
  script; suite en verde: 70 tests, 160 aserciones.
- Se corrigió un test intermitente de la etapa 0: `CsrfTest` alteraba el token
  fijando la primera letra en 'a' y, al ser hexadecimal, 1 de cada 16 veces
  el "alterado" era idéntico al original y el chequeo legítimamente devolvía
  true. Ahora el primer carácter se cambia por uno garantizadamente distinto;
  8 corridas seguidas de la suite en verde (70 tests, 160 aserciones).
- Se actualizó el RF-4 (y H1) por pedido explícito del usuario: la vitrina
  muestra la **foto principal** en cada tarjeta, no solo en el detalle. La spec,
  la tarea 3.3 y su criterio de "Hecho cuando" quedan reflejados. Cambio:
  `list_bikes_feed()` suma la foto de posición 0 por subconsulta (índice
  `photos(bike_id, position)`), `api/bikes.php` la expone como `photo`,
  `views/bike_card.php` y `feed.js` la pintan igual (con guarda si no hay
  fotos) y el CSS nueva clase `.bike-photo` (4:3 con `object-fit: cover`).
  Tests nuevos en `FeedTest` (foto correcta, moto sin fotos y campo en el
  JSON): suite en verde con 70 tests, 160 aserciones; verificado a mano con
  curl (20 tarjetas con `<img>`, API con `photo` y archivos sirviendo 200).
- Etapa 3 de `specs/01-bike-center/tasks.md` completada: vitrina y detalle
  (H1, H5, H10; RF-4, RF-5, RNF-3). `list_bikes_feed()` entrega motos de 20 en
  20 con paginado por clave (`created_at DESC, id DESC`, cursor por id) y
  `tests/Integration/FeedTest.php` prueba 3 tandas sin repetir ni perder motos
  aunque se publiquen nuevas en el medio, que editar no cambia la posición, y
  el endpoint JSON. La portada `public/index.php` reemplaza el placeholder con
  `views/bike_card.php` (título, marca, precio con moneda, etiqueta de estado
  y estado vacío) y `public/js/feed.js` + `public/api/bikes.php` (JSON con
  `has_more`) cargan el resto por scroll infinito con `IntersectionObserver`,
  agregando tarjetas con `textContent` (HTML literal). `public/bike.php?id=`
  muestra el detalle completo solo con sesión (fotos, vendedor, fecha;
  descripción como texto literal; sin teléfono: RNF-4) y "no disponible" para
  inexistentes o borradas. Nuevos: `src/format.php` (`format_price` con test),
  `sql/seed.sql` (4 usuarios, 27 motos, una vendida, 54 fotos), fixture
  `valid_400x400.jpg` y enlace "Publicar" en el encabezado. La publicación
  ahora redirige al detalle de la moto creada (decisión de la etapa 2).
  Suite en verde: 67 tests, 155 aserciones; flujo verificado a mano con curl
  (vitrina ordenada con etiqueta vendida, endpoint 20+7 sin repetidos, detalle
  con y sin sesión, HTML literal, estado vacío y fotos de la semilla con 200).
- Se corrigió la carga de `sql/seed.sql`: el cliente `mysql` se conecta en
  latin1 por defecto y doble-codificaba los acentos; el archivo ahora incluye
  `SET NAMES utf8mb4;`.
- `db_connect()` acepta la variable de entorno `BIKE_CENTER_DB` para que los
  tests de páginas y endpoints usen `bike_center_test`.
- Etapa 2 de `specs/01-bike-center/tasks.md` completada: publicación de motos
  (H4, RF-3). `src/storage.php` valida las fotos por tipo real (`finfo`),
  peso (≤ 2 MB), medidas (≥ 200×200), máximo de 4 y repetidas por hash `sha1`,
  y las guarda con nombre generado por el servidor; `create_bike()` crea la
  moto con sus fotos en una transacción (o todo o nada) y `public/publish.php`
  + `views/bike_form.php` ofrecen el formulario con CSRF y patrón PRG.
  Se agregó `validate_brand()` (marca obligatoria de hasta 60 caracteres),
  los helpers `require_login_or_redirect()`, `flash_set()`/`flash_pull()` con
  `views/flash.php` (los avisos post-redirect se ven una sola vez) y tests de
  `save_photo`/`delete_photo_file`. Suite en verde: 60 tests, 127 aserciones;
  flujo verificado a mano con curl: visitante mandado al login, datos
  inválidos o sin fotos no guardan nada, token CSRF inválido no guarda nada,
  publicación válida con flash de éxito, fotos repetidas o fuera de medida se
  rechazan con aviso y la moto se publica con las válidas.
- Se corrigió un bug de la etapa 2 detectado en la verificación manual:
  `save_photo()` usaba la extensión como clave del mapa MIME→extensión y
  rechazaba toda foto en el navegador; ahora toma la extensión que devuelve
  `photo_extension()`. Cubierto con tests nuevos de guardado y borrado.

## 2026-10-14
- Etapa 1 de `specs/01-bike-center/tasks.md` completada: registro con
  persistencia (`src/auth.php` + `tests/Integration/UserTest.php`), pantallas
  de registro, login y logout con CSRF y patrón PRG, encabezado y pie en
  `views/`, estilos mínimos y una portada placeholder que confirma la sesión.
  Suite en verde: 41 tests, 78 aserciones; flujo completo verificado a mano
  con el servidor local (registro inválido/válido, correo duplicado, login
  genérico, logout y que el GET al logout no cierra sesión).
- Etapa 0 de `specs/01-bike-center/tasks.md` completada: Composer con Pest 4
  (solo dev-dependencias), `sql/schema.sql` con las 3 tablas cargadas en
  `bike_center`, `tests/Pest.php` que recrea `bike_center_test` antes de cada
  tanda, conexión PDO en `src/db.php`, validaciones puras en
  `src/validation.php`, sesión en `src/session.php` y token CSRF en
  `src/csrf.php`. Suite en verde: 35 tests, 61 aserciones.
- Se creó `specs/01-bike-center/spec.md`, la spec de la primera funcionalidad
  (registro, publicación de motos y vitrina pública), con 9 requisitos
  funcionales en notación EARS, casos límite y criterios de finalización.
- Se resolvieron todas las dudas abiertas de la spec: búsqueda y filtros
  pospuestos, moneda por publicación sin conversión, teléfono de 10 dígitos en
  el registro y listado de 20 motos con scroll infinito.
- Revisión de QA sobre la spec 01 (37 hallazgos): se agregaron el panel "Mis
  publicaciones" (RF-9), reglas de longitud y formato de todos los campos, el
  teléfono guardado como +54 11 seguido de los 10 dígitos para el contacto
  futuro, el borrado de fotos junto con su publicación, el texto HTML mostrado
  como literal y la verificación manual de los requisitos visuales y de
  rendimiento.
- Se ajustaron los principios 4 y 5 de la constitución: lo visual y de
  rendimiento se verifica con recorrido manual, y las fotos son archivos en
  disco con su referencia en la base.
- Se creó `specs/01-bike-center/plan.md`: plan de implementación con estructura
  de carpetas, esquema de MySQL, módulos de `src/`, pantallas, política de
  tests, semilla de prueba y orden de trabajo en 6 etapas.
- Se creó `specs/01-bike-center/tasks.md`: 26 tareas chicas en orden de
  dependencia, cada una con sus RF y su criterio "Hecho cuando".
- Se creó `docs/constitution.md` con los 6 principios innegociables del proyecto: stack simple, spec → código, lógica separada de la interfaz, tests con Pest, persistencia en MySQL y política de idioma.
