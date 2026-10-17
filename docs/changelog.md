# Changelog

## 2026-10-17
- **Recuadros de fotos en publicar y editar** (pedido del usuario tras probar
  la etapa 4; tarea 4.6): donde antes había una sola línea de input con el
  atributo `multiple` (que Chrome escondía y Safari no soporta), ahora hay
  **4 recuadros clicables con borde punteado, uno por foto**, cada uno con su
  propio input de una sola foto: se comportan igual en todos los
  navegadores.
  - Nuevo `public/js/photos.js`: vista previa de la foto elegida en el
    recuadro (mejora progresiva: sin JS el recuadro igual selecciona y envía).
  - Nuevo `replace_photo_of_bike()` en el modelo de fotos, con test; el
    reemplazo valida tipo/peso/medidas y rechaza repetidas contra las otras
    fotos de la publicación.
- **Edición de fotos rediseñada con una sola grilla de 4 recuadros**
  (segundo pedido del usuario): donde antes había una sección "Fotos
  actuales" (miniaturas con su botón) separada de los recuadros vacíos del
  formulario de datos, ahora la edición muestra **los 4 recuadros juntos**:
  los que ya tienen foto se ven **rellenos** con la foto publicada y su
  control —**✕** para quitar (con 2 o más fotos), o campo + botón
  **"Reemplazar"** cuando es la única—, y los vacíos son los pickers para
  agregar fotos (con vista previa).
  - La página tiene ahora **dos formularios** que apuntan al mismo URL y se
    distinguen por el botón presionado: fotos (`remove_photo_id`,
    `replace_photo_id`, o agregar `photos[]`) y datos (`save_data`), todos
    con un único chequeo de CSRF al entrar.
  - Nuevo helper `next_photo_position()` en el modelo de fotos (la posición
    más alta + 1), compartido por agregar fotos y `update_bike_with_photos()`.
  - Verificado con curl: moto de 2 fotos → 2 rellenos con ✕ + 2 vacíos;
    de 1 foto → 1 relleno con Reemplazar + 3 vacíos; de 4 → 4 rellenos sin
    botón de agregar; agregar/quitar/reemplazar/guardar datos OK; sin CSRF
    ningún cambio. Suite en verde: **91 tests, 219 aserciones**.
- **Reemplazar la única foto con un click en la imagen** (tercer pedido del
  usuario, ajuste del rediseño): el recuadros de la foto única ya no muestra
  el archivo nativo ("Seleccionar archivo") ni el botón a la vista; el
  recuadros entero es un `<label>` del archivo oculto, así que al hacer
  click se abre el selector y `photos.js` envía el reemplazo apenas se elige
  (apretando el botón "Reemplazar" del mismo formulario, que en ese caso
  queda escondido). Al pasar el mouse (o al llegar con Tab) aparece una
  pista "🔄 Cambiar foto" sobre la imagen. **Sin JavaScript** se ve el
  archivo chiquito y el botón "Reemplazar" de antes: el mismo envío, sólo
  que manual — la clase `.js` en `<html>` la agrega `photos.js`, como hace
  el carrusel. Verificado con curl: el HTML trae el label, el archivo oculto
  y el botón de reserva (ningún "Seleccionar archivo" a la vista) y el
  reemplazo funciona (foto nueva, archivo viejo borrado). Suite en verde:
  **91 tests, 219 aserciones**.
- **Guard contra sesiones huérfanas** (bug encontrado al probar la etapa 4):
  al restaurar la base de desarrollo se borraron cuentas de prueba y quedaron
  sesiones apuntando a usuarios inexistentes; publicar entonces fallaba con
  el confuso "No se pudo guardar la publicación..." (el INSERT chocaba con
  la clave foránea de `user_id`). Ahora `require_login_or_redirect()` comprueba
  con `user_exists()` que la cuenta siga existiendo: si no, cierra la sesión
  y manda al login. Tests: `UserTest` cubre `user_exists()` (91 tests, 219
  aserciones). Verificado con curl: la sesión huérfana recibe 302 a
  /login.php en las pantallas protegidas y una sesión válida sigue en 200.

## 2026-10-16
- **Etapa 4 completa — Gestión del dueño (RF-6 a RF-10)** en la rama
  `feature/etapa-4-gestion-dueño`:
  - **4.1 Mis publicaciones** (`public/my-bikes.php` + `list_bikes_by_user()`):
    el dueño ve solo sus motos con foto principal, badge Disponible/Vendida y
    las acciones Editar / Borrar / Marcar como vendida; estado vacío que
    invita a publicar; sin sesión → 302 a /login.php. Enlace "Mis
    publicaciones" en el header de los logueados.
  - **4.2 Editar** (`public/edit-bike.php?id=`): reutiliza `views/bike_form.php`
    con los mismos validadores que publicar; quitar fotos de a una con botón
    por foto (bloqueado al ser la última), agregar fotos nuevas con cupo 4 en
    total y rechazo de repetidas contra las ya guardadas por hash
    (`photo_batch()` con `$existingHashes`). `update_bike()` no toca
    created_at ni status: la moto no se mueve en el feed y una vendida se
    puede editar.
  - **4.3 y 4.4 Borrar y vender** (`public/bike_actions.php`): solo POST con
    CSRF y redirect PRG a Mis publicaciones; `delete_bike_as_owner()` borra
    la moto (cascada en la base) y después sus archivos del disco;
    `toggle_bike_status_as_owner()` alterna disponible ↔ vendida de forma
    reversible. La verificación de dueño vive en el modelo, con el id sacado
    de la base, nunca del formulario (RF-10).
  - **4.5 CSRF completo**: registro, publicar, editar, quitar foto, borrar y
    vender chequean el token; verificado con curl que un POST sin token a
    ninguna de las cinco acciones produce cambio.
- Tests: +18 (`tests/Integration/OwnerTest.php` con 17 y uno nuevo en
  `StorageTest`): lista del dueño, editar sin mover el feed ni el estado,
  edición de vendida, transacción de editar con fotos (y su rollback), quitar
  foto (máximo, mínimo y foto ajena), borrar con archivos (dueño, ajeno,
  inexistente) y alternar vendida (reversibilidad, doble marca, ajeno).
  Suite en verde: **88 tests, 211 aserciones**.
- **Bug encontrado y corregido durante la verificación**: al agregar fotos en
  la edición, la posición se calculaba con `count()` y tras quitar una foto
  (quedan huecos en la numeración) la nueva colisionaba con una existente.
  Ahora es `MAX(position) + 1`, con test de regresión.
- Verificación manual con curl de la etapa completa: editar (propia y ajena),
  quitar foto (una, la última y ajena), agregar foto (aceptada y repetida),
  borrar (dueño con archivos y ajeno sin cambio), vender dos veces, y las
  cinco acciones sin CSRF. Base de desarrollo restaurada a la semilla
  prístina (4 usuarios, 27 motos, 54 fotos, uploads completos).

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
