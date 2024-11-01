<?php


class TavakalTable
{
    /**
     * @var TavakalColumn[] $tavakal_columns
     */
    public $tavakal_columns = [];

    public $post_type;


    public function __construct($post_type)
    {
        $this->post_type = $post_type;

        $this->tavakal_columns = get_option('tavakal_' . $post_type . '_columns', []);
        $this->tavakal_columns = array_filter($this->tavakal_columns, function ($field) {
            return $field->render;
        });

        $count_columns = count(array_keys($this->tavakal_columns));
        if($post_type === 'user'){
            add_filter('manage_users_columns', [$this, 'tavakal_post_column']);
            add_filter('manage_users_custom_column', [$this, 'tavakal_user_custom_column'], 1, 3);
        }else
       if ($count_columns) {
            add_filter('manage_' . $post_type . '_posts_columns', [$this, 'tavakal_post_column']);
            add_action('manage_' . $post_type . '_posts_custom_column', [$this, 'tavakal_posts_custom_column'], 1, 2);
        }
    }

    public function tavakal_posts_custom_column( $column_name, $post_id)
    {
        foreach ($this->tavakal_columns as $tavakal_column => $field) {
            if ($column_name === $tavakal_column) {
                $field->render_value($post_id);
            }
        }
    }


    public function tavakal_user_custom_column( $null ,$column_name, $post_id)
    {
        foreach ($this->tavakal_columns as $tavakal_column => $field) {
            if ($column_name === $tavakal_column) {
                $html = '';
                ob_start();
                $field->render_value($post_id);
                $html .= ob_get_contents();
                ob_get_clean();
                return $html;
            }
        }
    }

    public function tavakal_post_column($columns)
    {
        foreach ($this->tavakal_columns as $tavakal_column => $field) {
            /**
             * @var TavakalColumn $field
             */
            $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'desc' : 'asc';
            $columns[$tavakal_column] = "<a href='?custom_field_order=" . esc_html($tavakal_column) . "&post_type=" . esc_html($this->post_type) . "&order=" . esc_html($order) . "'  >" . esc_html(empty($field->field_name) ? $tavakal_column : $field->field_name) . "</a>";
        }
        return $columns;
    }
}