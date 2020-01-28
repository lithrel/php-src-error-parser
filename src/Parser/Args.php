<?php
declare(strict_types=1);

namespace PhpSrcErrorParser\Parser;

class Args
{
    private const QUOTED_REPLACEMENT = 'str::';

    public function parseString(string $argString): array
    {
        // strip newlines and large spaces
        $args = preg_replace('#\s+#', ' ', $argString);

        // split args, keep double quotes
        $i = 0;
        //preg_match_all('#"([^"]*)"#imu', $args, $strArgs);
        preg_match_all('#"([^"]*)"#imu', $args, $replacedStrings);

        $replacement = [];
        $replacedStrings = $replacedStrings[0] ?? [];
        foreach ($replacedStrings as $k => $str) {
            $replacementString = self::QUOTED_REPLACEMENT . $k;
            $args = str_replace($str, $replacementString, $args);
            $replacement[] = $replacementString;
        }
        $args = explode(',', $args);

        foreach ($args as &$arg) {
            // var_dump($replacement, $replacedStrings, $arg);
            $arg = trim(str_replace('" "', '',
                str_replace($replacement, $replacedStrings, $arg)
            ));
        }

        /*$splittedArgs = array_map(static function ($s) use ($strArgs) {
                $s = trim($s);
                $a = str_replace(self::QUOTED_REPLACEMENT, '', $s);
                return $strArgs[$a] ?? $s;
            },
            $args
        );*/

        return $args;
    }
}