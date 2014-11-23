<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML-RPC server proxy class
 *
 * PHP version 5
 *
 * Copyright (c) 2014, Vitaly Doroshko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Web Services
 * @package    XML_RPC_Proxy
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */

// {{{ classes
// {{{ class XML_RPC_Proxy

/**
 * XML-RPC server proxy class
 *
 * @category   Web Services
 * @package    XML_RPC_Proxy
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_Proxy
{
    // {{{ protected class properties

    /**
     * URL of XML-RPC server
     *
     * @var    string
     * @since  1.0
     */
    protected $_url;

    /**
     * Runtime configuration options
     *
     * @var    array
     * @since  1.0
     */
    protected $_options;

    // }}}
    // {{{ constructor

    /**
     * Constructs a new XML_RPC_Proxy object
     *
     * @param  string  $url The URL of the XML-RPC server to connect
     * @param  array   $options (optional) The runtime configuration options
     * @throws DomainException
     * @throws XML_RPC_Proxy_InvalidURLException
     */
    public function __construct($url, $options = array())
    {
        if (!filter_var((string)$url, FILTER_VALIDATE_URL)) {
            throw new XML_RPC_Proxy_InvalidURLException(sprintf("'%s' is not valid url", $url));
        }

        $this->_url = (string)$url;

        $this->_registerOption('prefix');
        $this->_registerOption('charset', 'utf-8');

        $this->setOptions($options);
    }

    // }}}
    // {{{ getOption()

    /**
     * Returns the value of the specified runtime configuration option
     *
     * @param  string  $name The option name
     * @return mixed   The option value
     * @throws DomainException
     * @since  1.0
     */
    public function getOption($name)
    {
        if (!array_key_exists((string)$name, $this->_options)) {
            throw new DomainException(sprintf("unknown option '%s'", $name));
        }

        return $this->_options[(string)$name];
    }

    // }}}
    // {{{ getOptions()

    /**
     * Returns either the values of all the runtime configuration options or the
     * values of the specified runtime configuration options only
     *
     * @param  array   $names (optional) An array containing the names of the options
     * @return array   An associative array of the options
     * @throws DomainException
     * @since  1.0
     */
    public function getOptions($names = array())
    {
        if (empty($names)) {
            return $this->_options;
        }

        $options = array();
        foreach ((array)$names as $name) {
            $options[(string)$name] = $this->getOption($name);
        }

        return $options;
    }

    // }}}
    // {{{ setOption()

    /**
     * Sets the value of the specified runtime configuration option
     *
     * @param  string  $name The option name
     * @param  mixed   $value The option value
     * @return void
     * @throws DomainException
     * @since  1.0
     */
    public function setOption($name, $value)
    {
        if (!array_key_exists((string)$name, $this->_options)) {
            throw new DomainException(sprintf("unknown option '%s'", $name));
        }

        $this->_options[(string)$name] = (string)$value;
    }

    // }}}
    // {{{ setOptions()

    /**
     * Sets the values of specified runtime configuration options
     *
     * @param  array   $options An associative array of the options
     * @return void
     * @throws DomainException
     * @since  1.0
     */
    public function setOptions($options)
    {
        foreach ((array)$options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    // }}}
    // {{{ isFault()

    /**
     * Checks if the given array represents an XML-RPC fault
     *
     * @param  array   $result An array to check
     * @return boolean true if the array represents an XML-RPC fault or false
     *                 otherwise
     * @since  1.0
     */
    public static function isFault($result)
    {
        return xmlrpc_is_fault((array)$result);
    }

    // }}}
    // {{{ __call()

    /**
     * Invokes a method on the XML-RPC server
     *
     * @param  string  $name The method to call
     * @param  array   $arguments An array of arguments to pass to the method
     * @return mixed   The method result decoded into native PHP types or an
     *                 associative array representing an XML-RPC fault
     * @throws BadMethodCallException
     * @throws XML_RPC_Proxy_IOException
     * @since  1.0
     */
    public function __call($name, $arguments)
    {
        $method = ($this->_options['prefix'] ? $this->_options['prefix'] . '.' : '') . $name;
        $request = xmlrpc_encode_request($method, $arguments);

        $options = array(
           'http' => array(
               'method'  => 'POST',
               'header'  => sprintf('Content-Type: text/xml; charset=%s', $this->_options['charset']),
               'content' => $request
           )
        );

        $context = stream_context_create($options);
        if (!($file = @file_get_contents($this->_url, false, $context))) {
            throw new XML_RPC_Proxy_IOException(sprintf("unable to communicate with server at '%s'", $this->_url));
        }

        $response = xmlrpc_decode($file);
        if (self::isFault($response)) {
            if ($response['faultCode'] == -32601) {
                throw new BadMethodCallException(sprintf('method %s() does not exist', $name));
            }
        }

        return $response;
    }

    // }}}
    // {{{ _registerOption()

    /**
     * Adds a new option into the runtime configuration option array
     *
     * @param  string  $name The option name
     * @param  mixed   $value (optional) The default option value
     * @return void
     * @throws DomainException
     * @since  1.0
     */
    protected function _registerOption($name, $value = null)
    {
        if (array_key_exists($name, (array)$this->_options)) {
            throw new DomainException(sprintf("option '%s' already exists", $name));
        }

        $this->_options[$name] = $value;
    }

    // }}}
}

// }}}
// {{{ class XML_RPC_Proxy_IOException

/**
 * Exception class that is thrown when a communications failure occurs during
 * a remote operation
 *
 * @category   Web Services
 * @package    XML_RPC_Proxy
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_Proxy_IOException extends RuntimeException {}

// }}}
// {{{ class XML_RPC_Proxy_InvalidURLException

/**
 * Exception class that is thrown when specified URL is invalid
 *
 * @category   Web Services
 * @package    XML_RPC_Proxy
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_Proxy_InvalidURLException extends XML_RPC_Proxy_IOException {}

// }}}
// }}}

?>
