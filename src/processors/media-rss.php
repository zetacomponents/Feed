<?php
/**
 * File containing the ezcFeedMediaRss class.
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Feed
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @filesource
 */

/**
 * Class providing limited parsing of Media RSS feeds.
 *
 * Specifications:
 * {@link http://www.rssboard.org/media-rss Media RSS Specifications}.
 *
 * @package Feed
 * @version //autogentag//
 */
class ezcFeedMediaRss extends ezcFeedRss2
{
    protected function parseModules( $item, DOMElement $node, $tagName )
    {
        if ( $tagName === 'media:content' )
        {
            self::parseMediaContent( $item, $node );
        }
        else
        {
            parent::parseModules( $item, $node, $tagName );
        }
    }

    private static function parseMediaContent( $item, DOMElement $node )
    {
        if ( ! $node->hasAttribute( 'url' ) )
        {
            return;
        }

        $subElement = $item->add( 'enclosure' );

        $subElement->url = $node->getAttribute( 'url' );

        if ( $node->hasAttribute( 'type' ) )
        {
            $subElement->type = $node->getAttribute( 'type' );
        }
    }
}
