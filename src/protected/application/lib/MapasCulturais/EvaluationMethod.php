<?php
namespace MapasCulturais;

use \MapasCulturais\i;

abstract class EvaluationMethod extends Plugin implements \JsonSerializable{
    abstract protected function _register();

    abstract function enqueueScriptsAndStyles();

    abstract function getSlug();
    abstract function getName();
    abstract function getDescription();

    abstract protected function _getConsolidatedResult(Entities\Registration $registration);
    abstract function getEvaluationResult(Entities\RegistrationEvaluation $evaluation);

    abstract function valueToString($value);

    function getReportConfiguration($opportunity, $call_hooks = true){
        $app = App::i();

        // Registration Section Columns
        $registration_columns = [];
        if($opportunity->projectName){
            $registration_columns['projectName'] = (object) [
                'label' => i::__('Nome do projeto'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation){
                    return $evaluation->registration->projectName;
                }
            ];
        }

        if($opportunity->registrationCategories){
            $registration_columns['category'] = (object) [
                'label' => i::__('Categoria de inscrição'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation){
                    return $evaluation->registration->category;
                }
            ];
        }

        $registration_columns = $registration_columns + [
            'owner' => (object) [
                'label' => i::__('Agente Responsável'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation){
                    return $evaluation->registration->owner->name;
                }
            ],
            'number' => (object) [
                'label' => i::__('Número de inscrição'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation){
                    return $evaluation->registration->number;
                }
            ],
        ];


        /*
         * @TODO: adicionar as colunas abaixo:
         * - fatiamento?
         * - tempo de permanência na avaliacao
         */
        $committee_columns = [
            'evaluator' => (object) [
                'label' => i::__('Nome'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) {
                    return $evaluation->user->profile->name;
                }
            ]
        ];


        $evaluation_columns = [
            'status' => (object) [
                'label' => i::__('Status'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) {
                    return $evaluation->getStatusString();
                }
            ],
            'result' => (object) [
                'label' => i::__('Resultado'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) {
                    return $evaluation->getResultString();
                }
            ],
        ];

        $sections = [
            'registration' => (object) [
                'label' => i::__('Informações sobre as inscrições e proponentes'),
                'color' => '#CCCCFF',
                'columns' => $registration_columns
            ],

            'committee' => (object) [
                'label' => i::__('Informações sobre o avaliador'),
                'color' => '#CCFFCC',
                'columns' => $committee_columns
            ],

            'evaluation' => (object) [
                'label' => i::__('Avaliação'),
                'color' => '#FFCCCC',
                'columns' => $evaluation_columns
            ]
        ];

        if($call_hooks){
            $app->applyHookBoundTo($this, "evaluationsReport({$this->slug}).sections", [$opportunity, &$sections]);

            foreach($sections as $section_slug => &$section){
                $app->applyHookBoundTo($this, "evaluationsReport({$this->slug}).section({$section_slug})", [$opportunity, &$section]);
            }
        }

        return $sections;
    }


    function evaluationToString(Entities\RegistrationEvaluation $evaluation){
        return $this->valueToString($evaluation->result);
    }
    
    function fetchRegistrations(){
        return false;
    }

    function getConsolidatedResult(Entities\Registration $registration){
        $registration->checkPermission('viewConsolidatedResult');

        return $this->_getConsolidatedResult($registration);
    }

    public function canUserEvaluateRegistration(Entities\Registration $registration, $user){
        if($user->is('guest')){
            return false;
        }
        
        $config = $registration->getEvaluationMethodConfiguration();
        
        $can = $config->canUser('@control');
        
        if($can && $this->fetchRegistrations()){
            
            $fetch = [];
            if(!is_null($config->fetch)){
                foreach($config->fetch as $id => $val){
                    $fetch [(int)$id] = $val;
                }
            }
            $fetch_categories = [];
            if(!is_null($config->fetchCategories)){
                foreach($config->fetchCategories as $id => $val){
                    $fetch_categories [(int)$id] = $val;
                }
            }

            if(isset($fetch[$user->id])){
                $ufetch = $fetch[$user->id];
                if(preg_match("#([0-9]+) *[-] *([0-9]+)*#", $ufetch, $matches)){
                    $s1 = $matches[1];
                    $s2 = $matches[2];
                    
                    $len = max([strlen($s1), strlen($s2)]);
                    
                    $fin = substr($registration->id, -$len);
                    
                    if(intval($s2) == 0){ // "00" => "100"
                        $s2 = "1$s2";
                    }
                    if($fin < $s1 || $fin > $s2){
                        $can = false;
                    }
                }
            }

            if(isset($fetch_categories[$user->id])){
                $ucategories = $fetch_categories[$user->id];
                if($ucategories){
                    $categories = explode(';', $ucategories);
                    if($categories){
                        $found = false;

                        foreach($categories as $cat){
                            $cat = trim($cat);
                            if(strtolower($registration->category) === strtolower($cat)){
                                $found = true;
                            }
                        }

                        if(!$found) {
                            $can = false;
                        }
                    }
                }
            } 
        }

        return $can;
    }

    function canUserViewConsolidatedResult(Entities\Registration $registration){
        $opp = $registration->opportunity;

        if($opp->publishedRegistrations || $opp->canUser('@control')){
            return true;
        } else {
            return false;
        }
    }

    function getEvaluationFormPartName(){
        $slug = $this->getSlug();

        return "$slug--evaluation-form";
    }

    function getEvaluationViewPartName(){
        $slug = $this->getSlug();

        return "$slug--evaluation-view";
    }

    function getEvaluationFormInfoPartName(){
        $slug = $this->getSlug();

        return "$slug--evaluation-info";
    }
    
    function getConfigurationFormPartName(){
        $slug = $this->getSlug();

        return "$slug--configuration-form";
    }

    function register(){
        $app = App::i();

        $def = new Definitions\EvaluationMethod($this);

        $app->registerEvaluationMethod($def);

        $type = new Definitions\EntityType('MapasCulturais\Entities\EvaluationMethodConfiguration', $this->getSlug(), $this->getName());

        $app->registerEntityType($type);

        $this->_register();

        $self = $this;

        $app->hook('view.includeAngularEntityAssets:after', function() use($self){
            $self->enqueueScriptsAndStyles();
        });
        
        if($this->fetchRegistrations()){
            $this->registerEvaluationMethodConfigurationMetadata('fetch', [
                'label' => i::__('Configuração do fatiamento das inscrições entre os avaliadores'),
                'serialize' => function ($val) {
                    return json_encode($val);
                },
                'unserialize' => function($val) {
                    return json_decode($val);
                }
            ]);
            $this->registerEvaluationMethodConfigurationMetadata('fetchCategories', [
                'label' => i::__('Configuração do fatiamento das inscrições entre os avaliadores por categoria'),
                'serialize' => function ($val) {
                    return json_encode($val);
                },
                'unserialize' => function($val) {
                    return json_decode($val);
                }
            ]);
        }

    }
    
    function registerEvaluationMethodConfigurationMetadata($key, array $config){
        $app = App::i();

        $metadata = new Definitions\Metadata($key, $config);

        $app->registerMetadata($metadata, 'MapasCulturais\Entities\EvaluationMethodConfiguration', $this->getSlug());
    }

    function usesEvaluationCommittee(){
        return true;
    }

    public function jsonSerialize() {
        return null;
    }
}
