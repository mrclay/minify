<?php

class Minify_LessCssSource extends Minify_Source
{
    /**
     * @var Minify_CacheInterface
     */
    private $cache;

    /**
     * Parsed lessphp cache object
     *
     * @var array
     */
    private $parsed;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $spec, Minify_CacheInterface $cache)
    {
        parent::__construct($spec);

        $this->cache = $cache;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        $cache = $this->getCache();

        return $cache['compiled'];
    }

    /**
     * Get last modified of all parsed files
     *
     * @return int
     */
    public function getLastModified()
    {
        $cache = $this->getCache();

        return $cache['lastModified'];
    }

    /**
     * Get lessphp cache object
     *
     * @return array
     */
    private function getCache()
    {
        // cache for single run
        // so that getLastModified and getContent in single request do not add additional cache roundtrips (i.e memcache)
        if (isset($this->parsed)) {
            return $this->parsed;
        }

        // check from cache first
        $cache = null;
        $cacheId = $this->getCacheId();
        if ($this->cache->isValid($cacheId, 0)) {
            $cache = $this->cache->fetch($cacheId);
            if ($cache) {
                $cache = \unserialize($cache);
            }
        }

        $less = $this->getCompiler();
        $input = $cache ?: $this->filepath;
        $cache = $less->cachedCompile($input);

        if (!\is_array($input) || $cache['updated'] > $input['updated']) {
            $cache['lastModified'] = $this->getMaxLastModified($cache);
            $this->cache->store($cacheId, \serialize($cache));
        }

        return $this->parsed = $cache;
    }

    /**
     * Make a unique cache id for for this source.
     *
     * @param string $prefix
     *
     * @return string
     */
    private function getCacheId($prefix = 'minify')
    {
        $md5 = \md5($this->filepath);

        return "{$prefix}_less2_{$md5}";
    }

    /**
     * Get instance of less compiler
     *
     * @return lessc
     */
    private function getCompiler()
    {
        $less = new lessc();
        // do not spend CPU time letting less doing minify
        $less->setPreserveComments(true);

        return $less;
    }

    /**
     * Calculate maximum last modified of all files,
     * as the 'updated' timestamp in cache is not the same as file last modified timestamp:
     *
     * @see https://github.com/leafo/lessphp/blob/v0.4.0/lessc.inc.php#L1904
     *
     * @param array $cache
     *
     * @return int
     */
    private function getMaxLastModified($cache)
    {
        $lastModified = 0;
        foreach ($cache['files'] as $mtime) {
            $lastModified = \max($lastModified, $mtime);
        }

        return $lastModified;
    }
}
