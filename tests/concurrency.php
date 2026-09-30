<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
require dirname(__DIR__).'/app/bootstrap.php';
use App\Models\{Room,User,Reservation};
use App\Core\Database;
if(($argv[1]??'')==='--worker'){
    [$script,$flag,$room,$user,$date,$startAt]=$argv;
    while(microtime(true)<(float)$startAt){usleep(10000);}
    try{
        (new Reservation())->book(['room_id'=>(int)$room,'user_id'=>(int)$user,'date'=>$date,'start_time'=>'10:00','end_time'=>'12:00','purpose'=>'Concorrência de teste','notes'=>'','status'=>'Pendente']);
        echo 'CREATED';
    }catch(DomainException $e){echo 'CONFLICT';}
    exit;
}
$prefix='CONC-'.bin2hex(random_bytes(5));$roomId=0;$userId=0;$processes=[];
try{
    $userId=(new User())->save(['name'=>$prefix,'email'=>$prefix.'@example.test','role'=>'Usuário']);
    $roomId=(new Room())->save(['name'=>$prefix,'type'=>'Sala de Aula','capacity'=>10,'floor'=>'Térreo','features'=>'Projetor','image'=>'scene-5.jpg','status'=>'Disponível','description'=>'Teste de concorrência']);
    $startAt=(string)(microtime(true)+1);
    for($i=0;$i<2;$i++){
        $pipes=[];
        $process=proc_open([PHP_BINARY,__FILE__,'--worker',(string)$roomId,(string)$userId,date('Y-m-d',strtotime('+20 days')),$startAt],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process)){throw new RuntimeException('Não foi possível iniciar processo de teste.');}
        fclose($pipes[0]);$processes[]=[$process,$pipes];
    }
    $results=[];
    foreach($processes as [$process,$pipes]){
        $results[]=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($process)!==0){throw new RuntimeException($error);}
    }
    sort($results);
    if($results!==['CONFLICT','CREATED']){throw new RuntimeException('Resultado inesperado: '.json_encode($results));}
    echo "OK: duas solicitações simultâneas produzem uma reserva e um conflito.\n";
    if(count((new Reservation())->listing($userId))!==1){throw new RuntimeException('Mais de uma reserva foi persistida.');}
    echo "OK: somente uma reserva persistida no MySQL.\n";
}finally{
    if($roomId){Database::connection()->prepare('DELETE FROM reservations WHERE room_id = ?')->execute([$roomId]);(new Room())->delete($roomId);}
    if($userId){(new User())->delete($userId);}
}
