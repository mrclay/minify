<?php

namespace Minify\Test;

use HTTP_ConditionalGet;

/**
 * @internal
 */
final class HTTPConditionalGetTest extends TestCase
{
    public function TestData()
    {
        /*
         * NOTE: calculate $lmTime as parameter
         * because dataProviders are executed before tests run,
         * and in fact each test is new instance of the class, so can't share data
         * other than parameters.
         */

        $lmTime = \time() - 900;
        $gmtTime = \gmdate('D, d M Y H:i:s \G\M\T', $lmTime);

        return array(
            array(
                'lm'   => $lmTime,
                'desc' => 'client has valid If-Modified-Since',
                'inm'  => null,
                'ims'  => $gmtTime,
                'exp'  => array(
                    'Vary'          => 'Accept-Encoding',
                    'Last-Modified' => $gmtTime,
                    'ETag'          => "\"pri{$lmTime}\"",
                    'Cache-Control' => 'max-age=0, private',
                    '_responseCode' => 'HTTP/1.0 304 Not Modified',
                    'isValid'       => true,
                ),
            ),
            array(
                'lm'   => $lmTime,
                'desc' => 'client has valid If-Modified-Since with trailing semicolon',
                'inm'  => null,
                'ims'  => $gmtTime . ';',
                'exp'  => array(
                    'Vary'          => 'Accept-Encoding',
                    'Last-Modified' => $gmtTime,
                    'ETag'          => "\"pri{$lmTime}\"",
                    'Cache-Control' => 'max-age=0, private',
                    '_responseCode' => 'HTTP/1.0 304 Not Modified',
                    'isValid'       => true,
                ),
            ),
            array(
                'lm'   => $lmTime,
                'desc' => 'client has valid ETag (non-encoded version)',
                'inm'  => "\"badEtagFoo\", \"pri{$lmTime}\"",
                'ims'  => null,
                'exp'  => array(
                    'Vary'          => 'Accept-Encoding',
                    'Last-Modified' => $gmtTime,
                    'ETag'          => "\"pri{$lmTime}\"",
                    'Cache-Control' => 'max-age=0, private',
                    '_responseCode' => 'HTTP/1.0 304 Not Modified',
                    'isValid'       => true,
                ),
            ),
            array(
                'lm'   => $lmTime,
                'desc' => 'client has valid ETag (gzip version)',
                'inm'  => "\"badEtagFoo\", \"pri{$lmTime};gz\"",
                'ims'  => null,
                'exp'  => array(
                    'Vary'          => 'Accept-Encoding',
                    'Last-Modified' => $gmtTime,
                    'ETag'          => "\"pri{$lmTime};gz\"",
                    'Cache-Control' => 'max-age=0, private',
                    '_responseCode' => 'HTTP/1.0 304 Not Modified',
                    'isValid'       => true,
                ),
            ),
            array(
                'lm'   => $lmTime,
                'desc' => 'no conditional get',
                'inm'  => null,
                'ims'  => null,
                'exp'  => array(
                    'Vary'          => 'Accept-Encoding',
                    'Last-Modified' => $gmtTime,
                    'ETag'          => "\"pri{$lmTime};gz\"",
                    'Cache-Control' => 'max-age=0, private',
                    'isValid'       => false,
                ),
            ),
            array(
                'lm'   => $lmTime,
                'desc' => 'client has invalid ETag',
                'inm'  => '"pri' . ($lmTime - 300) . '"',
                'ims'  => null,
                'exp'  => array(
                    'Vary'          => 'Accept-Encoding',
                    'Last-Modified' => $gmtTime,
                    'ETag'          => "\"pri{$lmTime};gz\"",
                    'Cache-Control' => 'max-age=0, private',
                    'isValid'       => false,
                ),
            ),
            array(
                'lm'   => $lmTime,
                'desc' => 'client has invalid If-Modified-Since',
                'inm'  => null,
                'ims'  => \gmdate('D, d M Y H:i:s \G\M\T', $lmTime - 300),
                'exp'  => array(
                    'Vary'          => 'Accept-Encoding',
                    'Last-Modified' => $gmtTime,
                    'ETag'          => "\"pri{$lmTime};gz\"",
                    'Cache-Control' => 'max-age=0, private',
                    'isValid'       => false,
                ),
            ),
        );
    }

    /**
     * @dataProvider TestData
     *
     * @param mixed $lmTime
     * @param mixed $desc
     * @param mixed $inm
     * @param mixed $ims
     * @param mixed $exp
     */
    public function testHTTPConditionalGet($lmTime, $desc, $inm, $ims, $exp)
    {
        // setup env
        if ($inm === null) {
            unset($_SERVER['HTTP_IF_NONE_MATCH']);
        } else {
            $_SERVER['HTTP_IF_NONE_MATCH'] = \get_magic_quotes_gpc()
                ? \addslashes($inm) :
                $inm;
        }

        if ($ims === null) {
            unset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        } else {
            $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $ims;
        }

        $cg = new HTTP_ConditionalGet(array(
            'lastModifiedTime' => $lmTime,
            'encoding'         => 'x-gzip',
        ));
        $ret = $cg->getHeaders();
        $ret['isValid'] = $cg->cacheIsValid;

        static::assertSame($exp, $ret, $desc);
    }
}
