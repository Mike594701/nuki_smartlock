<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

try {
  require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
  include_file('core', 'authentification', 'php');

  if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
  }

  if (init('action') == 'searchnukiDevices') {
    nuki_smartlock::searchnukiDevices();
    ajax::success();
  }
 

  if (init('action') == 'authDelete') {
    nuki_smartlock::authDelete(init('id'), init('auth'));
    ajax::success();
  }

  
if (init('action') == 'authPin') {

    $avant = nuki_smartlock::getAuths(init('id'));

    $idsAvant = array();
    foreach ($avant as $auth) {
        $idsAvant[] = $auth['id'];
        nuki_smartlock::add_log('info', $auth);
    }

    nuki_smartlock::authPin(
        init('id'),
        init('name'),
        init('pin')
    );
    for ($i = 0; $i < 5; $i++) {

      sleep(1);

      $apres = nuki_smartlock::getAuths(init('id'));

      foreach ($apres as $auth) {
        if (!in_array($auth['id'], $idsAvant)) {
          ajax::success($auth);
        }
      }
    }
    ajax::error('Nouvelle autorisation non trouvée après attente');
}
  throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
  /*     * *********Catch exeption*************** */
} catch (Exception $e) {
  ajax::error(displayExeption($e), $e->getCode());
}