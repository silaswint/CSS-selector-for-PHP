<?php
function Optional_First() {
    $ors = [
        '(not(@required) and name()="input")',
        '(not(@required) and name()="select")',
        '(not(@required) and name()="textarea")',
    ];

    return "(" . implode(" or ", $ors) . ")";
}