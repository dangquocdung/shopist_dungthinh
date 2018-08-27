<?php
namespace shopist\Http\Controllers\Admin;

use shopist\Http\Controllers\Controller;
use Validator;
use Request;
use Session;
use shopist\Models\Post;
use shopist\Models\AttributesList;
use shopist\Models\Option;
use shopist\Models\ArtList;
use shopist\Library\GetFunction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use shopist\Models\OrdersItem;
use shopist\Models\VendorWithdraw;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use shopist\Models\PostExtra;
use Illuminate\Support\Facades\App;


class AdminDashboardController extends Controller
{
  public $classGetFunction;
  
  public function __construct(){
    $this->classGetFunction    =   new GetFunction();
  }
  
  /**
   * 
   * Save products attributes
   *
   * @param null
   * @return void
   */
  public function saveProductAttribute(){
    if( Request::isMethod('post') && Session::token() == Input::get('_token')){
      $data = Input::all();
      $rules = [
               'attrName'               => 'required',
               'attrValues'             => 'required'
               ];
        
      $validator = Validator:: make($data, $rules);
      
      if($validator->fails()){
        return redirect()-> back()
        ->withInput()
        ->withErrors( $validator );
      }
      else{
        $attr =       new AttributesList;
        
        $attr->attr_name    = Input::get('attrName');
        $attr->attr_values  = Input::get('attrValues');
        $attr->attr_status  = Input::get('attr_status');
        
        if($attr->save()){
          Session::flash('success-message', Lang::get('admin.successfully_saved_msg'));
          return redirect()->route('admin.update_attr_content', $attr->id);
        }
      }
    }
    else
    {
      return redirect()-> back();
    }
  }
  
  /**
   * 
   * Update products attributes
   *
   * @param id
   * @return void
   */
  public function updateAttrDetails($id){
    if( Request::isMethod('post') && Session::token() == Input::get('_token') ){
      $data = Input::all();
      
      $rules = [
               'attrName'               => 'required',
               'attrValues'             => 'required'
               ];
        
      $validator = Validator:: make($data, $rules);
      
      if($validator->fails()){
        return redirect()-> back()
        ->withInput()
        ->withErrors( $validator );
      }
      else{
        $attr =       new AttributesList;
        
        $data = array(
                      'attr_name'        =>    Input::get('attrName'),
                      'attr_values'      =>    Input::get('attrValues'),
                      'attr_status'      =>    Input::get('attr_status')
        );
        
        if( $attr::where('attr_id', $id)->update($data)){
          Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
          return redirect()->route('admin.update_attr_content', $id);
        }
      }
    }
    else 
    {
      return redirect()-> back();
    }
  }
  
  /**
   * 
   * Update art data
   *
   * @param id
   * @return void
   */
  public function updateArtData($id){
    if( Request::isMethod('post') && Session::token() == Input::get('_token') ){
      $data  = Input::all();
      
      $rules = [
                'inputSelectCategory'               => 'required'
               ];
        
      $validator = Validator:: make($data, $rules);
      
      if($validator->fails()){
        return redirect()-> back()
        ->withInput()
        ->withErrors( $validator );
      }
      else{
        $imgUrlArray = json_decode(Input::get('ht_art_all_uploaded_images'));
        
        if(count($imgUrlArray)>0){
          DB::table('art_lists')
            ->where('id', $id)
            ->update(['art_img_url'  =>  $imgUrlArray[0]->url, 'art_cat_id'   =>  Input::get('inputSelectCategory'), 'art_status'   =>  Input::get('inputArtStatus')]);
          
          if($id){
            Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
            return redirect()->route('admin.update_clipart_content', $id);
          }
        }
        else{
          $art =       new ArtList;
        
          $data = array(
                        'art_img_url'        =>    '',
                        'art_cat_id'         =>    Input::get('inputSelectCategory'),
                        'art_status'         =>    Input::get('inputArtStatus')
          );

          if( $art::where('id', $id)->update($data)){
            Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
            return redirect()->route('admin.update_clipart_content', $id);
          }
        }
      }
    }
    else 
    {
      return redirect()-> back();
    }
  }

  /**
   * 
   * Save shipping method
   *
   * @param null
   * @return void
   */
  public function saveShippingMethod(){
    if( Request::isMethod('post') && Session::token() == Input::get('_token') ){
      $get_return_shipping_data = array();
           
      if(Input::get('_shipping_method_name') == 'save_options'){
        $enable_shipping = (Input::has('inputEnableShipping')) ? true : false;
        $display_mode    = Input::get('inputDisplayMode');

        $get_return_shipping_data = $this->create_shipping_array_data('shipping_option', $enable_shipping, $display_mode, '', '');
      }
      elseif (Input::get('_shipping_method_name') == 'save_flat_rate') {
        $enable_method   = (Input::has('inputEnableFlatRate')) ? true : false;
        $method_title    = Input::get('inputFlatRateTitle');
        $method_cost     = Input::get('inputFlatRateCost');

        $get_return_shipping_data = $this->create_shipping_array_data('shipping_method_flat_rate', $enable_method, $method_title, $method_cost, '');
      }
      elseif (Input::get('_shipping_method_name') == 'save_free_shipping') {
        $enable_method   = (Input::has('inputEnableFreeShipping')) ? true : false;
        $method_title    = Input::get('inputFreeShippingTitle');
        $method_amount   = Input::get('inputFreeShippingOrderAmount');

        $get_return_shipping_data = $this->create_shipping_array_data('shipping_method_free_shipping', $enable_method, $method_title, $method_amount, '');
      }
      elseif (Input::get('_shipping_method_name') == 'save_local_delivery') {
        $enable_method   = (Input::has('inputEnableLocalDelivery')) ? true : false;
        $method_title    = Input::get('inputLocalDeliveryTitle');
        $fee_type        = Input::get('inputLocalDeliveryFeeType');
        $delivery_fee    = Input::get('inputLocalDeliveryDeliveryFee');

        $get_return_shipping_data = $this->create_shipping_array_data('shipping_method_local_delivery', $enable_method, $method_title, $fee_type, $delivery_fee);
      }
      
      $data = array(
                      'option_value'        => serialize($get_return_shipping_data)
      );
      
      if( Option::where('option_name', '_shipping_method_data')->update($data)){
        Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
        return redirect()->back();
      }
    }
    else 
    {
      return redirect()-> back();
    }
  }
  
  

  /**
   * 
   * Shipping data process
   *
   * @param shipping data
   * @return array
   */
  public function create_shipping_array_data($source, $parm1, $parm2, $parm3, $parm4){
    $enable_shipping_option         =   '';
    $shipping_option_display_mode   =   '';
    $flat_rate_enable               =   '';
    $flat_rate_title                =   '';
    $flat_rate_cost                 =   '';
    $free_shipping_enable           =   '';
    $free_shipping_title            =   '';
    $free_shipping_order_amount     =   '';
    $local_delivery_enable          =   '';
    $local_delivery_title           =   '';
    $local_delivery_fee_type        =   '';
    $local_delivery_fee             =   '';
    
    $get_option = Option :: where('option_name', '_shipping_method_data')->first();
    $unserialize_data = unserialize($get_option->	option_value);
    
    if($source == 'shipping_option'){
      $enable_shipping_option         =   $parm1;
      $shipping_option_display_mode   =   $parm2;
    }
    else {
      $enable_shipping_option         =   $unserialize_data['shipping_option']['enable_shipping'];
      $shipping_option_display_mode   =   $unserialize_data['shipping_option']['display_mode'];
    }
    
    if($source == 'shipping_method_flat_rate'){
      $flat_rate_enable               =   $parm1;
      $flat_rate_title                =   $parm2;
      $flat_rate_cost                 =   $parm3;
    }
    else{
      $flat_rate_enable               =   $unserialize_data['flat_rate']['enable_option'];
      $flat_rate_title                =   $unserialize_data['flat_rate']['method_title'];
      $flat_rate_cost                 =   $unserialize_data['flat_rate']['method_cost'];
    }

    if($source == 'shipping_method_free_shipping'){
      $free_shipping_enable           =   $parm1;
      $free_shipping_title            =   $parm2;
      $free_shipping_order_amount     =   $parm3;
    }
    else{
      $free_shipping_enable           =   $unserialize_data['free_shipping']['enable_option'];
      $free_shipping_title            =   $unserialize_data['free_shipping']['method_title'];
      $free_shipping_order_amount     =   $unserialize_data['free_shipping']['order_amount'];
    }
    
    if($source == 'shipping_method_local_delivery'){
      $local_delivery_enable          =   $parm1;
      $local_delivery_title           =   $parm2;
      $local_delivery_fee_type        =   $parm3;
      $local_delivery_fee             =   $parm4;
    }
    else {
      $local_delivery_enable          =   $unserialize_data['local_delivery']['enable_option'];
      $local_delivery_title           =   $unserialize_data['local_delivery']['method_title'];
      $local_delivery_fee_type        =   $unserialize_data['local_delivery']['fee_type'];
      $local_delivery_fee             =   $unserialize_data['local_delivery']['delivery_fee'];
    }
    
    $shipping_method_array = array( 
        'shipping_option'  => array('enable_shipping' => $enable_shipping_option, 'display_mode' => $shipping_option_display_mode),
        'flat_rate'        => array('enable_option' => $flat_rate_enable, 'method_title' => $flat_rate_title, 'method_cost' => $flat_rate_cost),
        'free_shipping'    => array('enable_option' => $free_shipping_enable, 'method_title' => $free_shipping_title, 'order_amount' => $free_shipping_order_amount),
        'local_delivery'   => array('enable_option' => $local_delivery_enable, 'method_title' => $local_delivery_title, 'fee_type' => $local_delivery_fee_type, 'delivery_fee' => $local_delivery_fee)
    );
    
    return $shipping_method_array;
  }
  
  /**
   * 
   * Clear design cache 
   *
   * @param null
   * @return void
   */
  public function clearDesignCache(){
    if(Session::has('shopist_admin_user_id')){
      Artisan::call('cache:clear');
      Artisan::call('view:clear');
      die( Lang::get('admin.cache_cleared'). ' <a href="'. route('admin.dashboard') .'">'. Lang::get('admin.admin_dashboard') .'</a>');
    }
    else{
      die( Lang::get('admin.do_not_sufficient_permission') );
    }
  }
  
  
  /**
   * 
   * Save testimonial post data
   *
   * @param null
   * @return response
   */
  public function saveTestimonialPost($params = ''){
    if( Request::isMethod('post') && Session::token() == Input::get('_token') ){
      $data = Input::all();

      $rules =  [
                  'testimonial_post_title'  => 'required',
                ];

      $validator = Validator:: make($data, $rules);
      
      if($validator->fails()){
        return redirect()-> back()
        ->withInput()
        ->withErrors( $validator );
      }
      else{
        $post       =       new Post;
        $postMeta   =       new PostExtra;

        $post_slug  = '';
        $check_slug = Post::where(['post_slug' => string_slug_format( Input::get('testimonial_post_title') )])->orWhere('post_slug', 'like', '%' . string_slug_format( Input::get('testimonial_post_title') ) . '%')->get()->count();

        if($check_slug === 0){
          $post_slug = string_slug_format( Input::get('testimonial_post_title') );
        }
        elseif($check_slug > 0){
          $slug_count = $check_slug + 1;
          $post_slug = string_slug_format( Input::get('testimonial_post_title') ). '-' . $slug_count;
        }
        
        if(Input::get('hf_post_type') == 'add'){
          $post->post_author_id         =   Session::get('shopist_admin_user_id');
          $post->post_content           =   string_encode(Input::get('testimonial_description_editor'));
          $post->post_title             =   Input::get('testimonial_post_title');
          $post->post_slug              =   $post_slug;
          $post->parent_id              =   0;
          $post->post_status            =   Input::get('testimonial_post_visibility');
          $post->post_type              =   'testimonial';
          
          if($post->save()){
            if(PostExtra::insert(array(
                                array(
                                      'post_id'       =>  $post->id,
                                      'key_name'      =>  '_testimonial_image_url',
                                      'key_value'    =>  Input::get('image_url'),
                                      'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                      'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                ),
                                array(
                                      'post_id'       =>  $post->id,
                                      'key_name'      =>  '_testimonial_client_name',
                                      'key_value'    =>  Input::get('testimonial_client_name'),
                                      'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                      'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                ),
                                array(
                                      'post_id'       =>  $post->id,
                                      'key_name'      =>  '_testimonial_job_title',
                                      'key_value'    =>  Input::get('testimonial_job_title'),
                                      'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                      'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                ),
                                array(
                                      'post_id'       =>  $post->id,
                                      'key_name'      =>  '_testimonial_company_name',
                                      'key_value'    =>  Input::get('testimonial_company_name'),
                                      'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                      'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                ),
                                array(
                                      'post_id'       =>  $post->id,
                                      'key_name'      =>  '_testimonial_url',
                                      'key_value'    =>  Input::get('testimonial_url'),
                                      'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                      'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                )
            ))){
              Session::flash('success-message', Lang::get('admin.successfully_saved_msg') );
              Session::flash('update-message', "");
              return redirect()->route('admin.update_testimonial_post_content', $post->post_slug);
            }
          }
        }
        elseif($params && Input::get('hf_post_type') == 'update'){
          
          $data = array(
                      'post_author_id'         =>  Session::get('shopist_admin_user_id'),
                      'post_content'           =>  string_encode(Input::get('testimonial_description_editor')),
                      'post_title'             =>  Input::get('testimonial_post_title'),
                      'post_status'            =>  Input::get('testimonial_post_visibility')
          );

          if(Post::where('post_slug', $params)->update($data)){
            
            $get_post                 =   Post :: where('post_slug', $params)->first()->toArray();
            
            $testimonial_image_url    = array(
                                            'key_value'    =>  Input::get('image_url')
            );
            
            $testimonial_client_name  = array(
                                            'key_value'    =>  Input::get('testimonial_client_name')
            );
            
            $testimonial_job_title    = array(
                                            'key_value'    =>  Input::get('testimonial_job_title')
            );
            
            $testimonial_company_name = array(
                                            'key_value'    =>  Input::get('testimonial_company_name')
            );
            
            $testimonial_url          = array(
                                            'key_value'    =>  Input::get('testimonial_url')
            );
            
            
            PostExtra::where(['post_id' => $get_post['id'], 'key_name' => '_testimonial_image_url'])->update($testimonial_image_url);
            PostExtra::where(['post_id' => $get_post['id'], 'key_name' => '_testimonial_client_name'])->update($testimonial_client_name);
            PostExtra::where(['post_id' => $get_post['id'], 'key_name' => '_testimonial_job_title'])->update($testimonial_job_title);
            PostExtra::where(['post_id' => $get_post['id'], 'key_name' => '_testimonial_company_name'])->update($testimonial_company_name);
            PostExtra::where(['post_id' => $get_post['id'], 'key_name' => '_testimonial_url'])->update($testimonial_url);
            
            Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
            return redirect()->route('admin.update_testimonial_post_content', $params);
          }
        }
      }
    }
  }
  
	/**
   * 
   * Save color filter data
   *
   * @param null
   * @return response
   */
		
  public function saveProductFilterColorData(){
    if( Request::isMethod('post') && Session::token() == Input::get('_token') ){
      $get_color_name            =  array();
      $get_color_code_name       =  array();
      $final_name_color_code     =  array();
      $input                     =  '';

      if(count(Input::get('product_filter_color_name')) > 0){
        $get_color_name = Input::get('product_filter_color_name');
      }

      if(count(Input::get('product_filter_color')) > 0){
        $get_color_code_name = Input::get('product_filter_color');
      }

      if(count($get_color_name) > 0 && count($get_color_code_name) > 0){
        foreach($get_color_name as $key => $val){
          array_push($final_name_color_code, array('key' => $key, 'color_name' => $get_color_name[$key], 'color_code' => $get_color_code_name[$key]));
        }
      }

      if(count($final_name_color_code) > 0){
        $input = json_encode($final_name_color_code);
      }

      $get_color_filter_field = Option::where(['option_name' => '_product_color_filter_data'])->get();
      if(!empty($get_color_filter_field) && $get_color_filter_field->count() > 0){
          $data = array(
                        'option_value' =>	 $input
                  );

          if(Option::where('option_name', '_product_color_filter_data')->update($data)){
              Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
          }
      }
      else{
          if(Option::insert(
              array(
                              'option_name'  =>  '_product_color_filter_data',
                              'option_value' =>	 $input,
                              'created_at'   =>  date("y-m-d H:i:s", strtotime('now')),
                              'updated_at'   =>  date("y-m-d H:i:s", strtotime('now'))
              ))){
              Session::flash('success-message', Lang::get('admin.successfully_saved_msg'));
          }
      }

      return redirect()-> back();
    }
  }
}