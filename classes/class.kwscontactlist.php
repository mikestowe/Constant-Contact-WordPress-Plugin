<?php

use Ctct\Components\Contacts\ContactList;
use Ctct\Components\EmailMarketing\MessageFooter;
class KWSContactList extends ContactList {

	private static $read_only = array('contact_count', 'id');

	function __construct($List = '') {

		if(is_array($List)) {
			$List = $this->prepare($List, true);
		}

		if(!empty($List) && (is_array($List) || $List instanceof ContactList)) {
			foreach($List as $k => &$v) {
				$this->{$k} = $v;
			}
		}
	}

	/**
     * Factory method to create a Contact object from an array
     * @param array $props - Associative array of initial properties to set
     * @return Contact
     */
    public static function create(array $props)
    {
        $List = new KWSContactList($props);

        return $List;
    }

    public function update(array $new_contact_array) {

    	$existing_contact = wp_clone($this);

    	$new_contact = new KWSContactList($new_contact_array, true);

    	unset($new_contact->id, $new_contact->status, $new_contact->source, $new_contact->source_details);

    	foreach($new_contact as $k => $v) {
    		$existing_contact->{$k} = $v;
    	}

    	return $existing_contact;
    }

    private function prepareAddress(array $address) {
    	return wp_parse_args($address, array('line1' => '', 'line2' => '', 'line3' => '', 'city' => '', 'address_type' => 'PERSONAL', 'state_code' => '', 'country_code' => '', 'postal_code' => '', 'sub_postal_code' => ''));
    }


    private function prepareMessageFooter($message_footer_array) {
    	$defaults = array("city" => '', "state" => '', "country" => '', "organization_name" => '', "address_line_1" => '', "address_line_2" => '', "address_line_3" => '', "international_state" => '', "postal_code" => '', "include_forward_email" => false, "forward_email_link_text" => '', "include_subscribe_link" => true, "subscribe_link_text" => '');
		$message_footer = wp_parse_args( $message_footer_array, $defaults );
		$message_footer['country'] = strtoupper($message_footer['country']);
		return $message_footer;
    }

	private function prepare(array $list_array, $add = false) {

		$defaults = array(
			'id' => NULL,
			'name' => NULL,
			'subject' => NULL,
			'from_name' => NULL,
			'from_email' => NULL,
			'reply_to_email' => NULL,
			'template_type' => NULL,
			'created_date' => NULL,
			'modified_date' => NULL,
			'last_run_date' => NULL,
			'next_run_date' => NULL,
			'status' => NULL,
			'is_permission_reminder_enabled' => NULL,
			'permission_reminder_text' => NULL,
			'is_view_as_webpage_enabled' => NULL,
			'view_as_web_page_text' => NULL,
			'view_as_web_page_link_text' => NULL,
			'greeting_salutations' => NULL,
			'greeting_name' => NULL,
			'greeting_string' => NULL,
			'message_footer' => NULL,
			'tracking_summary' => NULL,
			'email_content' => NULL,
			'email_content_format' => NULL,
			'style_sheet' => NULL,
			'text_content' => NULL,
			'sent_to_contact_lists' => array(),
	    	'click_through_details' => array(),
		);

        $List = wp_parse_args( $list_array, $defaults );

        if (array_key_exists("message_footer", $List)) {
            $List['message_footer'] = MessageFooter::create($this->prepareMessageFooter($List['message_footer']));
        }

        $List['greeting_name'] = strtoupper($List['greeting_name']);
        if(!in_array($List['greeting_name'], array('FIRST_NAME', 'LAST_NAME', 'FIRST_AND_LAST_NAME', 'NONE'))) {
        	$List['greeting_name'] = 'NONE';
        }

		return $List;
	}

	function getLabel($key) {

		switch($key) {
			case 'id':
				return 'ID';
				break;
			case 'email_addresses':
				return 'Email Address';
				break;
		}

		$key = ucwords(preg_replace('/\_/ism', ' ', $key));
	    $key = preg_replace('/Addr([0-9])/', __('Address $1', 'constant-contact-api'), $key);
	    $key = preg_replace('/Field([0-9])/', __('Field $1', 'constant-contact-api'), $key);

		return $key;
	}

	function set($key, $value) {
	    switch($key) {
	        case 'name':
	            $this->{$key} = $value;
	            break;
	        default:
	        	return false;
		        break;
	    }

	    return true;
	}

	/**
	 * Convert an array of List objects into HTML output
	 * @param  string $as    Format of HTML; `list`|`select`
	 * @param  array  $items List array
	 * @param  array  $atts  Settings; `fill`, `selected`, `format`; `format` should use replacement tags with the tag being the name of the var of the List object you want to replace. For example, `%%name%% (%%contact_count%% Contacts)` will return an item with the content "List Name (140 Contacts)"
	 *
	 * `showhidden` If true, will exclude lists that have a status of "hidden" in http://dotcms.constantcontact.com/docs/contact-list-api/contactlist-collection.html
	 * @return [type]        [description]
	 */
	static function outputHTML($passed_items = array(), $atts = array()) {

		$settings = wp_parse_args($atts, array(
			'type' => 'checkboxes',
			'fill' => true, // Fill data into lists
			'format' => '<span>%%name%%</span>', // Choose HTML format for each item
			'id_attr' => 'ctct-%%id%%', // Pass a widget instance
			'name_attr' => 'lists',
			'checked' => array(), // If as select, what's active?
			'include' => array(),
			'showhidden' => true,
		));

		extract($settings);

		$items = array();
		if($passed_items === 'all') {
			$items = WP_CTCT::getInstance()->cc->getAllLists();
		} elseif(!empty($passed_items) && is_array($passed_items)) {
			foreach($passed_items as $item) {
				if($fill) {
					$id = is_object($item) ? $item->id : $item;
					$item = WP_CTCT::getInstance()->cc->getList(CTCT_ACCESS_TOKEN, $id);
				}
				$items[] = $item;
			}
		}

		switch($type) {
			case 'hidden':
				$before = $before_item = $after_item = $after = '';
				$format = '<input type="hidden" value="%%id%%" name="%%name_attr%%[]" />';
				break;
			case 'ul':
				$before = '<ul class="ul-square">';
					$before_item = '<li>';
					$after_item = '</li>';
				$after = '</ul>';
			case 'dropdown':
			case 'select':
			case 'multiselect':
				$multiple = '';

				// Even though the multiselect option is no longer available
				// in the settings, keep this around for backward compatibility.
				// And if crazy people want multi-selects
				if($type === 'select' || $type === 'multiselect') {
					$multiple = ' multiple="multiple"';
				}
				$before = '<select name="%%name_attr%%"'.$multiple.' class="select2 ctct-lists">';
					$before_item = '<option value="%%id%%">';
					$after_item = '</option>';
				$after = '</select>';
				break;
			case 'checkbox':
			case 'checkboxes':
				$before = '<ul class="ctct-lists ctct-checkboxes">';
					$before_item = '<li><label for="%%id_attr%%"><input type="checkbox" id="%%id_attr%%" value="%%id%%" name="%%name_attr%%[]" %%checked%% /> ';
					$after_item = '</label></li>';
				$after = '</ul>';
				break;
		}

		$output = $before;

		$items_output = '';
		foreach($items as &$item) {
			
			// If include was specified, then we need to skip lists not included
			if(is_array($passed_items) && (!empty($include) && !in_array($item->id, $include)) || ($item->status === 'HIDDEN' && !$showhidden)) {
				#continue;
			}

			$item = new KWSContactList($item);

			$item_content = (!empty($format) || is_null($format)) ? $format : $item->name;

			$item_output = $before_item.$item_content.$after_item."\n";
			$item_output = str_replace('%%name_attr%%', $name_attr, $item_output);
			$item_output = str_replace('%%id_attr%%', $id_attr, $item_output);
			$item_output = str_replace('%%id%%', sanitize_title( $item->get('id') ), $item_output);
			$item_output = str_replace('%%name%%',	$item->get('name', false), $item_output);
			$item_output = str_replace('%%status%%', $item->get('status', false), $item_output);
			$item_output = str_replace('%%contact_count%%', $item->get('contact_count', true), $item_output);

			$item_output = str_replace('%%checked%%', checked((in_array($item->get('id'), (array)$checked) || (is_null($checked) && $item->get('status') === 'ACTIVE')), true, false), $item_output);

			$items_output .= $item_output;
		}

		$output .= $items_output;

		$output .= $after;

		return $output;
	}

	private function is_editable($key) {
	    return !in_array($key, $this::$read_only);
	}

	function get($key, $format = false) {
		switch($key) {
			case 'created_date':
			case 'next_run_date':
			case 'modified_date':
			case 'last_run_date':
				$date = date_i18n(get_option('date_format').' '.get_option('time_format'), strtotime($this->{$key}), true);

				return $format ? $date : $this->{$key};
				break;
			case 'status':
				return $format ? ucfirst(strtolower($this->{$key})) : $this->{$key};
				break;
			default:
				if(isset($this->{$key})) {
					return ($format && $this->is_editable($key)) ? '<span class="editable" data-name="'.$key.'" data-id="'.$this->get('id').'">'.esc_html($this->{$key}).'</span>' : $this->{$key};
				} else {
					return '';
				}
				break;
		}
	}
}