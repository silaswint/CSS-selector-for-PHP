<?php
function First_child_First($instance) {
    return $instance->buildNthXQuery($instance->string_recreated, "0n+1");
}