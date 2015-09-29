<?php
/**
 * Class Minify_Controller_Base  
 * @package Minify
 */

/**
 * Base class for Minify controller
 * 
 * The controller class validates a request and uses it to create a configuration for Minify::serve().
 * 
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
abstract class Minify_Controller_Base implements Minify_ControllerInterface {

    /**
     * @var Minify_Env
     */
    protected $env;

    /**
     * @var Minify_Source_Factory
     */
    protected $sourceFactory;

    /**
     * @param Minify_Env            $env
     * @param Minify_Source_Factory $sourceFactory
     */
    public function __construct(Minify_Env $env, Minify_Source_Factory $sourceFactory)
    {
        $this->env = $env;
        $this->sourceFactory = $sourceFactory;
    }

    /**
     * Create controller sources and options for Minify::serve()
     * 
     * @param array $options controller and Minify options
     * 
     * @return Minify_ServeConfiguration
     */
    abstract public function createConfiguration(array $options);
    
    /**
     * Send message to the Minify logger
     *
     * @param string $msg
     *
     * @return null
     */
    public function log($msg)
    {
        Minify_Logger::log($msg);
    }
}
