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
	curl_setopt($curl, CURLINFO_HEADER_OUT, true); // enable tracking    
	
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
	{
		//echo $timespent." ".$count." wrong type of time coversion";
		return 0;
	}
	
	
}

function ConvertJiraTime($timespent)//converts to days
{
	$acc = 0;
	$timespent_array = explode(" ",$timespent);
	foreach ($timespent_array as $i)
		$acc = $acc + ConvertTimeSpentSimple($i);
	return $acc*8*60*60;
}

function GetWorkLog(&$task)
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
		{
			$ts = ConvertJiraTime($log['timeSpent']);
			if($ts == 0)
			{
				echo "Some work log of task ".$task->key." is wrong";
			}
			$task->timespent = $task->timespent - $ts;
			
			continue;
		}
		
		
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
$id=-1;
function JsonDecode($json_data,$jira_project="none")
{
	//global $project_labels ;
	global $id;
	global $configuration;
	
	$components = $configuration[$jira_project];
	$issues = json_decode($json_data, true);
	if(isset($issues['issues']))
	{
		$tasks = array();
		foreach ($issues['issues'] as $entry) 
		{
			$task = new Task();
			//print_r($entry);
			$task->key = $entry['key'];
			//echo $task->key." ";
			$task->id = $entry['id'];
			$task->summary = $entry['fields']['summary'];
			$task->assignee = $entry['fields']['assignee']['name'];	
			$task->originalestimate = $entry['fields']['aggregatetimeoriginalestimate'];
			//echo $task->originalestimate.EOL;
			$update_date= explode("T", $entry['fields']['updated'], 2);
			$task->updated = $update_date[0];

			if(isset($entry['fields']['labels']))
			{
			foreach($entry['fields']['labels'] as $label)
			{
				//echo $label."\n";
				$j = 0;
				foreach($components as $comp_name => $comp_id)
				{
					//echo $comp_name." ".$comp_decorated_name."\n";
					if($label == $comp_name)
					{
						$comp = new Component();
						$comp->id = $comp_id;
						//echo $comp->id;
						$comp->name = $label;
						$task->components[] = $comp;
						//$id = $id - 1;
					}
					$j++;
				}
			}
			}
			
			foreach($entry['fields']['components'] as $component)
			{
				$found = 0;
				foreach($components as $comp_name => $comp_id)
				{
					
					if(($component['name'] == $comp_name)||($comp_name == "ALL"))
					{
				$comp = new Component();
				$comp->id = $component['id'];
				$comp->name = $component['name'];
						$task->components[] = $comp;
						$found = 1;
					}
				}
				if(!$found)
					echo "Skipping ".$component['name'].EOL;
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
			if( count($task->components) > 0 )
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
	$query = 'project='.$jira_project.'+and+updated+>+"'.$updated_after.'"&maxResults=1000&fields=id,key,labels,summary,components,assignee,timespent,status,updated,parent,aggregatetimeoriginalestimate';

	$url=JIRA_SERVER."/rest/api/latest/search?jql=".$query;
//echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);

	//$information = curl_getinfo($curl, CURLINFO_HEADER_OUT ); // request headers
	//print_r($information);
	
	return JsonDecode($out,$jira_project);
}

// Return Tasks of some component. No worklog added 
function GetTask($task_key)
{
	
	global $curl;
	$query = 'issuekey="'.$task_key.'"&maxResults=1000&fields=id,key,labels,summary,components,assignee,timespent,status,updated,parent,aggregatetimeoriginalestimate';
	$url=JIRA_SERVER."/rest/api/latest/search?jql=".$query;
//echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);
//echo $out;
	return JsonDecode($out);

}

// Return Tasks of some component. No worklog added 
function SearchTasksByLabel($jira_project,$label)
{
	
	global $curl;
	$label = str_replace(" ","%20",$label);
	$query = 'project='.$jira_project.'+and+labels="'.$label.'"&maxResults=1000&fields=id,key,labels,summary,components,assignee,timespent,status,updated,parent,aggregatetimeoriginalestimate';
	$url=JIRA_SERVER."/rest/api/latest/search?jql=".$query;
//echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);
	return JsonDecode($out,$jira_project);

}


// Return Tasks of some component. No worklog added 
function SearchTasks($jira_project,$component)
{
	
	global $curl;
	$component = str_replace(" ","%20",$component);
	$query = 'project='.$jira_project.'+and+component="'.$component.'"&maxResults=1000&fields=id,key,labels,summary,components,assignee,timespent,status,updated,parent,aggregatetimeoriginalestimate';
	$url=JIRA_SERVER."/rest/api/latest/search?jql=".$query;
#echo $url;
	curl_setopt($curl, CURLOPT_URL,$url);
	$out = curl_exec($curl);
	return JsonDecode($out,$jira_project);

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
	if(count($tasks)>0)
	{
	foreach($tasks as $task)
		GetWorkLog($task);
	return $tasks;
	}
}

// Return Tasks - Along with worklog 
function GetWorkLogsForLabel($jira_project,$label)
{
	$tasks = SearchTasksByLabel($jira_project,$label);
	if(count($tasks)>0)
	{
		foreach($tasks as $task)
			GetWorkLog($task);
		return $tasks;
	}
}

/////////////////////////////////////////////////////////////////
// API

// Login();
// $tasks = GetWorkLogsForUpdatedTasks($jira_project,"2016/01/01")
// $tasks = GetWorkLogsForComponent($jira_project,$component)

/////////////////////////////////////////////////////////////////

?>
