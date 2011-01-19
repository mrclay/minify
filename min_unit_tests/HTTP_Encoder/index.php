<?php
ini_set('display_errors', 'on');

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../min/lib'));
require 'HTTP/Encoder.php';

if (!isset($_GET['test'])) {
    $type = 'text/html';
    ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>HTTP_Encoder Test</title>
<style type="text/css">
@import "?test=2";
#img {background:url("?test=1");}
.green {background:#0f0;}
p span {padding:0 .5em;}
</style>
</head>
<body>
<h1>HTTP_Encoder test</h1>
<p><span class="green"> HTML </span></p>
<p><span id="css"> CSS </span></p>
<p><span id="js"> Javascript </span></p>
<p><span id="img"> image </span></p>
<script src="?test=3" type="text/javascript"></script>
</body>
</html>
<?php
    $content = ob_get_contents();
    ob_end_clean();

} elseif ($_GET['test'] == '1') {
    $content = file_get_contents(dirname(__FILE__) . '/green.png');
    $type = 'image/png';

} elseif ($_GET['test'] == '2') {
    $content = '#css {background:#0f0;}';
    $type = 'text/css';

} else {
    $content = '
window.onload = function(){
    document.getElementById("js").className = "green";
};
    ';
    $type = 'application/x-javascript';
}

$he = new HTTP_Encoder(array(
    'content' => $content
    ,'type' => $type
));
$he->encode();
$he->sendAll();

?>