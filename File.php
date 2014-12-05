<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Generic file handling class
 *
 * PHP version 5
 *
 * Copyright (c) 2005-2014, Vitaly Doroshko
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
 * @category   File System
 * @package    File
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2005-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
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
     * Path to a file
     *
     * @var    string
     * @since  1.0
     */
    protected $_path;

    /**
     * File access mode
     *
     * @var    string
     * @since  1.0
     */
    protected $_mode;

    /**
     * File pointer
     *
     * @var    resource
     * @since  1.0
     */
    protected $_handle;

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
     * Constructs a new File object and opens a file on the specified path for
     * reading and/or writing
     *
     * @param  string  $path The file to open
     * @param  string  $mode The file access mode
     * @param  array   $options The runtime configuration options
     * @throws DomainException
     * @throws File_NotFoundException
     * @throws File_IOException
     */
    protected function __construct($path, $mode, $options)
    {
        if (!preg_match('/[rwaxc][bt]?\+?/', (string)$mode)) {
            throw new DomainException(sprintf("invalid access mode '%s'", $mode));
        }

        if (isset($options['useIncludePath'])) {
            $useIncludePath = (boolean)$options['useIncludePath'];
        } else {
            $useIncludePath = false;
        }

        if (!($handle = @fopen((string)$path, (string)$mode, $useIncludePath))) {
            if (!self::exists($path, $useIncludePath)) {
                throw new File_NotFoundException(sprintf("file '%s' not found", $path));
            }

            throw new File_IOException(sprintf("could not open file '%s'", $path));
        }

        $this->_path   = (string)$path;
        $this->_mode   = (string)$mode;
        $this->_handle = $handle;

        $this->_registerOption('useIncludePath', false);
        $this->_registerOption('encoding');
        $this->_registerOption('filter');

        $this->setOptions($options);
    }

    // }}}
    // {{{ destructor

    /**
     * @throws File_IOException
     * @since  1.0
     */
    public function __destruct()
    {
        $this->close();
    }

    // }}}
    // {{{ open()

    /**
     * Creates a new File object and opens a file on the specified path for
     * reading and/or writing
     *
     * @param  string  $path The file to open
     * @param  string  $mode (optional) The file access mode
     * @param  array   $options (optional) The runtime configuration options
     * @return object  A new File object
     * @throws DomainException
     * @throws File_NotFoundException
     * @throws File_IOException
     * @since  1.0
     */
    public static function open($path, $mode = 'r', $options = array())
    {
        static $instances;

        if (empty($instances[(string)$path])) {
            $instances[(string)$path] = new self($path, $mode, $options);
        } elseif (empty($instances[(string)$path]->handle)) {
            $instances[(string)$path] = new self($path, $mode, $options);
        }

        return $instances[(string)$path];
    }

    // }}}
    // {{{ createTemporary()

    /**
     * Creates a file with unique name in the specified directory and returns a
     * new File object associated with the file
     *
     * @param  string  $dir The directory where the temporary file should be created
     * @param  string  $prefix The prefix of the temporary file name
     * @param  string  $mode (optional) The file access mode
     * @param  array   $options (optional) The runtime configuration options
     * @return object  A new File object
     * @throws DomainException
     * @throws File_NotFoundException
     * @throws File_IOException
     * @since  1.0
     */
    public static function createTemporary($dir, $prefix = '', $mode = 'w', $options = array())
    {
        if (!self::exists($dir)) {
            throw new File_NotFoundException(sprintf("directory '%s' does not exist", $dir));
        }

        if (!($path = @tempnam((string)$dir, (string)$prefix))) {
            throw new File_IOException(sprintf("could not create temporary file in directory '%s'", $dir));
        }

        return self::open($path, $mode, $options);
    }

    // }}}
    // {{{ close()

    /**
     * Closes the file
     *
     * @return void
     * @throws File_IOException
     * @since  1.0
     */
    public function close()
    {
        if (empty($this->_handle)) {
            return;
        }

        if (!@fflush($this->_handle)) {
            $errorMessage = sprintf("could not flush file '%s'", $this->_path);
        }

        if (!@fclose($this->_handle)) {
            $errorMessage = sprintf("failed to close file '%s'", $this->_path);
        }

        $this->_handle = null;

        if (isset($errorMessage)) {
            throw new File_IOException($errorMessage);
        }
    }

    // }}}
    // {{{ read()

    /**
     * Reads either a line or the specified number of bytes from the file
     *
     * @param  mixed   $numBytes (optional) The maximum number of bytes to read
     * @return mixed   The read string or null if the end of the file has been
     *                 reached
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function read($numBytes = null)
    {
        if (preg_match('/^[waxc][bt]?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for reading", $this->_path));
        }

        if ($numBytes !== null) {
            $str = @fread($this->_handle, (integer)$numBytes);
        } else {
            $str = @fgets($this->_handle);
        }

        if ($str === false) {
            if (feof($this->_handle)) {
                return null;
            }

            throw new File_IOException(sprintf("could not read from file '%s'", $this->_path));
        }

        if ($this->_options['encoding']) {
            $str = $this->_convertEncoding($str, $this->_options['encoding']);
        }

        if (is_callable($this->_options['filter'])) {
            $str = $this->_applyFilter($str);
        }

        return $str;
    }

    // }}}
    // {{{ readAll()

    /**
     * Reads the entire file into a string
     *
     * @return mixed   The read string or null if the end of the file has been
     *                 reached
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function readAll()
    {
        if (($numBytes = @filesize($this->_path)) === false) {
            throw new File_IOException(sprintf("could not read from file '%s'", $this->_path));
        }

        return $this->read($numBytes);
    }

    // }}}
    // {{{ write()

    /**
     * Writes either the entire given string or the specified number of bytes of
     * the given string to the file
     *
     * @param  string  $str The string to be written
     * @param  mixed   $numBytes (optional) The maximum number of bytes to be written
     * @return integer The number of bytes written
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function write($str, $numBytes = null)
    {
        if (preg_match('/^[r][bt]?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for writing", $this->_path));
        }

        if (is_callable($this->_options['filter'])) {
            $str = $this->_applyFilter($str);
        }

        if ($this->_options['encoding']) {
            $str = $this->_convertEncoding($str, 'utf-8');
        }

        if ($numBytes !== null) {
            $numBytesWritten = @fwrite($this->_handle, (string)$str, (integer)$numBytes);
        } else {
            $numBytesWritten = @fwrite($this->_handle, (string)$str);
        }

        if ($numBytesWritten === false) {
            throw new File_IOException(sprintf("could not write to file '%s'", $this->_path));
        }

        return $numBytesWritten;
    }

    // }}}
    // {{{ writeAll()

    /**
     * Writes the given string to the file
     *
     * @param  string  $str The string to be written
     * @return integer The number of bytes written
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function writeAll($str)
    {
        return $this->write($str);
    }

    // }}}
    // {{{ seek()

    /**
     * Sets the file position indicator to the given value
     *
     * @param  integer $offset The offset to seek to
     * @param  integer $whence (optional) The position from which to apply the offset
     * @return void
     * @throws File_IOException
     * @since  1.0
     */
    public function seek($offset, $whence = self::SEEK_SET)
    {
        if (@fseek($this->_handle, (integer)$offset, (integer)$whence) == -1) {
            throw new File_IOException(sprintf("could not seek to requested offset in file '%s'", $this->_path));
        }
    }

    // }}}
    // {{{ exists()

    /**
     * Checks if the specified file or directory exists
     *
     * @param  string  $path The file or directory to check
     * @param  boolean $useIncludePath (optional) Whether to check in include path too
     * @return boolean true if the file or directory exists or false otherwise
     * @since  1.0
     */
    public static function exists($path, $useIncludePath = false)
    {
        if (@file_exists((string)$path)) {
            return true;
        }

        if ((boolean)$useIncludePath) {
            $includePath = explode(PATH_SEPARATOR, get_include_path());
            foreach ($includePath as $includePathItem) {
                if (@file_exists($includePathItem . DIRECTORY_SEPARATOR . (string)$path)) {
                    return true;
                }
            }
        }

        return false;
    }

    // }}}
    // {{{ unlink()

    /**
     * Deletes the specified file
     *
     * @param  string  $path The file to delete
     * @return void
     * @throws File_NotFoundException
     * @throws File_IOException
     * @since  1.0
     */
    public static function unlink($path)
    {
        if (!self::exists($path)) {
            throw new File_NotFoundException(sprintf("file '%s' not found", $path));
        }

        if (!@unlink((string)$path)) {
            throw new File_IOException(sprintf("could not delete file '%s'", $path));
        }
    }

    // }}}
    // {{{ getIterator()

    /**
     * Returns an iterator to traverse the lines in the text file opened for
     * reading
     *
     * @return object  A File_Iterator object
     * @throws File_UnsupportedOperationException
     * @since  1.0
     */
    public function getIterator()
    {
        return new File_Iterator($this);
    }

    // }}}
    // {{{ getName()

    /**
     * Returns the name of the file without path
     *
     * @return string  The name of the file without path
     * @since  1.0
     */
    public function getName()
    {
        return basename($this->_path);
    }

    // }}}
    // {{{ getPath()

    /**
     * Returns the path to the file
     *
     * @return string  The path to the file
     * @since  1.0
     */
    public function getPath()
    {
        return $this->_path;
    }

    // }}}
    // {{{ getMode()

    /**
     * Returns the file access mode
     *
     * @return string  The file access mode
     * @since  1.0
     */
    public function getMode()
    {
        return $this->_mode;
    }

    // }}}
    // {{{ getHandle()

    /**
     * Returns the file pointer
     *
     * @return mixed   The file pointer or null if the file has been closed
     * @since  1.0
     */
    public function getHandle()
    {
        return $this->_handle;
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

        switch (gettype($this->_options[(string)$name])) {
            case 'NULL':
            case 'array':
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
    // {{{ _convertEncoding()

    /**
     * Converts the given string to the encoding specified by the 'encoding'
     * runtime configuration option
     *
     * @param  string  $str The string to convert
     * @param  string  $encoding The string encoding
     * @return string  The converted string
     * @throws File_EncodingException
     * @since  1.0
     */
    protected function _convertEncoding($str, $encoding)
    {
        if (in_array(strtolower($encoding), array('utf-8', 'utf8'))) {
            return $str;
        }

        if (($str = iconv($encoding, $encoding == $this->_options['encoding'] ? 'utf-8' : $this->_options['encoding'], (string)$str)) === false) {
            throw new File_EncodingException(sprintf("'%s' is not valid encoding", $this->_options['encoding']));
        }

        return $str;
    }

    // }}}
    // {{{ _applyFilter()

    /**
     * Applies the callback function specified by the 'filter' runtime
     * configuration option to the given string
     *
     * @param  string  $str The string to apply the callback filter function to
     * @return string  The string after applying the callback filter function
     * @throws File_FilterException
     * @since  1.0
     */
    protected function _applyFilter($str)
    {
        if (($str = call_user_func($this->_options['filter'], (string)$str)) === false) {
            throw new File_FilterException(sprintf('function %s() does not exist or could not be called', $this->_options['filter']));
        }

        return $str;
    }

    // }}}
}

// }}}
// {{{ class File_Iterator

/**
 * Iterator class for traversing lines in a text file
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
class File_Iterator implements Iterator
{
    // {{{ protected class properties

    /**
     * File object
     *
     * @var    object
     * @since  1.0
     */
    protected $_file;

    /**
     * Current line
     *
     * @var    mixed
     * @since  1.0
     */
    protected $_currentLine;

    /**
     * Current line number
     *
     * @var    integer
     * @since  1.0
     */
    protected $_lineNumber;

    // }}}
    // {{{ constructor

    /**
     * Constructs a new File_Iterator object
     *
     * @param  object  $file The File object to traverse
     * @throws File_UnsupportedOperationException
     */
    public function __construct(File $file)
    {
        if (preg_match('/^[waxc][bt]?$/', $file->getMode())) {
            throw new File_UnsupportedOperationException(sprintf("file '%s' is not open for reading", $file->getPath()));
        }

        $this->_file = $file;
    }

    // }}}
    // {{{ rewind()

    /**
     * Rewinds the iterator to the beginning of the file
     *
     * @return void
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function rewind()
    {
        $this->_file->seek(0);

        $this->_currentLine = $this->_file->read();
        $this->_lineNumber = 0;
    }

    // }}}
    // {{{ valid()

    /**
     * Checks if the end of the file has not been reached
     *
     * @return boolean true if the end of the file has not been reached or false
     *                 otherwise
     * @since  1.0
     */
    public function valid()
    {
        return $this->_currentLine !== null;
    }

    // }}}
    // {{{ key()

    /**
     * Returns the current line number in the file
     *
     * @return integer The current line number
     * @since  1.0
     */
    public function key()
    {
        return $this->_lineNumber;
    }

    // }}}
    // {{{ current()

    /**
     * Returns the current line from the file
     *
     * @return string  The current line
     * @since  1.0
     */
    public function current()
    {
        return $this->_currentLine;
    }

    // }}}
    // {{{ next()

    /**
     * Moves the iterator to the next line in the file
     *
     * @return void
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function next()
    {
        $this->_currentLine = $this->_file->read();
        $this->_lineNumber++;
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
// {{{ class File_EncodingException

/**
 * Exception class that is thrown when a character encoding or decoding error
 * occurs
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
class File_EncodingException extends File_IOException {}

// }}}
// {{{ class File_FilterException

/**
 * Exception class that is thrown when a callback filter function execution
 * failed
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
class File_FilterException extends File_IOException {}

// }}}
// {{{ class File_NotFoundException

/**
 * Exception class that is thrown when an attempt to access a file that does
 * not exist fails
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
class File_NotFoundException extends File_IOException {}

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
class File_UnsupportedOperationException extends File_IOException {}

// }}}
// }}}

?>
