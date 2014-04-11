<?php

namespace Xdmod;

use ArrayAccess;
use Exception;

class Config implements ArrayAccess
{

    /**
     * Instance for singleton pattern;
     *
     * @var Config
     */
    private static $instance = null;

    /**
     * Config data by section.
     *
     * @var array
     */
    private $sections = array();

    /**
     * Private constructor for factory pattern.
     */
    private function __construct()
    {
    }

    /**
     * Factory method.
     */
    public static function factory()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset)
    {
        try {
            $this->getFileName($offset);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        if (!isset($this->sections[$offset])) {
            $this->sections[$offset] = $this->loadSection($offset);
        }

        return $this->sections[$offset];
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        throw new Exception("Cannot set config section '$offset'");
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset)
    {
        throw new Exception("Cannot unset config section '$offset'");
    }

    /**
     * Load a config section from a file.
     *
     * @param string $section The name of the config section.
     *
     * @return mixed
     */
    private function loadSection($section)
    {
        $file = $this->getFileName($section);

        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new Exception("Failed to read config file '$file'");
        }

        $data = json_decode($contents, true);

        if ($data === null) {
            throw new Exception("Failed to decode config file '$file'");
        }

        return $data;
    }

    /**
     * Get the file name for a configuration section.
     *
     * @param string $section
     *
     * @return string
     */
    public function getFileName($section)
    {
        $name = preg_replace('/[^a-z_]/', '_', $section);

        $file = CONFIG_DIR . '/' . $name . '.json';

        if (!is_file($file)) {
            throw new Exception("Configuration file '$section' not found");
        }

        if (!is_readable($file)) {
            throw new Exception("Configuration file '$section' not readable");
        }

        return $file;
    }
}

