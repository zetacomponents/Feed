<?php
class mySlashHandler extends ezcFeedModule
{
    public function __construct( $level = 'feed' )
    {
        parent::__construct( $level );
    }

    public function generate( DOMDocument $xml, DOMNode $root )
    {
        // write implementation here

        // should fill $root with values from $this
    }

    public function parse( $name, DOMElement $node )
    {
        // write implementation here

        // should parse $node and add a new ezcFeedElement to $this with name $name
    }


    public function isElementAllowed( $name )
    {
        // return true if the element $name is allowed in this module at level $this->level,
        // and false otherwise
    }

    public function add( $name )
    {
        // add the element $name to this module at level $this->level (feed-level or item-level)
    }

    public static function getModuleName()
    {
        return 'Slash';
    }

    public static function getNamespace()
    {
        return 'http://purl.org/rss/1.0/modules/slash/';
    }

    public static function getNamespacePrefix()
    {
        return 'slash';
    }
}
