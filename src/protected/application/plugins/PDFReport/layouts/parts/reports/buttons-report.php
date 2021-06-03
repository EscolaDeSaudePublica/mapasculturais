<!--botão de imprimir-->
<!-- <a class="btn btn-default" title="Imprimir Resultado Documental"  ng-click="editbox.open('report-evaluation-documental-options', $event)" rel="noopener noreferrer">
    <i class="fa fa-file-text-o" aria-hidden="true"></i>
</a>
<a class="btn btn-default" title="Imprimir Resultado Tecnica"  ng-click="editbox.open('report-evaluation-documental-options', $event)" rel="noopener noreferrer">
    <i class="fa fa-align-justify" aria-hidden="true"></i>
</a>
<a class="btn btn-default" title="Imprimir Resultado Simiples"  ng-click="editbox.open('report-evaluation-documental-options', $event)" rel="noopener noreferrer">
    <i class="fa fa-file-o" aria-hidden="true"></i>
</a> -->
<?php 

?>
<div>
    <hr>
    <form action="<?php echo $app->createUrl('pdf/gerarPdf'); ?>" method="POST" target="TargetWindow">
    <label class="label">Filtrar Relatório</label>
        
        <div id="divSelectTiebreaker" ng-controller="PdfReportController">
            <select name="selectRel" id="selectRel" class="" style="margin-left: 10px;" ng-model="filterReport" ng-change="changeFilterReport()">
            <option value="0">--Selecione--</option>
            <option value="1">Relação de Inscritos</option>
            <?php if($resource): ?>
                <option value="2">Resultados preliminares</option>
            <?php endif; ?>
            <option value="3">Resultados definitivos</option>
            <option value="4">Relação de contatos</option>
            </select>
            <span>{{filterReport}}</span>
                <div id="orderTiebreaker" class="hide-select">
                    <span>Ordem de desempate: </span> <br />
                </div>
                <select name="" id="selectTiebreaker" class="form-control show-select" ng-model="alter" ng-change="change()">
                    <option value="0">--Selecione--</option>
                    <option ng-repeat="opt in options" value="{{opt.value}}">
                        {{opt.label}}
                </select>
                <span>{{alter}}</span>
        </div>
        </div>
        <input type="hidden" id="idopportunityReport" name="idopportunityReport">
        <button type="submit">Gerar Relatório <i class="fa fa-file-pdf-o" aria-hidden="true"></i></button>
    </form>

</div>
<div>

    <?php 
        $entity = $this->controller->requestedEntity;
        $entity->id;
        $url = $app->createUrl('oportunidade/'.$entity->id);
        if(isset($_SESSION['error']))
        {
            echo '<hr><div class="alert danger">'.$_SESSION['error'].'<a href="'.$url.'" class="alignright"><i class="fa fa-times" aria-hidden="true"></i></a></div>';
        }
        unset($_SESSION['error']);
    ?>
</div>