<?php
function First_of_type_Last($instance) {
    return $instance->buildNthXQuery($instance->string_recreated, "0n+1");
}