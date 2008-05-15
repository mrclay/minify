<?php

require '../../config.php';
require 'HTTP/ConditionalGet.php';

// far expires
$cg = new HTTP_ConditionalGet(array(
    'setExpires' => (time() + 86400 * 365) // 1 yr
));
$cg->sendHeaders();

// generate, send content
$title = 'Expires date is known';
$explain = '
<p>Here we set "setExpires" to a timestamp or GMT date string. This results in
<code>$cacheIsValid</code> always being false, so content is always served, but
with an Expires header.
<p><strong>Note:</strong> This isn\'t a conditional GET, but is useful if you\'re
used to the HTTP_ConditionalGet workflow already.</p>
';

require '_include.php';
echo get_content(array(
    'title' => $title
    ,'explain' => $explain
));

?>