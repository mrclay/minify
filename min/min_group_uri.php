<?php
/**
 * Function min_group_uri()
 * 
 * @package Minify
 */

require_once dirname(__FILE__) . '/lib/Minify/Build.php';

/**
 * Get a timestamped URI to a minified resource using the default Minify install
 *
 * <code>
 * <link rel="stylesheet" type="text/css" href="<?php min_group_uri('css'); ?>" />
 * <script type="text/javascript" src="<?php min_group_uri('js'); ?>"></script>
 * </code>
 *
 * @param string $group a key of the array in groupsConfig.php
 * @param string $ampersand '&' or '&amp;' (default '&amp;')
 * @return string
 */ 
function min_group_uri($group, $ampersand = '&amp;')
{
    static $gc = false;
    if (false === $gc) {
        $gc = (require dirname(__FILE__) . '/groupsConfig.php');
    }
    $b = new Minify_Build($gc[$group]);
    Minify_Build::$ampersand = $ampersand;
    return $b->uri('/' . basename(dirname(__FILE__)) . '/?g=' . $group);
}
