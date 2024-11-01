<?php

class ObjectType  extends TavakalColumn  implements TavakalTypeInterface
{
    const name = 'object';

    public string $link = '';

    public function get_type_value($value): string
    {
        $post = get_post($value);
        $this->link = admin_url('post.php?post=' . $post->ID) . '&action=edit';
        return "<a href=" . esc_url($this->link) . ">" . esc_html($post->post_title) . "</a>";
    }

    public function get_object_link(): string
    {
        return $this->link;
    }

}