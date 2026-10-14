# Constitución del proyecto Bike Center

1. **Stack simple**: la app es PHP puro + MySQL + HTML/CSS/JS, sin frameworks ni librerías en producción; Composer solo como herramienta de desarrollo (testing).
2. **Spec → código**: todo cambio responde a una historia de usuario de `specs/`; sin historia aprobada no hay código, y el código siempre respeta la spec vigente.
3. **Lógica separada de la interfaz**: el SQL y la lógica viven en `src/`; `public/` y `views/` solo presentan datos (verificable: ningún `SELECT`/`INSERT` en vistas).
4. **Tests con Pest**: toda historia deja su test en `tests/` que pasa con `composer test`; la lógica en `src/` debe ser testeable sin servidor web y lo que toca BD usa `bike_center_test`. Lo visual y de rendimiento se verifica con recorrido manual, no con tests automatizados.
5. **Persistencia**: todo dato de negocio vive en MySQL, accedido desde `src/` con PDO y consultas preparadas; nada crítico vive solo en sesión ni en archivos planos. Las fotos son archivos en disco y en la base solo se guarda su referencia.
6. **Idioma**: identificadores, funciones y tablas en inglés; comentarios, documentación y mensajes al usuario en español.