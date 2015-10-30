<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML-RPC server proxy class
 *
 * PHP version 5
 *
 * Copyright (c) 2014, 2015, Vitaly Doroshko
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
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
require_once 'XML/RPC/Exception.php';

// {{{ classes
// {{{ class XML_RPC_Proxy

/**
 * XML-RPC server proxy class
 *
 * @category   Web Services
 * @package    XML_RPC_Proxy
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_Proxy
{
    // {{{ protected class properties

    /**
     * URL of the XML-RPC server
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
     * @param  string  $url The URL of the XML-RPC server to be connected to
     * @param  array   $options (optional) The runtime configuration options
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public function __construct($url, $options = array())
    {
        if (empty($url)) {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if (!filter_var((string)$url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf("'%s' is not a valid URL", $url));
        }

        $this->_url = (string)$url;

        $this->_registerOption('namespace');
        $this->_registerOption('encoding', 'iso-8859-1');
        $this->_registerOption('escaping', array('markup', 'non-ascii', 'non-print'));

        $this->setOptions($options);
    }

    // }}}
    // {{{ getURL()

    /**
     * Returns the URL of the XML-RPC server
     *
     * @return string  The URL of the XML-RPC server
     * @since  1.0
     */
    public function getURL()
    {
        return $this->_url;
    }

    // }}}
    // {{{ getOption()

    /**
     * Returns the value of a runtime configuration option
     *
     * @param  string  $name The name of the option
     * @return mixed   The value of the option
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
    // {{{ setOption()

    /**
     * Sets the value of a runtime configuration option
     *
     * @param  string  $name The name of the option
     * @param  string  $value The value of the option
     * @return void
     * @throws DomainException
     * @throws InvalidArgumentException
     * @since  1.0
     */
    public function setOption($name, $value)
    {
        if (!array_key_exists((string)$name, $this->_options)) {
            throw new DomainException(sprintf("unknown option '%s'", $name));
        }

        switch ((string)$name) {
            case 'encoding':
                if (empty($value)) {
                    throw new InvalidArgumentException('encoding cannot be empty');
                }

                if (iconv('iso-8859-1', (string)$value, 'encoding') === false) {
                    throw new DomainException(sprintf("'%s' is not valid encoding", $value));
                }

                $this->_options[(string)$name] = (string)$value;
                break;

            case 'escaping':
                if (empty($value)) {
                    throw new InvalidArgumentException('escaping cannot be empty');
                }

                foreach ((array)$value as $escaping) {
                    if (!in_array((string)$escaping, array('cdata', 'markup', 'non-ascii', 'non-print'))) {
                        throw new DomainException('escaping can be one or more of the following: cdata, markup, non-ascii, non-print');
                    }
                }

                $this->_options[(string)$name] = array_values((array)$value);
                break;

            default:
                $this->_options[(string)$name] = $value ? (string)$value : null;
        }
    }

    // }}}
    // {{{ getOptions()

    /**
     * Returns either the values of all the runtime configuration options or the
     * values of only the specified ones
     *
     * @param  array   $names (optional) An array of the option names
     * @return array   An associative array of the option names and their values
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
    // {{{ setOptions()

    /**
     * Sets the values of the specified runtime configuration options
     *
     * @param  array   $options An associative array of the option names and their values
     * @return void
     * @throws DomainException
     * @throws InvalidArgumentException
     * @since  1.0
     */
    public function setOptions($options)
    {
        foreach ((array)$options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    // }}}
    // {{{ __call()

    /**
     * Invokes a method on the XML-RPC server
     *
     * @param  string  $name The name of the method to invoke
     * @param  array   $args An array of arguments to pass to the method
     * @return mixed   The method invocation result decoded into native PHP types
     * @throws XML_RPC_BadMethodCallException
     * @throws XML_RPC_FaultException
     * @throws XML_RPC_IOException
     * @throws XML_RPC_NotAllowedException
     * @since  1.0
     */
    public function __call($name, $args)
    {
        if (!ini_get('allow_url_fopen')) {
            throw new XML_RPC_NotAllowedException('remote access is disabled in the local server configuration');
        }

        $method = ($this->_options['namespace'] ? $this->_options['namespace'] . '.' : '') . $name;

        $options = array(
            'encoding' => $this->_options['encoding'],
            'escaping' => $this->_options['escaping']
        );

        $request = xmlrpc_encode_request($method, $args, $options);

        $options = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => sprintf('Content-Type: text/xml; charset=%s', $this->_options['encoding']),
                'content' => $request
            )
        );

        $context = stream_context_create($options);
        if (($response = @file_get_contents($this->_url, false, $context)) === false) {
            throw new XML_RPC_IOException(sprintf("unable to communicate with server at '%s'", $this->_url));
        }

        $result = xmlrpc_decode($response, $this->_options['encoding']);
        if (xmlrpc_is_fault($result)) {
            if ($result['faultCode'] == -32601) {
                throw new XML_RPC_BadMethodCallException(sprintf('method %s() does not exist', $method));
            }

            throw new XML_RPC_FaultException($result['faultString'], $result['faultCode']);
        }

        return $result;
    }

    // }}}
    // {{{ _registerOption()

    /**
     * Adds a new option into the array of the runtime configuration options
     *
     * @param  string  $name The name of the option to add
     * @param  mixed   $value (optional) The default value of the option
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
// }}}

?>
