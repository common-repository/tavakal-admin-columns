<?php

class DateType extends TavakalColumn  implements TavakalTypeInterface
{
    const name = 'date';

    public string $date_format = 'Y-m-d';

    public function get_type_value($value):string
    {
        return date($this->date_format, strtotime($value));
    }
}