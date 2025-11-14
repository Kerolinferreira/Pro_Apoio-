# 🎯 Guia de Seletores - Testes E2E ProApoio

Este documento lista todos os seletores CSS/ID/XPath usados nos testes E2E e suas referências no código-fonte do frontend.

## 📋 Índice

- [Página de Login](#página-de-login)
- [Página de Cadastro de Candidato](#página-de-cadastro-de-candidato)
- [Página de Cadastro de Vaga](#página-de-cadastro-de-vaga)
- [Página de Busca de Candidatos](#página-de-busca-de-candidatos)
- [Modal de Proposta](#modal-de-proposta)
- [Componentes Comuns](#componentes-comuns)

---

## Página de Login

**Arquivo**: `frontend_proapoio/src/pages/LoginPage.tsx`

| Elemento | Seletor | Linha | Descrição |
|----------|---------|-------|-----------|
| Campo Email | `#email` | 154 | Input de email com id="email" |
| Campo Senha | `#password` | 182 | Input de senha com id="password" |
| Botão Mostrar/Ocultar Senha | `button[type="button"][aria-label*="senha"]` | 196 | Botão com aria-label |
| Checkbox "Lembrar" | `input[type="checkbox"]` | 219 | Checkbox para manter conectado |
| Link Esqueci Senha | `a[href="/forgot-password"]` | 225 | Link para recuperação |
| Botão Submeter | `button[type="submit"]` | 229 | Botão principal de login |
| Mensagem de Erro | `div.alert.alert-error` | 138 | Alert de erro com role="alert" |

### Validações de Acessibilidade

| Atributo | Seletor | Descrição |
|----------|---------|-----------|
| aria-invalid | `input[aria-invalid="true"]` | Campos com erro de validação |
| aria-describedby | `input[aria-describedby*="error"]` | Associação com mensagem de erro |
| aria-busy | `button[aria-busy="true"]` | Botão em estado de carregamento |
| aria-live | `[aria-live="assertive"]` | Região de anúncios para leitores de tela |

### Exemplo de Uso no Teste

```php
// Login básico
$driver->findElement(WebDriverBy::id('email'))->sendKeys($email);
$driver->findElement(WebDriverBy::id('password'))->sendKeys($password);
$driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

// Verificar erro
$errorAlert = $driver->findElement(WebDriverBy::cssSelector('div.alert.alert-error'));
```

---

## Página de Cadastro de Candidato

**Arquivo**: `frontend_proapoio/src/pages/RegisterCandidatoPage.tsx`

### Dados Pessoais

| Elemento | Seletor | Linha | Type |
|----------|---------|-------|------|
| Nome Completo | `input[name="nome_completo"]` | - | text |
| Email | `input[name="email"]` | - | email |
| Telefone | `input[name="telefone"]` | - | tel |
| CPF | `input[name="cpf"]` | - | text |
| Data de Nascimento | `input[name="data_nascimento"]` | - | date |
| Senha | `input[name="password"]` | - | password |
| Confirmar Senha | `input[name="password_confirmation"]` | - | password |

### Endereço

| Elemento | Seletor | Type |
|----------|---------|------|
| CEP | `input[name="cep"]` | text |
| Logradouro | `input[name="logradouro"]` | text |
| Bairro | `input[name="bairro"]` | text |
| Cidade | `input[name="cidade"]` | text |
| Estado | `select[name="estado"]` | select |

### Escolaridade

| Elemento | Seletor | Type |
|----------|---------|------|
| Escolaridade | `select[name="escolaridade"]` | select |
| Curso Superior | `input[name="curso_superior"]` | text (condicional) |
| Instituição de Ensino | `input[name="instituicao_ensino"]` | text |

### Experiência Profissional (Array)

| Elemento | Seletor | Type |
|----------|---------|------|
| Idade do Aluno | `input[name="experiencias_profissionais[0][idade_aluno]"]` | number |
| Tempo de Experiência | `select[name="experiencias_profissionais[0][tempo_experiencia]"]` | select |
| Comentário | `textarea[name="experiencias_profissionais[0][comentario]"]` | textarea |

### Deficiências

| Elemento | Seletor | Type |
|----------|---------|------|
| Checkboxes de Deficiência | `input[type="checkbox"][name^="deficiencia"]` | checkbox |

### Exemplo de Uso

```php
// Preencher dados pessoais
$driver->findElement(WebDriverBy::name('nome_completo'))->sendKeys('João Silva');
$driver->findElement(WebDriverBy::name('email'))->sendKeys('joao@example.com');

// Selecionar estado
$estadoSelect = $driver->findElement(WebDriverBy::name('estado'));
$estadoSelect->click();
$driver->findElement(
    WebDriverBy::cssSelector('select[name="estado"] option[value="SP"]')
)->click();

// Adicionar experiência
$driver->findElement(
    WebDriverBy::name('experiencias_profissionais[0][comentario]')
)->sendKeys('Experiência com educação inclusiva...');
```

---

## Página de Cadastro de Vaga

**Arquivo**: `frontend_proapoio/src/pages/CreateVagaPage.tsx`

| Elemento | Seletor | Type |
|----------|---------|------|
| Título da Vaga | `input[name="titulo_vaga"]` | text |
| Descrição | `textarea[name="descricao"]` | textarea |
| Necessidades Específicas | `textarea[name="necessidades_descricao"]` | textarea |
| Cidade | `input[name="cidade"]` | text |
| Estado | `select[name="estado"]` | select |
| Tipo | `select[name="tipo"]` | select |
| Modalidade | `input[name="modalidade"]` | text |
| Carga Horária Semanal | `input[name="carga_horaria_semanal"]` | number |
| Regime de Contratação | `select[name="regime_contratacao"]` | select |
| Remuneração | `input[name="valor_remuneracao"]` | number |
| Tipo de Remuneração | `select[name="tipo_remuneracao"]` | select |
| Mês Nascimento Aluno | `input[name="aluno_nascimento_mes"]` | number |
| Ano Nascimento Aluno | `input[name="aluno_nascimento_ano"]` | number |

### Valores dos Selects

**Tipo:**
- `PRESENCIAL`
- `REMOTO`
- `HIBRIDO`

**Regime de Contratação:**
- `CLT`
- `PJ`
- `ESTAGIO`
- `TEMPORARIO`

**Tipo de Remuneração:**
- `MENSAL`
- `HORA`
- `PROJETO`

### Exemplo de Uso

```php
// Criar vaga
$driver->findElement(WebDriverBy::name('titulo_vaga'))
    ->sendKeys('Agente de Apoio Educacional');

$driver->findElement(WebDriverBy::name('descricao'))
    ->sendKeys('Descrição da vaga...');

// Selecionar tipo
$tipoSelect = $driver->findElement(WebDriverBy::name('tipo'));
$tipoSelect->click();
$driver->findElement(
    WebDriverBy::cssSelector('select[name="tipo"] option[value="PRESENCIAL"]')
)->click();
```

---

## Página de Busca de Candidatos

**Arquivo**: `frontend_proapoio/src/pages/BuscarCandidatosPage.tsx`

### Filtros

| Elemento | Seletor | Linha | Type |
|----------|---------|-------|------|
| Campo de Busca | `#search-termo` | 169 | input text |
| Filtro de Localização | `#localizacao-filtro` | 209 | select |
| Filtro de Escolaridade | `input#escolaridade-{valor}` | 188 | checkbox |
| Filtro de Deficiência | `#tipo_deficiencia` | 229 | select |

### Valores do Filtro de Localização

- `` (vazio) - Qualquer distância
- `cidade` - Apenas na minha cidade (~50km)
- `estado` - Apenas no meu estado (~200km)

### Valores do Filtro de Escolaridade

- `fundamental_incompleto`
- `fundamental_completo`
- `medio_incompleto`
- `medio_completo`
- `superior_incompleto`
- `superior_completo`
- `pos_graduacao`
- `mestrado`
- `doutorado`

### Componente de Resultado

| Elemento | Seletor | Descrição |
|----------|---------|-----------|
| Link para Perfil | `a[href*="/candidatos/"]` | Link clicável para perfil do candidato |
| Card de Candidato | `div[class*="CandidatoCard"]` | Container do card |

### Exemplo de Uso

```php
// Aplicar filtros
$driver->findElement(WebDriverBy::id('search-termo'))
    ->sendKeys('Braille');

// Filtrar por cidade
$localizacaoSelect = $driver->findElement(WebDriverBy::id('localizacao-filtro'));
$localizacaoSelect->click();
$driver->findElement(
    WebDriverBy::cssSelector('select#localizacao-filtro option[value="cidade"]')
)->click();

// Marcar escolaridade
$driver->findElement(WebDriverBy::id('escolaridade-superior_completo'))
    ->click();

// Aguardar debounce (400ms) + margem
sleep(1);

// Verificar resultados
$cards = $driver->findElements(WebDriverBy::cssSelector('a[href*="/candidatos/"]'));
echo "Encontrados: " . count($cards) . " candidatos\n";
```

---

## Modal de Proposta

**Arquivo**: `frontend_proapoio/src/components/PropostaModal.tsx`

| Elemento | Seletor | Linha | Descrição |
|----------|---------|-------|-----------|
| Container do Modal | `div[role="dialog"]` | 104 | Div principal do modal |
| Container do Modal (alt) | `div[aria-modal="true"]` | 108 | Alternativa com aria-modal |
| Campo de Mensagem | `textarea#mensagem` | 139 | Textarea para mensagem |
| Botão Fechar | `button[aria-label="Fechar modal"]` | 123 | Botão X no canto |
| Botão Cancelar | `button[type="button"]` | 168 | Botão secundário |
| Botão Enviar | `button[type="submit"]` | 176 | Botão primário |

### Botão para Abrir Modal (na página do candidato)

**Seletores Possíveis:**
```php
// Por texto
$button = $driver->findElement(
    WebDriverBy::xpath("//button[contains(text(), 'Fazer Proposta')]")
);

// Por classe
$button = $driver->findElement(
    WebDriverBy::cssSelector('button[class*="proposta"]')
);

// Por aria-label
$button = $driver->findElement(
    WebDriverBy::cssSelector('button[aria-label*="proposta"]')
);
```

### Exemplo de Uso

```php
// Abrir modal
$propostaButton = $driver->findElement(
    WebDriverBy::xpath("//button[contains(text(), 'Fazer Proposta')]")
);
$propostaButton->click();

// Aguardar modal abrir
$driver->wait(10)->until(
    WebDriverExpectedCondition::presenceOfElementLocated(
        WebDriverBy::cssSelector('div[role="dialog"]')
    )
);

// Preencher mensagem
$mensagem = $driver->findElement(WebDriverBy::id('mensagem'));
$mensagem->clear();
$mensagem->sendKeys('Olá! Gostaríamos de convidá-lo...');

// Enviar
$driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

// Aguardar modal fechar
$driver->wait(10)->until(
    WebDriverExpectedCondition::invisibilityOfElementLocated(
        WebDriverBy::cssSelector('div[role="dialog"]')
    )
);
```

---

## Componentes Comuns

### Header

**Arquivo**: `frontend_proapoio/src/components/Header.tsx`

| Elemento | Seletor | Descrição |
|----------|---------|-----------|
| Tag Semântica | `header` | Tag HTML5 semântica |
| Logo/Link Home | `a[href="/"]` | Link para página inicial |
| Menu de Navegação | `nav` | Navegação principal |

### Footer

**Arquivo**: `frontend_proapoio/src/components/Footer.tsx`

| Elemento | Seletor | Descrição |
|----------|---------|-----------|
| Tag Semântica | `footer` | Tag HTML5 semântica |

### Alertas e Toasts

| Elemento | Seletor | Descrição |
|----------|---------|-----------|
| Alerta de Erro | `div.alert.alert-error` | Mensagem de erro |
| Alerta de Sucesso | `div.alert.alert-success` | Mensagem de sucesso |
| Alerta de Info | `div.alert.alert-info` | Mensagem informativa |
| Alerta de Warning | `div.alert.alert-warning` | Mensagem de aviso |

---

## Estratégias de Seleção

### Ordem de Preferência

1. **ID** - Mais específico e rápido
   ```php
   WebDriverBy::id('email')
   ```

2. **Name** - Para campos de formulário
   ```php
   WebDriverBy::name('titulo_vaga')
   ```

3. **CSS com atributos ARIA** - Para acessibilidade
   ```php
   WebDriverBy::cssSelector('button[aria-label="Fechar modal"]')
   ```

4. **CSS com classes estáveis** - Evitar classes utilitárias
   ```php
   WebDriverBy::cssSelector('div.alert.alert-error')
   ```

5. **XPath** - Quando não há alternativa
   ```php
   WebDriverBy::xpath("//button[contains(text(), 'Fazer Proposta')]")
   ```

### Boas Práticas

✅ **Fazer:**
- Usar IDs quando disponíveis
- Preferir atributos `name` para formulários
- Usar atributos ARIA (aria-label, role)
- Documentar seletores no teste

❌ **Evitar:**
- Classes CSS utilitárias (ex: `mb-4`, `text-center`)
- Seletores muito específicos (quebradiços)
- Índices de elementos (`div:nth-child(3)`)
- Textos que podem ser traduzidos

### Esperas (Waits)

**Explícitas (Preferir):**
```php
$driver->wait(10)->until(
    WebDriverExpectedCondition::presenceOfElementLocated(
        WebDriverBy::id('email')
    )
);
```

**Implícitas (Usar com moderação):**
```php
sleep(1); // Apenas para debounce ou animações inevitáveis
```

---

## Manutenção dos Seletores

### Se um Teste Quebrar

1. **Verificar se o seletor mudou no frontend**
   - Abrir DevTools no navegador
   - Inspecionar elemento
   - Verificar ID, name, classes

2. **Atualizar seletor no teste**
   ```php
   // Antigo
   WebDriverBy::id('old-id')

   // Novo
   WebDriverBy::id('new-id')
   ```

3. **Documentar mudança neste arquivo**

### Sugestões para o Frontend

Para facilitar os testes, considere:

1. **Adicionar IDs estáveis** em elementos testáveis
2. **Usar data-testid** para elementos dinâmicos:
   ```tsx
   <button data-testid="submit-proposal">Enviar</button>
   ```
3. **Manter atributos ARIA** consistentes
4. **Evitar remover atributos `name`** de formulários

---

**Última atualização**: 2025-01-14
**Versão do Frontend**: Verificar `package.json` em `frontend_proapoio`
