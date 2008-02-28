<?php

/**
 * Implement conditional GET via a timestamp or hash of content
 *
 * <code>
 * // easiest usage
 * $cg = new HTTP_ConditionalGet(array(
 *     'lastModifiedTime' => filemtime(__FILE__)
 * ));
 * $cg->sendHeaders();
 * if ($cg->cacheIsValid) {
 *     exit(); // done
 * }
 * // echo content
 * </code>
 *
 *
 * <code>
 * // better to add content length once it's known
 * $cg = new HTTP_ConditionalGet(array(
 *     'lastModifiedTime' => filemtime(__FILE__)
 * ));
 * if ($cg->cacheIsValid) {
 *     $cg->sendHeaders();
 *     exit();
 * }
 * $content = get_content();
 * $cg->setContentLength(strlen($content));
 * $cg->sendHeaders();
 * </code>
 */
class HTTP_ConditionalGet {

    private $headers = array();
    private $lmTime = null;
    private $etag = null;
    public $cacheIsValid = null;

    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Depending on the PHP config, PHP will buffer all output and set
     * Content-Length for you. If it doesn't, or you flush() while sending data,
     * you'll want to call this to let the client know up front.
     */
    public function setContentLength($bytes) {
        return $this->headers['Content-Length'] = $bytes;
    }

    public function sendHeaders() {
        $headers = $this->headers;
        if (array_key_exists('_responseCode', $headers)) {
            header($headers['_responseCode']);
            unset($headers['_responseCode']);
        }
        foreach ($headers as $name => $val) {
            header($name . ': ' . $val);
        }
    }

    private function setEtag($hash, $scope) {
        $this->etag = '"' . $hash
            . substr($scope, 0, 3)
            . '"';
        $this->headers['ETag'] = $this->etag;
    }

    private function setLastModified($time) {
        $this->lmTime = (int)$time;
        $this->headers['Last-Modified'] = self::gmtdate($time);
    }

    // TODO: allow custom Cache-Control directives, but offer pre-configured
    // "modes" for common cache models
    public function __construct($spec) {
        $scope = (isset($spec['isPublic']) && $spec['isPublic'])
            ? 'public'
            : 'private';
        // allow far-expires header
        if (isset($spec['cacheUntil'])) {
            if (is_numeric($spec['cacheUntil'])) {
                $spec['cacheUntil'] = self::gmtdate($spec['cacheUntil']); 
            }
            $this->headers = array(
                'Cache-Control' => $scope
                ,'Expires' => $spec['cacheUntil']
            );
            $this->cacheIsValid = false;
            return;
        }
        if (isset($spec['lastModifiedTime'])) {
            // base both headers on time
            $this->setLastModified($spec['lastModifiedTime']);
            $this->setEtag($spec['lastModifiedTime'], $scope);
        } else {
            // hope to use ETag
            if (isset($spec['contentHash'])) {
                $this->setEtag($spec['contentHash'], $scope);
            }
        }
        $this->headers['Cache-Control'] = "max-age=0, {$scope}, must-revalidate";
        // invalidate cache if disabled, otherwise check
        $this->cacheIsValid = (isset($spec['invalidate']) && $spec['invalidate'])
            ? false
            : $this->isCacheValid();
    }

    /**
     * Determine validity of client cache and queue 304 header if valid
     */
    private function isCacheValid()
    {
        if (null === $this->etag) {
            // ETag was our backup, so we know we don't have lmTime either
            return false;
        }
        $isValid = ($this->resourceMatchedEtag() || $this->resourceNotModified());
        if ($isValid) {
            // overwrite headers, only need 304
            $this->headers = array(
                '_responseCode' => 'HTTP/1.0 304 Not Modified'
            );
        }
        return $isValid;
    }

    private function resourceMatchedEtag() {
        if (!isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return false;
        }
        $cachedEtagList = get_magic_quotes_gpc()
            ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])
            : $_SERVER['HTTP_IF_NONE_MATCH'];
        $cachedEtags = split(',', $cachedEtagList);
        foreach ($cachedEtags as $cachedEtag) {
            if (trim($cachedEtag) == $this->etag) {
                return true;
            }
        }
        return false;
    }

    private function resourceNotModified() {
        if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            return false;
        }
        $ifModifiedSince = get_magic_quotes_gpc()
            ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            : $_SERVER['HTTP_IF_MODIFIED_SINCE'];
        if (false !== ($semicolon = strrpos($ifModifiedSince, ';'))) {
            // IE has tacked on extra data to this header, strip it
            $ifModifiedSince = substr($ifModifiedSince, 0, $semicolon);
        }
        return ($ifModifiedSince == self::gmtdate($this->lmTime));
    }

    private static function gmtdate($ts) {
        return gmdate('D, d M Y H:i:s \G\M\T', $ts);
    }
}

