# Tareas — Spec 01 (Bike Center)

Una tarea por vez. Al cerrar cada una: `php -l` en los archivos tocados, sus
tests en verde y registro en `docs/changelog.md`. Ver `plan.md` para el porqué
de cada decisión.

## Etapa 0 — Entorno y base

- [x] **0.1 Composer y Pest**: `composer.json` con Pest en require-dev y script
  `composer test`; `vendor/` en `.gitignore`.
  RF: ninguno (constitución, principio 4).
  Hecho cuando: `composer test` corre en verde sin tests todavía y `git status`
  no muestra `vendor/`.

- [x] **0.2 Esquema**: `sql/schema.sql` con `users`, `bikes` y `photos`, índices
  y borrado en cascada; crear la base `bike_center` en Docker y cargarla.
  RF: base de RF-1, RF-3.
  Hecho cuando: el script carga sin errores y las 3 tablas existen vacías en
  `bike_center`.

- [x] **0.3 Base de tests**: `tests/Pest.php` recrea `bike_center_test` desde
  `sql/schema.sql` antes de cada tanda.
  RF: base (constitución, principio 4).
  Hecho cuando: `composer test` deja `bike_center_test` con las 3 tablas vacías
  antes de correr y en verde.

- [x] **0.4 Conexión**: `src/db.php` con PDO (utf8mb4, excepciones) leyendo
  `src/config.php`.
  RF: base de todos.
  Hecho cuando: un script CLI imprime "conectado" contra `bike_center` y
  `bike_center_test`, y `php -l src/db.php` pasa.

- [x] **0.5 Validaciones de cuenta**: `src/validation.php` con
  `normalize_email`, `validate_password` (≥ 8), `validate_full_name` (≥ 3) y
  `validate_phone` (10 dígitos, ignora espacios y guiones, devuelve
  `+54 11` + dígitos) + `tests/Unit/ValidationTest.php`.
  RF: RF-1.
  Hecho cuando: el test cubre cada caso válido e inválido del teléfono (letras,
  "+", 9 y 11 dígitos) y pasa en verde.

- [x] **0.6 Validaciones de moto**: `validate_price` (coma o punto, hasta 2
  decimales, > 0), `validate_title` (≤ 80), `validate_description` (≤ 2000),
  `validate_currency`, `validate_condition` + tests.
  RF: RF-3.
  Hecho cuando: `composer test` cubre precio negativo, cero, 3 decimales y
  textos en el límite, y queda en verde.

- [x] **0.7 Sesión y CSRF**: `src/session.php` (cookie httponly, sin `expires`)
  y `src/csrf.php` (`csrf_token`/`csrf_check` con `hash_equals`) + su test.
  RF: RF-2.
  Hecho cuando: el test confirma que un token correcto pasa y uno alterado no,
  y la cookie de sesión no tiene fecha de expiración.

## Etapa 1 — Registro y sesión (H2, H3)

- [x] **1.1 Registro con persistencia**: `src/auth.php` con `register_user`
  (valida, email en minúsculas, `password_hash`, teléfono +54 11) +
  `tests/Integration/UserTest.php`.
  RF: RF-1.
  Hecho cuando: el test crea una cuenta, rechaza el correo duplicado sin
  importar mayúsculas y verifica el teléfono guardado con `+54 11`.

- [x] **1.2 Pantalla de registro**: `public/register.php` + `views/header`,
  `footer`, `flash` con PRG y errores en español.
  RF: RF-1.
  Hecho cuando: a mano, registro válido entra logueado y el inválido (correo
  existente, contraseña corta, teléfono de 9 dígitos) muestra el motivo sin
  borrar lo tipeado.

- [x] **1.3 Login y logout**: `login_user` en `auth.php`,
  `public/login.php`, `public/logout.php` + test de login.
  RF: RF-2.
  Hecho cuando: el mensaje de login fallido no dice cuál dato está mal, y
  cerrar sesión devuelve al usuario a la vitrina como visitante.

## Etapa 2 — Publicar (H4)

- [x] **2.1 Almacén de fotos**: `src/storage.php` (tipo real con `finfo`,
  ≤ 2 MB, ≥ 200×200, nombre generado, `sha1` para repetidas) +
  `tests/Unit/StorageTest.php` con fixtures.
  RF: RF-3, RNF-5.
  Hecho cuando: el test rechaza un PDF renombrado a .jpg, una de 3 MB, una de
  100×100 y detecta la repetida por hash.

- [x] **2.2 Modelos de moto y fotos**: `bike_model.php` (`create_bike`) y
  `photo_model.php` en transacción + `tests/Integration/BikeTest.php` y
  `PhotoTest.php` mínimos.
  RF: RF-3.
  Hecho cuando: el test crea una moto con 4 fotos, guarda la posición de cada
  foto y no queda registro huérfano si falla a mitad de camino.

- [x] **2.3 Formulario de publicación**: `public/publish.php` +
  `views/bike_form.php`: datos + hasta 4 fotos, CSRF, PRG.
  RF: RF-3.
  Hecho cuando: se recorre a mano el criterio de "datos inválidos → no se guarda
  nada" y el de "fotos parciales → publica con las válidas y avisa".

## Etapa 3 — Vitrina y detalle (H1, H5)

- [x] **3.1 Feed**: `bike_model.php` con `list_bikes_feed` (orden
  `created_at DESC, id DESC`, paginado por clave) +
  `tests/Integration/FeedTest.php`.
  RF: RF-4, RNF-3.
  Hecho cuando: el test pide 3 tandas de 20 sin repetir ni perder motos aun
  publicando nuevas en el medio, y editar una moto no cambia su posición.

- [x] **3.2 Semilla**: `sql/seed.sql` con 4 usuarios y 27 motos (una vendida) y
  fotos copiadas de `tests/fixtures/` a `public/uploads/`; cargar en
  `bike_center`.
  RF: soporte de RF-4.
  Hecho cuando: contar en la base da 4 usuarios y 27 motos, y sus fotos abren
  en el navegador.

- [x] **3.3 Pantalla vitrina**: `public/index.php` + `views/bike_card.php` con
  foto principal, título, marca, precio con moneda, etiqueta de estado y
  estado vacío.
  RF: RF-4.
  Hecho cuando: con la semilla cargada se ven las 20 primeras ordenadas por
  fecha con su foto, la moto vendida muestra su etiqueta; sin motos, aparece
  el mensaje para publicar la primera.

- [x] **3.4 Scroll infinito**: `public/api/bikes.php` (JSON con `has_more`) +
  `js/feed.js`.
  RF: RNF-3, H10.
  Hecho cuando: bajando el listado aparecen las 27 motos sin repetir y sin
  recargar la página.

- [x] **3.5 Detalle**: `public/bike.php?id=` con datos completos solo para
  logueados, nombre del vendedor, fecha, HTML literal, fotos en carrusel con
  botones ◀ ▶ (pedido del usuario) y "no disponible" para borradas.
  RF: RF-5.
  Hecho cuando: un visitante no logueado no ve ningún dato completo, el
  logueado ve todo con el carrusel funcionando, y una moto borrada muestra
  "no disponible".

## Etapa 4 — Gestión del dueño (H6–H9)

- [x] **4.1 Mis publicaciones**: `public/my-bikes.php` con estado vacío y
  acciones por dueño.
  RF: RF-9.
  Hecho cuando: el dueño ve solo sus motos con su estado y un usuario sin
  sesión que abre la URL recibe el pedido de registro.
  Verificado: el listado usa `list_bikes_by_user()` (solo las suyas, con foto
  principal y badge Disponible/Vendida), el estado vacío invita a publicar y
  un visitante recibe 302 a /login.php. Enlace "Mis publicaciones" en el
  header de todo usuario logueado.

- [x] **4.2 Editar**: `public/edit-bike.php?id=` con mismas validaciones y
  fotos de a una (1 a 4) + tests de edición.
  RF: RF-6.
  Hecho cuando: el test confirma que editar no mueve la moto en el feed y que
  una vendida se puede editar; a mano no se puede dejar en 0 fotos.
  Verificado: `update_bike()` no toca created_at ni status (test y curl);
  quitar foto con botón propio por foto, bloqueado al ser la última; fotos
  nuevas contra las guardadas por hash (`photo_batch()` con los hashes
  existentes) y cupo 4 en total; un ajeno ni llega al formulario (302 con
  flash "Solo el dueño...").

- [x] **4.3 Borrar**: `public/bike_actions.php` para borrar (DB + archivos) +
  test de cascada y de usuario ajeno.
  RF: RF-7, RF-10.
  Hecho cuando: al borrar desaparecen la moto, sus registros de fotos y sus
  archivos, y el test de un usuario ajeno no logra borrar.
  Verificado: `delete_bike_as_owner()` borra la moto (cascada de photos en el
  esquema) y después los archivos del disco; curl: moto borrada, filas y
  archivo desaparecidos, detalle en "no disponible"; el ajeno recibe error y
  todo queda igual.

- [x] **4.4 Estado vendida**: alternar disponible/vendida en
  `bike_actions.php` + test de reversibilidad y de doble marca.
  RF: RF-8, RF-10.
  Hecho cuando: el test alterna dos veces sin error y la vitrina muestra la
  etiqueta correcta; el usuario ajeno no tiene la opción ni le funciona la
  acción directa.
  Verificado: `toggle_bike_status_as_owner()` alterna sold ↔ available (tests
  de reversibilidad y de doble marca); curl: dos POST seguidos sin error,
  badge Vendida/Disponible correcto en tarjeta y detalle; el POST directo del
  ajeno no cambia nada.

- [x] **4.5 CSRF completo**: token en registro, publicar, editar, borrar y
  vender.
  RF: RF-1 a RF-10 (seguridad).
  Hecho cuando: enviar un POST sin token a cualquiera de esas acciones no
  produce ningún cambio.

- [x] **4.6 Recuadros de fotos** (pedido del usuario): publicar y editar
  muestran recuadros clicables (uno por foto, con vista previa) y editar
  ofrece "Reemplazar esta foto" cuando solo queda una.
  RF: RF-3, RF-6.
  Hecho cuando: se pueden elegir hasta 4 fotos, una por recuadro, en
  cualquier navegador; la vista previa se ve con JavaScript y sin él igual
  se envían las fotos; y la moto de una sola foto se puede reemplazar sin
  quedarse sin fotos. Verificado con curl: 4 recuadros en publicar (4 fotos
  guardadas en posiciones 0-3), recuadros libres = 4 − las actuales en
  editar, reemplazo conserva id/posición y borra el archivo viejo, repetida
  rechazada y POST sin token sin cambio.
  Verificado con curl en las cinco acciones: sin token, ninguna cambia la
  base (motos, fotos, usuarios, título y estado intactos) y todas muestran
  "El formulario expiró o no era válido"; GET a bike_actions.php redirige sin
  acción.

## Etapa 5 — No funcionales (H10)

- [x] **5.1 Responsive**: `public/css/style.css` para vitrina, detalle, panel y
  formularios.
  RF: RNF-2.
  Hecho cuando: a mano, en ancho de celular se usan las 4 pantallas sin
  desplazamiento horizontal.
  Verificado: solo CSS (el viewport ya existía en el header y todo el resto
  ya era fluido: grillas con `1fr`, imágenes con `width: 100%` y el main con
  padding). Ajustes de la etapa 5: campos (`input`, `select`, `textarea`)
  heredan la tipografía (evita el zoom de iOS al enfocar), navegación del
  encabezado que se envuelve a segunda fila hasta 480px, ✕ de quitar foto de
  2.5rem en celular para el dedo, textos largos sin espacios con
  `overflow-wrap` (títulos y descripción) y pie con estilo propio. Se pidió
  revisar a mano en el navegador del usuario.

- [x] **5.2 Checklist manual**: segunda tanda en < 2 s, botón "atrás" tras
  cerrar sesión pide login, cerrar la app del navegador corta la sesión y dos
  dispositivos con sesión no se pisan.
  RF: RNF-3, RF-2.
  Hecho cuando: los 4 puntos se recorren en verde y queda anotado en
  `docs/changelog.md`.
  Verificado: (1) la segunda tanda del scroll infinito tardó 0.003 s y
  0.002 s en dos corridas contra `/api/bikes.php` (límite: 2 s); (2) las 4
  pantallas privadas mandan `Cache-Control: no-store` (nuevo, en
  `require_login_or_redirect()`; PHP ya manda su `nocache` de sesión y esto
  lo deja explícito a prueba de configuración), así que el "atrás" vuelve a
  pedirlas al servidor y el guard manda al login; (3) la cookie de sesión no
  tiene Expires ni Max-Age (`Set-Cookie: PHPSESSID=...; path=/; HttpOnly;
  SameSite=Lax`), o sea que cerrar la aplicación del navegador la corta; (4)
  dos sesiones independientes de ana en jars distintos: cerrar sesión en uno
  deja al otro en 200 y funcionando. El paso 2 y el 3 se completan a mano en
  el navegador (atrás real y cierre real de la app).

## Etapa 6 — Cierre

- [x] **6.1 Recorrido final**: recorrer los criterios de aceptación de RF-1 a
  RF-10 sobre la app con la semilla, `php -l` en todo PHP del proyecto y
  registro final en `docs/changelog.md` y `docs/decisions.md`.
  RF: todos.
  Hecho cuando: cada criterio de la spec está en verde y no queda ningún
  archivo sin lintear.
  Verificado (2026-10-17): los 38 archivos PHP del proyecto pasan `php -l`
  (0 errores) y la suite cierra en verde con **91 tests, 219 aserciones**.
  Recorrido por curl sobre la app con la semilla (RF-1 a RF-10, cada punto
  de la sección 4): registro válido con teléfono `+54 11` + 10 dígitos y
  sesión iniciada; duplicado con mayúsculas y los 5 inválidos (nombre, clave
  corta, correo, teléfono de 9 y con "+") rechazados con su aviso; login
  con mensaje genérico; logout, acción sin sesión y cookie de sesión sin
  expiración; publicación válida primera en la vitrina y datos inválidos
  sin guardar nada; fotos parciales (repetida, no-imagen, 3 MB y 100×100
  rechazadas con motivo por flash) y cupo de 4; vitrina con 20 tarjetas en
  orden `created_at DESC, id DESC` y vendida con su etiqueta; vitrina vacía
  (contra `bike_center_test`); detalle completo para logueado, 302 a login
  para visitante y "no disponible" para borrada; edición sin mover la moto
  ni tocar `created_at`/`status`, mismas validaciones, fotos entre 1 y 4 con
  reemplazo de la única y venta que no bloquea editar; borrar propio borra
  filas (cascada) y archivos y saca la moto de la vitrina; vendida
  reversible; "Mis publicaciones" solo con las propias y estado vacío; y
  toda acción directa sobre moto ajena con CSRF válido sin ningún cambio
  (RF-10). RNF-4 verificado en pantalla (teléfono y contraseña nunca
  visibles) y RNF-6 sin conversión de moneda.
  Incidentes de datos detectados y corregidos durante el recorrido: la moto
  1 había quedado con la descripción doble-codificada por un UPDATE hecho
  sin `--default-character-set=utf8mb4` (restaurada con el charset correcto)
  y faltaba `seed_01.jpg` en `public/uploads/` (regenerado desde
  `tests/fixtures/valid_200x200.jpg`; la moto 1 volvió a su estado exacto
  de semilla). La única foto sin archivo es la de la "Ninja 500se" del
  usuario, perdida por un borrado mío indebido durante el chequeo: su
  dueño debe reemplazarla desde la edición (ver `docs/changelog.md`).