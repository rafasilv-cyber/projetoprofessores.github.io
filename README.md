# SalaHub — Escola SESI Paraguaçu Paulista

Sistema escolar em **PHP 8.2, MVC e MySQL/MariaDB**, implementado a partir do [protótipo publicado](https://alarm-even-52034738.figma.site/). A interface conserva a organização, imagens, tipografia e fluxos do SalaHub, com controles e destaques em azul e branco.

## Abrir no XAMPP

O banco local já foi preparado. Com Apache e MySQL ligados, acesse:

**http://localhost/projetoprofessores.github.io/**

| Acesso de demonstração | E-mail | Senha |
| --- | --- | --- |
| Professor | ana.lima@escola.edu.br | SalaHub@2026 |
| Coordenação | maria.santos@escola.edu.br | SalaHub@2026 |

O login usa autenticação real e senhas com hash. Os botões de demonstração apenas preenchem as credenciais; não ignoram a autenticação. Cada botão de login valida o perfil da conta no servidor: coordenação deve usar Entrar como Coordenação, e professor deve usar Entrar como Professor. Uma escolha incorreta mostra um aviso e não autentica a conta. Professor não acessa as funções administrativas. A coordenação pode visualizar a interface do professor mantendo a própria identidade.

## Instalação em outro ambiente

Requisitos: PHP 8.2+ com PDO MySQL e mbstring, MySQL 8+ ou MariaDB 10.4+, Apache com suporte a .htaccess. cURL é necessário somente para os testes HTTP.

1. Copie o projeto para uma pasta servida pelo Apache.
2. Ajuste a conexão em config/local.php, copiando config/local.example.php, ou use DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASSWORD.
3. Execute na raiz:

    C:/xampp/php/php.exe scripts/setup.php --demo

O comando importa database.sql, migra a coluna de senha da versão inicial e adiciona a demonstração somente se não houver salas. Não apaga registros existentes. Também é possível importar database.sql pelo phpMyAdmin e executar o comando para preparar os acessos.

Sem --demo, o comando cria/atualiza apenas a estrutura. A demonstração contém seis salas, sete reservas e quatro usuários. As reservas usam datas relativas ao dia da instalação. Os indicadores internos são calculados a partir do banco; os números da imagem institucional do login são os textos ilustrativos do protótipo.

O nome da instituição está centralizado em **config/app.php**. APP_DEMO=0 oculta as dicas e o preenchimento de credenciais de demonstração. Isso não remove as contas: antes de disponibilizar o sistema para uso real, substitua suas senhas pelo cadastro de usuários.

**GitHub Pages não executa PHP nem MySQL.** Este projeto precisa de hospedagem com PHP/MySQL, como o XAMPP local ou um servidor PHP.

## Funcionalidades

- Login, logout, opção de manter a sessão e botão mostrar/ocultar senha.
- Painéis de professor e coordenação com dados persistidos.
- Catálogo com pesquisa, filtros por tipo, capacidade e disponibilidade.
- Detalhes das salas, recursos e horários por data.
- Cadastro, edição e exclusão de salas.
- Reserva em duas etapas, confirmação, histórico e cancelamento.
- Aprovação e recusa pela coordenação.
- Calendário semanal com navegação entre semanas e filtro por sala.
- Notificações das reservas e edição do perfil.
- **RF01:** cadastrar, editar, excluir e listar usuários.
- **RF02:** cadastrar, editar, excluir e listar chamados.
  A coordenação gerencia os chamados e seleciona o professor em **Destinatário / responsável**. Professores consultam a lista, os filtros e os detalhes em **Meus Chamados**, somente quando são solicitantes ou destinatários. A restrição também é aplicada no servidor ao acessar um endereço diretamente.
- **RF03:** cadastrar, editar, excluir e listar categorias.
- **RF04:** pesquisar por palavra-chave nas listagens.
- Interface responsiva para desktop, tablet e celular; menu móvel; formulários com labels, foco visível, estados vazios e confirmação de exclusão.

Recuperação de senha é feita pela coordenação em Usuários → Editar. Não há envio de e-mail de recuperação nem serviço SMTP configurado.

## MVC e persistência

    index.php                    Front controller
    app/Core/                    Router, autenticação, PDO, CSRF e validação
    app/Controllers/             Coordenação das requisições e regras dos formulários
    app/Models/                  Consultas e persistência
    app/Views/                   Templates HTML e helpers de apresentação
    public/assets/               CSS, JavaScript, fontes, fotos e ícones locais
    config/                      Configuração da aplicação e conexão
    scripts/                     Instalação e dados demonstrativos (somente CLI)
    tests/                       Testes de integração, HTTP e concorrência
    database.sql                 Script de criação do banco e tabelas

Não há React, Node ou etapa de build necessária para executar a aplicação. JavaScript complementa a interface; PHP processa as requisições e MySQL persiste os dados.

Tabelas: users, categories, tickets, rooms e reservations. Todas têm PK; chamados referenciam solicitante, categoria e responsável opcional; reservas referenciam sala e professor. Exclusões com histórico vinculado são bloqueadas. Remover um responsável de chamado limpa apenas a atribuição.

## Validação e integridade

- Obrigatórios rejeitam vazio e espaços, inclusive espaços Unicode.
- Validação no cliente e no servidor; limites de tamanho; e-mail válido e único; opções e referências verificadas.
- SQL parametrizado, escape HTML e token CSRF em todas as mutações.
- Operações de escrita exclusivamente por POST.
- Controle de acesso no servidor; cancelamento somente pelo titular.
- Hash de senha, renovação de sessão no login, cookies HttpOnly/SameSite.
- Horários no futuro, término posterior ao início, período das 7h às 18h.
- Salas em manutenção não aceitam reservas.
- Reservas pendentes e confirmadas bloqueiam sobreposição; canceladas/recusadas liberam o período.
- Transação e bloqueio de linha da sala impedem reserva dupla concorrente.
- Código privado, configuração, scripts, testes, SQL e arquivos Git protegidos pelo Apache.

## Verificações executadas

    C:/xampp/php/php.exe tests/integration.php
    C:/xampp/php/php.exe tests/http.php
    C:/xampp/php/php.exe tests/concurrency.php

Resultado: **23 verificações de integração, 99 verificações HTTP e 2 de concorrência passaram**. Os testes HTTP incluem visibilidade de chamados por professor, bloqueio de alterações e revogação de acesso após retirar a atribuição.

Os testes de integração fazem rollback. Os testes HTTP/concorrência criam dados identificados por prefixos aleatórios e removem somente esses dados no final; sequências AUTO_INCREMENT podem avançar.

Também foram verificadas as telas no navegador, carregamento das imagens, menu móvel, fluxos de autenticação/reserva, visibilidade de senha e layout responsivo.

## Referência visual e assets

Fotos: os mesmos URLs Unsplash do protótipo, salvos localmente em public/assets/images. Ícones: Lucide 0.468.0 (licença ISC). Fonte: Inter (SIL Open Font License). As licenças estão em public/assets/licenses.

Os destaques verdes/amarelos do protótipo foram adaptados a tons de azul conforme o requisito. Nome institucional atualizado para Escola SESI Paraguaçu Paulista. Botões ilustrativos do protótipo, como cadastro de salas e configurações, receberam implementação funcional. As telas adicionais de usuários, chamados e categorias seguem o mesmo padrão visual.


