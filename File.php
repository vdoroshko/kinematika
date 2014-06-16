<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Generic file handling class
 *
 * PHP version 5
 *
 * Copyright (c) 2005-2014 Vitaly Doroshko
 * All right reserved.
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
 * @category   File System
 * @package    File
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2005-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 */

// {{{ classes
// {{{ class File

/**
 * Generic file handling class
 *
 * @category   File System
 * @package    File
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2005-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File implements IteratorAggregate
{
    // {{{ class constants

    /**
     * Beginning of a file
     *
     * @since  1.0
     */
    const SEEK_SET = 0;

    /**
     * Current location within a file
     *
     * @since  1.0
     */
    const SEEK_CUR = 1;

    /**
     * End of a file
     *
     * @since  1.0
     */
    const SEEK_END = 2;

    // }}}
    // {{{ protected class properties

    /**
     * File name
     *
     * @since  1.0
     * @var    string
     */
    protected $_filename;

    /**
     * File access mode
     *
     * @since  1.0
     * @var    string
     */
    protected $_mode;

    /**
     * File pointer
     *
     * @since  1.0
     * @var    resource
     */
    protected $_handle;

    /**
     * Runtime configuration options
     *
     * @since  1.0
     * @var    array
     */
    protected $_options;

    // }}}
    // {{{ constructor

    /**
     * @param  string  $filename
     * @param  string  $mode
     * @param  array   $options (optional)
     * @throws DomainException
     * @throws OutOfBoundsException
     * @throws File_IOException
     */
    protected function __construct($filename, $mode = 'r', $options = array())
    {
        if (!preg_match('/[rwaxc][bt]?\+?/', (string)$mode)) {
            throw new DomainException(sprintf("invalid file access mode '%s'", $mode));
        }

        if (isset($options['useIncludePath'])) {
            $useIncludePath = (boolean)$options['useIncludePath'];
        } else {
            $useIncludePath = false;
        }

        if (!($handle = @fopen((string)$filename, (string)$mode, $useIncludePath))) {
            throw new File_IOException(sprintf("could not open file '%s'", $filename));
        }

        $this->_filename = (string)$filename;
        $this->_mode     = (string)$mode;
        $this->_handle   = $handle;

        $this->_registerOption('useIncludePath', false);
        $this->_registerOption('encoding');

        $this->setOptions($options);
    }

    // }}}
    // {{{ destructor

    /**
     * @since  1.0
     * @throws File_IOException
     */
    public function __destruct()
    {
        $this->close();
    }

    // }}}
    // {{{ open()

    /**
     * @param  string  $filename
     * @param  string  $mode
     * @param  array   $options (optional)
     * @return object
     * @since  1.0
     * @throws DomainException
     * @throws OutOfBoundsException
     * @throws File_IOException
     */
    public static function open($filename, $mode = 'r', $options = array())
    {
        static $instances;

        if (!isset($instances[(string)$filename])) {
            $instances[(string)$filename] = new self($filename, $mode, $options);
        } elseif (!$instances[(string)$filename]->handle) {
            $instances[(string)$filename] = new self($filename, $mode, $options);
        }

        return $instances[(string)$filename];
    }

    // }}}
    // {{{ close()

    /**
     * @return void
     * @since  1.0
     * @throws File_IOException
     */
    public function close()
    {
        if (!$this->_handle) {
            return;
        }

        if (!@fflush($this->_handle)) {
            $errorMessage = sprintf("could not flush file '%s'", $this->_filename);
        }

        if (!@fclose($this->_handle)) {
            $errorMessage = sprintf("failed to close file '%s'", $this->_filename);
        }

        $this->_handle = null;

        if (isset($errorMessage)) {
            throw new File_IOException($errorMessage);
        }
    }

    // }}}
    // {{{ read()

    /**
     * @param  mixed   $numBytes (optional)
     * @return mixed
     * @since  1.0
     * @throws File_IOException
     */
    public function read($numBytes = null)
    {
        if (preg_match('/^[waxc][bt]?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for reading", $this->_filename));
        }

        if ($numBytes !== null) {
            $data = @fread($this->_handle, (integer)$numBytes);
        } else {
            $data = @fgets($this->_handle);
        }

        if ($data === false) {
            if (feof($this->_handle)) {
                return null;
            }

            throw new File_IOException(sprintf("could not read from file '%s'", $this->_filename));
        }

        return $data;
    }

    // }}}
    // {{{ readAll()

    /**
     * @return mixed
     * @since  1.0
     * @throws File_IOException
     */
    public function readAll()
    {
        if (($numBytes = @filesize($this->_filename)) === false) {
            throw new File_IOException(sprintf("could not read from file '%s'", $this->_filename));
        }

        return $this->read($numBytes);
    }

    // }}}
    // {{{ write()

    /**
     * @param  string  $data
     * @param  mixed   $numBytes (optional)
     * @return integer
     * @since  1.0
     * @throws File_IOException
     */
    public function write($data, $numBytes = null)
    {
        if (preg_match('/^[r][bt]?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for writing", $this->_filename));
        }

        if ($numBytes !== null) {
            $numBytesWritten = @fwrite($this->_handle, (string)$data, (integer)$numBytes);
        } else {
            $numBytesWritten = @fwrite($this->_handle, (string)$data);
        }

        if ($numBytesWritten === false) {
            throw new File_IOException(sprintf("could not write to file '%s'", $this->_filename));
        }

        return $numBytesWritten;
    }

    // }}}
    // {{{ writeAll()

    /**
     * @param  string  $data
     * @return integer
     * @since  1.0
     * @throws File_IOException
     */
    public function writeAll($data)
    {
        return $this->write($data);
    }

    // }}}
    // {{{ seek()

    /**
     * Sets the file position indicator to the given value
     *
     * @param  integer $offset The position relative to $origin from which to begin seeking
     * @param  integer $origin (optional) Position used as reference for the $offset
     * @return void
     * @since  1.0
     * @throws File_IOException
     */
    public function seek($offset, $origin = self::SEEK_SET)
    {
        if (@fseek($this->_handle, (integer)$offset, (integer)$origin) == -1) {
            throw new File_IOException(sprintf("could not seek to requested position in file '%s'", $this->_filename));
        }
    }

    // }}}
    // {{{ getIterator()

    /**
     * @return void
     * @since  1.0
     * @throws BadMethodCallException
     */
    public function getIterator()
    {
        throw new BadMethodCallException(sprintf('method %s() is not implemented', __METHOD__));
    }

    // }}}
    // {{{ parse()

    /**
     * Parses file and replaces all occurencies of keys to it values specified in
     * $values array.
     *
     * For example, we have the following array:
     *
     * $values = array(
     *     "%firstName" => "Vitaly",
     *     "%lastName"  => "Doroshko"
     * );
     *
     * The function replaces all occurencies of '%firstName' to 'Vitaly' and
     * '%lastName' will be replace to 'Doroshko'.
     *
     * Feel free to use in your own scripts.
     *
     * @param    string    $fileName
     * @param    array     $values
     * @return   string
     * @since    1.0.0
     * @throws   File_IOException
     */
    public static function parse($fileName, $values)
    {
        $contents = self::open($fileName)->readAll();
        return str_replace(array_keys((array)$values), array_values((array)$values),
                           (string)$contents);
    }

    // }}}
    // {{{ getOption()

    /**
     * @param  string  $name
     * @return mixed
     * @since  1.0
     * @throws OutOfBoundsException
     */
    public function getOption($name)
    {
        if (!array_key_exists((string)$name, $this->_options)) {
            throw new OutOfBoundsException(sprintf("unknown option '%s'", $name));
        }

        return $this->_options[(string)$name];
    }

    // }}}
    // {{{ getOptions()

    /**
     * @param  array   $names (optional)
     * @return array
     * @since  1.0
     * @throws OutOfBoundsException
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
     * @param  string  $name
     * @param  mixed   $value
     * @return void
     * @since  1.0
     * @throws OutOfBoundsException
     */
    public function setOption($name, $value)
    {
        if (!array_key_exists((string)$name, $this->_options)) {
            throw new OutOfBoundsException(sprintf("unknown option '%s'", $name));
        }

        switch (gettype($this->_options[(string)$name])) {
            case 'NULL': case 'array':
                $this->_options[(string)$name] = $value;
                break;

            case 'integer':
                $this->_options[(string)$name] = (integer)$value;
                break;

            default:
                $this->_options[(string)$name] = (string)$value;
        }
    }

    // }}}
    // {{{ setOptions()

    /**
     * @param  array   $options
     * @return void
     * @since  1.0
     * @throws OutOfBoundsException
     */
    public function setOptions($options)
    {
        foreach ((array)$options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    // }}}
    // {{{ _registerOption()

    /**
     * @param  string  $name
     * @param  mixed   $value (optional)
     * @return void
     * @since  1.0
     * @throws DomainException
     */
    protected function _registerOption($name, $value = null)
    {
        if (array_key_exists($name, (array)$this->_options)) {
            throw new DomainException(sprintf("option '%s' already exists", $name));
        }

        $this->_options[$name] = $value;
    }

    // }}}
    // {{{ __get()

    /**
     * @param  string  $name
     * @return mixed
     * @since  1.0
     * @throws OutOfRangeException
     */
    public function __get($name)
    {
        if (!array_key_exists(sprintf('_%s', $name), get_class_vars(__CLASS__))) {
            throw new OutOfRangeException(sprintf("property '%s' does not exist", $name));
        }

        return $this->{sprintf('_%s', $name)};
    }

    // }}}
    // {{{ __set()

    /**
     * @param  string  $name
     * @param  mixed   $value
     * @return void
     * @since  1.0
     * @throws LogicException
     * @throws OutOfRangeException
     */
    public function __set($name, $value)
    {
        if (array_key_exists(sprintf('_%s', $name), get_class_vars(__CLASS__))) {
            throw new LogicException(sprintf("'%s' property is read-only", $name));
        } else {
            throw new OutOfRangeException(sprintf("property '%s' does not exist", $name));
        }
    }

    // }}}
    // {{{ __isset()

    /**
     * @param  string  $name
     * @return boolean
     * @since  1.0
     */
    public function __isset($name)
    {
        return array_key_exists(sprintf('_%s', $name), get_class_vars(__CLASS__));
    }

    // }}}
    // {{{ __unset()

    /**
     * @param  string  $name
     * @return void
     * @since  1.0
     * @throws LogicException
     * @throws OutOfRangeException
     */
    public function __unset($name)
    {
        if (array_key_exists(sprintf('_%s', $name), get_class_vars(__CLASS__))) {
            throw new LogicException(sprintf("property '%s' cannot be unset", $name));
        } else {
            throw new OutOfRangeException(sprintf("property '%s' does not exist", $name));
        }
    }

    // }}}
}

// }}}
// {{{ class File_IOException

/**
 * Exception class that is thrown when an I/O failure occurs
 *
 * @category   File System
 * @package    File
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2005-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_IOException extends RuntimeException {}

// }}}
// {{{ class File_OptionValueException

/**
 * Exception class that is thrown when an option value is not valid
 *
 * @category   File System
 * @package    File
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2005-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_OptionValueException extends UnexpectedValueException {}

// }}}
// {{{ class File_UnsupportedOperationException

/**
 * Exception class that is thrown to indicate that the requested operation is
 * not supported
 *
 * @category   File System
 * @package    File
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2005-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_UnsupportedOperationException extends LogicException {}

// }}}
// }}}

?>
