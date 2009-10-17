<?php

$tests = array(
    '$_SERVER["DOCUMENT_ROOT"]'    => $_SERVER["DOCUMENT_ROOT"]
    ,'__FILE__'                    => __FILE__
    ,'$_SERVER["SCRIPT_FILENAME"]' => $_SERVER["SCRIPT_FILENAME"]
);

function e($txt) {
    if (is_string($txt)) {
        return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
    } else {
        return '<em>' . htmlspecialchars(var_export($txt, true), ENT_QUOTES, 'UTF-8') . '</em>';
    }
}
function rp() {
    if (! isset($_POST['rp']) || ! is_string($_POST['rp'])) {
        return '';
    }
    return get_magic_quotes_gpc()
        ? stripslashes($_POST['rp'])
        : $_POST['rp'];
}

header('Content-Type: text/html; charset=utf-8');

?><!doctype html>
<head>
<title>Test of $_SERVER and realpath()</title>
<style>
h2, td {font:80% monospace}
h2 {margin:1em 0 0}
table {margin-left:2em}
th {text-align:right; padding-right:.5em}
</style>
</head>
<body>
<h1>Test of $_SERVER and realpath()</h1>

<?php foreach ($tests as $key => $value): ?>
<h2><?= e($key) ?> </h2>
<table>
  <tr><th>value</th><td><?= e($value) ?></td></tr>
  <tr><th>realpath(value)</th><td><?= e(realpath($value)) ?></td></tr>
</table>
<?php endForeach; ?>
<h2>$_SERVER['REQUEST_URI'] </h2>
<table>
  <tr><th>value</th><td><?= e($_SERVER['REQUEST_URI']) ?></td></tr>
</table>

<h3>Test realpath()</h3>


<form action="" method="post"><p>
 <label style="font:80% monospace">realpath(<input type="text" name="rp" size="80" value="<?= e(rp()) ?>">)<label> 
 <input type="submit" value="evaluate...">
 </p>
<?php if (rp() !== ''): ?>
<pre>= <?= e(realpath(rp())) ?></pre>
<?php endIf; ?>
</form>

</body>