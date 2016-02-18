<?php
require_once('common.php');
require_once('db-interface.php');


if($_weekly==1)
	GenerateWeeklyJSONData();
else
	GenerateDailyJSONData();

function GarnishWorkLogs(&$worklogs)
{
	foreach($worklogs as $worklog)
	{
		$worklog->comment = DisplayName($worklog->author)."-".$worklog->comment;
		
	}
	
}

function MergeSameDayLogs(&$worklogs)
{

	$index = -1;
	$delete_index = array();
	foreach($worklogs as $worklog)
	{
		$index++;
		if(!isset($prev_worklog))
		{
			$prev_worklog = $worklog;
			continue;
		}
		if($prev_worklog->author == $worklog->author)
		{
			if($prev_worklog->started == $worklog->started)
			{
				$prev_worklog->comment = $prev_worklog->comment."<br>".$worklog->comment;
				$prev_worklog->timespent = (float)($prev_worklog->timespent + $worklog->timespent);
				$delete_index[] = $index;
				continue;
			}
		}
		$prev_worklog = $worklog;
	}
	foreach($delete_index as $index)
	{
		unset($worklogs[$index]);
		
	}
	
}

function GenerateWeeklyJSONData()
{
	global $_component;
	global $_showunworkedtasks;
	global $_showrecentonly;
	global $_significant;
	
	$projects = ReadDataBase();
	$gantt = array();

	usort($projects, "MostResentProjectSort");
	foreach($projects as $comp)
	{
		if($_component == "all")
		{
			
			
		}
		else if($comp->name != $_component)
			continue;
		
		if($_significant == 1)
		{
			if($comp->recent)
			{
				
			}
			else
			{			
				if($comp->dayspent < 10)
					continue;
			}
		}
		
		if($_showrecentonly == true) if($comp->recent==false) continue;
			
		if($comp->dayspent == 0)
			continue;
		else
			$data['desc'] = $comp->dayspent. " d";
		$component = str_replace(" ","%20",$comp->name);
		$link = '<a target="_blank" href=showgantt.php?_component='.$component.'&_showunworkedtasks=1&_showrecentonly=0&_itemsperpage=200>'.$comp->name.'</a>';
		//echo $link;
		$data['name'] = $link;
		$data['id'] = "1001";
		if($comp->status == "Done")
			$data['cssClassName'] = "ganttProjectDone";
		else
			$data['cssClassName'] = "ganttProject";
		$data['cssClassDesc'] = "ganttDesc";
		$data['values'] =  array();
	
		foreach($comp->weekwork as $week=>$timespent)
		{
			$start_date =  strtotime($week);
			if($timespent < 1)
				$days = 0;
			else if($timespent > 5)
				$days = 4;
			else
				$days = floor($timespent)-1;
			$start_date_monday = strtotime("-4 days",$start_date);
			$end_date = strtotime("+".$days." days",$start_date_monday);
			$start_date = $start_date_monday*1000;
			$end_date = $end_date*1000;
			
			$val['from'] = '/Date(' . $start_date . ')/';
			$val['to'] = '/Date(' . $end_date . ')/';
			
			if($comp->status == "Done")
				$val['customClass'] = "ganttGrey";
			else
				$val['customClass'] = "ganttGreen";
	
			//$val['customClass'] = "ganttGreen";
			$val['desc'] = 'sdss';
			$val['label'] = round($timespent);
			if($val['label'] == "")
				$val['label'] = 1;
			$val['cssClass'] = 'blue';
			$val['dataObj'] = (object) ['id' => 1000];

			$data['values'][] = $val;
		}
		$gantt[] = $data;
	}
	$db=NULL;
	echo json_encode($gantt);
}



function GenerateDailyJSONData()
{
	global $_component;
	global $_showunworkedtasks;
	global $_showrecentonly;
	
	$projects = ReadDataBase();
	$gantt = array();

	foreach($projects as $comp)
	{
		if($_component == "all")
		{
			
			
		}
		else if($comp->name != $_component)
			continue;
		
		if($_showrecentonly == true) if($comp->recent==false) continue;
			
		if($comp->dayspent == 0)
			continue;
		else
			$data['desc'] = $comp->dayspent. " d";
	
		$data['name'] = $comp->name;
		$data['id'] = "1001";
		$data['cssClassName'] = "ganttProject";
		$data['cssClassDesc'] = "ganttDesc";
		$data['values'] =  array();
	
		$start_date = strtotime($comp->updated)*1000;
		$end_date = $start_date;
		
		$val['from'] = '/Date(' . $start_date . ')/';
		$val['to'] = '/Date(' . $end_date . ')/';
		$val['customClass'] = "ganttProjectBar";
		$val['desc'] = '';
		$val['label'] = '';
		$val['cssClass'] = 'blue';
		$val['dataObj'] = (object) ['id' => 1000];

		$data['values'][] = $val;
		$gantt[] = $data;
	
		foreach($comp->tasks as $task)
		{
			if($_showrecentonly == true) if($task->recent==false) continue;
			if(isset($task->hide))
				continue;
			//echo $task->timespent." ".$task->status.EOL;
			if(($task->timespent == 0) &&  (($task->status == "Closed")||($task->status == "Resolved")))
			//if($_showunworkedtasks=false)
			{
				//echo "ignoring";
				//if($task->timespent == 0)
					continue;
			}
			$link = '<a id="pop" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
			$data['name'] = $link;//"&nbsp&nbsp".$task['summary'];
			
			if($task->timespent == 0)
			{
				$data['desc'] = " ";
				$data['values'] =  array();
				if(($task->status == "Resolved")||($task->status == "Closed"))
						$data['cssClassName'] = "ganttTaskDone";
					else
						$data['cssClassName'] = "ganttTask";
					
				$start_date = strtotime($task->updated)*1000;
				$end_date = $start_date;
				
				$val['from'] = '/Date(' . $start_date . ')/';
				$val['to'] = '/Date(' . $end_date . ')/';
				$val['customClass'] = "ganttProjectBar";
				$val['desc'] = "";
				$val['label'] = "";
				$val['dataObj'] = (object) ['id' => 1000];
				$data['values'][] = $val;
				$gantt[] = $data;
				continue;
			}
		
			$last_author = "none";
		
		
			GarnishWorkLogs($task->worklogs);
			MergeSameDayLogs($task->worklogs);
	
			foreach($task->worklogs as $worklog)
			{
				if($worklog->author != $last_author)
				{
					if($last_author != "none")
					{
						$gantt[] = $data;
						$data['name'] = " ";
					}
					$data['desc'] = DisplayName($worklog->author);
					if(($task->status == "Resolved")||($task->status == "Closed"))
						$data['cssClassName'] = "ganttTaskDone";
					else
						$data['cssClassName'] = "ganttTask";
					$data['id'] = "1001";
					$data['values'] =  array();
				}
			
				$start_date =  strtotime($worklog->started);
				
				if($worklog->timespent < 1)
					$days = 0;
				else
					$days = floor($worklog->timespent)-1;
				
				$end_date = strtotime("+".$days." days",$start_date);
				$start_date = $start_date*1000;
				$end_date = $end_date*1000;
			
				//echo $start_date.EOL;
				$val['from'] = '/Date(' . $start_date . ')/';
				$val['to'] = '/Date(' . $end_date . ')/';
				if(($task->status == "Resolved")||($task->status == "Closed"))
					$val['customClass'] = "ganttGrey";
				else
					$val['customClass'] = "ganttGreen";
				$val['desc'] = $worklog->comment;
				$val['label'] = truncate($worklog->timespent,1);
				$val['dataObj'] = (object) ['id' => 1000];
				$data['values'][] = $val;
				$last_author = $worklog->author;
				//echo "\t\t".$worklog['author'].$worklog['started'].EOL;
			}
			$gantt[] = $data;
		}
	}
	$db=NULL;
	echo json_encode($gantt);
}




?>