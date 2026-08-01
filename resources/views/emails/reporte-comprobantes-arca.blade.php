<x-mail::message>
# Comprobantes ARCA sincronizados

Período consultado: **{{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}** al **{{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}**

@foreach ($comprasPorEmpresa as $empresa => $compras)
## {{ $empresa }}

<x-mail::table>
| Fecha | Proveedor | Comprobante | Total |
| :---- | :-------- | :---------- | ----: |
@foreach ($compras as $compra)
| {{ $compra->fecha->format('d/m/Y') }} | {{ $compra->proveedor?->nombre ?? '—' }} | {{ $compra->tipo_comprobante_label }} {{ $compra->numero_comprobante }} | ${{ number_format((float) $compra->total, 2, ',', '.') }} |
@endforeach
</x-mail::table>

Subtotal {{ $empresa }}: **${{ number_format($compras->sum('total'), 2, ',', '.') }}** ({{ $compras->count() }} {{ $compras->count() === 1 ? 'comprobante' : 'comprobantes' }})

@endforeach

---

Total general: **${{ number_format($totalGeneral, 2, ',', '.') }}**

<x-mail::button :url="url('/compras')">
Ver comprobantes en el sistema
</x-mail::button>

Este es un informe automático generado por la sincronización diaria con ARCA.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
