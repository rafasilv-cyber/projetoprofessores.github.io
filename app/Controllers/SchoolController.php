<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller, Auth, Validation};
use App\Models\{Room, Reservation, User};
use DomainException;
use PDOException;

final class SchoolController extends Controller
{
    public function handle(string $page,string $action,?int $id): void
    {
        match($page) {
            'dashboard' => $this->dashboard(), 'rooms' => $this->rooms($action,$id),
            'booking' => $this->booking($action), 'reservations','approvals' => $this->reservations($page,$action,$id),
            'calendar' => $this->calendar(), 'settings' => $this->settings($action),
        };
    }
    private function dashboard(): void
    {
        $admin=Auth::adminView(); $rooms=(new Room())->search();
        $all=(new Reservation())->listing($admin?null:(int)Auth::user()['id']);
        $active=array_values(array_filter($all,fn($b)=>in_array($b['status'],['Pendente','Confirmado'],true)&&$b['date']>=date('Y-m-d')));
        $pending=array_values(array_filter($all,fn($b)=>$b['status']==='Pendente'));
        $weekStart=(new \DateTimeImmutable('monday this week'))->format('Y-m-d');
        $this->render($admin?'admin-dashboard':'dashboard',compact('rooms','all','active','pending','weekStart')+[
            'page'=>'dashboard','title'=>$admin?'Painel de Controle':'Início','subtitle'=>\app_config('school_name'),'userCount'=>count(array_filter((new User())->search(),fn($u)=>$u['role']==='Usuário')),
        ]);
    }
    private function rooms(string $action,?int $id): void
    {
        $model=new Room(); $record=$id?$model->find($id):[];
        if ($id&&!$record) { $this->error(404,'Sala não encontrada.'); return; }
        if ($action==='index') {
            $search=$this->query('q'); $type=$this->query('type');
            $capacity=max(0,(int)$this->query('capacity','0')); $available=$this->query('available')==='1';
            $rows=$model->search($search,$type,$capacity,$available);
            $this->render('rooms',compact('rows','search','type','capacity','available')+['page'=>'rooms','title'=>Auth::adminView()?'Gerenciar Salas':'Explorar Salas','subtitle'=>Auth::adminView()?'Ambientes e recursos cadastrados':'Encontre e reserve o ambiente ideal']);
        } elseif ($action==='show') {
            $date=$this->query('date',date('Y-m-d',strtotime('+1 day')));
            if (!$this->validDate($date)) { $date=date('Y-m-d'); }
            $bookings=(new Reservation())->listing(null,'',$date,$date,$id);
            $this->render('room-detail',compact('record','date','bookings')+['page'=>'rooms','title'=>$record['name'],'subtitle'=>$record['type'].' · '.$record['floor']]);
        } elseif ($action==='delete') {
            try { $model->delete($id); $this->flash('Sala excluída com sucesso.'); }
            catch(PDOException $e) { if (($e->errorInfo[1]??0)!==1451) {throw $e;} $this->flash('Esta sala tem reservas vinculadas e não pode ser excluída. Você pode colocá-la em manutenção.','error'); }
            $this->redirect('rooms');
        } else {
            $errors=[];
            if (in_array($action,['store','update'],true)) {
                $v=new Validation();
                $v->text($_POST,'name','Nome da sala',120)->text($_POST,'floor','Andar',60)->text($_POST,'features','Recursos',500)->text($_POST,'description','Descrição',3000)
                    ->choice($_POST,'type',Room::TYPES)->choice($_POST,'status',Room::STATUSES)->choice($_POST,'image',array_map(fn($n)=>'scene-'.$n.'.jpg',range(1,6)));
                $raw=$_POST['capacity']??null;
                $number=is_scalar($raw)?filter_var($raw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>2000]]):false;
                if (!$number) {$v->errors['capacity']='Informe uma capacidade entre 1 e 2000 pessoas.';}
                $v->data['capacity']=$number?:'';
                if (!$v->errors) {
                    try { $model->save($v->data,$id); $this->flash('Sala salva com sucesso.'); $this->redirect('rooms'); }
                    catch(PDOException $e) { if (($e->errorInfo[1]??0)!==1062) {throw $e;} $v->errors['name']='Já existe uma sala com este nome.'; }
                }
                $record=$v->data+['id'=>$id]; $errors=$v->errors; http_response_code(422);
            }
            $this->render('room-form',compact('record','errors')+['page'=>'rooms','title'=>$id?'Editar sala':'Nova sala','subtitle'=>'Ambientes e recursos cadastrados']);
        }
    }
    private function validDate(string $date): bool
    {
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        return $parsed&&$parsed->format('Y-m-d')===$date;
    }
    private function booking(string $action): void
    {
        $roomId=(int)$this->query('room','0'); $room=(new Room())->find($roomId);
        if (!$room) { $this->error(404,'Sala não encontrada.'); return; }
        if ($action==='success') {
            $booking=(new Reservation())->find((int)$this->query('id','0'));
            if (!$booking || (int)$booking['user_id']!==(int)Auth::user()['id'] || (int)$booking['room_id']!==$roomId) { $this->error(404,'Reserva não encontrada.'); return; }
            $this->render('booking-success',compact('room','booking')+['page'=>'rooms','title'=>'Reserva solicitada','subtitle'=>$room['name']]); return;
        }
        $values=['date'=>date('Y-m-d',strtotime('+1 day')),'start_time'=>'10:00','end_time'=>'12:00','purpose'=>'','notes'=>''];
        if ($action==='create') {
            $chosenDate=$this->query('date');
            if ($this->validDate($chosenDate)&&$chosenDate>=date('Y-m-d')) {$values['date']=$chosenDate;}
            foreach(['start_time','end_time'] as $timeField){$chosen=$this->query($timeField);if(preg_match('/^(0[7-9]|1[0-8]):00$/',$chosen)){$values[$timeField]=$chosen;}}
        }
        $errors=[]; $step=1;
        if (in_array($action,['check','store'],true)) {
            foreach ($values as $key=>$default) { $values[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):''; }
            if (!$this->validDate($values['date']) || $values['date']<date('Y-m-d')) {$errors['date']='Escolha uma data válida a partir de hoje.';}
            foreach (['start_time','end_time'] as $key) {
                if (!preg_match('/^(0[7-9]|1[0-7]):00$|^18:00$/',$values[$key])) {$errors[$key]='Selecione um horário válido.';}
            }
            if (!$errors && $values['end_time']<=$values['start_time']) {$errors['end_time']='O término deve ser posterior ao início.';}
            if (!$errors && $values['date']===date('Y-m-d') && $values['start_time']<=date('H:i')) {$errors['start_time']='Escolha um horário futuro.';}
            if ($room['status']==='Manutenção') {$errors['_form']='Esta sala está em manutenção. Escolha outro ambiente.';}
            if (!$errors && (new Reservation())->conflicts($roomId,$values['date'],$values['start_time'],$values['end_time'])) {$errors['_form']='Este horário já está reservado. Escolha outro período.';}
            if (!$errors) { $step=2; }
            if ($action==='store' && !$errors) {
                $v=new Validation(); $v->text($values,'purpose','Finalidade',180)->text($values,'notes','Observações',3000,false); $errors=$v->errors;
                if (!$errors) {
                    try {
                        $id=(new Reservation())->book($values+['room_id'=>$roomId,'user_id'=>(int)Auth::user()['id'],'status'=>'Pendente']);
                        $this->redirect('booking',['action'=>'success','room'=>$roomId,'id'=>$id]);
                    } catch(DomainException $e) { $errors['_form']=$e->getMessage(); $step=1; }
                }
            }
            if ($errors) { http_response_code(422); }
        }
        $this->render('booking',compact('room','values','errors','step')+['page'=>'rooms','title'=>'Nova Reserva','subtitle'=>$room['name']]);
    }
    private function reservations(string $page,string $action,?int $id): void
    {
        $model=new Reservation(); $admin=$page==='approvals';
        if ($action!=='index') {
            $record=$model->find($id);
            if (!$record) { $this->error(404,'Reserva não encontrada.'); return; }
            if (!$admin && (int)$record['user_id']!==(int)Auth::user()['id']) {$this->error(403,'Você só pode cancelar suas próprias reservas.');return;}
            if ($record['date']<date('Y-m-d')) { $this->flash('Reservas passadas não podem ser alteradas.','error'); $this->redirect($page); }
            if ($admin && $record['status']!=='Pendente') { $this->flash('Esta solicitação já foi analisada.','error'); $this->redirect($page); }
            try {
                $status=match($action){'approve'=>'Confirmado','reject'=>'Recusado','cancel'=>'Cancelado'};
                $model->changeStatus($id,$status);
                $this->flash(match($action){'approve'=>'Reserva aprovada.','reject'=>'Reserva recusada.','cancel'=>'Reserva cancelada.'});
            } catch(DomainException $e) {$this->flash($e->getMessage(),'error');}
            $this->redirect($page);
        }
        $tab=$this->query('tab',$admin?'pending':'upcoming'); $all=$model->listing($admin?null:(int)Auth::user()['id']);
        $rows=array_values(array_filter($all,fn($b)=>$admin?($tab==='all'||$b['status']==='Pendente'):($tab==='past'?($b['date']<date('Y-m-d')||in_array($b['status'],['Cancelado','Recusado'],true)):($b['date']>=date('Y-m-d')&&in_array($b['status'],['Pendente','Confirmado'],true)))));
        $this->render('reservations',compact('rows','all','tab')+['page'=>$page,'title'=>$admin?'Aprovações':'Minhas Reservas','subtitle'=>$admin?'Gerencie solicitações de reserva':'Suas solicitações e reservas confirmadas']);
    }
    private function calendar(): void
    {
        $week=$this->query('week',date('Y-m-d')); if (!$this->validDate($week)) {$week=date('Y-m-d');}
        $start=(new \DateTimeImmutable($week))->modify('monday this week'); $roomId=max(0,(int)$this->query('room','0'));
        $rows=(new Reservation())->listing(null,'',$start->format('Y-m-d'),$start->modify('+4 days')->format('Y-m-d'),$roomId?:null);
        $rooms=(new Room())->options();
        $this->render('calendar',compact('start','roomId','rows','rooms')+['page'=>'calendar','title'=>'Calendário Semanal','subtitle'=>'Visão geral de ocupação']);
    }
    private function settings(string $action): void
    {
        $record=Auth::user(); $errors=[];
        if ($action==='update') {
            $v=new Validation();$v->text($_POST,'name','Nome',120)->email($_POST); $user=new User();
            if (!isset($v->errors['email'])&&$user->emailTaken($v->data['email'],(int)$record['id'])) {$v->errors['email']='Este e-mail já está cadastrado.';}
            if (!$v->errors) {
                try {$user->save($v->data,(int)$record['id']);$this->flash('Perfil atualizado.');$this->redirect('settings');}
                catch(PDOException $e){if(($e->errorInfo[1]??0)!==1062){throw $e;}$v->errors['email']='Este e-mail já está cadastrado.';}
            }
            $record=array_replace($record,$v->data);$errors=$v->errors;http_response_code(422);
        }
        $this->render('settings',compact('record','errors')+['page'=>'settings','title'=>'Configurações','subtitle'=>'Seus dados e preferências de acesso']);
    }
}
