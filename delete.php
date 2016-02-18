<?php

require_once('jira-interface.php');
require_once('db-interface.php');

$projects = ReadDataBase();

if(isset($project))
{
	foreach($projects as $component)
	{
		if($component->name == $project)
		{
			echo "Deleting ".$project.EOL;
			Delete($project);
			return;
		}
	}
	echo "Project not found".EOL;
}
else
	echo "Usage:delete.php project=xyz";


?>
