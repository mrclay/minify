<?php

if (isset($_FILES['subject']['name'])) {

    require '../config.php';
    
    $he = new HTTP_Encoder(array(
        'content' => file_get_contents($_FILES['subject']['tmp_name'])
        ,'method' => $_POST['method']
    ));
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"{$_FILES['subject']['name']}." 
        . ($_POST['method'] == 'deflate'
        	? 'zd'
        	: ($_POST['method'] == 'gzip'
        		? 'zg'
        		: 'zc'
        	)
        ) . '"');
    $he->encode(9);
    echo $he->getContent();
    exit();
}

?>
<form enctype="multipart/form-data" action="" method="post">
<p>Encode <input type="file" name="subject" /><br />
as <input type="submit" name="method" value="deflate" />
<input type="submit" name="method" value="gzip" />
<input type="submit" name="method" value="compress" />
</p>
</form>