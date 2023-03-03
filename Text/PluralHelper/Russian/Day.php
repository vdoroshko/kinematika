<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Russian day count formatting helper class
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
 * @category   Text
 * @package    Text_PluralHelper
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
require_once 'Text/PluralHelper.php';

// {{{ classes
// {{{ class Text_PluralHelper_Russian_Day

/**
 * Russian day count formatting helper class
 *
 * @category   Text
 * @package    Text_PluralHelper
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class Text_PluralHelper_Russian_Day implements Text_PluralHelper
{
    // {{{ format()

    /**
     * @param  integer $number
     * @return string
     */
    public static function format($number)
    {
        if ((integer)$number % 100 > 10 && (integer)$number % 100 < 20) {
            return sprintf('%d дней', $number);
        } elseif ((integer)$number % 10 == 1) {
            return sprintf('%d день', $number);
        } elseif ((integer)$number % 10 > 1 && (integer)$number % 10 < 5) {
            return sprintf('%d дня', $number);
        } else {
            return sprintf('%d дней', $number);
        }
    }

    // }}}
}

// }}}
// }}}

?>
