<?php
function icon(string $name, string $class = ''): string { return '<img class="icon '.e($class).'" src="public/assets/icons/'.e($name).'.svg" width="18" height="18" alt="" aria-hidden="true">'; }
function initials(string $name): string { $parts=explode(' ',trim($name)); return mb_strtoupper(mb_substr($parts[0],0,1).(count($parts)>1?mb_substr(end($parts),0,1):'')); }
function date_br(string $date): string { return date('d/m/Y',strtotime($date)); }
function month_short(string $date): string { return ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'][(int)date('n',strtotime($date))-1]; }
function badge(string $status): string {
    $class=match($status){'Confirmado','Disponível','Resolvido'=>'solid','Pendente','Em andamento','Ocupado'=>'outline','Recusado','Cancelado','Manutenção','Fechado'=>'muted',default=>'light'};
    return '<span class="badge badge-'.$class.'">'.e($status).'</span>';
}
function room_icon(string $type): string {return match($type){'Laboratório'=>'flask-conical','Biblioteca'=>'book-open','Auditório'=>'mic','Informática'=>'monitor','Sala de Aula'=>'graduation-cap',default=>'users'};}
function field(string $name,string $label,array $record,array $errors=[],string $type='text',bool $required=true,int $max=180): void {
    $value=$type==='password'?'':($record[$name]??'');
    echo '<div class="field"><label for="'.e($name).'">'.e($label).($required?' <span aria-hidden="true">*</span>':'').'</label>';
    $attrs=' id="'.e($name).'" name="'.e($name).'" '.($required?'required ':'').'maxlength="'.$max.'"'.(isset($errors[$name])?' aria-invalid="true" aria-describedby="error-'.e($name).'"':'');
    if ($type==='textarea') {echo '<textarea'.$attrs.' rows="4">'.e($value).'</textarea>';}
    else {
        if($type==='password'){echo '<div class="password-input">';}
        echo '<input'.$attrs.' type="'.e($type).'" value="'.e($value).'"'.($type==='password'?' autocomplete="new-password" minlength="8"':'').($type==='number'?' min="1" max="2000"':'').'>';
        if($type==='password'){echo password_toggle($name).'</div>';}
    }
    if(isset($errors[$name])){echo '<p class="field-error" id="error-'.e($name).'">'.e($errors[$name]).'</p>';}
    echo '</div>';
}
function select_field(string $name,string $label,array $options,array $record,array $errors=[],bool $required=true): void {
    echo '<div class="field"><label for="'.e($name).'">'.e($label).($required?' <span aria-hidden="true">*</span>':'').'</label><select id="'.e($name).'" name="'.e($name).'"'.($required?' required':'').(isset($errors[$name])?' aria-invalid="true" aria-describedby="error-'.e($name).'"':'').'>';
    echo '<option value="">'.($required?'Selecione uma opção':'Não atribuído').'</option>';
    foreach($options as $key=>$labelText){echo '<option value="'.e($key).'"'.((string)($record[$name]??'')===(string)$key?' selected':'').'>'.e($labelText).'</option>';}
    echo '</select>';if(isset($errors[$name])){echo '<p class="field-error" id="error-'.e($name).'">'.e($errors[$name]).'</p>';}echo '</div>';
}
function options_for(array $list): array {return array_combine($list,$list);}
function password_toggle(string $id): string {return '<button type="button" class="password-toggle" data-password-toggle="'.e($id).'" aria-controls="'.e($id).'" aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha">'.icon('eye').'</button>';}
function form_errors(array $errors): void {if($errors){echo '<div class="alert" role="alert"><strong>Revise os dados informados.</strong>';if(isset($errors['_form'])){echo '<p>'.e($errors['_form']).'</p>';}echo '</div>';}}
function empty_state(string $title='Nenhum resultado encontrado',string $text='Tente ajustar sua busca ou adicionar um novo registro.'): void {echo '<div class="empty-state">'.icon('search').'<h3>'.e($title).'</h3><p>'.e($text).'</p></div>';}
function delete_button(string $page,int $id,string $label): void {echo '<form method="post" action="'.e(url($page,['action'=>'delete','id'=>$id])).'" data-confirm="Excluir '.e($label).'? Esta ação não pode ser desfeita.">'.csrf_field().'<button class="icon-button" aria-label="Excluir '.e($label).'" title="Excluir">'.icon('trash-2').'</button></form>';}
