# SIPRI Analyzer

Herramienta para descargar, extraer y analizar convocatorias de adjudicación de plazas docentes de la Junta de Andalucía publicadas en el sistema SIPRI (Sistema de Información de Personal de la Región de Andalucía). Genera un sitio web estático con estadísticas navegables por especialidad, provincia y curso académico.

## Descripción del dominio

- **Convocatoria**: cada proceso de adjudicación publicado en SIPRI, identificada por un número entero (1–459). Se mapea a un curso académico según rangos predefinidos (2018–2025).
- **Plaza**: puesto ofertado en una convocatoria. Puede ser vacante (`VACANTE`) o sustitución (`SUSTITUCION`), obligatoria o voluntaria.
- **Adjudicación**: asignación de una plaza a un aspirante, identificada por su número de orden en la lista.
- **Cuerpo → Especialidad → Plaza**: jerarquía del catálogo docente (p. ej. cuerpo 590 → Matemáticas).
- **Centro → Localidad → Provincia**: jerarquía geográfica del puesto.

El pipeline completo es: descarga de PDF → extracción de tablas (tabula-py) → persistencia en SQLite → generación del sitio estático.

## Stack tecnológico

| Capa           | Tecnología                                                 |
|----------------|------------------------------------------------------------|
| Framework      | Symfony 7.3 (PHP ≥ 8.4)                                    |
| Servidor       | FrankenPHP (Caddy + PHP embebido)                          |
| ORM            | Doctrine ORM 3 con SQLite                                  |
| Extracción PDF | tabula-py (Python 3 + Java 17)                             |
| Parse PDF      | smalot/pdfparser, spatie/pdf-to-text                       |
| Scraping       | Guzzle 7 + Symfony DomCrawler                              |
| Frontend       | Twig + Bootstrap 5.3 + Symfony UX (Chart.js, Autocomplete) |
| Assets         | Symfony AssetMapper (importmap, sin Webpack)               |
| Sitio estático | Stenope (genera HTML en `./docs/`)                         |
| Contenedores   | Docker Compose (imagen `dunglas/frankenphp:1-php8.5`)      |

## Estructura del proyecto

```
src/
  Command/          # Comandos de consola (pipeline principal)
  Controller/       # Controladores web (vistas y stats)
  Dto/              # Objetos de transferencia para parsing
  Entity/           # Entidades Doctrine (Convocatoria, Plaza, Adjudicacion, ...)
  Enum/             # Enums PHP (TipoPlazaEnum, TipoProcesoEnum, ...)
  Repository/       # Repositorios Doctrine
  Service/
    DtoToEntity/    # Conversores DTO → entidad con upsert
    ScrapperService # Parsing de filas extraídas por tabula
    TabulaPythonService # Wrapper que invoca scripts Python
    ChartService    # Construcción de datos para Chart.js
  Twig/             # Extensiones Twig (ProvinciaExtension)
bin/                # Scripts Python de tabula (tabula-plazas.py, tabula-adjudicaciones.py)
templates/          # Plantillas Twig
config/             # Configuración Symfony (packages/, routes.yaml, services.yaml)
migrations/         # Migraciones Doctrine
var/                # Bases de datos SQLite (data_dev.db, data_prod.db)
docs/               # Salida del sitio estático (publicada en GitHub Pages)
pdfs/               # PDFs descargados organizados por convocatoria
```

## Base de datos

SQLite con tres archivos según entorno:
- `var/data_dev.db` — desarrollo
- `var/data_prod.db` — producción / sitio estático

El esquema lo gestionan las migraciones Doctrine. Para aplicarlas:

```bash
bin/console doctrine:migrations:migrate --no-interaction
```

## Comandos del pipeline

Los comandos están en `src/Command/` y se invocan como `bin/console sipri:<subcomando>`:

| Comando                  | Alias CLI   | Descripción                                                       |
|--------------------------|-------------|-------------------------------------------------------------------|
| `sipri:get-convocatoria` | `sipri:get` | Descarga los PDFs de una convocatoria desde SIPRI                 |
| `sipri:extraer-plazas`   | `sipri:ext` | Extrae plazas del PDF de convocatoria y las persiste              |
| `sipri:adj`              | —           | Extrae adjudicaciones del PDF y las vincula a plazas              |
| `sipri:del`              | —           | Elimina convocatorias (y opcionalmente adjudicaciones o archivos) |
| `sipri:og-images`        | —           | Genera las imágenes de previsualización en `public/og/` (requiere `ext-gd`) |

Ejemplo de procesado completo en bucle:
```bash
for i in {1..10}; do
    php bin/console sipri:get "$i"
    php bin/console sipri:ex "$i"
    php bin/console sipri:adj "$i"
    sleep 5
done
```

## Generación del sitio estático

```bash
cp var/data_dev.db var/data_prod.db
bin/console sipri:og-images
bin/console asset-map:compile
bin/console -e prod cache:clear
bin/console -e prod stenope:build \
    --host=acardielf.github.io \
    --base-url=/sipri_analyzer \
    --scheme=https \
    --no-sitemap \
    ./docs
rm public/assets/ -rf
```

También disponible como script de Composer: `composer run generate-static`.

Stenope copia `public/` completo a `docs/`, así que las imágenes de `public/og/` acaban publicadas en `docs/og/` sin configuración adicional.

### Minificación del HTML

El workflow `ci.yml` minifica `docs/` tras generarlo con [minify-html](https://github.com/wilsonzlin/minify-html) (binario `minhtml`, Rust). Reduce los ~1,6 GB de HTML a algo menos de la mitad en segundos, lo que frena el crecimiento del historial de git. Se ejecuta con `--keep-closing-tags --keep-html-and-head-opening-tags`, y un paso posterior comprueba con un parser HTML que las meta etiquetas Open Graph siguen siendo legibles.

No se aplica en el build local: es un paso exclusivo del CI, sobre la carpeta ya generada.

## Previsualización al compartir enlaces (Open Graph)

`base.html.twig` genera `description`, `canonical`, Open Graph y Twitter Cards para todas las páginas. Cada plantilla define dos bloques:

- `{% block meta_description %}` — descripción específica de la página. **Sin cifras**: quedarían desfasadas y obligarían a regenerar el HTML de las 16.000 páginas en cada build.
- `{% block og_image %}` — ruta de la imagen dentro de `public/`, una por sección (`og/especialidad.png`, `og/convocatoria.png`, `og/centro.png`, `og/cuerpo.png`, `og/faq.png`, `og/default.png`).

La URL canónica se compone con `url()` del router, **no** con `absolute_url(app.request.pathInfo)`: pathInfo no incluye el `--base-url` del build (`/sipri_analyzer`) y la URL saldría mal.

## Entorno de desarrollo con Docker

```bash
make build   # Construye las imágenes (FrankenPHP)
make up      # Arranca los contenedores en modo detached
make sh      # Shell dentro del contenedor
make down    # Para y elimina los contenedores
```

Variables de entorno relevantes (`.env`):
- `DATABASE_URL` → `sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db`
- `APP_ENV` → `dev` / `prod`
- `XDEBUG_MODE` → `debug` (para depuración con Xdebug)

Para depurar un comando con Xdebug:
```bash
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console <comando>
```

## Sistema de diseño (Design tokens)

Los tokens CSS están definidos en `assets/styles/app.css` bajo `:root`. **No usar valores hardcodeados**; usar siempre las variables CSS del sistema.

| Token                  | Valor             | Uso                                      |
|------------------------|-------------------|------------------------------------------|
| `--sipri-blue`         | `#1a5f28`         | Verde oscuro USTEA — color principal     |
| `--sipri-blue-mid`     | `#2e8636`         | Verde medio — hover, acentos activos     |
| `--sipri-blue-light`   | `#4aaa58`         | Verde claro — degradados, iconos         |
| `--sipri-accent`       | `#c41111`         | Rojo rayo USTEA — CTAs primarios         |
| `--sipri-bg`           | `#f2f7f3`         | Fondo general con tinte verde sutil      |
| `--sipri-surface`      | `#ffffff`         | Fondo de tarjetas y modales              |
| `--sipri-text`         | `#1a202c`         | Texto principal                          |
| `--sipri-muted`        | `#6b7280`         | Texto secundario / placeholders          |
| `--sipri-border`       | `#ddeee0`         | Bordes de componentes                    |
| `--sipri-shadow-sm`    | `0 1px 4px …`     | Sombra sutil de tarjetas                 |
| `--sipri-shadow`       | `0 6px 24px …`    | Sombra elevada (hover, modales)          |
| `--sipri-radius`       | `.75rem`          | Radio de esquinas estándar               |
| `--sipri-radius-sm`    | `.4rem`           | Radio pequeño (badges, botones inline)   |
| `--t`                  | `.18s ease`       | Duración base de transiciones CSS        |

**Color de favoritos/estrella**: `#f0b429` (ámbar, sin variable propia — consistente en todos los componentes de estrella).

### Color por tipo de plaza

El verde de marca está reservado a identidad y navegación (códigos, enlaces, totales, adjudicaciones), así que cada tipo de plaza tiene su propio color y se distingue sin leer la etiqueta:

| Token                       | Valor     | Uso                                        |
|-----------------------------|-----------|--------------------------------------------|
| `--sipri-vacante`           | `#b8860b` | Ámbar oscuro — cifras y texto sobre blanco |
| `--sipri-vacante-bg`        | `#ffc107` | Ámbar — fondo de `.badge-vacante`          |
| `--sipri-vacante-soft`      | `#fff3cd` | Ámbar claro — fondo de `.adj-chip--v`      |
| `--sipri-vacante-ink`       | `#212529` | Texto sobre fondo ámbar                    |
| `--sipri-sustitucion`       | `#1d6fa3` | Azul acero — cifras y texto sobre blanco   |
| `--sipri-sustitucion-bg`    | `#1d6fa3` | Azul acero — fondo de `.badge-sustitucion` |
| `--sipri-sustitucion-soft`  | `#e1eff8` | Azul claro — fondo de `.adj-chip--s`       |

Componentes que los aplican: `.badge-vacante` / `.badge-sustitucion` (tablas de plazas), `.adj-chip--v` / `.adj-chip--s` (rejilla del tab «¿Cuándo voy a currar?»), `.cell-b-vac` / `.cell-b-sust` (celda de la tabla de especialidad) y las tarjetas de `_stats_plazas.html.twig`. **Nunca hardcodear estos colores**: `buscador_controller.js` y `calendario_convocatorias_controller.js` generan HTML en cliente y deben usar las mismas clases.

**Nota sobre Docker**: Cambios en PHP/Twig requieren `docker restart sipri` para invalidar OPcache. El directorio del proyecto se monta en `/app` vía bind mount (definido en `compose.override.yaml`, no en `compose.yaml`).

## Notas de arquitectura relevantes

- El sitio está pensado para ser publicado (carpeta `docs/`) de forma estática sin acceso a ningún backend, así que todas las decisiones deben tener en cuenta esto.
- La extracción de tablas de PDF se realiza mediante scripts Python (`bin/tabula-*.py`) invocados desde `TabulaPythonService` vía `shell_exec`. La convocatoria 1 usa un script diferente (`tabula-adjudicaciones-no-agrupadas.py`) por formato histórico.
- La identidad de una `Plaza` se determina por un hash SHA-256 de sus campos clave para detectar duplicados entre ejecuciones.
- `ConvocatoriaConfigurationTrait` contiene la lógica que mapea número de convocatoria → curso académico, incluyendo la lista de convocatorias ausentes (p. ej. la 73, cancelada por COVID-19).
- Los cuerpos docentes (511–597) están sembrados mediante migración (`Version20250729100000`).
- El sitio generado se publica en GitHub Pages en `acardielf.github.io/sipri_analyzer`.
