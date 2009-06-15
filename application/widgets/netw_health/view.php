<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<div class="widget movable collapsable removable closeconfirm w32 left " id="widget-netw_health">
	<div class="widget-header"><span><?php echo $title ?></span></div>
	<div class="widget-content">
		<table summary="Network healt" class="healt">
				<tr>
					<td style="text-align: center">
						<div style="<?php echo ($host_value > 33) ? 'color: #ffffff;' : ''?>font-size: 22px; position: absolute; padding-top: 62px; padding-left: 10px;"><?php echo $host_value ?> %</div>
						<div style="<?php echo ($host_value > 12) ? 'color: #ffffff;' : ''?>font-size: 10px; position: absolute; padding-top: 84px; padding-left: 10px"><?php echo $host_label ?></div>
						<div class="border">
							<?php echo html::image($host_image, array('style' => 'height:'.$host_value.'px; width: 100%; padding-top: '.round(100-$host_value).'px', 'alt' => $host_label)) ?>
						</div>
					</td>
					<td style="text-align: center">
						<div style="<?php echo ($service_value > 33) ? 'color: #ffffff;' : ''?>font-size: 22px; position: absolute;padding-top: 62px; padding-left: 10px"><?php echo $service_value ?> %</div>
						<div style="<?php echo ($service_value > 12) ? 'color: #ffffff;' : ''?>font-size: 10px; position: absolute; padding-top: 84px; padding-left: 10px;"><?php echo $service_label ?></div>
						<div class="border">
							<?php echo html::image($service_image, array('style' => 'height:'.$service_value.'px; width: 100%; padding-top: '.round(100-$service_value).'px', 'alt' => $service_label)) ?>
						</div>
					</td>
				</tr>
			</table>
	</div>
</div>
<div style="clear:both"></div>