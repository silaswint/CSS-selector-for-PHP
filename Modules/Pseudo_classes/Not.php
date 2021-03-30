<?php
function Not_First($instance) {
    $list_not = [];
    foreach ($instance->getData("values") as $value) {
        $instance->pre($value);
        $not_xpath_string = $instance->buildXPathString($value);
        $list_not[] = preg_replace('/^\[(.*)\]$/', '$1', $not_xpath_string); // delete first and last [] brackets
    }

    return "not(" . implode(" or ", $list_not) . ")";
}