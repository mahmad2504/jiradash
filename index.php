<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>UI and Graphics Team Dashboard</title>
	
	<!-- <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css"> -->
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <!-- <link href="css/prettify.min.css" rel="stylesheet" type="text/css"> -->
     <style type="text/css">.fn-gantt *, .fn-gantt *:after,.fn-gantt *:before {-webkit-box-sizing: content-box;-moz-box-sizing: content-box;box-sizing: content-box;}  </style> 
	<!-- <script src="js/jquery-1.7.2.min.js"></script> -->
	<!-- <script src="js/jquery.fn.gantt.js"></script> -->
	
	
<link rel="stylesheet" type="text/css" href="easyui/themes/default/easyui.css">
	<link rel="stylesheet" type="text/css" href="easyui/themes/icon.css">
	
       

	<link rel="stylesheet" type="text/css" href="easyui/demo/demo.css">
	<script type="text/javascript" src="easyui/jquery.min.js"></script>
	<script type="text/javascript" src="easyui/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui.min.js"></script>
	
	
	<script src="js/jquery.fn.gantt.js"></script>
    <link type="text/css" rel="Stylesheet" href="themes/vader/jquery-ui.css">
	<!-- <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">  -->
	
	<!-- <link href="css/style.css" type="text/css" rel="stylesheet"> -->
    <!--  <link href="css/prettify.min.css" rel="stylesheet" type="text/css"> -->

<script type="text/javascript">
    $(document).ready(function () 
	{

		$(".gantt").gantt({
			source:"json-generator.php?component=all&_showunworkedtasks=0&_showrecentonly=0&_weekly=1&_significant=1",
			scale: "weeks",
            navigate: "scroll",
            maxScale: "months",
            minScale: "days",
			scrollToToday:true,
            itemsPerPage:100,
            useCookie: false,
			
			onItemClick: function(data) {
					var page = "showdata.php?"+data;
					alert(page);
					var pagetitle = "dfff";//$(this).attr("title")
					var height = 570;//$(this).attr("height")
					var width = 1200;//$(this).attr("width")
					var resizeable = false;//$(this).attr("resizeable")
					var $dialog = $('<div></div>')
			
					.html('<iframe style="" " src="' + page + '" width="99.5%" height="100%"></iframe>')
					.dialog({
						autoOpen: false,
						modal: true,
						height:height,
						resizable: resizeable,
						width: width,
						title: pagetitle
					});
					$dialog.dialog('open');
            },
		});
		
		
		$('a#pop').live('click', function (e) 
		{
            e.preventDefault();
            var page = $(this).attr("href")
            var pagetitle = $(this).attr("title")
			var height = $(this).attr("height")
			var width = $(this).attr("width")
			var resizeable = $(this).attr("resizeable")
            var $dialog = $('<div></div>')
            .html('<iframe style="border: 0px; " src="' + page + '" width="100%" height="100%"></iframe>')
            .dialog({
				autoOpen: false,
                modal: true,
                height:height,
				resizable: resizeable,
                width: width,
                title: pagetitle
            });
            $dialog.dialog('open');
        });
    });
</script>
	
	
	
</head>
<?php
require_once('common.php');
require_once('db-interface.php');
$projects = ReadDataBase();
//SavePieChartRecentProjects($projects);
//return;

function GetEngineerList($component)
{
	$eng_str = "";
	foreach($component->recent_engineers as $engineer=>$value) 
	if($eng_str=="") $eng_str = DisplayName($engineer); else $eng_str  = $eng_str." , ".DisplayName($engineer);
	return $eng_str;
}

function GetWeekNumber($date)
{
	$date = DateTime::createFromFormat("Y-m-d", $date);
	return date("W", $date->getTimestamp());
	
}
function DisplayOldProjects()
{
	global $projects;
	foreach($projects as $component)
	{
		if(!$component->recent)
		{
			$tabs = "&nbsp&nbsp&nbsp&nbsp";
			$encode_comp_name = str_replace(" ","%20",$component->name);
			if($component->status == "In Progress") $link_color = "green";
			else $link_color = "grey";
			$link= 'showgantt.php?_component='.$encode_comp_name.'&_showunworkedtasks=1&_showrecentonly=0&_itemsperpage=200&_scroll=1';
			echo '<p style="margin-left: .5cm;background-color: #f6f6f6;">';
			$leftspan= '<a id="pop" width="1200" height="570" title="'.$component->name.'" href='.$link. '>'.$component->name.'</a>';
			echo $leftspan;
			echo '<span style="float:left: 6cm;color:'.$link_color.'">'.$tabs.round($component->dayspent)." Days</span>";
			$egr_list = GetEngineerList($component);
			echo '<span style="color:grey;font-size:80%;float:right; border-right: thick double #f6f6f6; border-right-width: 40px; background-color:f6f6f6">'.$egr_list."</span>";
			echo '</p>';
		}
	}
}
function HowOld($date)
{
	$date1 = new DateTime("now");
	$date2 = new DateTime($date);
	$interval = date_diff($date1, $date2);
	return $interval->format('%R%a');
							
	
}
function DisplayResentWorkLogs()
{
	global $projects;
	echo '<div id="projects" title="Recent Project Feed"  style="">';
	echo '</div>';
	echo '<div>';
		echo "<p> Recent Projects </p>";
	echo '</div>';
	foreach($projects as $component)
	{
		if($component->recent)
		{
			$eng_str = GetEngineerList($component);
	
			$tabs = "&nbsp&nbsp&nbsp&nbsp";
			$encode_comp_name = str_replace(" ","%20",$component->name);
			$link = '<span style="float:right;"><a id="pop" width="1200" height="570" style="padding-right: 1cm;" title="'.$component->name.'" href=showgantt.php?_component='.$encode_comp_name.'&_showunworkedtasks=1&_showrecentonly=0&_itemsperpage=200>Gantt</a></span>';
			if($component->status == "In Progress")
				$link_color = "green";
			else
				$link_color = "grey";
			
			$title = "<span style='float:left;'>".$tabs.$component->name."<span style='color:".$link_color.";font-size:80%'>".$tabs.round($component->dayspent)." Days"."</span>"."</span><span style='color:grey;font-size:80%;float:right;padding-right:1cm;;'>".$eng_str."</span>";
			//$title ="ddddd";
			echo'<div id="project1" title="'.$title.'"  data-options="selected:true" style="padding:10px;">';  
			echo $link."<br>";
			foreach($component->parenttasks as $parenttask)
			{
				if($parenttask->recent)
				{
					echo $tabs.'<a id="pop" width="1200" height="570" style="font-weight: bold;" title="'.$parenttask->key.'" href='.JIRA_SERVER.'/browse/'.$parenttask->key.'>'.$parenttask->summary.'</a>'."".EOL;
					$timespent = 0.0;
					foreach($parenttask->worklogs as $worklog)
					{
						if($worklog->recent)
						{
							if( HowOld($worklog->started) >= -6)
								$image = "thisweek.png";
							else
								$image = "prevweek.png";;
							//$timespent = (float)$timespent + (float)$worklog->timespent;
							echo '<p style="margin-left: .5cm;margin-right: .5cm;">';
							
							$href=JIRA_SERVER.'/browse/'.$parenttask->key.'?focusedWorklogId='.$worklog->id.'&page=com.atlassian.jira.plugin.system.issuetabpanels%3Aworklog-tabpanel#worklog-'.$worklog->id.'>';
							echo '<a id="pop" width="1200" height="570" style="font-weight: bold;" title="'.$parenttask->key.'"  href="'.$href.'">';
							echo '<img  src="'.$image.'" alt="More" style="width:10;height:10px;">';
							echo '</a>';
							echo " ".DisplayName($worklog->author)." ".$worklog->comment.EOL;
							//echo $worklog->started;
							echo '</p>';
						}
					}
					foreach($parenttask->subtasks as $subtask)
					{
						foreach($subtask->worklogs as $worklog)
						if($worklog->recent)
						{
							if( HowOld($worklog->started) >= -6)
								$image = "thisweek.png";
							else
								$image = "prevweek.png";;
							//$timespent = (float)$timespent + (float)$worklog->timespent;
							echo '<p style="margin-left: .5cm;margin-right: .5cm;">';
							echo '<a id="pop" width="1200" height="570" style="" title="'.$subtask->key.'" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>'."".EOL;
							//echo $subtask->summary.'<br>';
							$href=JIRA_SERVER.'/browse/'.$subtask->key.'?focusedWorklogId='.$worklog->id.'&page=com.atlassian.jira.plugin.system.issuetabpanels%3Aworklog-tabpanel#worklog-'.$worklog->id.'>';
							echo '<a id="pop" width="1200" height="570" style="font-weight: bold;" title="'.$subtask->key.'"  href="'.$href.'">';
							echo '<img src="'.$image.'" alt="More" style="width:10;height:10px;">';
							echo '</a>';
							echo " ".DisplayName($worklog->author)." ".$worklog->comment.EOL;
							//echo $worklog->started;
							echo '</p>';
						}
					}
					//$timespent = "   (".truncate($timespent,1). " Days)";
					//echo '<script>';
					//echo '$("#task'.$task->id.'").append("'.$timespent.'")';
					//echo '</script>';
				}
				
			}
			
			echo'</div>';
		}
	}
}
function GetEastPanelContent()
{
	echo '<div class="easyui-accordion" style="width:100%;">';
		echo '<img style="max-width:500px;max-height:500px;width:auto; height:auto;" id="graph-image" alt="Pie chart"  src="generated/projects.png" />';
		echo '<img  style="max-width:500px;max-height:500px;width:auto; height:auto;" id="graph-image" alt="Pie chart"  src="generated/workload.png"/>';

		global $projects;
		$height =  count($projects)*28;
	
		//echo '<img style="max-width:500px;max-height:500px;width:auto; height:auto;" id="graph-image" alt="Pie chart"  src="bar.jpg" />';
		//echo '<iframe id="foo" name="foo" frameborder="0" scrolling="no" width="100%" height="'.$height.'" src="showgantt.php?component=all&_showunworkedtasks=0&_showrecentonly=0&_itemsperpage=200&_scroll=1&_weekly=1&_scale=weeks&_significant=1"></iframe>';
		//echo '<div id="projects" title="Click for Old Projects" data-options="" style="">';
		//DisplayOldProjects();
		//echo '</div>';
		
		echo '<div id="projects" title="Monthly Summary" data-options="selected:true" style="">';
		echo '<div class="gantt" style="width:100%;" >dsdsdsds</div>';
		echo '</div>';
		
	echo '</div>';
}
function GetWestPanelContent()
{
	echo '<div class="easyui-accordion" style="width:100%;">';
		DisplayResentWorkLogs();
		echo '<div id="projects" title="Old Projects" data-options="" style="">';
			DisplayOldProjects();
		echo '</div>';
		//global $projects;
		//$height =  count($projects)*29;
	echo '</div>';
	
	
	
	
	//echo '<img style="max-width:500px;max-height:500px;width:auto; height:auto;   display:table-cell;margin: auto auto;" id="graph-image" alt="Pie chart"  src="generated/projects.png" />';
	//echo '<img  style="max-width:500px;max-height:500px;width:auto; height:auto; display:table-cell;margin: auto auto;" id="graph-image" alt="Pie chart"  src="generated/workload.png"/>';
}

function GetSouthPanelContent()
{
	global $projects;
	$height =  count($projects)*29;
	//echo '<iframe id="foo" name="foo" frameborder="0" scrolling="no" width="100%" height="'.$height.'" src="showgantt.php?component=all&_showunworkedtasks=0&_showrecentonly=0&_itemsperpage=200&_scroll=1&_weekly=1&_scale=weeks&_significant=1"></iframe>';
	
}
?>


<body class="easyui-layout">

	<div data-options="region:'north'" style="height:25px">
	<img src="blue-gradient.jpg" alt="Mountain View" style="width:100%;height:20px;">
	</div>
	<div data-options="region:'west',collapsible:false, split:false,title:'UI & Graphics Dashboard'" style="width:60%;">
	<?php GetEastPanelContent()?>
	
	</div>
	
	<div data-options="region:'center',title:''"><?php GetWestPanelContent()?></div>
	<div data-options="region:'south',split:true" style="height:30px;">
	<img src="blue-gradient.jpg" alt="Mountain View" style="width:100%;height:20px;">
	</div>
</body>
</html>