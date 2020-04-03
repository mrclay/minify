<?php


interface Minify_ControllerInterface
{

    /**
     * Create controller sources and options for Minify::serve()
     *
     * @param array $options controller and Minify options
     *
     * @return Minify_ServeConfiguration
     */
    public function createConfiguration(array $options);

    /**
     * Get the Env component
     *
     * @return Minify_Env
     */
    public function getEnv();
}
