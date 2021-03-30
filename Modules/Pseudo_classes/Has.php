<?php
function Has_Last($instance) {
    $has_string = "";
    foreach ($instance->getData("values") as $node) {
        $has_build_xpath_string = $instance->buildXPathString($node);
        $has_string .= $has_build_xpath_string;
    }

    if($has_string !== "") {
        return "self::*" . $has_string . "";
    }

    return NULL;
}