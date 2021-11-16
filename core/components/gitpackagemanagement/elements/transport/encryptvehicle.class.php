<?php
/**
 * Encrypt Vehicle
 *
 * @package gitpackagemanagement
 * @subpackage classfile
 */

use Psr\Http\Message\ResponseInterface;

class_alias('encryptVehicle', 'xPDO\Transport\encryptVehicle');

class encryptVehicle extends xPDOObjectVehicle
{
    public $class = 'encryptVehicle';
    public const version = '3.0.0';
    public const cipher = 'AES-256-CBC';

    /**
     * Put an encrypted xPDOObject representation into the vehicle
     *
     * @param xPDOTransport $transport
     * @param mixed $object
     * @param array $attributes
     */
    public function put(&$transport, &$object, $attributes = array())
    {
        parent::put($transport, $object, $attributes);

        $this->payload['support_name'] = $transport->xpdo->getOption('gitpackagemanagement.support_name', null, $transport->xpdo->getOption('site_name'), true);
        $this->payload['support_mail'] = $transport->xpdo->getOption('gitpackagemanagement.support_mail', null, $transport->xpdo->getOption('email_sender'), true);
        $this->payload['support_url'] = $transport->xpdo->getOption('gitpackagemanagement.support_url', null, $transport->xpdo->getOption('hostname'), true);

        $encryptKey = $transport->xpdo->getOption('gitpackagemanagement.encrypt_key');
        if ($encryptKey) {
            $this->payload['object_encrypted'] = $this->encrypt($this->payload['object'], $encryptKey);
            unset($this->payload['object']);

            if (isset($this->payload['related_objects'])) {
                $this->payload['related_objects_encrypted'] = $this->encrypt($this->payload['related_objects'], $encryptKey);
                unset($this->payload['related_objects']);
            }

            $transport->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Package encrypted!');
        } else {
            $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Encrypt key not set. Package not encrypted!');
        }
    }

    /**
     * Install the decrypted xPDOObjects represented by vehicle into the transport host.
     *
     * @param xPDOTransport $transport
     * @param array $options
     *
     * @return bool
     */
    public function install(&$transport, $options)
    {
        if (!$this->decryptPayloads($transport)) {
            return false;
        } else {
            $transport->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Package decrypted!');
        }

        return parent::install($transport, $options);
    }

    /**
     * Uninstalls the decrypted xPDOObjects represented by vehicle from the transport host.
     *
     * @param xPDOTransport $transport
     * @param array $options
     * @return boolean
     */
    public function uninstall(&$transport, $options)
    {
        if (!$this->decryptPayloads($transport, 'uninstall')) {
            return false;
        } else {
            $transport->xpdo->log(xPDO::LOG_LEVEL_INFO, 'Package decrypted!');
        }

        return parent::uninstall($transport, $options);
    }

    /**
     * Encrypt the data
     *
     * @param array $data
     * @param string $key
     *
     * @return string
     */
    protected function encrypt($data, $key)
    {
        $ivLen = openssl_cipher_iv_length(self::cipher);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $cipher_raw = openssl_encrypt(serialize($data), self::cipher, $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $cipher_raw);
    }

    /**
     * Decrypt the data
     *
     * @param string $string
     * @param string $key
     * @param string $error Passed by reference, an error message from the decoding (if any)
     * @return string
     */
    protected function decrypt($string, $key, &$error = '')
    {
        $ivLen = openssl_cipher_iv_length(self::cipher);
        $encoded = base64_decode($string);
        if (ini_get('mbstring.func_overload')) {
            $strLen = mb_strlen($encoded, '8bit');
            $iv = mb_substr($encoded, 0, $ivLen, '8bit');
            $cipher_raw = mb_substr($encoded, $ivLen, $strLen, '8bit');
        } else {
            $iv = substr($encoded, 0, $ivLen);
            $cipher_raw = substr($encoded, $ivLen);
        }
        $decrypted = openssl_decrypt($cipher_raw, self::cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            $error = 'Decryption failed: ';
            while ($msg = openssl_error_string()) {
                $error .= '- ' . $msg;
            }
            return false;
        }
        return unserialize($decrypted);
    }

    /**
     * Decrypt the encrypted payload if the api_key and the username are allowed to install the package
     *
     * @param xPDOTransport $transport
     * @param string $action
     * @return bool
     */
    protected function decryptPayloads(&$transport, $action = 'install')
    {
        $error = '';
        if (isset($this->payload['object_encrypted']) || isset($this->payload['related_objects_encrypted'])) {
            if (!$key = $this->getDecryptKey($transport, $action)) {
                $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Could not retrieve the decryption key.');
                return false;
            }
            if (isset($this->payload['object_encrypted'])) {
                $decrypted = $this->decrypt($this->payload['object_encrypted'], $key, $error);
                if ($decrypted === false) {
                    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Failed to decrypt object with key ' . $key . ': ' . $error);
                    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Please try to install the package again and contact ' . $this->payload['support_mail'] . ', if the problem persists.');
                    return false;
                }
                $this->payload['object'] = $decrypted;
                unset($this->payload['object_encrypted']);
            }
            if (isset($this->payload['related_objects_encrypted'])) {
                $decrypted = $this->decrypt($this->payload['related_objects_encrypted'], $key, $error);
                if ($decrypted === false) {
                    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Failed to decrypt related objects with key ' . $key . ': ' . $error);
                    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Please try to install the package again and contact ' . $this->payload['support_mail'] . ', if the problem persists.');
                    return false;
                }
                $this->payload['related_objects'] = $decrypted;
                unset($this->payload['related_objects_encrypted']);
            }
        }

        return true;
    }

    /**
     * Get the decrypt key from the package provider
     *
     * @param xPDOTransport $transport
     * @param string $action
     *
     * @return bool|string
     */
    protected function getDecryptKey(&$transport, $action)
    {
        $endpoint = 'package/decode/' . $action;

        /** @var modTransportPackage|\MODX\Revolution\Transport\modTransportPackage $package */
        $package = $transport->xpdo->getObject('transport.modTransportPackage', array(
            'signature' => $transport->signature,
        ));
        if ($package) {
            /** @var modTransportProvider|\MODX\Revolution\Transport\modTransportProvider $provider */
            if ($provider = $package->getOne('Provider')) {
                $provider->xpdo->setOption('contentType', 'default');
                $params = array(
                    'package' => $package->package_name,
                    'version' => $transport->version,
                    'signature' => $package->signature,
                    'username' => $provider->username,
                    'api_key' => $provider->api_key,
                    'vehicle_version' => self::version,
                );

                $response = $provider->request($endpoint, 'POST', $params);
                // On MODX 2.x and up to 3.0.0-alpha2, providers use modRestClient
                if ($response instanceof modRestResponse) {
                    if ($response->isError()) {
                        $msg = $response->getError();
                        $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, (string)$msg);
                    } else {
                        $data = $response->toXml();
                        if (!empty($data->key)) {
                            return base64_decode((string)$data->key);
                        }

                        if (!empty($data->message)) {
                            $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, (string)$data->message);
                        }
                    }
                } // MODX 3.0.0-alpha3 and up, providers return a PSR response
                elseif ($response instanceof ResponseInterface) {
                    $raw = $response->getBody()->getContents();
                    $disableEntities = libxml_disable_entity_loader();
                    $internalErrors = libxml_use_internal_errors(true);
                    $data = simplexml_load_string($raw);
                    libxml_disable_entity_loader($disableEntities);
                    libxml_use_internal_errors($internalErrors);

                    if (!empty($data->key)) {
                        return base64_decode((string)$data->key);
                    }

                    if (!empty($data->message)) {
                        $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, (string)$data->message);
                    }
                } else {
                    $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Could not find the provider for this package. Please make sure this package was installed with the ' . $this->payload['support_name'] . ' package provider and contact ' . $this->payload['support_mail'] . ', if you need assistance.');
                }
            }
        } else {
            $transport->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Could not find the package object for ' . $transport->signature . '.');
        }

        return false;
    }
}