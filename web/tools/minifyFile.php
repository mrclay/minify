<?php

if (isset($_FILES['subject']['name'])
    && preg_match('/\\.(js|css|html)$/', $_FILES['subject']['name'], $m)
) {
    ini_set('include_path', 
        dirname(__FILE__) . '/../../min/lib'
        . PATH_SEPARATOR . ini_get('include_path')
    );

    // eh why not
    require 'Minify/HTML.php';
    require 'Minify/CSS.php';
    require 'Minify/Javascript.php';
    
    $arg2 = null;
    switch ($m[1]) {
    case 'js':
        $type = 'Javascript';
        break;
    case 'css':
        $type = 'CSS';
        break;
    case 'html':
        $type = 'HTML';
        $arg2 = array(
            'cssMinifier' => array('Minify_CSS', 'minify')
            ,'jsMinifier' => array('Minify_Javascript', 'minify')
        );
    }
    $func = array('Minify_' . $type, 'minify');

    $out = call_user_func($func, file_get_contents($_FILES['subject']['tmp_name']), $arg2);
    
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="'
        . preg_replace('/\\.(\w+)$/', '.min.$1', $_FILES['subject']['name'])   
        . '"');
    
    echo $out;    
    exit();
}

?>
<form enctype="multipart/form-data" action="" method="post">
<p>Minify <input type="file" name="subject" /><br />
<input type="submit" name="method" value="Go!" />
</p>
</form>