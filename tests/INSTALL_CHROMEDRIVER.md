# 📥 Guia de Instalação do ChromeDriver

## O que é ChromeDriver?

ChromeDriver é um servidor standalone que implementa o protocolo WebDriver do W3C para o navegador Chrome. Ele permite que scripts automatizem o navegador Chrome/Chromium.

## Passo a Passo - Windows

### 1. Verificar Versão do Chrome

Abra o Chrome e acesse: `chrome://settings/help`

Ou via linha de comando:
```cmd
reg query "HKEY_CURRENT_USER\Software\Google\Chrome\BLBeacon" /v version
```

Exemplo: `Version 120.0.6099.71`

### 2. Baixar ChromeDriver Correspondente

Acesse: https://chromedriver.chromium.org/downloads

**Importante**: A versão do ChromeDriver deve corresponder à versão principal do Chrome (ex: 120.x.x.x → ChromeDriver 120.x.x.x)

Para versões mais recentes do Chrome (115+), use:
https://googlechromelabs.github.io/chrome-for-testing/

### 3. Instalar ChromeDriver

**Opção A - Instalação Manual:**
```cmd
# Baixar e extrair para C:\chromedriver\
# Adicionar ao PATH
setx PATH "%PATH%;C:\chromedriver"
```

**Opção B - Via Chocolatey:**
```cmd
choco install chromedriver
```

### 4. Verificar Instalação

```cmd
chromedriver --version
# Deve mostrar algo como: ChromeDriver 120.0.6099.71
```

### 5. Testar ChromeDriver

```cmd
# Iniciar ChromeDriver
chromedriver --port=9515

# Em outro terminal, testar
curl http://127.0.0.1:9515/status
```

## Passo a Passo - Linux (Ubuntu/Debian)

### 1. Verificar Versão do Chrome

```bash
google-chrome --version
# Ou
chromium-browser --version
```

### 2. Baixar e Instalar ChromeDriver

**Método Automático (Script):**
```bash
#!/bin/bash
# Script para instalar ChromeDriver automaticamente

# Obter versão do Chrome
CHROME_VERSION=$(google-chrome --version | grep -oP '\d+\.\d+\.\d+\.\d+')
CHROME_MAJOR_VERSION=$(echo $CHROME_VERSION | cut -d'.' -f1)

echo "Chrome version detected: $CHROME_VERSION"
echo "Major version: $CHROME_MAJOR_VERSION"

# Obter URL do ChromeDriver
CHROMEDRIVER_VERSION=$(curl -sS "https://chromedriver.storage.googleapis.com/LATEST_RELEASE_$CHROME_MAJOR_VERSION")
echo "ChromeDriver version to install: $CHROMEDRIVER_VERSION"

# Baixar ChromeDriver
wget -N "https://chromedriver.storage.googleapis.com/$CHROMEDRIVER_VERSION/chromedriver_linux64.zip"

# Extrair e instalar
unzip -o chromedriver_linux64.zip
chmod +x chromedriver
sudo mv -f chromedriver /usr/local/bin/chromedriver

# Limpar
rm chromedriver_linux64.zip

# Verificar
chromedriver --version
```

**Método Manual:**
```bash
# Baixar ChromeDriver (substitua VERSION pela versão correta)
VERSION="120.0.6099.71"
wget https://chromedriver.storage.googleapis.com/$VERSION/chromedriver_linux64.zip

# Extrair
unzip chromedriver_linux64.zip

# Mover para /usr/local/bin
sudo mv chromedriver /usr/local/bin/

# Dar permissões de execução
sudo chmod +x /usr/local/bin/chromedriver

# Limpar
rm chromedriver_linux64.zip
```

### 3. Verificar Instalação

```bash
chromedriver --version
which chromedriver
```

### 4. Configurar como Serviço (Opcional)

Criar arquivo `/etc/systemd/system/chromedriver.service`:

```ini
[Unit]
Description=ChromeDriver Service
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/local/bin/chromedriver --port=9515 --whitelisted-ips=
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Habilitar e iniciar:
```bash
sudo systemctl enable chromedriver
sudo systemctl start chromedriver
sudo systemctl status chromedriver
```

## Passo a Passo - macOS

### 1. Verificar Versão do Chrome

```bash
/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version
```

### 2. Instalar via Homebrew (Recomendado)

```bash
# Instalar ChromeDriver
brew install chromedriver

# Se houver problemas de assinatura
xattr -d com.apple.quarantine $(which chromedriver)
```

### 3. Instalar Manualmente

```bash
# Baixar ChromeDriver (substitua VERSION)
VERSION="120.0.6099.71"
wget https://chromedriver.storage.googleapis.com/$VERSION/chromedriver_mac64.zip

# Extrair
unzip chromedriver_mac64.zip

# Mover para /usr/local/bin
mv chromedriver /usr/local/bin/

# Dar permissões
chmod +x /usr/local/bin/chromedriver

# Remover quarentena do macOS
xattr -d com.apple.quarantine /usr/local/bin/chromedriver

# Limpar
rm chromedriver_mac64.zip
```

### 4. Verificar Instalação

```bash
chromedriver --version
```

## Executar ChromeDriver

### Modo Interativo (Desenvolvimento)

```bash
# Iniciar ChromeDriver
chromedriver --port=9515

# Deixar rodando em primeiro plano
# Pressione Ctrl+C para parar
```

### Modo Background

**Linux/Mac:**
```bash
chromedriver --port=9515 &

# Verificar processo
ps aux | grep chromedriver

# Parar
killall chromedriver
```

**Windows (PowerShell):**
```powershell
Start-Process chromedriver -ArgumentList "--port=9515" -WindowStyle Hidden

# Verificar
Get-Process chromedriver

# Parar
Stop-Process -Name chromedriver
```

## Opções do ChromeDriver

```bash
# Porta customizada
chromedriver --port=9515

# Permitir conexões de qualquer IP (cuidado em produção!)
chromedriver --port=9515 --whitelisted-ips=

# Modo verbose para debug
chromedriver --port=9515 --verbose

# Log para arquivo
chromedriver --port=9515 --log-path=/var/log/chromedriver.log
```

## Troubleshooting

### Erro: "ChromeDriver version mismatch"

**Problema**: Versão do ChromeDriver não corresponde ao Chrome instalado.

**Solução**:
1. Verificar versão do Chrome: `chrome://version`
2. Baixar ChromeDriver correspondente
3. Substituir binário antigo

### Erro: "Permission denied"

**Linux/Mac:**
```bash
sudo chmod +x /usr/local/bin/chromedriver
```

**Windows:**
- Executar como Administrador
- Desabilitar antivírus temporariamente (pode bloquear)

### Erro: "Cannot find Chrome binary"

**Solução**: Instalar Google Chrome ou definir caminho:

```bash
# Linux
export CHROME_BIN=/usr/bin/google-chrome

# Mac
export CHROME_BIN="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

# Windows (variável de ambiente)
set CHROME_BIN=C:\Program Files\Google\Chrome\Application\chrome.exe
```

### Erro: "Address already in use"

**Problema**: Porta 9515 já está em uso.

**Solução**:
```bash
# Verificar processo usando a porta
netstat -ano | findstr 9515  # Windows
lsof -i :9515  # Linux/Mac

# Matar processo
taskkill /PID <PID> /F  # Windows
kill -9 <PID>  # Linux/Mac

# Ou usar outra porta
chromedriver --port=9516
```

## Testar Instalação

### Teste Rápido com cURL

```bash
# ChromeDriver deve estar rodando
curl http://127.0.0.1:9515/status

# Resposta esperada (JSON):
{
  "value": {
    "ready": true,
    "message": "ChromeDriver ready for new sessions."
  }
}
```

### Teste com Script PHP

Crie `test_chromedriver.php`:

```php
<?php
require 'vendor/autoload.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

try {
    echo "Conectando ao ChromeDriver...\n";
    $driver = RemoteWebDriver::create(
        'http://127.0.0.1:9515',
        DesiredCapabilities::chrome()
    );

    echo "✓ ChromeDriver conectado!\n";

    $driver->get('https://www.google.com');
    echo "✓ Navegação bem-sucedida!\n";

    echo "Título da página: " . $driver->getTitle() . "\n";

    $driver->quit();
    echo "✓ Teste concluído com sucesso!\n";

} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
```

Execute:
```bash
php test_chromedriver.php
```

## Recursos Adicionais

- **ChromeDriver Docs**: https://chromedriver.chromium.org/
- **WebDriver Spec**: https://w3c.github.io/webdriver/
- **Chrome for Testing**: https://googlechromelabs.github.io/chrome-for-testing/
- **Selenium PHP Docs**: https://github.com/php-webdriver/php-webdriver

## Atualizações Automáticas

Para manter ChromeDriver atualizado automaticamente:

### Script de Atualização (Linux/Mac)

Crie `update_chromedriver.sh`:

```bash
#!/bin/bash
CHROME_VERSION=$(google-chrome --version | grep -oP '\d+' | head -1)
LATEST_DRIVER=$(curl -sS "https://chromedriver.storage.googleapis.com/LATEST_RELEASE_$CHROME_VERSION")

echo "Chrome version: $CHROME_VERSION"
echo "Latest ChromeDriver: $LATEST_DRIVER"

CURRENT_DRIVER=$(chromedriver --version | grep -oP '\d+\.\d+\.\d+\.\d+')
echo "Current ChromeDriver: $CURRENT_DRIVER"

if [ "$LATEST_DRIVER" != "$CURRENT_DRIVER" ]; then
    echo "Updating ChromeDriver..."
    wget -N "https://chromedriver.storage.googleapis.com/$LATEST_DRIVER/chromedriver_linux64.zip"
    unzip -o chromedriver_linux64.zip
    sudo mv -f chromedriver /usr/local/bin/
    rm chromedriver_linux64.zip
    echo "✓ ChromeDriver updated to $LATEST_DRIVER"
else
    echo "✓ ChromeDriver is up to date"
fi
```

---

**Agora você está pronto para executar os testes E2E do ProApoio!** 🎉

Volte para [QUICKSTART.md](QUICKSTART.md) para começar a usar os testes.
