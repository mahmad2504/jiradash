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
		<script src="js/jquery-1.7.2.min.js"></script>
		<script src="js/jquery.fn.gantt.js"></script>
		
		<script src="jsalert/jquery.alerts.js" type="text/javascript"></script>
		<link href="jsalert/jquery.alerts.css" rel="stylesheet" type="text/css" media="screen" />
		<link rel="stylesheet" type="text/css" href="css/jquery.jqtimeline.css" />
		<script type="text/javascript" src="js/jquery.jqtimeline.js"></script>
        <style type="text/css">.fn-gantt *, .fn-gantt *:after,.fn-gantt *:before {-webkit-box-sizing: content-box;-moz-box-sizing: content-box;box-sizing: content-box;}
        </style>
    </head>
    <body> 
<?php
		require_once('common.php');
		require_once('db-interface.php');
		$projects = ReadDataBase();
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
		function ReplaceAnyIllegalCharacter(&$string)
		{
			$string = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);
		}
		function ConstructTimeLine()
		{
			global $projects;
			$start_year = 2030;
			$end_year = 0;
			// Fake entry
			echo 'id : 1,';
			echo 'name : "Joined",';
			echo 'on : new Date(2009,11,15)';
			$id = 2;
			
			global $_component;
			foreach ($projects as $comp)
			if($_component == $comp->name)
			{
				foreach($comp->parenttasks as $ptask)
				{
					foreach($ptask->worklogs as $worklog)
					{
						$pos = strpos($worklog->comment, '{');
						if ($pos === false) 
						{
							
						}
						else	 
						{
							$submsgg = explode("}",substr($worklog->comment, $pos+1));
							
							echo '},{';
							echo 'id : '.$id++.',';
							//$submsg[0]=$worklog->started;//"https://pkl-mumtaza-w7/svn/SCRPackager/bitmap_code_gen".;
							ReplaceAnyIllegalCharacter($submsgg[0]);
							
							$submsg = explode("::",$submsgg[0]);
							if(count($submsg)>1)
							{
								$pos = strpos($submsg[1], 'http');
								if ($pos === false) {}
								else	
								{
									$submsg[1] = "<a style='color:white' href='".$submsg[1]."'>".$submsg[0]."</a>";
								}
								//$submsg[1] = $pos;
								echo 'name : "'.$submsg[1].'",';
								//echo 'on : new Date(2011,01,17)';
							}
							else
								echo 'name : "'.$submsgg[0].'",';
							
							$dte = explode("-",$worklog->started);
							echo 'on : new Date('.$dte[0].','.($dte[1]-1).','.$dte[2].')';
							if($dte[0] < $start_year)
								$start_year = $dte[0];
							if($dte[0]> $end_year)
								$end_year = $dte[0];
						}
					}
					foreach($ptask->subtasks as $stask)
					{
						foreach($stask->worklogs as $worklog)
						{
							$pos = strpos($worklog->comment, '{');
							if ($pos === false) {}
							else							
							{
								$submsgg = explode("}",substr($worklog->comment, $pos+1));
								
								echo '},{';
								echo 'id : '.$id++.',';
								//$submsg[0]=$worklog->started;//"https://pkl-mumtaza-w7/svn/SCRPackager/bitmap_code_gen".;
								ReplaceAnyIllegalCharacter($submsgg[0]);
								$submsg = explode("::",$submsgg[0]);
								//$submsg[0] = "<a style='color:white' href='http://www.w3schools.com'>Mumtaz!</a>";
								if(count($submsg)>1)
								{
									$pos = strpos($submsg[1], 'http');
									if ($pos === false) {}
									else	
									{
										$submsg[1] = "<a style='color:white' href='".$submsg[1]."'>".$submsg[0]."</a>";
									}
									//$submsg[1] = $pos;
									echo 'name : "'.$submsg[1].'",';
									//echo '<a href="'.$submsg[0].EOL.'">'.$submsg[0].EOL.'</a>';
								}
								else
									echo 'name : "'.$submsgg.'",';
								$dte = explode("-",$worklog->started);
								echo 'on : new Date('.$dte[0].','.($dte[1]-1).','.$dte[2].')';
								if($dte[0] < $start_year)
									$start_year = $dte[0];
								if($dte[0]> $end_year)
									$end_year = $dte[0];
							}
						}
					}
				}
			}
			return array($start_year,$end_year);
			
		}
?>

		<script>
	    $(function() {

            "use strict";
			jQuery(".content").hide();
				//toggle the componenet with class msg_body
			jQuery(".heading").click(function()
			{
				//jAlert("dfdfd","fdfdfd");
				jQuery(this).next(".content").slideToggle(200);
				//if ($(this).text()== "Hide") 
				//	jQuery(this).text("Old Logs ....");
				//else
				//	jQuery(this).text("Hide");
			});
	
			
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
					//$("#worklog").text("dsdsdsd");
					//jAlert(datastring,"Log By ");
                    //alert(data);
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
	<hr style="margin-bottom:10px"/>
	<div id="myTimeline"></div>
	<hr style="margin-bottom:0px"/>
	
<script type="text/javascript">
<?php
					echo 'var ev = [{';
					$years = ConstructTimeLine();
					$startYear = $years[0];
					$endYear = $years[1];
					$nyears = $endYear-$startYear+1;
					$gap = 75/$nyears;
					echo '}]';
?>

			var tl = $('#myTimeline').jqtimeline({
							events : ev,
<?php
							echo 'numYears:'.$nyears.',';
							echo 'gap:'.$gap.',';
							echo 'startYear:'.$startYear.',';
?>
							click:function(e,event){
								alert(event.name);
							}
						});
		</script>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-36251023-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

	<div class="gantt"></div>
<?php
	if($_component != "all")
	{
		if (file_exists('generated/'.$_component.'_ed.png')) 
		{
			echo '<img style="max-width:400px;max-height:400px;width:auto; height:auto;" src="generated/'.$_component.'_ed.png" alt="bottle" class="thumbnails" />';
			echo '</img>';
		}
		
		if (file_exists('generated/'.$_component.'.png'))
		{
			echo '<img style="max-width:400px;max-height:400px;width:auto; height:auto;" src="generated/'.$_component.'.png" alt="bottle" class="thumbnails" />';
			echo '</img>';
		}
	}
?>	
    </body>
</html>
