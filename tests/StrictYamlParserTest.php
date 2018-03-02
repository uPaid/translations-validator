<?php

namespace Upaid\TranslationsValidator\Tests;

use Upaid\TranslationsValidator\StrictYamlParser;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use PHPUnit\Framework\TestCase;

class StrictYamlParserTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
    */
    protected $sfParserMock;

    public function setUp()
    {
        $this->sfParserMock = $this->createMock(Parser::class);
    }

    /**
     * @test
     * @covers \App\Utilities\StrictYamlParser::parse()
    */
    public function it_throws_exception_if_there_are_duplicated_keys_unhandled_by_sf_parser()
    {
        $this->sfParserMock->expects($this->once())
            ->method('parse')
            ->will($this->returnCallback(function($value) {
                trigger_error('Duplicate key "foo" detected whilst parsing YAML', E_USER_DEPRECATED);
                return []; // it's not used at all, so can be empty
            }));
        $parser = new StrictYamlParser($this->sfParserMock);

        $yaml = <<<'EOF'
foo: bar
foo: baz
EOF;

        $this->expectException(ParseException::class);
        $parser->parse($yaml);
    }

    /**
     * @test
     * @covers \App\Utilities\StrictYamlParser::parse()
     */
    public function it_throws_exception_if_there_is_no_space_after_colon()
    {
        $this->sfParserMock->expects($this->once())->method('parse');
        $parser = new StrictYamlParser($this->sfParserMock);

        $yaml = <<<'EOF'
foo:bar
bar: baz
EOF;

        $this->expectException(ParseException::class);
        $parser->parse($yaml);
    }

    /**
     * @test
     * @covers \App\Utilities\StrictYamlParser::parse()
     */
    public function it_passes_when_there_is_line_feed_or_null_character_at_the_end_of_line()
    {
        $this->sfParserMock->expects($this->once())->method('parse');
        $parser = new StrictYamlParser($this->sfParserMock);

        $lineFeed = chr(10);
        $yaml = "
foo:\0
  bar:$lineFeed    bar: aaa";

        $parser->parse($yaml);
    }

}
