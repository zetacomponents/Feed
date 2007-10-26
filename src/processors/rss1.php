<?php
/**
 * File containing the ezcFeedRss1 class.
 *
 * @package Feed
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Class providing parsing and generating of RSS1 feeds.
 *
 * Specifications:
 * {@link http://www.rssboard.org/rss-specification RSS1 Specifications}
 *
 * @package Feed
 * @version //autogentag//
 */
class ezcFeedRss1 extends ezcFeedProcessor implements ezcFeedParser
{
    /**
     * Defines the feed type of this processor.
     */
    const FEED_TYPE = 'rss1';

    /**
     * Holds the definitions for the elements in RSS1.
     *
     * @var array(string=>mixed)
     * @ignore
     */
    protected static $rss1Schema = array(
        // these are actually part of channel: title, link, description, items, image, textinput
        'title'        => array( '#' => 'string' ),
        'link'         => array( '#' => 'string' ),
        'description'  => array( '#' => 'string' ),
        'items'        => array( '#' => 'node',
                                 'ATTRIBUTES' => array( 'resource' => 'string' ) ),

        'image'        => array( '#' => 'string' ),
        'textinput'    => array( '#' => 'string',
                                 'ATTRIBUTES' => array( 'resource' => 'string' ) ),


        'image'     => array( '#'          => 'node',
                              'ATTRIBUTES' => array( 'about'   => 'string' ),
                              
                              'NODES'      => array(
                                                'title'        => 'string',
                                                'url'          => 'string',
                                                'link'         => 'string',

                                                'REQUIRED'     => array( 'title', 'url', 'link' ),
                                                ), ),

        // outside channel
        'item'      => array( '#'          => 'node',
                              'ATTRIBUTES' => array( 'about'   => 'string',
                                                     'resource'=> 'string', ),

                              'NODES'      => array(
                                                'title'        => array( '#' => 'string' ),
                                                'link'         => array( '#' => 'string' ),

                                                'description'  => array( '#' => 'string' ),

                                                'REQUIRED'     => array( 'title', 'link' ),
                                                'OPTIONAL'     => array( 'description' ),
                                                ),
                              'MULTI'      => 'items' ),

        'textInput' => array( '#'          => 'string',
                              'ATTRIBUTES' => array( 'resource' => 'string' ) ),

        'REQUIRED'  => array( 'title', 'link', 'description', 'item' ),
        'OPTIONAL'  => array( 'image', 'textinput' ),

        'MULTI'     => array( 'items'      => 'item' ),

        );

    /**
     * Creates a new RSS1 processor.
     */
    public function __construct()
    {
        $this->feedType = self::FEED_TYPE;
        $this->schema = new ezcFeedSchema( self::$rss1Schema );
    }

    /**
     * Creates a root node for the XML document being generated.
     *
     * @param string $version The RSS version for the root node
     */
    public function createRootElement( $version )
    {
        $rss = $this->xml->createElementNS( 'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:RDF' );
        $this->channel = $channelTag = $this->xml->createElement( 'channel' );
        $rss->appendChild( $channelTag );
        $this->root = $this->xml->appendChild( $rss );
    }

    /**
     * Sets the namespace attribute in the XML document being generated.
     *
     * @param string $prefix The prefix to use
     * @param string $namespace The namespace to use
     */
    public function generateNamespace( $prefix, $namespace )
    {
        $this->root->setAttributeNS( "http://www.w3.org/2000/xmlns/", "xmlns:$prefix", $namespace );
    }

    /**
     * Returns an XML string from the feed information contained in this
     * processor.
     *
     * @return string
     */
    public function generate()
    {
        $this->xml = new DOMDocument( '1.0', 'utf-8' );
        $this->xml->formatOutput = 1;
        $this->createRootElement( '2.0' );

        $this->generateRequired();
        $this->generateOptional();
        //$this->generateItems();

        return $this->xml->saveXML();
    }

    /**
     * Adds the required feed elements to the XML document being generated.
     *
     * @ignore
     */
    protected function generateRequired()
    {
        foreach ( $this->schema->getRequired() as $element )
        {
            $data = $this->schema->isMulti( $element ) ? $this->get( $this->schema->getMulti( $element ) ) : $this->get( $element );
            if ( is_null( $data ) )
            {
                throw new ezcFeedRequiredMetaDataMissingException( $element );
            }

            $attributes = array();
            foreach ( $this->schema->getAttributes( $element ) as $attribute => $type )
            {
                if ( isset( $data->$attribute ) )
                {
                    $attributes[$attribute] = $data->$attribute;
                }
            }
            if ( count( $attributes ) >= 1 )
            {
                if ( in_array( $element, array( 'item' ) ) )
                {
                    $this->generateMetaDataWithAttributes( $this->root, $element, $data, $attributes );
                }
                else
                {
                    $this->generateMetaDataWithAttributes( $this->channel, $element, $data, $attributes );
                }
            }
            else
            {
                $this->generateMetaData( $this->channel, $element, $data );
            }
        }
    }

    /**
     * Adds the optional feed elements to the XML document being generated.
     *
     * @ignore
     */
    protected function generateOptional()
    {
        foreach ( $this->schema->getOptional() as $attribute )
        {
            $normalizedAttribute = ezcFeedTools::normalizeName( $attribute, $this->schema->getElementsMap() );
            $data = $this->schema->isMulti( $attribute ) ? $this->get( $this->schema->getMulti( $attribute ) ) : $this->get( $attribute );

            if ( !is_null( $data ) )
            {
                // @todo Add hooks
                switch ( $attribute )
                {
                    case 'published':
                    case 'updated':
                        $this->generateMetaData( $this->channel, $normalizedAttribute, date( 'D, d M Y H:i:s O', $data ) );
                        break;

                    default:
                        $this->generateMetaData( $this->channel, $normalizedAttribute, $data );
                }
            }
        }
    }

    /**
     * Adds the feed items to the XML document being generated.
     *
     * @ignore
     */
    protected function generateItems()
    {
        foreach ( $this->get( 'items' ) as $item )
        {
            $itemTag = $this->xml->createElement( 'item' );
            $this->channel->appendChild( $itemTag );

            $atLeastOneRequiredFeedItemPresent = false;
            foreach ( $this->schema->getAtLeastOne( 'item' ) as $attribute )
            {
                $data = $this->schema->isMulti( 'item', $attribute ) ? $this->get( $this->schema->getMulti( 'item', $attribute ) ) : $item->$attribute;
                if ( !is_null( $data ) )
                {
                    $atLeastOneRequiredFeedItemPresent = true;
                    break;
                }
            }

            if ( $atLeastOneRequiredFeedItemPresent === false )
            {
                throw new ezcFeedAtLeastOneItemDataRequiredException( $this->schema->getAtLeastOne( 'item' ) );
            }

            foreach ( $this->schema->getOptional( 'item' ) as $attribute )
            {
                $normalizedAttribute = ezcFeedTools::normalizeName( $attribute, $this->schema->getItemsMap() );

                $metaData = $this->schema->isMulti( 'item', $attribute ) ? $this->get( $this->schema->getMulti( 'item', $attribute ) ) : $item->$attribute;

                if ( !is_null( $metaData ) )
                {
                    // @todo Add hooks
                    switch ( $attribute )
                    {
                        case 'guid':
                            $permalink = substr( $metaData, 0, 7 ) === 'http://' ? "true" : "false";
                            $this->generateItemDataWithAttributes( $itemTag, $normalizedAttribute, $metaData, array( 'isPermaLink' => $permalink ) );
                            break;

                        case 'published':
                            $this->generateItemData( $itemTag, $normalizedAttribute, date( 'D, d M Y H:i:s O', $metaData ) );
                            break;

                        default:
                            $this->generateItemData( $itemTag, $normalizedAttribute, $metaData );
                    }
                }
            }
        }
    }

    /**
     * Returns true if the parser can parse the provided XML document object,
     * false otherwise.
     *
     * @param DOMDocument $xml The XML document object to check for parseability
     * @return bool
     */
    public static function canParse( DOMDocument $xml )
    {
        if ( strpos( $xml->documentElement->tagName, 'RDF' ) === false )
        {
            return false;
        }

        return true;
    }

    /**
     * Parses the provided XML document object and returns an ezcFeed object
     * from it.
     *
     * @throws ezcFeedParseErrorException
     *         If an error was encountered during parsing.
     *
     * @param DOMDocument $xml The XML document object to parse
     * @return ezcFeed
     */
    public function parse( DOMDocument $xml )
    {
        $feed = new ezcFeed( self::FEED_TYPE );
        $rssChildren = $xml->documentElement->childNodes;
        $channel = null;

        $this->usedPrefixes = array();
        $xp = new DOMXpath( $xml );
        $set = $xp->query( './namespace::*', $xml->documentElement );
        $this->usedNamespaces = array();
        $items = $xml->getElementsByTagName( 'item' );

        // @todo Parse modules

        foreach ( $rssChildren as $rssChild )
        {
            if ( $rssChild->nodeType === XML_ELEMENT_NODE
                 && $rssChild->tagName === 'channel' )
            {
                $channel = $rssChild;
            }

            if ( $rssChild->nodeType === XML_ELEMENT_NODE
                 && $rssChild->tagName === 'image' )
            {
                $image = $rssChild;
            }
        }

        if ( $channel === null )
        {
            throw new ezcFeedParseErrorException( "No channel tag" );
        }

        foreach ( $channel->childNodes as $channelChild )
        {
            if ( $channelChild->nodeType == XML_ELEMENT_NODE )
            {
                $tagName = $channelChild->tagName;
                $tagName = ezcFeedTools::deNormalizeName( $tagName, $this->schema->getElementsMap() );

                switch ( $tagName )
                {
                    case 'title':
                    case 'link':
                    case 'description':
                        $feed->$tagName = $channelChild->textContent;
                        break;

                    case 'items':
                        $seq = $channelChild->getElementsByTagName( 'Seq' );
                        if ( $seq->length === 0 )
                        {
                            break;
                        }

                        $lis = $seq->item( 0 )->getElementsByTagName( 'li' );

                        foreach ( $lis as $el )
                        {
                            $resource = ezcFeedTools::getAttribute( $el, 'resource' );

                            if ( $resource !== null )
                            {
                                foreach ( $items as $item )
                                {
                                    $about = ezcFeedTools::getAttribute( $item, 'about' );
                                    if ( $about === $resource )
                                    {
                                        $element = $feed->add( 'item' );
                                        $this->parseItem( $feed, $element, $item );
                                        break;
                                    }
                                }
                            }

                        }
                        break;

                    case 'image':
                        $this->parseImage( $feed, $image );
                        break;

                    case 'textinput':
                        // @todo Implement
                        break;

                    default:
                        // @todo Check if it's part of a known module/namespace
                }
            }

            foreach ( ezcFeedTools::getAttributes( $channelChild ) as $key => $value )
            {
                $feed->$tagName->$key = $value;
            }
        }
        return $feed;
    }

    /**
     * Parses the provided XML element object and stores it as a feed item in
     * the provided ezcFeed object.
     *
     * @param ezcFeed $feed The feed object in which to store the parsed XML element as a feed item
     * @param ezcFeedElement $element The feed element object that will contain the feed item
     * @param DOMElement $xml The XML element object to parse
     */
    public function parseItem( ezcFeed $feed, ezcFeedElement $element, DOMElement $xml )
    {
        foreach ( $xml->childNodes as $itemChild )
        {
            if ( $itemChild->nodeType == XML_ELEMENT_NODE )
            {
                $tagName = $itemChild->tagName;
                $tagName = ezcFeedTools::deNormalizeName( $tagName, $this->schema->getItemsMap() );

                switch ( $tagName )
                {
                    case 'title':
                    case 'link':
                    case 'description':
                        $element->$tagName = $itemChild->textContent;
                        break;

                    default:
                        // @todo Check if it's part of a known module/namespace
                }
            }
        }

        foreach ( ezcFeedTools::getAttributes( $xml ) as $key => $value )
        {
            $element->$key = $value;
        }
    }

    /**
     * Parses the provided XML element object and stores it as a feed image in
     * the provided ezcFeed object.
     *
     * @param ezcFeed $feed The feed object in which to store the parsed XML element as a feed image
     * @param DOMElement $xml The XML element object to parse
     */
    public function parseImage( ezcFeed $feed, DOMElement $xml )
    {
        $feed->image = new ezcFeedElement( $this->schema->get( 'image' ) );
        foreach ( $xml->childNodes as $itemChild )
        {
            if ( $itemChild->nodeType == XML_ELEMENT_NODE )
            {
                $tagName = $itemChild->tagName;
                switch ( $tagName )
                {
                    case 'title':
                    case 'link':
                    case 'url':
                        $feed->image->$tagName = $itemChild->textContent;
                        break;
                }
            }
        }

        foreach ( ezcFeedTools::getAttributes( $xml ) as $key => $value )
        {
            $feed->image->$key = $value;
        }
    }
}
?>
