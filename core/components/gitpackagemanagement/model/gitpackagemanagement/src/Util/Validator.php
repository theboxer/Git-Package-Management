<?php
namespace GPM\Util;

trait Validator
{
    protected $section = '';
    protected $required = [];

    protected function validate($config)
    {
        foreach ($this->required as $field) {
            if (!isset($config['field'])) {
                $msg = '';
                if (!empty ($this->section)) {
                    $msg .= $this->section . ' - ';
                }
                
                $msg .= $field . ' is not set';
                
                throw new \Exception($msg);
            }
        }
    }

}