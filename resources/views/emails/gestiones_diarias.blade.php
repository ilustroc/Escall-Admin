<h3>Resumen de gestiones - {{ $fecha }} ({{ $hora }})</h3>

<ul>
    <li><strong>Eliminadas:</strong> {{ $deleted }}</li>
    <li><strong>Insertadas:</strong> {{ $inserted }}</li>
    <li><strong>Total hoy en BD:</strong> {{ $total }}</li>
</ul>

<h4>Top Status</h4>
<ul>
@foreach($topStatus as $x)
    <li>{{ $x['label'] }}: {{ $x['count'] }}</li>
@endforeach
</ul>

<h4>Top Tipificación</h4>
<ul>
@foreach($topTipificaciones as $x)
    <li>{{ $x['label'] }}: {{ $x['count'] }}</li>
@endforeach
</ul>

<p style="font-size:12px;color:#666">
Enviado automáticamente por el scheduler (America/Lima).
</p>
