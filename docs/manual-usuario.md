# Manual de Usuario — ERP Agropecuario

**Versión:** 1.0  
**Fecha:** Junio 2026  

---

## Índice

1. [Introducción y acceso al sistema](#1-introducción-y-acceso-al-sistema)
2. [Pantalla principal (Dashboard)](#2-pantalla-principal-dashboard)
3. [Configuración inicial](#3-configuración-inicial)
4. [Módulo: Establecimientos y Lotes](#4-módulo-establecimientos-y-lotes)
5. [Módulo: Agricultura](#5-módulo-agricultura)
6. [Módulo: Ganadería](#6-módulo-ganadería)
7. [Módulo: Feedlot](#7-módulo-feedlot)
8. [Módulo: Insumos](#8-módulo-insumos)
9. [Módulo: Compras](#9-módulo-compras)
10. [Módulo: Ventas](#10-módulo-ventas)
11. [Módulo: Finanzas](#11-módulo-finanzas)
12. [Módulo: RRHH](#12-módulo-rrhh)
13. [Módulo: Reportes](#13-módulo-reportes)
14. [Módulo: Sistema](#14-módulo-sistema)
15. [Casos de uso frecuentes](#15-casos-de-uso-frecuentes)
16. [Preguntas frecuentes](#16-preguntas-frecuentes)

---

## 1. Introducción y acceso al sistema

### ¿Qué es este sistema?

El ERP Agropecuario es una plataforma de gestión integral diseñada para establecimientos rurales. Permite gestionar en un solo lugar:

- La producción agrícola y ganadera
- El stock de insumos
- Las compras a proveedores y ventas de productos
- Las finanzas y flujo de caja
- El personal y jornales
- Los reportes productivos, económicos y fiscales

### Acceso

1. Ingresar la URL del sistema en el navegador (Chrome, Firefox o Edge recomendados)
2. Completar email y contraseña
3. Hacer clic en **Ingresar**

> **Si olvidaste tu contraseña:** Contactar al administrador del sistema para que la restablezca desde el módulo de Usuarios.

### Roles y permisos

Cada usuario tiene un **rol** que determina qué puede ver y hacer:

| Rol | Descripción |
|-----|-------------|
| **Administrador del sistema** | Acceso total. Gestiona usuarios, roles y configuración. |
| **Gerente / Dueño** | Ve todos los módulos, puede aprobar operaciones críticas. |
| **Capataz** | Registra operaciones de campo (ganadería, agricultura, feedlot, jornales). |
| **Administrativo** | Gestiona compras, ventas, finanzas y RRHH. |
| **Asesor técnico** | Solo lectura de historiales productivos. |
| **Auditor** | Solo lectura de reportes y auditoría. |

Si no ves algún módulo o botón, es porque tu rol no tiene permiso. Contactar al administrador.

---

## 2. Pantalla principal (Dashboard)

El dashboard es la pantalla de inicio. Se actualiza en tiempo real y muestra solo la información a la que tenés acceso según tu rol.

### Franja de bienvenida

Muestra el saludo según el horario, tu nombre, rol, empresa y la fecha/hora actual.

### Alertas (barra amarilla/roja)

Aparece automáticamente si hay:
- **Insumos bajo stock mínimo** — click en la alerta para ir al catálogo
- **Compras vencidas sin pagar** — click para ir al módulo de compras

Atender estas alertas es prioritario.

### KPI Cards

Seis indicadores clave en la parte superior:

| Card | Qué muestra | Dónde lleva |
|------|-------------|-------------|
| **Hacienda** | Total de animales activos | Módulo Ganadería → Animales |
| **Agricultura** | Hectáreas sembradas activas | Módulo Agricultura → Siembras |
| **Ventas del mes** | Total ventas (granos + hacienda) del mes actual | — |
| **Compras** | Cantidad de compras pendientes de pago | Módulo Compras |
| **Saldo cuentas** | Saldo total de todas las cuentas activas | Módulo Finanzas → Cuentas |
| **Jornales** | Importe pendiente de liquidar | Módulo RRHH → Jornales |

### Panel central

- **Últimas operaciones** (tabs): últimas 6 ventas de granos, hacienda y compras
- **Siembras en curso**: tabla con las siembras activas

### Panel derecho

- **Flujo del mes**: ingresos vs egresos del mes actual con barra de progreso
- **Stock por categoría**: desglose del rodeo con barras de progreso por categoría
- **Insumos bajo mínimo**: detalle de los insumos en alerta

---

## 3. Configuración inicial

Antes de empezar a cargar datos operativos, hacer estos pasos en orden.

### 3.1 Configurar la empresa

**Sistema → Empresa**

Completar:
- **Razón social**: nombre legal de la empresa
- **CUIT**: con formato `XX-XXXXXXXX-X` (ej: `20-12345678-9`)
- **Condición fiscal**: Responsable Inscripto, Monotributista, etc.
- **Domicilio fiscal**: dirección legal
- **Moneda principal**: ARS o USD

Esta información aparece en los reportes fiscales.

### 3.2 Crear los establecimientos

**Menú → Establecimientos** (o desde Admin)

Cada campo o establecimiento físico es una unidad de gestión independiente. Crear uno por cada campo o fracción que se gestione por separado.

Datos a cargar:
- Nombre del establecimiento
- Provincia, partido, localidad
- Superficie total, agrícola y ganadera (en ha)
- Tipo de tenencia (propietario, arrendatario, etc.)

### 3.3 Crear usuarios y roles

**Admin → Usuarios**

Para cada persona que va a usar el sistema:
1. Completar nombre, apellido, email y contraseña inicial
2. Asignar el rol correspondiente
3. Asignar los establecimientos a los que tiene acceso

### 3.4 Crear proveedores

**Compras → Proveedores**

Cargar todos los proveedores habituales antes de registrar compras:
- Nombre, razón social, CUIT
- Rubro (semillas, agroquímicos, veterinaria, combustible, servicios, etc.)
- Teléfono, email, dirección

> **Tip:** Los proveedores de servicios públicos también van acá: EPE, Litoral Gas, ASSA, etc.

### 3.5 Crear las cuentas bancarias/caja

**Finanzas → Cuentas**

Crear una cuenta por cada instrumento financiero:
- Cuenta bancaria corriente
- Caja chica
- Tarjeta de débito
- etc.

Ingresar el **saldo inicial** al momento de comenzar a usar el sistema.

---

## 4. Módulo: Establecimientos y Lotes

### Establecimientos

**Menú → Establecimientos**

Funciones:
- Ver todos los establecimientos de la empresa
- Crear / editar establecimientos
- Activar / desactivar

### Lotes

**Menú → Lotes** (dentro de Campos)

Cada establecimiento se divide en **lotes** o potreros. Los lotes son la unidad mínima de gestión agrícola y ganadera.

**Para crear un lote:**
1. Clic en **Nuevo lote**
2. Asignar al establecimiento correspondiente
3. Ingresar nombre (ej: "Lote 1", "Potrero Norte", "La Cuchilla")
4. Superficie en hectáreas
5. Tipo: agrícola, ganadero o mixto
6. Coordenadas geográficas (opcional, para mapas)

> Los lotes son necesarios para poder registrar siembras, labores y cosechas.

---

## 5. Módulo: Agricultura

### Flujo de trabajo agrícola

```
Crear Campaña → Crear Siembra en un Lote → Registrar Labores → Registrar Cosecha
```

### 5.1 Campañas

**Agricultura → Campañas**

Una campaña agrícola es el período productivo (ej: "Campaña 2025/2026").

**Para crear una campaña:**
1. Nombre: "2025/2026" o "Campaña Fina 2025"
2. Fechas de inicio y fin
3. Descripción (opcional)

### 5.2 Siembras

**Agricultura → Siembras**

Registra qué se sembró, en qué lote y en qué campaña.

**Para registrar una siembra:**
1. Clic en **Nueva siembra**
2. Seleccionar **Campaña** y **Lote**
3. Seleccionar **Cultivo**: soja, maíz, trigo, girasol, sorgo, cebada, avena, etc.
4. Ingresar **Variedad** (ej: "DM 4615", "AW 197")
5. **Fecha de siembra**
6. **Superficie sembrada** en ha (puede ser menor a la superficie total del lote)
7. **Densidad de siembra** (opcional, kg/ha o semillas/m²)
8. **Estado**: Planificada → Sembrada → En cultivo → Cosechada / Perdida

> Cambiar el estado manualmente a medida que avanza el ciclo del cultivo.

### 5.3 Labores

**Agricultura → Labores**

Registra las intervenciones realizadas sobre cada siembra.

**Para registrar una labor:**
1. Seleccionar el **Lote** y la **Siembra** correspondiente
2. Tipo de labor: Pulverización, Fertilización, Rastra, Arado, Riego, Control de plagas, etc.
3. **Fecha** de realización
4. **Superficie trabajada** en ha
5. Descripción del producto aplicado, dosis, maquinaria usada (en campo Descripción)

> Registrar cada labor permite llevar el historial completo de intervenciones por lote y campaña.

### 5.4 Cosechas

**Agricultura → Cosechas**

Registra el resultado de la cosecha de cada siembra.

**Para registrar una cosecha:**
1. Seleccionar la **Siembra** que se cosechó
2. **Fecha de cosecha**
3. **Superficie cosechada** en ha
4. **Rinde** en kg/ha
5. **Humedad** en % al momento de cosecha
6. **Producción total** en kg (se puede ingresar manualmente o se calcula)

Después de registrar la cosecha, cambiar el estado de la siembra a "Cosechada".

---

## 6. Módulo: Ganadería

### Flujo de trabajo ganadero

```
Cargar Animales → Registrar Movimientos → Registrar Pesajes → Registrar Sanidad/Reproducción
```

### 6.1 Animales

**Ganadería → Animales**

El registro individual de cada animal del rodeo.

**Para dar de alta un animal:**
1. **Caravana**: número/código identificatorio
2. **Establecimiento** al que pertenece
3. **Categoría**: Vaca, Toro, Novillo, Vaquillona, Ternero, Ternera, etc.
4. **Raza**: Angus, Hereford, Braford, etc.
5. **Sexo**: Macho / Hembra
6. **Fecha de nacimiento** y **fecha de ingreso al establecimiento**
7. **Peso al ingreso** y **peso actual** (en kg)
8. Color, observaciones

> Para dar de **baja** un animal (muerte, venta, faena) NO eliminarlo del sistema. Usar el módulo **Movimientos** para registrar la salida, y luego inactivar el animal.

### 6.2 Movimientos ganaderos

**Ganadería → Movimientos**

Registra entradas y salidas del rodeo a nivel general (por lote/categoría, no individual).

**Tipos de movimiento:**
| Tipo | Cuándo usarlo |
|------|---------------|
| **Compra** | Se compraron animales |
| **Venta** | Se vendieron animales |
| **Nacimiento** | Parición del rodeo |
| **Muerte** | Animal muerto (accidente, enfermedad) |
| **Faena** | Animal faenado para consumo propio o comercial |
| **Transferencia entrada** | Animales que llegaron de otro establecimiento propio |
| **Transferencia salida** | Animales enviados a otro establecimiento propio |

**Para registrar un movimiento:**
1. Seleccionar **Tipo** y **Establecimiento**
2. **Fecha** del evento
3. **Categoría** de los animales (Vaca, Novillo, etc.)
4. **Cantidad** de cabezas
5. **Peso total** (opcional) y **precio por cabeza** (para compras/ventas)
6. **Procedencia/Destino** (nombre del campo o frigorífico)

### 6.3 Pesajes

**Ganadería → Pesajes**

Registra el control de peso periódico de los animales.

**Para registrar un pesaje:**
1. Seleccionar el **Animal** (por número de caravana)
2. **Fecha** del pesaje
3. **Peso** en kg
4. **Tipo**: Ingreso, Control periódico, Pre-venta, Pre-faena

El sistema actualiza automáticamente el peso actual del animal.

### 6.4 Sanidad

**Ganadería → Sanidad**

Registra todas las intervenciones sanitarias del rodeo.

**Para registrar un evento sanitario:**
1. Seleccionar el **Animal**
2. **Tipo**: Vacunación, Antiparasitario, Vitaminas, Tratamiento, Cirugía, Diagnóstico
3. **Fecha** y **producto utilizado**
4. **Dosis** y **unidad**, **vía de administración**
5. **Veterinario** que lo realizó (opcional)
6. **Próxima fecha** de intervención (para recordatorio)

### 6.5 Reproducción

**Ganadería → Reproducción**

Registra eventos reproductivos: servicios, diagnósticos de preñez y partos.

**Para registrar un evento:**
1. Seleccionar el **Animal hembra** (o el toro, para servicio natural)
2. **Tipo**: Servicio natural, Inseminación artificial, Diagnóstico de preñez, Parto
3. **Fecha** y resultado (preñada/vacía, porcentaje de preñez, etc.)

---

## 7. Módulo: Feedlot

El módulo de feedlot gestiona el encierre a corral para engorde.

### Flujo de trabajo

```
Crear Corrales → Crear Tropa → Registrar Consumos diarios de alimento
```

### 7.1 Corrales

**Feedlot → Corrales**

**Para crear un corral:**
1. **Nombre** (ej: "Corral 1", "Corral Norte")
2. **Código** identificatorio
3. **Establecimiento** donde se ubica
4. **Capacidad** en cabezas
5. **Superficie** en m²

La vista de corrales muestra una barra de ocupación:
- Verde: < 70% de capacidad
- Amarillo: 70–90%
- Rojo: ≥ 90% (sobrecargado)

### 7.2 Tropas

**Feedlot → Tropas**

Una **tropa** es un lote de animales encerrados juntos en un corral para engorde.

**Para crear una tropa:**
1. **Nombre** de la tropa (ej: "Tropa 1 - Novillos Jun 2026")
2. **Corral** asignado
3. **Categoría** de animales
4. **Cantidad de cabezas**
5. **Fecha de entrada** al corral
6. **Peso de entrada** (promedio en kg)
7. **Ganancia diaria objetivo** (kg/día — meta de engorde)
8. **Fecha de salida planificada** y **peso de salida objetivo**

**Para finalizar una tropa** (cuando salen a venta/faena):
- Editar la tropa y cambiar estado a **Finalizada**
- Completar fecha de salida real, peso de salida real
- El sistema calcula la ganancia diaria real vs objetivo

### 7.3 Consumos de alimento

**Feedlot → Consumos**

Registra el alimento suministrado diariamente a cada tropa.

**Para registrar un consumo:**
1. **Fecha** del suministro
2. **Corral** (el sistema filtra las tropas activas de ese corral)
3. **Tropa** que recibió el alimento
4. **Insumo** del catálogo (si usás raciones del catálogo de insumos) — se completa automáticamente el precio unitario
5. **Descripción** libre (si no hay insumo en catálogo: "Ración balanceada", "Silaje de maíz", etc.)
6. **Cantidad** en kg
7. **Costo unitario** ($/kg) — se calcula el costo total automáticamente

La vista incluye filtros por fecha, corral y tropa, y muestra los totales de kg y costo del período.

---

## 8. Módulo: Insumos

### 8.1 Catálogo de insumos

**Insumos → Catálogo**

El catálogo centraliza todos los productos que se usan en el campo.

**Para agregar un insumo al catálogo:**
1. **Nombre**: nombre comercial del producto (ej: "Glifosato 48% — Atanor")
2. **Código**: código interno o SKU (opcional)
3. **Tipo**: Semilla, Agroquímico, Fertilizante, Combustible, Veterinario, Herramienta, Repuesto, Otro
4. **Unidad de medida**: kg, lt, tn, unidad, saco, bolsa, frasco, bidón, cc/ml, dosis
5. **Marca**: fabricante o proveedor del producto
6. **Stock mínimo**: cantidad mínima de alerta (el sistema avisa cuando cae por debajo)
7. **Precio de referencia**: precio unitario de referencia para cálculos de costo

La vista muestra el **stock actual** de cada insumo calculado en tiempo real (entradas − salidas).

> Si el stock aparece en **rojo**, está por debajo del mínimo configurado.

### 8.2 Movimientos de stock

**Insumos → Stock / Movimientos**

Registra todas las entradas y salidas de insumos.

**Tipos de movimiento:**
| Tipo | Cuándo usar |
|------|-------------|
| **Entrada** | Llegó mercadería (compra, devolución, transferencia de otro campo) |
| **Salida** | Se usó en campo, se transfirió a otro campo, hubo merma |
| **Ajuste +** | Corrección de inventario a favor (conteo real > sistema) |
| **Ajuste −** | Corrección de inventario a la baja (conteo real < sistema) |

**Para registrar un movimiento:**
1. Seleccionar el **Tipo** (los motivos disponibles cambian según el tipo)
2. Seleccionar el **Insumo** del catálogo
3. **Motivo**:
   - Entrada: Compra / Devolución / Transferencia / Conteo de inventario / Otro
   - Salida: Consumo en campo / Transferencia / Merma-Pérdida / Conteo / Otro
4. **Establecimiento** donde ocurrió el movimiento
5. **Fecha** y **cantidad**
6. Para entradas: **Precio unitario** y **Proveedor** (si fue compra directa)
7. **N° de remito/comprobante**

> Las compras registradas en el módulo de **Compras** pueden registrar automáticamente el stock de los insumos del catálogo al marcar la opción "Registrar en stock".

---

## 9. Módulo: Compras

### Flujo de trabajo

```
Alta de Proveedor → Cargar Factura → Recibir mercadería → Registrar en Stock → Pagar
```

### 9.1 Proveedores

**Compras → Proveedores**

Crear todos los proveedores antes de cargar compras.

**Para crear un proveedor:**
1. **Nombre** o razón social
2. **CUIT** (con formato XX-XXXXXXXX-X)
3. **Rubro**: insumos agrícolas, veterinaria, maquinaria, combustible, semillas, servicios, transporte, otro
4. Teléfono, email, dirección, ciudad, provincia

> Incluir acá también a prestadores de servicios públicos: EPE, Litoral Gas, ASSA, etc.

### 9.2 Compras / Facturas

**Compras → Compras**

Registra cada factura o comprobante recibido de proveedores.

**Para cargar una factura:**
1. Clic en **Nueva compra**
2. Seleccionar **Proveedor**
3. **Tipo de comprobante**: Factura A, Factura B, Factura C, Remito, Recibo, Ticket
4. **Número de comprobante** (ej: `0001-00012345`)
5. **Fecha de emisión** y **Fecha de vencimiento** (para control de pagos)
6. **Establecimiento** que recibió la compra

**Detalle de ítems:**
- Descripción de cada ítem
- Cantidad y unidad de medida
- Precio unitario
- Si el ítem corresponde a un insumo del catálogo: vincularlos con el desplegable "Insumo del catálogo"

El sistema calcula automáticamente subtotales por ítem.

**IVA:**
- Ingresar el **porcentaje de IVA** (21% o 10.5%)
- El sistema calcula automáticamente el monto de IVA y el total

**Estados de la compra:**
| Estado | Significado |
|--------|-------------|
| **Pendiente** | Factura cargada, no pagada |
| **Recibida** | La mercadería fue recibida |
| **Pagada** | La factura fue abonada |
| **Cancelada** | Se anuló la operación |

**Registrar el stock:** Una vez que la mercadería está recibida, hacer clic en el botón **Registrar en stock**. El sistema crea automáticamente un movimiento de entrada en el módulo de Insumos para todos los ítems vinculados al catálogo.

---

## 10. Módulo: Ventas

### 10.1 Ventas de granos

**Ventas → Granos**

Registra las operaciones de venta de cereales y oleaginosas.

**Para cargar una venta de granos:**
1. Clic en **Nueva venta de granos**
2. **Cereal**: soja, maíz, trigo, girasol, sorgo, cebada, avena, colza, otro
3. **Tipo de venta**: Disponible, Forward, A fijar, Canje, Exportación directa
4. **Comprador** y **CUIT del comprador**
5. **Corredor** (si aplica) y **N° de comprobante**
6. **Establecimiento** y **Campaña** de origen
7. **Fecha de venta** y **Fecha de entrega** (para forwards)
8. **Cantidad** en toneladas, **Precio por tonelada** y **Moneda** (ARS o USD)
9. El **Importe total** se calcula automáticamente

**Estados:**
| Estado | Significado |
|--------|-------------|
| **Borrador** | Venta en negociación, no confirmada |
| **Confirmada** | Operación cerrada, pendiente de entrega/cobro |
| **Cobrada** | Se recibió el pago (requiere permiso de aprobación) |
| **Cancelada** | Operación anulada |

### 10.2 Ventas de hacienda

**Ventas → Hacienda**

Registra las ventas de animales en pie.

**Para cargar una venta de hacienda:**
1. **Tipo de operación**: Invernada, Terminado, Faena directa, Remate/Feria, Consignación
2. **Categoría** de los animales: Novillo, Vaquillona, Ternero, Vaca, etc.
3. **Comprador** y **corredor/feria** (si aplica)
4. **N° de guía de traslado**
5. **Establecimiento** de origen y **Fecha**
6. **Cantidad de cabezas** y **Peso promedio** en kg
7. **Precio**: ingresar precio por kg O precio por cabeza (el sistema calcula el importe total)
8. **Moneda**: ARS o USD

---

## 11. Módulo: Finanzas

### 11.1 Cuentas

**Finanzas → Cuentas**

Representan los instrumentos financieros de la empresa.

**Para crear una cuenta:**
1. **Nombre** descriptivo (ej: "Cuenta corriente Banco Nación", "Caja chica campo")
2. **Tipo**: Banco, Caja, Tarjeta de débito, Tarjeta de crédito, Otro
3. **Moneda**: ARS o USD
4. **Banco** y **Número de cuenta** (opcional)
5. **Saldo inicial**: saldo al momento de comenzar a usar el sistema

La vista muestra para cada cuenta:
- Saldo inicial
- Total de ingresos acreditados
- Total de egresos debitados
- **Saldo actual** = saldo inicial + ingresos − egresos

### 11.2 Transacciones

**Finanzas → Transacciones**

Registra movimientos de dinero en las cuentas.

**Para registrar un ingreso o egreso:**
1. Seleccionar **Cuenta** afectada
2. **Tipo**: Ingreso o Egreso
3. **Categoría** (cambia según el tipo):

   **Ingresos:**
   - Venta de granos / Venta de hacienda / Venta de leche
   - Alquiler cobrado / Subsidios / Transferencia entrada / Otro ingreso

   **Egresos:**
   - Semillas / Agroquímicos / Fertilizantes / Combustible
   - Sueldos y jornales / Alquiler-arrendamiento
   - Gastos veterinarios / Maquinaria y reparaciones
   - Fletes y transporte / Honorarios-Servicios
   - Impuestos y tasas / Seguros / Gastos de comercialización
   - Transferencia salida / **Otro egreso**

4. **Concepto**: descripción libre del movimiento
5. **Importe** en la moneda de la cuenta
6. **Fecha** y **N° de comprobante** (opcional)
7. **Establecimiento** (opcional, para análisis por campo)

> **Servicios públicos (EPE, gas, agua):** categoría **Otro egreso** con concepto descriptivo. Si tienen IVA discriminado y necesitás el crédito fiscal, cargalos también como **Compra** en el módulo de Compras.

---

## 12. Módulo: RRHH

### 12.1 Personal

**RRHH → Personal**

Registro de todos los empleados de la empresa.

**Para dar de alta a un empleado:**
1. **Nombre y Apellido**
2. **DNI** y **CUIL**
3. **Tipo de contrato**: Relación de dependencia, Jornalero, Monotributista, Plazo fijo, Pasante, Eventual/Zafra
4. **Categoría**: Peón general, Tractorista, Maquinista, Capataz, Ordeñador, Veterinario, Agrónomo, Administrativo, Otro
5. **Establecimiento** donde trabaja habitualmente
6. **Fecha de ingreso** y **sueldo base**
7. Teléfono, email, dirección, CBU, banco

### 12.2 Jornales

**RRHH → Jornales**

Registra la asistencia y liquidación de haberes del personal.

**Para registrar un jornal:**
1. Seleccionar **Empleado** (muestra el sueldo base de referencia)
2. **Establecimiento**
3. **Fecha** del jornal
4. **Tipo de jornada**: Jornada completa, Media jornada, Horas extra, Feriado/Domingo, Ausencia
5. **Horas trabajadas** (si corresponde)
6. **Tarea realizada** (descripción libre)
7. **Importe** a abonar

**Estados:**
| Estado | Significado |
|--------|-------------|
| **Pendiente** | Jornal registrado, no pagado |
| **Liquidado** | Importe abonado al empleado |
| **Anulado** | Jornal que no corresponde abonar |

**Liquidación masiva:** Botón **Liquidar pendientes** en la barra superior — marca como liquidados todos los jornales pendientes que coincidan con los filtros activos (útil para liquidar un período completo de un empleado o de todos los empleados de un establecimiento).

---

## 13. Módulo: Reportes

### 13.1 Reporte Productivo

**Reportes → Productivo**

Consolidado de la actividad productiva de la empresa.

**Filtros disponibles:**
- Establecimiento
- Campaña agrícola
- Rango de fechas

**Contenido (tabs):**

**Tab Ganadería:**
- Stock de hacienda por categoría (cabezas y peso promedio)
- Movimientos del período (entradas y salidas de rodeo)

**Tab Agricultura:**
- Superficie sembrada por cultivo con cantidad de lotes
- Cosechas del período: producción total en tn, superficie y rinde en kg/ha

**Tab Insumos:**
- Stock actual de todos los insumos activos
- Alertas de insumos bajo stock mínimo

**Exportar CSV:** genera un archivo con todos los datos del reporte para trabajar en Excel.

### 13.2 Reporte Económico

**Reportes → Económico**

Consolidado financiero-económico de la empresa.

**Filtros:** Establecimiento y rango de fechas

**Contenido (tabs):**

**Tab Ventas:**
- Ventas de granos por cereal (cantidad de operaciones, toneladas, importe)
- Ventas de hacienda por categoría (operaciones, cabezas, importe)

**Tab Compras:**
- Compras del período agrupadas por estado

**Tab Finanzas:**
- Ingresos por categoría y egresos por categoría
- Saldo por cuenta
- Flujo neto del período (ingresos − egresos)

**Tab RRHH:**
- Jornales pendientes y liquidados del período

### 13.3 Reporte Fiscal / IVA

**Reportes → Fiscal/IVA**

Diseñado para la **presentación mensual de IVA** ante la AFIP.

**Filtros:** Establecimiento y rango de fechas (filtrar por mes)

**Cómo generar el reporte de IVA mensual:**

1. Ir a **Reportes → Fiscal/IVA**
2. En **Período desde**: ingresar el primer día del mes (ej: `01/06/2026`)
3. En **Período hasta**: ingresar el último día del mes (ej: `30/06/2026`)
4. El reporte calcula automáticamente:

**IVA CRÉDITO FISCAL (Compras):**
- Total de IVA en compras del período
- Desglose por tipo de comprobante (Factura A, B, C, etc.)
- Subtotal neto, IVA y total bruto
- Detalle comprobante a comprobante con proveedor, fecha y montos

**VENTAS — IVA DÉBITO ESTIMADO:**
- Ventas de granos por cereal y moneda (ARS / USD)
- Ventas de hacienda por categoría y moneda
- Subtotales separados en ARS y USD

5. Clic en **Exportar CSV** para obtener el archivo para el contador

> **Importante:** Para que los gastos de servicios (EPE, gas, agua) aparezcan en el IVA Crédito, deben estar cargados en el módulo de **Compras** con su comprobante (Factura B o C) y el IVA discriminado. Las transacciones simples del módulo Finanzas NO aparecen en este reporte.

---

## 14. Módulo: Sistema

### 14.1 Mi Perfil

**Sistema → Mi perfil**

Cada usuario puede editar sus propios datos:
- Nombre, apellido, email, teléfono
- Cambio de contraseña (requiere la contraseña actual)

La card de "Información de cuenta" muestra el rol asignado, empresa, último acceso y estado de la cuenta.

### 14.2 Empresa

**Sistema → Empresa** (solo Administrador)

Configuración general de la empresa: razón social, CUIT, condición fiscal, domicilio fiscal y moneda principal.

### 14.3 Roles y permisos

**Sistema → Roles y permisos** (solo Administrador)

Permite ver y modificar los permisos de cada rol.

**Para editar los permisos de un rol:**
1. Hacer clic en el botón de llave (🔑) del rol
2. Se abre un panel con todos los permisos agrupados por módulo
3. Tildar/destildar los permisos deseados
4. Hacer clic en **Guardar permisos**

**Para crear un rol personalizado:**
1. Clic en **Nuevo rol**
2. Ingresar un nombre en formato snake_case (ej: `supervisor_feedlot`)
3. Luego editar sus permisos

> Los roles del sistema (administrador, gerente, capataz, administrativo, asesor técnico, auditor) no se pueden eliminar.

### 14.4 Auditoría

**Sistema → Auditoría** (solo roles con permiso `auditoria.ver`)

Muestra el historial completo de cambios en el sistema.

**Qué se registra:**
- Quién creó, modificó o eliminó cada registro
- Cuándo ocurrió (fecha y hora)
- En qué módulo (animal, siembra, compra, transacción, etc.)
- Qué campos cambiaron (valor anterior → valor nuevo)

**Filtros disponibles:**
- Usuario (busca por nombre)
- Módulo / Entidad
- Tipo de acción (Creó / Modificó / Eliminó)
- Rango de fechas (últimos 30 días por defecto)

---

## 15. Casos de uso frecuentes

### CASO 1: Cargar una factura de EPE (Electricidad) con IVA

**Objetivo:** Registrar la factura para que quede en el Reporte de IVA Crédito.

1. **Compras → Proveedores** → verificar que existe "EPE" o "Empresa Provincial de Electricidad". Si no existe, crearlo.
2. **Compras → Nueva compra**
3. Proveedor: EPE
4. Tipo comprobante: **Factura B** (si la empresa es consumidor final) o **Factura A** (si la empresa es R.I.)
5. Número: el que figura en la factura (ej: `0001-00045678`)
6. Fecha: fecha de emisión de la factura
7. Fecha de vencimiento: fecha de vencimiento del pago
8. Detalle: descripción "Servicio eléctrico mes de junio 2026", cantidad 1, precio = monto neto
9. IVA: 21% — el sistema calcula el monto de IVA
10. Cambiar estado a **Recibida**
11. Cuando se paga: cambiar a **Pagada**

El IVA de esta factura aparecerá en el Reporte Fiscal del mes correspondiente.

**Además**, si querés que se refleje en la caja/flujo de fondos, registrar también en **Finanzas → Transacciones**:
- Tipo: Egreso
- Categoría: Impuestos y tasas (o Honorarios/Servicios)
- Concepto: "Pago EPE junio 2026"
- Importe: total de la factura

---

### CASO 2: Cargar gastos de gas, agua, internet (sin IVA discriminado o con recibo simple)

Si la factura es un **ticket** o **recibo** sin IVA discriminado:

1. **Finanzas → Transacciones → Nueva transacción**
2. Tipo: Egreso
3. Categoría: Otro egreso
4. Concepto: "Gas natural — junio 2026" (o "ASSA — agua julio 2026")
5. Cuenta: la cuenta desde donde se pagó
6. Importe: monto total pagado
7. Fecha: fecha de pago

> En este caso NO aparece en el Reporte de IVA (porque no hay IVA discriminado que recuperar).

---

### CASO 3: Generar el reporte de IVA para el contador (mensual)

1. Ir a **Reportes → Fiscal/IVA**
2. Desde: `01/06/2026` — Hasta: `30/06/2026`
3. Revisar los datos en pantalla:
   - IVA Crédito: total de IVA en facturas de compras del mes
   - Ventas: operaciones de venta del mes
4. Clic en **Exportar CSV**
5. Enviar el archivo `.csv` al contador (se abre en Excel)

El CSV incluye:
- Encabezado del período
- Sección IVA Crédito con todos los comprobantes de compras
- Sección Ventas (IVA Débito estimado) con granos y hacienda

---

### CASO 4: Registrar el ciclo completo de una cosecha

1. **Agricultura → Campañas** → verificar que existe la campaña activa
2. **Admin → Lotes** → verificar que el lote está creado
3. **Agricultura → Siembras → Nueva siembra**
   - Campaña, lote, cultivo, variedad, fecha, superficie, estado=Sembrada
4. Durante el ciclo: **Agricultura → Labores** → ir registrando cada pulverización/fertilización
5. **Agricultura → Cosechas → Nueva cosecha**
   - Seleccionar la siembra, fecha, superficie cosechada, rinde, humedad, producción total
6. Volver a la siembra y cambiar estado a **Cosechada**
7. Si se vende la producción: **Ventas → Granos → Nueva venta**

---

### CASO 5: Registrar la compra de insumos y actualizar el stock

1. **Compras → Proveedores** → verificar o crear el proveedor
2. **Insumos → Catálogo** → verificar que el producto existe (si no, crearlo)
3. **Compras → Nueva compra**
   - Cargar la factura con los ítems, vinculando cada ítem al insumo del catálogo
4. Cambiar estado a **Recibida** cuando llegue la mercadería
5. Clic en **Registrar en Stock** → el sistema suma automáticamente las cantidades al stock del insumo
6. Cuando se pague: cambiar estado a **Pagada**

---

### CASO 6: Liquidar los jornales mensuales

1. **RRHH → Jornales** → verificar que todos los jornales del mes estén registrados
2. Usar el filtro de fechas para ver solo el mes a liquidar
3. Revisar que los importes y estados sean correctos
4. Clic en **Liquidar pendientes** → se marcan todos los jornales visibles como **Liquidado**
5. Registrar el pago en **Finanzas → Transacciones** (egreso, categoría: Sueldos y jornales)

---

### CASO 7: Registrar animales vendidos (baja del rodeo)

1. **Ventas → Hacienda → Nueva venta de hacienda**
   - Completar todos los datos de la operación
2. **Ganadería → Movimientos → Nuevo movimiento**
   - Tipo: Venta
   - Categoría y cantidad de cabezas vendidas
   - Fecha, peso total, precio, procedencia/destino
3. En **Ganadería → Animales**: buscar cada animal vendido y desactivarlo (botón de baja)

---

## 16. Preguntas frecuentes

**¿Cómo corrijo un error en una factura de compra ya cargada?**
Ir a **Compras → Compras**, buscar la factura, hacer clic en el lápiz (✏️) para editar. Solo se puede editar si el estado no es "Pagada". Si ya está pagada, consultar al administrador.

**¿Cómo cambio el stock de un insumo que está mal en el sistema?**
Ir a **Insumos → Stock/Movimientos** y registrar un **Ajuste positivo** o **Ajuste negativo** según corresponda. Usar motivo "Conteo de inventario". El sistema ajusta el stock sin modificar el historial anterior.

**¿Cómo registro la misma operación en cuentas y compras sin duplicar?**
Son dos registros independientes con propósitos distintos:
- **Compra**: registra el comprobante con IVA → aparece en reporte fiscal
- **Transacción de egreso**: registra el movimiento de caja → aparece en flujo de fondos

Ambos son válidos y necesarios para tener la información completa.

**¿Qué significa "Registrar en Stock" en el módulo de Compras?**
Cuando hacés clic en ese botón, el sistema crea automáticamente movimientos de **entrada** en el módulo de Insumos para todos los ítems de la factura que estén vinculados a un insumo del catálogo. Solo se puede usar una vez por compra.

**¿Por qué no veo un módulo en el menú?**
Tu rol no tiene permiso para acceder a ese módulo. Consultar al administrador del sistema para que te asigne el permiso correspondiente.

**¿Cómo exporto datos para el contador?**
- **IVA mensual**: Reportes → Fiscal/IVA → Exportar CSV
- **Resumen económico**: Reportes → Económico → Exportar CSV
- **Actividad productiva**: Reportes → Productivo → Exportar CSV

**¿Qué pasa si elimino un usuario?**
El sistema solo permite **desactivar** usuarios (no eliminarlos), para preservar el historial de auditoría. Un usuario inactivo no puede iniciar sesión.

**¿Cómo sé quién modificó un registro?**
Ir a **Sistema → Auditoría**, filtrar por la entidad (ej: "Compra") y el período. El sistema muestra quién, cuándo y qué campos cambió.

**¿Puedo manejar campos en USD y ARS al mismo tiempo?**
Sí. El sistema soporta ambas monedas en ventas de granos y hacienda, transacciones y cuentas. Los reportes muestran los totales separados por moneda para evitar conversiones incorrectas.

**¿Cómo registro un arrendamiento que se paga en kg de soja?**
Registrarlo en **Finanzas → Transacciones** como egreso con categoría "Alquiler/Arrendamiento", concepto "Arrendamiento Q1 2026 — 300 qq soja", e ingresar el importe equivalente en ARS al tipo de cambio del momento. Agregar en el campo de observaciones el dato en especie para trazabilidad.

---

*Manual generado para ERP Agropecuario — Versión 1.0 — Junio 2026*
