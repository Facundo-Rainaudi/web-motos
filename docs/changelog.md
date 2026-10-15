# Changelog

## 2026-10-15
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
