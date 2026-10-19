# Design — Bike Center

Sistema de diseño del sitio. Lo aplica `public/css/style.css`; cualquier
cambio visual nuevo debe salir de estas variables y registrarse acá.
Dirección elegida con el usuario: **"taller y ruta en frío"** (2026-10-19),
tras rechazar el acento ámbar cálido (no combinaba con la paleta y es,
además, uno de los "tales" de diseño genérico que marca la skill
`frontend-design`).

## Dirección visual (resumen en 3 líneas)
1. **Azul noche `#10243e` asume toda la acción**: header, botones,
   títulos y enlaces. Es la marca y no compite con nada.
2. **Un único acento petróleo `#0f6b70`** (tinta de manual de taller,
   frío, hermano del navy): reservado **solo** a precios, foco y hovers.
   Nada cálido en el sitio.
3. **Superficies planas**: blanco + borde hairline, sin sombras ni
   elevación de hover; la foto y el precio (petróleo, extra-bold) son lo
   único que grita. Mobile-first a 375 px.

## Tokens (`:root` en style.css)
- **Paleta**: `--color-ink` (texto), `--color-ink-soft` (secundario),
  `--color-navy` / `--color-navy-soft` (marca, botones, títulos),
  `--color-accent` (petróleo: precios, foco, hovers),
  `--color-accent-strong` (petróleo oscuro: enlaces de acción),
  `--color-bg`, `--color-surface`, `--color-line`, `--color-ok`(+`-bg`),
  `--color-danger`(+`-bg`), `--focus-ring`.
- **Espaciado**: escala 4 / 8 / 16 / 24 / 32 → `--space-1` a `--space-5`.
- **Tipografía**: `--font-sans` (system-ui), `--text-sm/base/lg/price`;
  pesos 400 / 600 / 800.
- **Formas**: `--radius-sm/md/pill`. Sin tokens de sombra: no se usan.

## Decisiones de diseño
- **El ámbar se probó y se rechazó** (feedback del usuario, 2026-10-19):
  no combinaba con el navy claro del sitio. La guía `frontend-design`
  coincide: el acento cálido tipo terracota es señal de diseño generado.
  Reemplazo: petróleo frío, análogo del navy.
- **Botones navy, precios petróleo**: el acento no se gasta en botones
  (ahí alcanza con la marca); se gasta en el precio, que es lo que el
  comprador busca primero. Blanco sobre navy y sobre petróleo pasan AA
  sobradísimos.
- **Superficies planas**: sin `box-shadow` y sin hover de elevación en las
  tarjetas (también señal de template genérico, según la skill). Borde
  hairline de 1px cumple el mismo rol estructural con menos ruido.
- **Errores de campo ≠ avisos de página**: abajo de cada input el error es
  texto rojo (`.error`); los mensajes por flash son banners (`.flash`,
  fondo suave + barra de color lateral).
- **Vendida se "apaga"**: además de la etiqueta roja, la foto de la
  tarjeta vendida se atenúa (`filter: grayscale` vía `:has`); sin `:has`,
  la etiqueta sola comunica el estado.
- **Header que se envuelve**: en celular la navegación pasa a segunda fila
  (regla base) y desde 640 px vuelve a una fila (mobile-first).
- **Marca con punto petróleo**: `brand::before` es el único guiño
  decorativo del sitio.
- **Foco visible global**: aro petróleo (`--focus-ring`) con
  `:focus-visible` en todo lo interactivo (accesibilidad con Teclado).
- **Movimiento solo como respuesta**: transiciones únicamente en botones
  (hover/press) y foco; nada que se mueva sin que el usuario actúe.

## Reglas para estilos nuevos
- Nada de colores o espaciados sueltos: usar variables (agregar en `:root`
  si falta una).
- Un estilo por componente: si ya existe `.button`, `.card`, `.bike-card`
  o `.badge`, reutilizarlo con sus modificadores (`-secondary`, `-danger`,
  `-sold`, `-available`).
- Verificar siempre a 375 px y 1280 px sin scroll horizontal.
- Estados a cubrir en cada componente nuevo: normal, foco, hover (si hay
  mouse), vacío, error y vendida cuando aplique.
- Antes de agregar una sombra, un degradado o una transición de entrada,
  preguntarse si es una decisión o el default del template (guía de la
  skill `frontend-design`).
