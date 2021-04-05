<?php
function Only_of_type_Last($instance) {
    $string_recreated_without_first_slashes = preg_replace("~^[/]{1,2}~i", "", $instance->string_recreated);
    return 'count(../child::' . $string_recreated_without_first_slashes . ') = 1';
}