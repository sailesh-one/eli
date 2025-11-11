<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';
if( !empty($_GET['doc']) )
{
	$filename = $_GET['doc'];
	echo $filename;
	$uniqid = uniqid();
	$tmpdir = sys_get_temp_dir().'/';
	$file = new Files();
	$res = $file->downloadFiles("docs",$filename,$tmpdir.$uniqid);
	$content_type = $res['data']['headers']['content-type'];

	ob_clean();
	header("Content-Type: {$content_type}");
	header("Content-Disposition: $Dwntype; filename=".pathinfo($filename)['basename']."");
	readfile($tmpdir.$uniqid);
}
else{
    http_response_code(400);
    echo json_encode(['status'=>'fail', 'msg' => 'Invalid document']);
    exit;
}
exit;
?>
