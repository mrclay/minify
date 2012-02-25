<?php

namespace MrClay\Cli;

/**
 * An argument for a CLI app. This specifies the argument, what values it expects and
 * how it's treated during validation.
 *
 * By default, the argument will be assumed to be a letter flag with no value following.
 *
 * If the argument may receive a value, call mayHaveValue(). If there's whitespace after the
 * flag, the value will be returned as true instead of the string.
 *
 * If the argument is required, or mustHaveValue() is called, the value will be required and
 * whitespace is permitted between the flag and its value.
 *
 * If the argument AND string BOTH must be present for validation, call assertRequired().
 *
 * Use assertFile() or assertDir() to indicate that the argument must return a string value
 * specifying a file or directory. During validation, the value will be resolved to a
 * full file/dir path (not necessarily existing!) and the original value will be accessible
 * via a "*.raw" key. E.g. $cli->values['f.raw']
 *
 * Use assertReadable()/assertWritable() to cause the validator to test the file/dir for
 * read/write permissions respectively.
 *
 * @method \MrClay\Cli\Arg mayHaveValue() Assert that the argument, if present, may receive a string value
 * @method \MrClay\Cli\Arg mustHaveValue() Assert that the argument, if present, must receive a string value
 * @method \MrClay\Cli\Arg assertFile() Assert that the argument's value must specify a file
 * @method \MrClay\Cli\Arg assertDir() Assert that the argument's value must specify a directory
 * @method \MrClay\Cli\Arg assertReadable() Assert that the specified file/dir must be readable
 * @method \MrClay\Cli\Arg assertWritable() Assert that the specified file/dir must be writable
 *
 * @property-read bool isRequired
 * @property-read bool mayHaveValue
 * @property-read bool mustHaveValue
 * @property-read bool assertFile
 * @property-read bool assertDir
 * @property-read bool assertReadable
 * @property-read bool assertWritable
 * @property-read bool useAsInfile
 * @property-read bool useAsOutfile
 */
class Arg {
    /**
     * @return array
     */
    public function getDefaultSpec()
    {
        return array(
            'mayHaveValue' => false,
            'mustHaveValue' => false,
            'assertFile' => false,
            'assertDir' => false,
            'assertReadable' => false,
            'assertWritable' => false,
            'useAsInfile' => false,
            'useAsOutfile' => false,
        );
    }

    /**
     * @var string
     */
    protected $letter;

    /**
     * @var array
     */
    protected $spec = array();

    protected $required = false;

    /**
     * @param string $letter
     * @param bool $isRequired
     */
    public function __construct($letter, $isRequired = false)
    {
        if (! preg_match('/^[a-zA-Z]$/', $letter)) {
            throw new \InvalidArgumentException('$letter must be in [a-zA-z]');
        }
        $this->letter = $letter;
        $this->spec = $this->getDefaultSpec();
        $this->required = (bool) $isRequired;
        if ($isRequired) {
            $this->spec['mustHaveValue'] = true;
        }
    }

    /**
     * Assert that the argument's value points to a writable file. When
     * CliArgs::openOutput() is called, a write pointer to this file will
     * be provided.
     * @return Arg
     */
    public function useAsOutfile()
    {
        $this->spec['useAsOutfile'] = true;
        return $this->assertFile()->assertWritable();
    }

    /**
     * Assert that the argument's value points to a readable file. When
     * CliArgs::openInput() is called, a read pointer to this file will
     * be provided.
     * @return Arg
     */
    public function useAsInfile()
    {
        $this->spec['useAsInfile'] = true;
        return $this->assertFile()->assertReadable();
    }

    /**
     * @return mixed
     */
    public function getLetter()
    {
        return $this->letter;
    }

    /**
     * @return array
     */
    public function getSpec()
    {
        return $this->spec;
    }

    /**
     * @param string $name
     * @param array $args
     * @return Arg
     * @throws \BadMethodCallException
     */
    public function __call($name, array $args = array())
    {
        if (array_key_exists($name, $this->spec)) {
            $this->spec[$name] = true;
            if ($name === 'assertFile' || $name === 'assertDir') {
                $this->spec['mustHaveValue'] = true;
            }
        } else {
            throw new \BadMethodCallException('Method does not exist');
        }
        return $this;
    }

    /**
     * @param string $name
     * @return bool|null
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->spec)) {
            return $this->spec[$name];
        } elseif ($name === 'isRequired') {
            return $this->required;
        }
        return null;
    }
}
