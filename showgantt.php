<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Gantt Chart</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=IE8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <link href="css/style.css" type="text/css" rel="stylesheet">
        <link href="css/prettify.min.css" rel="stylesheet" type="text/css">
        <style type="text/css">.fn-gantt *, .fn-gantt *:after,.fn-gantt *:before {-webkit-box-sizing: content-box;-moz-box-sizing: content-box;box-sizing: content-box;}
        </style>
    </head>
    <body> 
<?php
		require_once('common.php');
		function BuildJsonFetchLink()
		{
			global $_component,$_showunworkedtasks,$_showrecentonly,$_weekly,$_significant;
			return 'json-generator.php'.
			'?_component='.$_component.
			'&_showunworkedtasks='.$_showunworkedtasks.
			'&_showrecentonly='.$_showrecentonly.
			'&_weekly='.$_weekly.
			'&_significant='.$_significant;
					
		}
		if($_component != "all")
		{
			if (file_exists('generated/'.$_component.'_ed.png')) 
				echo '<img style="max-width:500px;max-height:500px;width:auto; height:auto;" src="generated/'.$_component.'_ed.png" alt="bottle" class="thumbnails" />';
			
			if (file_exists('generated/'.$_component.'.png'))
				echo '<img style="max-width:500px;max-height:500px;width:auto; height:auto;" src="generated/'.$_component.'.png" alt="bottle" class="thumbnails" />';
		}
		//echo BuildJsonFetchLink();
		//$arg_str='source: "json-generator.php?_component='.$_component.'&_showunworkedtasks='.$_showunworkedtasks.'&_showrecentonly='.$_showrecentonly.'&_weekly='.$_weekly.'",';
		//echo $arg_str;
?>
		<script src="js/jquery-1.7.2.min.js"></script>
		<script src="js/jquery.fn.gantt.js"></script>
		
		<script>
	    $(function() {

            "use strict";
			$(".gantt").gantt({
<?php
				echo 'source:"'.BuildJsonFetchLink().'",';
				echo 'scale:"'.$_scale.'",';
?>
                navigate: "scroll",				
                maxScale: "months",
                minScale: "days",
<?php
				echo 'scrollToToday:'.$_scroll.',';
                echo 'itemsPerPage:'.$_itemsperpage.',';
?>
                useCookie: false,
                onItemClick: function(data) {
					//window.open("showdata.php?"+data);
					//$("#content").load("showdata.php?"+data);
					//jAlert(datastring,"Log By ");
                    //alert(datastring);
					//e.preventDefault();
					
                },
				
                onAddClick: function(dt, rowId) {
                    //alert("Empty space clicked - add an item!");
                },
                onRender: function() {
                    if (window.console && typeof console.log === "function") {
                        console.log("chart rendered");
                    }
                }
            });
        });
    </script>
	<div class="gantt"></div>
    </body>
</html>
