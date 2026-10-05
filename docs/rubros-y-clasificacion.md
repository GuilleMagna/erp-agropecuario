# Rubros y clasificación de compras

En **Compras → Proveedores → Administrar rubros** se pueden crear y editar rubros para la empresa activa. Cada rubro tiene un nombre, las actividades disponibles y una actividad predeterminada. Si el rubro corresponde a varias actividades, seleccioná todas las que correspondan y elegí la habitual como predeterminada.

Al editar un proveedor, seleccioná el Rubro: se sugerirá su Actividad. Podés elegir otra de las actividades asociadas. Guardá el CUIT para que las importaciones ARCA recuperen esta clasificación. Los CUIT con y sin guiones identifican al mismo proveedor dentro de una empresa.

Al crear una compra, el proveedor completa Rubro y Actividad. **La Actividad es la imputación del gasto**, utilizada por los reportes. Podés cambiarla libremente para ese comprobante, junto con Zona, Lote y Campaña. El botón **Usar predeterminada** vuelve a la clasificación del proveedor o del Rubro seleccionado.

Modificar el catálogo o un proveedor no reclasifica automáticamente compras anteriores. Una compra editada conserva su imputación hasta que el usuario la cambie. Los proveedores recién importados con Rubro Otro continúan sin clasificar.

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
