<?php
$gdata = __DIR__. '/../../gdata.php';
//echo $gdata. PHP_EOL;exit;
require($gdata);

//echo 'test';exit;
$todo_no = "srv-tools#101";
//$todo_no = "todo#2001";
$migrate = "migration20250706002";
$mfile = $migrate. ".go";

$cmd_1 = get_cmd_1 ($migrate, $mfile, $todo_no);
$cmd_2 = get_cmd_2 ($migrate, $mfile, $todo_no);
$cmd_3 = get_cmd_3 ($migrate, $mfile, $todo_no);
$cmd_4 = get_cmd_4 ($migrate, $mfile, $todo_no);
/*
var_dump($cmd_1);
var_dump($cmd_2);
var_dump($cmd_3);
var_dump($cmd_4);
exit;
*/

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

