<?php
require_once('common.php');
require_once('db-interface.php');


if($_weekly==1)
	GenerateWeeklyJSONData();
else
	GenerateDailyJSONDataDetail();

//	GenerateDailyJSONData();

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
		
		//if($_significant == 1)
		//{
		//	if($comp->recent)
		//	{
		//		// Override
		//	}
		//	else
		//	{			
		//		if($comp->dayspent < 10)
		//			continue;
		//	}
		//}
		
		//if($_showrecentonly == true) if($comp->recent==false) continue;
		
		//$last_update = strtotime($comp->updated);
                if($comp->updated == 0)
                      continue;
		$days = HowOld($comp->updated);
		//echo $comp->name." ".$comp->updated.EOL;
		//echo $days.EOL;
		
		//$updated =  strtotime($comp->updated);

		if($days < -100)// Ignore if more than 100 days old
			continue;
		
		//if(!$comp->recent)
		//{
			
			//if($days < -100)// Ignore if more than 100 days old
			//	continue;
		
			//if($comp->dayspent < 10)
			//{
			//	if($days > -15)// Ignore if more than 100 days old
			//	{}
			//	else
			//		continue;
			//}
		//}
		
		if($comp->dayspent == 0)
			continue;
		else
		{
			if($comp->originalestimate > 0)
			{
				$data['desc'] = round($comp->dayspent)." / ".round($comp->originalestimate)."d";
				if(round($comp->dayspent)<=round($comp->originalestimate))
					$data['cssClassDesc'] = "ganttDesc";
				else
					$data['cssClassDesc'] = "ganttDescRed";
			}
			else
			{
				$data['desc'] = round($comp->dayspent)."d";
				$data['cssClassDesc'] = "ganttDesc";
			}
		}			
		$component = str_replace(" ","%20",$comp->name);
		if($comp->status == "Done")
			$link = '<a id="pop" style="color:grey;" width="1200" height="570" href=showgantt.php?_component='.$component.'&_showunworkedtasks=1&_showrecentonly=0&_itemsperpage=200>'.$comp->name.'</a>';
		else
			$link = '<a id="pop" style="color:green;" width="1200" height="570" href=showgantt.php?_component='.$component.'&_showunworkedtasks=1&_showrecentonly=0&_itemsperpage=200>'.$comp->name.'</a>';
		
		//echo $link;
		$data['name'] = $link;
		
		if($comp->status == "Done")
			$data['cssClassName'] = "ganttProjectDone";
		else
			$data['cssClassName'] = "ganttProject";
		
		$data['values'] =  array();
	
		foreach($comp->weekwork as $week=>$timespent)
		{
			$timespent = $timespent/(8*60*60);
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
			
			if(array_key_exists($week,$comp->weekhighlights))
			{
				$val['desc'] = $comp->weekhighlights[$week];
				if($comp->status == "Done")
					$val['customClass'] = "ganttGreyRed";
				else
					$val['customClass'] = "ganttGreenRed";
			}
			else
				$val['desc'] = "";
			
			//if( $comp->weekhighlights[$week]
	
			//$val['customClass'] = "ganttGreen";
			
			//$pos = strpos($worklog->comment, 'https');
			//if ($pos != false) 
			//{
			//	$submsg = explode(" ",substr($worklog->comment, $pos));
			//	$val['desc'] = $submsg[0];
			//}
			//else
			//$val['desc'] = ""; //round($timespent)." Days";
			$val['label'] = round($timespent);
			if($val['label'] == "")
				$val['label'] = 1;
			$val['cssClass'] = 'blue';
			$name =  str_replace(" ","%20",$comp->name);
			$val['dataObj'] = "_component=".$name."&_date=".date('Y-m-d', $start_date_monday);

			$data['values'][] = $val;
		}
		$gantt[] = $data;
	}
	$db=NULL;
	echo json_encode($gantt);
}



function GenerateDailyJSONDataDetail()
{
	global $_component;
	$projects = ReadDataBase();
	$gantt = array();
	foreach($projects as $component)
	{
		if($component->name != $_component)
			continue;
		
		$data['name'] = $component->name;
		if($component->originalestimate > 0)
		{
			$data['desc'] = '<span style="font-size: 11px; font-weight: bold;">'.round($component->dayspent)."/".round($component->originalestimate)."d".'</span>';
			if(round($component->dayspent)<=round($component->originalestimate))
				$data['cssClassDesc'] = "ganttDesc";
			else
				$data['cssClassDesc'] = "ganttDescRed";
		}
		else
		{
			$data['desc'] =  '<span style="font-size: 11px; font-weight: bold;">'.round($component->dayspent)."d".'</span>';
			$data['cssClassDesc'] = "ganttDesc";
		}
		$data['cssClassName'] = "ganttProject";
		//$data['cssClassDesc'] = "ganttDesc";
		$data['values'] =  array();
		
		if($component->updated == 0)
			$component->updated = date('Y-m-d');
		
		$start_date = strtotime($component->updated)*1000;
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
		
		
		foreach($component->parenttasks as $task)
		{
			// Remove closed unworked tasks
			if ($task->status=="Done" && $task->timespent == 0) // There is no worklog 
					continue;
			GarnishWorkLogs($task->worklogs);
			MergeSameDayLogs($task->worklogs);
			
			// Manage Sameday Work logs
			
			if($task->status == "Done")
				$link = '<a id="pop" style="color:grey;" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
			else
			{
				if($task->timespent == 0)
					$link = '<a id="pop" style="background-color: #E0FFFF;" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
				else
					$link = '<a id="pop" style="background-color: #c4ff4d;" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
			}
			
			$data['name'] = $link;//"&nbsp&nbsp".$task['summary'];
			if($task->originalestimate > 0)
			{
				$ts = round($task->timespent/(8*60*60));
				$oe = round($task->originalestimate/(8*60*60));
				
				$data['desc'] = '<span style="font-size: 11px; font-weight: bold;">'.$ts."/".$oe."d".'</span>';
				if($ts<=$oe)
					$data['cssClassDesc'] = "ganttDesc";
				else
					$data['cssClassDesc'] = "ganttDescRed";
				
			}
			else
			{
				$data['desc'] = '<span style="font-size: 11px;font-weight: bold;">'.round($task->timespent/(8*60*60))."d".'</span>';
				$data['cssClassDesc'] = "ganttDesc";
			
			}
			
			$data['values'] =  array();
			if(($task->status == "Done")) $data['cssClassName'] = "ganttTaskDone";
			else $data['cssClassName'] = "ganttTask";
			$start_date = strtotime($component->updated)*1000;
			$end_date = $start_date;
			$val['from'] = '/Date(' . $start_date . ')/';
			$val['to'] = '/Date(' . $end_date . ')/';
			$val['customClass'] = "ganttProjectBar";
			$val['desc'] = "";
			$val['label'] = "";
			$val['dataObj'] = (object) ['id' => 1000];
			$data['values'][] = $val;
			
			foreach($task->worklogs as $worklog)
			{
				$start_date =  strtotime($worklog->started);
				$timespent = $worklog->timespent/(8*60*60);
	
				if($timespent < 1)
					$days = 0;
				else
					$days = floor($timespent)-1;
				
				$end_date = strtotime("+".$days." days",$start_date);
				$start_date = $start_date*1000;
				$end_date = $end_date*1000;
				$val['from'] = '/Date(' . $start_date . ')/';
				$val['to'] = '/Date(' . $end_date . ')/';
				if(($task->status == "Done"))
					$val['customClass'] = "ganttGrey";
				else
					$val['customClass'] = "ganttGreen";
				$val['desc'] = $worklog->comment;
				$val['label'] = truncate($timespent,1);
				$val['dataObj'] = (object) ['id' => 1000];
				$data['values'][] = $val;
				
				
				
			}
			$gantt[] = $data;
			
			foreach($task->subtasks as $subtask)
			{
				if ($subtask->timespent == 0 && $subtask->status=="Done") // There is no worklog 
					continue;
				if($subtask->status == "Done")
					$link = '<a id="pop" style="color:grey;" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>';
				else
				{
					if($subtask->timespent == 0)
						$link = '<a id="pop" style="background-color: #E0FFFF;" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>';
					else
						$link = '<a id="pop" style="background-color: #c4ff4d;" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>';
				}
				$data['name'] = "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp".$link;//"&nbsp&nbsp".$subtask['summary'];
				$data['desc'] = "&nbsp&nbsp&nbsp".round($subtask->timespent/(8*60*60))."d";
				$data['cssClassDesc'] = "ganttDesc";
				
				$data['values'] =  array();
				if(($subtask->status == "Done")) $data['cssClassName'] = "ganttTaskDone";
				else $data['cssClassName'] = "ganttTask";
				$start_date = strtotime($component->updated)*1000;
				$end_date = $start_date;
				$val['from'] = '/Date(' . $start_date . ')/';
				$val['to'] = '/Date(' . $end_date . ')/';
				$val['customClass'] = "ganttProjectBar";
				$val['desc'] = "";
				$val['label'] = "";
				$val['dataObj'] = (object) ['id' => 1000];
				$data['values'][] = $val;
				//$gantt[] = $data;
				
				GarnishWorkLogs($subtask->worklogs);
				MergeSameDayLogs($subtask->worklogs);
				
				foreach($subtask->worklogs as $worklog)
				{
					$start_date =  strtotime($worklog->started);
					$timespent = $worklog->timespent/(8*60*60);
	
					if($timespent < 1)
						$days = 0;
					else
						$days = floor($timespent)-1;
				
					$end_date = strtotime("+".$days." days",$start_date);
					$start_date = $start_date*1000;
					$end_date = $end_date*1000;
					$val['from'] = '/Date(' . $start_date . ')/';
					$val['to'] = '/Date(' . $end_date . ')/';
					if(($subtask->status == "Done"))
						$val['customClass'] = "ganttGrey";
					else
						$val['customClass'] = "ganttGreen";
					$val['desc'] = $worklog->comment;
					$val['label'] = truncate($timespent,1);
					$val['dataObj'] = (object) ['id' => 1000];
					$data['values'][] = $val;
				}
				$gantt[] = $data;
				continue;
			}
		}
		break;
	}
	$db=NULL;
	echo json_encode($gantt);
	
}
function GenerateDailyJSONDataDetail_old()
{
	global $_component;
	$projects = ReadDataBase();
	$gantt = array();
	
	foreach($projects as $component)
	{
		if($component->name != $_component)
			continue;
		
		$data['name'] = $component->name;
		
		if($component->originalestimate > 0)
		{
			$data['desc'] = round($component->dayspent)."/".round($component->originalestimate)."d";
			if(round($component->dayspent)<=round($component->originalestimate))
				$data['cssClassDesc'] = "ganttDesc";
			else
				$data['cssClassDesc'] = "ganttDescRed";
		}
		else
		{
			$data['desc'] = round($component->dayspent)."d";
			$data['cssClassDesc'] = "ganttDesc";
		}
		
		//$data['desc'] = round($component->dayspent). " d";
		$data['cssClassName'] = "ganttProject";
		//$data['cssClassDesc'] = "ganttDesc";
		$data['values'] =  array();
		
		if($component->updated == 0)
			$component->updated = date('Y-m-d');
		
		$start_date = strtotime($component->updated)*1000;
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
		$data['cssClassDesc'] = "ganttDesc";
		
		
		foreach($component->parenttasks as $task)
		{
			if ($task->timespent == 0) // There is no worklog 
			{
				if($task->status=="Done") // Task is close as well so ignore that
					continue;
				else // Unworked tasks that is either open or in progress
				{
					if($task->status == "Done")
						$link = '<a id="pop" style="color:grey;font-size:11px" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
					else
						$link = '<a id="pop" style="color:#4169E1;font-weight: bold;font-size:11px;" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
					
					
					$data['name'] = $link;//"&nbsp&nbsp".$task['summary'];
					if($task->originalestimate > 0)
						$data['desc'] = round($task->originalestimate/(8*60*60));
					else
						$data['desc'] = " ";
					$data['cssClassDesc'] = "ganttDesc";
					$data['values'] =  array();
					if(($task->status == "Done")) $data['cssClassName'] = "ganttTaskDone";
					else $data['cssClassName'] = "ganttTask";
					$start_date = strtotime($component->updated)*1000;
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
			}
			// Task has some worklog
			if($task->status == "Done")
				$link = '<a id="pop" style="color:grey;;font-size:11px" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
			else
				$link = '<a id="pop" style="color:#4169E1;font-weight: bold;font-size:11px;" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
			$data['name'] = $link;//"&nbsp&nbsp".$task['summary'];
			
			if($task->originalestimate > 0)
			{
				$ts = round($task->timespent/(8*60*60));
				$oe = round($task->originalestimate/(8*60*60));
				
				$data['desc'] = $ts."/".$oe."d";
				if($ts<=$oe)
					$data['cssClassDesc'] = "ganttDesc";
				else
					$data['cssClassDesc'] = "ganttDescRed";
				
			}
			else
				$data['desc'] = round($task->timespent/(8*60*60))."d";
			
			$data['values'] =  array();
			if(($task->status == "Done")) $data['cssClassName'] = "ganttTaskDone";
			else $data['cssClassName'] = "ganttTask";
			$start_date = strtotime($component->updated)*1000;
			$end_date = $start_date;
			$val['from'] = '/Date(' . $start_date . ')/';
			$val['to'] = '/Date(' . $end_date . ')/';
			$val['customClass'] = "ganttProjectBar";
			$val['desc'] = "";
			$val['label'] = "";
			$val['dataObj'] = (object) ['id' => 1000];
			$data['values'][] = $val;
			$gantt[] = $data;
			$data['cssClassDesc'] = "ganttDesc";
			$data['name'] = "";
			
			$last_author = "none";
			GarnishWorkLogs($task->worklogs);
			MergeSameDayLogs($task->worklogs);
			$worklogged =  false;
			foreach($task->worklogs as $worklog)
			{
				$worklogged =  true;
				if($worklog->author != $last_author)
				{
					if($last_author != "none")
					{
						$gantt[] = $data;
						$data['name'] = " ";
					}
					$data['desc'] = DisplayName($worklog->author);
					if(($task->status == "Done"))
					{
						$data['cssClassName'] = "ganttTaskDone";
					}
					else
					{
						$data['cssClassName'] = "ganttTask";
					}
					$data['values'] =  array();
				}
				$start_date =  strtotime($worklog->started);
				$timespent = $worklog->timespent/(8*60*60);
	
				if($timespent < 1)
					$days = 0;
				else
					$days = floor($timespent)-1;
				
				$end_date = strtotime("+".$days." days",$start_date);
				$start_date = $start_date*1000;
				$end_date = $end_date*1000;
				$val['from'] = '/Date(' . $start_date . ')/';
				$val['to'] = '/Date(' . $end_date . ')/';
				if(($task->status == "Done"))
					$val['customClass'] = "ganttGrey";
				else
					$val['customClass'] = "ganttGreen";
				$val['desc'] = $worklog->comment;
				$val['label'] = truncate($timespent,1);
				$val['dataObj'] = (object) ['id' => 1000];
				$data['values'][] = $val;
				$last_author = $worklog->author;
			}
			if($worklogged)
			{
				$gantt[] = $data;
			}
				
			
			
			foreach($task->subtasks as $subtask)
			{
				if ($subtask->timespent == 0) // There is no worklog 
				{
					if($subtask->status=="Done") // subtask is close as well so ignore that
						continue;
					else // Unworked tasks that is either open or in progress
					{
						if($subtask->status == "Done")
							$link = '<a id="pop" style="color:grey;" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>';
						else
							$link = '<a id="pop" style="color:blue" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>';
						
						$data['name'] = "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp".$link;//"&nbsp&nbsp".$subtask['summary'];
						$data['desc'] = " ";
						$data['values'] =  array();
						if(($subtask->status == "Done")) $data['cssClassName'] = "ganttTaskDone";
						else $data['cssClassName'] = "ganttTask";
						$start_date = strtotime($component->updated)*1000;
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
				}
				// subtask has some worklog
				if($subtask->status == "Done")
					$link = '<a id="pop" style="color:grey;" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>';
				else
					$link = '<a id="pop" style="" href='.JIRA_SERVER.'/browse/'.$subtask->key.'>'.$subtask->summary.'</a>';
				
				$data['name'] = "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp".$link;//"&nbsp&nbsp".$subtask['summary'];
				$last_author = "none";
				GarnishWorkLogs($subtask->worklogs);
				MergeSameDayLogs($subtask->worklogs);
				foreach($subtask->worklogs as $worklog)
				{
					if($worklog->author != $last_author)
					{
						if($last_author != "none")
						{
							$gantt[] = $data;
							$data['name'] = " ";
						}
						$data['desc'] = DisplayName($worklog->author);
						if(($subtask->status == "Done"))
						{
							$data['cssClassName'] = "ganttTaskDone";
						}
						else
						{
							$data['cssClassName'] = "ganttTask";
						}
						$data['values'] =  array();
					}
					$start_date =  strtotime($worklog->started);
					$timespent = $worklog->timespent/(8*60*60);
	
					if($timespent < 1)
						$days = 0;
					else
						$days = floor($timespent)-1;
				
					$end_date = strtotime("+".$days." days",$start_date);
					$start_date = $start_date*1000;
					$end_date = $end_date*1000;
					$val['from'] = '/Date(' . $start_date . ')/';
					$val['to'] = '/Date(' . $end_date . ')/';
					if(($subtask->status == "Done"))
						$val['customClass'] = "ganttGrey";
					else
						$val['customClass'] = "ganttGreen";
					$val['desc'] = $worklog->comment;
					$val['label'] = truncate($timespent,1);
					$val['dataObj'] = (object) ['id' => 1000];
					$data['values'][] = $val;
					$last_author = $worklog->author;
				}
				$gantt[] = $data;
			}
		}
		break;
	}
	$db=NULL;
	echo json_encode($gantt);
}

function MergeChildWorkLogs(&$component)
{
	foreach($component->parenttasks as $parenttask)
	{
		foreach($parenttask->subtasks as $subtask)
		{
			$parenttask->worklogs = array_merge($parenttask->worklogs,$subtask->worklogs);
			//$subtask->worklogs = array();
		}
		
	}
	
}
function SortOnUpdated($a,$b)
{
	return ($a->updated < $b->updated);
}
function SortByUser($a,$b)
{
	return strnatcmp ( $a->author , $b->author );

}
function GenerateDailyJSONData()
{
	global $_component;
	global $env;
	$projects = ReadDataBase();
	
	$gantt = array();

	foreach($projects as $component)
	{
		if($component->name != $_component)
			continue;

		MergeChildWorkLogs($component);
		
		$data['name'] = $component->name;
		
		if($component->originalestimate > 0)
		{
			$data['desc'] = round($component->dayspent)."/".round($component->originalestimate)."d";
			if(round($component->dayspent)<=round($component->originalestimate))
				$data['cssClassDesc'] = "ganttDesc";
			else
				$data['cssClassDesc'] = "ganttDescRed";
		}
		else
		{
			$data['desc'] = round($component->dayspent)."d";
			$data['cssClassDesc'] = "ganttDesc";
		}
		//$data['desc'] = round($component->dayspent). " d";
		$data['cssClassName'] = "ganttProject";
		//$data['cssClassDesc'] = "ganttDesc";
		$data['values'] =  array();
		if($component->updated == 0)
			$component->updated = date('Y-m-d');
		
		$start_date = strtotime($component->updated)*1000;
		$end_date = $start_date;
		
		$val['from'] = '/Date(' . $start_date . ')/';
		$val['to'] = '/Date(' . $end_date . ')/';
		$val['customClass'] = "ganttProjectBar";
		$val['desc'] = '';
		//$data['cssClassDesc'] = "ganttDesc";
		$val['label'] = '';
		$val['cssClass'] = 'blue';
		$val['dataObj'] = (object) ['id' => 1000];

		$data['values'][] = $val;
		$gantt[] = $data;
		
		usort($component->parenttasks,"SortOnUpdated");
		foreach($component->parenttasks as $task)
		{
			
			if ($task->timespent == 0) // There is no worklog 
			{
				if($task->status=="Done") // Task is close as well so ignore that
					continue;
				else // Unworked tasks that is either open or in progress
				{
					$link = '<a id="pop" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
					$data['name'] = $link;//"&nbsp&nbsp".$task['summary'];
					$data['desc'] = " ";
					$data['values'] =  array();
					if(($task->status == "Done")) $data['cssClassName'] = "ganttTaskDone";
					else $data['cssClassName'] = "ganttTask";
					$start_date = strtotime($component->updated)*1000;
					$end_date = $start_date;
					$val['from'] = '/Date(' . $start_date . ')/';
					$val['to'] = '/Date(' . $end_date . ')/';
					$val['customClass'] = "ganttProjectBar";
					$val['desc'] = "";
					$val['label'] = "";
					$val['dataObj'] = "";//(object) ['id' => 1000];
					$data['values'][] = $val;
					$gantt[] = $data;
					continue;
				}
			}
			
	
			//GarnishWorkLogs($task->worklogs);
			//MergeSameDayLogs($task->worklogs);
			
			// Task has some worklog
			$link = '<a id="pop" href='.JIRA_SERVER.'/browse/'.$task->key.'>'.$task->summary.'</a>';
			$data['name'] = $link;//"&nbsp&nbsp".$task['summary'];
			$last_author = "none";
			GarnishWorkLogs($task->worklogs);
			MergeSameDayLogs($task->worklogs);
	        
			usort($task->worklogs, "SortByUser");
			//echo $task->summary.EOL;
			
			 
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
					$data['cssClassDesc'] = "ganttDesc";
					if(($task->status == "Done"))
					{
						$data['cssClassName'] = "ganttTaskDone";
					}
					else
					{
						$data['cssClassName'] = "ganttTask";
					}
					$data['values'] =  array();
				}
				$start_date =  strtotime($worklog->started);
				$timespent = $worklog->timespent/(8*60*60);
	
				if($timespent < 1)
					$days = 0;
				else
					$days = floor($timespent)-1;
				
				$end_date = strtotime("+".$days." days",$start_date);
				$start_date = $start_date*1000;
				$end_date = $end_date*1000;
				$val['from'] = '/Date(' . $start_date . ')/';
				$val['to'] = '/Date(' . $end_date . ')/';
				if(($task->status == "Done"))
					$val['customClass'] = "ganttGrey";
				else
					$val['customClass'] = "ganttGreen";
				
				$pos = strpos($worklog->comment, 'https');
				if ($pos != false) 
				{
					//$val['desc'] = $comp->weekhighlights[$week];
					if($component->status == "Done")
						$val['customClass'] = "ganttGreyRed";
					else
						$val['customClass'] = "ganttGreenRed";
				}
				
				
				$val['desc'] = $worklog->comment;
				$val['label'] = truncate($timespent,1);
				$val['dataObj'] = $worklog->comment;
				$data['values'][] = $val;
				$last_author = $worklog->author;
			}
			$gantt[] = $data;
		}
	}
	$db=NULL;
	echo json_encode($gantt);
}

?>