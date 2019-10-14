<?php

//简单分页封装类
class fenye{

	private $total=0;//数据总条数
	private $settotal=false;//用于判断是否已经设置了总条数
	private $number=10;//每页显示的条数
	private $page_number=5;//显示的页码数
	private $offset=0;//当前页按钮的偏移量,正向右,负向左。偏移量最好小于$page_number/2
	private $urlmodel=array(
		'p'=>'p',//检测的翻页键值
		'p_url_type'=>'get',//自动过滤路径的方式 'get':?附加参数的路径方式 '\/':如index/p/5.html等方式可以是其他符合,由于使用到正则所以特殊符合要加'\' 'empty':如http://80s.la/movie/list/-----p25等无间隔=$style['p']='---p'
		);

	//以下可访问变量,需要在mainData()方法调用后，才可以访问到有效值
	public $count_paye=0;//共有多少页
	public $pageurl='';//当前路径
	public $p=0;//当前页
	public $url='';//已过滤的路径信息，p值位置将被过滤成#num#。
				  //如果该值被外部设置,此时不会自动获取$p的值,因此此时需要手动设置$p的值
	public $loopstart=1;//循环起始值
	public $loopend=1;//循环起始结束
	public $loopge=0;//当前页码到循环起始和结束的间隔
	
	private $data=[];//主体数据
	private $model=[];//模板数据

	//$number每页显示的数据条数 $page_number显示的页码数 $total数据总量
	public function __construct($number=10,$page_number=5,$total='')
	{
		$this->number=$number;
		$this->page_number=$page_number;
		if($total!==''){
			$this->setTotal($total);
		}
	}

	//设置总数
	public function setTotal($total){
		$this->total=$total;
		$this->settotal=true;
	}

	// 配置$this->urlmodel参数
	public function setUrlModel($arr=array()){
		foreach($arr as $k => $v){
			if(isset($this->urlmodel[$k])){
				$this->urlmodel[$k]=$v;
			}
		}
	}

	//获取全部结果
	public function result(){
		// 数据主体
		$data=$this->mainData();
		// html主体
		$html=$this->mainHtml();

		return [
			'p'=>$this->p,//当前页数
			'data'=>$data['maindata'],//主体数据
			'limit'=>$data['limit'],//limit信息
			'html'=>$html['html'],
			'css'=>$html['css'],
			'script'=>$html['script'],
		];
		
	}

	/**
	 * [生成html主体]
	 * @param  array  [$data] 主体数据
	 * @return [type]       html的内容、css、JavaScript
	 */
	public function mainHtml()
	{
		//确认数据主体
		if(empty($this->data)){
			$this->mainData();
		}
		$model=$this->sethtmlModel('default',[],[],'self');//确认初始化模板
		$html=$this->btn($this->data);//按钮主体
		return [
			'html'=>$html,
			'css'=>$this->model['css'],
			'script'=>$this->model['script'],
		];
	}

	/**
	 * 计算数据主体 返回主体数据
	 * @return [type] [description]
	 */
	public function mainData(){
		if(!$this->settotal){echo "分页错误：你没有设置数据总数。(settotal())";die;}
		//解析路径
		$this->resetUrl();
		$data=[];
		//总页数
		$countpaye=ceil($this->total/$this->number)==0?1:ceil($this->total/$this->number);
		$this->count_paye=$countpaye;
		//过滤非法p值
		$this->p=$this->p>$countpaye?$countpaye:($this->p<=0?1:$this->p);
		//间隔
		$ge=floor($this->page_number/2);
		$this->loopge=$ge;
		//循环起始值
		$start=$this->p-$ge;
		$start=$start-$this->offset;//偏移
		$maxstart=$countpaye-$this->page_number+1;//最大起点
		$start=$start>$maxstart?$maxstart:$start;
		$start=$start<1?1:$start;//最小起点为1
		//循环结束值
		$last=$start+$this->page_number-1;
		$last=$last>$countpaye?$countpaye:$last;//最大结束值$countpaye
		$this->loopstart=$start;
		$this->loopend=$last;
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
			'self_p'=>$this->p-1<=0?1:$this->p-1,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$this->p-1<=0?1:$this->p-1,$this->url),
			'status'=>($this->p-1<=0?'disable':'normal')
		];
		$data['next']=[
			'name'=>'next',
			'self_p'=>$this->p>=$countpaye?$countpaye:$this->p+1,
			'page_p'=>$this->p,
			'href'=>str_replace('#num#',$this->p>=$countpaye?$countpaye:$this->p+1,$this->url),
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

		$this->data=$data;
		return [
			'maindata'=>$data,
			'limit'=>$this->getLimit()
		];
	}
	
	/**
	 * [设置html模板]
	 * @param  string $modelid     模板id
	 * @param  array  $model_v_set 动态修改模板的$model的参数
	 * @param  array  $css_v_set   动态修改模板的css参数 
	 * @param  string $call        调用方式，external或self
	 * @return array               模板数据
	 */
	public function sethtmlModel($modelid='default',$model_v_set=[],$css_v_set=[],$call='external'){
		//阻止分页类内部的重复调用导致外部调用该方法时模板被覆盖
		if($call=='self'){
			if(!empty($this->model)){
				return $this->model;
			}
		}
		//确认数据主体
		if(empty($this->data)){
			$this->mainData();
		}
		//配置模板
		$model=$this->htmlModel($modelid,$model_v_set,$css_v_set);
		//默认配置
		$modelDefaultSeting=$this->modelDefaultSeting();
		//合并默认配置
		$this->myArrayMerge($model,$modelDefaultSeting);

		//过滤标签
		if(isset($model['css'])){
			if($model['css']!=''){
				$model['css']=$this->resetLabel('style',$model['css'],$model['style_label']);
			}
		}
		if(isset($model['script'])){
			if($model['script']!=''){
				$model['script']=$this->resetLabel('script',$model['script'],$model['script_label']);
			}
		}

		return $this->model=$model;
	}

	//html btn主体
	private function btn($data){
		$btn=[];

		//4个基础按钮
		$basebtn=[
			$data['first'],
			$data['last'],
			$data['prev'],
			$data['next'],
		];
		foreach ($basebtn as $v) {
			$btn[$v['name'].'_btn']=$this->makingABtn($v['name'],$v);
		}

		//间隙按钮
		$btn['block']=$this->model['html']['block'];

		//循环体按钮
		if($this->model['html']['loop_check_reset']){//检查修正循环：重设循环体
			$data['loop']=$this->loopCheckReset($data['loop']);
		}
		$loopblock=$this->model['html']['loop_block'];//循环间隙
		foreach ($data['loop'] as $k => $v) {
			//循环按钮
			$btn['loop_btn'][]=$this->makingABtn('loop',$v);
			
			//循环间隔
			if($k<=count($data['loop'])-2){
				$btn['loop_btn'][]=$loopblock;
			}
		}
		$btn['loop_btn']=implode('',$btn['loop_btn']);

		//排序并匹配系统变量
		$order=$this->model['html_btn_order']['order'];
		$order_true=$this->model['html_btn_order']['order_true'];
		$end=[];
		foreach ($order as $k => $v) {
			$content='';//显示的内容
			if(in_array($v,$order_true)&&$v!=''){//判断order是否合法
				if(isset($btn[$v])){
					$content=$btn[$v];
				}
			}else{
				$content=$v;
			}
			//检查是否存在显示逻辑
			if(isset($this->model['html_btn_order']['order_rule'][$k])){
				if($this->model['html_btn_order']['order_rule'][$k]){
				//显示
					$end[]=$this->replaceSystemVal($content);
				}
			}else{
			//直接显示
				$end[]=$this->replaceSystemVal($content);
			}
		}
					
		//拼接首尾
		$html=$this->model['html']['outer_begin'].implode('',$end).$this->model['html']['outer_end'];
		return $html;
	}

	private function loopCheckReset($data){
		//当循环起始为1时
		if($this->loopstart==1&&count($data)>$this->p+$this->loopge){
			$i=count($data)-($this->p+$this->loopge);//需要修正的项数
			$data=array_slice($data,0,-$i);
		}
		if($this->loopend==$this->count_paye&&$this->loopstart<$this->p-$this->loopge){
			$i=$this->p-$this->loopge-$this->loopstart;//需要修正的项数
			$data=array_slice($data,$i);
		}
		return $data;
	}

	//生产一个btn 
	private function makingABtn($name,$val){
		$text='#text#';
		$href='#href#';
		$self_p='#self_p#';

		// btn最终配置
		$finalset=$this->getBtnFinalSeting($name);

		//匹配默认变量 #text#
		$remark=$name=='loop'?$val['name']:$this->model['html'][$name.'_text'];//合适的#text#
		$btn= str_replace($text,$remark,$finalset[$val['status']]);
		
		//匹配默认变量 #href#
		$btn=str_replace($href,$val['href'],$btn);

		//匹配默认变量 #self_p#
		$btn=str_replace($self_p,$val['self_p'],$btn);

		return $btn;
	}

	//获取按钮的最终配置
	private function getBtnFinalSeting($name){
		$btnset=$this->getArrayValue('html.'.$name.'_btn',$this->model);
		$globalbtnset=$this->getArrayValue('html.btn',$this->model);
		//合并
		return array_merge((is_array($globalbtnset)?$globalbtnset:[]),(is_array($btnset)?$btnset:[]));
	}

	//html标签过滤 删除或增加
	private function resetLabel($label='',$content='',$has_label=true){
		if ($has_label) {
			return strpos($content, '<'.$label.'>')!==false?$content:'<'.$label.'>'.$content.'</'.$label.'>';
		}else{
			return strpos($content, '<'.$label.'>')!==false?preg_replace('/<[\/]?'.$label.'>/','',$content):$content;
		}
	}

	//匹配系统变量名并全部替换成系统值
	private function replaceSystemVal($str){
		$preg='/#([^#]+)#/';//匹配规则 #系统变量名# 或同#styel.first#

		preg_match_all($preg,$str,$pv);
		if(!empty($pv[0])){
			foreach ($pv[1] as $k => $v) {
				//常规字符串变量
				if(isset($this->$v)){
					$str=str_replace($pv[0][$k],$this->$v,$str);
				}

				//数组内变量 如url.\w+
				$preg_style='/([\w]+)\.([\w]+)/';
				preg_match_all($preg_style,$v,$pv_style);
				if(!empty($pv_style[0])){
					$yi=$pv_style[1][0];
					if(isset($this->$yi[$pv_style[2][0]])){
						$str=str_replace($pv[0][$k],$this->$yi[$pv_style[2][0]],$str);
					}
				}
			}
		}

		return $str;
	}

	//获得当前页和过滤url路径
	private function resetUrl(){
		$this->getPageUrl();//获取当前路径

		if($this->url!=''){return false;}//保证手动设置的url优先

		if ($this->urlmodel['p_url_type']=='get') {
		//get
			$this->p=!isset($_GET[$this->urlmodel['p']])?1:$_GET[$this->urlmodel['p']];
			$this->p=is_numeric($this->p)?$this->p:1;
			//过滤的url信息
			$this->url=str_replace('&'.$this->urlmodel['p'].'='.$this->p,'', $this->pageurl);
			$this->url=str_replace('?'.$this->urlmodel['p'].'='.$this->p.'&','?', $this->url);
			$this->url=str_replace('?'.$this->urlmodel['p'].'='.$this->p,'', $this->url);
			$fuhao=strpos($this->url,'?')?'&':'?';
			$this->url=$this->url.$fuhao.$this->urlmodel['p'].'=#num#';
		}

		if($this->urlmodel['p_url_type']=='empty'){
		//empty
			// 判断p值
			$preg='/'.$this->urlmodel['p'].'[0-9]+/';
			preg_match_all($preg,$this->pageurl,$getp);
			if(count($getp[0])>1){
				//你可以更改$this->urlmodel["p"]的值来解决匹配到多个的问题
				echo '分页错误：匹配到多个$style["p"],请正确配置$style["p"]参数';
			}else{

				if(count($getp[0])==0){
					$this->p=1;
					$this->url=$this->pageurl.$this->urlmodel['p'].'#num#';
				}
				if(count($getp[0])==1){
					preg_match_all("/\d+$/",$getp[0][0],$pv);
					$this->p=$pv[0][0];
					$this->url=preg_replace($preg,$this->urlmodel['p'].'#num#',$this->pageurl);
				}
			}			
		}
		
		if($this->urlmodel['p_url_type']!='empty'&&$this->urlmodel['p_url_type']!='get'){
		//特殊符合做间隔
			$preg='/'.$this->urlmodel['p'].$this->urlmodel['p_url_type'].'[0-9]+/';
			//重新设置url
			$this->url=preg_replace($preg,$this->urlmodel['p'].str_replace('\\','', $this->urlmodel['p_url_type']).'#num#',$this->pageurl);
			preg_match_all('/'.$this->urlmodel['p'].$this->urlmodel['p_url_type'].'([0-9]+)/',$this->pageurl,$pageurlnum);
			if(isset($pageurlnum[1][0])){
				$this->p=$pageurlnum[1][0];
			}else{
				//待开发,不知道实际情况
				echo "分页错误：特殊符合做间隔方式空白部分";//
			}
		}
			
	}

	//获取当前页面url
	private function getPageUrl(){
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

	// 获取limit
	private function getLimit(){
		return [
			' limit '.($this->p-1)*$this->number.','.$this->number.' ',
			array(($this->p-1)*$this->number,$this->number)
		];
	}

	//返回多维数组的某个值,$where="waiceng.ceng2.ceng3"
	private function getArrayValue($where='',$arr=[]){
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

	//多维数组合并 以$a为主,冲突会保留$a的数据
	private function myArrayMerge(&$a,$b){
		foreach($a as $key=>&$val){
			if(is_array($val) && array_key_exists($key, $b) && is_array($b[$key])){
				$this->myArrayMerge($val,$b[$key]);
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
	private function htmlModel($modelid='',$model_v_set=[],$css_v_set=[]){

		switch ($modelid) {
			case 'base':
			//基础模板,仅用于参数说明
				$css_v=[];//下面的$model['css']中对应变量集，如不使用$model['css']可以忽略该参数 一维数组
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html'=>[//固定html变量配置
						'loop_check_reset'=>false,//鉴于某些网站分页，某些情况下(如在首页或者尾页时)的页码显示不是默认的按照最大页码来显示的，因此增加了这个额外的参数。当该参数为true时按照在某些情况下按照间隔来显示页码
						'outer_begin'=>'',//分页外层头部
						'outer_end'=>'',//分页外层尾部
						'loop_block'=>'',//循环间隙，主体循环时自动加
						'block'=>'',//普通间隙 在排序手动添加
						'btn'=>[//所有按钮的公共默认样式
							
							'normal'=>'',//正常状态
							'selection'=>'',//选中状态
							'disable'=>'',//不可选状态 
							//['#self_p#','#href#','#text#']有效的匹配值
							//#self_p# 匹配当前按钮对应页码数
							//#href# 匹配当前按钮对应路径
							//#text# 默认的按钮文字
							//可以使用 #count_paye#的方式来匹配到系统值 
							// 'normal'=>'<div>#p#/#count_paye#<div>',//可以利用 #变量系统名# 的的方式匹配到该系统值，如变量$this->p可以写成#p#来匹配到值，或者数组值#style.last#
						],//总页数
						'loop_btn'=>[],//循环页按钮
						'prev_btn'=>[],//上页按钮
						'next_btn'=>[],//下页按钮
						'first_btn'=>[],//首页按钮
						'last_btn'=>[],//尾页按钮
						'first_text'=>'首页',//若该值为#self_p#代表匹配自身页码
						'last_text'=>'尾页',//若该值为#self_p#代表匹配自身页码
						'prev_text'=>'上一页',//若该值为#self_p#代表匹配自身页码
						'next_text'=>'下一页',//若该值为#self_p#代表匹配自身页码

						
					],
					'html_btn_order'=>[//按钮显示顺序配置
						'order'=>[ //显示顺序可以同时存在相同的项 
							'first_btn',
							'prev_btn',
							'loop_btn',
							'next_btn',
							'last_btn',//可以重复出现
							'last_btn',//可以重复出现
							'block',
							//'<div>共#p#页</div>',/*这个是非法顺序值，所有的非法顺序值会被认为是间隙对应添加到分页的对应位置中*/
						],
						'order_true'=>[//合法的显示顺序名
							'first_btn',
							'prev_btn',
							'loop_btn',
							'next_btn',
							'last_btn',
							'block',
						],
						'order_rule'=>[//显示逻辑
							3=>true,//对应html_btn_order.order的第4项 数字代表对应项 0代表第一项
						],
					],
					'css'=>'',//模板的css内容，已在外部加载过样式时可以忽略
					'script'=>'',//js内容
					'style_label'=>true,//css是否要带有style标签
					'script_label'=>true,//script是否要带有标签
				];
				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
			case 'select':
				$css_v=[];
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html'=>[
						'outer_begin'=>'<select onchange="fenye_select(this.value)">',
						'outer_end'=>'</select>',
						'btn'=>[
							'normal'=>'<option value="#href#">#text#</option>',
							'selection'=>'<option value="#href#" selected = "selected">#text#</option>',
							'disable'=>'<option value="#href#" disabled="disabled">#text#</option>',
						]
					],
					'html_btn_order'=>[
						'order'=>[ 
							'first_btn',
							'loop_btn',
							'',
							'',//视情况补上一些空白排序以防默认模板中的排序漏出
							'',
							'',
						],
					],
					'script'=>'<script>fenye_select=function (url){window.location.href=url;}</script>',
				];

				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
			case 'bilibili':
				$css_v=[];
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html'=>[
						'outer_begin'=>'<ul class="fenye_bili">',
						'outer_end'=>'</ul>',
						'btn'=>[
							'normal'=>'<a href="#href#"><li class="fenye_bili_item">#text#</li></a>',
							'selection'=>'<a href="#href#"><li class="fenye_bili_item fenye_bili_item_action">#text#</li></a>',
							'disable'=>'',
						],
						'prev_btn'=>[
							'normal'=>'<a href="#href#"><li class="fenye_bili_prve">上一页</li></a>',
						],
						'next_btn'=>[
							'normal'=>'<a href="#href#"><li class="fenye_bili_prve">下一页</li></a>',
						],
						'first_text'=>'#self_p#',
						'last_text'=>'#self_p#',

					],
					'html_btn_order'=>[
						'order'=>[ 
							'prev_btn',
							'first_btn',
							'<li class="fenye_bili_ge"></li>',
							'loop_btn',
							'<li class="fenye_bili_ge"></li>',
							'last_btn',
							'next_btn',
							'<span class="fenye_bili_total">共 #count_paye# 页，跳至<input id="fenye_bili_search" onkeydown="enterRedirect(this)" placeholder="回车" url="#url#" type="text">页</span>'
						],
						'order_rule'=>[//显示逻辑
							1=>$this->loopstart>=2,//对应html_btn_order.order.1
							2=>$this->loopstart>=2+1,//对应html_btn_order.order.2
							4=>$this->loopend<$this->count_paye-1,//对应html_btn_order.order.4
							5=>$this->loopend<$this->count_paye,//对应html_btn_order.order.5
						]
					],
					'script'=>'function enterRedirect(e) {if (event.keyCode == 13) {var str=e.getAttribute("url");var url =str.replace("#num#",e.value);window.location.href=url;}}',
					'css'=>'.fenye_bili{text-align: center;width: auto;display: inline-block;margin:0; padding:0;}.fenye_bili li{list-style:none;display:inline-block;line-height:38px;padding:0 14px;border:1px solid #d7dde4;border-radius:4px;background-color:#fff;font-size:14px;transition:all .2s ease-in-out;font-family:Arial;color:#666}.fenye_bili .fenye_bili_prve{margin-right:10px}.fenye_bili .fenye_bili_ge{cursor:pointer;font-family:Arial;transition:all .2s ease-in-out;border:none;padding:0 3px}.fenye_bili .fenye_bili_ge:after{content:"\2022\2022\2022";display:block;letter-spacing:1px;color:#ccc;text-align:center}.fenye_bili .fenye_bili_item{margin-right:8px;-ms-user-select:none;user-select:none;cursor:pointer;padding:0 15px}.fenye_bili .fenye_bili_item_action{background-color:#00a1d6;border-color:#00a1d6;color:#fff}.fenye_bili_total{display:inline-block;height:32px;line-height:32px;margin-left:20px;color:#99a2aa;font-size:12px}#fenye_bili_search{border-radius:4px;margin:0 8px;width:50px;line-height:28px;height:28px;padding:0 10px;transition:all .3s ease;vertical-align:top;border:1px solid #ccd0d7;text-align:center}'
				];

				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
			default:
			//默认模板
				$css_v=[//动态css配置,与$model['css']对应
					'float'=>'left',//整体浮动
					'color'=>'#009688',//#1E9FFF #5FB878 #009688 #4476A7 #FF5722
					'btn_class_pre'=>''
					//...可以自定义更多动态参数
				];
				$css_v=empty($css_v_set)?$css_v:array_merge($css_v,$css_v_set);
				$model=[
					'html'=>[
						'outer_begin'=>'<div class="config_fanye_">',
						'outer_end'=>'<div style="clear:both"></div></div>',
						'btn'=>[//按钮样式
							'normal'=>'<a href="#href#" class="config_fanye_a">#text#</a>',
							'selection'=>'<a href="#href#" class="config_fanye_a config_fanye_a_">#text#</a>',
							'disable'=>'<a href="javascript:;" class="config_fanye_a config_fanye_d">#text#</a>',
						],
					],
					'html_btn_order'=>[//按钮显示顺序配置
						'order'=>[ //显示顺序
							'first_btn',
							'prev_btn',
							'loop_btn',
							'next_btn',
							'last_btn',
							'<div class="config_fanye_count">共#count_paye#页</div><div class="config_fanye_count">共#total#条</div>',
						],
					],
					'css'=>'.'.$css_v['btn_class_pre'].'config_fanye_{  float: '.$css_v['float'].';}.'.$css_v['btn_class_pre'].'config_fanye_a{padding: 0 15px;color: #333;background-color: #fff;float: left;height: 28px;line-height: 28px;margin-left:-1px;font-size: 12px;vertical-align: middle;border: 1px solid #e2e2e2;text-decoration:none;font-family: "微软雅黑";}.'.$css_v['btn_class_pre'].'config_fanye_a:hover{color:'.$css_v['color'].';}.'.$css_v['btn_class_pre'].'config_fanye_a:first-child{border-radius:2px 0 0 2px;}.'.$css_v['btn_class_pre'].'config_fanye_a:last-child{border-radius: 0 2px 2px 0;}.'.$css_v['btn_class_pre'].'config_fanye_a_{background: '.$css_v['color'].';border: 1px solid '.$css_v['color'].';color: #fff;border-radius:2px ;}.'.$css_v['btn_class_pre'].'config_fanye_a_:hover{color:#fff;}.'.$css_v['btn_class_pre'].'config_fanye_d{color: #d2d2d2 !important;cursor: not-allowed !important;}.'.$css_v['btn_class_pre'].'config_fanye_count{line-height: 28px;float: left;color: #666;font-size: 14px;margin-left: 8px;}.hand{cursor:pointer;}',
				];

				if(empty($model_v_set)){
					return $model;
				}else{
					//动态$model
					$this->myArrayMerge($model_v_set,$model);
					return $model_v_set;
				}
				break;
		}
	}


	/**
	 *  html模板必须参数的默认值
	 *  启用的html模板缺少必须参数时默认使用这些默认值
	 *  任何时候请不要修改此方法内的任何参数,否则缺少某些参数可能导致错误
	 * @param string $where 指定返回配置项
	 * @return array
	 */
	private function modelDefaultSeting($where=''){
		$model=[
			'html'=>[
				'loop_check_reset'=>false,
				'outer_begin'=>'',//空值
				'outer_end'=>'',//空值
				'loop_block'=>'',//空值
				'btn'=>[
					'normal'=>'<a href="#href#" class="fenye_a">#text#</a>',
					'selection'=>'<a href="#href#" class="fenye_a fenye_selection">#text#</a>',
					'disable'=>'<a href="#href#" class="fenye_a fenye_disable">#text#</a>',
				],
				'loop_btn'=>[],//空值
				'prev_btn'=>[],//空值
				'next_btn'=>[],//空值
				'first_btn'=>[],//空值
				'last_btn'=>[],//空值
				'first_text'=>'首页',
				'last_text'=>'尾页',
				'prev_text'=>'上一页',
				'next_text'=>'下一页',
				'block'=>''//空值
			],
			
			'html_btn_order'=>[
				'order'=>[ 
					'first_btn',
					'prev_btn',
					'loop_btn',
					'next_btn',
					'last_btn',
					'<span>#p#/#count_paye#</span>',
				],
				'order_true'=>[
					'first_btn',
					'prev_btn',
					'loop_btn',
					'next_btn',
					'last_btn',
					'block',
				],
				'order_rule'=>[]
			],
			'css'=>'',//空值
			'script'=>'',//空值
			'style_label'=>true,
			'script_label'=>true,
		];

		if($where==''){
			return $model;
		}else{
			return $this->getArrayValue($where,$model);
		}
	}

}


?>
