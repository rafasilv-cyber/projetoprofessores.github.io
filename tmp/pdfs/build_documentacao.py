from pathlib import Path
from datetime import date

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.graphics.shapes import Drawing, Line, Polygon, Rect, String
from reportlab.platypus import (
    BaseDocTemplate, Frame, Image, KeepTogether, PageBreak, PageTemplate,
    Paragraph, Spacer, Table, TableStyle
)

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "output" / "pdf" / "Documentacao_SalaHub.pdf"
OUT.parent.mkdir(parents=True, exist_ok=True)

BLUE = colors.HexColor("#0759C7")
BLUE_DARK = colors.HexColor("#073B7A")
BLUE_LIGHT = colors.HexColor("#EAF3FF")
CYAN = colors.HexColor("#55B6FF")
INK = colors.HexColor("#17233A")
MUTED = colors.HexColor("#60708A")
LINE = colors.HexColor("#D9E4F2")
PAPER = colors.HexColor("#F7FAFE")
WHITE = colors.white

fonts = ROOT / "public" / "assets" / "fonts"
for name, file in [("Inter", "inter-400.ttf"), ("InterMed", "inter-500.ttf"),
                   ("InterSemi", "inter-600.ttf"), ("InterBold", "inter-700.ttf"),
                   ("InterExtra", "inter-800.ttf")]:
    pdfmetrics.registerFont(TTFont(name, str(fonts / file)))

styles = getSampleStyleSheet()
BODY = ParagraphStyle("Body", fontName="Inter", fontSize=9.2, leading=14.2,
                      textColor=INK, spaceAfter=7)
SMALL = ParagraphStyle("Small", parent=BODY, fontSize=7.7, leading=11.2,
                       textColor=MUTED, spaceAfter=4)
H1 = ParagraphStyle("H1", fontName="InterBold", fontSize=21, leading=25,
                    textColor=BLUE_DARK, spaceBefore=3, spaceAfter=11)
H2 = ParagraphStyle("H2", fontName="InterBold", fontSize=13.5, leading=17,
                    textColor=BLUE, spaceBefore=9, spaceAfter=6)
H3 = ParagraphStyle("H3", fontName="InterSemi", fontSize=10.5, leading=14,
                    textColor=BLUE_DARK, spaceBefore=5, spaceAfter=4)
CELL = ParagraphStyle("Cell", parent=BODY, fontSize=8, leading=11, spaceAfter=0)
CELL_HEAD = ParagraphStyle("CellHead", parent=CELL, fontName="InterSemi", textColor=WHITE)
CODE = ParagraphStyle("Code", fontName="Courier", fontSize=7.7, leading=11,
                      textColor=colors.HexColor("#DCEBFF"), leftIndent=3*mm,
                      rightIndent=3*mm, spaceAfter=0)
BULLET = ParagraphStyle("Bullet", parent=BODY, leftIndent=5*mm, firstLineIndent=-3.5*mm,
                        bulletIndent=0, spaceAfter=4)
CALLOUT = ParagraphStyle("Callout", parent=BODY, fontName="InterMed", textColor=BLUE_DARK,
                         leftIndent=4*mm, rightIndent=4*mm, spaceAfter=0)


def p(text, style=BODY):
    return Paragraph(text, style)


def bullet(text):
    return Paragraph("• " + text, BULLET)


def section(number, title, subtitle=None):
    items = [Spacer(1, 2*mm), p(f"{number}  {title}", H1)]
    if subtitle:
        items.append(p(subtitle, ParagraphStyle("Lead", parent=BODY, fontSize=10.3,
                                                leading=15.3, textColor=MUTED,
                                                spaceAfter=8)))
    return items


def callout(text):
    t = Table([[p(text, CALLOUT)]], colWidths=[166*mm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), BLUE_LIGHT),
        ("BOX", (0, 0), (-1, -1), 0.7, colors.HexColor("#B9D7FA")),
        ("LINEBEFORE", (0, 0), (0, -1), 3, BLUE),
        ("LEFTPADDING", (0, 0), (-1, -1), 4*mm),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4*mm),
        ("TOPPADDING", (0, 0), (-1, -1), 3.5*mm),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3.5*mm),
    ]))
    return t


def data_table(rows, widths, header=True):
    cooked = []
    for r, row in enumerate(rows):
        cooked.append([p(str(x), CELL_HEAD if header and r == 0 else CELL) for x in row])
    t = Table(cooked, colWidths=widths, repeatRows=1 if header else 0, hAlign="LEFT")
    commands = [
        ("BACKGROUND", (0, 0), (-1, 0), BLUE if header else PAPER),
        ("GRID", (0, 0), (-1, -1), 0.45, LINE),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 3*mm),
        ("RIGHTPADDING", (0, 0), (-1, -1), 3*mm),
        ("TOPPADDING", (0, 0), (-1, -1), 2.4*mm),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 2.4*mm),
    ]
    if header:
        for r in range(1, len(rows)):
            if r % 2 == 0:
                commands.append(("BACKGROUND", (0, r), (-1, r), PAPER))
    t.setStyle(TableStyle(commands))
    return t


def code_block(lines):
    content = "<br/>".join(line.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;") for line in lines)
    t = Table([[p(content, CODE)]], colWidths=[166*mm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), colors.HexColor("#10243F")),
        ("BOX", (0, 0), (-1, -1), 0.5, colors.HexColor("#294A71")),
        ("LEFTPADDING", (0, 0), (-1, -1), 3*mm),
        ("RIGHTPADDING", (0, 0), (-1, -1), 3*mm),
        ("TOPPADDING", (0, 0), (-1, -1), 3*mm),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3*mm),
    ]))
    return t


def navigation_diagram():
    """Mapa visual das páginas por perfil, com o login como ponto de entrada."""
    d = Drawing(166*mm, 175*mm)
    width, height = 166*mm, 175*mm
    d.add(Rect(0, 0, width, height, rx=10, ry=10, fillColor=PAPER,
               strokeColor=LINE, strokeWidth=0.8))

    def box(x, y, w, h, title, subtitle="", fill=WHITE, stroke=LINE,
            title_color=BLUE_DARK, title_size=9):
        d.add(Rect(x, y, w, h, rx=6, ry=6, fillColor=fill, strokeColor=stroke,
                   strokeWidth=1))
        d.add(String(x + w/2, y + h/2 + (3 if subtitle else -3), title,
                     fontName="InterSemi", fontSize=title_size,
                     fillColor=title_color, textAnchor="middle"))
        if subtitle:
            d.add(String(x + w/2, y + h/2 - 10, subtitle,
                         fontName="Inter", fontSize=6.7,
                         fillColor=MUTED, textAnchor="middle"))

    def down_arrow(x, y_top, y_bottom, color=BLUE):
        d.add(Line(x, y_top, x, y_bottom + 6, strokeColor=color, strokeWidth=1.6))
        d.add(Polygon([x-4, y_bottom+7, x+4, y_bottom+7, x, y_bottom],
                      fillColor=color, strokeColor=color))

    # Entrada e separação por perfil.
    box(165, 438, 140, 42, "LOGIN", "e-mail, senha e perfil", BLUE_DARK,
        BLUE_DARK, WHITE, 10)
    d.add(Line(235, 438, 235, 420, strokeColor=BLUE, strokeWidth=1.6))
    d.add(Line(107, 420, 363, 420, strokeColor=BLUE, strokeWidth=1.6))
    down_arrow(107, 420, 392)
    down_arrow(363, 420, 392)
    box(37, 350, 140, 42, "PROFESSOR", "acesso às próprias atividades",
        BLUE_LIGHT, CYAN, BLUE_DARK, 9)
    box(293, 350, 140, 42, "COORDENAÇÃO", "gestão administrativa",
        BLUE_LIGHT, CYAN, BLUE_DARK, 9)
    down_arrow(107, 350, 330)
    down_arrow(363, 350, 330)

    left = [
        ("Início", "painel e notificações"),
        ("Salas", "pesquisa, filtros e detalhes"),
        ("Reservar sala", "data, horário e finalidade"),
        ("Minhas reservas", "histórico e cancelamento"),
        ("Meus chamados", "lista, filtros e detalhes"),
        ("Configurações", "dados do próprio perfil"),
    ]
    right = [
        ("Painel", "indicadores e solicitações"),
        ("Salas", "consulta e gerenciamento"),
        ("Aprovações", "aprovar ou recusar reservas"),
        ("Calendário", "agenda semanal por sala"),
        ("Usuários e categorias", "cadastros administrativos"),
        ("Chamados", "criação, atribuição e status"),
    ]
    y_values = [292, 245, 198, 151, 104, 57]
    for (title, subtitle), y in zip(left, y_values):
        box(12, y, 190, 34, title, subtitle)
    for (title, subtitle), y in zip(right, y_values):
        box(268, y, 190, 34, title, subtitle)

    d.add(String(width/2, 23, "As permissões são verificadas novamente no servidor.",
                 fontName="InterMed", fontSize=7.4, fillColor=BLUE,
                 textAnchor="middle"))
    return d


def draw_header_footer(canvas, doc):
    if doc.page == 1:
        return
    canvas.saveState()
    canvas.setFillColor(BLUE)
    canvas.rect(0, A4[1] - 9*mm, A4[0], 9*mm, stroke=0, fill=1)
    canvas.setFont("InterSemi", 7.5)
    canvas.setFillColor(WHITE)
    canvas.drawString(22*mm, A4[1] - 5.8*mm, "SALAHUB  |  DOCUMENTAÇÃO TÉCNICA")
    canvas.setStrokeColor(LINE)
    canvas.line(22*mm, 15*mm, A4[0] - 22*mm, 15*mm)
    canvas.setFillColor(MUTED)
    canvas.setFont("Inter", 7.4)
    canvas.drawString(22*mm, 10.5*mm, "Escola SESI Paraguaçu Paulista")
    canvas.drawRightString(A4[0] - 22*mm, 10.5*mm, f"Página {doc.page}")
    canvas.restoreState()


def draw_cover(canvas, doc):
    canvas.saveState()
    width, height = A4
    image_path = ROOT / "public" / "assets" / "images" / "sesifrente.webp"
    canvas.setFillColor(BLUE_DARK)
    canvas.rect(0, 0, width, height, stroke=0, fill=1)
    canvas.drawImage(str(image_path), 0, height*0.50, width=width, height=height*0.50,
                     preserveAspectRatio=False, mask="auto")
    canvas.setFillColor(colors.Color(0.02, 0.18, 0.42, alpha=0.42))
    canvas.rect(0, height*0.50, width, height*0.50, stroke=0, fill=1)
    canvas.setFillColor(CYAN)
    canvas.roundRect(22*mm, height*0.43, 34*mm, 8*mm, 4*mm, stroke=0, fill=1)
    canvas.setFont("InterBold", 8)
    canvas.setFillColor(BLUE_DARK)
    canvas.drawCentredString(39*mm, height*0.43 + 2.7*mm, "DOCUMENTAÇÃO")
    canvas.setFillColor(WHITE)
    canvas.setFont("InterExtra", 30)
    canvas.drawString(22*mm, height*0.35, "SalaHub")
    canvas.setFont("InterSemi", 14)
    canvas.drawString(22*mm, height*0.30, "Gestão de ambientes e chamados escolares")
    canvas.setStrokeColor(CYAN)
    canvas.setLineWidth(2)
    canvas.line(22*mm, height*0.265, 72*mm, height*0.265)
    canvas.setFont("Inter", 10)
    canvas.setFillColor(colors.HexColor("#D9EBFF"))
    canvas.drawString(22*mm, height*0.225, "Escola SESI Paraguaçu Paulista")
    canvas.drawString(22*mm, height*0.19, "PHP 8.2  •  MVC  •  MySQL  •  HTML5  •  CSS3  •  JavaScript")
    canvas.setFont("Inter", 8)
    canvas.drawString(22*mm, 20*mm, f"Versão da documentação: {date.today().strftime('%d/%m/%Y')}")
    canvas.restoreState()


doc = BaseDocTemplate(str(OUT), pagesize=A4, leftMargin=22*mm, rightMargin=22*mm,
                      topMargin=20*mm, bottomMargin=20*mm,
                      title="Documentação do Projeto SalaHub",
                      author="Escola SESI Paraguaçu Paulista",
                      subject="Documentação funcional e técnica do sistema SalaHub")
frame = Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id="body")
doc.addPageTemplates([
    PageTemplate(id="cover", frames=frame, onPage=draw_cover, autoNextPageTemplate="content"),
    PageTemplate(id="content", frames=frame, onPage=draw_header_footer),
])

story = [Spacer(1, 250*mm), PageBreak()]

story += section("01", "Visão geral", "Uma visão funcional do problema, dos usuários e do escopo entregue.")
story += [
    p("O <b>SalaHub</b> é um sistema web para organizar o uso dos ambientes escolares e centralizar chamados entre professores e coordenação. A solução reúne autenticação por perfil, catálogo de salas, reserva de horários, aprovação administrativa e acompanhamento de chamados em uma única interface responsiva."),
    callout("<b>Objetivo:</b> reduzir conflitos de agenda, dar visibilidade às solicitações e manter um histórico confiável de reservas e chamados."),
    p("Perfis atendidos", H2),
    data_table([
        ["Perfil", "Responsabilidades e acesso"],
        ["Professor", "Consulta salas e disponibilidade, solicita e cancela as próprias reservas, acompanha notificações e visualiza chamados em que é solicitante ou destinatário."],
        ["Coordenação", "Gerencia usuários, salas, categorias e chamados; aprova ou recusa reservas; consulta o calendário; e atribui chamados aos professores."],
    ], [35*mm, 131*mm]),
    p("Escopo funcional", H2),
    bullet("Autenticação real com validação do perfil escolhido no login."),
    bullet("CRUD de usuários, salas, categorias e chamados."),
    bullet("Pesquisa por palavra-chave e filtros nas principais listagens."),
    bullet("Reserva em etapas, validação de conflito, aprovação e cancelamento."),
    bullet("Interface em azul e branco adaptada para desktop, tablet e celular."),
    bullet("Persistência em banco relacional com chaves primárias e estrangeiras."),
]

story += [PageBreak()] + section("02", "Requisitos e fluxos", "Requisitos funcionais e jornadas principais da aplicação.")
story += [
    data_table([
        ["Código", "Requisito funcional"],
        ["RF01", "Cadastrar, editar, excluir e listar usuários."],
        ["RF02", "Cadastrar, editar, excluir e listar chamados."],
        ["RF03", "Cadastrar, editar, excluir e listar categorias."],
        ["RF04", "Pesquisar registros por palavra-chave."],
    ], [25*mm, 141*mm]),
    p("Fluxo de reserva", H2),
    data_table([
        ["Etapa", "Ação", "Resultado"],
        ["1", "Professor seleciona uma sala.", "O sistema exibe detalhes e disponibilidade."],
        ["2", "Informa data, início, término e finalidade.", "O servidor valida período, manutenção e conflitos."],
        ["3", "Confirma a solicitação.", "A reserva é criada como Pendente."],
        ["4", "Coordenação aprova ou recusa.", "Professor recebe o estado atualizado em sua área."],
    ], [16*mm, 68*mm, 82*mm]),
    p("Fluxo de chamados", H2),
    data_table([
        ["Ator", "Ação", "Regra de visibilidade"],
        ["Coordenação", "Cria ou edita o chamado e escolhe o destinatário/responsável.", "Visualiza e gerencia todos os chamados."],
        ["Professor", "Abre Meus Chamados e consulta lista, filtros e detalhes.", "Visualiza apenas chamados em que é solicitante ou destinatário."],
        ["Servidor", "Aplica o escopo pela identidade autenticada.", "Tentativas por URL ou parâmetros forjados continuam bloqueadas."],
    ], [31*mm, 72*mm, 63*mm]),
    callout("A retirada da atribuição revoga o acesso do antigo destinatário. O solicitante continua vendo o chamado por permanecer vinculado ao registro."),
]

story += [PageBreak()] + section("03", "Mapa de navegação", "Diagrama das páginas disponíveis para cada perfil após a autenticação.")
story += [
    p("O login identifica a conta e valida o perfil selecionado. A partir desse ponto, o menu e as permissões conduzem cada usuário às páginas compatíveis com sua função."),
    Spacer(1, 3*mm),
    navigation_diagram(),
    Spacer(1, 3*mm),
    callout("A coordenação pode alternar para a visualização de professor mantendo sua identidade administrativa. A troca altera a interface, sem elevar o acesso de contas de professor."),
]

story += [PageBreak()] + section("04", "Arquitetura MVC", "Organização do código e caminho percorrido por cada requisição.")
story += [
    p("A aplicação usa um front controller em <b>index.php</b>. O roteador valida a rota, o método HTTP, a autenticação e as permissões. O controller coordena a regra da operação, os models acessam o MySQL por PDO e as views geram a resposta HTML."),
    Spacer(1, 2*mm),
    data_table([
        ["1. Navegador", "2. Router", "3. Controller", "4. Model", "5. View"],
        ["Envia GET ou POST", "Valida rota, CSRF e acesso", "Executa o caso de uso", "Consulta ou grava via PDO", "Renderiza HTML responsivo"],
    ], [33.2*mm]*5),
    p("Estrutura de diretórios", H2),
    code_block([
        "projetoprofessores.github.io/",
        "|- app/Controllers/     Regras de requisição",
        "|- app/Core/            Router, autenticação, PDO, CSRF e validação",
        "|- app/Models/          Consultas e persistência",
        "|- app/Views/           Templates HTML e helpers",
        "|- config/              Configuração da aplicação e do banco",
        "|- public/assets/       CSS, JavaScript, imagens, fontes e ícones",
        "|- scripts/             Instalação e dados demonstrativos",
        "|- tests/               Integração, HTTP e concorrência",
        "|- database.sql         Estrutura do banco",
        "`- index.php            Front controller",
    ]),
    p("Tecnologias", H2),
    data_table([
        ["Camada", "Tecnologia"],
        ["Backend", "PHP 8.2+"], ["Arquitetura", "MVC"],
        ["Persistência", "MySQL 8+ ou MariaDB 10.4+ com PDO"],
        ["Frontend", "HTML5, CSS3 e JavaScript"],
        ["Servidor", "Apache / XAMPP"],
    ], [42*mm, 124*mm]),
]

story += [PageBreak()] + section("05", "Banco de dados", "Entidades, relacionamentos e dados fornecidos para demonstração.")
story += [
    data_table([
        ["Tabela", "Finalidade", "Relacionamentos principais"],
        ["users", "Contas, perfis e hashes de senha.", "Referenciada por reservations e tickets."],
        ["rooms", "Ambientes, capacidade, recursos e situação.", "Possui muitas reservations."],
        ["reservations", "Agenda e estado das reservas.", "FK para rooms e users."],
        ["categories", "Classificação dos chamados.", "Possui muitos tickets."],
        ["tickets", "Solicitações e acompanhamento.", "FK para solicitante, categoria e destinatário opcional."],
    ], [29*mm, 62*mm, 75*mm]),
    p("Relações", H2),
    data_table([
        ["Origem", "Cardinalidade", "Destino", "Regra"],
        ["users", "1 : N", "reservations", "Exclusão restrita para preservar histórico."],
        ["rooms", "1 : N", "reservations", "Exclusão restrita para preservar histórico."],
        ["users", "1 : N", "tickets (solicitante)", "Exclusão restrita."],
        ["users", "1 : N", "tickets (destinatário)", "Ao excluir, a atribuição se torna nula."],
        ["categories", "1 : N", "tickets", "Exclusão restrita."],
    ], [35*mm, 24*mm, 47*mm, 60*mm]),
    p("Arquivos SQL", H2),
    bullet("<b>database.sql:</b> cria o banco portal_chamados e todas as tabelas, índices, restrições e relacionamentos."),
    bullet("<b>banco_com_dados_iniciais.sql:</b> contém a estrutura e os dados iniciais, pronto para importação em um banco novo."),
    callout("O pacote inicial inclui 4 usuários, 6 salas, 7 reservas e 3 categorias. A tabela de chamados começa vazia."),
]

story += [PageBreak()] + section("06", "Instalação", "Procedimento recomendado para executar o projeto localmente com XAMPP.")
story += [
    p("Pré-requisitos", H2),
    bullet("PHP 8.2+ com as extensões pdo_mysql e mbstring."),
    bullet("MySQL 8+ ou MariaDB 10.4+."),
    bullet("Apache com suporte às regras .htaccess."),
    bullet("cURL somente para executar os testes HTTP."),
    p("1. Clonar o repositório", H2),
    code_block(["git clone https://github.com/rafasilv-cyber/projetoprofessores.github.io.git", "cd projetoprofessores.github.io"]),
    p("2. Configurar a conexão", H2),
    p("Copie <b>config/local.example.php</b> para <b>config/local.php</b> e ajuste host, porta, banco, usuário e senha. Esse arquivo local é ignorado pelo Git."),
    code_block(["'host' => '127.0.0.1',", "'port' => '3306',", "'database' => 'portal_chamados',", "'username' => 'root',", "'password' => '',"]),
    p("3. Criar o banco e os dados demonstrativos", H2),
    code_block(["php scripts/setup.php --demo", "# PowerShell no XAMPP", "& C:/xampp/php/php.exe scripts/setup.php --demo"]),
    p("Também é possível importar <b>banco_com_dados_iniciais.sql</b> pelo phpMyAdmin. Use um banco novo ou vazio, pois o arquivo traz IDs definidos para os registros iniciais."),
    p("4. Abrir a aplicação", H2),
    code_block(["http://localhost/projetoprofessores.github.io/"]),
    callout("GitHub Pages não executa PHP ou MySQL. Para publicar o sistema, use uma hospedagem que ofereça essas tecnologias."),
]

story += [PageBreak()] + section("07", "Acessos iniciais", "Contas disponíveis depois da instalação dos dados de demonstração.")
story += [
    data_table([
        ["Nome", "Perfil", "E-mail"],
        ["Maria Santos", "Coordenação", "maria.santos@escola.edu.br"],
        ["Ana Lima", "Professor", "ana.lima@escola.edu.br"],
        ["Carlos Mendes", "Professor", "carlos.mendes@escola.edu.br"],
        ["Ricardo Alves", "Professor", "ricardo.alves@escola.edu.br"],
    ], [42*mm, 35*mm, 89*mm]),
    Spacer(1, 4*mm),
    callout("<b>Senha inicial de todas as contas:</b> SalaHub@2026"),
    p("Regras de autenticação", H2),
    bullet("O usuário deve escolher Professor ou Coordenação antes de entrar."),
    bullet("Uma conta administrativa usada no botão Professor é recusada com uma mensagem explicativa; o inverso também é bloqueado."),
    bullet("Os botões demonstrativos apenas preenchem as credenciais e não ignoram a autenticação."),
    bullet("As senhas são armazenadas como hash, nunca como texto simples."),
    bullet("O botão de visibilidade permite mostrar ou ocultar a senha no login e no cadastro."),
    p("Preparação para produção", H2),
    p("Substitua as senhas iniciais pelo cadastro administrativo. Definir <b>APP_DEMO=0</b> oculta as dicas demonstrativas, mas não remove as contas. A recuperação de senha é feita pela coordenação em <b>Usuários - Editar</b>; o projeto não configura envio de e-mail."),
]

story += [PageBreak()] + section("08", "Segurança e integridade", "Controles aplicados na interface, no servidor e no banco de dados.")
story += [
    data_table([
        ["Controle", "Aplicação"],
        ["Validação", "Obrigatórios rejeitam conteúdo vazio ou apenas espaços; tamanhos, opções e referências são conferidos no servidor."],
        ["Banco", "Consultas parametrizadas, chaves estrangeiras, índices e restrições de integridade."],
        ["Sessão", "Hash de senha, renovação de sessão no login e cookies HttpOnly/SameSite."],
        ["CSRF", "Token obrigatório em todas as operações que alteram dados."],
        ["Autorização", "Permissões conferidas no servidor, inclusive em URLs acessadas diretamente."],
        ["XSS", "Conteúdo dinâmico escapado antes de ser exibido nas views."],
        ["Métodos HTTP", "Operações de escrita aceitam somente POST."],
    ], [42*mm, 124*mm]),
    p("Regras específicas de reserva", H2),
    bullet("Data futura, término posterior ao início e horário entre 7h e 18h."),
    bullet("Salas em manutenção não aceitam reservas."),
    bullet("Reservas pendentes e confirmadas bloqueiam sobreposição; canceladas e recusadas liberam o período."),
    bullet("Transação e bloqueio da linha da sala evitam reserva dupla concorrente."),
    p("Proteção de arquivos", H2),
    p("As regras do Apache impedem listagem de diretórios e acesso público direto a configuração, scripts, testes, arquivos SQL e metadados do Git."),
]

story += [PageBreak()] + section("09", "Testes e manutenção", "Como validar a aplicação e quais cenários já estão cobertos.")
story += [
    p("Com o banco configurado e Apache/MySQL ativos, execute:"),
    code_block(["php tests/integration.php", "php tests/http.php", "php tests/concurrency.php"]),
    p("Para uma pasta ou endereço diferente, informe a URL base:"),
    code_block(["php tests/http.php http://localhost/nome-da-pasta/"]),
    p("Cobertura principal", H2),
    bullet("Campos obrigatórios, e-mail, enumerações e referências relacionais."),
    bullet("Login por perfil, sessão, CSRF, métodos HTTP e permissões."),
    bullet("CRUD de usuários, categorias, chamados e salas."),
    bullet("Pesquisa, filtros e visibilidade de chamados destinados ao professor."),
    bullet("Criação, aprovação, recusa e cancelamento de reservas."),
    bullet("Conflito de horários e tentativa simultânea de reserva."),
    data_table([
        ["Suíte", "Resultado da última validação"],
        ["Integração", "23 verificações aprovadas"],
        ["HTTP", "99 verificações aprovadas"],
        ["Concorrência", "2 verificações aprovadas"],
    ], [60*mm, 106*mm]),
    p("Os testes de integração revertem as alterações com rollback. Os testes HTTP e de concorrência usam dados identificados e removem esses registros ao terminar; os valores AUTO_INCREMENT podem avançar."),
]

story += [PageBreak()] + section("10", "Referência e entrega", "Arquivos principais, personalização e observações para publicação.")
story += [
    p("Arquivos importantes", H2),
    data_table([
        ["Arquivo", "Uso"],
        ["README.md", "Apresentação do projeto e guia rápido para o GitHub."],
        ["database.sql", "Estrutura completa do banco de dados."],
        ["banco_com_dados_iniciais.sql", "Estrutura e registros demonstrativos."],
        ["config/app.php", "Nome da instituição e modo de demonstração."],
        ["config/local.example.php", "Modelo de configuração local do banco."],
        ["scripts/setup.php", "Instalação e atualização do banco via terminal."],
    ], [62*mm, 104*mm]),
    p("Identidade visual", H2),
    p("A interface foi desenvolvida a partir do protótipo publicado no Figma e adaptada para a identidade em azul e branco. O nome institucional usado em todas as páginas é <b>Escola SESI Paraguaçu Paulista</b>. Fotos da escola foram incorporadas à tela de acesso."),
    p("As fontes Inter e os ícones Lucide são armazenados localmente; suas licenças estão em <b>public/assets/licenses</b>. Os demais estilos, scripts e imagens ficam em <b>public/assets</b>."),
    p("Publicação", H2),
    callout("O código pode ficar no GitHub, mas a execução exige um servidor com PHP e MySQL/MariaDB. GitHub Pages serve apenas conteúdo estático."),
    p("Repositório", H2),
    code_block(["https://github.com/rafasilv-cyber/projetoprofessores.github.io"]),
    Spacer(1, 10*mm),
    p("SalaHub", ParagraphStyle("End", fontName="InterExtra", fontSize=18, leading=22,
                                textColor=BLUE_DARK, alignment=TA_CENTER)),
    p("Gestão escolar integrada", ParagraphStyle("EndSub", parent=SMALL, alignment=TA_CENTER,
                                                  textColor=BLUE)),
]

doc.build(story)
print(OUT)
