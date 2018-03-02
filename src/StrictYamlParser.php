<?php

namespace Upaid\TranslationsValidator;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class StrictYamlParser
{
    const PARSER_WARNING_REGEX = '#Duplicate key ".*?" detected whilst parsing YAML#';

    /**
     * @var \Symfony\Component\Yaml\Parser
    */
    protected $sfParser;

    public function __construct(Parser $sfParser)
    {
        $this->sfParser = $sfParser;
    }

    /**
     * Parses a YAML string to a PHP value.
     *
     * @return mixed A PHP value
     *
     * @throws ParseException If the YAML is not valid
     */
    public function parse(string $value)
    {
        $this->treatYamlDuplicatedKeysAsExceptions();

        $output = $this->sfParser->parse($value);
        $this->checkSpacesAfterColons($value);

        $this->restoreOriginalErrorHandler();

        return $output;
    }

    /**
     * handle E_USER_DEPRECATED triggered by YAML parser for duplicated keys by rethrowing them as exceptions
     */
    protected function treatYamlDuplicatedKeysAsExceptions(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            switch ($errno) {
                case E_USER_DEPRECATED:
                    $matches = [];
                    if (preg_match($this::PARSER_WARNING_REGEX, $errstr, $matches)) {
                        throw new ParseException($matches[0]);
                    }
                    break;
            }
        });
    }

    protected function restoreOriginalErrorHandler() : void
    {
        restore_error_handler();
    }

    /**
     * @throws ParseException
     */
    protected function checkSpacesAfterColons(string $content) : void
    {
        $content = $this->cleanup($content);
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $this->checkSpaceAfterFirstColonInALine($line);
        }
    }

    /**
     * @throws ParseException
     */
    protected function checkSpaceAfterFirstColonInALine(string $line) : void
    {
        if (strpos(trim($line), ':') === 0) { // colon is the first non-whitespace character
            return;
        }

        if (!$this->characterAfterFirstColonIsValid($line)) {
            $e  = new ParseException('No space after colon');
            $e->setSnippet($line);
            throw $e;
        }
    }

    /**
     * search for a first colon (if exists) and check if the next character is space or null character or Line Feed
    */
    protected function characterAfterFirstColonIsValid(string $line) : bool
    {
        if (($firstColonPos = strpos($line, ':')) === false) {
            return true; // there is no colon in the line at all, so there is nothing to check
        }

        if (($nextChar = substr($line, $firstColonPos + 1, 1)) === false) {
            return true; // colon is the last character, so it's ok
        }

        if ($nextChar === ' ' || ord($nextChar) === 0 || ord($nextChar) === 10) {
            return true;
        }

        return false;
    }

    /**
     * Cleanups a YAML string to be parsed.
     *
     * @see \Symfony\Component\Yaml\Parser::cleanup() (method cleanup() in that is private,
     * so I cannot use it in a simpler way - neither by inheritance nor composition)
     */
    protected function cleanup(string $value) : string
    {
        $value = str_replace(array("\r\n", "\r"), "\n", $value);

        // strip YAML header
        $count = 0;
        $value = preg_replace('#^\%YAML[: ][\d\.]+.*\n#u', '', $value, -1, $count);
        $this->offset += $count;

        // remove leading comments
        $trimmedValue = preg_replace('#^(\#.*?\n)+#s', '', $value, -1, $count);
        if ($count == 1) {
            // items have been removed, update the offset
            $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
            $value = $trimmedValue;
        }

        // remove start of the document marker (---)
        $trimmedValue = preg_replace('#^\-\-\-.*?\n#s', '', $value, -1, $count);
        if ($count == 1) {
            // items have been removed, update the offset
            $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
            $value = $trimmedValue;

            // remove end of the document marker (...)
            $value = preg_replace('#\.\.\.\s*$#', '', $value);
        }

        return $value;
    }

}