# Tareas — Spec 01 (Bike Center)

Una tarea por vez. Al cerrar cada una: `php -l` en los archivos tocados, sus
tests en verde y registro en `docs/changelog.md`. Ver `plan.md` para el porqué
de cada decisión.

## Etapa 0 — Entorno y base

- [ ] **0.1 Composer y Pest**: `composer.json` con Pest en require-dev y script
  `composer test`; `vendor/` en `.gitignore`.
  RF: ninguno (constitución, principio 4).
  Hecho cuando: `composer test` corre en verde sin tests todavía y `git status`
  no muestra `vendor/`.

- [ ] **0.2 Esquema**: `sql/schema.sql` con `users`, `bikes` y `photos`, índices
  y borrado en cascada; crear la base `bike_center` en Docker y cargarla.
  RF: base de RF-1, RF-3.
  Hecho cuando: el script carga sin errores y las 3 tablas existen vacías en
  `bike_center`.

- [ ] **0.3 Base de tests**: `tests/Pest.php` recrea `bike_center_test` desde
  `sql/schema.sql` antes de cada tanda.
  RF: base (constitución, principio 4).
  Hecho cuando: `composer test` deja `bike_center_test` con las 3 tablas vacías
  antes de correr y en verde.

- [ ] **0.4 Conexión**: `src/db.php` con PDO (utf8mb4, excepciones) leyendo
  `src/config.php`.
  RF: base de todos.
  Hecho cuando: un script CLI imprime "conectado" contra `bike_center` y
  `bike_center_test`, y `php -l src/db.php` pasa.

- [ ] **0.5 Validaciones de cuenta**: `src/validation.php` con
  `normalize_email`, `validate_password` (≥ 8), `validate_full_name` (≥ 3) y
  `validate_phone` (10 dígitos, ignora espacios y guiones, devuelve
  `+54 11` + dígitos) + `tests/Unit/ValidationTest.php`.
  RF: RF-1.
  Hecho cuando: el test cubre cada caso válido e inválido del teléfono (letras,
  "+", 9 y 11 dígitos) y pasa en verde.

- [ ] **0.6 Validaciones de moto**: `validate_price` (coma o punto, hasta 2
  decimales, > 0), `validate_title` (≤ 80), `validate_description` (≤ 2000),
  `validate_currency`, `validate_condition` + tests.
  RF: RF-3.
  Hecho cuando: `composer test` cubre precio negativo, cero, 3 decimales y
  textos en el límite, y queda en verde.

- [ ] **0.7 Sesión y CSRF**: `src/session.php` (cookie httponly, sin `expires`)
  y `src/csrf.php` (`csrf_token`/`csrf_check` con `hash_equals`) + su test.
  RF: RF-2.
  Hecho cuando: el test confirma que un token correcto pasa y uno alterado no,
  y la cookie de sesión no tiene fecha de expiración.

## Etapa 1 — Registro y sesión (H2, H3)

- [ ] **1.1 Registro con persistencia**: `src/auth.php` con `register_user`
  (valida, email en minúsculas, `password_hash`, teléfono +54 11) +
  `tests/Integration/UserTest.php`.
  RF: RF-1.
  Hecho cuando: el test crea una cuenta, rechaza el correo duplicado sin
  importar mayúsculas y verifica el teléfono guardado con `+54 11`.

- [ ] **1.2 Pantalla de registro**: `public/register.php` + `views/header`,
  `footer`, `flash` con PRG y errores en español.
  RF: RF-1.
  Hecho cuando: a mano, registro válido entra logueado y el inválido (correo
  existente, contraseña corta, teléfono de 9 dígitos) muestra el motivo sin
  borrar lo tipeado.

- [ ] **1.3 Login y logout**: `login_user` en `auth.php`,
  `public/login.php`, `public/logout.php` + test de login.
  RF: RF-2.
  Hecho cuando: el mensaje de login fallido no dice cuál dato está mal, y
  cerrar sesión devuelve al usuario a la vitrina como visitante.

## Etapa 2 — Publicar (H4)

- [ ] **2.1 Almacén de fotos**: `src/storage.php` (tipo real con `finfo`,
  ≤ 2 MB, ≥ 200×200, nombre generado, `sha1` para repetidas) +
  `tests/Unit/StorageTest.php` con fixtures.
  RF: RF-3, RNF-5.
  Hecho cuando: el test rechaza un PDF renombrado a .jpg, una de 3 MB, una de
  100×100 y detecta la repetida por hash.

- [ ] **2.2 Modelos de moto y fotos**: `bike_model.php` (`create_bike`) y
  `photo_model.php` en transacción + `tests/Integration/BikeTest.php` y
  `PhotoTest.php` mínimos.
  RF: RF-3.
  Hecho cuando: el test crea una moto con 4 fotos, guarda la posición de cada
  foto y no queda registro huérfano si falla a mitad de camino.

- [ ] **2.3 Formulario de publicación**: `public/publish.php` +
  `views/bike_form.php`: datos + hasta 4 fotos, CSRF, PRG.
  RF: RF-3.
  Hecho cuando: se recorre a mano el criterio de "datos inválidos → no se guarda
  nada" y el de "fotos parciales → publica con las válidas y avisa".

## Etapa 3 — Vitrina y detalle (H1, H5)

- [ ] **3.1 Feed**: `bike_model.php` con `list_bikes_feed` (orden
  `created_at DESC, id DESC`, paginado por clave) +
  `tests/Integration/FeedTest.php`.
  RF: RF-4, RNF-3.
  Hecho cuando: el test pide 3 tandas de 20 sin repetir ni perder motos aun
  publicando nuevas en el medio, y editar una moto no cambia su posición.

- [ ] **3.2 Semilla**: `sql/seed.sql` con 4 usuarios y 27 motos (una vendida) y
  fotos copiadas de `tests/fixtures/` a `public/uploads/`; cargar en
  `bike_center`.
  RF: soporte de RF-4.
  Hecho cuando: contar en la base da 4 usuarios y 27 motos, y sus fotos abren
  en el navegador.

- [ ] **3.3 Pantalla vitrina**: `public/index.php` + `views/bike_card.php` con
  título, marca, precio con moneda, etiqueta de estado y estado vacío.
  RF: RF-4.
  Hecho cuando: con la semilla cargada se ven las 20 primeras ordenadas por
  fecha y la moto vendida muestra su etiqueta; sin motos, aparece el mensaje
  para publicar la primera.

- [ ] **3.4 Scroll infinito**: `public/api/bikes.php` (JSON con `has_more`) +
  `js/feed.js`.
  RF: RNF-3, H10.
  Hecho cuando: bajando el listado aparecen las 27 motos sin repetir y sin
  recargar la página.

- [ ] **3.5 Detalle**: `public/bike.php?id=` con datos completos solo para
  logueados, nombre del vendedor, fecha, HTML literal, "no disponible" para
  borradas.
  RF: RF-5.
  Hecho cuando: un visitante no logueado no ve ningún dato completo, el
  logueado ve todo, y una moto borrada muestra "no disponible".

## Etapa 4 — Gestión del dueño (H6–H9)

- [ ] **4.1 Mis publicaciones**: `public/my-bikes.php` con estado vacío y
  acciones por dueño.
  RF: RF-9.
  Hecho cuando: el dueño ve solo sus motos con su estado y un usuario sin
  sesión que abre la URL recibe el pedido de registro.

- [ ] **4.2 Editar**: `public/edit-bike.php?id=` con mismas validaciones y
  fotos de a una (1 a 4) + tests de edición.
  RF: RF-6.
  Hecho cuando: el test confirma que editar no mueve la moto en el feed y que
  una vendida se puede editar; a mano no se puede dejar en 0 fotos.

- [ ] **4.3 Borrar**: `public/bike_actions.php` para borrar (DB + archivos) +
  test de cascada y de usuario ajeno.
  RF: RF-7, RF-10.
  Hecho cuando: al borrar desaparecen la moto, sus registros de fotos y sus
  archivos, y el test de un usuario ajeno no logra borrar.

- [ ] **4.4 Estado vendida**: alternar disponible/vendida en
  `bike_actions.php` + test de reversibilidad y de doble marca.
  RF: RF-8, RF-10.
  Hecho cuando: el test alterna dos veces sin error y la vitrina muestra la
  etiqueta correcta; el usuario ajeno no tiene la opción ni le funciona la
  acción directa.

- [ ] **4.5 CSRF completo**: token en registro, publicar, editar, borrar y
  vender.
  RF: RF-1 a RF-10 (seguridad).
  Hecho cuando: enviar un POST sin token a cualquiera de esas acciones no
  produce ningún cambio.

## Etapa 5 — No funcionales (H10)

- [ ] **5.1 Responsive**: `public/css/style.css` para vitrina, detalle, panel y
  formularios.
  RF: RNF-2.
  Hecho cuando: a mano, en ancho de celular se usan las 4 pantallas sin
  desplazamiento horizontal.

- [ ] **5.2 Checklist manual**: segunda tanda en < 2 s, botón "atrás" tras
  cerrar sesión pide login, cerrar la app del navegador corta la sesión y dos
  dispositivos con sesión no se pisan.
  RF: RNF-3, RF-2.
  Hecho cuando: los 4 puntos se recorren en verde y queda anotado en
  `docs/changelog.md`.

## Etapa 6 — Cierre

- [ ] **6.1 Recorrido final**: recorrer los criterios de aceptación de RF-1 a
  RF-10 sobre la app con la semilla, `php -l` en todo PHP del proyecto y
  registro final en `docs/changelog.md` y `docs/decisions.md`.
  RF: todos.
  Hecho cuando: cada criterio de la spec está en verde y no queda ningún
  archivo sin lintear.
