<?php

namespace Bismuth\Tools;

class Options
{

    private $options = null;

    public function __construct($defaultOpts = array())
    {
        $opts = array('default' => $defaultOpts, 'user' => array());
        $this->options = new Object($opts);
    }

    public function __get($key)
    {
        return $this->getOption($key);
    }

    public function __set($key, $value)
    {
        $this->setOption($key, $value);
    }

    public function getDefault($key)
    {
        return (array_key_exists($key, $this->options->default)) ? $this->options->default[$key] : null;
    }

    public function getOption($key)
    {
        if (array_key_exists($key, $this->options->user)) {
            return $this->options->user[$key];
        } elseif (array_key_exists($key, $this->options->default)) {
            return $this->options->default[$key];
        } else {
            return null;
        }
    }

    public function setOption($key, $value)
    {
        if (array_key_exists($key, $this->options->default)) {
            @$this->options->user[$key] = $value;
            return true;
        }

        return false;
    }

    public function setBulkOptions(array $options)
    {
        foreach ($this->options->default as $key => $value) {
            if (array_key_exists($key, $options)) {
                $this->setOption($key, $options[$key]);
            }
        }
    }

}

?>