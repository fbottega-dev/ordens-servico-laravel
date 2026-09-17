<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Oficina · Ordens de serviço</title><link rel="stylesheet" href="/style.css"></head><body>
<header><strong>Oficina / Serviços</strong><small>Do diagnóstico à entrega</small>@auth<form method="post" action="/logout">@csrf<button class="secondary">Sair</button></form>@endauth</header>
<main class="layout"><span class="eyebrow">Assistência técnica</span>
@if(session('success'))<p class="card" role="status">{{ session('success') }}</p>@endif
@if($errors->any())<div class="card" role="alert"><strong>Revise os dados</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')<footer>PHP · Laravel · MySQL · Testes de fluxo e permissões<br>Projeto de portfólio — dados de demonstração fictícios.</footer></main></body></html>
