<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * CSV file reading and writing class
 *
 * PHP version 5
 *
 * Copyright (c) 2011-2015, Vitaly Doroshko
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
 * @category   File Formats
 * @package    File_CSV
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2011-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
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
 * @copyright  2011-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_CSV extends File
{
    // {{{ constructor

    /**
     * Constructs a new File_CSV object and opens a CSV file on the specified
     * path either for reading or for writing
     *
     * @param  string  $filename The filename of the file to open
     * @param  string  $mode The file access mode
     * @param  array   $options The runtime configuration options
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws LengthException
     * @throws File_InvalidPathException
     * @throws File_NotFoundException
     * @throws File_IOException
     */
    protected function __construct($filename, $mode, $options)
    {
        if (empty($filename)) {
            throw new File_InvalidPathException('filename cannot be empty');
        }

        if (!preg_match('/^(r|[waxc]).?$/', (string)$mode)) {
            throw new DomainException('access mode can be either read or write');
        }

        if (!preg_match('/^.[bt]?$/', (string)$mode)) {
            throw new DomainException('file can be opened either in text mode or in binary mode');
        }

        $this->_registerOption('length', null);
        $this->_registerOption('delimiter', ',');
        $this->_registerOption('enclosure', '"');

        parent::__construct($filename, $mode, $options);
    }

    // }}}
    // {{{ open()

    /**
     * Creates a new File_CSV object and opens a CSV file on the specified path
     * either for reading or for writing
     *
     * @param  string  $filename The filename of the file to open
     * @param  string  $mode (optional) The file access mode
     * @param  array   $options (optional) The runtime configuration options
     * @return object  A new File_CSV object
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws File_InvalidPathException
     * @throws File_NotFoundException
     * @throws File_IOException
     * @since  1.0
     */
    public static function open($filename, $mode = 'r', $options = array())
    {
        static $instances;

        if (empty($instances[(string)$filename])) {
            $instances[(string)$filename] = new self($filename, $mode, $options);
        } elseif ($instances[(string)$filename]->getHandle() === null) {
            $instances[(string)$filename] = new self($filename, $mode, $options);
        }

        return $instances[(string)$filename];
    }

    // }}}
    // {{{ createTemporary()

    /**
     * Creates a CSV file with unique name in the specified directory and returns
     * a new File_CSV object associated with the CSV file
     *
     * @param  string  $dir The directory where the temporary file should be created
     * @param  string  $prefix The prefix of the temporary file name
     * @param  string  $mode (optional) The file access mode
     * @param  array   $options (optional) The runtime configuration options
     * @return object  A new File_CSV object
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws File_InvalidPathException
     * @throws File_NotFoundException
     * @throws File_IOException
     * @since  1.0
     */
    public static function createTemporary($dir, $prefix = '', $mode = 'w', $options = array())
    {
        if (empty($dir)) {
            throw new File_InvalidPathException('directory path cannot be empty');
        }

        if (!self::exists($dir)) {
            throw new File_NotFoundException(sprintf("directory '%s' does not exist", $dir));
        }

        if (($filename = @tempnam((string)$dir, (string)$prefix)) === false) {
            throw new File_IOException(sprintf("could not create temporary file in directory '%s'", $dir));
        }

        return self::open($filename, $mode, $options);
    }

    // }}}
    // {{{ read()

    /**
     * Reads a row from the CSV file into an array
     *
     * @return mixed   The read array or null if the end of the CSV file has been
     *                 reached
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function read()
    {
        return $this->_getRow();
    }

    // }}}
    // {{{ readAll()

    /**
     * Reads the entire CSV file into a multidimensional array
     *
     * @return array   The read multidimensional array or null if the end of the
     *                 CSV file has been reached
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function readAll()
    {
        $rows = array();
        while (($row = $this->_getRow()) !== null) {
            $rows[] = $row;
        }

        return $rows;
    }

    // }}}
    // {{{ write()

    /**
     * Writes the given array to the CSV file
     *
     * @param  array   $row The array to be written
     * @return integer The number of bytes written
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function write($row)
    {
        return $this->_putRow($row);
    }

    // }}}
    // {{{ writeAll()

    /**
     * Writes the given multidimensional array to the CSV file
     *
     * @param  array   $rows The multidimensional array to be written
     * @return integer The number of bytes written
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function writeAll($rows)
    {
        $numBytesWritten = 0;
        foreach ((array)$rows as $row) {
            $numBytesWritten += $this->_putRow($row);
        }

        return $numBytesWritten;
    }

    // }}}
    // {{{ getIterator()

    /**
     * Returns an iterator to traverse the rows in the CSV file opened for reading
     *
     * @return object  A File_CSV_Iterator object
     * @throws File_UnsupportedOperationException
     * @since  1.0
     */
    public function getIterator()
    {
        return new File_CSV_Iterator($this);
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
     * @throws LengthException
     * @since  1.0
     */
    public function setOption($name, $value)
    {
        if (!array_key_exists((string)$name, $this->_options)) {
            throw new DomainException(sprintf("unknown option '%s'", $name));
        }

        switch ((string)$name) {
            case 'length':
                if ($value) {
                    if ((integer)$value < 0) {
                        throw new LengthException('length cannot be negative');
                    }

                    $this->_options[(string)$name] = (integer)$value;
                } else {
                    $this->_options[(string)$name] = null;
                }

                break;

            case 'delimiter':
            case 'enclosure':
                if (empty($value)) {
                    throw new InvalidArgumentException(sprintf('%s cannot be empty', $name));
                }

                $this->_options[(string)$name] = substr((string)$value, 0, 1);
                break;

            default:
                parent::setOption($name, $value);
        }
    }

    // }}}
    // {{{ _getRow()

    /**
     * Reads a row from the CSV file into an array
     *
     * @return mixed   The read array or null if the end of the CSV file has been
     *                 reached
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    protected function _getRow()
    {
        if (empty($this->_handle)) {
            throw new File_IOException(sprintf("attempt to read from closed file '%s'", $this->_path));
        }

        if (!preg_match('/^r.?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for reading", $this->_path));
        }

        $row = @fgetcsv(
            $this->_handle,
            (integer)$this->_options['length'],
            $this->_options['delimiter'],
            $this->_options['enclosure']
        );

        if (empty($row)) {
            if (feof($this->_handle)) {
                return null;
            }

            throw new File_IOException(sprintf("could not read from file '%s'", $this->_path));
        }

        if ($this->_options['encoding']) {
            $row = $this->_convertEncoding($row, 'utf-8');
        }

        if ($this->_options['filter']) {
            $row = $this->_applyFilter($row);
        }

        return $row;
    }

    // }}}
    // {{{ _putRow()

    /**
     * Writes the given array to the CSV file
     *
     * @param  array   $row The array to be written
     * @return integer The number of bytes written
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    protected function _putRow($row)
    {
        if (empty($this->_handle)) {
            throw new File_IOException(sprintf("attempt to write to closed file '%s'", $this->_path));
        }

        if (!preg_match('/^[waxc].?$/', $this->_mode)) {
            throw new File_IOException(sprintf("file '%s' is not open for writing", $this->_path));
        }

        if ($this->_options['filter']) {
            $row = $this->_applyFilter((array)$row);
        }

        if ($this->_options['encoding']) {
            $row = $this->_convertEncoding((array)$row, $this->_options['encoding']);
        }

        $numBytesWritten = @fputcsv(
            $this->_handle,
            (array)$row,
            $this->_options['delimiter'],
            $this->_options['enclosure']
        );

        if ($numBytesWritten === false) {
            throw new File_IOException(sprintf("could not write to file '%s'", $this->_path));
        }

        return $numBytesWritten;
    }

    // }}}
    // {{{ _convertEncoding()

    /**
     * Converts the elements of the given array from/to the encoding specified by
     * the 'encoding' runtime configuration option
     *
     * @param  array   $row The array to convert
     * @param  string  $encoding The encoding to convert the elements of the array to
     * @return array   The converted array
     * @throws File_EncodingException
     * @since  1.0
     */
    protected function _convertEncoding($row, $encoding)
    {
        if (in_array(strtolower($this->_options['encoding']), array('utf-8', 'utf8'))) {
            return $row;
        }

        foreach ($row as &$value) {
            if ($encoding == $this->_options['encoding']) {
                if (($value = iconv('utf-8', $this->_options['encoding'], (string)$value)) === false) {
                    throw new File_EncodingException(sprintf("'%s' is not valid encoding", $this->_options['encoding']));
                }
            } else {
                if (($value = iconv($this->_options['encoding'], 'utf-8', (string)$value)) === false) {
                    throw new File_EncodingException(sprintf("'%s' is not valid encoding", $this->_options['encoding']));
                }
            }
        }

        return $row;
    }

    // }}}
    // {{{ _applyFilter()

    /**
     * Applies the callback function specified by the 'filter' runtime
     * configuration option to the elements of the given array
     *
     * @param  array   $row The array to apply the callback filter function to
     * @return array   The array after applying the callback filter function
     * @throws File_FilterException
     * @since  1.0
     */
    protected function _applyFilter($row)
    {
        if (!is_callable($this->_options['filter'])) {
            throw new File_FilterException('filter is not a valid callback');
        }

        foreach ($row as &$value) {
            if (($value = @call_user_func($this->_options['filter'], (string)$value)) === false) {
                throw new File_FilterException('failed to call filter callback');
            }
        }

        return $row;
    }

    // }}}
}

// }}}
// {{{ class File_CSV_Iterator

/**
 * Iterator class for traversing rows in a CSV file
 *
 * @category   File Formats
 * @package    File_CSV
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2011-2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class File_CSV_Iterator implements Iterator
{
    // {{{ protected class properties

    /**
     * File_CSV object
     *
     * @var    object
     * @since  1.0
     */
    protected $_csv;

    /**
     * Current row data
     *
     * @var    mixed
     * @since  1.0
     */
    protected $_row;

    /**
     * Current row number
     *
     * @var    integer
     * @since  1.0
     */
    protected $_rowNumber;

    // }}}
    // {{{ constructor

    /**
     * Constructs a new File_CSV_Iterator object
     *
     * @param  object  $csv The File_CSV object to traverse
     * @throws File_UnsupportedOperationException
     */
    public function __construct(File_CSV $csv)
    {
        if (!preg_match('/^r.?$/', $csv->getMode())) {
            throw new File_UnsupportedOperationException(sprintf("file '%s' is not open for reading", $csv->getPath()));
        }

        $this->_csv = $csv;
    }

    // }}}
    // {{{ rewind()

    /**
     * Rewinds the iterator to the beginning of the CSV file
     *
     * @return void
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function rewind()
    {
        $this->_csv->seek(0);
        $this->_row = $this->_csv->read();
        $this->_rowNumber = 0;
    }

    // }}}
    // {{{ valid()

    /**
     * Checks if the end of the CSV file has not been reached
     *
     * @return boolean true if the end of the CSV file has not been reached or
     *                 false otherwise
     * @since  1.0
     */
    public function valid()
    {
        return $this->_row !== null;
    }

    // }}}
    // {{{ key()

    /**
     * Returns the current row number in the CSV file
     *
     * @return integer The current row number
     * @since  1.0
     */
    public function key()
    {
        return $this->_rowNumber;
    }

    // }}}
    // {{{ current()

    /**
     * Returns the current row from the CSV file
     *
     * @return array   The current row
     * @since  1.0
     */
    public function current()
    {
        return $this->_row;
    }

    // }}}
    // {{{ next()

    /**
     * Moves the iterator to the next row in the CSV file
     *
     * @return void
     * @throws File_EncodingException
     * @throws File_FilterException
     * @throws File_IOException
     * @since  1.0
     */
    public function next()
    {
        $this->_row = $this->_csv->read();
        $this->_rowNumber++;
    }

    // }}}
}

// }}}
// }}}

?>
