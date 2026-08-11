#!/usr/bin/env node

const { spawn } = require('child_process');
const path = require('path');

const endpoint = process.env.ERP_ARCA_SYNC_URL || '';
const statusEndpoint = endpoint.replace(/\/comprobantes\/?$/, '/estado');
const token = process.env.ERP_ARCA_SYNC_TOKEN || '';
const rawConfig = process.env.ARCA_EMPRESAS_JSON || '';
const desde = process.env.ARCA_DESDE || inicioDelMes();
const hasta = process.env.ARCA_HASTA || fechaIso(new Date());
const githubRunId = process.env.GITHUB_RUN_ID || ('manual-' + Date.now());
const runId = githubRunId + '-' + (process.env.GITHUB_RUN_ATTEMPT || '1');
const runUrl = process.env.GITHUB_SERVER_URL && process.env.GITHUB_REPOSITORY
    ? process.env.GITHUB_SERVER_URL + '/' + process.env.GITHUB_REPOSITORY + '/actions/runs/' + githubRunId
    : '';

if (!endpoint || !token || !rawConfig) {
    fail('Faltan ERP_ARCA_SYNC_URL, ERP_ARCA_SYNC_TOKEN o ARCA_EMPRESAS_JSON.');
}

let empresas;
try {
    empresas = JSON.parse(rawConfig);
} catch {
    fail('ARCA_EMPRESAS_JSON no contiene JSON válido.');
}

if (!Array.isArray(empresas) || empresas.length === 0) {
    fail('ARCA_EMPRESAS_JSON debe ser un array con al menos una empresa.');
}

(async () => {
    await reportarEstado('running', 'Sincronización iniciada.');

    for (const empresa of empresas) {
        validarEmpresa(empresa);
        const etiqueta = empresa.empresa_cuit || empresa.cuit_representado || empresa.cuit_login;
        process.stderr.write('Sincronizando empresa ' + etiqueta + ' (' + desde + ' a ' + hasta + ')...\n');

        const comprobantes = await descargar(empresa);
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: cabeceras(),
            body: JSON.stringify({
                run_id: runId,
                empresa_cuit: etiqueta,
                desde,
                hasta,
                comprobantes,
            }),
        });

        const body = await response.text();
        if (!response.ok) {
            throw new Error('ERP respondió HTTP ' + response.status + ': ' + body.slice(0, 500));
        }

        process.stderr.write('Resultado ERP: ' + body + '\n');
    }

    await reportarEstado('success', 'Sincronización finalizada correctamente.');
})().catch(async (error) => {
    try {
        await reportarEstado('failure', error.message);
    } catch (reportError) {
        process.stderr.write('No se pudo informar el fallo al ERP: ' + reportError.message + '\n');
    }
    fail(error.message);
});

async function reportarEstado(estado, mensaje) {
    const response = await fetch(statusEndpoint, {
        method: 'POST',
        headers: cabeceras(),
        body: JSON.stringify({
            run_id: runId,
            estado,
            mensaje,
            run_url: runUrl || undefined,
            desde,
            hasta,
            empresas_total: empresas.length,
        }),
    });

    if (!response.ok) {
        throw new Error('No se pudo registrar el estado en el ERP (HTTP ' + response.status + ').');
    }
}

function cabeceras() {
    return {
        Authorization: 'Bearer ' + token,
        'Content-Type': 'application/json',
        Accept: 'application/json',
    };
}

function descargar(empresa) {
    const args = [
        path.join(__dirname, 'arca_descarga.js'),
        '--cuit', String(empresa.cuit_login),
        '--clave', String(empresa.clave_fiscal),
        '--desde', desde,
        '--hasta', hasta,
    ];

    if (empresa.cuit_representado) {
        args.push('--cuit-representado', String(empresa.cuit_representado));
    }

    return new Promise((resolve, reject) => {
        const child = spawn(process.execPath, args, {
            stdio: ['ignore', 'pipe', 'inherit'],
            windowsHide: true,
        });
        let stdout = '';

        child.stdout.setEncoding('utf8');
        child.stdout.on('data', (chunk) => { stdout += chunk; });
        child.on('error', reject);
        child.on('close', (code) => {
            if (code !== 0) {
                reject(new Error('El descargador ARCA terminó con código ' + code + '.'));
                return;
            }

            try {
                const data = JSON.parse(stdout);
                if (data && data.__error__) {
                    reject(new Error(data.__error__));
                    return;
                }
                if (!Array.isArray(data)) {
                    reject(new Error('ARCA no devolvió una lista de comprobantes.'));
                    return;
                }
                resolve(data);
            } catch {
                reject(new Error('El descargador ARCA devolvió JSON inválido.'));
            }
        });
    });
}

function validarEmpresa(empresa) {
    if (!empresa || !empresa.cuit_login || !empresa.clave_fiscal) {
        fail('Cada empresa requiere cuit_login y clave_fiscal.');
    }
}

function inicioDelMes() {
    const ahora = new Date();
    return fechaIso(new Date(Date.UTC(ahora.getUTCFullYear(), ahora.getUTCMonth(), 1)));
}

function fechaIso(fecha) {
    return fecha.toISOString().slice(0, 10);
}

function fail(message) {
    process.stderr.write('ERROR: ' + message + '\n');
    process.exit(1);
}
