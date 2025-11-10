@component('mail::message')
# Nova Proposta Recebida

Olá, **{{ $destinatario }}**!

Você recebeu uma nova proposta no **ProApoio**! 🎉

@component('mail::panel')
@if($proposta->iniciador === 'CANDIDATO')
**Candidato:** {{ $proposta->candidato->user->nome_completo }}

**Vaga:** {{ $proposta->vaga->titulo }}

**Mensagem do Candidato:**
{{ $proposta->mensagem_candidato ?? 'Nenhuma mensagem adicional' }}
@else
**Instituição:** {{ $proposta->vaga->instituicao->user->nome_completo }}

**Vaga:** {{ $proposta->vaga->titulo }}

**Mensagem da Instituição:**
{{ $proposta->mensagem_instituicao ?? 'Nenhuma mensagem adicional' }}
@endif
@endcomponent

**Status da Proposta:** {{ $proposta->status }}

**Data:** {{ $proposta->created_at->format('d/m/Y H:i') }}

@component('mail::button', ['url' => config('app.frontend_url', 'http://localhost:5174') . '/propostas/' . $proposta->id])
Ver Proposta Completa
@endcomponent

---

**Sobre a {{ $proposta->iniciador === 'CANDIDATO' ? 'Vaga' : 'Candidatura' }}:**

@if($proposta->iniciador === 'CANDIDATO')
- **Local:** {{ $proposta->vaga->cidade }}/{{ $proposta->vaga->estado }}
- **Tipo:** {{ $proposta->vaga->tipo_vaga }}
- **Área:** {{ $proposta->vaga->area_atuacao }}
@else
- **Experiência:** {{ $proposta->candidato->anos_experiencia }} anos
- **Área:** {{ $proposta->candidato->area_atuacao }}
@endif

Não perca tempo! Acesse sua conta no ProApoio e responda à proposta.

Atenciosamente,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Você está recebendo este e-mail porque tem uma conta ativa no ProApoio. Se você não reconhece esta proposta, entre em contato com nosso suporte.
@endcomponent
@endcomponent
