<?php
$placement = $_REQUEST['PLACEMENT'];
$placementOptions = isset($_REQUEST['PLACEMENT_OPTIONS']) ? json_decode($_REQUEST['PLACEMENT_OPTIONS'], true) : array();
$handler = ($_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];


if ($placement === 'USERFIELD_TYPE' && $placementOptions['MODE'] !== 'edit')
{
	//header('Location: https://www.parkzabava.ru/local/payment-sync/compiled/#/'.$placementOptions['VALUE']);
}

if(!is_array($placementOptions))
{
	$placementOptions = array();
}

if($placement === 'DEFAULT')
{
	$placementOptions['MODE'] = 'edit';
}
?>
<!DOCTYPE html>
<html>
<head>
	<link href=/local/payment-sync/compiled/css/app.83430947.css rel=preload as=style><link href=/local/payment-sync/compiled/js/app.6ff862e7.js rel=preload as=script><link href=/local/payment-sync/compiled/js/chunk-vendors.4adbf6a7.js rel=preload as=script><link href=/local/payment-sync/compiled/css/app.83430947.css rel=stylesheet>
	<script src="//api.bitrix24.com/api/v1/dev/"></script>
</head>
<body style="margin: 0; padding: 0; background-color: <?=$placementOptions['MODE'] === 'edit' ? '#fff' : '#f9fafb'?>;"><div id=app>Оплаты будут доступны после сохранения сделки</div>
	<?
	if($placement === 'DEFAULT'):
		?>

	<?
	elseif($placement === 'USERFIELD_TYPE'):
	if($placementOptions['MODE'] === 'edit')
	{
		?><script>
		BX24.placement.call('setValue', "Test", function(){});
		</script><?
	}
	else
	{
		if ($placementOptions['ENTITY_VALUE_ID'] > 0)
		{
			?><script>var vueAppParams = {
				deal: <?echo $placementOptions['ENTITY_VALUE_ID']?>
			}</script>

			<script src=/local/payment-sync/compiled/js/chunk-vendors.4adbf6a7.js></script><script src=/local/payment-sync/compiled/js/app.6ff862e7.js></script><?
		}

		/*?><script>window.location.href='https://www.parkzabava.ru/local/payment-sync/compiled/#/<?echo $placementOptions['VALUE']?>'</script><?*/
	}

	endif;
	?>

<script>
	var cheight = 200;
	function updateParent() {
		var newheight = document.getElementById("app").clientHeight;
		if (newheight !== cheight)
		{
			cheight = newheight;
			if (BX24) {
				BX24.resizeWindow(document.body.clientWidth,
					newheight);
			}
		}
	}
	BX24.ready(function()
	{
		updateParent();
	});

	updateParent();
</script>

</body>
</html>
