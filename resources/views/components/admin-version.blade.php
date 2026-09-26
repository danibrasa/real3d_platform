{{-- Version desplegada, en el pie del panel. Quien administra es quien la
     necesita, para saber si un cambio ya esta publicado. --}}
@php $v = \App\Support\Version::all(); @endphp
<div style="padding:16px;text-align:center;font-size:11px;color:#9ca3af;">
    <a href="https://github.com/danibrasa/real3d_platform/blob/main/CHANGELOG.md"
       target="_blank" rel="noopener"
       style="color:inherit;text-decoration:none;border-bottom:1px dotted currentColor;"
       title="Ver el registro de cambios">{{ \App\Support\Version::release() }}</a>
    @if($v['commit_short'] !== 'unknown')
        <span style="font-family:ui-monospace,Consolas,monospace;"> · {{ $v['commit_short'] }}</span>
    @endif
    @if($v['deployed_at'])
        <span> · desplegado {{ $v['deployed_at'] }}</span>
    @endif
</div>
