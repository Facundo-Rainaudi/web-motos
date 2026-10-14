# Changelog

## 2026-10-14
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
