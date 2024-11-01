<?php

class ImageType extends TavakalColumn  implements TavakalTypeInterface
{
    const name = 'image';

    public function get_type_value($value): string
    {
        return "<img src='" . esc_url(wp_get_attachment_image_url($value)) . "' width=100 height=100 />";
    }
}