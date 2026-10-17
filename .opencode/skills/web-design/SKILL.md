---
name: web-design
description: Mejora el diseño visual de bike-center (HTML/CSS/JS en public/ y views/). Usar cuando se pida mejorar la apariencia, el diseño, el responsive o la experiencia visual de una pantalla.
metadata:
  opencode/slash: "true"
---

## Alcance
- Solo tocá `public/css/`, `views/` y el HTML de `public/*.php`. No cambies
  lógica PHP, SQL ni validaciones.
- Stack obligatorio: HTML, CSS y poco JS. Sin frameworks, sin Tailwind ni
  Bootstrap, sin librerías nuevas.

## Reglas de diseño
- Mobile-first: se diseña primero para 375 px y después se amplía.
- Todos los colores, tamaños de letra y espaciados salen de variables CSS
  definidas en `:root` en `style.css`. Nada de valores sueltos repetidos.
- Escala de espaciado coherente (ej. 4/8/16/24/32 px) y máximo dos tipografías.
- Un solo estilo por componente (botón, input, card de moto, mensaje flash).
  Si ya existe, reutilizalo en vez de crear otro.
- Accesibilidad: contraste suficiente, `label` en cada campo, foco visible,
  `alt` en las imágenes, botones con texto claro.
- Diseñá todos los estados: vacío, error, cargando y moto vendida.
- Evitá el aspecto genérico: elegí una dirección visual (ej. deportiva,
  limpia, oscura) y mantenela en todas las pantallas.

## Proceso
1. Antes de editar, describí en 3 líneas la dirección visual y esperá mi OK.
2. Hacé cambios chicos, una pantalla por vez.
3. Verificá en 375 px y en 1280 px que no haya scroll horizontal ni
   elementos cortados.
4. Explicame qué cambiaste y por qué, porque estoy aprendiendo CSS.
5. Registrá las decisiones de diseño en `docs/design.md`.