<?php

/**
 * @property string $meta_key;
 * @property string $field_name;
 * @property string $field_type;
 * @property bool $render
 */
abstract class TavakalColumn
{
    /**
     * @var string $meta_key
     */
    public string $meta_key;
    /**
     * @var string $field_name
     */
    public string $field_name;
    /**
     * @var  string $field_type
     */
    public $field_type;

    /**
     * @var bool $render
     */
    public $order = 0;

    public string $value_template = '%value%';

    public string $post_type = '';
    /**
     * @var bool $render
     */
    public bool $render = true;

    public ?TavakalColumn $nested_meta = null;

    public function get_field_type_name(): string
    {
        return $this->get_type();
    }


    public function get_type(): string
    {
        return $this::name;
    }


    public function get_meta_key(): string
    {
        return $this->meta_key;
    }

    /**
     * @param $field_name
     * @return $this
     */
    public function set_field_name($field_name): TavakalColumn
    {
        $this->field_name = $field_name;
        return $this;
    }


    public function set_render($render): TavakalColumn
    {
        $this->render = $render;
        return $this;
    }

    public function set_meta_key($meta_key): TavakalColumn
    {
        $this->meta_key = $meta_key;
        return $this;
    }


    public function set_order($order = 0): TavakalColumn
    {
        $this->order = $order;
        return $this;
    }

    public function set_post_type($post_type): TavakalColumn
    {
        if (!empty($post_type)) {
            $this->post_type = $post_type;
        }
        return $this;
    }


    public function set_value_template($value_template): TavakalColumn
    {
        if (!empty($value_template)) {
            $this->value_template = $value_template;
        }
        return $this;
    }


    public function set_nested_meta(TavakalColumn $nested_meta): TavakalColumn
    {
        $this->nested_meta = $nested_meta;
        return $this;
    }


    public function is_text(): bool
    {
        return $this->get_type() === TextType::name;
    }

    public function is_object(): bool
    {
        return $this->get_type() === ObjectType::name;
    }

    public function is_boolean(): bool
    {
        return $this->get_type() === BooleanType::name;
    }

    public function is_date(): bool
    {
        return $this->get_type() === DateType::name;
    }

    public function is_taxonomy(): bool
    {
        return $this->get_type() === TaxonomyType::name;
    }

    public function is_image(): bool
    {
        return $this->get_type() === ImageType::name;
    }

    public function render_value($post_id)
    {
        if ($this->post_type === 'user') {
            $post_meta = get_user_meta($post_id, $this->get_meta_key(), true);
        } else {
            $post_meta = get_post_meta($post_id, $this->get_meta_key(), true);
        }

        // if type taxonomy
        if ($this->is_taxonomy()) {
            /**
             * @var TaxonomyType $this
             */
            $post_meta = wp_get_post_terms($post_id, $this->get_meta_key());
            foreach ($post_meta as $term) {
                echo preg_replace('#\%(.*?)\%#',  esc_html($term->name), esc_html($this->value_template));
            }

        } else
        // if array of values
        if (is_array($post_meta)) {
            foreach ($post_meta as $key => $item) {
                $this->bring_the_action($item);
                if ($key !== array_key_last($post_meta)) {
                    echo "<hr>";
                }
            }
        } else {
            $this->bring_the_action($post_meta);
        }


    }


    public function bring_the_action($post_meta)
    {
        if ($this->nested_meta && $this->is_object()) {
            /**
             * @var ObjectType $nested_meta
             */
            $nested_meta = $this;
            $nested_meta = $nested_meta->nested_meta;
            $nested_meta->render_value($post_meta);
        }  else{
            echo $this->get_value($post_meta);
        }
    }

    /**
     * @param $value
     * @return string|null
     */
    public function get_value($value): ?string
    {
        if ($value === '') {
            return null;
        }
        $result = $this->get_type_value($value);
        return preg_replace('#\%(.*?)\%#', $result, $this->value_template);
    }

    abstract function get_type_value($value);

}