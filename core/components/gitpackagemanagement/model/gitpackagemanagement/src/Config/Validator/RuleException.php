<?php
namespace GPM\Config\Validator;

class RuleException extends \Exception
{
    protected $field;
    
    public function __construct($field, $message = "", $code = 0, \Exception $previous = null)
    {
        $this->field = $field;
        
        parent::__construct($message, $code, $previous);
    }

    public function getField()
    {
        return $this->field;
    }
}