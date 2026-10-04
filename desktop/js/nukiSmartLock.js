
function handleDialogClick(event, dialogs) {
    const target = event.target;
    for (const [selector, [title, url]] of Object.entries(dialogs)) {
        if (target.closest(selector)) {
            jeeDialog.dialog({
                id: 'md_modal',
                title,
                contentUrl: url
            });
            return;
        }
    }
}

document.getElementById('eqlogictab').addEventListener('click', function(event) {
    const nukiWebId = document.querySelector( '.eqLogicAttr[data-l1key="configuration"][data-l2key="nukiWebId"]')?.textContent.trim();
    handleDialogClick(event, {
        '#btLogsLock': [
            'Logs Serrures',
            `index.php?v=d&plugin=nukiSmartLock&modal=logs&id=${nukiWebId}`
        ],
        '#btAuthLock': [
            'Autorisations Serrure',
            `index.php?v=d&plugin=nukiSmartLock&modal=auth&id=${nukiWebId}`
        ]
    });
});

document.querySelector('.eqLogicThumbnailContainer').addEventListener('click', function(event) {
    const target = event.target;
    if (target.closest('#bt_syncEqLogic')) {
        return searchnukiDevices();
    }
    handleDialogClick(event, {
        '#btLogs': [
            'Logs Serrures',
            'index.php?v=d&plugin=nukiSmartLock&modal=logs&id=all'
        ],
        '#btAuth': [
            'Autorisations Serrures',
            'index.php?v=d&plugin=nukiSmartLock&modal=auth&id=all'
        ]
    });
});
document.getElementById('eqlogictab').addEventListener('change', function(event) {
    const target = event.target;

    if (target.matches('.eqLogicAttr[data-l1key="configuration"][data-l2key="type"]')) {

        // Image
        if (target.value) {
            document.getElementById('nukiTypeImg').src =
                `plugins/nukiSmartLock/core/config/devices/${target.value}.png`;
        }

        // Affichage boutons
        const hide = target.value === 'doorsensor';

        document.getElementById('btLogsLock').style.display = hide ? 'none' : '';
        document.getElementById('btAuthLock').style.display = hide ? 'none' : '';
    }
});


function searchnukiDevices() {
   domUtils.ajax({
	type: "POST",
	url: "plugins/nukiSmartLock/core/ajax/nukiSmartLock.ajax.php",
	data: {
		action: "searchnukiDevices"
	},
	error: (request, status, error) => {
		handleAjaxError(request, status, error);
	},
	success: (data) => {
		if (data.state !== 'ok') {
		jeedomUtils.showAlert({
			message: data.result,
			level: 'danger'
		});
		return;
		}

		jeedomUtils.showAlert({
		message: 'Recherche ok',
		level: 'success'
		});

		window.location.reload();
	}
	});
}



function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = { configuration: {} };
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="name">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    if (init(_cmd.type) == 'action') {
        tr += '<div class="row">';
        tr += '<div class="col-lg-6">';
        tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> Icone</a>';
        tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
        tr += '</div>';
        tr += '<div class="col-lg-6">';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
        tr += '</div>';
        tr += '</div>';
    } else {
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom de l\'info}}">';
    }
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="subType" value="' + init(_cmd.subType) + '" disabled style="margin-bottom : 5px;" />';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" data-label-text="{{Historiser}}" data-size="mini" />{{Historiser}}</span> ';
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" data-label-text="{{Afficher}}" data-size="mini" checked/>{{Visible}}</span> ';
    tr += '<span><input type="checkbox" class="cmdAttr expertModeVisible" data-label-text="{{Inverser}}" data-l1key="display" data-l2key="invertBinary" data-size="mini"/>{{Inverser}}</span> ';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    document.querySelector('#table_cmd tbody').insertAdjacentHTML('beforeend', tr);
    const lastRow = document.querySelector('#table_cmd tbody tr:last-child');
    lastRow.setJeeValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType(lastRow, init(_cmd.subType));
}