<?php


namespace Opencontent\OpenApi;


class StringTools
{
    /**
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param string $str String in underscore format
     * @param bool $capitaliseFirstChar If true, capitalise the first char in $str
     * @return   string                              $str translated into camel caps
     */
    public static function toCamelCase($str, $capitaliseFirstChar = true)
    {
        if ($capitaliseFirstChar) {
            $str[0] = strtoupper($str[0]);
        }

        return preg_replace_callback('/_([a-z])/', function ($c) {
            return strtoupper($c[1]);
        }, $str
        );
    }

    /**
     * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
     * @param string $str String in camel case format
     * @param string $separator
     * @return    string            $str Translated into underscore format
     */
    public static function fromCamelCase($str, $separator = '_')
    {
        $str[0] = strtolower($str[0]);

        return preg_replace_callback('/([A-Z])/', function ($c) use ($separator){
            return $separator . strtolower($c[1]);
        }, $str
        );
    }

}