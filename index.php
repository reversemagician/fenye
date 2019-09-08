<?php
include 'fenye.php';

$fenye=new fenye();
$fenye->setstyle();//配置$this->style
$fenye->settotal(100);//设置数据总数
	$model=[
				'html'=>[
					'btn'=>[//全局按钮样式
						'selection'=>'<a href="#href#" class="config_fanye_a config_fanye_a_">##text##</a>',
					]
				],
				'html_btn_order'=>[
					'order'=>[ //显示顺序
						'loop_btn',
						'first_btn',
						'prev_btn',
						'next_btn',
						'last_btn',
						'count_p_ele',
						'count_t_ele',
					],
				]
			];
	$css=[
		'color'=>'#4476A7'
	];
$fenye->sethtmlmodel('default',$model,$css);//动态配置模板参数
$fenye_result=$fenye->result();//获取结果
echo $fenye_result['css'],$fenye_result['html'];