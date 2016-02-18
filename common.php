<?php

if( isset($argv))
{	
	define("EOL","\r\n"); 
	foreach ( $argv as $value)
	{
		$env="cmd";
		$params=explode("=",$value);
		if(count($params)==2)
			${$params[0]} = $params[1];
	}
	
}
else
{
	$env="web";
	define("EOL","<br>");
	foreach ($_GET as $key=>$value)
	{
		$$key=$value;
	}
}

// Default value for various command line parameters
if(!isset($_component))
	$_component = "all";
if(!isset($_showunworkedtasks))
	$_showunworkedtasks=0;
if(!isset($_showrecentonly))
	$_showrecentonly=true;
if(!isset($_itemsperpage))
	$_itemsperpage=20;
if(!isset($_scroll))
	$_scroll = 2;
if(!isset($_weekly))
	$_weekly = 0;
if(!isset($_scale))
	$_scale = "days";
if(!isset($_significant))
	$_significant = 0;
	
		


date_default_timezone_set('Asia/Karachi');


define("JIRA_SERVER",'http://jira.alm.mentorg.com:8080');
define("DATABASE",'jira.sqlite');
define("UPDATEFILE",'date');

$supported_jira_projects = array(
	'HMIP',
);

$users = array(
	"asali" => "Asad Ali",
    "ziaa" => "Asad Zia", 
    "mnadeem" => "Mehreen", 
    "aanwar" => "Awais Anwar", 
    "emalik" => "Ehtsham",
    "afazal" => "Awais Fazal",
    "aayub" => "Ali Ayub",
    "ymajeed" => "Yasir Majeed",
    "aarehman" => "Abdul Rehman",
);

class Task {
	public function __construct($task=0) 
	{
		$this->id = $task['tid'];
		$this->key = $task['key'];
		$this->assignee = $task['assignee'];
		$this->summary = $task['summary'];
		$this->timespent = $task['ttimespent'];
		$this->status = $task['status'];
		$this->updated = $task['updated'];
		$this->parentid = $task['parentid'];
		$this->parentkey = $task['parentkey'];
	}
	public $id;
	public $key;
	public $assignee;
	public $components;
	public $summary;
	public $timespent;
	public $status;
	public $worklogs;
	public $updated;
	public $parentid;
	public $parentkey;
}
class WorkLog {
	public function __construct($worklog=0) 
	{
		$this->id = $worklog['wid'];
		$this->started = $worklog['started'];
		$this->timespent = $worklog['timespent'];
		$this->author = $worklog['author'];
		$this->comment = $worklog['comment'];
	}
	public $id;
	public $started;
	public $timespent;
	public $author;
	public $comment;
}
class Component {
	public function __construct($comp=0) 
	{
		$this->id = $comp['cid'];
		$this->name = $comp['cname'];
		$this->project = $comp['project'];
	}
	public $id;
	public $name;
	public $dayspent;
	public $updated;
	public $project;
}
function DisplayName($user)
{
	global $users;
	if(array_key_exists ($user , $users ))
		return $users[$user];
	else
		return $user;
}

function SaveProjectWeeklyWorkLoad($compt)
{
	
    $date_labels = array();
	$date_labels = $compt->weekwork;
	if(count($date_labels)==1)
		return;
		
    ksort($date_labels);
    
    
    include "libchart/libchart/classes/libchart.php";

    $chart = new LineChart();

    $dataSet = new XYDataSet();

    $today = new DateTime("now");
     
    foreach ($date_labels as $key => $value) 
	{
		$dataSet->addPoint(new Point($key, $value));
	}
    $chart->setDataSet($dataSet);
    $chart->setTitle("Activity");
    $chart->render("generated/".$compt->name.".png");
}

function MostResentProjectSort($a, $b)
{
    return strcmp($b->updated, $a->updated);
}

function MostWorkedProjectSort($a, $b)
{
    return strcmp($b->dayspent, $a->dayspent);
}

function SaveWeeklyWorkLoad($projects)
{
    $date_labels = array();
	usort($projects, "MostWorkedProjectSort");
	foreach ($projects as $compt) 
    {
		//echo $compt->name.EOL;
		SaveProjectWeeklyWorkLoad($compt);
		foreach ($compt->weekwork as $key => $value) 
         {
            if (array_key_exists($key,$date_labels))
                $date_labels[$key] += $value;
            else
                $date_labels[$key] = $value;
            
         }
    }
    ksort($date_labels);
    
    
    include "libchart/libchart/classes/libchart.php";

    $chart = new LineChart();

    $dataSet = new XYDataSet();

    $today = new DateTime("now");
     
    foreach ($date_labels as $key => $value) {
      

        $dataSet->addPoint(new Point($key, $value));
   }

    $chart->setDataSet($dataSet);

    $chart->setTitle("Team Work Load");
    $chart->render("generated/workload.png");


}


function SavePieChartRecentProjects($projects)
{
    include "libchart/libchart/classes/libchart.php";

    $chart = new PieChart();

    $dataSet = new XYDataSet();

    foreach ($projects as $component)
    {
        if(!$component->recent)
            continue;
        $dataSet->addPoint(new Point($component->name.'('.(string)truncate($component->recent_worklog_acc,1).' Days)', round($component->recent_worklog_acc)));
    }    
    $chart->setDataSet($dataSet);
    $chart->setTitle("Last Week Projects");
    $chart->render("generated/projects.png");
}
function SaveEngineersWorkDistribution($projects)
{
	include "libchart/libchart/classes/libchart.php";
	foreach ($projects as $comp)
	{
		$chart = new PieChart();
		$dataSet = new XYDataSet();
		foreach($comp->engineers as $engineer=>$work)
		{
			//echo $engineer." ".$work.EOL;
			$dataSet->addPoint(new Point(DisplayName($engineer), round($work)));
		}    
		$chart->setDataSet($dataSet);
		$chart->setTitle("Distribution");
		$chart->render("generated/".$comp->name."_ed.png");
	}
}

function CreateAllChartsAndGraphs()
{
	global $projects;
	SavePieChartRecentProjects($projects);
	SaveWeeklyWorkLoad($projects);
	SaveEngineersWorkDistribution($projects);
}

function GetIntervalDate()
{
    $date = new DateTime();
    $date->modify('-9 day'); // to get the last week's first date
    $interval = $date->format('U');
    return $interval;
}


function truncate($val, $f="0")
{
    if(($p = strpos($val, '.')) !== false) {
        $val = floatval(substr($val, 0, $p + 1 + $f));
    }
    return $val;
}

?>