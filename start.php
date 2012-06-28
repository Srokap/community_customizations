<?php
/**
 * General customizations for the Elgg community site
 *
 */

elgg_register_event_handler('init', 'system', 'customizations_init');

function customizations_init() {

	elgg_extend_view('css/elgg', 'customizations/css');

	unexpose_function('auth.gettoken');

	// turn off site notifications for performance reasons
	unregister_notification_handler('site');

	elgg_register_event_handler('delete', 'user', 'customizations_purge_messages');

	// convert messageboard to private message interface
	elgg_register_widget_type('messageboard', elgg_echo("customizations:widget:pm"), elgg_echo("customizations:widget:pm:desc"), "profile");
	elgg_register_plugin_hook_handler('forward', 'system', 'customizations_pm_forward');

	// do not want the pages link in hover menu
	elgg_unextend_view('profile/menu/links', 'pages/menu');

	// button for flushing apc cache
	elgg_register_plugin_hook_handler('register', 'menu:admin_control_panel', 'customizations_control_panel');

	$action_path = elgg_get_plugins_path() . "community_customizations/actions";
	elgg_register_action('comments/edit', "$action_path/edit_comment.php", 'admin');
	elgg_register_action('admin/flush_apc', "$action_path/admin/flush_apc.php", 'admin');
}

/**
 * Forward to referrer if posting a pm from widget
 */
function customizations_pm_forward() {
	if (get_input('pm_widget') == true) {
		return $_SERVER['HTTP_REFERER'];
	}
}

/**
 * Delete messages from a user who is being deleted
 *
 * @param string   $event
 * @param string   $type
 * @param ElggUser $user
 */
function customizations_purge_messages($event, $type, $user) {

	// make sure we delete them all
	$entity_disable_override = access_get_show_hidden_status();
	access_show_hidden_entities(true);

	$messages = elgg_get_entities_from_metadata(array(
		'type' => 'object',
		'subtype' => 'messages',
		'metadata_name' => 'fromId',
		'metadata_value' => $user->getGUID(),
	));
	if ($messages) {
		foreach ($messages as $e) {
			$e->delete();
		}
	}

	access_show_hidden_entities($entity_disable_override);
}

function customizations_control_panel($hook, $type, $value) {
	$options = array(
		'name' => 'flush_apc',
		'text' => elgg_echo('apc:flush'),
		'href' => 'action/admin/flush_apc',
		'is_action' => true,
		'link_class' => 'elgg-button elgg-button-action',
	);
	$value[] = ElggMenuItem::factory($options);
	return $value;
}