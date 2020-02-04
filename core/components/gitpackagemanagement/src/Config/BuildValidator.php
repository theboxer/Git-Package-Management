<?php
namespace GitPackageManagement\Config;

use MODX\Revolution\modX;

class BuildValidator {
    private $modx;
    private $validatorsDir = 'validators';
    private $validators = array();

    public function __construct(modX &$modx) {
        $this->modx =& $modx;
    }

    public function fromArray($config) {
        if(isset($config['validatorsDir'])){
            $this->validatorsDir = $config['validatorsDir'];
        }

        if(isset($config['validators'])){
            $this->validators = $config['validators'];
        }

        return true;
    }

    /**
     * @return string
     */
    public function getValidatorsDir() {
        return $this->validatorsDir;
    }

    /**
     * @param string $validatorsDir
     */
    public function setValidatorsDir($validatorsDir) {
        $this->validatorsDir = $validatorsDir;
    }

    /**
     * @return array
     */
    public function getValidators() {
        return $this->validators;
    }

    /**
     * @param array $validators
     */
    public function setValidators($validators) {
        $this->validators = $validators;
    }
}
