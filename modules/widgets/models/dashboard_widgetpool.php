<?php

/**
 * Autogenerated class Dashboard_WidgetPool_Model
 *
 * @todo: documentation
 */
class Dashboard_WidgetPool_Model extends BaseDashboard_WidgetPool_Model {

	/**
	 * Get a list of widget names that are available in the system
	 *
	 * @returns array keys for widget names, values for friendly names
	 */
	public static function get_available_widgets()
	{
		$widgets = array();
		foreach(Kohana::include_paths() as $path) {
			if(is_dir($path . 'widgets')) {
				foreach(scandir($path . 'widgets') as $widget_name) {
					if(substr($widget_name, 0, 1) == '.')
						continue;
					if(!is_dir($path . 'widgets/' . $widget_name))
						continue;
					if(!is_file($path . 'widgets/' . $widget_name . '/' . $widget_name . '.php'))
						continue;

					$widget_model = new Ninja_Widget_Model();
					$widget_model->set_name($widget_name);
					$widget = $widget_model->build();
					$metadata = $widget->get_metadata();
					if(!$metadata['instanceable'])
						continue;
					$metadata['path'] = $widget_model->widget_path();
					$widgets[$widget_name] = $metadata;
				}
			}
		}
		uasort($widgets, function($a, $b) {
			if($a['friendly_name'] < $b['friendly_name'])
				return -1;
			if($a['friendly_name'] > $b['friendly_name'])
				return 1;
			return 0;
		});
		return $widgets;
	}
}
