<?php
function Read_only_First() {
    $ors = [
        '(name()="input" and @contenteditable="false" and @readonly)',
        '(name()="input" and not(@contenteditable) and @readonly)',

        '(name()="textarea" and @contenteditable="false" and @readonly)',
        '(name()="textarea" and not(@contenteditable) and @readonly)',

        '(name()="select" and @contenteditable="false" and @readonly)',
        '(name()="select" and not(@contenteditable) and @readonly)',


/*        '(name()="input" and @readonly and @contenteditable="false")',
        '(name()="textarea" and @readonly and @contenteditable="false")',
        '(name()="select" and @readonly and @contenteditable="false")',*/
    ];

    return "(" . implode(" or ", $ors) . ")";
}