<?php

namespace Bolt\Extension\CND\RelationList\Utility;

class ConfigUtility
{

    /**
     * Replace search > replacement from array of replacements
     * @param $query
     * @param $parameters
     * @return mixed
     */
    public static function applyQueryParameters($query, $replacements){
        $path = [&$query]; // Helper variable for recursive replacements in loop below
        $i = 0;
        while($i<count($path) && $i<10000) {
            $current = &$path[$i++];
            foreach($current as $key => &$val) {
                // Replace a key placeholder in $query
                if(array_key_exists($key, $replacements)) {
                    $current[$replacements[$key]] = $val;
                    unset($current[$key]);
                }
                // Replace a value placeholder in $query
                if(is_string($val) && array_key_exists($val,$replacements)) {
                    $val = $replacements[$val];
                }
                // Add to the process $path $path
                if(is_array($val)) {
                    $path[] = &$val;
                }
            }
        }
        return $query;
    }

    /**
     * Generate a list of replacements from given parameters set and their default values
     * The custom fields array contains a constants, which can be accessed by calling %customfields%{parameters_key}%.
     * The reads the value of the parameter_key and tries to find a custom field which name matches read value of the parameter_key.
     * @param $defaults
     * @param $parameters
     * @param array $customfields
     * @return array
     */
    public static function getQueryParameters($defaults, $parameters, $customfields = []): array {

        $replacements = [];

        // Remove any empty strings from given parameters. These should fallback to defaults.
        // (Reason: Empty values from Input-Fields in forms have empty strings when nothing is specified instead of false/null)
        $filtered = array_filter($parameters, function($value,$key) {
            return (bool)$value;
        }, ARRAY_FILTER_USE_BOTH);
        $parameters = $filtered + $defaults;

        // Prepare replacements array with proper key/values for string replacement
        array_walk($parameters, function($value, $key) use (&$replacements, $customfields){
            // Syntax 1 - '%<key>%' - Normal values from parameters (relationlist global attrributes) array
            $replacements['%'.$key.'%'] = $value;
            // Syntax 2 - '%customfields%<key>%' - Values from $customfields (mapping of custom fields in teaser object)
            $mapped = is_string($value) && array_key_exists($value, $customfields) ? $customfields[$value] : null;
            $replacements['%customfields%'.$key.'%'] = $mapped;
        });

        return $replacements;
    }
}