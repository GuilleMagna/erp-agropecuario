# Sincronización ARCA desde GitHub Actions

La descarga de “Mis Comprobantes” se ejecuta fuera de cPanel para no depender de
`proc_open`, Node.js ni un navegador instalado en el servidor PHP.

## 1. Generar el token compartido

Generar un valor aleatorio de al menos 32 bytes. Por ejemplo:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Agregar el valor al archivo `.env` de producción:

```env
ARCA_SYNC_TOKEN=valor-generado
```

Después limpiar la caché de configuración:

```bash
/opt/cpanel/ea-php84/root/usr/bin/php /home/hilariomagnano/repositories/erp-agropecuario/artisan optimize:clear
```

## 2. Crear secretos del environment `production` en GitHub

En **Settings → Environments → production → Environment secrets**, crear:

- `ERP_ARCA_SYNC_URL`: `https://hilariomagnano.com.ar/api/arca/comprobantes`
- `ERP_ARCA_SYNC_TOKEN`: el mismo valor configurado en producción.
- `ARCA_EMPRESAS_JSON`: un array JSON con las empresas que se descargarán.

Ejemplo de estructura:

```json
[
  {
    "empresa_cuit": "30-00000000-0",
    "cuit_login": "20000000000",
    "clave_fiscal": "secreto",
    "cuit_representado": "30000000000"
  }
]
```

`empresa_cuit` debe coincidir con el CUIT guardado en la tabla `empresas`.
`cuit_representado` puede omitirse cuando coincide con el CUIT usado para ingresar.

## 3. Ejecución

El workflow **Sincronizar comprobantes ARCA** se ejecuta diariamente a las 07:00
de Argentina (10:00 UTC). También puede iniciarse manualmente desde la pestaña
**Actions**, indicando opcionalmente las fechas.

El cron general de cPanel puede permanecer activo para las demás tareas de Laravel.
La sincronización ARCA ya no forma parte del scheduler interno.
