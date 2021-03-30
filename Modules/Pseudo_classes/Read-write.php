<?php
function Read_write_First() {
    $ors = [
        '(not(@contenteditable="false") and @contenteditable)',
        '(@contenteditable="true")',
        '(not(@readonly) and name()="input")',
        '(not(@readonly) and name()="textarea")',
        '(not(@readonly) and name()="select")',
    ];

    return "(" . implode(" or ", $ors) . ")";
}