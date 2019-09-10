<?php
include 'fenye.php';

//////最简单的使用放
$fen=new fenye();
$fen->settotal(100);//总数据量，至少调用一次该方法
$fen_result=$fen->result();//获取分页结果
echo '简单使用：<br>'.$fen_result['css'],$fen_result['html'];//输出获取结果


//////可用方法 (4个)
$fenye=new fenye();
$fenye->settotal(100);//设置数据总数 方法一
$fenye->setstyle(['first'=>'first','last'=>'last','p'=>'p2']);//配置$this->style 方法二
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
		'color'=>'#4476A7',
	];
$fenye->sethtmlmodel('default',$model,$css);//动态配置模板参数 方法三
$fenye_result=$fenye->result();//获取结果 方法四
echo '<br><br><br>可用方法：<br>'.$fenye_result['css'],$fenye_result['html'];

//////动态默认模板css.btn_class_pre参数
//由上两个例子可见,虽然第二个例子配置了css.color的参数,但是按钮颜色没有改变，因为在同一个html页面中class相同所以，css样式被覆盖了(默认颜色是绿色)，所以默认模板增加了一个类前缀参数
$fenye2=new fenye();
$fenye2->settotal(100);
	$model=[
				'html'=>[
					'btn'=>[//全局按钮样式
						'normal'=>'<a href="#href#" class="pre_config_fanye_a">#text#</a>',
						'selection'=>'<a href="#href#" class="pre_config_fanye_a pre_config_fanye_a_">#text#</a>',
						'disable'=>'<a href="#href#" class="pre_config_fanye_a pre_config_fanye_d">#text#</a>',
					]
				]
			];
	$css=[
		'color'=>'#FF5722',
		'btn_class_pre'=>'pre_'//类前缀,需要在上面的html.btn中同时修改类名才能生效
	];
$fenye2->sethtmlmodel('default',$model,$css);
$fenye2_result=$fenye2->result();
echo '<br><br><br>css.btn_class_pre：<br>'.$fenye2_result['css'],$fenye2_result['html'];

