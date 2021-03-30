<?php
function Nth_of_type_Last($instance) {
    $inner_commas = [];
    foreach ($instance->getData("values") as $value2) {
        $inner_commas[] = $instance->buildNthXQuery($instance->string_recreated, $value2[0]["query_string"]);
    }

    return "(" . implode(" or ", $inner_commas) . ")";
}