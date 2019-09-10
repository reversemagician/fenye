<?php
//简单分页封装类
class fenye{


	private $url=0;//路径信息
	private $pageurl;//当前路径
	private $total=0;//总条数
	private $number=0;//每页显示的条数
	private $page_number=0;//显示的页码数
	private $p=0;//当前页
	private $style=array(//样式数组,
		'set_first'=>true,//显示首页
		'set_last'=>true,//显示尾页
		'set_prev'=>true,//显示上一页
		'set_next'=>true,//显示下一页
		'set_count_t'=>true,//总条数
		'set_count_p'=>true,//总页数
		'first'=>'首页',
		'last'=>'尾页',
		'prev'=>'上一页',
		'next'=>'下一页',
		'p'=>'p',//检测的翻页键值
		'url'=>'',//自定义一个路径,系统将会采用该路径而不是自动获取。该功能暂未有效
		'p_url_type'=>'get',//路径方式 'get'=?附加参数的路径方式 '\/'=如index/p/5.html等方式可以是其他符合,由于使用到正则所以特殊符合要加'\' 'empty'=如http://80s.la/movie/list/-----p25等无间隔=$style['p']='---p'
		'css_haslabel'=>true,//css样式是否带有<style>标签
		);
	private $modelid='default';//默认的模板id
	private $model=[];//模板数据
	private $settotal=false;//用于判断是否已经设置了总条数


		

	public function __construct($number=10,$page_number=5,$total='')
	{
		$this->number=$number;
		$this->page_number=$page_number;
		if($total!=''){
			$this->total=$total;
			$this->settotal=true;
		}
	}

	// 配置$this->style参数
	public function setstyle($arr=array()){
		foreach($arr as $k => $v){
			if($this->style[$k]){
				$this->style[$k]=$arr[$k];
			}
		}
	}

	//设置总数
	public function settotal($total){
		$this->total=$total;
		$this->settotal=true;
	}

	//[
	//	'data',//主体数据
	//	'limit',//limit信息
	//	'css',
	//	'html',
	//	'p'//当前页码
	//];
	public function result(){
		if(!$this->settotal){echo "分页error：你没有设置数据总数。(settotal())";die;}
		//解析路径并转化
		$this->getp_reseturl();

		// 主体数据
		$data=$this->maindata();
		// html数据
		$html=$this->mainhtml($data);

		return [
			'p'=>$this->p,
			'data'=>$data,
			'limit'=>$this->getlimit(),
			'html'=>$html,
			'css'=>$this->model['css'],
		];
		
	}

	

	//设置模板 $modelid模板id $model_v_set=动态修改模板的$model的参数 $css_v_set=动态修改模板的css参数 
	//可外部调用,来选择模板和动态更改模板参数
	//$call=>external或self;external会覆盖已有$this->model,self不会覆盖
	public function sethtmlmodel($modelid='',$model_v_set=[],$css_v_set=[],$call='external'){
		//阻止class内的的重复调用
		if($call=='self'){
			if(!empty($this->model)){
				return $this->model;
			}
		}
		$modelid=$modelid==''?'default':$modelid;
		$model=$this->htmlmodel($modelid,$model_v_set,$css_v_set);
		//css加或删标签<style>
		if(isset($model['css'])){
			if($model['css']!=''){
				$model['css']=$this->resetcss($model['css']);
			}
		}
		$this->model=$model;
		// print_r($this->model);
		return $this->model;
	}
	

	//主体数据
	private function maindata(){
		$data=[];
		//总页数
		$countpaye=ceil($this->total/$this->number)==0?1:ceil($this->total/$this->number);
		//过滤非法p值
		$this->p=$this->p>$countpaye?$countpaye:($this->p<=0?1:$this->p);
		//间隔
		$ge=floor($this->page_number/2);
		//循环起始值
		$start=$this->p-$ge;
		$start=$this->p>$countpaye-$ge?$countpaye-$this->page_number+1:$start;
		$start=$start<1?1:$start;//1开始
		//循环结束值
		$last=$this->p+$ge;
		$last=$last<$this->page_number?($countpaye>=$this->page_number?$this->page_number:$countpaye):$last;//1开始
		$last=$last>$countpaye?$countpaye:$last;

		$data['first']=[
			'name'=>'first',
			'self_p'=>1,//本身的p值
			'page_p'=>$this->p,//当前页p值
			'href'=>str_replace('#num#',1,$this->url),//url
			'status'=>($this->p==1?'disable':'normal')//'normal','selection'=>'','disable'
		];
		$data['last']=[
			'name'=>'last',
			'self_p'=>$countpaye,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$countpaye,$this->url),
			'status'=>($this->p==$countpaye?'disable':'normal')
		];
		$data['prev']=[
			'name'=>'prev',
			'self_p'=>$this->p-1,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$this->p-1,$this->url),
			'status'=>($this->p-1<=0?'disable':'normal')
		];
		$data['next']=[
			'name'=>'next',
			'self_p'=>$this->p+1,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$this->p+1,$this->url),
			'status'=>($this->p>=$countpaye?'disable':'normal')
		];
		$data['count_p']=$countpaye;
		$data['count_t']=$this->total;
		for ($x=$start; $x<=$last;$x++) {
			$data['loop'][]=[
				'name'=>$x,
				'self_p'=>$x,
				'page_p'=>$this->p,
				'href'=>str_replace('#num#',$x,$this->url),
				'status'=>($this->p==$x?'selection':'normal')
			];
		}

		return $data;
		

	}

	//页面主体，基于maindata()
	private function mainhtml($data=[])
	{
		$data=empty($data)?$this->maindata():$data;//确认主体数据
		$model=$this->sethtmlmodel($this->modelid,[],[],'self');//初始化模板
		return $this->btn($data);//按钮主体

	}

	private function getlimit(){
		return [
			' limit '.($this->p-1)*$this->number.','.$this->number.' ',
			array(($this->p-1)*$this->number,$this->number)
		];
	}

	//判断当前页数和转换url路径
	private function getp_reseturl(){
		$this->getpageurl();//获取当前路径
		if ($this->style['p_url_type']=='get') {
		//get
			$this->p=!isset($_GET[$this->style['p']])?1:$_GET[$this->style['p']];
			$this->p=is_numeric($this->p)?$this->p:1;
			//重新设置url
			$this->url=str_replace('&'.$this->style['p'].'='.$this->p,'', $this->pageurl);
			$this->url=str_replace('?'.$this->style['p'].'='.$this->p.'&','?', $this->url);
			$this->url=str_replace('?'.$this->style['p'].'='.$this->p,'', $this->url);
			$fuhao=strpos($this->url,'?')?'&':'?';
			$this->url=$this->url.$fuhao.$this->style['p'].'=#num#';
		}

		if($this->style['p_url_type']=='empty'){
		//none
			// 判断p值
			$preg='/'.$this->style['p'].'[0-9]+/';
			preg_match_all($preg,$this->pageurl,$getp);
			if(count($getp[0])>1){
				//你可以更改$style["p"]的值来解决匹配到多个的问题
				echo '分页error：匹配到多个$style["p"],请正确配置$style["p"]参数';
			}else{

				if(count($getp[0])==0){
					$this->p=1;
					$this->url=$this->pageurl.$this->style['p'].'#num#';//重新设置url
				}
				if(count($getp[0])==1){
					preg_match_all("/\d+$/",$getp[0][0],$pv);
					$this->p=$pv[0][0];
					$this->url=preg_replace($preg,$this->style['p'].'#num#',$this->pageurl);//重新设置url
				}
			}			
		}
		
		if($this->style['p_url_type']!='empty'&&$this->style['p_url_type']!='get'){
		//特殊符合做间隔
			$preg='/'.$this->style['p'].$this->style['p_url_type'].'[0-9]+/';
			//重新设置url
			$this->url=preg_replace($preg,$this->style['p'].str_replace('\\','', $this->style['p_url_type']).'#num#',$this->pageurl);
			preg_match_all('/'.$this->style['p'].$this->style['p_url_type'].'([0-9]+)/',$this->pageurl,$pageurlnum);
			if(isset($pageurlnum[1][0])){
				$this->p=$pageurlnum[1][0];
			}else{
				//待开发,不知道实际情况
				echo "特殊符合做间隔方式空白部分";//
			}
			
		}
			
	}

	//获取当前页面url
	private function getpageurl(){
		$pageurl='http';
		if(isset($_SERVER['HTTPS'])){
			if($_SERVER['HTTPS']=="on"){
				$pageurl.='s';
			}
		}
		$pageurl.='://';
		if($_SERVER['SERVER_PORT']!='80'){
			$pageurl.=$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		}else{
			$pageurl.=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		$this->pageurl=$pageurl;
	}

	//css的<style>标签删除或增加
	private function resetcss($css=''){
		if($css==''){return '';}
		if ($this->style['css_haslabel']) {
			return strpos($css, '<style>')!==false?$css:'<style>'.$css.'</style>';
		}else{
			return strpos($css, '<style>')!==false?preg_replace('/<[\/]?style>/','',$css):$css;
		}
	}
	

	//返回多维数组的某个值,$where="waiceng.ceng2.ceng3"
	private function getvalue($where='',$arr=[]){
		if($where==''||empty($arr)){return '';}
		$layer=explode('.',$where);
		$vstr='$arr';
		foreach ($layer as $v) {
			$vstr.='["'.$v.'"]';
		}
		$evalstr='$result=isset('.$vstr.')?'.$vstr.':"undefined";';//undefined该值不存在
		eval($evalstr);
		return $result;
	}

	

	private function btn($data){
		$btn=[];

		//总条数
		if($this->style['set_count_t']){
			$btn['count_t_ele']=$this->makingabtn('count_t',$data['count_t']);
		}

		//总页数
		if($this->style['set_count_p']){
			$btn['count_p_ele']=$this->makingabtn('count_p',$data['count_p']);
		}

		//basebtn
		$basebtn=[
			$data['first'],
			$data['last'],
			$data['prev'],
			$data['next'],
		];
		foreach ($basebtn as $v) {
			if($this->style['set_'.$v['name']]){
				$btn[$v['name'].'_btn']=$this->makingabtn($v['name'],$v);
			}
		}

		//block
		$block=$this->getmodelval('html.block');
		
		// loopbtn
		$modelblock=$this->getvalue('html.loop_block',$this->model);
		$defaulblock=$this->modeldefaultseting('html.loop_block');
		foreach ($data['loop'] as $k => $v) {
			
			$btn['loop_btn'][]=$this->makingabtn('loop',$v);
			
			//判断循环间隔
			if($k<=count($data['loop'])-2){
				
				if($modelblock!=''&&$modelblock!='undefined'){
					$btn['loop_btn'][]=$modelblock;
				}elseif($defaulblock!=''&&$defaulblock!='undefined'){
					$btn['loop_btn'][]=$defaulblock;
				}

			}
		}
		$btn['loop_btn']=implode('',$btn['loop_btn']);

		//排序
		$order=$this->getmodelval('html_btn_order.order');
		$order_true=$this->getmodelval('html_btn_order.order_true');

		$end=[];
		foreach ($order as  $v) {
			if(in_array($v,$order_true)&&$v!=''){
				if(isset($btn[$v])){
					$end[]=$btn[$v];
				}
			}else{
				$end[]=$v;
			}
		}


		$html=$this->getmodelval('html.outer_begin').implode('',$end).$this->getmodelval('html.outer_end');
		return $html;
	}

	//生产一个btn 
	private function makingabtn($name,$val){
		$text='#text#';
		$href='#href#';

		//count
		if(in_array($name,['count_t','count_p'])){
			$finalset=$this->getfinalseting($name);
			return str_replace($text,$val,$finalset['normal']);
		}

		//btn
		if(in_array($name,['first','last','prev','next','loop'])){
			// 最终配置
			$finalset=$this->getfinalseting($name);
			
			//按钮上显示的文字
			$remark=$name=='loop'?$val['name']:$this->style[$name];

			$btn= str_replace($text,$remark,$finalset[$val['status']]);
			
			//href
			if($val['status']=='disable'){
				$btn=str_replace($href,'javascript:;',$btn);
			}else{
				$btn=str_replace($href,$val['href'],$btn);
			}
			return $btn;
		}


		
	}

	//获取按钮的最终配置
	private function getfinalseting($name){
		// conutset
		if(in_array($name,['count_t','count_p'])){
			$modelset=$this->getvalue('html.'.$name.'_ele',$this->model);	
			$defaultset=$this->modeldefaultseting('html.'.$name.'_ele');
			if($modelset!='undefined'){
				return array_merge($defaultset,$modelset);
			}else{
				return $defaultset;
			}
		}

		// btnset
		if(in_array($name,['first','last','prev','next','loop'])){
			//优先使用模板配置
			$modelset=$this->getvalue('html.'.$name.'_btn',$this->model);
			$modelbtnset=$this->getvalue('html.btn',$this->model);
			//默认模板配置
			$defaultset=$this->modeldefaultseting('html.'.$name.'_btn');
			$defaultbtnset=$this->modeldefaultseting('html.btn');

			//合并
			return array_merge((is_array($defaultbtnset)?$defaultbtnset:[]),(is_array($defaultset)?$defaultset:[]),(is_array($modelbtnset)?$modelbtnset:[]),(is_array($modelset)?$modelset:[]));
		}
	}

	//获取一个模板或默认模板的值
	private function getmodelval($where=''){
		//当模板无无该变量时取默认模板的值
		$result=$this->getvalue($where,$this->model);
		return $result=="undefined"?$this->modeldefaultseting($where):$result;
	}

	//多维数组合并 以$a为主,冲突会保留$a的数据
	private function my_merge(&$a,$b){
		foreach($a as $key=>&$val){
			if(is_array($val) && array_key_exists($key, $b) && is_array($b[$key])){
				$this->my_merge($val,$b[$key]);
				$val = $val + $b[$key];
			}else if(is_array($val) || (array_key_exists($key, $b) && is_array($b[$key]))){
				$val = is_array($val)?$val:$b[$key];
			}
		}
		$a = $a + $b;
	}

	/**
	 * 分页模板
	 * @param  modelid $modelid    模板id
	 * @param  modelid $model_v_set动态地配置$model的参数(格式与$model一致)
	 * @param  modelid $css_v_set  对应模板分页的css样式配置参数,示例参考默认模板
	 * @return model               模板数组
	 */
	private function htmlmodel($modelid='',$model_v_set=[],$css_v_set=[]){

		switch ($modelid) {
			case 'base':
				//基础模板,仅用于参数说明
				$css_v=[];//下面的$model['css']中对应变量集，如不使用$model['css']可以忽略
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html'=>[//固定html变量配置
						'outer_begin'=>'',//string分页外层头部
						'outer_end'=>'',//string分页外层尾部
						'loop_block'=>'',//循环间隙，主体循环时自动加
						'btn'=>[//所有按钮的公共默认样式
							
							//按钮配置方式一 
							'normal'=>'',//正常状态
							'selection'=>'',//选中状态
							'disable'=>'',//不可选状态 默认href="javascript:;"
						],
						'loop_btn'=>[],//循环页
						'prev_btn'=>[],//上页
						'next_btn'=>[],//下页
						'first_btn'=>[],//首页
						'last_btn'=>[],//尾页
						'count_p_ele'=>[
							//以下三个元素有效
							'normal'=>'',
						],//总页数
						'count_t_ele'=>[
							'normal'=>'',
						],//总数据
						'block'=>''//string普通间隙 在排序手动添加
					],
					'html_btn_order'=>[//按钮显示顺序配置
						'order'=>[ //显示顺序可以同时存在相同的项 
							'first_btn',
							'prev_btn',
							'loop_btn',
							'next_btn',
							'last_btn',
							'last_btn',//可以重复出现
							'block',
							'count_p_btn',
							'block',
							'count_t_btn',
							//'<div>||</div>',/*这个是非法顺序值，所有的非法顺序值会被认为是间隙对应添加到分页的对应位置中*/
						],
						'order_true'=>[//合法的显示顺序名
							'first_btn',
							'prev_btn',
							'loop_btn',
							'next_btn',
							'last_btn',
							'block',
							'count_p_btn',
							'count_t_btn',
						],
					],
					'css'=>'',//模板的css，已在外部加载过样式时可以忽略
				];
				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->my_merge($model_v_set,$model);
					return $model_v_set;
				}
				break;
			default:
			//默认模板
				$css_v=[//动态配置css,与$model['css']内参数对应,设置模板时自定义
					'float'=>'left',//整体浮动
					'color'=>'#009688',//#1E9FFF #5FB878 #009688 #4476A7 #FF5722
					'btn_class_pre'=>'',//类前缀，一般使用可用忽略
					//...更多动态参数
				];//下面的$model['css']中对应变量集，不使用$model['css']可以忽略
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html'=>[
						'outer_begin'=>'<div class="config_fanye_">',
						'outer_end'=>'<div style="clear:both"></div></div>',
						'btn'=>[//按钮样式
							'normal'=>'<a href="#href#" class="config_fanye_a">#text#</a>',
							'selection'=>'<a href="#href#" class="config_fanye_a config_fanye_a_">#text#</a>',
							'disable'=>'<a href="#href#" class="config_fanye_a config_fanye_d">#text#</a>',
						],
						'count_p_ele'=>[
							'normal'=>'<div class="config_fanye_count">共#text#页</div>'
						],//总页数
						'count_t_ele'=>[
							'normal'=>'<div class="config_fanye_count">共#text#条</div>'
						],//总数据
					],
					'css'=>'
						/*默认分页类样式*/
						.'.$css_v['btn_class_pre'].'config_fanye_{
						  float: '.$css_v['float'].';
						}
						.'.$css_v['btn_class_pre'].'config_fanye_a{
						  padding: 0 15px;
						  color: #333;
						  background-color: #fff;
						  float: left;
						  height: 28px;
						  line-height: 28px;
						  margin-left:-1px;
						  font-size: 12px;
						  vertical-align: middle;
						  border: 1px solid #e2e2e2;
						  text-decoration:none;
						  font-family: "微软雅黑";

						}
						.'.$css_v['btn_class_pre'].'config_fanye_a:hover{
						  color:'.$css_v['color'].';
						}
						.'.$css_v['btn_class_pre'].'config_fanye_a:first-child{
						  border-radius:2px 0 0 2px;
						}
						.'.$css_v['btn_class_pre'].'config_fanye_a:last-child{
						  border-radius: 0 2px 2px 0;
						}
						.'.$css_v['btn_class_pre'].'config_fanye_a_{
						  background: '.$css_v['color'].';
						  border: 1px solid '.$css_v['color'].';
						  color: #fff;
						  border-radius:2px ;
						}
						.'.$css_v['btn_class_pre'].'config_fanye_a_:hover{
						  color:#fff;
						}
						.'.$css_v['btn_class_pre'].'config_fanye_d{
						  color: #d2d2d2 !important;
						  cursor: not-allowed !important;
						}
						.config_fanye_count{
						  line-height: 28px;
						  float: left;
						  color: #666;
						  font-size: 14px;
						  margin-left: 8px;
						}
						.hand{cursor:pointer;}',
				];


				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->my_merge($model_v_set,$model);
					return $model_v_set;
				}

				break;
		}
	}

	//模板的默认配置 $where返回指定配置项
	private function modeldefaultseting($where=''){
		
		$model=[
			'html'=>[
				'outer_begin'=>'',
				'outer_end'=>'',
				'loop_block'=>'',
				'btn'=>[
					'normal'=>'<a href="#href#" class="fenye_a">#text#</a>',
					'selection'=>'<a href="#href#" class="fenye_a fenye_selection">#text#</a>',
					'disable'=>'<a href="#href#" class="fenye_a fenye_disable">#text#</a>',
				],
				'loop_btn'=>[],//循环页
				'prev_btn'=>[],//上页
				'next_btn'=>[],//下页
				'first_btn'=>[],//首页
				'last_btn'=>[],//尾页
				'count_p_ele'=>[
					'normal'=>'<div>共#text#页</div>',
				],//总页数
				'count_t_ele'=>[
					'normal'=>'<div>共#text#条</div>',
				],//总数据
				'block'=>''
			],
			'html_btn_order'=>[//按钮显示顺序配置
				'order'=>[ //显示顺序
					'first_btn',
					'prev_btn',
					'loop_btn',
					'next_btn',
					'last_btn',
					'count_p_ele',
					'count_t_ele',
				],
				'order_true'=>[//合法的显示顺序名
					'first_btn',
					'prev_btn',
					'loop_btn',
					'next_btn',
					'last_btn',
					'block',
					'count_p_ele',
					'count_t_ele',
				],
			],
		];

		if($where==''){
			return $model;
		}else{
			return $this->getvalue($where,$model);
		}
	}

}


?>
