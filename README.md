# AnCar Global Leads Finder

Sistema de prospección comercial de AnCar para descubrir, calificar y gestionar oportunidades en cualquier nicho.

## Objetivo

Buscar oportunidades a escala global, en español e inglés, usando señales de necesidad/intención comercial y evidencia pública verificable.

### Segmentos iniciales

El usuario puede cambiar el nicho y el tipo de cliente. El sistema no está limitado a seguros, celulares ni un sector concreto.

### Segmentos principales

- `tienda_celulares_financia` — tiendas de celulares que financian.
- `financiera_celulares` — financieras/crédito especializado en celulares o dispositivos.
- `electrodomesticos_financia_celulares` — tiendas de electrónica/electrodomésticos que también financian celulares.
- `bnpl_smartphones` — BNPL y crédito para smartphones.
- `telecom_financia_celulares` — operadores/retailers de telecom que financian dispositivos.

## Regla crítica

**No buscar "bloqueo de celulares" como criterio de descubrimiento.** El bloqueo/MDM es parte de la propuesta comercial de AnCar, no el perfil que define al prospecto.

La señal principal es: **el negocio vende o financia teléfonos/celulares mediante crédito, cuotas, plazos, BNPL o financiamiento.**

## Flujo

Prospectar → detectar evidencia de financiamiento → enriquecer → puntuar → clasificar → contactar → seguimiento → demo → cierre.

## Principios

- No inventar datos de prospectos.
- Separar datos verificados de datos pendientes de verificación.
- Usar únicamente información pública o fuentes autorizadas.
- Registrar fuente y fecha de cada dato.
- No enviar mensajes automáticamente sin una integración y autorización explícitas.
- Diferenciar venta de celulares de financiamiento real.
- Priorizar evidencia verificable de financiamiento.

## MVP actual

- Interfaz web inicial.
- Selector global por región.
- Cinco segmentos de prospectos.
- Diccionario bilingüe español/English.
- Generación de consultas de descubrimiento.
- Exportación CSV de consultas.
- Carga del dataset JSON normalizado de leads.
- Filtro por estado, país y texto libre.
- Ordenación por score comercial.
- Recalculo del score en tiempo de ejecución y al exportar.

## Scoring

El score máximo es 100 y se calcula con evidencia disponible en cada lead:

- +40 evidencia explícita de financiamiento de celulares/dispositivos.
- +20 cuotas, crédito, installments, finance o BNPL.
- +15 múltiples sucursales o red de dealers/partners/agentes.
- +15 contacto comercial público.
- +10 fuente identificable con fecha dentro de los últimos 30 días.

Los valores almacenados en el JSON no sustituyen el cálculo actual del frontend.

## Publicación

El repositorio incluye un workflow de GitHub Pages en `.github/workflows/deploy-pages.yml`. La última ejecución verificada falla en `Setup Pages` antes de `Upload site` y `Deploy` porque GitHub rechaza la configuración automática del sitio para el token de Actions. El código puede seguir desarrollándose sin considerar la publicación como completada.

## Motor web

La interfaz genera consultas por nicho, ubicación, tipo de cliente y señal de necesidad. `api/search.php` conecta con Exa Search, recupera resultados y evidencia, y extrae contactos públicos cuando aparecen en el contenido. Exa ofrece búsqueda web, contenido y salidas estructuradas para flujos de prospección. La API requiere `EXA_API_KEY` configurada como secreto del servidor, nunca en JavaScript público.

## Próxima fase

Configurar `EXA_API_KEY` en GoDaddy y verificar el endpoint en producción. Después validar búsquedas reales, deduplicación, scoring y exportación. La publicación debe verificarse mediante una prueba pública exitosa antes de considerarse operativa.
