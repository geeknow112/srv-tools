<?php
$gdata = __DIR__. '/../../gdata.php';
//echo $gdata. PHP_EOL;exit;
require($gdata);

//echo 'test';exit;
if (!$argv[1] || !$argv[2]) { echo 'arg error.'. PHP_EOL;exit; }

//$todo_no = "srv-tools#101";
//$todo_no = "todo#1963";
$todo_no = $argv[1];
date_default_timezone_set('Asia/Tokyo');
$dt = date('Ymd');

//$migrate = "migration20250706003";
$next_no = get_next_no($dt);
$migrate = sprintf("migration%s%03d", $dt, $next_no);
//var_dump($migrate);exit;
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
 * set_next_no
 **/
function set_next_no($no = null) {
	$count_file = __DIR__. '/../migrations/count.txt';
	$next_no = $no + 1;
//var_dump($next_no);exit;
	file_put_contents($count_file, $next_no);
/*
	$files_dir = __DIR__. '/../migrations/';
	$files_name = sprintf('%smigration%s*.*', $files_dir, $dt);
	$files = glob($files_name);
	$next_no = count($files) + 1;
	return $next_no;
*/
}

/**
 * get_next_no
 **/
function get_next_no() {
	$count_file = __DIR__. '/../migrations/count.txt';
	$count = file_get_contents($count_file);
	$no = intval(trim($count));
	return $no;
}

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
if (!empty($argv[2])) {
	switch ($argv[2]) {
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
			set_next_no($next_no);
			break;
		default:
			echo 'Please set argv...'. PHP_EOL;
			break;
	}
} else {
	echo 'Please set argv...'. PHP_EOL;
}

