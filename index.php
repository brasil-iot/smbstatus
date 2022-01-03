<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
<title>SHARE MANAGER</title>
<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="css/rowGroup.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<style type="text/css" class="init">
td.details-control {
    background: url('img/details_open.png') no-repeat center center;
    cursor: pointer;
}
tr.details td.details-control {
    background: url('img/details_close.png') no-repeat center center;
}
td.action-control {
    background: url('img/details_close.png') no-repeat center left;
    cursor: pointer;
}
tr.odd td:first-child,
tr.even td:first-child {
    padding-left: 4em;
}
</style>
<script type="text/javascript" language="javascript" src="js/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="js/dataTables.buttons.min.js"></script>
<script type="text/javascript" language="javascript" src="js/dataTables.rowGroup.min.js"></script>
<script type="text/javascript" language="javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" class="init">
$(document).ready(function() {
	var collapsedGroups = {};
	var bExpandCollapse = false;
	var bExpand = false;

	var dt = $('#thedata').DataTable( {
		ajax: "/smb.php",
		dom: "<'row'<'col-md-12'B>>" + "<'row'<'col-md-3'><'col-md-9'f>>" +
			"<'row'<'col-md-12'tr>>" +
			"<'row'<'col-md-12'l>>" +
			"<'row'<'col-md-5'i><'col-md-7'p>>",
		pageLength: -1,
		lengthMenu: [ [20, 500, 1000, -1], [20, 500, 1000, "todos"]],
		columns: [
			{ "data": "user" },
			{ "data": "name" },
			{ "data": "machine" },
			{ "data": "logged" },
			{ "data": "file" },
			{ "data": "since" },
			{
       				"class":          "action-control",
				"orderable":      false,
				"data":           null,
				"defaultContent": ""
			},
		],
		order: [[1, 'asc'], [4, 'asc']],
		rowGroup: {
			dataSrc: "name",
			startRender: function (rows, group) {
				if(bExpandCollapse) {
					collapsedGroups[group] = bExpand;
				}
				var collapsed = !!collapsedGroups[group];
				var x = rows.data();
				var nPID = x[0].pid;
				rows.nodes().each(function (r) {
					r.style.display = 'none';
					if (collapsed) {
						r.style.display = '';
					}
				});
				return $('<tr class="display"/>')
					.append('<td class="display" colspan="' + rows.columns()[0].length + '"><button type="button" class="btn btn-warning" data-pid="' + nPID + '"' + '>Disconnect</button> ' + group + ' (' + rows.count() + ' files open)</td>')
					.attr('data-name', group)
					.attr('data-pid', nPID)
					.toggleClass('collapsed', collapsed);
			}
		},
		columnDefs: [ {
			targets: [ 0,1,6 ],
			visible: false
		} ],
		buttons: [ {
			text: 'Show files per user',
			className: "btn btn-primary",
			action: function ( e, k, node, config ) {
				bExpandCollapse = true;
				bExpand = !bExpand;
				if(bExpand) {
					this.text('Show users only');
				} else {
					this.text('Show files per user');
				}

				dt.draw(false);

				bExpandCollapse = false;
			}
		}, {
			text: 'SHARE restart',
			className: "btn btn-danger",
			action: function ( e, k, node, config ) {
				if (!confirm('Confirm share restart ?'))
					return

				$.ajax({
					type: "POST",
					url: '/share-restart.php',
					data:"pid=NULL",
					success : function(data) {
						remote = data;
						alert(remote);
					}
				});
			}
		} ],
	} );

	$('#thedata tbody').on('click', 'tr.dtrg-start', function (e) {
		e.preventDefault();

		var name = $(this).data('name');
		collapsedGroups[name] = !collapsedGroups[name];

		dt.draw(false);
	});

	$('#thedata tbody').on('click', 'button.btn-warning', function (e) {
		e.preventDefault();

		if (!confirm('Confirm user disconnect ?'))
				return
		
		var PID = $(this).data('pid');

		$.ajax({
			type: "POST",
			url: '/killuser.php',
			data:"pid="+PID,
			async: false,
			success : function(data) {
				remote = data;
				alert(remote);
			}
		});
	});

} );
</script>
</head>
<body>
<div class="content" style="margin: 25px;">
	<h1 class="page_title">SHARE MANAGER</h1>
	<div class="info">
		<h2>Users and Files open</h2>
	</div>
	<hr/>
	<div class="xtable">
		<table id="thedata" class="table table-striped table-bordered" width="100%">
			<thead>
				<tr>
					<th>UID</th>
					<th>Name</th>
					<th>Machine</th>
					<th>Logged in</th>
					<th>File</th>
					<th>Open in</th>
					<th></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>UID</th>
					<th>Name</th>
					<th>Machine</th>
					<th>Logged in</th>
					<th>File</th>
					<th>Open in</th>
					<th></th>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
</body>
</html>
