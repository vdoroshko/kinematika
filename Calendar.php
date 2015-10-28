<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Calendar helper class
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
 * @category   Date and Time
 * @package    Calendar
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */

// {{{ classes
// {{{ class Calendar

/**
 * Calendar helper class
 *
 * @category   Date and Time
 * @package    Calendar
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class Calendar
{
    // {{{ protected class properties

    /**
     * Month of the calendar
     *
     * @var    integer
     * @since  1.0
     */
    protected $_month;

    /**
     * Year of the calendar
     *
     * @var    integer
     * @since  1.0
     */
    protected $_year;

    /**
     * First day of the week of the calendar
     *
     * @var    integer
     * @since  1.0
     */
    protected $_firstDayOfWeek;

    /**
     * Unix timestamp for the first day of the calendar
     *
     * @var    integer
     * @since  1.0
     */
    protected $_firstDayTime;

    /**
     * Unix timestamp for the last day of the calendar
     *
     * @var    integer
     * @since  1.0
     */
    protected $_lastDayTime;

    /**
     * Unix timestamp for the first day of the month
     *
     * @var    integer
     * @since  1.0
     */
    protected $_firstDayOfMonthTime;

    /**
     * Unix timestamp for the first day of the week
     *
     * @var    integer
     * @since  1.0
     */
    protected $_firstDayOfWeekTime;

    // }}}
    // {{{ constructor

    /**
     * Constructs a new Calendar object
     *
     * @param  integer $month (optional) The month of the calendar
     * @param  integer $year (optional) The year of the calendar
     * @param  integer $firstDayOfWeek (optional) The first day of the week of the calendar
     * @throws OutOfRangeException
     */
    public function __construct($month = null, $year = null, $firstDayOfWeek = 0)
    {
        if ($month !== null) {
            if ((integer)$month < 1 || (integer)$month > 12) {
                throw new OutOfRangeException('month must be between 1-12');
            }
        } else {
            $month = date('n');
        }

        if ($year !== null) {
            if ((integer)$year < 1970 || (integer)$year > 2037) {
                throw new OutOfRangeException('year must be between 1970-2037');
            }
        } else {
            $year = date('Y');
        }

        $this->_month = (integer)$month;
        $this->_year = (integer)$year;

        $this->setFirstDayOfWeek($firstDayOfWeek);
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetches a row of the calendar data into an array then moves the internal
     * pointer to the next row
     *
     * @return mixed   A numeric array containing Unix timestamps for the days of
     *                 the week or null if there are no more rows
     * @since  1.0
     */
    public function fetchRow()
    {
        if ($this->_firstDayOfWeekTime > $this->_lastDayTime) {
            return null;
        }

        $row = array();
        for ($i = 0; $i < 7; $i++) {
            $row[] = $this->_firstDayOfWeekTime;
            $this->_firstDayOfWeekTime = strtotime('+1 day', $this->_firstDayOfWeekTime);
        }

        return $row;
    }

    // }}}
    // {{{ getMonth()

    /**
     * Returns the month of the calendar
     *
     * @return integer The month of the calendar
     * @since  1.0
     */
    public function getMonth()
    {
        return $this->_month;
    }

    // }}}
    // {{{ getYear()

    /**
     * Returns the year of the calendar
     *
     * @return integer The year of the calendar
     * @since  1.0
     */
    public function getYear()
    {
        return $this->_year;
    }

    // }}}
    // {{{ getFirstDayOfWeek()

    /**
     * Returns the first day of the week of the calendar
     *
     * @return integer The first day of the week of the calendar
     * @since  1.0
     */
    public function getFirstDayOfWeek()
    {
        return $this->_firstDayOfWeek;
    }

    // }}}
    // {{{ setFirstDayOfWeek()

    /**
     * Sets the first day of the week of the calendar
     *
     * @param  integer $firstDayOfWeek The first day of the week of the calendar
     * @return void
     * @throws OutOfRangeException
     * @since  1.0
     */
    public function setFirstDayOfWeek($firstDayOfWeek)
    {
        if ((integer)$firstDayOfWeek < 0 || (integer)$firstDayOfWeek > 6) {
            throw new OutOfRangeException('first day of week must be between 0-6');
        }

        $this->_firstDayOfWeek = (integer)$firstDayOfWeek;

        $this->_firstDayOfMonthTime = mktime(0, 0, 0, $this->_month, 1, $this->_year);
        $firstSundayTime = strtotime(sprintf('-%d days', date('w', $this->_firstDayOfMonthTime)), $this->_firstDayOfMonthTime);

        $this->_firstDayTime = strtotime(sprintf('+%d days', $firstDayOfWeek), $firstSundayTime);
        if ($this->_firstDayTime > $this->_firstDayOfMonthTime) {
            $this->_firstDayTime = strtotime('-7 days', $this->_firstDayTime);
        }

        $this->_lastDayTime = strtotime('+41 days', $this->_firstDayTime);
        $this->_firstDayOfWeekTime = $this->_firstDayTime;
    }

    // }}}
    // {{{ getFirstDay()

    /**
     * Returns the Unix timestamp for the first day of the calendar
     *
     * @return integer The Unix timestamp for the first day of the calendar
     * @since  1.0
     */
    public function getFirstDay()
    {
        return $this->_firstDayTime;
    }

    // }}}
    // {{{ getLastDay()

    /**
     * Returns the Unix timestamp for the last day of the calendar
     *
     * @return integer The Unix timestamp for the last day of the calendar
     * @since  1.0
     */
    public function getLastDay()
    {
        return $this->_lastDayTime;
    }

    // }}}
    // {{{ getFirstDayOfMonth()

    /**
     * Returns the Unix timestamp for the first day of the month
     *
     * @return integer The Unix timestamp for the first day of the month
     * @since  1.0
     */
    public function getFirstDayOfMonth()
    {
        return $this->_firstDayOfMonthTime;
    }

    // }}}
    // {{{ getLastDayOfMonth()

    /**
     * Returns the Unix timestamp for the last day of the month
     *
     * @return integer The Unix timestamp for the last day of the month
     * @since  1.0
     */
    public function getLastDayOfMonth()
    {
        return mktime(0, 0, 0, date('n', $this->_firstDayOfMonthTime), date('t', $this->_firstDayOfMonthTime), date('Y', $this->_firstDayOfMonthTime));
    }

    // }}}
    // {{{ getFirstDayOfPreviousMonth()

    /**
     * Returns the Unix timestamp for the first day of the previous month
     *
     * @return integer The Unix timestamp for the first day of the previous month
     * @since  1.0
     */
    public function getFirstDayOfPreviousMonth()
    {
        return mktime(0, 0, 0, date('n', $this->_firstDayOfMonthTime - 1), 1, date('Y', $this->_firstDayOfMonthTime - 1));
    }

    // }}}
    // {{{ getLastDayOfPreviousMonth()

    /**
     * Returns the Unix timestamp for the last day of the previous month
     *
     * @return integer The Unix timestamp for the last day of the previous month
     * @since  1.0
     */
    public function getLastDayOfPreviousMonth()
    {
        return strtotime('-1 day', $this->_firstDayOfMonthTime);
    }

    // }}}
    // {{{ getFirstDayOfNextMonth()

    /**
     * Returns the Unix timestamp for the first day of the next month
     *
     * @return integer The Unix timestamp for the first day of the next month
     * @since  1.0
     */
    public function getFirstDayOfNextMonth()
    {
        return mktime(0, 0, 0, date('n', $this->_lastDayTime), 1, date('Y', $this->_lastDayTime));
    }

    // }}}
    // {{{ getLastDayOfNextMonth()

    /**
     * Returns the Unix timestamp for the last day of the next month
     *
     * @return integer The Unix timestamp for the last day of the next month
     * @since  1.0
     */
    public function getLastDayOfNextMonth()
    {
        return mktime(0, 0, 0, date('n', $this->_lastDayTime), date('t', $this->_lastDayTime), date('Y', $this->_lastDayTime));
    }

    // }}}
}

// }}}
// }}}

?>
