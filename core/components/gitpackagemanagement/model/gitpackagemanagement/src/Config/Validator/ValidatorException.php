<?php
namespace GPM\Config\Validator;

class ValidatorException extends \Exception
{
    protected $section;
    protected $errors = [];
    
    public function __construct($section, $errors = [], $code = 0, \Exception $previous = null)
    {
        $this->section = $section;
        $this->errors = $errors;
        
        $this->createMessage($section, $errors);
        
        parent::__construct($this->message, $code, $previous);
    }
    
    public function getSection()
    {
        return $this->section;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }

    protected function createMessage($section, $errors)
    {
        $this->message = $section . ':' . PHP_EOL;
        
        foreach ($errors as $field => $messages) {
            $this->message .= '    - ' . $field . ':' . PHP_EOL;
            
            foreach ($messages as $message) {
                $this->message .= '        - ' . $message . PHP_EOL;    
            }
        }
    }
}