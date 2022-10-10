<?php

namespace EventImporter;

use MapasCulturais\App;
use MapasCulturais\Definitions;

class Module extends \MapasCulturais\Module
{

    function __construct($config = []) 
    {
        $app = App::i();

        $config += [
            'frequence_list_allowed' => $app->config['eventimporter.frequence_list_allowed'],
            'rating_list_allowed' => $app->config['eventimporter.rating_list_allowed'],
            'days_list_positive' => $app->config['eventimporter.days_list_positive'],
            'week_days' => $app->config['eventimporter.week_days'],
            'use_endsat' => $app->config['eventimporter.use_endsat'],
            'dic_months' => $app->config['eventimporter.dic_months'],
            'files_grp_import' => $app->config['eventimporter.files_grp_import'],
            'metalists_import' => $app->config['eventimporter.metalists_import'],
        ];

        parent::__construct($config);

    }

    function _init()
    {
        $app = App::i();

        $app->view->enqueueStyle('app','assets-file','css/eventimporter.css');
        //Inseri parte para upload na sidbar direita
        $app->hook('template(panel.events.settings-nav):begin', function() use($app) {
            /** @var Theme $this */
            $this->controller = $app->controller('agent');
            $this->part('upload-csv-event',['entity' => $app->user->profile]);
            $this->controller = $app->controller('panel');

        });
    }

    function register()
    {
        $app = App::i();

        $app->registerController('eventimporter', Controller::class);

        $this->registerAgentMetadata('event_importer_processed_file', [
            'label' => 'Arquivo de processamento de importação',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);
        
        $app->registerFileGroup(
            'agent',
            new Definitions\FileGroup(
                'event-import-file',
                ['text/csv','application/zip'],
                'O arquivo não e valido'
            )
        );
    }
}
