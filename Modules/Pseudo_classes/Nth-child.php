<?php
function Nth_child_First($instance) {
    $inner_commas = [];
    foreach ($instance->getData("values") as $value) {
        $inner_commas[] = $instance->buildNthXQuery($instance->string_recreated, $value[0]["query_string"]);
    }

    return "(" . implode(" or ", $inner_commas) . ")";
}