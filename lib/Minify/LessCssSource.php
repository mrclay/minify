<?php

class Minify_LessCssSource extends Minify_Source {
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
     * @inheritdoc
     */
    public function __construct(array $spec, Minify_CacheInterface $cache) {
        parent::__construct($spec);

        $this->cache = $cache;
    }

    /**
     * Get last modified of all parsed files
     *
     * @return int
     */
    public function getLastModified() {
        $cache = $this->getCache();

        $lastModified = 0;
        foreach ($cache['files'] as $mtime) {
            $lastModified = max($lastModified, $mtime);

        }
        return $lastModified;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent() {
        $cache = $this->getCache();

        return $cache['compiled'];
    }

    /**
     * Get lessphp cache object
     *
     * @return array
     */
    private function getCache() {
        // cache for single run
        // so that getLastModified and getContent in single request do not add additional cache roundtrips (i.e memcache)
        if (isset($this->parsed)) {
            return $this->parsed;
        }

        // check from cache first
        $cache = null;
        $cacheId = $this->getCacheId();
        if ($this->cache->isValid($cacheId, 0)) {
            if ($cache = $this->cache->fetch($cacheId)) {
                $cache = unserialize($cache);
            }
        }

        $less = $this->getCompiler();
        $input = $cache ? $cache : $this->filepath;
        $cache = $less->cachedCompile($input);

        if (!is_array($input) || $cache['updated'] > $input['updated']) {
            $this->cache->store($cacheId, serialize($cache));
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
    private function getCacheId($prefix = 'minify') {
        $md5 = md5($this->filepath);
        return "{$prefix}_less_{$md5}";
    }

    /**
     * Get instance of less compiler
     *
     * @return lessc
     */
    private function getCompiler() {
        $less = new lessc();
        // do not spend CPU time letting less doing minify
        $less->setPreserveComments(true);
        return $less;
    }
}
