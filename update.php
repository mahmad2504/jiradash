<?php

require_once('jira-interface.php');
require_once('db-interface.php');


if (Check(JIRA_SERVER))
{
}
else
{
       echo "Jira Server Down".EOL;
	   return;
}

$projects = ReadDataBase();


function UpdateTheDateFile()
{
	$dte = date("Y/m/d+H:i");
	$file = fopen(UPDATEFILE,"w");
	fwrite($file,$dte.PHP_EOL);
	fclose($file);
	
	echo "Updated till ".$dte;
}
function Update($jira_project,$component)
{
	global $db;
	global $ignore_projects;
	
	echo "Updating ".$component,EOL;
	if(in_array ( $component , $ignore_projects ))
		return;
	
	$tasks = GetWorkLogsForComponent($jira_project,$component);
	if(count($tasks)>0)
	{
		//DeleteDatabase();
		OpenDataBase();
		//CreateDataBase();
		$db->beginTransaction();
		foreach($tasks as $task)
		{
			//echo $task->key;
			$components =  $task->components;
			foreach($components as $comp)
			{
				//echo $comp->name.EOL;
				AddComponent($jira_project,$comp);
			}
			AddTask($task);
			$worklogs = $task->worklogs;
			foreach($worklogs as $log)
				AddLog($log,$task->id);
		}
		$db->commit();
	}
	else
		echo "No task is found".EOL;
}

function Rebuild($jira_project,$date)
{
	global $db;

	$tasks = GetWorkLogsForUpdatedTasks($jira_project,$date);
	if(count($tasks)>0)
	{
		DeleteDatabase();
		OpenDataBase();
		CreateDataBase();
		$db->beginTransaction();
		foreach($tasks as $task)
		{
			//echo $task->key;
			$components =  $task->components;
			foreach($components as $comp)
			{
				//echo $comp->name.EOL;
				AddComponent($jira_project,$comp);
			}
			AddTask($task);
			$worklogs = $task->worklogs;
			foreach($worklogs as $log)
				AddLog($log,$task->id);
		}
		$db->commit();
	}
}

/// Full Update

function FullUpdate($date)
{
	global $supported_jira_projects;
	foreach($supported_jira_projects as $jira)
	{
		echo "Updating ".$jira.EOL;
		Rebuild($jira,$date);
	}
}

function ProjectUpdate($jira,$project)
{
	global $projects;
	foreach($projects as $component)
	{
		if($component->name == $project)
		{
			echo "Deleting ".$project.EOL;
			Delete($project);
			$project_name = $component->project;
			break;
		}
	}
	Update($jira,$project);
}


// Update delta

function UpdateDelta()
{
	global $supported_jira_projects;
	$update_list = array();
	foreach($supported_jira_projects as $jira)
	{
		if (file_exists(UPDATEFILE)) 
			$last_update_date = date ("Y/m/d+H:i" , filemtime(UPDATEFILE));	
		else
			$last_update_date = "2016/01/01+00:00";
		//echo "Fetching Data from Jira ".$jira." from ".$last_update_date." onwards".EOL;
		$tasks = GetWorkLogsForUpdatedTasks($jira,$last_update_date);
		if(count($tasks)> 0 )
		{
			foreach($tasks as $task)
			{
				$components =  $task->components;
				foreach($components as $comp)
				{
					$update_list[$comp->name]=$jira;
					//echo $comp->name.EOL;
				}
			}		
		}
	}
	foreach($update_list as $comp_name=>$jira)
	{
		//$comp_name,$jira.EOL;
		Delete($comp_name);
		Update($jira,$comp_name);
	}
}


function PrintUsage()
{
	echo "usage:update.php jira=hmip project=xyz",EOL;
	echo "usage:update.php date=2016/01/01".EOL;
	echo "usage:update.php";
}

// try these in particular order
if(isset($date))
{	
	FullUpdate($date);
	UpdateTheDateFile();
}

else if(isset($project))
{
	if(isset($jira))
	{
		ProjectUpdate($jira,$project);
	}
	else
	{
		PrintUsage();
		return;
	}
}
else if(isset($jira))
{
	PrintUsage();
	return;
}
else
{
	UpdateDelta();
	UpdateTheDateFile();
}

// Read the data again to create new graphs
$projects = ReadDataBase();
CreateAllChartsAndGraphs();

$db=null;
?>

