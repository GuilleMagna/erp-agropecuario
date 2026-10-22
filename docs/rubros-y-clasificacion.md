# Rubros y clasificación de compras

En **Compras → Proveedores → Administrar rubros** se pueden crear y editar rubros para la empresa activa. Cada rubro tiene un nombre, las actividades disponibles y una actividad predeterminada. Si el rubro corresponde a varias actividades, seleccioná todas las que correspondan y elegí la habitual como predeterminada.

Al editar un proveedor, seleccioná el Rubro: se sugerirá su Actividad. Podés elegir otra de las actividades asociadas. Guardá el CUIT para que las importaciones ARCA recuperen esta clasificación. Los CUIT con y sin guiones identifican al mismo proveedor dentro de una empresa.

Al crear una compra, el proveedor completa Rubro y Actividad. **La Actividad es la imputación del gasto**, utilizada por los reportes. Podés cambiarla libremente para ese comprobante, junto con Zona, Lote y Campaña. El botón **Usar predeterminada** vuelve a la clasificación del proveedor o del Rubro seleccionado.

Modificar el catálogo no reclasifica compras anteriores. Guardar el rubro de un proveedor lo aplica a los comprobantes del mismo CUIT entre empresas; conserva sus actividades existentes. Otro es un rubro válido. Los proveedores nuevos sin información de rubro quedan con ese campo vacío.

## Catálogo ampliado

Se conservan los 17 rubros anteriores y se agregan 29:

- Servicios de cosecha, Servicios de siembra, Pulverización / Aplicaciones.
- Semillas, Fertilizantes, Fitosanitarios / Agroquímicos.
- Acopio / Secado de granos, Análisis de suelos, Riego.
- Servicios veterinarios, Reproducción / Inseminación, Compra de hacienda.
- Pasturas / Forrajes, Suplementos / Balanceados.
- Alambrados / Corrales, Agua / Bebederos.
- Repuestos, Neumáticos, Lubricantes.
- Maquinaria / Equipos, Construcciones / Mejoras, Arrendamientos rurales.
- Seguros, Energía eléctrica, Telefonía / Internet.
- Servicios contables, Servicios legales, Impuestos / Tasas, Gastos bancarios / Financieros.

## Despliegue

La nueva tabla se crea con `php artisan migrate --path=database/migrations/2026_10_05_000001_create_rubros_table.php --force`. La tarea de despliegue de cPanel ya incluye este comando. Los códigos históricos de Rubro se mantienen y los cambios del catálogo se guardan por empresa.


## Comprobantes anteriores e informes

Clasificar proveedores no modifica automáticamente los comprobantes anteriores. En **Compras → Completar clasificación pendiente** se completan únicamente Actividad y Rubro vacíos de la empresa activa, incluso fuera de los filtros del listado. Los valores ya cargados prevalecen y los proveedores sin evidencia suficiente permanecen pendientes. Un rubro específico del comprobante determina su actividad predeterminada antes que el proveedor. Lote y Campaña se eligen manualmente: no se pueden deducir del CUIT.

La columna **Rubro / Imputación** muestra el rubro y el destino de la compra. **Reportes → Compras por clasificación** agrupa los comprobantes por Rubro, Actividad, Lote y Campaña, permite filtrar fechas y proveedor y exporta CSV. Se excluyen cancelados, las notas de crédito conservan su signo negativo y los pendientes se muestran sin clasificar. El informe usa los valores guardados del comprobante, por lo que primero debe completarse la clasificación pendiente si se desean incluir esas compras en los grupos correspondientes.

Cuando el comprobante no tiene clasificación guardada, el listado muestra la del proveedor como **Sugerida / Sugerido**. Consultar no la guarda ni modifica la compra. Para incorporarla a los informes, usar **Completar clasificación pendiente**.


## Gráficos y acceso a comprobantes

El informe de compras incluye barras y tortas por Rubro y por Actividad / Imputación. Se calculan sobre los comprobantes filtrados, usando su total: las notas de crédito restan y los cancelados se excluyen. Las barras admiten saldos negativos; las tortas representan únicamente la participación de grupos con saldo positivo y lo aclaran en pantalla.

Los iconos, barras, porciones y leyendas abren los comprobantes del grupo conservando empresa, fechas y proveedor. El detalle de la tabla conserva también Actividad, Lote y Campaña. El listado muestra CUIT del proveedor para facilitar la revisión.

**Sin rubro** reúne únicamente campos nulos o vacíos. Tiene cantidad, importe y enlace **Ver y completar**. El filtro también está disponible en Compras. La clasificación del informe sigue siendo la guardada en el comprobante, aunque el listado muestre una sugerencia del proveedor.


## Aplicar el rubro del CUIT a sus comprobantes

Al guardar el rubro de un proveedor, se reemplaza en todos los comprobantes de ese CUIT entre empresas, aunque ya tengan rubro. Las actividades existentes se conservan. Se requiere permiso de edición de compras para actualizar sus comprobantes.

Para proveedores clasificados antes de esta mejora, **Completar clasificación pendiente** completa únicamente campos vacíos y conserva `otro`. Al editar una compra pendiente, se muestra la sugerencia del proveedor para poder guardarla. El filtro **Sin rubro** consulta la clasificación guardada: una vez aplicada, el comprobante deja de pertenecer a ese grupo. No cambian importes, fechas, presentación fiscal, lote ni campaña.


## Corregir una clasificación ya aplicada

En Compras, filtrar por Proveedor y revisar los filtros de fecha/rubro. **Seleccionar los N comprobantes filtrados** incluye todas las páginas. Luego **Editar selección → Rubro** permite reemplazar incluso rubros ya asignados. Las actividades y los demás datos se conservan; se pueden corregir por separado con la acción Actividad. El cambio masivo no cambia la ficha del proveedor: actualizarla también para futuras compras. Los listados y el administrador de rubros se ordenan alfabéticamente por nombre, incluyendo rubros personalizados y nombres con acentos.


## Eliminar rubros

En Administrar rubros, el icono de papelera permite eliminar un rubro de la empresa activa tras confirmar la operación. Sus proveedores y comprobantes quedan con Rubro vacío; se conservan Actividad, importes y demás campos. La baja se registra también para rubros base, para que no reaparezcan en el catálogo. Se requieren `compras.proveedores.gestionar` y `compras.editar`. Un rubro personalizado puede crearse nuevamente, pero no recupera automáticamente las asociaciones eliminadas.

El reporte Compras por clasificación requiere actualmente `reportes.economicos.ver`, tanto para acceder como para exportar. Ese permiso también habilita el Reporte Económico; el rol `cargador_datos` no lo recibe en el seeder actual.


## Zona del CUIT y gastos por provincia

Cada proveedor tiene Zona: Santa Fe (predeterminada) o Corrientes. La carga inicial reconoce la provincia explícita, la ciudad Corrientes o una provincia claramente identificada al final de la dirección. Una calle llamada Corrientes no se interpreta como provincia. La zona puede corregirse manualmente desde Proveedores.

Las compras nuevas heredan la zona del proveedor, también al importar desde ARCA/Mrbot. El establecimiento ya no reemplaza esa zona. Al cambiar la Zona del proveedor, se actualizan sus comprobantes de la misma empresa (requiere edición de compras). No se modifican importes, rubros ni actividades.

La migración clasifica todos los proveedores existentes y completa compras con zona vacía, General o El Trébol; conserva compras históricas explícitamente en Corrientes. El reporte agrupa El Trébol histórico dentro de Santa Fe. Incluye barras, torta, filtro y enlaces por Zona, además del detalle y exportación CSV. La migración no modifica la fecha de emisión ni la marca de presentación fiscal.


## Gastos no ARCA e informe conjunto

El catálogo de Gastos no ARCA mantiene su Categoría y agrega un Rubro específico (Luz, Gas, Agua, Telefonía, Obra social, etc.). Se edita desde el servicio; cambiarlo agrupa también sus registros mensuales históricos bajo el nuevo rubro. La carga inicial identifica EPE como Luz, Litoral Gas como Gas y servicios de agua por su nombre. También clasifica proveedores EPE existentes y sus comprobantes como Energía/Luz, sin alterar importes ni imputaciones.

**Finanzas → Informe de gastos** combina Compras de la empresa activa y registros mensuales no ARCA de esa empresa. Los generales sin empresa se identifican por separado y pueden excluirse. Se muestran gráficos, importes por origen, filtros por mes y rubro, exportación CSV y enlaces al detalle. El acceso requiere `finanzas.gastos.gestionar` y `compras.ver`, permisos que ya tiene el rol cargador_datos.

Compras usa fecha del comprobante y total con IVA; notas de crédito restan y cancelados se excluyen. Gastos no ARCA usa el mes registrado del importe. No existe una relación para deduplicar una misma factura entre ambas fuentes: el informe aclara que la suma es el total registrado sin conciliación de duplicados. El enlace de No ARCA abre el catálogo del rubro para revisar servicios e historial; el de Compras conserva el período.


## Zonas en Gastos no ARCA

Cada servicio no ARCA tiene Zona propia (Santa Fe o Corrientes), independiente del proveedor y del rubro: EPE puede estar en cualquiera de las dos. Al editarla, su historial mensual se agrupa bajo la zona elegida sin cambiar importes ni fechas. La migración asigna Santa Fe por defecto y Corrientes cuando aparece explícitamente en el nombre; una calle Corrientes seguida de número no determina provincia.

El catálogo permite filtrar por Zona y la muestra también en la grilla mensual. Gastos por rubro incluye Zona en la tabla y CSV, filtro para ambos orígenes y gráficos por zona. Los enlaces conservan la zona seleccionada. El Trébol histórico de Compras se agrupa como Santa Fe.


### Zona General

La opción General está disponible en proveedores, comprobantes, gastos no ARCA y filtros/gráficos de reportes. Se elige manualmente; Santa Fe continúa siendo la opción predeterminada. Las zonas disponibles están definidas en el sistema, sin pantalla de altas de zonas por el momento.

En Compras, **Editar selección → Zona** permite reemplazar la zona de los comprobantes seleccionados, incluso al seleccionar todas las páginas filtradas. Conserva rubro, actividad y demás datos; no modifica la zona del proveedor.


## Rubro compartido por CUIT entre empresas

Al guardar un proveedor con un rubro específico, se sincroniza automáticamente con todos los proveedores existentes del mismo CUIT normalizado, aunque esté escrito con guiones o puntos. Si es un rubro personalizado ausente del catálogo de destino, se agrega para poder utilizarlo allí. También se reemplaza el rubro de todos sus comprobantes si el usuario puede editar compras.

Se reemplazan los rubros de los comprobantes; se conservan las actividades y las zonas de las otras empresas. No se crean proveedores vacíos en cuentas donde el CUIT no existe. Si más adelante se crea/importa allí sin clasificación, toma el rubro ya asignado al CUIT. Esta sincronización es una excepción acotada al aislamiento por empresa, aplicada sólo al CUIT que se está guardando. Las asignaciones existentes se sincronizan al volver a guardar el proveedor; no se resuelven conflictos históricos de forma masiva sin indicar cuál corresponde.


## Reasignación completa del rubro por CUIT

Al guardar el rubro de un proveedor, se reemplaza el rubro de TODOS los comprobantes del mismo CUIT normalizado en todas las empresas, incluidos los que ya estaban clasificados y los de la empresa de origen. No se modifica la actividad ni zona existente en las otras cuentas, ni importes, fechas o presentación fiscal. También se permite asignar Otro a todos sus comprobantes. El botón Completar clasificación pendiente conserva su función de completar únicamente faltantes; la edición individual o masiva de comprobantes sigue actuando sólo sobre los comprobantes elegidos.


## Otro es una clasificación válida

El rubro `otro` se muestra como Otro, con su propio total, filtros y enlaces. No forma parte de Sin rubro/Sin clasificar: estos grupos incluyen únicamente valores nulos o vacíos. Completar clasificación pendiente respeta Otro y no lo sustituye por la sugerencia del proveedor. Las importaciones nuevas dejan Rubro vacío cuando no existe información, y conservan Otro si el proveedor lo tiene asignado. La reasignación explícita desde el CUIT sigue reemplazando todos sus comprobantes según el comportamiento solicitado.
