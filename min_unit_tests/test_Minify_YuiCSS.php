<?php
require_once '_inc.php';

function test_Minify_YuiCSS()
{
    // XXX this may need customizing
    Minify_YUICompressor::$jarFile = '/usr/share/java/yuicompressor.jar';
    Minify_YUICompressor::$tempDir = sys_get_temp_dir();

    $src = "/* stack overflow test */
    div.image {
      width:            100px;
      height:           100px;
      background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAALCAYAAABGbhwYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyRpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoTWFjaW50b3NoKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo1RDg4RDYwQzU2N0ExMUUyOUNCMEY5NzdDNzlGNzg3MSIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo1RDg4RDYwRDU2N0ExMUUyOUNCMEY5NzdDNzlGNzg3MSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkM0RjRBMkZGNTY3NzExRTI5Q0IwRjk3N0M3OUY3ODcxIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOkM0RjRBMzAwNTY3NzExRTI5Q0IwRjk3N0M3OUY3ODcxIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+2Di36AAAALBJREFUeNpi+P//PwMQ60NpdMwIxAYgNogTBsQHgNgGi0IVIN4CxH4whSDwAk2xLBCvh8odhgmuRlNsCMSboWLHgDgE2Zp5SIrXQNlXgNgI5kZkh9+AKvgGpZWAmAUkz8SAADxAfAfK5oTS7ED8B8yCmqYOxOuA+AcQXwDiJVATn8I8CFIUCA0CmMNNoZqXILnZHiQQjeRwU7RwhCk+xAB17A4gdgFiNiyBDlKcBBBgAG/qVav+VuC1AAAAAElFTkSuQmCC');
    }
    ";
    $minExpected = "div.image{width:100px;height:100px;background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAALCAYAAABGbhwYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyRpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoTWFjaW50b3NoKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo1RDg4RDYwQzU2N0ExMUUyOUNCMEY5NzdDNzlGNzg3MSIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo1RDg4RDYwRDU2N0ExMUUyOUNCMEY5NzdDNzlGNzg3MSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkM0RjRBMkZGNTY3NzExRTI5Q0IwRjk3N0M3OUY3ODcxIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOkM0RjRBMzAwNTY3NzExRTI5Q0IwRjk3N0M3OUY3ODcxIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+2Di36AAAALBJREFUeNpi+P//PwMQ60NpdMwIxAYgNogTBsQHgNgGi0IVIN4CxH4whSDwAk2xLBCvh8odhgmuRlNsCMSboWLHgDgE2Zp5SIrXQNlXgNgI5kZkh9+AKvgGpZWAmAUkz8SAADxAfAfK5oTS7ED8B8yCmqYOxOuA+AcQXwDiJVATn8I8CFIUCA0CmMNNoZqXILnZHiQQjeRwU7RwhCk+xAB17A4gdgFiNiyBDlKcBBBgAG/qVav+VuC1AAAAAElFTkSuQmCC')}";

    // fails with java.lang.StackOverflowError as of Yui 2.4.6
    // unfortunately error output is not caught from yui, so have to guess
    try {
        $minOutput = Minify_YUICompressor::minifyCss($src);
        assertTrue(false, "Expected exception Not thrown");
    } catch (Exception $e) {
        assertTrue($e->getMessage() == 'Minify_YUICompressor : YUI compressor execution failed.', 'got expected Exception');
    }

    try {
        $options = array(
            'stack-size' => '2m',
        );
        $minOutput = Minify_YUICompressor::minifyCss($src, $options);
    } catch (Exception $e) {
        assertTrue(false, $e->getMessage());;
        $minOutput = false;
    }

    $passed = assertTrue($minExpected == $minOutput, 'Minify_YUICompressor : Overall');
    if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "\n---Output: " .countBytes($minOutput). " bytes\n\n{$minOutput}\n\n";
        echo "---Expected: " .countBytes($minExpected). " bytes\n\n{$minExpected}\n\n";
        echo "---Source: " .countBytes($src). " bytes\n\n{$src}\n\n\n";
    }
}

test_Minify_YuiCSS();
