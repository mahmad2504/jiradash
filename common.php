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

$label_id = -1000;
$configuration = Array(
    'MEH' => Array
        (
            "intel_automotive_hyp" =>  $label_id--,
        ),
		'HMIP' => Array
        (
            'ALL' => "ALL",
        ),
/*
    'HMIP' => Array
        (
            'Intel-Broxton' => "Intel Broxton",
        ),
*/
);
    

$supported_jira_projects = array();

foreach($configuration as $proj => $comp_arr)
	$supported_jira_projects[] = $proj;
	
/*
$ignore_projects = array(
"Venetian",
"test",

);

$project_labels = array(
	"intel_automotive_hyp" => "Intel-Hypervisor"
);
*/

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
    "hmustafa" => "Hamza Mustafa",
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
		$this->originalestimate = $task['originalestimate'];
		
	}
	public $id;
	public $key;
	public $assignee;
	public $components; // Filled in when updated from Jira. No other use
	public $summary;
	public $timespent;
	public $status;
	public $updated;
	public $parentid;
	public $parentkey;
	public $originalestimate;
	
	////////////////////
	public $subtasks; // Will hold objects of type Task if it is parent
	public $worklogs; // Will hold objects if type WorkLog
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
	public $project;
	
	///////////////////////////
	public $parenttasks;  // Will hold the parent task objects of type Task

	
	//public $dayspent;
	//public $updated;
}
function DisplayName($user)
{
	global $users;
	if(array_key_exists ($user , $users ))
		return $users[$user];
	else
		return $user;
}

function HowOld($date)
{
	$date1 = new DateTime("now");
	
	$date2 = new DateTime($date);
	$interval = date_diff($date1, $date2);
	return $interval->format('%R%a');					
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
		$value = $value/(8*60*60);
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

function Check($url){
       $agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";$ch=curl_init();
       curl_setopt ($ch, CURLOPT_URL,$url );
       curl_setopt($ch, CURLOPT_USERAGENT, $agent);
       curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt ($ch,CURLOPT_VERBOSE,false);
       curl_setopt($ch, CURLOPT_TIMEOUT, 5);
       curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
       curl_setopt($ch,CURLOPT_SSLVERSION,3);
       curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
       $page=curl_exec($ch);
       //echo curl_error($ch);
       $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       curl_close($ch);
       if($httpcode>=200 && $httpcode<303) return true;
	   
       else return false;
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
     
    foreach ($date_labels as $key => $value) 
	{
		$value = $value/(8*60*60);
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
		//echo "->".$component->name.EOL;
		
		$value = $component->recent_worklog_acc/(8*60*60); 
		//echo $component->recent_worklog_acc.EOL;
        $dataSet->addPoint(new Point($component->name.'('.(string)truncate($value,1).' Days)', round($value)));
    }    
    $chart->setDataSet($dataSet);
    $chart->setTitle("Recent Projects ");
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
