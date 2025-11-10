@component('mail::message')
# Recuperação de Senha

Olá!

Você está recebendo este e-mail porque recebemos uma solicitação de recuperação de senha para sua conta no **ProApoio**.

@component('mail::button', ['url' => $url])
Redefinir Senha
@endcomponent

Este link de recuperação expirará em **{{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutos**.

Se você não solicitou a recuperação de senha, nenhuma ação adicional é necessária.

---

**Dica de Segurança:** Nunca compartilhe sua senha com ninguém. A equipe do ProApoio nunca irá solicitar sua senha por e-mail.

Atenciosamente,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Se você está tendo problemas para clicar no botão "Redefinir Senha", copie e cole o URL abaixo em seu navegador:
[{{ $url }}]({{ $url }})
@endcomponent
@endcomponent
