<!DOCTYPE html>
<html lang="en">
    <head>
        <title>jQuery.Gantt</title>
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
		<script src="js/jquery.min.js"></script>
		<script src="js/jquery.cookie.min.js"></script>
		<script src="js/jquery.fn.gantt.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/prettify.min.js"></script>
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
                    alert("Item clicked - show some details");
                },
                onAddClick: function(dt, rowId) {
                    alert("Empty space clicked - add an item!");
                },
                onRender: function() {
                    if (window.console && typeof console.log === "function") {
                        console.log("chart rendered");
                    }
                }
            });



            $(".gantt").popover2({
                selector: ".bar",
                title: "I'm a popover",
                content: "And I'm the content of said popover.",
                trigger: "hover"
            });

            prettyPrint();

        });
    </script>
	

	
	<div class="gantt"></div>
    </body>
</html>
