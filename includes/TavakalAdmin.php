<?php


class TavakalAdmin
{

    const meta_keys_types = [
        TextType::name => TextType::class,
        BooleanType::name => BooleanType::class,
        ObjectType::name => ObjectType::class,
        DateType::name => DateType::class,
        TaxonomyType::name => TaxonomyType::class,
        ImageType::name => ImageType::class,
    ];


    public function __construct()
    {
        add_action('init', function () {
            add_options_page(
                'Tavakal admin columns',
                'Tavakal admin column',
                'manage_options',
                'tavakal-admin-column',
                [$this, 'generate_settings_page']
            );
        });

        add_action('pre_get_posts', [$this, 'custom_meta_filter'], 10);
        add_action('pre_user_query', [$this, 'custom_meta_filter_user'], 1, 10);

        add_action('restrict_manage_posts', function ($post_type) {
            $href_to_tavakal_table = admin_url() . 'admin.php?page=tavakal-admin-column&post_type=' . $post_type;
            ?>
            <a href="<?php echo esc_url($href_to_tavakal_table); ?>"
               style="
    width: 115px;
    height: 15px;
    background: #4E9CAF;
    padding: 8px;
    text-align: center;
    border-radius: 5px;
    color: white;
    font-weight: bold;
    line-height: 29px;"><span>EDIT TABLE COLUMNS</span></a>
            <?php

        }, 1);


        add_action('admin_post_add_tavakal_columns', function () {

            if (!current_user_can('administrator')) {
                wp_redirect(wp_get_referer());
                exit;
            }


            $post_type = sanitize_text_field($_POST['post_type']);
            $tavakal_field_names = array_map('sanitize_text_field',$_POST['tavakal_field_names']);
            $tavakal_field_type = array_map('sanitize_text_field',$_POST['tavakal_field_types']);
            $tavakal_nested_metas = array_map('sanitize_text_field',$_POST['tavakal_nested_meta']);
            $tavakal_value_template = array_map('sanitize_text_field',$_POST['tavakal_value_template']);
            $render_columns = array_keys(array_map('sanitize_text_field',$_POST['tavakal_columns'] ) ?? []);
            $taxonomies = get_object_taxonomies($post_type);

            $columns = array_keys($tavakal_field_names);
            $collection = [];
            $order = 0;
            foreach ($columns as $column) {

                $type = in_array($column, $taxonomies) ? TaxonomyType::name : $tavakal_field_type[$column];

                if (!in_array($type, array_keys(self::meta_keys_types))) {
                    wp_redirect(wp_get_referer());
                    exit;
                }

                $type_class = self::meta_keys_types[$type];

                $order++;

                $father_tavakal_column = new $type_class();
                $father_tavakal_column
                    ->set_meta_key($column)
                    ->set_field_name($tavakal_field_names[$column])
                    ->set_render(in_array($column, $render_columns))
                    ->set_order($order)
                    ->set_value_template($tavakal_value_template[$column])
                    ->set_post_type($post_type);
                $tavakal_column = $father_tavakal_column;
                $nested_metas = $tavakal_nested_metas[$column];
                $nested_metas = explode('.', $nested_metas);


                //  for nested meta keys
                foreach ($nested_metas as $nested_meta) {
                    preg_match('#\[(.*?)\]#', $nested_meta, $match);
                    $type = 'text';
                    $meta_key = preg_replace('#\[(.*?)\]#', '', $nested_meta);
                    if (isset($match[1])) {
                        $type = $match[1];
                    }

                    if (!in_array($type, array_keys(self::meta_keys_types))) {
                        wp_redirect(wp_get_referer());
                        exit;
                    }

                    $type_class = self::meta_keys_types[$type];

                    if ($meta_key && $type) {
                        $new_nested_tavakal_column = new $type_class();
                        $new_nested_tavakal_column->set_meta_key($meta_key);

                        $tavakal_column
                            ->set_nested_meta($new_nested_tavakal_column);

                        $tavakal_column = $new_nested_tavakal_column;
                    }

                    if ($type !== 'object') {
                        break;// you only can nest after object
                    }
                }

                $collection[$column] = $father_tavakal_column;
            }

            // update settings
            update_option('tavakal_' . $post_type . '_columns', $collection);
            wp_redirect(wp_get_referer());
            exit;
        });
    }


    public function generate_settings_page()
    {
        $post_types = get_post_types();
        $post_types[] = 'user';
        $chosen_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : null;
        echo "<select id='post-type' name='post_type'>";

        if ($chosen_post_type) {
            echo "<option disabled selected value='" . esc_html($chosen_post_type) . "'>" . esc_html($chosen_post_type) . "</option>";
        } else {
            echo "<option disabled selected  value=''>" . esc_html('Choose post type') . "</option>";
        }


        foreach ($post_types as $post_Type) {
            echo "<option value='" . esc_html($post_Type) . "'>" . esc_html($post_Type) . "</option>";
        }
        echo "</select>";


        ?>

        <div>
            <h2>How to use nested values</h2>
            <p>You need to use a specific syntax to use the feature</p>
            <p>Examples:</p>
            <ul>
                <li><h3><code>[object]car.[text]car_model</code> //open brackets to define the meta_key type then type the meta_key</h3></li>
                <li><h3><code>[object]car.[object]company.[text]company_location</code> // ps: you can only nest after objects</h3></li>
            </ul>

        <p>Not the greatest UI 😁 , but it's totally free 😘</p>
        </div>

            <?php

        if ($chosen_post_type):

            ?>
            <style>
                /*table, th, td {*/
                /*    border: 1px solid black;*/
                /*}*/
                table {
                    font-size: 16px;
                    width: 200px;
                    margin: 20px auto;
                }

                td {
                    padding: 5px 10px;
                    cursor: pointer;
                }

                tr:nth-child(odd) {
                    background: #ddd;
                }

                tr:nth-child(even) {
                    background: #bbb;
                }
            </style>
            <hr>
            <div>
                <form action="<?php echo esc_url(admin_url("admin-post.php")); ?>" method="POST">
                    <button>Save settings</button>
                    <input name="action" type="hidden" value="add_tavakal_columns">
                    <input name="post_type" type="hidden" value="<?php echo esc_html($chosen_post_type); ?>">
                    <table>
                        <tr>
                            <th>Meta</th>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Nested values (Only for object type)</th>
                            <th>Value template</th>
                            <th>Render</th>
                            <th>Drag</th>

                        </tr>

                        <?php
                        $meta_keys = $this->us_get_meta_keys_for_post_type($chosen_post_type);
                        $taxonomies = get_object_taxonomies($chosen_post_type);
                        $meta_keys = [...$meta_keys, ...$taxonomies];
                        /**
                         * @var TavakalColumn[] $columns ;
                         */
                        $columns = get_option('tavakal_' . $chosen_post_type . '_columns', []);


                        foreach ($meta_keys as $meta_key) {
                            if(empty($meta_key)){
                                continue;
                            }
                            if (!isset($columns[$meta_key])) {
                                $columns[$meta_key] = (object)[];
                            }
                        }

                        foreach ($columns as $meta_key => $tavakal_column):
                            $checked = @$tavakal_column->render ? 'checked' : '';

                            $nested_values = '';
                            $field_type = in_array($meta_key, $taxonomies) ? 'taxonomy' : ($tavakal_column instanceof TavakalColumn ? $tavakal_column->get_field_type_name() : 'text');
                            $value_template = @$tavakal_column->value_template ?? '%value%';

                            if (isset($tavakal_column->nested_meta)) {
                                while ($tavakal_column->nested_meta) {
                                    $tavakal_column = $tavakal_column->nested_meta;
                                    $nested_values .= "[{$tavakal_column->get_field_type_name()}]" . $tavakal_column->meta_key . '.';
                                }
                            }
                            ?>
                            <tr draggable="true" ondragstart="dragit(event)" ondragover="dragover(event)">
                                <td><?php echo esc_html($meta_key) ?></td>
                                <td><input id="tavakal_label_<?php echo esc_html($meta_key); ?>" type="text"
                                           name='tavakal_field_names[<?php echo esc_html($meta_key); ?>]'
                                           value="<?php echo @esc_html($tavakal_column->field_name ?? ''); ?>"
                                           placeholder="Label"></td>

                                <td>
                                    <select id="tavakal_type_<?php echo esc_html($meta_key); ?>"
                                            name="tavakal_field_types[<?php echo esc_html($meta_key); ?>]">

                                        <?php if (isset($field_type) && $field_type): ?>

                                            <option value="<?php echo @esc_html($field_type); ?>"
                                                    selected><?php echo @esc_html($field_type); ?></option>

                                        <?php endif; ?>

                                        <?php foreach (self::meta_keys_types as $type => $type_class): ?>
                                            <option value=<?php echo esc_html($type); ?>><?php echo esc_html(ucfirst($type)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input id="tavakal_nested_meta_<?php echo esc_html($meta_key); ?>"
                                           name="tavakal_nested_meta[<?php echo esc_html($meta_key); ?>]"
                                           value="<?php echo @esc_html($nested_values); ?>" type="text"
                                           placeholder="Nested values">
                                </td>

                                <td>
                                    <input id="tavakal_template_<?php echo esc_html($meta_key); ?>"
                                           name="tavakal_value_template[<?php echo esc_html($meta_key); ?>]"
                                           value="<?php echo esc_html($value_template); ?>" type="text"
                                           placeholder="Value template">
                                </td>

                                <td>
                                    <input name="tavakal_columns[<?php echo esc_html($meta_key); ?>]"
                                           type="checkbox" <?php echo @esc_html($checked); ?>
                                           onclick="checkRow('<?php echo esc_html($meta_key); ?>',this)">
                                </td>
                                <td>
                                    <span>🤚</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>


                    </table>

                    <button>Save settings</button>
                </form>
            </div>
        <?php

        endif;

        ?>
        <script>

            let shadow

            function dragit(event) {
                shadow = event.target;
            }

            function dragover(e) {
                let children = Array.from(e.target.parentNode.parentNode.children);
                if (children.indexOf(e.target.parentNode) > children.indexOf(shadow)) {
                    e.target.parentNode.after(shadow);
                } else {
                    e.target.parentNode.before(shadow);
                }
            }

            let select = document.getElementById('post-type');
            select.addEventListener('change', function (e) {
                window.location.replace('/wp-admin/admin.php?page=tavakal-admin-column&post_type=' + e.target.value);
            });

        </script>
        <?php


    }


    public function us_get_meta_keys_for_post_type($post_type, $sample_size = 5): array
    {

        $meta_keys = array();

        if ($post_type === 'user') {
            $posts = get_users(['limit' => $sample_size]);
        } else {
            $posts = get_posts(array('post_type' => $post_type, 'limit' => $sample_size, 'order' => 'DESC'));
        }


        foreach ($posts as $post) {
            if ($post_type === 'user') {
                $post_meta_keys = get_user_meta($post->ID);
                $post_meta_keys = array_keys($post_meta_keys);
            } else {
                $post_meta_keys = get_post_custom_keys($post->ID);
            }
            $meta_keys = array_merge($meta_keys, $post_meta_keys);
        }

        // Use array_unique to remove duplicate meta_keys that we received from all posts
        // Use array_values to reset the index of the array
        return array_values(array_unique($meta_keys));

    }


    public function custom_meta_filter_user($user_search)
    {
        global $wpdb;
        global $pagenow;

        if ($pagenow !== 'users.php') {
            return;
        }

        if (isset($_GET['orderby']) && isset($vars['order']) && isset($_GET['custom_field_order'])) {
            return;
        }
        $orderBy = isset($_GET['custom_field_order']) ? sanitize_text_field($_GET['custom_field_order']) : null;
        if ($orderBy) {
            $vars = $user_search->query_vars;
            $user_search->query_from .= " INNER JOIN {$wpdb->usermeta} tavakal_m1 ON {$wpdb->users}.ID=tavakal_m1.user_id AND (tavakal_m1.meta_key='$orderBy')";
            $user_search->query_orderby = ' ORDER BY CASE WHEN date(tavakal_m1.meta_value) IS NULL THEN 1 ELSE 0 END, date(tavakal_m1.meta_value)' . $vars['order'];
        }

    }


    public function custom_meta_filter($query)
    {
        global $pagenow;
        if ($pagenow === 'edit.php') {
            if (isset($_GET['custom_field_order'])) {
                $custom_field_order = sanitize_text_field($_GET['custom_field_order']);
                $order = sanitize_text_field($_GET['order']);
                if ($custom_field_order) {
                    $query->set('orderby', 'meta_value');
                    $query->set('meta_key', $custom_field_order);
                    $query->set('order', $order);
                }
            }
        }
    }

}