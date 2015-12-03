<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Common XML-RPC exception classes
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
 * @package    XML_RPC_Exception
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @version    1.0
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */

// {{{ classes
// {{{ class XML_RPC_Exception

/**
 * Base class for all XML-RPC exceptions
 *
 * @category   Web Services
 * @package    XML_RPC_Exception
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_Exception extends RuntimeException {}

// }}}
// {{{ class XML_RPC_BadMethodCallException

/**
 * Exception class that is thrown when an attempt to invoke a method on a
 * XML-RPC server that does not exist fails
 *
 * @category   Web Services
 * @package    XML_RPC_Exception
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_BadMethodCallException extends XML_RPC_Exception {}

// }}}
// {{{ class XML_RPC_FaultException

/**
 * Exception class that is thrown when a XML-RPC server returns a fault
 * response
 *
 * @category   Web Services
 * @package    XML_RPC_Exception
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_FaultException extends XML_RPC_Exception
{
    // {{{ constructor

    /**
     * Constructs a new XML_RPC_FaultException object
     *
     * @param  string  $message The fault message
     * @param  integer $code (optional) The fault code
     */
    public function __construct($message, $code = 0)
    {
        parent::__construct((string)$message, (integer)$code);
    }

    // }}}
}

// }}}
// {{{ class XML_RPC_IOException

/**
 * Exception class that is thrown when network error occurred while
 * communicating with an XML-RPC server
 *
 * @category   Web Services
 * @package    XML_RPC_Exception
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_IOException extends XML_RPC_Exception {}

// }}}
// {{{ class XML_RPC_HTTPException

/**
 * Exception class that is thrown when an HTTP error occured while communicating
 * with an XML-RPC server
 *
 * @category   Web Services
 * @package    XML_RPC_Exception
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_HTTPException extends XML_RPC_IOException
{
    // {{{ constructor

    /**
     * Constructs a new XML_RPC_HTTPException object
     *
     * @param  mixed   $message Either the HTTP status code or the exception message
     * @param  mixed   $code (optional) The HTTP status code
     * @throws OutOfRangeException
     */
    public function __construct($message, $code = null)
    {
        if (is_numeric((string)$message)) {
            if ((integer)$message < 100 || (integer)$message > 599) {
                throw new OutOfRangeException('HTTP status code must be between 100-599');
            }

            parent::__construct('', (integer)$message);
        } else {
            if ((integer)$code < 100 || (integer)$code > 599) {
                throw new OutOfRangeException('HTTP status code must be between 100-599');
            }

            parent::__construct((string)$message, (integer)$code);
        }
    }

    // }}}
}

// }}}
// {{{ class XML_RPC_NotAllowedException

/**
 * Exception class that is thrown to indicate that remote access is disabled
 * in the local server configuration
 *
 * @category   Web Services
 * @package    XML_RPC_Exception
 * @author     Vitaly Doroshko <vdoroshko@mail.ru>
 * @copyright  2014, 2015 Vitaly Doroshko
 * @license    http://opensource.org/licenses/BSD-3-Clause
 *             BSD 3-Clause License
 * @link       https://github.com/vdoroshko/kinematika
 * @since      1.0
 */
class XML_RPC_NotAllowedException extends XML_RPC_Exception {}

// }}}
// }}}

?>
