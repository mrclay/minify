<?php

require_once dirname(__FILE__) . '/String.php';

class Minify_YUI_Java_Matcher {

    protected $_matches;
    protected $_match;
    protected $_subject;
    protected $_appendPosition = 0;

    public function __construct($pattern, $subject)
    {
        $this->_subject = $subject;
        preg_match_all($pattern, $subject, $this->_matches, PREG_OFFSET_CAPTURE);
    }

    /**
     *
     * @return bool
     */
    public function find()
    {
        $this->_match = current($this->_matches);
        if ($this->_match) {
            next($this->_matches);
            return true;
        }
        return false;
    }

    public function group($group = 0)
    {
        return $this->_match[0][$group];
    }

    public function start()
    {
        return $this->_match[1];
    }

    public function end()
    {
        return $this->_match[1] + strlen($this->_match[0][0]);
    }

    public function appendReplacement(Minify_YUI_Java_String $string, $replacement)
    {
        $length = $this->start() - $this->_appendPosition;
        $string->append(substr($this->_subject, $this->_appendPosition, $length));

        $i = 0;
        $newReplacement = '';
        $next = '';
        $length = strlen($replacement);
        while ($i < $length) {
            $curr = $replacement[$i];
            $next = ($i === ($length - 1)) ? '' : $replacement[$i + 1];
            if ($curr === '\\' && $next === '$') {
                $newReplacement .= '$';
                $i += 2;
                continue;
            }
            if ($curr === '$' && is_numeric($next) && isset($this->_match[0][(int) $next])) {
                $newReplacement .= $this->_match[0][(int) $next];
                $i += 2;
                continue;
            }
            $newReplacement .= $curr;
            $i++;
        }

        $string->append($newReplacement);
        $this->_appendPosition = $this->end();
    }

    public function appendTail(Minify_YUI_Java_String $string)
    {
        $string->append(substr($this->_subject, $this->_appendPosition));
    }
}