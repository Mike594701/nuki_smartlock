<?php


if (!isConnect('admin')) {
  throw new Exception('401 - Accès non autorisé');
}
?>

<table class="table_auth table table-condensed tablesorter" align="center">
  <thead>
    <tr><?php
      $displaySerrure = (init('id') != 'all') ? 'display:none;' : '';

      echo '<th style="' . $displaySerrure . '">{{Serrure}}</th>';
      ?>
      <th>{{Authentification}}</th>
      <th>{{Activée}}</th>
      <th>{{Accès Distant}}</th>
      <th>{{Utilisations}}</th>
      <th>{{Dernière Activité}}</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $auths = nukiSmartLock::getAuths(init('id'));
    $authTypes = array(
      '0' => 'Application',
      '1' => 'Bridge',
      '2' => 'FOB',
      '3' => 'Keypad',
      '5' => 'Contrôle d\'accès',
      '13' => 'Code Keypad',
      '14' => 'Z-KEY',
      '15' => 'Virtuel',
    );
    $jour = array("Dimanche ","Lundi ","Mardi ","Mercredi ","Jeudi ","Vendredi ","Samedi ");
    $mois = array(
      1 => " janvier ",
      2 => " février ",
      3 => " mars ",
      4 => " avril ",
      5 => " mai ",
      6 => " juin ",
      7 => " juillet ",
      8 => " août ",
      9 => " septembre ",
      10 => " octobre ",
      11 => " novembre ",
      12 => " décembre "
    );
    $keypads = [];

    foreach ($auths as $auth) {
      $eqLogic = nukiSmartLock::byNukiWebId($auth['smartlockId']);

      if (is_object($eqLogic)) {
        $link = $eqLogic->getLinkToConfiguration();
        $name = $eqLogic->getHumanName(true);
        $eqLogicId = $eqLogic->getId();
        if (!isset($keypads[$eqLogicId])) {
          $keypads[$eqLogicId] = [
            'smartlockId' => $auth['smartlockId'],
            'eqLogicId' => $eqLogicId,
            'name' => $name,
          ];
        }
      } else {
        $link = '';
        $name = 'inconnu';
      }
      $auth['lastActiveDate']=strtotime($auth['lastActiveDate']);
      if ($auth['lastActiveDate'] === false) {
        $date_fr = 'Jamais';
      } else {
        $date_fr = $jour[date('w', $auth['lastActiveDate'])]    . date('d', $auth['lastActiveDate']) . $mois[date('n', $auth['lastActiveDate'])] . date('Y', $auth['lastActiveDate']) . ' ' . date('H', $auth['lastActiveDate']) . ':' . date('i', $auth['lastActiveDate']) . ': '. date('s', $auth['lastActiveDate']); 
            
      }
      echo '<tr class="auth" data-eqid="' . $auth['smartlockId'] . '" data-authid="' . $auth['id'] . '">';
      echo '<td style="' . $displaySerrure . '"><a href="' . $link . '">' . $name . '</a></td>';
      echo '<td>' . $auth['name'] . ' <i>(' . $authTypes[$auth['type']] . ')</i></td>';
      echo '<td><span class="label label-' . ($auth['enabled'] ? 'success' : 'danger') . '" style="font-size : 1em; cursor : default;">' . ($auth['enabled'] ? 'Oui' : 'Non') . '</span></td>';
      echo '<td><span class="label label-' . ($auth['remoteAllowed'] ? 'success' : 'danger') . '" style="font-size : 1em; cursor : default;">' . ($auth['remoteAllowed'] ? 'Oui' : 'Non') . '</span></td>';
      echo '<td>' . $auth['lockCount'] . '</td>';
      if ($date_fr == 'Jamais') {
        $labelClass = 'danger';
      } else {
        $labelClass = ($auth['lastActiveDate'] < strtotime('-1 month'))? 'warning': 'info';
      }
      echo '<td><span class="label label-' . $labelClass . '" style="font-size : 1em; cursor : default;">' . $date_fr . '</span></td>';
      echo '<td>';
      if (($auth['name'] != 'Nuki Web') && ($auth['name'] != 'Nuki Keypad')) {
        echo '<a class="btn btn-danger bt_delete" data-action="remove"><i class="fa fa-minus-circle"></i> Supprimer</a>';
      }
      echo '</td>';
      echo '</tr>';
    }
    ?>
  </tbody>
</table>

<br>
<?php

  if (count($keypads) > 0) {
    echo "Pour créer un accès par code PIN sur keypad";
    echo "<br>";
    echo "Le code PIN doit faire 6 chiffres de long, pas de 0 et ne pas commencer par 12";

    echo '<table class="table table-condensed tablesorter" align="center">';
    echo '<thead>';
    echo '<tr>';
    if (init('id') == 'all') {
      echo '<th>Serrure</th>';
    }else{
      echo '<th style="display:none;">Serrure</th>';
    }
    echo '<th>Nom</th>';
    echo '<th>Code PIN</th>';
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    echo '<tr>';
    if (init('id') == 'all') {
      echo '<td>';
    
      echo '<select id="smartlockId" class="form-control">';
      
      foreach ($keypads as $keypad) {
        echo '<option value="' . $keypad['smartlockId'] . '" data-eqlogic-id="' . $keypad['eqLogicId'] . '">' . $keypad['name'] . '</option>';
      }    
      echo '</select>';
   
      echo '</td>';
    }
    echo '<td><input type="text" id="name"/></td>';
    echo '<td><input type="text" id="pin" maxlength="6"/></td>';
    echo '<td><a class="btn btn-success bt_add" data-action="add" style="visibility:hidden;"><i class="fa fa-plus-circle"></i> Ajouter</a></td>';

    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
  } else {
      echo "Aucune serrure avec keypad associé";
  }
?>

<script>
  

 eqid = '<?php echo init('id'); ?>';
 pageall = '<?php echo init('id') == 'all' ? 'true' : 'false'; ?>';
  
  function addAuthLine(auth,pageall, url) {

    const displaySerrure = (pageall=='false') ? 'display:none;' : '';

    let serrureCell = '';
    if (pageall=='true') {
      const humanName = document.getElementById('smartlockId').selectedOptions[0].innerHTML ;
      serrureCell = `<td><a href="${url}">${humanName}</a></td>`;
      }   
    const ligne = `
      <tr class="auth" data-eqid="${auth.smartlockId}" data-authid="${auth.id}">
        ${serrureCell}
        <td>${auth.name}<i>(Code Keypad)</i></td>
        <td><span class="label label-success" style="font-size:1em;cursor:default;">Oui </span></td>
        <td><span class="label label-danger" style="font-size:1em;cursor:default;">Non</span></td>
        <td>${auth.lockCount || 0}</td>
        <td><span class="label label-danger" style="font-size:1em;cursor:default;">Jamais</span></td>
        <td><a class="btn btn-danger bt_delete" data-action="remove"><i class="fa fa-minus-circle"></i>Supprimer</a></td>
      </tr> `;
    document.querySelector('.table_auth tbody').insertAdjacentHTML('beforeend', ligne);
  }
  
  function handleModalInput(event) {

    if (!event.target.matches('#name, #pin')) {
      return;
    }

    const nom = document.getElementById('name');
    const pin = document.getElementById('pin');
    const bouton = document.querySelector('.bt_add');

    pin.value = pin.value.replace(/[^1-9]/g, '').slice(0, 6);

    bouton.style.visibility =
      (nom.value.trim() &&
      pin.value.length === 6 &&
      !pin.value.startsWith('12'))
        ? 'visible'
        : 'hidden';
  }
 
  function handleModalClick(event) {
    const target = event.target;
    
    if (target.closest('.bt_delete')) {
      const ligne = target.closest('.auth');
      const authid = ligne.dataset.authid;
      eqid = ligne.dataset.eqid;
      domUtils.ajax({
        type: 'POST',
        url: 'plugins/nukiSmartLock/core/ajax/nukiSmartLock.ajax.php',
        data: {
          action: 'authDelete',
          auth: authid,
          id: eqid
        },
        error: (request, status, error) => {
          handleAjaxError(request, status, error);
        },
        success: () => {
          jeedomUtils.showAlert({
            message: '{{Autorisation révoquée}}',
            level: 'success'
          });
          ligne.remove();
        }
      });
    }

    if (target.closest('.bt_add')) {
    
      let url = null;
      if (pageall == 'true') {

        const option = document.getElementById('smartlockId').selectedOptions[0];
        eqid = option.value;

        const eqlogicId = option.dataset.eqlogicId;

        url = `index.php?v=d&p=nukiSmartLock&m=nukiSmartLock&id=${eqlogicId}`;
      }

      const nom = document.getElementById('name').value.trim();
      const pin = document.getElementById('pin').value.trim();

      if (!nom) {
        alert('{{Le nom est obligatoire}}');
        return;
      }

      if (!/^[1-9]{6}$/.test(pin)) {
        alert('{{Le code PIN doit contenir exactement 6 chiffres de 1 à 9}}');
        return;
      }

      if (pin.startsWith('12')) {
        alert('{{Le code PIN ne peut pas commencer par 12}}');
        return;
      }

      document.getElementById('name').value = '';
      document.getElementById('pin').value = '';

      target.style.visibility = 'hidden';

      domUtils.ajax({
        type: 'POST',
        url: 'plugins/nukiSmartLock/core/ajax/nukiSmartLock.ajax.php',
        data: {
          action: 'authPin',
          name: nom,
          pin: pin,
          id: eqid
        },
        global: false,
        error: (request, status, error) => {
          jeedomUtils.showAlert({
            message: error.message || error,
            level: 'danger'
          });
        },
        success: (data) => {

          if (data.result === 'Nouvelle autorisation non trouvée après attente') {
            jeedomUtils.showAlert({
              message: '{{Nouvelle autorisation non trouvée après attente}}',
              level: 'warning'
            });
            return;
          }

          jeedomUtils.showAlert({
            message: '{{Autorisation créée}}',
            level: 'success'
          });
        
            addAuthLine(data.result, pageall, url || '');
                    
        }
      });
    }
  }
  var modal = document.getElementById('md_modal');

modal.onclick = handleModalClick;
modal.oninput = handleModalInput;
</script>