<?php r($Campaign);
    return;
?>

<h3><?php echo 'Contact Details: ';?> <a href="<?php echo add_query_arg(array('edit' => $Contact->get('id')), remove_query_arg('view')); ?>" class="button edit-new-h2" title="edit">edit</a></h3>
<table class="widefat fixed ctct_table" cellspacing="0">
    <thead>
        <th scope="col" class="column-name"><?php _e('Name', 'constant-contact-api'); ?></th>
        <th scope="col" class="column-title"><?php _e('Data', 'constant-contact-api'); ?></th>
    </thead>
    <tbody>
        <?php

    $alt = ''; $html = '';
    foreach ($Contact as $key => $value) {
        $alt = empty($alt) ? ' class="alt"' : '';

        if(empty($value)) { continue; }

        $html .= '<tr'.$alt.'>';

        if(is_array($value)) {
            switch($key) {
                case 'lists':
                    $html .= '<th>'.$Contact->getLabel($key).'</th>
                    <td>';
                    foreach($Contact->get('lists') as $List) {
                        $html .= '<li>'.$List->id.'</li>';
                    }
                    $html .= '</td>';
                break;
                case 'addresses':
                    $html .= sprintf('<th scope="row" valign="top" class="column-name">%s</th>
                        <td>', $Contact->getLabel($key));

                    if($personal = $Contact->getAddress('personal')) {
                        $html .= sprintf('<h3>%s</h3> <div>%s</div>',
                                         __('Personal Address', 'constant-contact-api'), $personal);
                    }

                    if($business = $Contact->getAddress('business')) {
                        $html .= sprintf('<h3>%s</h3> <div>%s</div>',
                                         __('Business Address', 'constant-contact-api'), $business);
                    }
                break;
                case 'email_addresses':
                    $html .= sprintf('<th scope="row" valign="top" class="column-name">%s</th>
                        <td>%s</td>', $Contact->getLabel($key), $Contact->get('email_address'));
                break;
                default:
                    $html .= sprintf('<th scope="row" valign="top" class="column-name">%s</th>
                        <td>%s</td>', $Contact->getLabel($key), $Contact->get($key));
                break;
            }
        } else {
            $html .= sprintf('<th scope="row" valign="top" class="column-name">%s</th>
                <td>%s</td>', $Contact->getLabel($key), $Contact->get($key));
        }
        $html .= '
        </tr>';
    }

    echo $html;
?>
</tbody>
</table>