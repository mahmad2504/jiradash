<?php
require_once('common.php');

$db = 0;
OpenDataBase();

function OpenDataBase()
{
	global $db;
	$db = new PDO('sqlite:'.DATABASE);
	//$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	$db->exec( 'PRAGMA foreign_keys = ON;' );

}
function DeleteDatabase()
{
	global $db;
	$db = null;
	unlink(DATABASE);
	
}

function CreateDataBase()
{
	global $db;
	$databaseSql = <<<SQL
	CREATE TABLE 'Component' 
	(
		'cid' INTEGER PRIMARY KEY,
		'cname' TEXT NOT NULL,
		'project' TEXT NOT NULL
    );
	
	CREATE TABLE 'Task' 
	(
		'tid' INTEGER PRIMARY KEY,
		'component_id' INTEGER NOT NULL CONSTRAINT 'Component_cid' REFERENCES 'Component'('cid') ON DELETE CASCADE,
		'key' TEXT NOT NULL,
		'assignee' TEXT,
		'summary' TEXT NOT NULL,
		'ttimespent' INTEGER NOT NULL,
		'status' TEXT NOT NULL,
		'updated' TEXT NOT NULL,
		'parentid' INTEGER,
		'parentkey' TEXT
    );
	
	CREATE TABLE 'Worklog' 
	(
		'wid' INTEGER PRIMARY KEY,
		'task_id' INTEGER NOT NULL CONSTRAINT 'Task_tid' REFERENCES 'Task'('tid') ON DELETE CASCADE,
		'started' DATETIME NOT NULL,
		'timespent' TEXT NOT NULL,
		'author' TEXT NOT NULL, 
		'comment' TEXT NOT NULL
    );

SQL;
	$result = $db->exec( $databaseSql );
	if ($result === false) 
	{
		$err = $db->errorInfo();
		print_r($err);
        
        
	}
}

function UpdateTask($task)
{
	global $db;
	$updstmt = $db->prepare('UPDATE Task SET ttimespent = :timespent WHERE tid = :tid');
    $updstmt->bindParam(':tid', $task->id);
	$updstmt->bindParam(':timespent', $task->timespent);
    $result = $updstmt->execute();
	if ($result === false) 
	{
		$err = $db->errorInfo();
		print_r($err);  
	}
}

function AddTask($task)
{
	global $db;
	$component = $task->components[0];
	// SQL for inserting dummy data
	//echo $task->summary.EOL;
	
	$stmt = $db->prepare("INSERT INTO [Task] (tid, component_id, key,assignee,summary,ttimespent,status,updated,parentid,parentkey) VALUES (:task_id, :component_id, :key, :assignee, :summary, :timespent, :status, :updated, :parentid, :parentkey)");
		$stmt->bindParam(':task_id', $task->id);
		$stmt->bindParam(':component_id', $component->id);
		$stmt->bindParam(':key', $task->key);
		$stmt->bindParam(':assignee', $task->assignee);
		$stmt->bindParam(':summary', $task->summary);
		$stmt->bindParam(':timespent', $task->timespent);
		$stmt->bindParam(':status', $task->status);
		$stmt->bindParam(':updated', $task->updated);
	$stmt->bindParam(':parentid', $task->parentid);
	$stmt->bindParam(':parentkey', $task->parentkey);
	$result = $stmt->execute();
	if ($result === false) 
	{
		$err = $db->errorInfo();
		print_r($err);  
	}
	
}

function AddLog($log,$task_id)
{
	global $db;
	$stmt = $db->prepare("INSERT INTO [Worklog] (wid, task_id, started,timespent,author,comment) VALUES (:log_id, :task_id, :started, :timespent, :author, :comment)");
	//var_dump($result->fetchArray());
	
	$stmt->bindParam(':log_id', $log->id);
	$stmt->bindParam(':task_id', $task_id);
	$stmt->bindParam(':started', $log->started);
	$stmt->bindParam(':timespent', $log->timespent);
	$stmt->bindParam(':author', $log->author);
	$stmt->bindParam(':comment', $log->comment);
	$result = $stmt->execute();
	if ($result === false) 
	{
		$err = $db->errorInfo();
		echo "Error in Add Log id=".$log->id." date=".$log->started." taskid = ".$task_id.EOL;
		echo $err[2].EOL;
	}
}

function IsTaskPresent($task_id)
{
	global $db;
	$query = "SELECT * FROM Task where Task.tid = ".$task_id;
	$tasks = $db->query($query);
	foreach($tasks as $task)
		return new Task($task);
	return null;
}

function AddComponent($jira_project,$component)
{
	global $db;
	//echo "AddComponent ".$component->name.EOL;
	$query = "SELECT * FROM Component where Component.cid = ".$component->id;
	//echo $query.EOL;
	$comps = $db->query($query);
	if(count($comps->fetchAll())>0)
	{
		//echo $component->name." Already exist".EOL;
		return;
	}
	
	$dataSql = <<<SQL
	INSERT INTO 'Component' VALUES( '$component->id', '$component->name' ,'$jira_project');
SQL;
	$result = $db->exec( $dataSql );
	if ($result === false) 
	{
		$err = $db->errorInfo();
		//print_r($err);
    }
}

function Delete($component_name)
{
	global $db;

	$stmt = $db->prepare("DELETE from Component WHERE cname = :cname");
	$stmt->bindParam(':cname', $component_name);
	$result = $stmt->execute();
	//print_r($db->errorInfo());
}
function Find($tasks,$parentid)
{
	foreach($tasks as $task)
		if($task->id == $parentid)
			return $task;
	return null;
}
function UpdateParentChildTasks(&$projects)
{
	global $env;
	foreach($projects as $comp)
	{
		foreach($comp->tasks as $task)
		{
			if($task->parentid>0)
			{
				$parenttask = Find($comp->tasks,$task->parentid);
				if($parenttask != null)
				{
					if($env == "cmd") echo "parent of ".$task->summary." is ".$parenttask->summary.EOL;
					$parenttask->worklogs = array_merge($parenttask->worklogs,$task->worklogs);
					$task->worklogs = array();
					$parenttask->timespent += $task->timespent;
					$task->timespent = 0;
					$parenttask->engineers = array_merge($parenttask->engineers,$task->engineers);
					$task->engineers = array();
					$parenttask->recent = $task->recent || $parenttask->recent;
					$task->recent = 0;
					$task->hide=true;
					if($env == "cmd") echo $parenttask->timespent.EOL;
				}
			}
		}
		
	}
	
}
function ReadDataBase()
{
	global $db;
	global $users;
				
	$projects = array();
	
	$comps = $db->query("SELECT * FROM Component");
	foreach($comps as $comp)
	{
		//echo $comp['cname'].EOL;
		$query = "SELECT SUM(ttimespent) FROM Task Where component_id=".$comp['cid'];
		$query = $db->prepare($query);
		$query->execute();   
		$total = $query->fetch(PDO::FETCH_NUM);
		$total_days = truncate($total[0]/(3600*8),1);
		//if($total_days == 0)
		//	continue;
	
		$compt = new Component($comp);
		$compt->weekwork = array();
		$compt->engineers = array();
		$compt->status = "Done";
		//echo $compt->name.EOL;
		$compt->recent=false;
		$compt->updated = 0;
		$compt->recent_worklog_acc =0;
		$compt->dayspent = $total_days;
		
		$compt->tasks = array();
		$query = "SELECT * FROM Task where Task.component_id = ".$comp['cid']." ORDER By ttimespent DESC";
		$tasks = $db->query($query);
		
		foreach($tasks as $task)
		{
			$tsk =  new Task($task);
			//echo $tsk->key.EOL;
			$tsk->engineers = array();
			$tsk->recent=false;
			$tsk->worklogs = array();
			$query = "SELECT * FROM Worklog where Worklog.task_id = ".$task['tid']." ORDER BY author ASC, started DESC";
			$worklogs = $db->query($query);
			if(($tsk->status != "Resolved") && ($tsk->status != "Closed"))
				$compt->status = "In Progress";
			//$compt->updated = $tsk->updated;
			foreach($worklogs as $worklog)
			{
				$wrklog = new WorkLog($worklog);
				// ignore un registered users and their logs
				//if(array_key_exists($wrklog->author , $users )==false)
				//	continue;
				if (array_key_exists($wrklog->author ,$tsk->engineers))
					$tsk->engineers[$wrklog->author]+= (float)$wrklog->timespent;
				else
					$tsk->engineers[$wrklog->author] = (float)$wrklog->timespent;
				
				if (array_key_exists($wrklog->author,$compt->engineers))
					$compt->engineers[$wrklog->author]+= (float)$wrklog->timespent;
				else
					$compt->engineers[$wrklog->author] = (float)$wrklog->timespent;
				
			//$tsk->engineers[$wrklog->author] = 1;
			//$compt->engineers[$wrklog->author] = 1;
				if($compt->updated<$wrklog->started)
					$compt->updated = $wrklog->started;
				//echo $compt->updated.EOL;
				
				////////////////////////////
				// Compute weekly work load
				$date = new DateTime();
                $date->setTimestamp(strtotime($wrklog->started));
				$date->modify('friday this week');
				$date_label =  $date->format('Y-m-d');
				if (array_key_exists($date_label,$compt->weekwork ))
					$compt->weekwork[$date_label] += (float)$wrklog->timespent;
				else
					$compt->weekwork[$date_label] = (float)$wrklog->timespent;
				////////////////////////////
				
				
				if(strtotime($wrklog->started)>GetIntervalDate())
				{
					$wrklog->recent = true;
					$tsk->recent=true;
					$compt->recent=true;
					$compt->recent_worklog_acc += (float)$wrklog->timespent;
					//echo $compt->recent_worklog_acc.EOL ;
					
				}
				else
					$wrklog->recent = false;
		
				$tsk->worklogs[] = $wrklog;
			}
			$compt->tasks[] = $tsk;
		}
		ksort($compt->weekwork);
		$projects[] = $compt;
	}
	UpdateParentChildTasks($projects);
	//ksort($weekwork);
	//$projects[0]->weekwork = $weekwork;
	return $projects;
}

ReadDataBase();
?>

