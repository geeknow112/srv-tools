<?php
//echo 'test';exit;
$todo_no = "srv-tools#86";
$migrate = "migration20250705008";
$mfile = $migrate. ".go";

/**
 * cmd_exec
 **/
function cmd_exec($cmds = null) {
	for ($i=0; $i<count($cmds); $i++) {
		echo $cmds[$i]. PHP_EOL;
		exec($cmds[$i]);
		sleep(1);
	}
}
