# SalaHub — Gestão de Ambientes e Chamados Escolares

Sistema web desenvolvido para a **Escola SESI Paraguaçu Paulista**, com foco na organização de reservas de salas e no acompanhamento de chamados entre professores e coordenação.

O projeto utiliza **PHP com arquitetura MVC e MySQL**, com interface responsiva em HTML5, CSS3 e JavaScript. Os elementos da interface seguem uma identidade visual em azul e branco.

## Funcionalidades

### Professores

- Login com validação do perfil selecionado e opção de mostrar ou ocultar a senha.
- Consulta de salas, recursos e disponibilidade, com pesquisa e filtros.
- Solicitação de reservas, acompanhamento do histórico e cancelamento das próprias reservas.
- Consulta em **Meus Chamados** dos registros em que o professor é solicitante ou destinatário.
- Pesquisa e filtros por status, prioridade e categoria nos chamados.
- Edição do próprio perfil e consulta das notificações de reservas.

### Coordenação

- Painel administrativo e calendário semanal de reservas.
- Aprovação e recusa de solicitações de reserva.
- Cadastro, edição, exclusão e listagem de usuários, salas, categorias e chamados.
- Atribuição de chamados a professores pelo campo **Destinatário / responsável**.
- Consulta dos detalhes, prioridades e status dos chamados.

A visualização de chamados é controlada no servidor: professores não podem consultar registros de terceiros nem criar, editar ou excluir chamados. A coordenação mantém a gestão desses registros.

## Tecnologias

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP 8.2+ |
| Arquitetura | MVC — Model, View e Controller |
| Banco de dados | MySQL 8+ ou MariaDB 10.4+ |
| Acesso ao banco | PDO com consultas parametrizadas |
| Frontend | HTML5, CSS3 e JavaScript |
| Servidor local | Apache / XAMPP |
| Recursos visuais | Fonte Inter e ícones Lucide |

A aplicação não exige Node.js, React, Composer ou uma etapa de build.

## Requisitos funcionais

| Código | Requisito |
| --- | --- |
| RF01 | Cadastrar, editar, excluir e listar usuários. |
| RF02 | Cadastrar, editar, excluir e listar chamados. |
| RF03 | Cadastrar, editar, excluir e listar categorias. |
| RF04 | Pesquisar registros por palavra-chave. |

## Como executar

### 1. Preparar o ambiente

Instale um ambiente com PHP 8.2+, Apache e MySQL/MariaDB, como o XAMPP. Habilite as extensões PHP **pdo_mysql** e **mbstring**. A extensão **curl** é necessária para os testes HTTP.

No XAMPP, inicie os serviços **Apache** e **MySQL**. O Apache deve permitir as regras dos arquivos `.htaccess` do projeto.

### 2. Baixar o projeto

Clone o repositório dentro da pasta `htdocs` do XAMPP:

```bash
git clone https://github.com/rafasilv-cyber/projetoprofessores.github.io.git
cd projetoprofessores.github.io
```

No Windows, o caminho padrão será `C:\xampp\htdocs\projetoprofessores.github.io`.

### 3. Configurar a conexão

Copie [`config/local.example.php`](config/local.example.php) para `config/local.php` e ajuste as credenciais:

```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'portal_chamados',
    'username' => 'root',
    'password' => '',
];
```

O arquivo `config/local.php` é ignorado pelo Git. Como alternativa, configure as variáveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD`. Valores definidos no arquivo local têm prioridade.

### 4. Criar o banco de dados

Escolha **uma** das opções abaixo.

**Opção A — Instalação pelo terminal**

Execute na raiz do projeto:

```bash
php scripts/setup.php --demo
```

Se o PHP não estiver no PATH, no PowerShell use:

```powershell
& C:/xampp/php/php.exe scripts/setup.php --demo
```

O comando cria a estrutura e adiciona os dados de demonstração quando não há salas cadastradas. As reservas de exemplo recebem datas relativas ao dia da instalação. Para criar apenas a estrutura, execute sem `--demo`.

**Opção B — Importação pelo phpMyAdmin**

Abra o phpMyAdmin, selecione **Importar** e envie [`banco_com_dados_iniciais.sql`](banco_com_dados_iniciais.sql). Use um banco novo/vazio: o arquivo cria `portal_chamados` e insere os registros iniciais com IDs definidos.

Esse arquivo inclui **4 usuários, 6 salas, 7 reservas de exemplo e 3 categorias**. A tabela de chamados começa vazia. As datas das reservas são as gravadas no arquivo exportado.

O arquivo [`database.sql`](database.sql) contém somente a estrutura, sem usuários ou outros dados de demonstração.

### 5. Acessar o sistema

Com Apache e MySQL em execução, abra:

```text
http://localhost/projetoprofessores.github.io/
```

Se alterar o nome da pasta, ajuste o endereço.

## Contas de demonstração

Após instalar os dados iniciais, use a senha **`SalaHub@2026`** para as contas abaixo:

| Nome | Perfil de acesso | E-mail |
| --- | --- | --- |
| Maria Santos | Coordenação | maria.santos@escola.edu.br |
| Ana Lima | Professor | ana.lima@escola.edu.br |
| Carlos Mendes | Professor | carlos.mendes@escola.edu.br |
| Ricardo Alves | Professor | ricardo.alves@escola.edu.br |

Selecione a opção de login correspondente ao perfil. A escolha incorreta exibe um aviso e impede a autenticação. Os botões de demonstração apenas preenchem as credenciais.

Para uso real, substitua as senhas demonstrativas. A variável `APP_DEMO=0` oculta as dicas de demonstração, mas não remove as contas.

A redefinição de senha é feita pela coordenação em **Usuários → Editar**; não há envio de e-mail de recuperação.

## Estrutura do projeto

```text
projetoprofessores.github.io/
├── app/
│   ├── Controllers/                 Tratamento das requisições
│   ├── Core/                        Rotas, autenticação, PDO, CSRF e validação
│   ├── Models/                      Consultas e persistência dos dados
│   ├── Views/                       Templates e apresentação
│   └── bootstrap.php                Inicialização da aplicação
├── config/                          Configurações da aplicação e do banco
├── public/assets/                   CSS, JavaScript, imagens, fontes e ícones
├── scripts/                         Instalação e dados de demonstração
├── tests/                           Testes automatizados
├── var/                             Arquivos locais de execução
├── banco_com_dados_iniciais.sql      Estrutura e dados iniciais
├── database.sql                     Estrutura do banco
├── index.php                        Ponto de entrada da aplicação
└── README.md
```

No fluxo MVC, `index.php` recebe a requisição e o roteador seleciona o controller. O controller processa a operação, utiliza os models para acessar o banco e renderiza a view correspondente.

## Modelagem do banco

| Tabela | Finalidade |
| --- | --- |
| `users` | Usuários, perfis e hashes de senha. |
| `rooms` | Salas, capacidade, recursos e situação. |
| `reservations` | Reservas vinculadas a uma sala e a um professor. |
| `categories` | Classificação dos chamados. |
| `tickets` | Chamados vinculados a solicitante, categoria e destinatário opcional. |

As tabelas possuem chaves primárias e os relacionamentos utilizam chaves estrangeiras. Exclusões com histórico vinculado são bloqueadas; a exclusão de um usuário vinculado somente como responsável por um chamado remove a atribuição e preserva o chamado.

No banco, o perfil `Usuário` corresponde ao acesso de professor e `Administrador` ao de coordenação.

## Validação e regras de acesso

- Validação de campos obrigatórios no cliente e no servidor, rejeitando conteúdo vazio ou composto apenas por espaços.
- Verificação de e-mail, unicidade, tamanho dos campos e referências a registros existentes.
- Senhas armazenadas com hash; renovação de sessão após o login.
- Consultas parametrizadas, escape de conteúdo HTML e proteção CSRF nas operações de escrita.
- Operações de escrita por POST e autorização verificada no servidor.
- Reservas futuras dentro do período das 7h às 18h, com término posterior ao início.
- Bloqueio de reservas para salas em manutenção.
- Reservas pendentes ou confirmadas impedem sobreposição de horários.
- Transações e bloqueio da sala no banco para impedir reservas simultâneas no mesmo horário.

## Testes

Com o banco configurado e os serviços ativos, execute na raiz:

```bash
php tests/integration.php
php tests/http.php
php tests/concurrency.php
```

Se o projeto estiver em outro endereço, informe a URL base aos testes HTTP:

```bash
php tests/http.php http://localhost/nome-da-pasta/
```

A suíte inclui validações, autenticação, permissões, CRUD, pesquisa, visibilidade de chamados e conflitos entre reservas. Na última validação registrada, passaram **23 verificações de integração, 99 HTTP e 2 de concorrência**.

Os testes de integração revertem suas alterações com rollback. Os testes HTTP e de concorrência criam dados identificados para a execução e os removem ao final; os contadores `AUTO_INCREMENT` podem avançar. Utilize um banco de desenvolvimento.

## Personalização e referência visual

O nome da instituição está centralizado em [`config/app.php`](config/app.php). Imagens, estilos e scripts ficam em `public/assets/`.

A interface foi desenvolvida a partir do [protótipo publicado no Figma](https://alarm-even-52034738.figma.site/), com adaptações para a identidade em azul e branco e inclusão das telas de gestão.

As imagens incluem fotos do protótipo e fotos da escola fornecidas para o projeto. Os ícones Lucide e a fonte Inter possuem suas licenças em [`public/assets/licenses/`](public/assets/licenses/).

## Hospedagem

O repositório pode ser mantido no GitHub, mas a aplicação precisa de um servidor com **PHP e MySQL/MariaDB** para funcionar. **GitHub Pages não executa o backend PHP nem o banco de dados.**

