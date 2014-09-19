<?php

class LessCss_Source extends Minify_Source {

    /**
     * Get last modified of all parsed files
     *
     * @return int|mixed
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
        $less = $this->getCompiler();
        return $less->cachedCompile($this->filepath);
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