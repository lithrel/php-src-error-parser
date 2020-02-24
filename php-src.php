<?php
declare(strict_types=1);

use PhpSrcErrorParser\Pattern;
use PhpSrcErrorParser\ErrorCall;
use PhpSrcErrorParser\Parser;

require __DIR__ . '/vendor/autoload.php';

define('ROOT_DIR', __DIR__);
define('PHPSRC_DIR', dirname(__DIR__, 3) . '/github.com/php-src');

$rec = new RecursiveDirectoryIterator(PHPSRC_DIR);
$it = new RecursiveIteratorIterator($rec);
$regex = new RegexIterator(
    $it, '/^.+\.c$/i', RecursiveRegexIterator::GET_MATCH
);

$errorList = [];
$regex->rewind();
while ($regex->valid()) {
    echo '.';
    $file = $regex->current()[0];
    $content = file_get_contents($file);

    $line = fn ($c) => substr_count(mb_substr($content, 0, $c), PHP_EOL) + 1;
    $parse = static function ($args): array {
        return (new Parser\Args())->parseString($args);
    };

    /*
    ZEND_API zend_class_entry *zend_ce_throwable;
    ZEND_API zend_class_entry *zend_ce_exception;
    ZEND_API zend_class_entry *zend_ce_error_exception;
    ZEND_API zend_class_entry *zend_ce_error;
    ZEND_API zend_class_entry *zend_ce_compile_error;
    ZEND_API zend_class_entry *zend_ce_parse_error;
    ZEND_API zend_class_entry *zend_ce_type_error;
    ZEND_API zend_class_entry *zend_ce_argument_count_error;
    ZEND_API zend_class_entry *zend_ce_value_error;
    ZEND_API zend_class_entry *zend_ce_arithmetic_error;
    ZEND_API zend_class_entry *zend_ce_division_by_zero_error;
    */

    // Zend/zend_execute_API.c
    // zend_use_undefined_constant
    $patterns = [
        // Zend/zend_execute_API.c
        // static ZEND_COLD void zend_throw_or_error(int fetch_type, zend_class_entry *exception_ce, const char *format, ...)
        new Pattern(
            '#zend_throw_or_error[\s]?\(([^;]*)\);#imsu',
            'zend_throw_or_error'
        ),
        // Zend/zend.h
        // ZEND_API ZEND_COLD void zend_throw_error(zend_class_entry *exception_ce, const char *format, ...) ZEND_ATTRIBUTE_FORMAT(printf, 2, 3);
        // Zend/zend.c
        // ZEND_API ZEND_COLD void zend_throw_error(zend_class_entry *exception_ce, const char *format, ...)
        new Pattern(
            '#zend_throw_error[\s]?\(([^;]*)\);#imsu',
            'zend_throw_error'
        ),
        // ZEND_API ZEND_COLD void zend_error(int type, const char *format, ...) ZEND_ATTRIBUTE_FORMAT(printf, 2, 3);
        new Pattern(
            '#zend_error[\s]?\(([^;]*)\);#imsu',
            'zend_error'
        ),
        // ZEND_API ZEND_COLD ZEND_NORETURN void zend_error_noreturn(int type, const char *format, ...) ZEND_ATTRIBUTE_FORMAT(printf, 2, 3);
        new Pattern(
            '#zend_error_noreturn[\s]?\(([^;]*)\);#imsu',
            'zend_error_noreturn'
        ),
        // ZEND_API ZEND_COLD void zend_error_at(int type, const char *filename, uint32_t lineno, const char *format, ...) ZEND_ATTRIBUTE_FORMAT(printf, 4, 5);
        new Pattern(
            '#zend_error_at[\s]?\(([^;]*)\);#imsu',
            'zend_error_at'
        ),
        // ZEND_API ZEND_COLD ZEND_NORETURN void zend_error_at_noreturn(int type, const char *filename, uint32_t lineno, const char *format, ...) ZEND_ATTRIBUTE_FORMAT(printf, 4, 5);
        new Pattern(
            '#zend_error_at_noreturn[\s]?\(([^;]*)\);#imsu',
            'zend_error_at_noreturn'
        ),
        // ZEND_API ZEND_COLD void zend_type_error(const char *format, ...)
        // zend_throw_exception(zend_ce_type_error, message, 0);
        new Pattern(
            '#zend_type_error[\s]?\(([^;]*)\);#imsu',
            'zend_type_error',
            'zend_ce_type_error'
        ),
        // ZEND_API ZEND_COLD void zend_argument_count_error(const char *format, ...) ZEND_ATTRIBUTE_FORMAT(printf, 1, 2);
        // zend_throw_exception(zend_ce_argument_count_error, message, 0);
        new Pattern(
            '#zend_argument_count_error[\s]?\(([^;]*)\);#imsu',
            'zend_argument_count_error',
            'zend_ce_argument_count_error'
        ),
        // ZEND_API ZEND_COLD void zend_value_error(const char *format, ...) ZEND_ATTRIBUTE_FORMAT(printf, 1, 2);
        // zend_throw_exception(zend_ce_value_error, message, 0);
        new Pattern(
            '#zend_value_error[\s]?\(([^;]*)\);#imsu',
            'zend_value_error',
            'zend_ce_value_error'
        ),
        // ZEND_API ZEND_COLD void zend_argument_count_error(const char *format, ...)
        // ZEND_API ZEND_COLD void zend_value_error(const char *format, ...)
        new Pattern(
            '#zend_mm_safe_error[\s]?\(([^;]*)\);#imsu',
            'zend_mm_safe_error'
        ),
    ];

    foreach ($patterns as $pattern) {
        $found = preg_match_all($pattern->pattern, $content, $m,
            PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
        if (!$found) {
            continue;
        }

        foreach ($m[1] as $match) {
            $args = $parse($match[0]);
            $msg = implode(',', array_filter(
                $args,
                fn ($a) => strpos($a, '"') !== false
            ));

            if (isset($errorList[$msg])) {
                $errorList[$msg]->addOccurence(
                    str_replace(PHPSRC_DIR, '', $file),
                    $line($match[1]),
                    $pattern->zendFunction,
                    $args,
                    $pattern->zendException ?? ''
                );
            } else {
                $errorList[$msg] = new ErrorCall(
                    $msg,
                    str_replace(PHPSRC_DIR, '', $file),
                    $line($match[1]),
                    $pattern->zendFunction,
                    $args,
                    $pattern->zendException ?? ''
                );
            }

        }
    }

    $regex->next();
}
$messages = array_map(
    fn ($e) => sprintf('%s', $e->errorMessage),
    $errorList
);
asort($messages);
ksort($errorList);

// print_r($errorList);

print_r(array_unique($messages));
echo sprintf('%d unique messages', count(array_unique($messages)));
echo "\n\n";


\Hugger\Type\Printer::configureOutput(\Hugger\Type\Printer::OUTPUT_RAW);
$factory = new \Hugger\ErrorFactory([]);
/** @var ErrorCall $e */
foreach ($errorList as $e) {
    echo '.';
    if ($h = $factory->getHandlerFor(trim($e->errorMessage, '"'))) {
        echo '!';
        /** @var \Hugger\KindError $hugger */
        $hugger = new $h([]);
        $e->addHug($hugger->hug()->resolve([]));
    }
}
echo "\n";


file_put_contents(
    'errors.json',
    json_encode(array_values($errorList), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
);


/**
 * Searched message if match
 *
 * Generic message
 *
 * short intro
 * explanation
 * fixes
 * examples on how to raise this error
 * location in codet
 */


/** @todo
Static errors to add:

main/main.c::php_error_cb has error_type_str
    Parse error is a difficult one, maybe 
    https://github.com/php/php-src/blob/master/Zend/zend_language_parser.y
    https://github.com/php/php-src/blob/master/ext/opcache/jit/dynasm/minilua.c
    can help ?

Zend/zend_execute.c::zend_wrong_string_offset
    messages about wrong offsets

ext/opcache/jit/zend_jit_helpers.c::zend_wrong_string_offset

main/main.c::php_module_startup
    Directive is (deprecated|no longer available)
        allow_url_include
        register_globals
        etc.

Zend/zend_alloc.c::zend_mm_safe_error
    "Allowed memory size of %zu bytes exhausted at %s:%d (tried to allocate %zu bytes)"
*/