<?php
/**
 * flightzilla
 *
 * Copyright (c)2012, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
    * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package flightzilla
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Auth-Wrapper
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Auth implements Zend_Auth_Adapter_Interface {

    /**
     * The encryption-key
     *
     * @var string
     */
    protected $_sKey = null;

    /**
     * The encryption-payload
     *
     * @var mixed
     */
    protected $_mValue;

    /**
     * The crypted value
     *
     * @var string
     */
    protected $_sCrypted;

    /**
     * Create the Model
     *
     * @param mixed $mValue
     */
    public function __construct($mValue = null) {
        $this->_sKey = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

        if (empty($mValue) !== true) {
            if (is_array($mValue) === true) {
                $this->_mValue = serialize($mValue);
                $this->_encrypt();
            }
            else {
                $this->_sCrypted = $mValue;
                $this->_decrypt();
            }

            $mValue = @unserialize($this->_mValue);
            if (is_array($mValue) === true) {
                Zend_Registry::set('_login', $mValue['username']);
                Zend_Registry::set('_password', $mValue['password']);
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see Zend_Auth_Adapter_Interface::authenticate()
     */
    public function authenticate() {
        if (Zend_Registry::isRegistered('_login') === true) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, Zend_Registry::get('_login'));
        }

        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
    }

    /**
     * Encrypt a string with a key
     *
     * @param  string $sKey
     * @param  string $sValue
     *
     * @return string
     */
    public function encrypt($sKey, $sValue) {
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $sKey, $sValue, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

    /**
     * Decrypt a string with a key
     *
     * @param  string $sKey
     * @param  string $sValue
     *
     * @return string
     */
    public function decrypt($sKey, $sValue) {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $sKey, base64_decode($sValue), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }

    /**
     * Get the crypted string
     *
     * @return string
     */
    public function getCrypted() {
        return $this->_sCrypted;
    }

    /**
     * Encrypt magic
     *
     * @return Model_Auth
     */
    protected function _encrypt() {
        $this->_sCrypted = $this->encrypt($this->_sKey, $this->_mValue);
        return $this;
    }

    /**
     * Decrypt magic
     *
     * @return Model_Auth
     */
    protected function _decrypt() {
        $this->_mValue = $this->decrypt($this->_sKey, $this->_sCrypted);
        return $this;
    }

}