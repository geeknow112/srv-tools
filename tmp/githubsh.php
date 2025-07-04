<?php
$gdata = __DIR__. '/gdata.php';
//echo $gdata. PHP_EOL;exit;
require($gdata);

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

var_dump($argv);
if (!empty($argv[1])) {
	switch ($argv[1]) {
		case 1:
			cmd_exec($cmd_1);
			break;
		case 2:
			cmd_exec($cmd_2);
			break;
		case 3:
			cmd_exec($cmd_3);
			break;
		case 4:
			cmd_exec($cmd_4);
			break;
		default:
			echo 'Please set argv...'. PHP_EOL;
			break;
	}
} else {
	echo 'Please set argv...'. PHP_EOL;
}

