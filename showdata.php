<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	 <title>Gantt Chart</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=IE8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <link href="css/style.css" type="text/css" rel="stylesheet">
        <link href="css/prettify.min.css" rel="stylesheet" type="text/css">
        <style type="text/css">.fn-gantt *, .fn-gantt *:after,.fn-gantt *:before {-webkit-box-sizing: content-box;-moz-box-sizing: content-box;box-sizing: content-box;}
        </style>
	</head>
    <body> 
	
<?php
require_once('common.php');
require_once('db-interface.php');

echo $_component;
echo $_date;
return;
$projects = ReadDataBase();
foreach($projects as $comp)
{
	if($comp->name == $_component)
	{
		foreach($comp->parenttasks as $ptask)
		{
			foreach($ptask->worklogs as $worklog)
			{
				if($worklog->started >= $_date)
					if(strlen($worklog->comment)>0)
						echo $worklog->started." ".
					$worklog->comment.EOL;
			}
			foreach($ptask->subtasks as $stask)
			{
				foreach($stask->worklogs as $worklog)
				{
					if($worklog->started >= $_date)
						if(strlen($worklog->comment)>0)
							echo $worklog->comment.EOL;
				
				}
				
			}
			
		}
	}
}


?>

</body>
</html>
