<?php
function Nth_of_type_Last($instance) {
    $inner_commas = [];
    foreach ($instance->getData("values") as $value) {
        $inner_commas[] = $instance->buildNthXQuery($instance->string_recreated, $value[0]["query_string"]);
    }

    return "(" . implode(" or ", $inner_commas) . ")";
}