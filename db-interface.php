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
					$parenttask->isParent=true;
					//if($env == "cmd") echo "parent of ".$task->summary." is ".$parenttask->summary.EOL;
					$parenttask->worklogs = array_merge($parenttask->worklogs,$task->worklogs);
					$task->worklogs = array();
					$parenttask->timespent += $task->timespent;
					$task->timespent = 0;
					$parenttask->engineers = array_merge($parenttask->engineers,$task->engineers);
					$task->engineers = array();
					$parenttask->recent = $task->recent || $parenttask->recent;
					$task->recent = 0;
					$task->hide=true;
					//if($env == "cmd") echo $parenttask->timespent.EOL;
				}
			}
			usort($comp->tasks,"MostWorkedTask");
		}
		
		
	}
	
}
function MostWorkedTask($a,$b)
{
	return $a->timespent < $b->timespent;
	
}

function PrintTOM($projects)
{
	foreach($projects as $component)
	{
		echo $component->name." ".$component->status." ".$component->timespent." (";
		foreach($component->engineers as $engineer=>$timespent)
			echo $engineer." ".$timespent. " ";
		echo ")".EOL;
		
		foreach($component->parenttasks as $parenttask)
		{
			echo " @".$parenttask->key." ".$parenttask->timespent." (";
			foreach($parenttask->engineers as $engineer=>$timespent)
				echo $engineer." ".$timespent. " ";
			echo ")".EOL;
			
	
			foreach($parenttask->worklogs as $worklog)
				echo "   ".$worklog->author." ".$worklog->timespent.EOL;
			
			foreach($parenttask->subtasks as $subtask)
			{
				echo "     ".$subtask->key." ".$subtask->timespent." (";
				foreach($subtask->engineers as $engineer=>$timespent)
					echo $engineer." ".$timespent. " ";
				echo ")".EOL;
				foreach($subtask->worklogs as $worklog)
					echo "       ".$worklog->author." ".$worklog->timespent.EOL;
			}
		}
		
	}
	
}


function ValidateTOM($projects)
{
	assert_options(ASSERT_ACTIVE, 1);
	assert_options(ASSERT_BAIL, 1);
	
	
	foreach($projects as $component)
	{

		$acc = 0;
		foreach($component->engineers as $engineer=>$timespent)
			$acc += $timespent;
		assert($acc == $component->timespent);
		
		$prev=0;
		$acc = 0;
		foreach($component->weekwork as $week=>$work)
		{
			assert($week >= $prev);
			$prev=$week;
			$acc += $work;
		}
		assert($component->timespent == $acc);
		//echo $component->name." ".$component->updated;
		if($component->recent)
		{
	
			assert($component->recent_worklog_acc > 0 );
			assert(strtotime($component->updated) > GetIntervalDate());
		}
		else
		{
			//echo $component->name." ".$component->updated." ".GetIntervalDate().EOL;
			assert( strtotime($component->updated) < GetIntervalDate());
			assert($component->recent_worklog_acc == 0 );
		}
		$acc2=0;
		
		foreach($component->parenttasks as $parenttask)
		{
			$acc2 += $parenttask->recent_worklog_acc;
			assert($component->status >= $parenttask->status);
			assert(count($component->engineers)>=count($parenttask->engineers));
			assert($component->updated >= $parenttask->updated);
			assert($parenttask->parentid == 0);
			$acc = 0;
			foreach($parenttask->engineers as $engineer=>$timespent)
				$acc += $timespent;
			assert($acc == $parenttask->timespent);
			
			$acc3=0;
			foreach($parenttask->subtasks as $subtask)
			{
				$acc3 += $subtask->recent_worklog_acc;
				assert($component->status >= $subtask->status);
				assert($parenttask->status >= $subtask->status);
				assert(count($parenttask->engineers)>=count($subtask->engineers));
				assert(count($component->engineers)>=count($subtask->engineers));
				assert($component->updated >= $subtask->updated);
				assert($parenttask->updated >= $subtask->updated);
				assert($subtask->parentid == $parenttask->id);
				$acc = 0;
				foreach($subtask->engineers as $engineer=>$timespent)
					$acc += $timespent;
				assert($acc == $subtask->timespent);
				
			}
			assert($parenttask->recent_worklog_acc >=  $acc3 );
		}
		assert($component->recent_worklog_acc == $acc2 );
		
		
		
	}
	
}


function UpdateEngineersWorkLogTime(&$engineers,$wlog)
{
	if (array_key_exists($wlog->author ,$engineers))
		$engineers[$wlog->author] += (float)$wlog->timespent;
	else
		$engineers[$wlog->author] = (float)$wlog->timespent;
}

function Decorate(&$status)
{
	if(($status == "Resolved") || ($status == "Closed"))
		$status = "Done";
	else
		$status = "In Progress";
	
}
function UpdateStatus($thisstatus,$prevstatus)
{
	if($prevstatus == "In Progress")
		return $prevstatus;
	else
		return $thisstatus;
}


function UpdateWeeklyWorkHighlights(&$comp,$wrklog,$date_label)
{
	$pos = strpos($wrklog->comment, 'https');
	if ($pos != false) 
	{
		$submsg = explode(" ",substr($wrklog->comment, $pos));
		if (array_key_exists($date_label,$comp->weekhighlights ))
			$comp->weekhighlights[$date_label] .= $submsg[0];
		else
			$comp->weekhighlights[$date_label] = $submsg[0].EOL;
	}
}
function UpdateWeeklyWorkLoad(&$comp,$wrklog)
{
	$date = new DateTime();
    $date->setTimestamp(strtotime($wrklog->started));
	$date->modify('friday this week');
	$date_label =  $date->format('Y-m-d');
	if (array_key_exists($date_label,$comp->weekwork ))
		$comp->weekwork[$date_label] += (float)$wrklog->timespent;
	else
		$comp->weekwork[$date_label] = (float)$wrklog->timespent;
	
	
	UpdateWeeklyWorkHighlights($comp,$wrklog,$date_label);
}
function IsRecent($dte)
{
	if(strtotime($dte)>GetIntervalDate())
		return true;
	else
		return false;
}
function ReadDataBase()
{
	global $db;
	global $env;
	global $users;
	$projects = array();
	$comps = $db->query("SELECT * FROM Component");
	foreach($comps as $comp)
	{
		$component = new Component($comp);
//if($env == "cmd") echo $component->name.EOL;
		$component->parenttasks = array();
		$component->engineers= array();
		$component->recent_engineers = array();
		$component->timespent=0;
		$component->updated=0;
		$component->recent_worklog_acc = 0;
		$component->status = "Done";
		$component->recent = false;
		$component->weekwork = array();
		$component->weekhighlights = array();
		
		$query = "SELECT * FROM Task where Task.component_id = ".$comp['cid']." AND Task.parentid = 0 ORDER By ttimespent DESC";
		//echo $query;
		$ptasks = $db->query($query);
		//print_r($db->errorInfo());
		
		foreach($ptasks as $ptask)
		{
			$parenttask = new Task($ptask);
			$parenttask->subtasks = array();
			$parenttask->worklogs = array();
			$parenttask->engineers= array();
			$parenttask->updated = 0;
			$parenttask->recent_worklog_acc = 0;
			$parenttask->recent = false;
			Decorate($parenttask->status);
			
			//echo " @".$ptask['key']." ".$ptask['status']." ".$ptask['ttimespent'].EOL;
			$query = "SELECT * FROM Worklog where Worklog.task_id = ".$ptask['tid']." ORDER BY author ASC, started DESC";
			$wlogs = $db->query($query);
			$acc=0;
			foreach($wlogs as $wlog)
			{
				$worklog = new WorkLog($wlog);
				$parenttask->worklogs[] = $worklog;
				$acc += $wlog['timespent'];
				if($parenttask->updated < $worklog->started)
					$parenttask->updated = $worklog->started;
				UpdateEngineersWorkLogTime($parenttask->engineers,$worklog);
				UpdateEngineersWorkLogTime($component->engineers,$worklog);
				UpdateWeeklyWorkLoad($component,$worklog);
				$worklog->recent = false;
				if(IsRecent($worklog->started))
				{
					$worklog->recent = true;
					$parenttask->recent=true;
					$component->recent=true;
					$component->recent_worklog_acc += $worklog->timespent;
					$parenttask->recent_worklog_acc+= $worklog->timespent;
					//if($env == "cmd") echo "1  ".$component->recent_worklog_acc." ".$worklog->timespent." ".$worklog->comment.EOL;
					//echo $worklog->timespent/(8*60*60)."   ".$parenttask->recent_worklog_acc.EOL;
					if (array_key_exists($worklog->author ,$component->recent_engineers))
						$component->recent_engineers[$worklog->author] += $worklog->timespent;
					else
						$component->recent_engineers[$worklog->author] = $worklog->timespent;
				}
				//echo "        ".$wlog['author']." ".$wlog['timespent'].EOL;
			}
			if($parenttask->timespent != $acc)  /// Means something is out of sync so we must flag it
				echo "     ".$parenttask->key." ".$parenttask->timespent." not equal to timespent of its child tasks whic is ".$acc.EOL;
			
			$query = "SELECT * FROM Task where Task.parentid = ".$ptask['tid']." ORDER By ttimespent DESC";
			$stasks = $db->query($query);
			foreach($stasks as $stask)
			{
				$subtask = new Task($stask);
				$subtask->worklogs = array();
				$subtask->engineers= array();
				$subtask->updated=0;
				$subtask->recent_worklog_acc = 0;
				$subtask->recent = false;
				Decorate($subtask->status);
			
				
				//echo "     ".$stask['key']." ".$stask['status']." ".$stask['ttimespent'].EOL;
				$query = "SELECT * FROM Worklog where Worklog.task_id = ".$stask['tid']." ORDER BY author ASC, started DESC";
				$wlogs = $db->query($query);
				$acc = 0;
				
				foreach($wlogs as $wlog)
				{
					$worklog = new WorkLog($wlog);
					$subtask->worklogs[] = $worklog;
					$acc += $wlog['timespent'];
					
					if($subtask->updated < $worklog->started)
						$subtask->updated = $worklog->started;
					
					UpdateEngineersWorkLogTime($subtask->engineers,$worklog);
					UpdateEngineersWorkLogTime($parenttask->engineers,$worklog);
					UpdateEngineersWorkLogTime($component->engineers,$worklog);
					UpdateWeeklyWorkLoad($component,$worklog);
					$worklog->recent = false;
					if(IsRecent($worklog->started))
					{
						$worklog->recent = true;
						$subtask->recent=true;
						$parenttask->recent=true;
						$component->recent=true;
						$component->recent_worklog_acc += $worklog->timespent;
						$subtask->recent_worklog_acc+=$worklog->timespent;
						$parenttask->recent_worklog_acc+=$worklog->timespent;
						//if($env == "cmd") echo "1  ".$component->recent_worklog_acc." ".$worklog->timespent." ".$worklog->comment.EOL;
						//echo $worklog->timespent/(8*60*60)."   ".$parenttask->recent_worklog_acc.EOL;
						
						if (array_key_exists($worklog->author ,$component->recent_engineers))
							$component->recent_engineers[$worklog->author] += $worklog->timespent;
						else
							$component->recent_engineers[$worklog->author] = $worklog->timespent;
					}
					//echo $compt->recent_worklog_acc.EOL ;
		
					//echo "        ".$wlog['author']." ".$wlog['timespent'].EOL;
				}
				if($subtask->timespent != $acc)  /// Means something is out of sync so we must flag it
					echo "     ".$subtask->key." ".$subtask->timespent." not equal to timespent of its child tasks whic is ".$acc.EOL;
				//$subtask->dstatus = DecorateStatus($subtask->status);
					
				if($parenttask->updated < $subtask->updated)
					$parenttask->updated = $subtask->updated;
			
				$parenttask->subtasks[] = $subtask;
				$parenttask->timespent += $subtask->timespent;
				$parenttask->status = UpdateStatus($subtask->status,$parenttask->status);
			}
			//echo "1  ".$component->recent_worklog_acc.EOL;
			if($component->updated < $parenttask->updated)
				$component->updated = $parenttask->updated;
			
			$component->parenttasks[] = $parenttask;
			$component->timespent += $parenttask->timespent;
			$component->status = UpdateStatus($parenttask->status,$component->status);
		}
		ksort($component->weekwork);
		
		//foreach($component->weekwork as $week=>$work)
		//	echo $week." ".$work.EOL;
		
		$component->dayspent = $component->timespent/(8*60*60);
		$projects[] = $component;
	}
	//PrintTOM($projects);
	//ValidateTOM($projects);
	
	return $projects;
	
}
//if($env == "cmd") ReadDataBase();

?>

