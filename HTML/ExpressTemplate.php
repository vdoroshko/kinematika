<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * PHP templating class
 *
 * PHP version 5
 *
 * Copyright (c) 2013-2015, Vitaly Doroshko
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
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */

// {{{ classes
// {{{ class HTML_ExpressTemplate

/**
 * PHP templating class
 *
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class HTML_ExpressTemplate
{
    // {{{ protected class properties

    /**
     * Filename of the template file
     *
     * @var    string
     * @since  1.0
     */
    protected $_filename;

    /**
     * Associative array of template variables
     *
     * @var    array
     * @since  1.0
     */
    protected $_vars;

    // }}}
    // {{{ constructor

    /**
     * Constructs a new HTML_ExpressTemplate object
     *
     * @param  string  $filename (optional) The filename of the template file to be rendered
     */
    public function __construct($filename = null)
    {
        if ($filename) {
            $this->_filename = (string)$filename;
        }

        $this->_vars = array();
    }

    // }}}
    // {{{ render()

    /**
     * Renders the template file and returns the rendered contents
     *
     * @return string  The rendered contents
     * @throws HTML_ExpressTemplate_FileNotFoundException
     * @throws HTML_ExpressTemplate_InvalidFilenameException
     * @throws HTML_ExpressTemplate_IOException
     * @throws HTML_ExpressTemplate_ParseException
     * @since  1.0
     */
    public function render()
    {
        if (empty($this->_filename)) {
            throw new HTML_ExpressTemplate_InvalidFilenameException('filename of template file is not set');
        }

        if (($script = @file_get_contents($this->_filename)) === false) {
            if (!@file_exists($this->_filename)) {
                throw new HTML_ExpressTemplate_FileNotFoundException(sprintf("template file '%s' not found", $this->_filename));
            }

            throw new HTML_ExpressTemplate_IOException(sprintf("could not read template file '%s'", $this->_filename));
        }

        ob_start();

        if (@eval('?>' . $script . '<?php ') === false) {
            if (function_exists('error_get_last')) {
                if ($errorInfo = error_get_last()) {
                    $message = sprintf("syntax error in template file '%s' on line %d", $this->_filename, $errorInfo['line']);
                    throw new HTML_ExpressTemplate_ParseException($message, $errorInfo['type'], $this->_filename, $errorInfo['line']);
                }
            }

            throw new HTML_ExpressTemplate_ParseException(sprintf("syntax error in template file '%s' on unknown line", $this->_filename));
        }

        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    // }}}
    // {{{ getFilename()

    /**
     * Returns the filename of the template file
     *
     * @return mixed   The filename of the template file or null if the filename
     *                 is not set
     * @since  1.0
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    // }}}
    // {{{ setFilename()

    /**
     * Sets the filename of the template file to be rendered
     *
     * @param  string  $filename The filename of the template file to be rendered
     * @return void
     * @throws HTML_ExpressTemplate_InvalidFilenameException
     * @since  1.0
     */
    public function setFilename($filename)
    {
        if (empty($filename)) {
            throw new HTML_ExpressTemplate_InvalidFilenameException('filename of template file cannot be empty');
        }

        $this->_filename = (string)$filename;
    }

    // }}}
    // {{{ __get()

    /**
     * Returns the value of a template variable
     *
     * @param  string  $name The name of the variable
     * @return mixed   The value of the variable
     * @throws HTML_ExpressTemplate_UndefinedVariableException
     * @since  1.0
     */
    public function __get($name)
    {
        if (!array_key_exists($name, $this->_vars)) {
            $backtrace = debug_backtrace();
            if (strpos($backtrace[0]['file'], __FILE__) !== false) {
                $message = sprintf("undefined template variable '%s' in template file '%s' on line %d", $name, $this->_filename, $backtrace[0]['line']);
            } else {
                $message = sprintf("undefined template variable '%s' in file '%s' on line %d", $name, $backtrace[0]['file'], $backtrace[0]['line']);
            }

            throw new HTML_ExpressTemplate_UndefinedVariableException($message);
        }

        return $this->_vars[$name];
    }

    // }}}
    // {{{ __set()

    /**
     * Sets the value of a template variable
     *
     * @param  string  $name The name of the variable
     * @param  mixed   $value The value of the variable
     * @return void
     * @since  1.0
     */
    public function __set($name, $value)
    {
        $this->_vars[$name] = $value;
    }

    // }}}
    // {{{ __isset()

    /**
     * Determines if a template variable is set and is not null
     *
     * @param  string  $name The name of the variable to check
     * @return boolean true if the variable exists and has value other than null
     *                 or false otherwise
     * @since  1.0
     */
    public function __isset($name)
    {
        return isset($this->_vars[$name]);
    }

    // }}}
    // {{{ __unset()

    /**
     * Unsets a template variable
     *
     * @param  string  $name The name of the variable to unset
     * @return void
     * @since  1.0
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_vars)) {
            unset($this->_vars[$name]);
        }
    }

    // }}}
}

// }}}
// {{{ class HTML_ExpressTemplate_Exception

/**
 * Base class for all templating exceptions
 *
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class HTML_ExpressTemplate_Exception extends RuntimeException {}

// }}}
// {{{ class HTML_ExpressTemplate_IOException

/**
 * Exception class that is thrown when an I/O failure occurs while reading a
 * template file
 *
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class HTML_ExpressTemplate_IOException extends HTML_ExpressTemplate_Exception {}

// }}}
// {{{ class HTML_ExpressTemplate_FileNotFoundException

/**
 * Exception class that is thrown when an attempt to access a template file
 * that does not exist fails
 *
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class HTML_ExpressTemplate_FileNotFoundException extends HTML_ExpressTemplate_IOException {}

// }}}
// {{{ class HTML_ExpressTemplate_InvalidFilenameException

/**
 * Exception class that is thrown when the filename of a template file is
 * invalid
 *
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class HTML_ExpressTemplate_InvalidFilenameException extends HTML_ExpressTemplate_IOException {}

// }}}
// {{{ class HTML_ExpressTemplate_ParseException

/**
 * Exception class that is thrown when a syntax error occurs while parsing a
 * template file
 *
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class HTML_ExpressTemplate_ParseException extends HTML_ExpressTemplate_Exception
{
    // {{{ constructor

    /**
     * Constructs a new HTML_ExpressTemplate_ParseException object
     *
     * @param  string  $message The exception message
     * @param  integer $code (optional) The exception code
     * @param  string  $filename (optional) The filename of the file where the exception was thrown
     * @param  integer $line (optional) The line in the file where the exception was thrown
     */
    public function __construct($message, $code = 0, $filename = null, $line = null)
    {
        parent::__construct((string)$message, (integer)$code);

        if ($filename) {
            $this->file = (string)$filename;
        }

        if ($line !== null) {
            $this->line = (integer)$line;
        }
    }

    // }}}
}

// }}}
// {{{ class HTML_ExpressTemplate_UndefinedVariableException

/**
 * Exception that is thrown when there is an attempt to access a template
 * variable that does not exist
 *
 * @category   HTML
 * @package    HTML_ExpressTemplate
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2013-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class HTML_ExpressTemplate_UndefinedVariableException extends HTML_ExpressTemplate_Exception {}

// }}}
// }}}

?>
