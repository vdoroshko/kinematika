<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * CSV file reading and writing class
 *
 * PHP version 5
 *
 * Copyright (c) 2011-2014 Vitaly Doroshko
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
 * @category   File Formats
 * @package    File_CSV
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2011-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 */
require_once 'File.php';

// {{{ classes
// {{{ class File_CSV

/**
 * CSV file reading and writing class
 *
 * @category   File Formats
 * @package    File_CSV
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2011-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_CSV extends File
{
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
        $this->_registerOption('linelen', 2048);
        $this->_registerOption('delimiter', ',');
        $this->_registerOption('enclosure', '"');
        $this->_registerOption('filter');

        parent::__construct($filename, $mode, $options);
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
    // {{{ read()

    /**
     * @return mixed
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    public function read()
    {
        return $this->_getRow();
    }

    // }}}
    // {{{ readAll()

    /**
     * @return array
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    public function readAll()
    {
        $rows = array();
        while (($row = $this->read()) !== null) {
            $rows[] = $row;
        }

        return $rows;
    }

    // }}}
    // {{{ write()

    /**
     * @param  array   $row
     * @return integer Number of bytes written including EOL separator
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    public function write($row)
    {
        return $this->_putRow($row);
    }

    // }}}
    // {{{ writeAll()

    /**
     * @param  array   $rows
     * @return integer Number of bytes written including EOL separators
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    public function writeAll($rows)
    {
        $numBytesWritten = 0;
        foreach ((array)$rows as $row) {
            $numBytesWritten += $this->write($row);
        }

        return $numBytesWritten;
    }

    // }}}
    // {{{ getIterator()

    /**
     * @return object
     * @since  1.0
     * @throws File_UnsupportedOperationException
     */
    public function getIterator()
    {
        return new File_CSV_Iterator($this);
    }

    // }}}
    // {{{ _getRow()

    /**
     * @return mixed
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    protected function _getRow()
    {
        if (preg_match('/^[waxc][bt]?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for reading", $this->_filename));
        }

        $row = @fgetcsv(
            $this->_handle,
            $this->_options['linelen'],
            $this->_options['delimiter'],
            $this->_options['enclosure']
        );

        if ($row === false) {
            if (feof($this->_handle)) {
                return null;
            }

            throw new File_IOException(sprintf("could not read from file '%s'", $this->_filename));
        }

        if ($this->_options['encoding']) {
            $row = $this->_convertEncoding($row, $this->_options['encoding']);
        }

        if (is_callable($this->_options['filter'])) {
            $row = $this->_applyFilter($row);
        }

        return $row;
    }

    // }}}
    // {{{ _putRow()

    /**
     * @param  array   $row
     * @return integer
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    protected function _putRow($row)
    {
        if (preg_match('/^[r][bt]?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for writing", $this->_filename));
        }

        if (is_callable($this->_options['filter'])) {
            $row = $this->_applyFilter($row);
        }

        if ($this->_options['encoding']) {
            $row = $this->_convertEncoding($row, 'utf8');
        }

        $numBytesWritten = @fputcsv(
            $this->_handle,
            (array)$row,
            $this->_options['delimiter'],
            $this->_options['enclosure']
        );

        if ($numBytesWritten === false) {
            throw new File_IOException(sprintf("could not write to file '%s'", $this->_filename));
        }

        return $numBytesWritten;
    }

    // }}}
    // {{{ _convertEncoding()

    /**
     * @param  array   $row
     * @param  string  $encoding
     * @return array
     * @since  1.0
     * @throws File_OptionValueException
     */
    protected function _convertEncoding($row, $encoding)
    {
        if (in_array(strtolower($encoding), array('utf-8', 'utf8'))) {
            return $row;
        }

        for ($i = 0; $i < count((array)$row); $i++) {
            if (($row[$i] = iconv($encoding, $encoding == $this->_options['encoding'] ? 'utf8' : $this->_options['encoding'], (string)$row[$i])) === false) {
                throw new File_OptionValueException(sprintf("'%s' is not valid encoding", $this->_options['encoding']));
            }
        }

        return $row;
    }

    // }}}
    // {{{ _applyFilter()

    /**
     * @param  array   $row
     * @return array
     * @since  1.0
     * @throws File_OptionValueException
     */
    protected function _applyFilter($row)
    {
        for ($i = 0; $i < count((array)$row); $i++) {
            if (($row[$i] = call_user_func($this->_options['filter'], (string)$row[$i])) === false) {
                throw new File_OptionValueException(sprintf('function %s() does not exist or could not be called', $this->_options['filter']));
            }
        }

        return $row;
    }

    // }}}
}

// }}}
// {{{ class File_CSV_Iterator

/**
 * CSV file iterator class
 *
 * @category   File Formats
 * @package    File_CSV
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2011-2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_CSV_Iterator implements Iterator
{
    // {{{ protected class properties

    /**
     * CSV file object
     *
     * @since  1.0
     * @var    object
     */
    protected $_file;

    /**
     * Current row data
     *
     * @since  1.0
     * @var    mixed
     */
    protected $_currentRow;

    /**
     * Current row number
     *
     * @since  1.0
     * @var    integer
     */
    protected $_rowNumber;

    // }}}
    // {{{ constructor

    /**
     * @param  object  $file
     * @throws File_UnsupportedOperationException
     */
    public function __construct(File_CSV $file)
    {
        if (preg_match('/^[waxc][bt]?$/', $file->mode)) {
            throw new File_UnsupportedOperationException(sprintf("file '%s' is not open for reading", $file->filename));
        }

        $this->_file = $file;
    }

    // }}}
    // {{{ rewind()

    /**
     * @return void
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    public function rewind()
    {
        $this->_file->seek(0);

        $this->_currentRow = $this->_file->read();
        $this->_rowNumber = 0;
    }

    // }}}
    // {{{ valid()

    /**
     * @return boolean
     * @since  1.0
     */
    public function valid()
    {
        return $this->_currentRow !== null;
    }

    // }}}
    // {{{ key()

    /**
     * @return integer
     * @since  1.0
     */
    public function key()
    {
        return $this->_rowNumber;
    }

    // }}}
    // {{{ current()

    /**
     * @return mixed
     * @since  1.0
     */
    public function current()
    {
        return $this->_currentRow;
    }

    // }}}
    // {{{ next()

    /**
     * @return void
     * @since  1.0
     * @throws File_IOException
     * @throws File_OptionValueException
     */
    public function next()
    {
        $this->_currentRow = $this->_file->read();
        $this->_rowNumber++;
    }

    // }}}
}

// }}}
// }}}

?>
