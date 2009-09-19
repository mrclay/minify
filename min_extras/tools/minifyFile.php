<?php

if (isset($_FILES['subject']['name'])
    && preg_match('/\\.(js|css|x?html?)$/', $_FILES['subject']['name'], $m)
) {
    require '../config.php';
    
    $arg2 = null;
    switch ($m[1]) {
    case 'js':
        $type = 'Javascript';
        break;
    case 'css':
        $type = 'CSS';
        break;
    case 'html': // fallthrough
    case 'htm': // fallthrough
    case 'xhtml':
        $type = 'HTML';
        $arg2 = array(
            'cssMinifier' => array('Minify_CSS', 'minify')
            ,'jsMinifier' => array('JSMin', 'minify')
        );
    }
    $func = array('Minify_' . $type, 'minify');

    $out = call_user_func($func, file_get_contents($_FILES['subject']['tmp_name']), $arg2);
    
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="'
        . preg_replace('/\\.(\w+)$/', '.min.$1', $_FILES['subject']['name'])   
        . '"');
    
    //@unlink($_FILES['subject']['tmp_name']);
    echo $out;    
    exit();
}

?>
<form enctype="multipart/form-data" action="" method="post">
<p>Minify <input type="file" name="subject" /><br />
<input type="submit" name="method" value="Go!" />
</p>
</form>