<?php
include 'fenye.php';

//////最简单的使用放
$fen=new fenye();
$fen->settotal(100);//总数据量，至少调用一次该方法
$fen_result=$fen->result();//获取分页结果
echo '简单使用：<br>'.$fen_result['css'],$fen_result['html'];//输出获取结果


//////常用方法 (4个)
// 全部方法：
// settotal() 设置数据总数
// setstyle() 设置分页配置 *也可直接修改$style变量达到效果
// result()	  直接获取全部结果
// maindata() 获取分页数据
// getlimit() 获取limit信息 
// mainhtml() 获取分页html字符串
// sethtmlmodel() 选择和动态配置模板参数
$fenye=new fenye();
$fenye->settotal(100);//设置数据总数 方法一
$fenye->setstyle(['first'=>'first','last'=>'last','p'=>'p2']);//配置$this->style 方法二
	$model=[
				'html'=>[
					'btn'=>[//全局按钮样式
						'selection'=>'<a href="#href#" class="config_fanye_a config_fanye_a_">##text##</a>',
						'disable'=>'',//隐藏按钮
					]
				],
				'html_btn_order'=>[
					'order'=>[ //显示顺序
						'loop_btn',
						'first_btn',
						'prev_btn',
						'next_btn',
						'last_btn',
						'<div class="config_fanye_count">#p#/#count_paye#</div>',//#系统变量名#
					],
				]
			];
	$css=[
		'color'=>'#4476A7',
	];
$fenye->sethtmlmodel('default',$model,$css);//动态配置模板参数 方法三
$fenye_result=$fenye->result();//获取结果 方法四
echo '<br><br><br>常用方法：<br>'.$fenye_result['css'],$fenye_result['html'];



//////动态默认模板css.btn_class_pre参数
//由上两个例子可见,虽然第二个例子配置了css.color的参数,但是按钮颜色是相同的，因为在同一个html页面中class相同所以，css样式被覆盖了(默认颜色是绿色)，所以默认模板增加了一个类前缀参数
$fenye1=new fenye();
$fenye1->settotal(100);
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
$fenye1->sethtmlmodel('default',$model,$css);
$fenye1_result=$fenye1->result();
echo '<br><br><br>css.btn_class_pre：<br>'.$fenye1_result['css'],$fenye1_result['html'];

//////其他用法1:在外部增加一个下一版的按钮
$yema=7;//分页的页码数量
$fenye2=new fenye(10,$yema,3000);
$fenye2_data=$fenye2->maindata();//初始化并获取数据
$prev_block=$fenye2->p>$yema/2+1?'<a href="'.str_replace('#num#',$fenye2->p-$yema, $fenye2->url).'" class="config_fanye_a">...<a>':'';//上一版btn
$next_block=$fenye2->p<$fenye2->count_paye-$yema/2?'<a href="'.str_replace('#num#',$fenye2->p+$yema, $fenye2->url).'" class="config_fanye_a">...<a>':'';//下一版btn
$model=[
	'html_btn_order'=>[
					'order'=>[ //显示顺序
						'first_btn',
						'prev_btn',
						$prev_block,
						'loop_btn',
						$next_block,
						'next_btn',
						'last_btn',
						'<div class="config_fanye_count">#p#/#count_paye#</div>',
						
					],
				]
];
$fenye2->sethtmlmodel('default',$model);
$fenye2_result=$fenye2->result();
echo '<br><br><br>其他用法：<br>'.$fenye2_result['css'],$fenye2_result['html'];