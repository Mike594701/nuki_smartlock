<?php


if (!isConnect('admin')) {
  throw new Exception('401 - Accès non autorisé');
}
?>

<table class="table table-condensed tablesorter" align="center">
  <thead>
    <tr>
      <th>{{Heure}}</th>
      <?php if (init('id') == 'all') { ?>
        <th>{{Serrure}}</th>
      <?php } ?>
      <th>{{Action}}</th>
      <th>{{Etat}}</th>
      <th>{{Déclencheur}}</th>
      <th>{{Type de déclencheur}}</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $logs = nukiSmartLock::getLogs(init('id'));
    $trigger =  array(
      '0' => 'Application nuki',
      '1' => 'Manuel',
      '2' => 'Bouton',
      '3' => 'Automatique',
      '4' => 'Web',
      '5' => 'Application',
      '6' => 'Vérouillage automatique',
      '7' => 'Acessoire',
      '255' => 'Keypad',
    );

    $action = array(
      '1' => 'Déverrouillage',
      '2' => 'Verrouillage',
      '3' => 'Déverrouillage (loquet)',
      '4' => 'Déverrouillage (lock n go)',
      '5' => 'Déverrouillage (lock n go avec loquet)',
      '208' => 'Avertissement de porte entrouverte',
      '209' => 'Avertissement de porte dans un état incohérent',
      '224' => 'Sonnette déclenchée',
      '240' => 'Porte ouverte',
      '241' => 'Porte fermée',
      '242' => 'Capteur de porte bloqué',
      '243' => 'Mise à jour',
      '250' => 'Journal de porte activé',
      '251' => 'Journal de porte désactivé',
      '252' => 'Initialisation',
      '253' => 'Calibration',
      '254' => 'Journal activé',
      '255' => 'Journal désactivé',
    );

    $states = array(
      '0' => 'Succès',
      '1' => 'Moteur bloqué',
      '2' => 'Annulé',
      '3' => 'Trop récent',
      '4' => 'Occupé',
      '5' => 'Voltage du moteur trop faible',
      '6' => 'Défaillance de l\'embrayage',
      '7' => 'Panne d\'alimentation du moteur',
      '8' => 'Incomplet',
      '9' => 'Rejeté',
      '10' => 'Rejeté mode nuit',
      '225'=> 'Empreinte inconnue',
      '254' => 'Autre erreur',
      '255' => 'Erreur inconnue',
    );
    
    foreach ($logs as $log) {
      $log['date']=strtotime($log['date']);
      $eqLogic = nukiSmartLock::byNukiWebId($log['smartlockId']);
      if (is_object($eqLogic)) {
        $link = $eqLogic->getLinkToConfiguration();
        $name = $eqLogic->getHumanName(true);
      } else {
        $link = '';
        $name = 'inconnu';
      }
    
        if ($log['name'] =="") {
          $source = 'Manuel';
        }else{
          $source = str_replace(' (Keypad)','',$log['name']);
        }
        
      
      if($log['autoUnlock']){
        $source .= '(auto unlock)';
      } 
      $jour = array("Dimanche ","Lundi ","Mardi ","Mercredi ","Jeudi ","Vendredi ","Samedi ");
      $mois = array(" janvier "," février "," mars "," avril "," mai "," juin "," juillet "," août "," septembre "," octobre "," novembre "," décembre ");
      $date_fr =  $jour[date('w', $log['date'])] .date('d',$log['date']) . " " . $mois[date('n', $log['date'])] . date('H',$log['date']) . ":" . date('i',$log['date']) . ":" . date('s',$log['date']);
      echo '<tr>';
      echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $date_fr. '</span></td>';
      if (init('id') == 'all') {
        echo '<td><a href="' . $link . '" style="text-decoration: none;">' . $name . '</a></td>';
      }
      echo '<td>' . $action[$log['action']] . '</td>';
      if ($states[$log['state']] == "Succès"){
        echo '<td>' . $states[$log['state']] . '</td>';
      }else{
        echo '<td style="color: red !important;">' . $states[$log['state']] . '</td>';
      }
      
      echo '<td>' . $source . '</td>';
      echo '<td>' . $trigger[$log['trigger']] . '</td>';
      echo '</tr>';
    }
    ?>
  </tbody>
</table>