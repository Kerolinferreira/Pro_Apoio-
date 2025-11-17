@component('mail::message')
# ❌ Sua Proposta foi Recusada

Olá, **{{ $destinatario }}**!

Informamos que sua proposta no **ProApoio** foi **RECUSADA**.

@component('mail::panel')
@if($proposta->iniciador === 'CANDIDATO')
**Vaga:** {{ $proposta->vaga->titulo }}

**Instituição:** {{ $proposta->vaga->instituicao->user->nome_completo }}

**Local:** {{ $proposta->vaga->cidade }}/{{ $proposta->vaga->estado }}
@else
**Candidato:** {{ $proposta->candidato->user->nome_completo }}

**Vaga:** {{ $proposta->vaga->titulo }}

**Experiência:** {{ $proposta->candidato->anos_experiencia }} anos
@endif
@endcomponent

**Status:** ❌ RECUSADA

**Data de Recusa:** {{ $proposta->updated_at->format('d/m/Y H:i') }}

@if($proposta->mensagem_resposta)
**Mensagem:**
{{ $proposta->mensagem_resposta }}
@endif

@component('mail::button', ['url' => config('app.frontend_url', 'http://localhost:5174') . '/propostas/' . $proposta->id])
Ver Detalhes da Proposta
@endcomponent

---

## Não desanime!

@if($proposta->iniciador === 'CANDIDATO')
Continue procurando outras oportunidades no ProApoio. Existem muitas vagas disponíveis que podem ser perfeitas para você! 💪
@else
Continue sua busca pelo candidato ideal. O ProApoio tem diversos profissionais qualificados cadastrados! 💪
@endif

Atenciosamente,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Continue acompanhando suas propostas e oportunidades através do painel do ProApoio.
@endcomponent
@endcomponent
