<?php
function Read_only_First() {
    $ors = [
        '(not(@contenteditable="false") and not(@contenteditable="true"))',
        '(@contenteditable="false" and @readonly)',
        '(name()="input" and @readonly and @contenteditable="false")',
        '(name()="textarea" and @readonly and @contenteditable="false")',
        '(name()="select" and @readonly and @contenteditable="false")',
    ];

    return "(" . implode(" or ", $ors) . ")";
}