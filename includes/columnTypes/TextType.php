<?php

class TextType extends TavakalColumn implements TavakalTypeInterface
{
    const name = 'text';

    public function get_type_value($value): string
    {
        return esc_html($value);
    }
}