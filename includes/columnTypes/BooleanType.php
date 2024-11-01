<?php

class BooleanType extends TavakalColumn implements TavakalTypeInterface
{

    const name = 'boolean';

    public function get_type_value($value): string
    {
        return esc_html((bool)$value ? 'True' : 'False');
    }
}