#!/usr/bin/env node
/**
 * ARCA - Descargador automático de Mis Comprobantes Recibidos.
 * Automatiza el login en el portal ARCA/AFIP con Playwright (Node.js).
 * Descarga el CSV y lo emite como JSON al stdout para que PHP lo consuma.
 *
 * Uso:
 *   node arca_descarga.js --cuit 20123456789 --clave MiClave --desde 2024-01-01 --hasta 2024-01-31
 *   node arca_descarga.js ... --debug        # navegador visible
 *   node arca_descarga.js ... --screenshots  # screenshots headless
 */

const { chromium } = require('playwright');
const fs   = require('fs');
const path = require('path');
const os   = require('os');

// ── Parsear argumentos ────────────────────────────────────────────────────────
const args = {};
process.argv.slice(2).forEach((val, i, arr) => {
    if (val.startsWith('--')) {
        const key = val.slice(2);
        const next = arr[i + 1];
        args[key] = (!next || next.startsWith('--')) ? true : next;
    }
});

const CUIT      = args.cuit       || '';
const CLAVE     = args.clave      || '';
const DESDE     = args.desde      || '';
const HASTA     = args.hasta      || '';
const DEBUG     = !!args.debug;
const SS        = !!args.screenshots || DEBUG;
const SS_DIR    = args['debug-dir'] || path.join(__dirname, '..', 'storage', 'app', 'arca', 'debug');

if (!CUIT || !CLAVE || !DESDE || !HASTA) {
    log('ERROR: Faltan argumentos requeridos: --cuit --clave --desde --hasta');
    process.exit(1);
}

// ── Logging al stderr (no contamina el JSON del stdout) ───────────────────────
function log(msg) {
    const ts = new Date().toTimeString().slice(0, 8);
    process.stderr.write(`[${ts}] ${msg}\n`);
}

function screenshot(page, nombre) {
    if (!SS) return Promise.resolve();
    fs.mkdirSync(SS_DIR, { recursive: true });
    const p = path.join(SS_DIR, `${nombre}.png`);
    return page.screenshot({ path: p, fullPage: false }).catch(() => {});
}

// ── Helpers de interacción ────────────────────────────────────────────────────
async function clickFirst(page, selectors, timeout = 5000) {
    for (const sel of selectors) {
        try {
            await page.click(sel, { timeout });
            return true;
        } catch { }
    }
    return false;
}

async function fillFirst(page, selectors, value, timeout = 5000) {
    for (const sel of selectors) {
        try {
            const el = page.locator(sel).first();
            await el.waitFor({ state: 'visible', timeout });
            await el.fill(value);
            return true;
        } catch { }
    }
    return false;
}

// ── Formato de fecha DD/MM/AAAA ───────────────────────────────────────────────
function fmtFecha(iso) {
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

// ── PASO 1: Login ─────────────────────────────────────────────────────────────
async function login(page) {
    log('Navegando al login de ARCA...');
    await page.goto('https://auth.afip.gob.ar/contribuyente_/login.xhtml', {
        waitUntil: 'domcontentloaded', timeout: 30000
    });
    await page.waitForTimeout(1500);
    await screenshot(page, '01_login_inicio');

    // Ingresar CUIT
    log('Ingresando CUIT...');
    const okCuit = await fillFirst(page, [
        'input[type="number"]',
        'input#F1', 'input[name="F1"]',
        'input[placeholder*="CUIT" i]', 'input[placeholder*="cuit" i]',
        'input[id*="cuit" i]', 'input[type="text"]',
    ], CUIT);
    if (!okCuit) {
        await screenshot(page, '01_error_cuit');
        throw new Error('No se encontró el campo CUIT.');
    }
    await screenshot(page, '02_cuit_ingresado');

    // Click Siguiente
    log('Clickeando Siguiente...');
    const okSig = await clickFirst(page, [
        '#btnSiguiente', 'button#siguiente',
        'input[value="Siguiente"]', 'button:has-text("Siguiente")',
        'input[type="submit"]', 'button[type="submit"]',
    ]);
    if (!okSig) {
        await screenshot(page, '02_error_siguiente');
        throw new Error('No se encontró el botón Siguiente.');
    }
    await page.waitForLoadState('domcontentloaded', { timeout: 15000 });
    await page.waitForTimeout(1500);
    await screenshot(page, '03_pagina_clave');

    // Ingresar clave
    log('Ingresando clave fiscal...');
    const okClave = await fillFirst(page, [
        'input[type="password"]',
        'input#F1', 'input[name="F1"]',
        'input[name*="clave" i]', 'input[id*="clave" i]',
        'input[type="text"]',
    ], CLAVE);
    if (!okClave) {
        await screenshot(page, '03_error_clave');
        throw new Error('No se encontró el campo de clave fiscal.');
    }

    // Click Ingresar
    log('Clickeando Ingresar...');
    const okIng = await clickFirst(page, [
        '#btnIngresar', 'button#ingresar',
        'input[value="Ingresar"]', 'button:has-text("Ingresar")',
        'input[type="submit"]', 'button[type="submit"]',
    ]);
    if (!okIng) {
        await screenshot(page, '03_error_ingresar');
        throw new Error('No se encontró el botón Ingresar.');
    }
    await page.waitForLoadState('domcontentloaded', { timeout: 20000 });
    await page.waitForTimeout(2000);
    await screenshot(page, '04_post_login');
    log(`Login OK. URL: ${page.url()}`);
}

// ── PASO 2: Navegar a Mis Comprobantes ────────────────────────────────────────
async function navegarMisComprobantes(context, page) {
    log('Buscando servicio Mis Comprobantes en el portal...');

    // El portal carga el contenido con JavaScript/AJAX → esperar hasta 30 segundos
    log('  Esperando que el portal renderice...');
    try {
        await page.waitForFunction(
            () => {
                const all = document.querySelectorAll('td, a, span, div, li');
                return Array.from(all).some(el => el.textContent.trim() === 'Mis Comprobantes');
            },
            { timeout: 30000 }
        );
        log('  Portal listo.');
    } catch {
        log('  ADVERTENCIA: timeout esperando "Mis Comprobantes" — intentando igual...');
    }

    await screenshot(page, '05_portal');

    // Buscar el elemento con texto EXACTO "Mis Comprobantes" y obtener su href
    const mcInfo = await page.evaluate(() => {
        // Recorrer todos los elementos buscando texto exacto
        const all = document.querySelectorAll('td, th, a, button, span, div, li');
        for (const el of all) {
            if (el.textContent.trim() === 'Mis Comprobantes') {
                const link = el.closest('a') || el.querySelector('a');
                return {
                    tag:  el.tagName,
                    href: link ? link.href : null,
                    text: el.textContent.trim(),
                };
            }
        }
        return null;
    });

    log(`  mcInfo: ${JSON.stringify(mcInfo)}`);

    // Si encontramos un href directo, navegar a él
    if (mcInfo && mcInfo.href) {
        log(`  Navegando directo a: ${mcInfo.href}`);
        const [newPage] = await Promise.all([
            context.waitForEvent('page', { timeout: 10000 }).catch(() => null),
            page.goto(mcInfo.href, { waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {}),
        ]);
        const target = newPage || page;
        await target.waitForLoadState('domcontentloaded', { timeout: 20000 });
        await target.waitForTimeout(3000);
        await screenshot(target, '06_mis_comprobantes');
        log(`  URL Mis Comprobantes: ${target.url()}`);
        return target;
    }

    // Sin href — hacer click con texto exacto y esperar nueva pestaña
    if (mcInfo) {
        log(`  Haciendo click en elemento ${mcInfo.tag} con texto exacto...`);
        const el = page.getByText('Mis Comprobantes', { exact: true }).first();
        const [newPage] = await Promise.all([
            context.waitForEvent('page', { timeout: 12000 }).catch(() => null),
            el.click(),
        ]);

        if (newPage) {
            await newPage.waitForLoadState('domcontentloaded', { timeout: 20000 });
            await newPage.waitForTimeout(3000);
            await screenshot(newPage, '06_mis_comprobantes');
            log(`  Nueva pestaña: ${newPage.url()}`);
            return newPage;
        }

        // El click puede haber abierto una ventana nueva del OS (window.open)
        await page.waitForTimeout(3000);
        const pages = context.pages();
        if (pages.length > 1) {
            const newP = pages[pages.length - 1];
            await newP.waitForLoadState('domcontentloaded', { timeout: 15000 });
            await screenshot(newP, '06_mis_comprobantes');
            log(`  Ventana nueva detectada: ${newP.url()}`);
            return newP;
        }

        await page.waitForLoadState('domcontentloaded', { timeout: 10000 });
        await page.waitForTimeout(2000);
        await screenshot(page, '06_mis_comprobantes');
        log(`  Misma pestaña: ${page.url()}`);
        return page;
    }

    await screenshot(page, '05_error_no_encontro_mc');
    throw new Error(
        'No se encontró el elemento "Mis Comprobantes" en el portal. ' +
        'Revisá los screenshots en: ' + SS_DIR
    );
}

// ── PASO 2b: Seleccionar persona/CUIT si aparece el selector ─────────────────
async function seleccionarPersonaSiAparece(page) {
    // Detectar si está en la pantalla "Elegí una persona para ingresar"
    const esSelectorPersona = await page.locator('text=Elegí una persona').count() > 0;
    if (!esSelectorPersona) return;

    log('  Pantalla de selección de persona detectada.');

    // Guardar HTML para debug (siempre, útil para diagnosticar)
    const html = await page.content();
    fs.mkdirSync(SS_DIR, { recursive: true });
    fs.writeFileSync(path.join(SS_DIR, 'persona_selection.html'), html);

    const cuitNorm = CUIT.replace(/-/g, '');

    // La página tiene un <form name="seleccionaEmpresaForm" action="setearContribuyente.do">
    // con <a onclick="document.getElementById('idcontribuyente').value='N'; form.submit()">
    // Buscamos el <a> del formulario cuyo textContent contiene el CUIT.
    await page.evaluate((cuit) => {
        const anchors = Array.from(document.querySelectorAll('form a'));
        const match = anchors.find(a => a.textContent.replace(/[-\s]/g, '').includes(cuit));
        if (match) {
            match.click(); // dispara onclick → setea idcontribuyente → form.submit()
            return;
        }
        // Fallback: enviar el formulario directamente con idContribuyente=1
        const hidden = document.getElementById('idcontribuyente');
        const form   = document.querySelector('form[name="seleccionaEmpresaForm"]') ||
                       document.querySelector('form');
        if (hidden && form) { hidden.value = '1'; form.submit(); }
    }, cuitNorm);

    log(`  CUIT ${cuitNorm} seleccionado — esperando navegación...`);

    // Esperar a que cargue la página de filtros (form submit hace POST + redirect)
    await page.waitForLoadState('domcontentloaded', { timeout: 20000 });
    await page.waitForTimeout(2000);
    await screenshot(page, '06b_post_seleccion');
    log(`  URL post-selección: ${page.url()}`);
}

// ── PASO 3: Filtrar y descargar ───────────────────────────────────────────────
async function descargarRecibidos(page, tmpDir) {
    log(`Configurando período: ${DESDE} → ${HASTA}`);
    await page.waitForTimeout(1000);

    // Guardar HTML del menú (Emitidos/Recibidos)
    const htmlMenu = await page.content();
    fs.mkdirSync(SS_DIR, { recursive: true });
    fs.writeFileSync(path.join(SS_DIR, 'filtros_menu.html'), htmlMenu);
    log(`  URL menú: ${page.url()}`);
    await screenshot(page, '07_menu');

    // Click en "Recibidos" → navega a comprobantesRecibidos.do
    const okRecibidos = await clickFirst(page, [
        '[id*="recibido" i]',
        'a:has-text("Recibido")', 'button:has-text("Recibido")',
        'input[value*="Recibido" i]', 'a[href*="Recibido" i]',
        'a[href*="recibido" i]',
    ]);
    if (!okRecibidos) {
        await screenshot(page, '07_error_recibidos');
        throw new Error('No se encontró el botón/link "Recibidos".');
    }

    // Esperar que cargue la página de filtros de comprobantes recibidos
    await page.waitForLoadState('domcontentloaded', { timeout: 20000 });
    await page.waitForTimeout(3000);
    log(`  URL comprobantes recibidos: ${page.url()}`);

    // Guardar HTML del formulario de filtros real
    const htmlFiltros = await page.content();
    fs.writeFileSync(path.join(SS_DIR, 'filtros_recibidos.html'), htmlFiltros);
    await screenshot(page, '07b_filtros_recibidos');

    const desdeStr = fmtFecha(DESDE);
    const hastaStr = fmtFecha(HASTA);
    log(`  Fechas a ingresar: ${desdeStr} → ${hastaStr}`);

    // La página usa Bootstrap Daterangepicker con id="fechaEmision"
    // El valor esperado es "DD/MM/YYYY - DD/MM/YYYY" en un solo campo.
    const rangoFecha = `${desdeStr} - ${hastaStr}`;

    const fechaOk = await page.evaluate(({ rango, desde, hasta }) => {
        // Intentar con la API del daterangepicker (jQuery plugin)
        const el = document.getElementById('fechaEmision');
        if (!el) return false;

        // Setear el valor visible
        el.value = rango;

        // Setear los campos ocultos del daterangepicker
        const startEl = document.querySelector('[name="daterangepicker_start"]');
        const endEl   = document.querySelector('[name="daterangepicker_end"]');
        if (startEl) startEl.value = desde;
        if (endEl)   endEl.value   = hasta;

        // Disparar eventos para que el plugin/form los detecte
        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new Event('input',  { bubbles: true }));

        // Si jQuery está disponible, usar la API del daterangepicker
        if (typeof $ !== 'undefined' && $(el).data('daterangepicker')) {
            const drp = $(el).data('daterangepicker');
            try { drp.setStartDate(desde); } catch(e) {}
            try { drp.setEndDate(hasta);   } catch(e) {}
            $(el).trigger('apply.daterangepicker', [drp]);
        }

        return true;
    }, { rango: rangoFecha, desde: desdeStr, hasta: hastaStr });

    log(`  Daterangepicker seteado: ${fechaOk ? rangoFecha : 'FALLÓ — campo no encontrado'}`);

    await screenshot(page, '08_filtros_completados');

    // Consultar
    log('Consultando...');
    const okConsultar = await clickFirst(page, [
        'button:has-text("Consultar")', 'input[value="Consultar"]',
        'button:has-text("Buscar")',   'input[value="Buscar"]',
        'button[type="submit"]',       'input[type="submit"]',
    ]);
    if (!okConsultar) {
        await screenshot(page, '08_error_consultar');
        throw new Error('No se encontró el botón Consultar.');
    }
    await page.waitForLoadState('networkidle', { timeout: 30000 });
    await page.waitForTimeout(3000);
    await screenshot(page, '09_resultados');
    // Guardar HTML de resultados
    const htmlResultados = await page.content();
    fs.writeFileSync(path.join(SS_DIR, 'resultados.html'), htmlResultados);
    log('Resultados cargados.');

    // Descargar
    log('Iniciando descarga...');
    const [download] = await Promise.all([
        page.waitForEvent('download', { timeout: 30000 }),
        clickFirst(page, [
            'button:has-text("Descargar")', 'a:has-text("Descargar")',
            'button:has-text("Exportar")',  'a:has-text("Exportar")',
            'button:has-text("CSV")',       'a:has-text("CSV")',
            'button:has-text("Excel")',     'a[href*=".csv"]',
            'a[href*="export" i]',          'input[value="Descargar"]',
        ]),
    ]).catch(async (e) => {
        await screenshot(page, '09_error_descargar');
        throw new Error('No se encontró el botón de descarga: ' + e.message);
    });

    const nombreOrig = download.suggestedFilename();
    const rutaDescarga = path.join(tmpDir, nombreOrig);
    await download.saveAs(rutaDescarga);
    log(`Descargado: ${rutaDescarga}`);
    await screenshot(page, '10_descarga_ok');

    // Si es ZIP, extraer CSV
    if (rutaDescarga.toLowerCase().endsWith('.zip')) {
        const AdmZip = require('adm-zip'); // opcional, puede no estar
        const zip = new AdmZip(rutaDescarga);
        const csvEntry = zip.getEntries().find(e => e.entryName.toLowerCase().endsWith('.csv'));
        if (!csvEntry) throw new Error('El ZIP no contiene CSV.');
        const csvPath = path.join(tmpDir, csvEntry.entryName);
        zip.extractEntryTo(csvEntry, tmpDir, false, true);
        return csvPath;
    }

    return rutaDescarga;
}

// ── PASO 4: Parsear CSV ───────────────────────────────────────────────────────
function parsearCsv(csvPath) {
    log(`Parseando CSV: ${csvPath}`);

    let contenido = fs.readFileSync(csvPath);

    // ARCA exporta en UTF-8 — intentar UTF-8 primero y sólo usar CP1252 si falla
    let texto;
    const asUtf8 = contenido.toString('utf-8');
    if (asUtf8.includes('�')) {
        try {
            const { TextDecoder } = require('util');
            texto = new TextDecoder('windows-1252').decode(contenido);
        } catch {
            texto = asUtf8;
        }
    } else {
        texto = asUtf8;
    }

    const lineas = texto.split(/\r?\n/);

    // Detectar separador
    const countPipe = (texto.match(/\|/g) || []).length;
    const countSemi = (texto.match(/;/g)  || []).length;
    const sep = countPipe > countSemi ? '|' : ';';
    log(`  Separador: '${sep}'`);

    // Encontrar fila de encabezados
    const indicadores = ['cuit', 'fecha', 'total', 'tipo', 'importe', 'denominac'];
    let headerIdx = null;
    for (let i = 0; i < Math.min(10, lineas.length); i++) {
        const lower = lineas[i].toLowerCase();
        const hits  = indicadores.filter(ind => lower.includes(ind)).length;
        if (hits >= 3) { headerIdx = i; break; }
    }
    if (headerIdx === null) throw new Error('No se encontró la fila de encabezados en el CSV.');

    const rawHeaders = lineas[headerIdx].split(sep).map(h => h.trim().toLowerCase()
        .replace(/á/g,'a').replace(/é/g,'e').replace(/í/g,'i')
        .replace(/ó/g,'o').replace(/ú/g,'u').replace(/ñ/g,'n')
        .replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,''));

    // Mapear nombres de columnas ARCA al formato estándar esperado por PHP
    const CAMPO_MAP = {
        // Fecha
        'fecha_de_emision':           'fecha',
        'fecha_emision':              'fecha',
        'fecha_comprobante':          'fecha',
        // Tipo
        'tipo_de_comprobante':        'tipo',
        'tipo_comprobante':           'tipo',
        // Número
        'numero_desde':               'numero_desde',
        'n_mero_desde':               'numero_desde',
        // Nombre emisor
        'denominacion_emisor':        'denominacion_emisor',
        'denominaci_n_emisor':        'denominacion_emisor',
        // Neto gravado total (ARCA divide por alícuota; usar el total)
        'imp_neto_gravado_total':     'imp_neto_gravado',
        // Cod. autorización
        'c_d_autorizacion':           'cod_autorizacion',
        'cod__autorizacion':          'cod_autorizacion',
    };

    const headers = rawHeaders.map(h => CAMPO_MAP[h] || h);

    const filas = [];
    for (let i = headerIdx + 1; i < lineas.length; i++) {
        const cols = lineas[i].split(sep).map(v => v.trim());
        if (cols.every(v => !v)) continue; // fila vacía
        const obj = {};
        headers.forEach((h, j) => { if (h) obj[h] = cols[j] || ''; });
        filas.push(obj);
    }

    log(`  Filas: ${filas.length}`);
    if (filas.length > 0) {
        log(`  Headers: ${JSON.stringify(headers)}`);
        log(`  Fila 1: ${JSON.stringify(filas[0])}`);
    }
    return filas;
}

// ── Main ──────────────────────────────────────────────────────────────────────
(async () => {
    const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'arca-'));
    let browser;

    try {
        browser = await chromium.launch({
            headless: !DEBUG,
            slowMo:   DEBUG ? 500 : 0,
            args:     ['--no-sandbox'],
        });

        const context = await browser.newContext({
            acceptDownloads: true,
            locale:          'es-AR',
            timezoneId:      'America/Argentina/Buenos_Aires',
        });
        const page = await context.newPage();

        // Paso 1: Login
        await login(page);

        // Paso 2: Navegar a Mis Comprobantes
        const pageMC = await navegarMisComprobantes(context, page);

        // Paso 2b: Seleccionar persona/CUIT si es necesario
        await seleccionarPersonaSiAparece(pageMC);

        // Paso 3: Descargar
        const csvPath = await descargarRecibidos(pageMC, tmpDir);

        // Paso 4: Parsear y emitir JSON
        const filas = parsearCsv(csvPath);
        process.stdout.write(JSON.stringify(filas));

    } catch (e) {
        log(`ERROR: ${e.message}`);
        process.stdout.write(JSON.stringify({ __error__: e.message }));
        process.exit(1);
    } finally {
        if (browser) await browser.close();
        // Limpiar tmp
        try { fs.rmSync(tmpDir, { recursive: true, force: true }); } catch {}
    }
})();
