<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	 <title>Gantt Chart</title>
  </head>
    <body> 
	
<?php
require_once('common.php');
require_once('db-interface.php');

//echo '<p style=color:blue>'.$_component.'</p>';
$start_date = $_date;
$date = strtotime($_date);
$date = strtotime("+6 day", $date);
$end_date = date('Y-m-d', $date);
//echo $start_date.EOL;
//echo $end_date.EOL;


$projects = ReadDataBase();


echo '<table style="width:100%">';
foreach($projects as $comp)
{
	if($comp->name == $_component)
	{
		foreach($comp->parenttasks as $ptask)
		{
			foreach($ptask->worklogs as $worklog)
			{
				
				if(($worklog->started >= $start_date)&&($worklog->started <= $end_date))
				{
					if(strlen($worklog->comment)>0)
					{
						
						if(strlen($worklog->comment)>0)
						{
							echo '<tr style="vertical-align: top; text-align: left;">';
							
							echo '<td width="3%">';
							
							
							$href=JIRA_SERVER.'/browse/'.$ptask->key.'?focusedWorklogId='.$worklog->id.'&page=com.atlassian.jira.plugin.system.issuetabpanels%3Aworklog-tabpanel#worklog-'.$worklog->id.'>';
							echo '<a id="pop" width="1200" height="570" style="font-weight: bold;" title="'.$ptask->key.'"  href="'.$href.'">';
							$image = "link.png";
							echo '<img  src="'.$image.'" alt="More" style="width:15;height:15px;">';
							
							echo '</td>';
							
							
							echo '<td width=15%">';
							$timespent = $worklog->timespent/(8*60*60);
							echo date('F jS Y',strtotime($worklog->started));
							
							echo '</td>';
							
							echo '<td width="9%">';
							echo DisplayName($worklog->author);
							echo '</td>';
							
							echo '<td width="6%">';
							$timespent = $worklog->timespent/(8*60*60);
							if($timespent < 1)
								echo truncate(($timespent*8),1)." Hours";
							else
								echo truncate($timespent,1)." Days";
							
							echo '</td>';
							
							echo '<td>';
							echo $worklog->comment;
							echo '</td>';
							//if($timespent < 1)
							//	echo date('F jS Y',strtotime($worklog->started)).' '.($timespent*8).' Hours ';
							//else
							//	echo date('F jS Y',strtotime($worklog->started)).' '.$worklog->timespent/(8*60*60)." Days ";
							//echo DisplayName($worklog->author)." ".$worklog->comment.EOL;
							echo '</tr>';
						}	
					
					}
				}
				
			}
			foreach($ptask->subtasks as $stask)
			{
				foreach($stask->worklogs as $worklog)
				{
					
					if(($worklog->started >= $start_date)&&($worklog->started <= $end_date))
					{
						if(strlen($worklog->comment)>0)
						{
							echo '<tr style="vertical-align: top; text-align: left;">';
							
						
							echo '<td width="3%">';
							
							$href=JIRA_SERVER.'/browse/'.$stask->key.'?focusedWorklogId='.$worklog->id.'&page=com.atlassian.jira.plugin.system.issuetabpanels%3Aworklog-tabpanel#worklog-'.$worklog->id.'>';
							echo '<a id="pop" width="1200" height="570" style="font-weight: bold;" title="'.$stask->key.'"  href="'.$href.'">';
							$image = "link.png";
							echo '<img  src="'.$image.'" alt="More" style="width:15;height:15px;">';
							
							echo '</td>';
						
							
							echo '<td width="15%">';
							$timespent = $worklog->timespent/(8*60*60);
							echo date('F jS Y',strtotime($worklog->started));
							
							echo '</td>';
							
							echo '<td width="9%">';
							echo DisplayName($worklog->author);
							echo '</td>';
							
							echo '<td width="6%">';
							$timespent = $worklog->timespent/(8*60*60);
							if($timespent < 1)
								echo truncate(($timespent*8),1)." Hours";
							else
								echo truncate($timespent,1)." Days";
							echo '</td>';
							
							echo '<td>';
							
							echo $worklog->comment;
							echo '</td>';
							//if($timespent < 1)
							//	echo date('F jS Y',strtotime($worklog->started)).' '.($timespent*8).' Hours ';
							//else
							//	echo date('F jS Y',strtotime($worklog->started)).' '.$worklog->timespent/(8*60*60)." Days ";
							//echo DisplayName($worklog->author)." ".$worklog->comment.EOL;
							 echo '</tr>';
						}
						
					}
				}
			}
			
		}
	}
}
echo '</table>';

?>

</body>
</html>
