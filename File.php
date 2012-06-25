<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Contains the \Lazy\File classes
 *
 * PHP versions 5
 *
 * LICENSE: Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    File System
 * @package     File
 * @author      Vitaly Doroshko <vdoroshko@gmail.com>
 * @copyright   2005-2012 Vitaly Doroshko
 * @license     http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version     SVN: $Id$
 * @link
 */

namespace Lazy;

// {{{ classes
// {{{ class \Lazy\File

class File implements \Iterator
{
    // {{{ protected class vars

    /**
     * @access  protected
     * @var     string
     */
    protected $_filename;

    /**
     * @access  protected
     * @var     resource
     */
    protected $_handle;

    // }}}
    // {{{ constructor

    /**
     * @access  public
     * @param   string  $filename
     * @param   string  $mode
     * @param   boolean $useIncludePath
     * @throws  \Lazy\IOException
     * @link    http://stackoverflow.com/questions/321979/how-do-you-determine-if-a-variable-contains-a-file-pointer-in-php
     */
    public function __construct($filename, $mode = 'r', $useIncludePath = false)
    {
        $this->_handle = @fopen((string)$filename, (string)$mode, (boolean)$useIncludePath);
        if (!$this->_handle) {
            throw new IOException(sprintf('Could not open file "%s"', $filename));
        }

        $this->_filename = (string)$filename;
    }

    // }}}
    // {{{ open()

    /**
     * @access  public
     * @param   string  $filename
     * @param   string  $mode
     * @param   boolean $useIncludePath
     * @return  \Lazy\File
     * @throws  \Lazy\IOException
     */
    public static function open($filename, $mode = 'r', $useIncludePath = false)
    {
        static $object;

        if (!is_a($object, get_class())) { //makes sure object is created only once
            $object = new static($filename, $mode, $useIncludePath);
        }

        return $object;
    }

    // }}}
    // {{{ readAll()

    /**
     * @access  public
     * @return  string
     * @throws  \Lazy\IOException
     */
    public function readAll()
    {
        $contents = @fread($this->_handle, filesize($this->_filename));
        if (false === $contents) {
            throw new IOException(sprintf('Could not read file "%s"',
                                             $this->_filename));
        }

        return $contents;
    }

    // }}}
    // {{{ current()

    /**
     * @access  public
     * @return  void
     */
    public function current() {}

    // }}}
    // {{{ key()

    /**
     * @access  public
     * @return  void
     */
    public function key() {}

    // }}}
    // {{{ next()

    /**
     * @access  public
     * @return  void
     */
    public function next() {}

    // }}}
    // {{{ rewind()

    /**
     * @access  public
     * @return  void
     * @throws  \Lazy\IOException
     */
    public function rewind()
    {
        if (fseek($this->_handle, 0) == -1) {
            throw new IOException(sprintf('Error seeking file "%s".', $this->_filename));
        }
    }

    // }}}
    // {{{ valid()

    /**
     * @access  public
     * @return  void
     */
    public function valid() {}

    // }}}
}

// }}}
// {{{ class \Lazy\IOException

class IOException extends \RuntimeException {}

// }}}
// }}}

?>
