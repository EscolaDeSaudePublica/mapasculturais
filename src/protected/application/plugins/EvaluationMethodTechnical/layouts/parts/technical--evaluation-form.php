<?php
use MapasCulturais\i;
$plugin = $app->plugins['EvaluationMethodTechnical'];

$params = ['registration' => $entity, 'opportunity' => $opportunity];
//VERIFICA SE O AVALIADOR ENVIOU AS NOTAS
$enabled = $app->repo('AgentRelation')->findBy([
    'objectId' => $opportunity->evaluationMethodConfiguration->id,
    'agent' => $app->user->profile->id
]);
$disabled = '';
if (count($enabled)) {
    if($enabled[0]->status == 10){
        $disabled = 'disabled';
    }
}
 
if($disabled == 'disabled') :
    echo '<div class="alert danger">
    <span>A avaliação já foi enviada. Não é possível alterar as notas.</span>
</div>';
endif;
$this->applyTemplateHook('evaluationForm.technical', 'before', $params); ?>
<div ng-controller="TechnicalEvaluationMethodFormController" class="technical-evaluation-form">
    <?php $this->applyTemplateHook('evaluationForm.technical', 'begin', $params); ?>
    <div class="alert-evaluation-load" id="alert-evaluation-load-div">
        <span id="successEvaluationNote" class="load-evaluation-note">A avaliação foi salva</span>
    </div>
    <section ng-repeat="section in ::data.sections" ng-if="section.categories.indexOf(data.registrationCategory) != -1">
        <table>
            <tr>
                <th colspan="2">
                    {{section.name}}</br>
                    Peso: {{ section.weight  }}
                </th>
            </tr>
            <tr ng-repeat="cri in ::data.criteria" ng-if="cri.sid == section.id">
                
                <td><label for="{{cri.id}}">{{cri.title}}:</label></td>
                <td>
                    <input id="{{cri.id}}" name="data[{{cri.id}}]" type="number" step="<?php echo $plugin->step ?>" <?php echo $disabled; ?> min="{{cri.min}}" max="{{cri.max}}" ng-model="evaluation[cri.id]" class="hltip" title="Configurações: min: {{cri.min}}<br>max: {{cri.max}}<br>peso: {{cri.weight}}"><input type="checkbox" name="data[na][{{cri.id}}]" ng-checked="{{ evaluation.na[cri.id] }}" value="true"> Não se aplica 
                </td>
            </tr>
            <tr class="subtotal">
                <td><?php i::_e('Subtotal')?></td>
                <td>
                {{subtotalSection(section) == 'NaN' ? '---' : subtotalSection(section)}}
                </td>
            </tr>
        </table>
    </section>
    <hr>
    <label>
        <?php i::_e('Parecer Técnico') ?>
        <textarea name="data[obs]" ng-model="evaluation['obs']"></textarea>
    </label>
    <hr>
    
    <label ng-show="data.enableViability=='true'">
        <strong> <?php i::_e('Exequibilidade Orçamentária'); ?> </strong> <span class="required">*</span> <br>
        <?php i::_e('Esta proposta está adequada ao orçamento apresentado? Os custos orçamentários estão compatíveis com os praticados no mercado?'); ?>
        <br>
        <label class="input-label">
            <input type="radio" name="data[viability]" value="valid" ng-model="evaluation['viability']" required="required"/>
            <em><?php i::_e('Sim')?></em> <br>

            <input type="radio" name="data[viability]" value="invalid" ng-model="evaluation['viability']"/>
            <em><?php i::_e('Não')?></em>
        </label>
    </label>
    <hr>
    <div class='total'>
        <?php i::_e('Pontuação Total'); ?>: <strong>{{total(total) == 'NaN' ? '---' : total(total)}}</strong><br>
        <?php i::_e('Pontuação Máxima'); ?>: <strong>{{max(total)}}</strong>
    </div>
    <?php $this->applyTemplateHook('evaluationForm.technical', 'end', $params); ?>
</div>
<?php $this->applyTemplateHook('evaluationForm.technical', 'after', $params); ?>
