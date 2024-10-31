<?php  
class nativery_widget extends WP_Widget {  
  
    function __construct() {  
        parent::__construct(false, 'Nativery Widget', array('description'=>'Display widgets created with nativery.com'));  
    }  
  
    function widget($args, $instance) {  
        
        if (isset($instance['selectedNATwidget'])){
        	$args['selectedNATwidget'] = $instance['selectedNATwidget'];
        	showNATWidget($args);
        }else{
        	$instance['selectedNATwidget'] = 0;
        	echo 'Seleziona il widget da mostrare dall\'area di amministrazione';
        }
        
          
    }  
  
    function update($new_instance, $old_instance) {  
       	$instance = array();
		
		$instance['selectedNATwidget'] = ( !empty( $new_instance['selectedNATwidget'] ) ) ? strip_tags( $new_instance['selectedNATwidget'] ) : '';

		return $instance;
    }  
  
    function old_form($instance) {  
       	$num_ist = 0;
    	if(is_array(get_option('nativery_widgets'))){
			if (isset($instance['selectedNATwidget'])){
				$selectedNATwidget = esc_attr($instance['selectedNATwidget']);
			}else{
				$selectedNATwidget = 0;
			}
		?> 
		<p>
		<label for="<?php echo $this->get_field_id('selectedNATwidget'); ?>">Widget: 
		<select class="widefat" id="<?php echo $this->get_field_id('selectedNATwidget'); ?>" name="<?php echo $this->get_field_name('selectedNATwidget'); ?>">
			<option value="">--seleziona il widget --</option>
		<?php foreach (get_option('nativery_widgets') as $kw => $vw){
			if(($vw['pos']==3)and($vw['act']==1)){
			?>
			<option value="<?php echo $vw['cod'];?>" <?php if($selectedNATwidget==$vw['cod']){echo 'selected=selected'; }?>><?php echo $vw['cod'];?></option>
		<?php 
			$num_ist++;
			}
		}?>
		</select>
		</label>
		</p>
<?php  
    	}
    	if($num_ist==0){
    		?>
    		<div style="border:1px solid red; background-color: #ffebe8; padding: 4px;">Verifica le impostazioni del plugin nativery</div>
    		<?php 
    	}
    }  
    function form($instance) {  
       	$check_cod = true;
		if (isset($instance['selectedNATwidget'])){
			$selectedNATwidget = esc_attr($instance['selectedNATwidget']);
			if((!preg_match ('/^[0-9a-fA-F]{24}$/' , $selectedNATwidget ))and($selectedNATwidget!='')){
	        	$check_cod = false;
	        }
		}else{
			$selectedNATwidget = '';
		}
		?> 
		<p>
			<label for="<?php echo $this->get_field_id('selectedNATwidget'); ?>">Codice Widget Nativery: 
				<input type="text"  id="<?php echo $this->get_field_id('selectedNATwidget'); ?>" name="<?php echo $this->get_field_name('selectedNATwidget'); ?>" value="<?php echo $selectedNATwidget; ?>">
					
			</label>
			<?php if(!$check_cod){echo '<div style="color:red">Codice errato</div>';}?>
		</p>
<?php  
    	
    }  
 }
//------------------  
function showNATWidget($args) {  

	 echo $args['before_widget'];  
?>  
  		
  		<div class="clearfix">
  			
			<?php 
					
			if (isset($args['selectedNATwidget'])and($args['selectedNATwidget']!='')) {  

				echo Nativery::nativery_createCodeNat($args['selectedNATwidget']);
			}
			?>
  		</div>
<?php  
        echo $args['after_widget'];  
    //}  
}  

?>
