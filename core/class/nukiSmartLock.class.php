<?php

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class nukiSmartLock extends eqLogic{
  public static function add_log($level = 'debug',$Log){
    if (is_array($Log)) $Log = json_encode($Log);
      $ligne = debug_backtrace(false, 2)[0]['line'];
    if (isset(debug_backtrace(false, 2)[1]['function'])) {
      $function_name = debug_backtrace(false, 2)[1]['function'];
      $msg =  $function_name .' (' . $ligne . '): '.$Log;
  
    }else{
      $msg =  '(' . $ligne . '): '.$Log;
    }
    log::add('nukiSmartLock' , $level,$msg);
  
  }
  public  function getNukiWebId(){
    if ($nukiWebId = $this->getConfiguration('nukiWebId')) {
      return $nukiWebId;
    }
    return $this->getLogicalId();
  }

  public static function byNukiWebId($id){
    $eqLogics = self::byType('nukiSmartLock');
    foreach ($eqLogics as $eqLogic) {
      if ($eqLogic->getConfiguration('type') == 'doorsensor') {
        # Avoid selecting door sensors cause they don't really exist in the Nuki Web API
        continue;
      }
      if ($eqLogic->getConfiguration('nukiWebId') == $id) {
        return $eqLogic;
      }
    }
    return eqLogic::byLogicalId($id, 'nukiSmartLock');
  }

  public static function getTriggers(){
    return array(
      '0' => 'Système',
      '1' => 'Manuel',
      '2' => 'Boutton',
      '3' => 'Automatique',
      '4' => 'Web',
      '5' => 'Application',
      '6' => 'Vérouillage automatique',
      '7' => 'Acessoire',
      '255' => 'Keypad',
    );
  }

  public static function getStatusLock(){
    return array(
      '0' => 'Non calibrée',
      '1' => 'Verrouillée',
      '2' => 'Déverrouillage',
      '3' => 'Déverrouillée',
      '4' => 'Verrouillage',
      '5' => 'Déverrouillée (loquet)',
      '6' => 'Déverrouillée (lock n go)',
      '7' => 'Déverrouillage (loquet)',
      '254' => 'Moteur Bloqué',
      '255' => 'Inconnu',
    );
  }

  public static function getStatusOpener(){
    return array(
      '0' => 'Non entrainé',
      '1' => 'En ligne',
      '3' => 'Appel pour ouverture actif',
      '5' => 'Ouvert',
      '7' => 'Ouverture',
      '253' => 'En démarrage',
      '255' => 'Inconnu',
    );
  }

  public static function getStatusDoor(){
    return array(
      '0' => 'Indisponible',
      '1' => 'Désactivé',
      '2' => 'Porte fermée',
      '3' => 'Porte ouverte',
      '4' => 'Porte état inconnu',
      '5' => 'En calibration',
      '16' => 'Non calibré',
      '240' => 'Retiré',
      '255' => 'Inconnu',
    );
  }

  public static function webApiIdToBridgeApiId($webId, $typeCode = 0){
    # Read https://developer.nuki.io/page/nuki-web-api-1-4/3/#heading--device-ids
    # or https://developer.nuki.io/t/nukiid-and-smartlockid/14509/4
    $hex = dechex($webId);
    if ($typeCode && str_starts_with($hex, $typeCode)) {
      $hex = substr($hex, 1);
    }
    return hexdec($hex);
  }

  public static function bridgeApiIdToWebApiId($bridgeId, $typeCode = 0){
    # Read https://developer.nuki.io/page/nuki-web-api-1-4/3/#heading--device-ids
    # or https://developer.nuki.io/t/nukiid-and-smartlockid/14509/4
    return hexdec($typeCode . dechex($bridgeId));
  }

  public static function cron($_eqLogic_id = null){
    self::updateValues();
  }

  public static function callBridge($_uri) {

    $ip    = config::byKey('bridge_ip', 'nukiSmartLock', '');
    $port  = config::byKey('bridge_port', 'nukiSmartLock', '');
    $token = config::byKey('api_token', 'nukiSmartLock', '');

    // Vérification configuration
    if ($ip == '' || $port == '' || $token == '') {
        self::add_log('error', 'Bridge non configuré');
        return null;
    }

    // Nettoyage de l’URI (trim + suppression espaces)
    $_uri = trim($_uri);
    $_uri = str_replace(' ', '', $_uri);

    // Normalisation du séparateur
    if (!str_contains($_uri, '?') && !str_contains($_uri, '&')) {
        $_uri .= '?';
    } elseif (!str_ends_with($_uri, '?') && !str_ends_with($_uri, '&')) {
        $_uri .= '&';
    }

    // Construction de l’URL
    $url = 'http://' . $ip . ':' . $port . '/' . $_uri . 'token=' . urlencode($token);

    // Vérification de l’URL avant com_http
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        self::add_log('error', 'URL invalide : ' . $url);
        return null;
    }

    // Requête HTTP
    try {
        $request_http = new com_http($url);
        $request_http->setNoReportError(true);
        $return = $request_http->exec(15, 2);
    } catch (Exception $e) {
        self::add_log('error', 'Exception HTTP : ' . $e->getMessage());
        return null;
    }

    self::add_log("debug", 'Bridge ' . $url . ' : ' . $return);

    if ($return === false || trim($return) === '') {
        self::add_log("error", "Réponse vide du Bridge");
        return null;
    }

    // Décodage JSON sécurisé
    $json = json_decode(trim($return), true);
    if (!is_array($json)) {
        self::add_log("error", "JSON invalide : " . $return);
        return null;
    }

    return $json;
  }
  public static function getBridgeStatus(){
    return self::callBridge('list?');
  }

  public static function getBridgeLogs(){
    return self::callBridge('log?');
  }

  public static function updateTrigger($_id) {

      // Liste des triggers connus
      $triggerList = self::getTriggers();

      // Récupération de l'équipement Jeedom
      $eqLogic = self::byNukiWebId($_id);
      if (!is_object($eqLogic)) {
          self::add_log('error', 'updateTrigger : aucun eqLogic trouvé pour ID ' . $_id);
          return;
      }

      // Appel API Web pour récupérer le dernier log
      $uri = 'smartlock/' . $eqLogic->getNukiWebId() . '/log';
      $array = self::callWeb($uri);

      if (!is_array($array) || !isset($array[0])) {
          self::add_log('error', 'updateTrigger : réponse invalide de l’API Web');
          return;
      }

      $entry = $array[0];

      // Détermination du nom du trigger
      if (!empty($entry['name'])) {
          $triggerName = $entry['name'];
      } elseif (isset($entry['trigger']) && isset($triggerList[$entry['trigger']])) {
          $triggerName = $triggerList[$entry['trigger']];
      } else {
          $triggerName = 'Inconnu';
      }

      self::add_log('debug', 'Trigger Webhook : ' . $triggerName);

      // Mise à jour de la commande Jeedom
      $eqLogic->checkAndUpdateCmd('trigger', $triggerName);
  }
  public static function getLogs($_id) {

      // Cas : récupérer tous les logs
      if ($_id == 'all') {
          $uri = 'smartlock/log';
          return self::callWeb($uri);
      }

      // Cas : récupérer les logs d’un équipement spécifique
      $eqLogic = self::byNukiWebId($_id);
      if (!is_object($eqLogic)) {
          self::add_log('error', 'getLogs : aucun eqLogic trouvé pour ID ' . $_id);
          return null;
      }

      $uri = 'smartlock/' . $eqLogic->getNukiWebId() . '/log';

      $result = self::callWeb($uri);

      if ($result === null) {
          self::add_log('error', 'getLogs : réponse vide ou invalide pour ' . $uri);
      }

      return $result;
  }
  public static function getAuths($_id){
    if ($_id == 'all') {
      $uri = 'smartlock/auth';
    } else {
      $eqLogic = nukiSmartLock::byNukiWebId($_id);
      $uri = 'smartlock/' . $eqLogic->getNukiWebId() . '/auth';
    }
    return nukiSmartLock::callWeb($uri);
  }
  public static function callWeb($_uri, $_type = 'get', $_post = ''){
    $token = config::byKey('api_web', 'nukiSmartLock', '');

    if ($token == '') {
        self::add_log('error', 'API Web non configurée');
        return null;
    }

    $url = 'https://api.nuki.io/' . ltrim($_uri, '/');

    $request_http = new com_http($url);

    $headers = array(
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    );

    switch (strtolower($_type)) {

        case 'post':

            $payload = json_encode($_post, JSON_UNESCAPED_UNICODE);

            $headers[] = 'Content-Type: application/json';
            $request_http->setPost($payload);

            self::add_log(
                'debug',
                'Web POST Payload : ' . $payload
            );

            break;

        case 'put':

            $payload = json_encode($_post, JSON_UNESCAPED_UNICODE);

            $headers[] = 'Content-Type: application/json';
            $request_http->setPut($payload);

            self::add_log(
                'debug',
                'Web PUT Payload : ' . $payload
            );

            break;

        case 'delete':

            $request_http->setDelete();

            break;

        case 'get':
        default:
            break;
    }

    $request_http->setHeader($headers);
    $request_http->setNoSslCheck(true);
    $request_http->setNoReportError(true);

    try {

        $return = $request_http->exec(15, 2);

        self::add_log(
            'debug',
            strtoupper($_type) . ' ' . $url . ' : ' . $return
        );

        // Erreur HTTP
        if ($return === false) {

            self::add_log(
                'error',
                'Erreur API Nuki : ' . $url
            );

            return null;
        }

        /*
         * Nuki retourne parfois une réponse vide
         * lors d'un PUT ou DELETE réussi.
         */
        if (trim($return) === '') {

            if (in_array(strtolower($_type), array('put', 'delete'))) {

                self::add_log(
                    'debug',
                    strtoupper($_type) . ' réussi : ' . $url
                );

                return array(
                    'success' => true
                );
            }

            self::add_log(
                'error',
                'Réponse vide de l\'API Nuki : ' . $url
            );

            return null;
        }

        $json = json_decode($return, true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            self::add_log(
                'error',
                'JSON invalide : '
                . json_last_error_msg()
                . ' | Réponse : '
                . $return
            );

            return null;
        }

        // Gestion des erreurs retournées par l'API
        if (
            isset($json['code']) &&
            is_numeric($json['code']) &&
            $json['code'] >= 400
        ) {

            self::add_log(
                'error',
                'Erreur API Nuki : '
                . json_encode($json)
            );

            return null;
        }

        return $json;

    } catch (Exception $e) {

        self::add_log(
            'error',
            'Exception API Nuki : ' . $e->getMessage()
        );

        return null;
    }
  }  

  public static function getConfig($_id) {

      // Cas : récupérer la configuration de tous les Smart Locks
      if ($_id == 'all') {
          $uri = 'smartlock';
          return self::callWeb($uri);
      }

      // Cas : récupérer la configuration d’un Smart Lock spécifique
      $eqLogic = self::byNukiWebId($_id);
      if (!is_object($eqLogic)) {
          self::add_log('error', 'getConfig : aucun eqLogic trouvé pour ID ' . $_id);
          return null;
      }

      $uri = 'smartlock/' . $eqLogic->getNukiWebId();

      $result = self::callWeb($uri);

      if ($result === null) {
          self::add_log('error', 'getConfig : réponse vide ou invalide pour ' . $uri);
      }

      return $result;
  }

  public static function authDelete($_id, $_auth){
    $eqLogic = self::byNukiWebId($_id);

    if (!is_object($eqLogic)) {
        self::add_log(
            'error',
            'authDelete : aucun eqLogic trouvé pour ID ' . $_id
        );
        return null;
    }

    $uri = 'smartlock/' . $eqLogic->getNukiWebId()
         . '/auth/' . urlencode($_auth);

    $result = self::callWeb($uri, 'delete');

    if ($result === null) {
        self::add_log(
            'error',
            'authDelete : échec de suppression pour ' . $uri
        );
    } else {
        self::add_log(
            'debug',
            'authDelete : suppression OK pour ' . $uri
        );
    }

    return $result;
  }
  public static function authPin($_id, $_name, $_pin){
    // Vérification du PIN Nuki (6 chiffres)
    if (!preg_match('/^[0-9]{6}$/', $_pin)) {
        self::add_log(
            'error',
            'authPin : PIN invalide (6 chiffres requis)'
        );
        return null;
    }

    $_name = trim($_name);

    if ($_name == '') {
        self::add_log(
            'error',
            'authPin : nom utilisateur vide'
        );
        return null;
    }

    // Récupération de l'équipement
    $eqLogic = self::byNukiWebId($_id);

    if (!is_object($eqLogic)) {
        self::add_log(
            'error',
            'authPin : aucun eqLogic trouvé pour ID ' . $_id
        );
        return null;
    }

    // Endpoint Nuki
    $uri = 'smartlock/' . $eqLogic->getNukiWebId() . '/auth';

    // Données à envoyer
    $post = array(
        'name' => $_name,
        'type' => 13,
        'code' => (int)$_pin
    );

    self::add_log(
        'debug',
        'authPin : création du PIN "' . $_name . '"'
    );

    // PUT et non POST
    $result = self::callWeb($uri, 'put', $post);

    // Gestion des erreurs API
    if (
        $result === null ||
        (is_array($result) &&
         isset($result['code']) &&
         $result['code'] >= 400)
    ) {
        self::add_log(
            'error',
            'authPin : échec création PIN : ' . json_encode($result)
        );
        return null;
    }

    self::add_log(
        'debug',
        'authPin : PIN créé pour ' . $_name
    );

    return $result;
  }

  
  public static function updateValues() {

    // Récupération du statut du Bridge
    $nukis = self::getBridgeStatus();

    if (!is_array($nukis)) {
        self::add_log('error', 'updateValues : réponse invalide du Bridge');
        return;
    }

    self::add_log('debug', 'BridgeStatus : ' . print_r($nukis, true));

    foreach ($nukis as $nuki) {

        if (!isset($nuki['lastKnownState']) || !is_array($nuki['lastKnownState'])) {
            self::add_log('error', 'updateValues : lastKnownState manquant pour nukiId ' . ($nuki['nukiId'] ?? 'inconnu'));
            continue;
        }

        // Ajout des infos nécessaires
        $state = $nuki['lastKnownState'];
        $state['nukiId'] = $nuki['nukiId'] ?? null;
        $state['firmwareVersion'] = $nuki['firmwareVersion'] ?? null;

        // Mise à jour de l’équipement
        self::updateDevice($state);
    }
  }

  
  public static function updateDevice($_array){
      self::add_log("debug", 'Update ' . print_r($_array, true));
      if (!isset($_array['nukiId'])) {
          self::add_log('error', 'updateDevice : nukiId manquant dans les données');
          return;
      }

      $eqLogic = eqLogic::byLogicalId($_array['nukiId'], 'nukiSmartLock');
      if (!is_object($eqLogic)) {
          self::add_log('error', 'updateDevice : aucun eqLogic pour nukiId ' . $_array['nukiId']);
          return;
      }

      if (!isset($_array['state'])) {
          self::add_log('error', 'updateDevice : state manquant pour nukiId ' . $_array['nukiId']);
          return;
      }

      // SMARTLOCK
      if ($eqLogic->getConfiguration('type') == 'smartlock' || $eqLogic->getConfiguration('type') == 'smartlock3') {

          $status = self::getStatusLock();
          $statebinary = in_array($_array['state'], array(1, 4)) ? 1 : 0;

      // OPENER
      } else {

          $status = self::getStatusOpener();
          $statebinary = in_array($_array['state'], array(1)) ? 1 : 0;

          if (isset($_array['mode'])) {
              $eqLogic->checkAndUpdateCmd('rto', $_array['mode'] == 3 ? 1 : 0);
          }
          if (isset($_array['ringactionState'])) {
              $eqLogic->checkAndUpdateCmd('doorbell', $_array['ringactionState'] ? 1 : 0);
          }
          if (!empty($_array['ringactionTimestamp'])) {
              $eqLogic->checkAndUpdateCmd(
                  'doorbell_last_time',
                  date('Y-m-d H:i:s', strtotime($_array['ringactionTimestamp']))
              );
          }
      }

      // STATEKIT
      $statekit = $statebinary;
      if ($_array['state'] == '254') $statekit = 2;
      if ($_array['state'] == '255') $statekit = 3;

      $eqLogic->checkAndUpdateCmd('statekit', $statekit);
      $eqLogic->checkAndUpdateCmd('statebinary', $statebinary);
      $eqLogic->checkAndUpdateCmd('state', $_array['state']);

      if (isset($status[$_array['state']])) {
          $eqLogic->checkAndUpdateCmd('state_name', $status[$_array['state']]);
      }

      // BATTERIE
      if (isset($_array['batteryCritical'])) {
          $eqLogic->checkAndUpdateCmd('battery_critical', $_array['batteryCritical']);
      }
      if (isset($_array['batteryCharging'])) {
          $eqLogic->checkAndUpdateCmd('battery_charging', $_array['batteryCharging'] ? 1 : 0);
      }
      if (isset($_array['keypadBatteryCritical'])) {
          $eqLogic->checkAndUpdateCmd('keypad_battery_critical', $_array['keypadBatteryCritical']);
      }

      $battery = isset($_array['batteryChargeState']) && $_array['batteryChargeState'] !== null
          ? $_array['batteryChargeState']
          : 100;

      if (!empty($_array['batteryCritical'])) {
          $battery = 0;
      }

      $eqLogic->checkAndUpdateCmd('battery', $battery);
      $eqLogic->batteryStatus($battery);
      $eqLogic->save();

      // DOOR SENSOR
      if (isset($_array['doorsensorState'])) {

          $statusDoor = self::getStatusDoor();   // <-- FIX ICI

          $statebinaryDoor = in_array($_array['doorsensorState'], array(2)) ? 1 : 0;

          $eqDoor = self::byLogicalId('door-' . $_array['nukiId'], 'nukiSmartLock');
          if (!is_object($eqDoor)) {
              self::add_log('debug', 'updateDevice : aucun eqLogic door- pour nukiId ' . $_array['nukiId']);
              //return;
          }else{ 
            $eqDoor->checkAndUpdateCmd('statebinary', $statebinaryDoor);
            $eqDoor->checkAndUpdateCmd('state', $_array['doorsensorState']);

            if (isset($statusDoor[$_array['doorsensorState']])) {
                $eqDoor->checkAndUpdateCmd('state_name', $statusDoor[$_array['doorsensorState']]);
            }

            $eqDoor->save();
          }

        
      }
  }

  public static function searchnukiDevices(){

    // Récupération du statut du Bridge
    $nukis = self::getBridgeStatus();

    if (!is_array($nukis)) {
      self::add_log('error', 'searchnukiDevices : réponse invalide du Bridge');
      return;
    }

    self::add_log("debug",'fichier : ' . json_encode($nukis));

    // --- Création / Mise à jour des équipements ---
    foreach ($nukis as $nuki) {

      if (!isset($nuki['nukiId'])) {
          self::add_log('error', 'searchnukiDevices : nukiId manquant dans un device');
          continue;
      }

      $eqLogic = self::byLogicalId($nuki['nukiId'], 'nukiSmartLock');

      // Création si inexistant
      if (!is_object($eqLogic)) {
        $eqLogic = new eqLogic();
        $eqLogic->setEqType_name('nukiSmartLock');
        $eqLogic->setIsEnable(1);
        $eqLogic->setName($nuki['name']);
        $eqLogic->setLogicalId($nuki['nukiId']);
        $eqLogic->setIsVisible(1);
        $eqLogic->save();
      }

      // Mise à jour des infos
      $eqLogic->setConfiguration('nukiId', $nuki['nukiId']);

      if ($nuki['deviceType'] == 0) $eqLogic->setConfiguration('type', 'smartlock');
      if ($nuki['deviceType'] == 2) $eqLogic->setConfiguration('type', 'opener');
      if ($nuki['deviceType'] == 4) $eqLogic->setConfiguration('type', 'smartlock3');

      $eqLogic->setConfiguration('nukiType', $nuki['deviceType']);
      $eqLogic->setConfiguration('nukiWebId', self::bridgeApiIdToWebApiId($nuki['nukiId'], $nuki['deviceType']));
      $eqLogic->setConfiguration('name', $nuki['name']);
      $eqLogic->setConfiguration('firmware', $nuki['firmwareVersion']);
      $eqLogic->save();

      // --- Capteur de porte ---
      if (isset($nuki['lastKnownState']['doorsensorState'])) {
        $eqDoor = self::byLogicalId('door-' . $nuki['nukiId'], 'nukiSmartLock');
        if (!is_object($eqDoor)) {
          $eqDoor = new eqLogic();
          $eqDoor->setEqType_name('nukiSmartLock');
          $eqDoor->setIsEnable(1);
          $eqDoor->setName('Capteur Porte - ' . $nuki['name']);
          $eqDoor->setLogicalId('door-' . $nuki['nukiId']);
          $eqDoor->setIsVisible(1);
          $eqDoor->save();
        }
        $eqDoor->setConfiguration('nukiId', $nuki['nukiId']);
        $eqDoor->setConfiguration('type', 'doorsensor');
        $eqDoor->setConfiguration('nukiType', $nuki['deviceType']);
        $eqDoor->setConfiguration('nukiWebId', self::bridgeApiIdToWebApiId($nuki['nukiId'], $nuki['deviceType']));
        $eqDoor->setConfiguration('name', 'door-' . $nuki['name']);
        $eqDoor->setConfiguration('firmware', $nuki['firmwareVersion']);
        $eqDoor->save();
      }
    }

    // --- Suppression des équipements absents du Bridge ---
    $eqLogics = self::byType('nukiSmartLock');
    if (is_array($eqLogics) && count($eqLogics) > 0) {
      $idsBridge = array_column($nukis, 'nukiId');
      foreach ($eqLogics as $eqLogic) {
        $id = $eqLogic->getConfiguration('nukiId');
        // Ne supprime pas les capteurs de porte si la serrure existe encore
        if (str_starts_with($eqLogic->getLogicalId(), 'door-')) {
          $baseId = substr($eqLogic->getLogicalId(), 5);
          if (in_array($baseId, $idsBridge)) continue;
        }
        if (!in_array($id, $idsBridge)) {
          self::add_log("debug",'Suppression de l\'équipement ayant l\'id : ' . $eqLogic->getLogicalId());
          $eqLogic->remove();
        }
      }
    }
  }
  
  public function fillAuthCmds(){
    // Pas d'autorisations pour un capteur de porte
    if ($this->getConfiguration('type') == 'doorsensor') {
      return;
    }
    // Récupération des authorisations
    $auths = self::getAuths($this->getLogicalId());
    if (!is_array($auths)) {
      self::add_log('error', 'fillAuthCmds : réponse invalide de getAuths()');
      return;
    }
    $values = array();
    foreach ($auths as $auth) {
      if (isset($auth['id']) && isset($auth['name'])) {
        $values[] = $auth['id'] . '|' . $auth['name'];
      }
    }
    $list = implode(';', $values);
    // Commande authEnable
    $cmd = cmd::byEqLogicIdAndLogicalId($this->getId(), 'authEnable');
    if (is_object($cmd)) {
      $cmd->setConfiguration('listValue', $list);
      $cmd->save();
    }
    // Commande authDisable
    $cmd = cmd::byEqLogicIdAndLogicalId($this->getId(), 'authDisable');
    if (is_object($cmd)) {
      $cmd->setConfiguration('listValue', $list);
      $cmd->save();
    }
  }

  public function preSave(){
    if ($this->getConfiguration('battery_type') != '4x1.5V AA') {
      $this->setConfiguration('battery_type', '4x1.5V AA');
    }
  }

  public function postSave(){
    $this->loadCmdFromConf($this->getConfiguration('type'));
    $this->fillAuthCmds();
  }

  public function loadCmdFromConf($type){
    /*create commands based on template*/
    if (!is_file(dirname(__FILE__) . '/../config/devices/' . $type . '.json')) {
      return;
    }
    $content = file_get_contents(dirname(__FILE__) . '/../config/devices/' . $type . '.json');
    if (!is_json($content)) {
      return;
    }
    $device = json_decode($content, true);
    if (!is_array($device) || !isset($device['commands'])) {
      return true;
    }
    foreach ($device['commands'] as $command) {
      $cmd = null;
      foreach ($this->getCmd() as $liste_cmd) {
        if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId'])
          || (isset($command['name']) && $liste_cmd->getName() == $command['name'])
        ) {
          $cmd = $liste_cmd;
          break;
        }
      }
      if ($cmd == null || !is_object($cmd)) {
        $cmd = new nukiSmartLockCmd();
        $cmd->setEqLogic_id($this->getId());
        utils::a2o($cmd, $command);
        $cmd->save();
      }
    }
  }
}

class nukiSmartLockCmd extends cmd{
  public function execute($_options = null){
    $eqLogic = $this->getEqLogic();
   
    if (strpos($this->getLogicalId(), 'auth') !== false) {
       
      if (($this->getLogicalId() == 'authDisable') || ($this->getLogicalId() == 'authEnable')) {
        nukiSmartLock::callWeb('smartlock/' . $eqLogic->getNukiWebId() . '/auth/' . $_options['select'], 'post', array($this->getConfiguration('request') => $this->getConfiguration('requestValue')));
      }
      if ($this->getLogicalId() == 'authMessage') {
        $conf = explode(',', $_options['title']);
        $data = array();
        $id = '';
        $mode = "post";
        foreach ($conf as $elt) {
          $conf2 = explode('=', $elt);
          if ($conf2[0] == 'auth') {
            $cmd = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), 'authEnable');
            if (!is_object($cmd)) {
              return;
            }
            $values = explode(';', $cmd->getConfiguration('listValue'));
            foreach ($values as $value) {
              $list = explode('|', $value);
              if ($list[1] == $conf2[1]) {
                $id = $list[0];
                break;
              }
            }
          } else if ($conf2[0] == 'mode') {
            $mode = $conf2[1];
          } else {
            $data[$conf2[0]] = $conf2[1];
          }
        }
        if ($id == '') {
          return;
        }
        nukiSmartLock::callWeb('smartlock/' . $eqLogic->getNukiWebId() . '/auth/' . $id, $mode, $data);
        $eqLogic->fillAuthCmds();
      }
    } elseif ($this->getLogicalId() == 'unlatch_duree') {
      // Objet de la modif : permettre de modifier le temps d'ouverture de la gâche
      //récupère la configuration depuis l'API Web Nuki
      $conf = nukiSmartLock->getConfig($eqLogic->getNukiWebId());
      self::add_log("debug",'Config NUKI originale :' . print_r($conf, true));
      //update de la valeur pour l'unlatchDuration selon l'option sélectionnée (sur le widget ou via scénario)
      $conf['advancedConfig']['unlatchDuration'] = $_options['select'];
      self::add_log("debug", 'Config MODIFIEE (unlatch duration) :' . print_r($conf['advancedConfig'], true));
      //$arr = array($this->getConfiguration('request0') => $this->getConfiguration('requestValue0'),$this->getConfiguration('request1') => $this->getConfiguration('requestValue1'));
      nukiSmartLock::callWeb('smartlock/' . $eqLogic->getNukiWebId() . '/advanced/config', 'post', $conf['advancedConfig']);
    } else {
      if ($this->getLogicalId() != 'refresh') {
        
        if ($this->type == 'action') {
        
          $result = nukiSmartLock::callBridge('lockAction?nukiId=' . $eqLogic->getLogicalId() . '&action=' . $this->getConfiguration('request') . '&noWait=1&');
        }
      }
      nukiSmartLock::updateValues();
    }
  }
}