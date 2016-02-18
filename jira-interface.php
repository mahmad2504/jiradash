<?php

require_once('common.php');


$curl = 0;

Login();
function Login()
{
	global $curl;
	$curl = curl_init();
	$username = 'himp';
	$password = 'hmip';
	curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	return $curl;
}

function ConvertTimeSpentSimple($timespent)
{
	
#echo $timespent."\r\n";
	$count = (int)$timespent;
	$type = preg_replace('/\d+/', '', $timespent );
	if($type == 'd')
		return $count;
	if($type == 'h')
		return $count/8;
	if($type == 'm')
		return $count/480;
#	if($type == 'h m')
#		return $count/8;
	if($type == 'w')
		return $count*5;
	else
		echo $timespent." ".$count." wrong type of time coversion";
		
	
	
}
function ConvertJiraTime($timespent)
{
	$acc = 0;
	$timespent_array = explode(" ",$timespent);
	foreach ($timespent_array as $i)
		$acc = $acc + ConvertTimeSpentSimple($i);
	return $acc;
}

function GetWorkLog($task)
{
	global $curl;
	global $users;
	$url=JIRA_SERVER."/rest/api/latest/issue/$task->key/worklog";
#echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);
	$worklogs = json_decode($out, true);

	
	$worklog_array = array();
	#echo $key.EOL;
    foreach ($worklogs['worklogs'] as $log) 
	{
		$worklog = new WorkLog();
		
		// ignore un registered users and their logs
		if(array_key_exists($log['author']['name'] , $users )==false)
			continue;
		
		
		
		$worklog->id = $log['id'];
		//str_replace("world","Peter","Hello world!");
		$start_date= explode("T", $log['started'], 2);
		$worklog->started = $start_date[0];
		
		$worklog->timespent = ConvertJiraTime($log['timeSpent']);
		$worklog->comment = $log['comment'];
		$worklog->author = $log['author']['name'];
		$worklog_array[] = $worklog;
		
    }
	// Sort  based on dates
	//$dates = array();
	//foreach ($worklog_array as $key => $value) 
	//{
	//	$dates[$key] = $value->started;
		//echo $dates[$key].'<br>';
	//}
	//if(count($dates)>0)
	//	array_multisort($dates,SORT_DESC,$worklog_array); 
	/////////////////////////////////////////////////
	
	//foreach ($worklog_array as $worklog)
	//	echo $worklog->started."  ".$worklog->comment.'<br>';
	
	$task->worklogs = $worklog_array;
	return $worklog_array;
}

function JsonDecode($json_data)
{
	$issues = json_decode($json_data, true);
	if(isset($issues['issues']))
	{
		$tasks = array();
		foreach ($issues['issues'] as $entry) 
		{
			$task = new Task();
			//print_r($entry);
			$task->key = $entry['key'];
			$task->id = $entry['id'];
			$task->summary = $entry['fields']['summary'];
			$task->assignee = $entry['fields']['assignee']['name'];				
			$update_date= explode("T", $entry['fields']['updated'], 2);
			$task->updated = $update_date[0];
			foreach($entry['fields']['components'] as $component)
			{
				$comp = new Component();
				$comp->id = $component['id'];
				$comp->name = $component['name'];
				$task->components[] = $comp;			
			}
			//$task->component = $entry['fields']['components']['0']['name'];
			if(strlen($entry['fields']['timespent']) > 0)
				$task->timespent = $entry['fields']['timespent'];
			else
				$task->timespent = 0;
			$task->status = $entry['fields']['status']['name'];
			
			if(isset($entry['fields']['parent']['id']))
			{
				$task->parentid = $entry['fields']['parent']['id'];
				$task->parentkey = $entry['fields']['parent']['key'];
			}
			else
			{
				$task->parentid = 0;
				$task->parentkey = 0;
			}
			
			$tasks[] = $task;
		}
		return $tasks;
	}
}
function SearchUpdatedTasks($jira_project,$updated_after)
{
	global $curl;
	//$component = str_replace(" ","%20",$component);
	//$query = 'project='.$jira_project.'+and+component="'.$component.'"&maxResults=1000';
	$query = 'project='.$jira_project.'+and+component+is+not+EMPTY+and+updated+>+"'.$updated_after.'"&maxResults=1000&fields=id,key,summary,components,assignee,timespent,status,updated,parent';

	$url=JIRA_SERVER."/rest/api/latest/search?jql=".$query;
#echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);
	
	return JsonDecode($out);
}

// Return Tasks of some component. No worklog added 
function GetTask($task_key)
{
	
	global $curl;
	$query = 'issuekey="'.$task_key.'"&maxResults=1000&fields=id,key,summary,components,assignee,timespent,status,updated,parent';
	$url=JIRA_SERVER."/rest/api/latest/search?jql=".$query;
//echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);
//echo $out;
	return JsonDecode($out);

}


// Return Tasks of some component. No worklog added 
function SearchTasks($jira_project,$component)
{
	
	global $curl;
	$component = str_replace(" ","%20",$component);
	$query = 'project='.$jira_project.'+and+component="'.$component.'"&maxResults=1000&fields=id,key,summary,components,assignee,timespent,status,updated,parent';
	$url=JIRA_SERVER."/rest/api/latest/search?jql=".$query;
#echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);
	return JsonDecode($out);

}

// Return Tasks - Along with worklog 
function GetWorkLogsForUpdatedTasks($jira_project,$updated_after)
{
	$tasks = SearchUpdatedTasks($jira_project,$updated_after); 
	if(count($tasks)>0)
	{
		foreach($tasks as $task)
			GetWorkLog($task);
		return $tasks;
	}
}

// Return Tasks - Along with worklog 
function GetWorkLogsForComponent($jira_project,$component)
{
	$tasks = SearchTasks($jira_project,$component);
	foreach($tasks as $task)
		GetWorkLog($task);
	return $tasks;
}


/////////////////////////////////////////////////////////////////
// API

// Login();
// $tasks = GetWorkLogsForUpdatedTasks($jira_project,"2016/01/01")
// $tasks = GetWorkLogsForComponent($jira_project,$component)

/////////////////////////////////////////////////////////////////

?>
