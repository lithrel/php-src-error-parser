<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ArgsTest extends TestCase
{
    public function testBasicArgs()
    {
        $parser = new \PhpSrcErrorParser\Parser\Args();

        $this->assertEquals(
            $parser->parseString('arg1, arg2, arg3'),
            ['arg1', 'arg2', 'arg3']
        );
    }

    public function testBasicQuotedArgs()
    {
        $parser = new \PhpSrcErrorParser\Parser\Args();

        $this->assertEquals(
            $parser->parseString('"arg1", "arg2", "arg3"'),
            ['"arg1"', '"arg2"', '"arg3"']
        );
    }

    public function testQuotedWithColon()
    {
        $parser = new \PhpSrcErrorParser\Parser\Args();

        $this->assertEquals(
            $parser->parseString('"arg,1", "arg,2", "arg,3"'),
            ['"arg,1"', '"arg,2"', '"arg,3"']
        );
    }

    public function testXmlParserError()
    {
        $parser = new \PhpSrcErrorParser\Parser\Args();

        $this->assertEquals(
            $parser->parseString(
                'NULL, '
                    . '"Cannot directly construct XmlParser, use xml_parser'
                    . '_create() or xml_parser_create_ns() instead"'
            ),
            [
                'NULL',
                '"Cannot directly construct XmlParser, use xml_parser'
                    . '_create() or xml_parser_create_ns() instead"'
            ]
        );
    }

    public function testCollatorError()
    {
        $parser = new \PhpSrcErrorParser\Parser\Args();

        $this->assertEquals(
            $parser->parseString('E_ERROR,
            "Collator: attempt to create properties "
			"on a non-registered class."'),
            [
                'E_ERROR',
                '"Collator: attempt to create properties '
                    . 'on a non-registered class."'
            ]
        );
    }
}