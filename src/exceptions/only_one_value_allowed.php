<?php
/**
 * File containing the ezcFeedOnlyOneValueAllowedException class.
 *
 * @package Feed
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Thrown when some elements value is not a single value but an array.
 *
 * @package Feed
 * @version //autogentag//
 */
class ezcFeedOnlyOneValueAllowedException extends ezcFeedException
{
    /**
     * Constructs a new ezcFeedOnlyOneValueAllowedException.
     *
     * @param string $attribute The attribute which caused the exception
     */
    public function __construct( $attribute )
    {
        parent::__construct( "The attribute '{$attribute}' supports only singular values." );
    }
}
?>