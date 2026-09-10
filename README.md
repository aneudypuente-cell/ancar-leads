# AnCar Global Leads Finder

Sistema de prospección comercial de AnCar para encontrar, calificar y gestionar negocios que venden o financian celulares.

## Objetivo

Buscar prospectos a escala global, en español e inglés, con prioridad en negocios donde exista evidencia de celulares financiados, vendidos a crédito, en cuotas, installments, BNPL o modelos equivalentes.

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
- Esquema JSON normalizado para leads.

## Próxima fase

Conectar el motor de consultas a búsqueda externa y enriquecimiento para producir leads reales, guardar fuente/evidencia/fecha y calcular automáticamente el score comercial.
