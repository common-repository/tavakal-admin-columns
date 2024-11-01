<?php


class TaxonomyType extends TavakalColumn  implements TavakalTypeInterface
{
    const  name = 'taxonomy';

    public function get_type_value($value):string
    {
        // do nothing
        return '';
    }
}