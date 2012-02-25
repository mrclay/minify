<?php 

namespace MrClay;

/**
 * Front controller for a command line app, handling and validating arguments
 */
class Cli {
    
    /**
     * @var array validation errors
     */
    public $errors = array();
    
    /**
     * @var array option values available after validation.
     * 
     * E.g. array(
     *      'a' => false              // option was missing
     *     ,'b' => true               // option was present
     *     ,'c' => "Hello"            // option had value
     *     ,'f' => "/home/user/file"  // file path from root
     *     ,'f.raw' => "~/file"       // file path as given to option
     * )
     */
    public $values = array();

    /**
     * @var array
     */
    public $moreArgs = array();

    /**
     * @var array
     */
    public $debug = array();

    /**
     * @var array of Cli\Arg
     */
    protected $_args = array();

    protected $_stdin = null;

    protected $_stdout = null;
    
    /**
     * @param bool $exitIfNoStdin (default true) Exit() if STDIN is not defined
     */
    public function __construct($exitIfNoStdin = true)
    {
        if ($exitIfNoStdin && ! defined('STDIN')) {
            exit('This script is for command-line use only.');
        }
    }

    /**
     * @param Cli\Arg|string $letter
     * @return Cli\Arg
     */
    public function addOptionalArg($letter)
    {
        return $this->addArgument($letter, false);
    }

    /**
     * @param Cli\Arg|string $letter
     * @return Cli\Arg
     */
    public function addRequiredArg($letter)
    {
        return $this->addArgument($letter, true);
    }

    /**
     * @param Cli\Arg|string $letter
     * @param bool $required
     * @return Cli\Arg
     */
    public function addArgument($letter, $required)
    {
        if (! $letter instanceof Cli\Arg) {
            if (is_string($letter)) {
                $letter = new Cli\Arg($letter, $required);
            } else {
                throw new \InvalidArgumentException('Must be letter or MrClay\\Cli\\Arg instance');
            }
        }
        $this->_args[$letter->getLetter()] = $letter;
        return $letter;
    }
    
    /*
     * Read and validate options
     * 
     * @return bool true if all options are valid
     */
    public function validate()
    {
        $options = '';
        $this->errors = array();
        $this->values = array();
        $this->_stdin = null;
        
        if (isset($GLOBALS['argv'][1]) 
            && ($GLOBALS['argv'][1] === '-?'
                || $GLOBALS['argv'][1] === '--help'
                )) {
            return false;
        }
        
        $lettersUsed = '';
        foreach ($this->_args as $arg) {
            /* @var Cli\Arg $arg  */
            $letter = $arg->getLetter();
            $options .= $letter;
            $lettersUsed .= $letter;
            
            if ($arg->mayHaveValue || $arg->mustHaveValue) {
                $options .= ($arg->mustHaveValue ? ':' : '::');
            }
        }

        $this->debug['argv'] = $GLOBALS['argv'];
        $argvCopy = array_slice($GLOBALS['argv'], 1);
        $o = getopt($options);
        $this->debug['getopt_options'] = $options;
        $this->debug['getopt_return'] = $o;

        foreach ($this->_args as $arg) {
            /* @var Cli\Arg $arg  */
            $letter = $arg->getLetter();
            $this->values[$letter] = false;
            if (isset($o[$letter])) {
                if (is_bool($o[$letter])) {

                    // remove from argv copy
                    $k = array_search("-$letter", $argvCopy);
                    if ($k !== false) {
                        array_splice($argvCopy, $k, 1);
                    }

                    if ($arg->mustHaveValue) {
                        $this->addError($letter, "Missing value");
                    } else {
                        $this->values[$letter] = true;
                    }
                } else {
                    // string
                    $this->values[$letter] = $o[$letter];
                    $v =& $this->values[$letter];

                    // remove from argv copy
                    // first look for -ovalue or -o=value
                    $pattern = "/^-{$letter}=?" . preg_quote($v, '/') . "$/";
                    $foundInArgv = false;
                    foreach ($argvCopy as $k => $argV) {
                        if (preg_match($pattern, $argV)) {
                            array_splice($argvCopy, $k, 1);
                            $foundInArgv = true;
                            break;
                        }
                    }
                    if (! $foundInArgv) {
                        // space separated
                        $k = array_search("-$letter", $argvCopy);
                        if ($k !== false) {
                            array_splice($argvCopy, $k, 2);
                        }
                    }
                    
                    // check that value isn't really another option
                    if (strlen($lettersUsed) > 1) {
                        $pattern = "/^-[" . str_replace($letter, '', $lettersUsed) . "]/i";
                        if (preg_match($pattern, $v)) {
                            $this->addError($letter, "Value was read as another option: %s", $v);
                            return false;
                        }    
                    }
                    if ($arg->assertFile || $arg->assertDir) {
                        if ($v[0] !== '/' && $v[0] !== '~') {
                            $this->values["$letter.raw"] = $v;
                            $v = getcwd() . "/$v";
                        }
                    }
                    if ($arg->assertFile) {
                        if ($arg->useAsInfile) {
                            $this->_stdin = $v;
                        } elseif ($arg->useAsOutfile) {
                            $this->_stdout = $v;
                        }
                        if ($arg->assertReadable && ! is_readable($v)) {
                            $this->addError($letter, "File not readable: %s", $v);
                            continue;
                        }
                        if ($arg->assertWritable) {
                            if (is_file($v)) {
                                if (! is_writable($v)) {
                                    $this->addError($letter, "File not writable: %s", $v);
                                }
                            } else {
                                if (! is_writable(dirname($v))) {
                                    $this->addError($letter, "Directory not writable: %s", dirname($v));
                                }
                            }
                        }
                    } elseif ($arg->assertDir && $arg->assertWritable && ! is_writable($v)) {
                        $this->addError($letter, "Directory not readable: %s", $v);
                    }
                }
            } else {
                if ($arg->isRequired) {
                    $this->addError($letter, "Missing");
                }
            }
        }
        $this->moreArgs = $argvCopy;
        reset($this->moreArgs);
        return empty($this->errors);
    }

    /**
     * Get the full paths of file(s) passed in as unspecified arguments
     *
     * @return array
     */
    public function getPathArgs()
    {
        $r = $this->moreArgs;
        foreach ($r as $k => $v) {
            if ($v[0] !== '/' && $v[0] !== '~') {
                $v = getcwd() . "/$v";
                $v = str_replace('/./', '/', $v);
                do {
                    $v = preg_replace('@/[^/]+/\\.\\./@', '/', $v, 1, $changed);
                } while ($changed);
                $r[$k] = $v;
            }
        }
        return $r;
    }
    
    /**
     * Get a short list of errors with options
     * 
     * @return string
     */
    public function getErrorReport()
    {
        if (empty($this->errors)) {
            return '';
        }
        $r = "Problems with your options:\n";
        foreach ($this->errors as $letter => $arr) {
            $r .= "  $letter : " . implode(', ', $arr) . "\n";
        }
        $r .= "\n";
        return $r;
    }
    
    /**
     * Get resource of open input stream. May be STDIN or a file pointer
     * to the file specified by an option with 'STDIN'.
     *
     * @return resource
     */
    public function openInput()
    {
        if (null === $this->_stdin) {
            return STDIN;
        } else {
            $this->_stdin = fopen($this->_stdin, 'rb');
            return $this->_stdin;
        }
    }
    
    public function closeInput()
    {
        if (null !== $this->_stdin) {
            fclose($this->_stdin);
        }
    }
    
    /**
     * Get resource of open output stream. May be STDOUT or a file pointer
     * to the file specified by an option with 'STDOUT'. The file will be
     * truncated to 0 bytes on opening.
     *
     * @return resource
     */
    public function openOutput()
    {
        if (null === $this->_stdout) {
            return STDOUT;
        } else {
            $this->_stdout = fopen($this->_stdout, 'wb');
            return $this->_stdout;
        }
    }
    
    public function closeOutput()
    {
        if (null !== $this->_stdout) {
            fclose($this->_stdout);
        }
    }

    /**
     * @param string $letter
     * @param string $msg
     * @param string $value
     */
    protected function addError($letter, $msg, $value = null)
    {
        if ($value !== null) {
            $value = var_export($value, 1);
        }
        $this->errors[$letter][] = sprintf($msg, $value);
    }
}

