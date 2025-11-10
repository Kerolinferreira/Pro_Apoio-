@component('mail::message')
# 🎉 Parabéns! Sua Proposta foi Aceita!

Olá, **{{ $destinatario }}**!

Temos ótimas notícias! Sua proposta no **ProApoio** foi **ACEITA**! 🎊

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

**Status:** ✅ ACEITA

**Data de Aceitação:** {{ $proposta->updated_at->format('d/m/Y H:i') }}

@component('mail::button', ['url' => config('app.frontend_url', 'http://localhost:5174') . '/propostas/' . $proposta->id])
Ver Detalhes da Proposta
@endcomponent

---

## Próximos Passos

@if($proposta->iniciador === 'CANDIDATO')
1. **Aguarde o contato** da instituição para alinhar os próximos passos
2. **Prepare sua documentação** necessária para iniciar o trabalho
3. **Mantenha seu perfil atualizado** no ProApoio
@else
1. **Entre em contato** com o candidato para alinhar os próximos passos
2. **Formalize a contratação** conforme os processos da sua instituição
3. **Prepare o ambiente** para a chegada do novo colaborador
@endif

---

**Informações de Contato:**

@if($proposta->iniciador === 'CANDIDATO')
- **Email da Instituição:** {{ $proposta->vaga->instituicao->user->email }}
- **Telefone:** {{ $proposta->vaga->instituicao->telefone ?? 'Não informado' }}
@else
- **Email do Candidato:** {{ $proposta->candidato->user->email }}
- **Telefone:** {{ $proposta->candidato->telefone ?? 'Não informado' }}
@endif

Desejamos muito sucesso nesta nova jornada! 🚀

Atenciosamente,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Continue acompanhando suas propostas e oportunidades através do painel do ProApoio.
@endcomponent
@endcomponent
