<?php
use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Server;
use Leafo\ScssPhp\Version;

/**
 * Class for using SCSS files
 *
 * @link https://github.com/leafo/scssphp/
 */
class Minify_ScssCssSource extends Minify_Source
{
    /**
     * @var Minify_CacheInterface
     */
    private $cache;

    /**
     * Parsed SCSS cache object
     *
     * @var array
     */
    private $parsed;

    /**
     * @inheritdoc
     */
    public function __construct(array $spec, Minify_CacheInterface $cache)
    {
        parent::__construct($spec);

        $this->cache = $cache;
    }

    /**
     * Get last modified of all parsed files
     *
     * @return int
     */
    public function getLastModified()
    {
        $cache = $this->getCache();

        return $cache['updated'];
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        $cache = $this->getCache();

        return $cache['content'];
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
        $md5 = md5($this->filepath);

        return "{$prefix}_scss_{$md5}";
    }

    /**
     * Get SCSS cache object
     *
     * Runs the compilation if needed
     *
     * Implements Leafo\ScssPhp\Server logic because we need to get parsed files without parsing actual content
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
            if ($cache = $this->cache->fetch($cacheId)) {
                $cache = unserialize($cache);
            }
        }

        $input = $cache ? $cache : $this->filepath;

        if ($this->cacheIsStale($cache)) {
            $cache = $this->compile($this->filepath);
        }

        if (!is_array($input) || $cache['updated'] > $input['updated']) {
            $this->cache->store($cacheId, serialize($cache));
        }

        return $this->parsed = $cache;
    }

    /**
     * Determine whether .scss file needs to be re-compiled.
     *
     * @param array $cache Cache object
     *
     * @return boolean True if compile required.
     */
    private function cacheIsStale($cache)
    {
        if (!$cache) {
            return true;
        }

        $updated = $cache['updated'];
        foreach ($cache['files'] as $import => $mtime) {
            $filemtime = filemtime($import);

            if ($filemtime !== $mtime || $filemtime > $updated) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compile .scss file
     *
     * @param string $filename Input path (.scss)
     *
     * @see Server::compile()
     * @return array meta data result of the compile
     */
    private function compile($filename)
    {
        $start = microtime(true);
        $scss = new Compiler();

        // set import path directory the input filename resides
        // otherwise @import statements will not find the files
        // and will treat the @import line as css import
        $scss->setImportPaths(dirname($filename));

        $css = $scss->compile(file_get_contents($filename), $filename);
        $elapsed = round((microtime(true) - $start), 4);

        $v = Version::VERSION;
        $ts = date('r', $start);
        $css = "/* compiled by scssphp $v on $ts (${elapsed}s) */\n\n" . $css;

        $imports = $scss->getParsedFiles();

        $updated = 0;
        foreach ($imports as $mtime) {
            $updated = max($updated, $mtime);
        }

        return array(
            'elapsed' => $elapsed, // statistic, can be dropped
            'updated' => $updated,
            'content' => $css,
            'files' => $imports,
        );
    }
}
