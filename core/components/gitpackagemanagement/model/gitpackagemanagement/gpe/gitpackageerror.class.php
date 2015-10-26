<?php
class GitPackageError {
    /** @var modX $modx */
    public $modx;
    /** @var array $errors */
    private $errors = array();

    public function __construct(modX &$modx){
        $this->modx =& $modx;
    }

    public function addError($msg, $log = false) {
        $this->errors[] = $msg;
        if ($log) {
            $this->log($msg);
        }
    }

    public function hasErrors() {
        return count($this->errors) > 0;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function log($msg, $level = modX::LOG_LEVEL_ERROR) {
        $this->modx->log($level, '[GitPackageManagement] ' . $msg);
    }

}