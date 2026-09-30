<?php
declare(strict_types=1);
// Testes HTTP em dados exclusivos desta execução. Requer Apache e MySQL ativos.
if (PHP_SAPI !== 'cli') { exit; }
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database;
use App\Models\{User,Category,Ticket,Room,Reservation};
$base=$argv[1]??'http://localhost/projetoprofessores.github.io/';
$prefix='QA-'.bin2hex(random_bytes(5));
$db=Database::connection();$checks=0;$adminId=0;$teacherId=0;$roomId=0;$categoryId=0;$ticketId=0;$newUserId=0;
function verify(bool $condition,string $message): void {global $checks;if(!$condition){throw new RuntimeException('FALHOU: '.$message);}echo 'OK: '.$message."\n";$checks++;}
final class HttpSession {
    private $curl; public string $token='';
    public function __construct(private string $base){$this->curl=curl_init();curl_setopt_array($this->curl,[CURLOPT_COOKIEFILE=>'',CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>20]);}
    public function request(string $query='',?array $data=null,bool $csrf=true): array {
        $headers=[];
        curl_setopt($this->curl,CURLOPT_URL,$this->base.'index.php?'.$query);
        curl_setopt($this->curl,CURLOPT_HEADERFUNCTION,static function($c,$line)use(&$headers){$headers[]=$line;return strlen($line);});
        if($data!==null){if($csrf){$data['_token']=$this->token;}curl_setopt($this->curl,CURLOPT_POST,true);curl_setopt($this->curl,CURLOPT_POSTFIELDS,http_build_query($data));}
        else{curl_setopt($this->curl,CURLOPT_HTTPGET,true);}
        $body=curl_exec($this->curl);if($body===false){throw new RuntimeException(curl_error($this->curl));}
        if(preg_match('/name="_token" value="([^"]+)"/',$body,$m)){$this->token=$m[1];}
        if(str_contains($body,'Fatal error')||str_contains($body,'<b>Warning</b>')){throw new RuntimeException('Erro PHP na resposta: '.$body);}
        return ['code'=>curl_getinfo($this->curl,CURLINFO_RESPONSE_CODE),'body'=>$body,'headers'=>implode('',$headers)];
    }
    public function login(string $email,string $portal='teacher'):void {$this->request('page=login');$r=$this->request('page=login&action=store',['email'=>$email,'password'=>'TestPass@123','portal'=>$portal]);verify($r['code']===303,'Login válido pelo acesso '.$portal);$this->request('page=dashboard');}
}
try {
    $users=new User();$hash=password_hash('TestPass@123',PASSWORD_DEFAULT);
    $adminId=$users->save(['name'=>$prefix.' Admin','email'=>$prefix.'-admin@example.test','role'=>'Administrador','password_hash'=>$hash]);
    $teacherId=$users->save(['name'=>$prefix.' Professor','email'=>$prefix.'-teacher@example.test','role'=>'Usuário','password_hash'=>$hash]);
    $a=new HttpSession($base);$t=new HttpSession($base);
    verify($a->request('page=users')['code']===303,'Visitante é encaminhado ao login');
    $a->request('page=login');
    verify($a->request('page=login&action=store',['email'=>'','password'=>''])['code']===422,'Login rejeita campos vazios no servidor');
    foreach ([
        [$prefix.'-admin@example.test','teacher','Entrar como Coordenação'],
        [$prefix.'-teacher@example.test','admin','Entrar como Professor'],
        [$prefix.'-admin@example.test',null,'Selecione uma opção de acesso'],
        [$prefix.'-admin@example.test','invalid','Selecione uma opção de acesso'],
        [$prefix.'-admin@example.test',['admin'],'Selecione uma opção de acesso'],
    ] as [$email,$portal,$message]) {
        $client=new HttpSession($base);$client->request('page=login');
        $response=$client->request('page=login&action=store',['email'=>$email,'password'=>'TestPass@123','portal'=>$portal]);
        verify($response['code']===422 && str_contains($response['body'],'role="alert"') && str_contains($response['body'], $message),'Acesso incompatível ou inválido exibe aviso');
        verify($client->request('page=dashboard')['code']===303,'Acesso rejeitado não cria sessão autenticada');
    }
    $a->login($prefix.'-admin@example.test','admin');$t->login($prefix.'-teacher@example.test');
    verify($t->request('page=users')['code']===403,'Professor não acessa gestão de usuários');
    verify($t->request('page=approvals')['code']===403,'Professor não acessa aprovações');
    verify($t->request('page=switch&action=store',[])['code']===403,'Professor não pode elevar privilégios');
    verify($a->request('page=categories&action=store',['name'=>'Teste','description'=>'Teste'],false)['code']===403,'POST sem CSRF é bloqueado');
    verify($a->request('page=users&action=delete&id='.$teacherId)['code']===405,'Exclusão via GET é bloqueada');
    verify($a->request('page=invalid')['code']===404,'Rota inexistente retorna 404');
    verify($a->request('page=rooms&action=show&id[]=1')['code']===404,'ID malformado não causa erro interno');
    foreach(['dashboard','rooms','users','categories','tickets','approvals','calendar','settings'] as $page){verify($a->request('page='.$page)['code']===200,'Página '.$page.' abre sem erro');}
    foreach(['users','categories','tickets','rooms'] as $page){verify($a->request('page='.$page.'&action=create')['code']===200,'Formulário '.$page.' abre sem erro');}
    verify($a->request('page=categories&action=store',['name'=>" \t\n",'description'=>'Teste'])['code']===422,'Categoria rejeita nome com espaços');
    verify($a->request('page=users&action=store',['name'=>'','email'=>'','role'=>'Usuário','password'=>''])['code']===422,'Usuário rejeita obrigatórios vazios');
    $user=['name'=>$prefix.' Novo','email'=>$prefix.'-new@example.test','role'=>'Usuário','password'=>'TestPass@123'];
    verify($a->request('page=users&action=store',$user)['code']===303,'Cadastrar usuário via HTTP');$newUserId=(int)$users->byEmail($user['email'])['id'];
    verify(password_verify('TestPass@123',$users->find($newUserId)['password_hash']),'Senha armazenada como hash');
    verify($a->request('page=users&action=store',$user)['code']===422,'E-mail duplicado é recusado');
    $user['name']=$prefix.' Editado';$user['password']='';
    verify($a->request('page=users&action=update&id='.$newUserId,$user)['code']===303&&$users->find($newUserId)['name']===$user['name'],'Editar usuário e preservar senha');
    verify($a->request('page=users&action=update&id='.$newUserId,array_replace($user,['password'=>'        ']))['code']===422,'Não substituir senha por espaços');
    $category=['name'=>$prefix.' Categoria','description'=>'Descrição inicial'];
    verify($a->request('page=categories&action=store',$category)['code']===303,'Cadastrar categoria via HTTP');
    $categoryId=(int)(new Category())->search($prefix)[0]['id'];$category['description']='Descrição revisada';
    verify($a->request('page=categories&action=update&id='.$categoryId,$category)['code']===303,'Editar categoria via HTTP');
    $ticket=['subject'=>$prefix.' Chamado','description'=>'<script>alert(1)</script>','user_id'=>$newUserId,'category_id'=>$categoryId,'assignee_id'=>'','status'=>'Aberto','priority'=>'Média'];
    verify($a->request('page=tickets&action=store',array_replace($ticket,['subject'=>'   ']))['code']===422,'Chamado rejeita título vazio');
    verify($a->request('page=tickets&action=store',array_replace($ticket,['user_id'=>999999]))['code']===422,'Chamado rejeita FK inexistente');
    verify($a->request('page=tickets&action=store',$ticket)['code']===303,'Cadastrar chamado via HTTP');$ticketId=(int)(new Ticket())->search($prefix)[0]['id'];
    $detail=$a->request('page=tickets&action=show&id='.$ticketId);verify($detail['code']===200&&str_contains($detail['body'],'&lt;script&gt;'),'Detalhe escapa conteúdo contra XSS');
    $listing=$t->request('page=tickets');
    verify($listing['code']===200 && str_contains($listing['body'],'Meus Chamados') && !str_contains($listing['body'],$ticket['subject']),'Professor tem menu e lista somente chamados vinculados');
    verify($t->request('page=tickets&action=show&id='.$ticketId)['code']===404,'Professor não acessa chamado alheio pelo endereço');
    $forged=$t->request('page=tickets&user_id='.$newUserId.'&assignee_id='.$newUserId.'&q='.urlencode($prefix));
    verify(!str_contains($forged['body'],$ticket['subject']),'Parâmetros de usuário não ampliam acesso à pesquisa');
    $ticket['assignee_id']=$teacherId;
    verify($a->request('page=tickets&action=update&id='.$ticketId,$ticket)['code']===303,'Coordenação destina chamado ao professor');
    $listing=$t->request('page=tickets&q='.urlencode($prefix).'&status=Aberto&priority='.urlencode('Média').'&category='.$categoryId);
    verify($listing['code']===200 && str_contains($listing['body'],$ticket['subject']),'Chamado atribuído aparece na pesquisa e nos filtros do destinatário');
    $detail=$t->request('page=tickets&action=show&id='.$ticketId);
    verify($detail['code']===200 && str_contains($detail['body'],'&lt;script&gt;'),'Destinatário pode consultar detalhes com conteúdo escapado');
    foreach (['create','edit','delete'] as $action) {
        verify(!str_contains($listing['body'],'action='.$action) && !str_contains($detail['body'],'action='.$action),'Professor não vê controle de '.$action);
    }
    foreach (['create','edit'] as $action) {
        verify($t->request('page=tickets&action='.$action.'&id='.$ticketId)['code']===403,'Professor não abre formulário de '.$action);
    }
    foreach (['store','update','delete'] as $action) {
        verify($t->request('page=tickets&action='.$action.'&id='.$ticketId,$ticket)['code']===403,'Servidor impede professor de executar '.$action);
    }
    verify((new Ticket())->find($ticketId)['status']==='Aberto','Tentativas não alteram o chamado');
    $requester=new HttpSession($base);$requester->login($user['email']);
    verify($requester->request('page=tickets&action=show&id='.$ticketId)['code']===200,'Professor solicitante também visualiza seu chamado');
    $ticket['assignee_id']='';
    verify($a->request('page=tickets&action=update&id='.$ticketId,$ticket)['code']===303,'Coordenação pode retirar atribuição');
    verify($t->request('page=tickets&action=show&id='.$ticketId)['code']===404 && !str_contains($t->request('page=tickets')['body'],$ticket['subject']),'Retirar atribuição revoga consulta do antigo destinatário');
    verify($requester->request('page=tickets&action=show&id='.$ticketId)['code']===200,'Retirar atribuição preserva acesso do solicitante');
    $ticket['status']='Resolvido';verify($a->request('page=tickets&action=update&id='.$ticketId,$ticket)['code']===303,'Editar chamado via HTTP');
    foreach(['users','categories','tickets'] as $page){$r=$a->request('page='.$page.'&q='.urlencode($prefix));verify($r['code']===200&&str_contains($r['body'],$prefix),'Pesquisa por palavra-chave em '.$page);}
    $a->request('page=categories&action=delete&id='.$categoryId,[]);verify((new Category())->find($categoryId)!==null,'Categoria vinculada não é excluída');
    $a->request('page=users&action=delete&id='.$newUserId,[]);verify($users->find($newUserId)!==null,'Solicitante vinculado não é excluído');
    $room=['name'=>$prefix.' Sala','type'=>'Laboratório','capacity'=>20,'floor'=>'Térreo','features'=>'Projetor, Wi-Fi','image'=>'scene-1.jpg','status'=>'Disponível','description'=>'Sala exclusiva dos testes'];
    verify($a->request('page=rooms&action=store',array_replace($room,['capacity'=>0]))['code']===422,'Sala rejeita capacidade inválida');
    verify($a->request('page=rooms&action=store',$room)['code']===303,'Cadastrar sala');$roomId=(int)(new Room())->search($prefix)[0]['id'];
    verify($a->request('page=rooms&action=edit&id='.$roomId)['code']===200,'Formulário de edição de sala');
    $room['capacity']=24;verify($a->request('page=rooms&action=update&id='.$roomId,$room)['code']===303,'Editar sala');
    verify($t->request('page=rooms&action=show&id='.$roomId)['code']===200,'Detalhe da sala e disponibilidade');
    verify($t->request('page=booking&action=create&room='.$roomId)['code']===200,'Primeira etapa da reserva');
    $booking=['date'=>date('Y-m-d',strtotime('+10 days')),'start_time'=>'10:00','end_time'=>'12:00','purpose'=>$prefix.' Reserva','notes'=>'Teste automatizado'];
    $r=$t->request('page=booking&action=check&room='.$roomId,$booking);verify($r['code']===200&&str_contains($r['body'],'Finalizar reserva'),'Segunda etapa da reserva');
    verify($t->request('page=booking&action=store&room='.$roomId,array_replace($booking,['date'=>'2020-01-01']))['code']===422,'Rejeitar reserva no passado');
    verify($t->request('page=booking&action=store&room='.$roomId,array_replace($booking,['end_time'=>'09:00']))['code']===422,'Rejeitar término anterior ao início');
    verify($t->request('page=booking&action=store&room='.$roomId,array_replace($booking,['purpose'=>' ']))['code']===422,'Rejeitar finalidade vazia');
    $r=$t->request('page=booking&action=store&room='.$roomId,$booking);verify($r['code']===303,'Criar reserva pendente');
    $bookings=(new Reservation())->listing($teacherId);$bookingId=(int)$bookings[0]['id'];
    verify($t->request('page=booking&action=success&room='.$roomId.'&id='.$bookingId)['code']===200,'Confirmação da solicitação');
    verify($a->request('page=booking&action=success&room='.$roomId.'&id='.$bookingId)['code']===404,'Outra conta não acessa comprovante alheio');
    verify($t->request('page=booking&action=store&room='.$roomId,$booking)['code']===422,'Bloquear reserva sobreposta');
    verify($a->request('page=reservations&action=cancel&id='.$bookingId,[])['code']===403,'Impedir cancelamento de reserva alheia');
    verify($a->request('page=approvals&action=approve&id='.$bookingId,[])['code']===303&&(new Reservation())->find($bookingId)['status']==='Confirmado','Aprovar reserva');
    $a->request('page=approvals&action=reject&id='.$bookingId,[]);verify((new Reservation())->find($bookingId)['status']==='Confirmado','Impedir reanálise de reserva já aprovada');
    verify($t->request('page=reservations&action=cancel&id='.$bookingId,[])['code']===303&&(new Reservation())->find($bookingId)['status']==='Cancelado','Professor cancela a própria reserva');
    verify($t->request('page=booking&action=store&room='.$roomId,$booking)['code']===303,'Cancelamento libera o horário');
    $pending=(new Reservation())->listing($teacherId,'Pendente');$pendingId=(int)$pending[0]['id'];
    verify($a->request('page=approvals&action=reject&id='.$pendingId,[])['code']===303&&(new Reservation())->find($pendingId)['status']==='Recusado','Recusar reserva');
    $room['status']='Manutenção';$a->request('page=rooms&action=update&id='.$roomId,$room);
    verify($t->request('page=booking&action=store&room='.$roomId,$booking)['code']===422,'Manutenção impede reserva');
    $a->request('page=rooms&action=delete&id='.$roomId,[]);verify((new Room())->find($roomId)!==null,'Preservar sala com histórico de reservas');
    $r=$t->request('page=settings&action=update',['name'=>'','email'=>'invalido']);verify($r['code']===422,'Validar perfil no servidor');
    verify($a->request('page=tickets&action=delete&id='.$ticketId,[])['code']===303&&(new Ticket())->find($ticketId)===null,'Excluir chamado');
    verify($a->request('page=categories&action=delete&id='.$categoryId,[])['code']===303&&(new Category())->find($categoryId)===null,'Excluir categoria sem vínculos');
    verify($a->request('page=users&action=delete&id='.$newUserId,[])['code']===303&&$users->find($newUserId)===null,'Excluir usuário sem vínculos');
    $t->request('page=logout&action=store',[]);verify($t->request('page=reservations')['code']===303,'Logout invalida acesso');
    echo "\n{$checks} verificações HTTP passaram.\n";
} finally {
    // Remove somente os IDs e nomes exclusivos criados por esta execução.
    foreach(['tickets'=>'subject','categories'=>'name','rooms'=>'name','users'=>'name'] as $table=>$column) {
        if($table==='rooms'){$db->prepare('DELETE FROM reservations WHERE room_id IN (SELECT id FROM rooms WHERE name LIKE ?)')->execute([$prefix.'%']);}
        $db->prepare("DELETE FROM {$table} WHERE {$column} LIKE ?")->execute([$prefix.'%']);
    }
}

