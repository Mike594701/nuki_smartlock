<?php

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-lg-4 control-label">{{Adresse IP du Bridge Nuki}}</label>
      <div class="col-lg-2">
        <input class="configKey tooltips form-control" data-l1key="bridge_ip" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-4 control-label">{{Port du Bridge Nuki}}</label>
      <div class="col-lg-2">
        <input class="configKey tooltips form-control" data-l1key="bridge_port" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-4 control-label">{{API token du Bridge Nuki}}</label>
      <div class="col-lg-2">
        <input class="configKey tooltips form-control" data-l1key="api_token" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-lg-4 control-label">{{API token de Nuki Web}}</label>
      <div class="col-lg-2">
        <input class="configKey tooltips form-control" data-l1key="api_web" />
      </div>
    </div>
  </fieldset>
</form>
<script>
 

</script>