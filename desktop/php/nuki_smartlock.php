<?php

if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'nuki_smartlock');
$eqLogics = eqLogic::byType('nuki_smartlock');

?>

<div class="row row-overflow">
  <div class="col-lg-12 eqLogicThumbnailDisplay">
    <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br>
        <span>{{Configuration}}</span>
      </div>
      <div class="cursor logoSecondary" id="bt_syncEqLogic">
        <i class="fas fa-sync"></i>
        <br>
        <span>{{Synchroniser}}</span>
      </div>

      <div class="cursor logoSecondary" id="btLogs">
        <i class="fas fa-file-alt"></i>
        <br>
        <span>{{Logs Serrures}}</span>
      </div>

      <div class="cursor logoSecondary" id="btAuth">
        <i class="fas fa-user-lock"></i>
        <br>
        <span>{{Autorisation Serrures}}</span>
      </div>
    </div>
    <legend><i class="techno-cable1"></i> {{Mes Nukis}}
    </legend>
    <?php
    if (count($eqLogics) == 0) {
      echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez pas encore de Nuki, cliquez sur Synchroniser pour commencer}}</span></center>";
    } else {
    ?>
      <div class="eqLogicThumbnailContainer">
        <?php
        foreach ($eqLogics as $eqLogic) {
          $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
          echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
          if ($type = $eqLogic->getConfiguration('type')) {
            echo '<img src="plugins/nuki_smartlock/core/config/devices/' . $type . '.png"/>';
          } else {
            echo '<img src="plugins/nuki_smartlock/plugin_info/nuki_smartlock.png"/>';
          }
          echo "<br>";
          echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
          echo '</div>';
        }
        ?>
      </div>
    <?php } ?>
  </div>
  <div class="col-lg-12 eqLogic" style="display: none;">
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
    <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <br />
        <div class="row">
          <div class="col-sm-9">
            <form class="form-horizontal">
              <fieldset>
                <div class="form-group">
                  <label class="col-lg-2 control-label">{{Nom de l'équipement nuki}}</label>
                  <div class="col-lg-4">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement nuki}}" />
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-lg-2 control-label">{{Objet parent}}</label>
                  <div class="col-lg-4">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                      <option value="">{{Aucun}}</option>
                      <?php
                      foreach (jeeObject::all() as $object) {
                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-lg-2 control-label">{{Catégorie}}</label>
                  <div class="col-lg-8">
                    <?php
                    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                      echo '<label class="checkbox-inline">';
                      echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                      echo '</label>';
                    }
                    ?>

                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label"></label>
                  <div class="col-sm-6">
                    <input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="isEnable" checked />Activer
                    <input type="checkbox" class="eqLogicAttr" data-label-text="{{Visible}}" data-l1key="isVisible" checked />Visible
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label">{{Nuki Bridge Id}}</label>
                  <div class="col-sm-6">
                    <span class="eqLogicAttr" data-l1key="configuration" data-l2key="nukiId"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label">{{Nuki Web Id}}</label>
                  <div class="col-sm-6">
                    <span class="eqLogicAttr" data-l1key="configuration" data-l2key="nukiWebId"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label">{{Type}}</label>
                  <div class="col-sm-6">
                    <span class="eqLogicAttr" data-l1key="configuration" data-l2key="type" id="type"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label">{{Nuki Type Id}}</label>
                  <div class="col-sm-6">
                    <span class="eqLogicAttr" data-l1key="configuration" data-l2key="nukiType"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label">{{Nom Nuki}}</label>
                  <div class="col-sm-6">
                    <span class="eqLogicAttr" data-l1key="configuration" data-l2key="name"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label">{{Firmware}}</label>
                  <div class="col-sm-6">
                    <span class="eqLogicAttr" data-l1key="configuration" data-l2key="firmware"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label"></label>
                  <div class="col-sm-6">
                    <a class="btn btn-default" id='btLogsLock'><i class="fas fa-file-alt"></i> {{Voir les logs actions}}</a>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-3 control-label"></label>
                  <div class="col-sm-6">
                    <a class="btn btn-default" id='btAuthLock'><i class="fas fa-user-lock"></i> {{Voir les autorisations}}</a>
                  </div>
                </div>


              </fieldset>
            </form>
          </div>
          <div class="col-sm-3">
            <div>
                <img id="nukiTypeImg" src="plugins/nuki_smartlock/plugin_info/nuki_smartlock_icon.png" style="max-width: 300px; height: auto;" />
            </div>
          </div>
        </div>
      </div>

      <div role="tabpanel" class="tab-pane" id="commandtab">
        <br />
        <table id="table_cmd" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th style="width: 300px;">{{Nom}}</th>
              <th style="width: 100px;">{{Type}}</th>
              <th style="width: 200px;">{{Options}}</th>
              <th style="width: 100px;"></th>
            </tr>
          </thead>
          <tbody>

          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include_file('desktop', 'nuki_smartlock', 'js', 'nuki_smartlock'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>