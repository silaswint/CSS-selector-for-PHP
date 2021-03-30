<?php
function Required_First() {
    $ors = [
        '(name()="input" and @required)',
        '(name()="select" and @required)',
        '(name()="textarea" and @required)'
    ];

    return "(" . implode(" or ", $ors) . ")";
}