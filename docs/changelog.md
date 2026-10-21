# Changelog

## 2026-10-21

- **Spec 02, etapa 1: la vitrina ya busca y filtra** (tests primero). Nuevo
  `views/filter_bar.php`: formulario GET a `/` con los seis campos del
  contrato (buscar, condición, moneda, precio mín/máx y "solo disponibles"),
  "Aplicar filtros" como submit normal (funciona sin JS) y "Limpiar filtros"
  como link. `public/index.php` lee `$_GET` con `parse_feed_filters()`, pide
  el listado filtrado y el contador, y renderiza: barra siempre visible,
  contador "N motos encontradas" (sólo con selección activa), estado propio
  de "sin resultados" con salida (priorizando el del RF-4 si el sitio está
  vacío) y centinela del scroll sólo en el listado completo con más páginas.
  Estilo en la sección 10 de `style.css` (grilla mobile-first reutilizando
  `.card` y los campos existentes; primer checkbox del sitio con
  `accent-color`), decisiones registradas en `docs/design.md`. Nuevos tests
  `tests/Integration/VitrinaTest.php` (11 tests que incluyen `index.php` con
  `$_GET` y revisan el HTML: contador y singular/plural, estados vacíos,
  basura ignorada, valor de `q` escapado literal, re-poblado y centinela).
  Recorrido con curl sobre la base de desarrollo en verde (checklist de casos
  límite en `tasks.md`). Suite completa: 130 tests / 311 aserciones.
  `php -l` limpio. Pendiente del usuario: vista visual a 375/1280 px.

- **Rediseño del layout de la vitrina (feedback del usuario)**: la card de
  filtros a todo el ancho arriba no gustó. Ahora, en ≥ 768 px, los filtros
  van en una columna izquierda de 250 px con la card *sticky* (visible
  mientras se hace scroll) y el catálogo a la derecha con las tarjetas de a
  dos por fila desde 1024 px; en < 768 px sigue como antes (filtros arriba,
  una columna de motos). El `main` de la vitrina se ensancha a 1040 px sólo
  cuando tiene el layout nuevo (`.site-main:has(...)`) y la grilla interna
  de la barra quedó acotada a 640-767 px. Nueva sección 11 de `style.css`,
  `VitrinaTest` actualizado con la estructura, `docs/design.md` y
  `docs/decisions.md` al día. Suite: 130 tests / 313 aserciones.

- **Panel de filtros tipo mercado (iteración 2 del diseño, por feedback
  del usuario con una referencia visual)**: `views/filter_bar.php` pasó de
  barra a panel con título "Filtros", filas separadas por hairlines, grupos
  Condición/Moneda/Precio plegables con `<details>` nativos (sin JS) que
  nacen abiertos cuando traen la selección activa, y "Solo disponibles"
  como palanca de CSS sobre un checkbox real (el foco de Teclado se dibuja
  en la palanca). "Aplicar filtros" quedó como botón ancho completo al
  fondo con "Limpiar filtros" debajo. El catálogo crece por breakpoints:
  2 tarjetas por fila desde 1024 px, 3 desde 1280 px y 4 desde 1560 px, con
  el `main` de la vitrina en 1400 px. Secciones 10 y 11 reescritas de
  `style.css`; `VitrinaTest` ampliado (grupos abiertos con selección y
  plegados sin ella). Suite: 130 tests / 315 aserciones. Recorrido curl en
  verde (panel, grupos abiertos, contador, basura ignorada, XSS escapado).

- **Nuevo `docs/maintenance.md` con la checklist de refactor diferido**
  (pedido del usuario): tras auditar la estructura del proyecto se acordó
  dejar por escrito 4 tareas de mantenimiento **sin historia de usuario**
  (por eso no van en `specs/`): A) partir `bike_model.php` en feed vs.
  dueño, B) extraer las 215 líneas de manejo de POST de `edit-bike.php` a
  `src/` con tests primero, C) arranque de página estándar
  (`page_start()`), y D) partir `style.css` (1.006 líneas) en 5 archivos CSS
  sin build. Se ejecutan **después de cerrar la spec 02**, una por vez con
  la política de la constitución.

## 2026-10-20
- **Spec 02 aprobada y con plan técnico**: el usuario aprobó el borrador de
  `specs/02-search-filters/spec.md` y se generó `plan.md` (contrato de
  parámetros por URL, `src/filters.php` con parseo puro, `list_bikes_feed`
  ampliada con filtros opcionales más `count_bikes_matching`, barra de filtros
  en `views/filter_bar.php`, scroll filtrado en `api/bikes.php` + `feed.js` e
  índices en bikes). Se completó también `tasks.md` con las 4 etapas (motor
  de filtros → pantalla → scroll filtrado → cierre), todas pendientes.
  Sin código todavía (constitución: primero la spec aprobada).

- **Etapas 0 de la spec 02 implementada: el motor de filtros** (tests
  primero). Nuevo `src/filters.php` con `parse_feed_filters()`: parseo puro
  de la URL con lista blanca por parámetro (`q` recortado a 80 sin comodines,
  `condition`/`currency`/`status` solo con sus valores válidos, precios con
  `validate_price` y rango invertido que descarta ambos lados). Nuevo
  `tests/Unit/FiltersTest.php` (16 tests). `list_bikes_feed()` acepta
  `$filters = []` opcional y comparte su WHERE con la nueva
  `count_bikes_matching()` vía `bikes_feed_where()`; `index.php` y
  `api/bikes.php` pasan `[]` por ahora (sin cambio de comportamiento, el
  RF-4 sigue cubierto por `FeedTest`). Nuevo `tests/Integration/SearchTest.php`
  (12 tests: título/marca con mayúsculas y acentos, cada filtro solo y todos
  combinados, vendidas por defecto vs `status=available`, contador y paginado
  con filtros). Índices de filtros agregados a `sql/schema.sql` y aplicados
  en desarrollo con `ALTER TABLE` (29 motos intactas). Suite completa en
  verde: 119 tests / 279 aserciones. `php -l` limpio en todos los archivos
  tocados. La etapa 1 (barra de filtros con la skill `web-design`) sigue
  pendiente.

## 2026-10-19
- **Bug corregido: la foto elegida no se guardaba al apretar "Guardar
  cambios" en la edición** (reportado por el usuario): la página tenía dos
  formularios (fotos y datos) y los archivos de los recuadros viajaban en uno
  distinto del botón "Guardar cambios", que además no procesaba `$_FILES` —
  la foto se descartaba en silencio. Arreglo: **un solo `<form>`** con un
  único "Guardar cambios" que guarda datos y fotos juntos (cupo de 4, hash,
  tipo/peso/medidas, rechazos por flash, igual que al publicar). ✕ y
  "Reemplazar" pasaron a ser botones con `name` dentro de ese mismo form (sin
  formularios anidados); también se aplica el reemplazo elegido en el
  recuadros de foto única si se guarda sin apretar "Reemplazar". Se eliminó
  el botón "Agregar fotos" (un solo modelo mental) y `views/bike_form.php`
  ganó `$inlineForm` (sólo campos, sin `<form>` ni csrf, cuando la página ya
  abrió el form). Verificado: `php -l` limpio; 12 flujos por curl en verde
  (incluye datos inválidos que no guardan nada, repetidas, CSRF y cupo de 4);
  suite **91 tests /219 aserciones**; la base de desarrollo quedó como estaba
  (29 motos, 6 archivos en uploads — las motos descartables de la
  verificación se borraron con la propia app). Decisión en `docs/decisions.md`
  (la de "dos formularios" de la etapa 4 queda superada). La regresión queda
  cubierta por este checklist curl, porque la suite no tiene arnés HTTP.
- **Acento ámbar rechazado y reemplazado por petróleo** (feedback del
  usuario; guía de la skill `frontend-design`): el ámbar cálido no
  combinaba con la paleta fría del sitio (y es, además, un "tell" de
  diseño genérico). Ahora el navy `#10243e` es el color de acción completo
  (header, botones, títulos, enlaces) y el único acento es el **petróleo
  `#0f6b70`**, reservado a precios, foco y hovers.
- **Superficies planas**: se quitaron las sombras de `.card`/`.bike-card`
  y el hover de elevación de las tarjetas (más señales de template
  genérico); ahora es blanco + borde hairline y sólo el enlace de la
  tarjeta reacciona al mouse. Movimiento solo como respuesta de botones.
  Sin tocar HTML ni lógica. Verificado: llaves balanceadas, cero variables
  sin definir (ni sobrantes), las 5 pantallas renderizando con la semilla
  (20 tarjetas en la vitrina). Sistema actualizado en `docs/design.md`.
- **Skill `frontend-design` instalada** (npx skills add desde
  `anthropics/skills`): guía de dirección estética intencional; queda en
  `.agents/skills/frontend-design/` junto con `skills-lock.json`.

## 2026-10-17
- **Base del rediseño visual ("showroom deportivo", pedido del usuario con
  la skill web-design)**: `public/css/style.css` reescrito con **sistema de
  diseño propio** —tokens en `:root` (paleta, escala de espaciado 4/8/16/24/
  32, tipografía única con pesos 400/700/800, radios y sombras)— y
  mobile-first (reglas base a 375 px, ampliaciones en `@media (min-width)`).
  Sin tocar lógica: mismo HTML y mismas clases; solo `views/flash.php`
  cambió para que los mensajes de página sean **banners** (`.flash-error` /
  `.flash-notice`, fondo suave + barra lateral) en vez de texto suelto.
  - Acento ámbar en dos pasos (puro para precios grandes, oscuro para
    botones/enlaces con blanco) por contraste AA; header azul noche con
    punto ámbar en la marca; tarjetas con borde, sombra al hover y precio
    protagonista; tarjetas **vendidas** con la foto atenuada (`:has`);
    campos con foco ámbar visible; botones con variantes semánticas
    (secundario/peligro) y estados hover/active.
  - Verificado: `php -l views/flash.php` limpio, llaves del CSS balanceadas
    (98/98), los 5 selectores funcionales del carrusel/recuadros intactos,
    las 5 pantallas en 200 y el flash renderizando como banner.
  - Sistema documentado en `docs/design.md` (tokens, decisiones y reglas
    para estilos nuevos).
- **Etapa 6 — Cierre: recorrido final de RF-1 a RF-10, todo en verde**.
  `php -l` sobre los **38 archivos PHP** del proyecto sin errores y suite en
  verde (**91 tests, 219 aserciones**). Recorrido por curl punto por punto
  sobre la app con la semilla: registro (teléfono `+54 11` + 10 dígitos,
  duplicado en otro formato de mayúsculas y los 5 inválidos con su aviso),
  sesión (mensaje genérico de login, logout, acción sin sesión, cookie sin
  expiración y dos dispositivos sin pisarse), publicar (datos inválidos sin
  guardar nada; fotos repetida/no-imagen/3 MB/100×100 rechazadas con motivo
  por flash y cupo de 4), vitrina (20 tarjetas en orden
  `created_at DESC, id DESC`, etiqueta vendida, vacía en la base de tests y
  moneda sin convertir), detalle (completo con sesión, 302 sin sesión,
  "no disponible" borrada), editar (sin mover la moto, mismas validaciones,
  fotos entre 1 y 4, reemplazo de la única, vendida editable), borrar
  (cascada de filas y archivos), vender (reversible), mis publicaciones
  (solo propias y estado vacío) y la regla de propiedad (toda acción directa
  sobre moto ajena con CSRF válido no produce ningún cambio). RNF-4 en
  pantalla: ni teléfono ni contraseña aparecen en ninguna vista. Detalle del
  recorrido en `specs/01-bike-center/tasks.md` (6.1).
  - **Incidente 1 (datos, corregido)**: la descripción de la moto 1 quedó
    doble-codificada por un `UPDATE` de restauración hecho con el cliente
    mysql sin `--default-character-set=utf8mb4`; el sitio estaba sano (las
    motos intactas mostraban sus acentos bien). Restaurado con el charset
    correcto.
  - **Incidente 2 (datos, corregido)**: faltaba `public/uploads/seed_01.jpg`
    (12 filas de la semilla apuntaban a él); regenerado desde
    `tests/fixtures/valid_200x200.jpg` y la moto 1 devuelta a su estado
    exacto de semilla (seed_01 + seed_02).
  - **Incidente 3 (dato del usuario, NO recuperable)**: durante un chequeo
    borré por error dos archivos referenciados; uno era mi foto de prueba en
    la moto 1 (ya restaurada a la semilla) y el otro era **la foto de la
    "Ninja 500se" del usuario**, cuyo contenido no se puede regenerar (los
    nombres de archivo son aleatorios, no hay snapshots ni está en git).
    Pedirle que la reemplace con un click en la foto desde su edición.
- **Etapa 5 — No funcionales (RNF-2, RNF-3, RF-2)**: responsive y checklist
  manual.
  - *Responsive (solo CSS)*: el sitio ya era fluido (grillas con `1fr`,
    imágenes al 100% con `object-fit`, main con padding); la etapa 5 cerró
    los huecos de celular: los campos heredan la tipografía del sitio (evita
    el zoom automático de iOS al enfocar), la navegación del encabezado se
    envuelve a una segunda fila hasta 480px, el ✕ de quitar foto crece a
    2.5rem para el dedo, los textos largos sin espacios (títulos, URLs en la
    descripción) se parten con `overflow-wrap` en vez de desbordar la
    pantalla, y el pie por fin tiene estilo propio.
  - *Checklist manual*: la segunda tanda del scroll infinito mide 0.003 s
    contra `/api/bikes.php` (límite: 2 s); las pantallas privadas mandan
    `Cache-Control: no-store` —nuevo header en
    `require_login_or_redirect()`— para que el botón "atrás" después de
    cerrar sesión vuelva a pedirlas al servidor y caiga en el guard de
    login; la cookie de sesión no tiene Expires ni Max-Age, así que cerrar
    la aplicación del navegador la corta; y dos sesiones independientes no
    se pisan (logout en un dispositivo deja al otro en pie, verificado con
    dos cookies jars). Los puntos de "atrás" y "cerrar la app" se
    completan a mano en el navegador del usuario.
  - Suite en verde: **91 tests, 219 aserciones**.
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
