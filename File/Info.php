<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File information class
 *
 * PHP version 5
 *
 * Copyright (c) 2015, Vitaly Doroshko
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
 * @package    File_Info
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
require_once 'File.php';

// {{{ classes
// {{{ class File_Info

/**
 * File information class
 *
 * @category   File System
 * @package    File_Info
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_Info
{
    // {{{ class constants

    /**
     * Regular file
     *
     * @since  1.0
     */
    const TYPE_FILE = 'file';

    /**
     * Directory
     *
     * @since  1.0
     */
    const TYPE_DIR = 'dir';

    /**
     * Symbolic link
     *
     * @since  1.0
     */
    const TYPE_LINK = 'link';

    /**
     * Named pipe
     *
     * @since  1.0
     */
    const TYPE_FIFO = 'fifo';

    /**
     * Socket
     *
     * @since  1.0
     */
    const TYPE_SOCKET = 'socket';

    /**
     * Character serial device
     *
     * @since  1.0
     */
    const TYPE_CHAR = 'char';

    /**
     * Block serial device
     *
     * @since  1.0
     */
    const TYPE_BLOCK = 'block';

    // }}}
    // {{{ protected class properties

    /**
     * Filename of the file
     *
     * @var    string
     * @since  1.0
     */
    protected $_filename;

    /**
     * Path of the parent directory
     *
     * @var    string
     * @since  1.0
     */
    protected $_dirname;

    /**
     * Name of the file without path
     *
     * @var    string
     * @since  1.0
     */
    protected $_basename;

    /**
     * Extension of the file
     *
     * @var    string
     * @since  1.0
     */
    protected $_extension;

    /**
     * Type of the file
     *
     * @var    mixed
     * @since  1.0
     */
    protected $_type;

    /**
     * Size of the file in bytes
     *
     * @var    mixed
     * @since  1.0
     */
    protected $_size;

    /**
     * Unix timestamp of when the file was last accessed
     *
     * @var    integer
     * @since  1.0
     */
    protected $_lastAccessedTime;

    /**
     * Unix timestamp of when the file was last modified
     *
     * @var    integer
     * @since  1.0
     */
    protected $_lastModifiedTime;

    /**
     * Unix timestamp of when the file status was last changed
     *
     * @var    integer
     * @since  1.0
     */
    protected $_lastChangedTime;

    // }}}
    // {{{ constructor

    /**
     * Constructs a new File_Info object and obtains information about a file
     * specified
     *
     * @param  string  $filename The filename of the file to obtain information about
     * @param  boolean $useIncludePath (optional) Whether to search for the file in the include path too
     * @throws File_InvalidPathException
     * @throws File_NotFoundException
     * @throws File_IOException
     */
    public function __construct($filename, $useIncludePath = false)
    {
        if (empty($filename)) {
            throw new File_InvalidPathException('file path cannot be empty');
        }

        if (!@file_exists((string)$filename)) {
            $fileNotFound = true;

            if ($useIncludePath) {
                $includePaths = explode(PATH_SEPARATOR, get_include_path());
                foreach ($includePaths as $includePath) {
                    $this->_filename = $includePath . DIRECTORY_SEPARATOR . (string)$filename;
                    if (@file_exists($this->_filename)) {
                        $fileNotFound = false;
                        break;
                    }
                }
            }

            if ($fileNotFound) {
                throw new File_NotFoundException(sprintf("file '%s' not found", $filename));
            }
        } else {
            $this->_filename = (string)$filename;
        }

        if (($lastDirectorySeparatorPos = strrpos($this->_filename, DIRECTORY_SEPARATOR)) !== false) {
            $this->_dirname = substr($this->_filename, 0, $lastDirectorySeparatorPos);
            $this->_basename = substr($this->_filename, $lastDirectorySeparatorPos + 1);
        } else {
            $this->_dirname = '';
            $this->_basename = $this->_filename;
        }

        $extensionPattern = '/\.([^\.' . preg_quote(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR) . ']+)$/';
        $matches = array();

        if (preg_match($extensionPattern, $this->_filename, $matches)) {
            $this->_extension = $matches[1];
        } else {
            $this->_extension = '';
        }

        if (($this->_type = @filetype($this->_filename)) === false) {
            throw new File_IOException(sprintf("lstat failed for file '%s'", $filename));
        }

        if ($this->_type == self::TYPE_FILE) {
            if (($this->_size = @filesize($this->_filename)) === false) {
                throw new File_IOException(sprintf("stat failed for file '%s'", $filename));
            }
        } elseif ($this->_type == self::TYPE_LINK) {
            $symlinkPath = $this->_filename;
            do {
                if (($targetPath = @readlink($symlinkPath)) === false) {
                    throw new File_IOException(sprintf("readlink failed for file '%s'", $symlinkPath));
                }

                if (($targetType = @filetype($targetPath)) === false) {
                    throw new File_IOException(sprintf("lstat failed for file '%s'", $targetPath));
                }

                $symlinkPath = $targetPath;
            } while ($targetType == self::TYPE_LINK);

            if ($targetType == self::TYPE_FILE) {
                if (($this->_size = @filesize($targetPath)) === false) {
                    throw new File_IOException(sprintf("stat failed for file '%s'", $targetPath));
                }
            }
        } elseif ($this->_type == 'unknown') {
            $this->_type = null;
        }

        if (($this->_lastAccessedTime = @fileatime($this->_filename)) === false) {
            throw new File_IOException(sprintf("stat failed for file '%s'", $filename));
        }

        if (($this->_lastModifiedTime = @filemtime($this->_filename)) === false) {
            throw new File_IOException(sprintf("stat failed for file '%s'", $filename));
        }

        if (($this->_lastChangedTime = @filectime($this->_filename)) === false) {
            throw new File_IOException(sprintf("stat failed for file '%s'", $filename));
        }
    }

    // }}}
    // {{{ __get()

    /**
     * Returns the value of an object property
     *
     * @param  string  $name The name of the property
     * @return mixed   The value of the property
     * @throws File_Info_BadPropertyException
     * @since  1.0
     */
    public function __get($name)
    {
        if (!array_key_exists('_' . $name, get_object_vars($this))) {
            throw new File_Info_BadPropertyException(sprintf("undefined property '%s'", $name));
        }

        return $this->{'_' . $name};
    }

    // }}}
    // {{{ __set()

    /**
     * Prevents the object properties from being modified
     *
     * @param  string  $name The name of the property
     * @param  mixed   $value The value of the property
     * @return void
     * @throws File_Info_BadPropertyException
     * @throws File_Info_ReadOnlyPropertyException
     * @since  1.0
     */
    public function __set($name, $value)
    {
        if (!array_key_exists('_' . $name, get_object_vars($this))) {
            throw new File_Info_BadPropertyException(sprintf("undefined property '%s'", $name));
        }

        throw new File_Info_ReadOnlyPropertyException(sprintf("attempt to set read-only property '%s'", $name));
    }

    // }}}
    // {{{ __isset()

    /**
     * Determines if an object property is set and is not null
     *
     * @param  string  $name The name of the property to check
     * @return boolean true if the property exists and has a value other than null
     *                 or false otherwise
     * @since  1.0
     */
    public function __isset($name)
    {
        if (!array_key_exists('_' . $name, get_object_vars($this))) {
            return false;
        }

        return $this->{'_' . $name} !== null;
    }

    // }}}
    // {{{ __unset()

    /**
     * Prevents the object properties from being unset
     *
     * @param  string  $name The name of the property to unset
     * @return void
     * @throws File_Info_BadPropertyException
     * @throws File_Info_ReadOnlyPropertyException
     * @since  1.0
     */
    public function __unset($name)
    {
        if (!array_key_exists('_' . $name, get_object_vars($this))) {
            throw new File_Info_BadPropertyException(sprintf("undefined property '%s'", $name));
        }

        throw new File_Info_ReadOnlyPropertyException(sprintf("attempt to unset read-only property '%s'", $name));
    }

    // }}}
}

// }}}
// {{{ class File_Info_Exception

/**
 * Base class for all file information exceptions
 *
 * @category   File System
 * @package    File_Info
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_Info_Exception extends LogicException {}

// }}}
// {{{ class File_Info_BadPropertyException

/**
 * Exception class that is thrown when there is an attempt to access a
 * file information property that does not exist
 *
 * @category   File System
 * @package    File_Info
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_Info_BadPropertyException extends File_Info_Exception {}

// }}}
// {{{ class File_Info_ReadOnlyPropertyException

/**
 * Exception class that is thrown when there is an attempt to modify or unset
 * a file information property
 *
 * @category   File System
 * @package    File_Info
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_Info_ReadOnlyPropertyException extends File_Info_Exception {}

// }}}
// }}}

?>
