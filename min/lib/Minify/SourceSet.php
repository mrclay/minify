<?php
/**
 * Class Minify_SourceSet
 * @package Minify
 */

/**
 * @package Minify
 */
class Minify_SourceSet {

    /**
     * Get unique string for a set of sources
     *
     * @param Minify_SourceInterface[] $sources Minify_Source instances
     *
     * @return string
     */
    public static function getDigest($sources)
    {
        $info = array();
        foreach ($sources as $source) {
            $info[] = array(
                $source->getId(), $source->getMinifier(), $source->getMinifierOptions()
            );
        }
        return md5(serialize($info));
    }

    /**
     * Get content type from a group of sources
     *
     * This is called if the user doesn't pass in a 'contentType' options
     *
     * @param Minify_SourceInterface[] $sources Minify_Source instances
     *
     * @return string content type. e.g. 'text/css'
     */
    public static function getContentType($sources)
    {
        foreach ($sources as $source) {
            $contentType = $source->getContentType();
            if ($contentType) {
                return $contentType;
            }
        }
        return 'text/plain';
    }
}
