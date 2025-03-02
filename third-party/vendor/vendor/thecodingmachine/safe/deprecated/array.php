<?php

namespace WPSEC_Vendor\Safe;

use WPSEC_Vendor\Safe\Exceptions\ArrayException;
/**
 * array_replace_recursive replaces the values of
 * array with the same values from all the following
 * arrays. If a key from the first array exists in the second array, its value
 * will be replaced by the value from the second array. If the key exists in the
 * second array, and not the first, it will be created in the first array.
 * If a key only exists in the first array, it will be left as is.
 * If several arrays are passed for replacement, they will be processed
 * in order, the later array overwriting the previous values.
 *
 * array_replace_recursive is recursive : it will recurse into
 * arrays and apply the same process to the inner value.
 *
 * When the value in the first array is scalar, it will be replaced
 * by the value in the second array, may it be scalar or array.
 * When the value in the first array and the second array
 * are both arrays, array_replace_recursive will replace
 * their respective value recursively.
 *
 * @param array $array The array in which elements are replaced.
 * @param array $replacements Arrays from which elements will be extracted.
 * @return array Returns an array.
 * @throws ArrayException
 *
 * @internal
 */
function array_replace_recursive(array $array, array ...$replacements) : array
{
    \error_clear_last();
    if ($replacements !== []) {
        $result = \array_replace_recursive($array, ...$replacements);
    } else {
        $result = \array_replace_recursive($array);
    }
    if ($result === null) {
        throw ArrayException::createFromPhpError();
    }
    return $result;
}
/**
 * array_replace replaces the values of
 * array with values having the same keys in each of the following
 * arrays. If a key from the first array exists in the second array, its value
 * will be replaced by the value from the second array. If the key exists in the
 * second array, and not the first, it will be created in the first array.
 * If a key only exists in the first array, it will be left as is.
 * If several arrays are passed for replacement, they will be processed
 * in order, the later arrays overwriting the previous values.
 *
 * array_replace is not recursive : it will replace
 * values in the first array by whatever type is in the second array.
 *
 * @param array $array The array in which elements are replaced.
 * @param array $replacements Arrays from which elements will be extracted.
 * Values from later arrays overwrite the previous values.
 * @return array Returns an array.
 * @throws ArrayException
 *
 * @internal
 */
function array_replace(array $array, array ...$replacements) : array
{
    \error_clear_last();
    if ($replacements !== []) {
        $result = \array_replace($array, ...$replacements);
    } else {
        $result = \array_replace($array);
    }
    if ($result === null) {
        throw ArrayException::createFromPhpError();
    }
    return $result;
}
/**
 * array_flip returns an array in flip
 * order, i.e. keys from array become values and values
 * from array become keys.
 *
 * Note that the values of array need to be valid
 * keys, i.e. they need to be either integer or
 * string. A warning will be emitted if a value has the wrong
 * type, and the key/value pair in question will not be included
 * in the result.
 *
 * If a value has several occurrences, the latest key will be
 * used as its value, and all others will be lost.
 *
 * @param array $array An array of key/value pairs to be flipped.
 * @return array Returns the flipped array on success.
 * @throws ArrayException
 * @deprecated The Safe version of this function is no longer needed in PHP 8.0+
 *
 * @internal
 */
function array_flip(array $array) : array
{
    \error_clear_last();
    $result = \array_flip($array);
    if ($result === null) {
        throw ArrayException::createFromPhpError();
    }
    return $result;
}
/**
 * This function sorts an array such that array indices maintain their
 * correlation with the array elements they are associated with.
 *
 * This is used mainly when sorting associative arrays where the actual
 * element order is significant.
 *
 * @param array $array The input array.
 * @param int $sort_flags You may modify the behavior of the sort using the optional parameter
 * sort_flags, for details see
 * sort.
 * @throws ArrayException
 * @deprecated The Safe version of this function is no longer needed in PHP 8.0+
 *
 * @internal
 */
function arsort(array &$array, int $sort_flags = \SORT_REGULAR) : void
{
    \error_clear_last();
    $result = \arsort($array, $sort_flags);
    if ($result === \false) {
        throw ArrayException::createFromPhpError();
    }
}
/**
 * This function sorts an array such that array indices maintain
 * their correlation with the array elements they are associated
 * with.  This is used mainly when sorting associative arrays where
 * the actual element order is significant.
 *
 * @param array $array The input array.
 * @param int $sort_flags You may modify the behavior of the sort using the optional
 * parameter sort_flags, for details
 * see sort.
 * @throws ArrayException
 * @deprecated The Safe version of this function is no longer needed in PHP 8.0+
 * @internal
 */
function asort(array &$array, int $sort_flags = \SORT_REGULAR) : void
{
    \error_clear_last();
    $result = \asort($array, $sort_flags);
    if ($result === \false) {
        throw ArrayException::createFromPhpError();
    }
}
/**
 * Sorts an array by key in reverse order, maintaining key to data
 * correlations. This is useful mainly for associative arrays.
 *
 * @param array $array The input array.
 * @param int $sort_flags You may modify the behavior of the sort using the optional parameter
 * sort_flags, for details see
 * sort.
 * @throws ArrayException
 * @deprecated The Safe version of this function is no longer needed in PHP 8.0+
 *
 * @internal
 */
function krsort(array &$array, int $sort_flags = \SORT_REGULAR) : void
{
    \error_clear_last();
    $result = \krsort($array, $sort_flags);
    if ($result === \false) {
        throw ArrayException::createFromPhpError();
    }
}
/**
 * Sorts an array by key, maintaining key to data correlations. This is
 * useful mainly for associative arrays.
 *
 * @param array $array The input array.
 * @param int $sort_flags You may modify the behavior of the sort using the optional
 * parameter sort_flags, for details
 * see sort.
 * @throws ArrayException
 * @deprecated The Safe version of this function is no longer needed in PHP 8.0+
 *
 * @internal
 */
function ksort(array &$array, int $sort_flags = \SORT_REGULAR) : void
{
    \error_clear_last();
    $result = \ksort($array, $sort_flags);
    if ($result === \false) {
        throw ArrayException::createFromPhpError();
    }
}
/**
 * This function sorts an array.  Elements will be arranged from
 * lowest to highest when this function has completed.
 *
 * @param array $array The input array.
 * @param int $sort_flags The optional second parameter sort_flags
 * may be used to modify the sorting behavior using these values:
 *
 * Sorting type flags:
 *
 *
 * SORT_REGULAR - compare items normally;
 * the details are described in the comparison operators section
 *
 *
 * SORT_NUMERIC - compare items numerically
 *
 *
 * SORT_STRING - compare items as strings
 *
 *
 *
 * SORT_LOCALE_STRING - compare items as
 * strings, based on the current locale. It uses the locale,
 * which can be changed using setlocale
 *
 *
 *
 *
 * SORT_NATURAL - compare items as strings
 * using "natural ordering" like natsort
 *
 *
 *
 *
 * SORT_FLAG_CASE - can be combined
 * (bitwise OR) with
 * SORT_STRING or
 * SORT_NATURAL to sort strings case-insensitively
 *
 *
 *
 * @throws ArrayException
 * @deprecated The Safe version of this function is no longer needed in PHP 8.0+
 * @internal
 */
function sort(array &$array, int $sort_flags = \SORT_REGULAR) : void
{
    \error_clear_last();
    $result = \sort($array, $sort_flags);
    if ($result === \false) {
        throw ArrayException::createFromPhpError();
    }
}
/**
 * This function will sort an array by its values using a user-supplied
 * comparison function.  If the array you wish to sort needs to be sorted by
 * some non-trivial criteria, you should use this function.
 *
 * @param array $array The input array.
 * @param callable $value_compare_func The comparison function must return an integer less than, equal to, or greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the second.
 * Note that before PHP 7.0.0 this integer had to be in the range from -2147483648 to 2147483647.
 *
 * Returning non-integer values from the comparison
 * function, such as float, will result in an internal cast to
 * integer of the callback's return value. So values such as
 * 0.99 and 0.1 will both be cast to an integer value of 0, which will
 * compare such values as equal.
 * @throws ArrayException
 * @deprecated The Safe version of this function is no longer needed in PHP 8.0+
 *
 * @internal
 */
function usort(array &$array, callable $value_compare_func) : void
{
    \error_clear_last();
    $result = \usort($array, $value_compare_func);
    if ($result === \false) {
        throw ArrayException::createFromPhpError();
    }
}
/**
 * Creates an array by using the values from the
 * keys array as keys and the values from the
 * values array as the corresponding values.
 *
 * @param array $keys Array of keys to be used. Illegal values for key will be
 * converted to string.
 * @param array $values Array of values to be used
 * @return array Returns the combined array, FALSE if the number of elements
 * for each array isn't equal.
 * @throws ArrayException
 * @deprecated
 *
 * @internal
 */
function array_combine(array $keys, array $values) : array
{
    \error_clear_last();
    $result = \array_combine($keys, $values);
    if ($result === \false) {
        throw ArrayException::createFromPhpError();
    }
    return $result;
}
