<?php
if(!defined('ABSPATH')) exit;
if(current_user_can( 'manage_options' )){
    if(isset($_POST['gwdp_add_form']) && wp_verify_nonce( $_POST['gwdp_wpnonce'], 'gwdp_nonce' )){
        $role_type = "";
        $post_type = "";
        $shown_link = "";
        $duplicate_post_status = "";
        $duplicate_post_redirect = "";
        $post_link_text = "";
        $post_link_prefix = "";
        $post_link_suffix = "";
        
        if(isset($_POST['role_type']))                  $role_type=$_POST['role_type'];
        if(isset($_POST['post_type']))                  $post_type=$_POST['post_type'];
        if(isset($_POST['shown_link']))                 $shown_link=$_POST['shown_link'];
        if(isset($_POST['duplicate_post_status']))      $duplicate_post_status=sanitize_text_field($_POST['duplicate_post_status']);
        if(isset($_POST['duplicate_post_redirect']))    $duplicate_post_redirect=sanitize_text_field($_POST['duplicate_post_redirect']);
        if(isset($_POST['duplicate_post_author']))      $duplicate_post_author=sanitize_text_field($_POST['duplicate_post_author']);
        if(isset($_POST['post_link_text']))             $post_link_text=sanitize_text_field($_POST['post_link_text']);
        if(isset($_POST['post_link_prefix']))           $post_link_prefix=sanitize_text_field($_POST['post_link_prefix']);
        if(isset($_POST['post_link_suffix']))           $post_link_suffix=sanitize_text_field($_POST['post_link_suffix']);
    
        /* Sanitizing field here */
        if(!empty($role_type)){
            array_walk($role_type, function($value, $key) {
            $value[$key] = sanitize_text_field($value[$key]);
            });	
        }        
        if(!empty($post_type)){
            array_walk($post_type, function($value, $key) {
            $value[$key] = sanitize_text_field($value[$key]);
            });	
        }
        if(!empty($shown_link)){
            array_walk($shown_link, function($value, $key) {
            $value[$key] = sanitize_text_field($value[$key]);
            });	
        }
        $options['gwdp_role_type']=$role_type;
        $options['gwdp_post_type']=$post_type;
        $options['gwdp_shown_link']=$shown_link;
        $options['gwdp_duplicate_post_status']=$duplicate_post_status;
        $options['gwdp_duplicate_post_redirect']=$duplicate_post_redirect;
        $options['gwdp_duplicate_post_author']=$duplicate_post_author;
        $options['gwdp_post_link_text']=$post_link_text;
        $options['gwdp_post_link_prefix']=$post_link_prefix;
        $options['gwdp_post_link_suffix']=$post_link_suffix;
        
        if(wp_verify_nonce( $_POST['gwdp_wpnonce'], 'gwdp_nonce' ) && current_user_can('manage_options')){        
            update_option("gwdp_allowed_options",$options);        
            $successmsg = gwdp_success_option_msg('Settting Saved!');

        }else{
            $errormsg = gwdp_failure_option_msg('Unable to save data!');
        }
    }
}
$options= gwdp_allowed_options_callback();
$selected_role_type="";             if(isset($options['gwdp_role_type'])) { $selected_role_type=$options['gwdp_role_type']; }
$selected_post_type="";             if(isset($options['gwdp_post_type'])) { $selected_post_type=$options['gwdp_post_type']; }
$selected_shown_link="";            if(isset($options['gwdp_shown_link'])) { $selected_shown_link=$options['gwdp_shown_link']; }
$selected_post_status="";           if(isset($options['gwdp_duplicate_post_status'])) { $selected_post_status=$options['gwdp_duplicate_post_status']; }
$selected_post_redirect="";         if(isset($options['gwdp_duplicate_post_redirect'])) { $selected_post_redirect=$options['gwdp_duplicate_post_redirect']; }
$selected_post_author="";           if(isset($options['gwdp_duplicate_post_author'])) { $selected_post_author=$options['gwdp_duplicate_post_author']; }
$post_link_text="Duplicate This";   if(isset($options['gwdp_post_link_text']) && !empty($options['gwdp_post_link_text'])) { $post_link_text=$options['gwdp_post_link_text']; }
$post_link_prefix="";               if(!empty($options['gwdp_post_link_prefix'])) { $post_link_prefix=$options['gwdp_post_link_prefix']; }
$post_link_suffix="";               if(!empty($options['gwdp_post_link_suffix'])) { $post_link_suffix=$options['gwdp_post_link_suffix']; }
$post_types = get_post_types(array('public' => true));
unset($post_types['attachment']); 
global $wp_roles;
$roles=$wp_roles->role_names; ?>

<div class="gwdp_wrap">
    <h2>Duplicate Page Settings</h2>
    <?php
        if ( isset( $successmsg ) ) 
        {
            echo $successmsg; 
        }	
        if ( isset( $errormsg ) ) 
        {
            echo $errormsg;
        }
    ?>
    
    <div class="gwdp_inner">
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr valign="top"><th scope="row">Allowed User Roles</th>
                        <td>
                            <?php if(count($roles)>0 && !empty($roles)){
                                foreach($roles as $key =>$value){ ?>
                                    <input type="checkbox" name="role_type[]" value="<?php echo esc_attr($key); ?>"
                                        <?php if(!empty($selected_role_type)){if(in_array($key,$selected_role_type)){ echo esc_attr("checked");}} ?>><?php echo esc_html($value); ?><br>
                                    <?php
                                }
                            } ?>
                            <span class="note">Allow User roles to access duplicate link.</span>
                        </td>
                    </tr>

                    <tr valign="top"><th scope="row">Allowed Post types</th>                   
                        <td>
                            <?php if(count($post_types)>0 && !empty($post_types)){
                                foreach($post_types as $post_type){ ?>
                                    <input type="checkbox" name="post_type[]" value="<?php echo esc_attr($post_type); ?>"
                                        <?php if(!empty($selected_post_type)){if(in_array($post_type,$selected_post_type)){ echo esc_attr("checked");}} ?>><?php echo esc_html(ucfirst($post_type)); ?><br>
                                    <?php
                                }                                
                            } ?>
                            <span class="note">Allow Post types to access duplicate link.</span>
                        </td>
                    </tr>

                    <?php                    
                        $where_show_links = array(
                            'to_post_list' => 'On item row on the post listing page',
                            'to_post_edit' => 'On The Post Edit Page', 
                            'to_admin_bar' => 'On Admin Bar Post View Page'
                        );
                    ?>
                    <tr valign="top"><th scope="row">Where to show Duplicate Page Link</th>
                        <td>
                            <?php if(count($where_show_links)>0 && !empty($where_show_links)){
                                foreach($where_show_links as $key => $value){ ?>
                                    <input type="checkbox" name="shown_link[]" value="<?php echo esc_attr($key); ?>"
                                        <?php if(!empty($selected_shown_link)){if(in_array($key,$selected_shown_link)){ echo esc_attr('checked');}} ?>><?php echo esc_html(ucfirst($value)); ?><br>
                                    <?php
                                }
                            }?>
                            <span class="note">Check where to shown duplicate link.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Duplicate Post Status</th>
                        <td>							
                            <select name="duplicate_post_status" >
                                <option value="draft" <?php if ($selected_post_status == 'draft') echo esc_attr('selected="selected"'); ?>><?php _e('Draft'); ?></option>
                                <option value="publish" <?php if ($selected_post_status == 'publish') echo esc_attr('selected="selected"'); ?>><?php _e('Publish'); ?></option>
                                <option value="private" <?php if ($selected_post_status == 'private') echo esc_attr('selected="selected"'); ?>><?php _e('Private'); ?></option>
                                <option value="pending" <?php if ($selected_post_status == 'pending') echo esc_attr('selected="selected"'); ?>><?php _e('Pending'); ?></option>
                            </select>
                            <span class="note">Please select any post status you want to assign for duplicate post. Default: <strong>Draft</strong></span>
                        </td>                    
                    </tr>

                    <tr valign="top">
                        <th scope="row">Redirect after click on Link</th>
                        <td>
                            <select name="duplicate_post_redirect">
                                <option value="to_list_post" <?php if ($selected_post_redirect == 'to_list_post') echo esc_attr('selected="selected"'); ?>><?php _e('To All Posts List'); ?></option>
                                <option value="to_edit_post" <?php if ($selected_post_redirect == 'to_edit_post') echo esc_attr('selected="selected"'); ?>><?php _e('To Edit Post'); ?></option>
                                <option value="to_front_post" <?php if ($selected_post_redirect == 'to_front_post') echo esc_attr('selected="selected"'); ?>><?php _e('To Front Post'); ?></option>
                            </select>
                            <span class="note">Please select any post redirection, redirect you to selected after click on duplicate this link. Default: <strong>To current list</strong></span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Duplicate Post Author</th>
                        <?php $users = get_users(); ?>
                        <td>
                            <select name="duplicate_post_author">
                                <option <?php if(empty($selected_post_author)) echo esc_html('selected="selected"'); ?>><?php echo esc_html('Select Author'); ?></option>     
                                <?php 
                                foreach($users as $user){ 
                                    $user_login = $user->data->user_login;
                                    $user_id = $user->data->ID; ?>                           
                                    <option value="<?php echo esc_attr($user_id); ?>" <?php if($user_id == $selected_post_author) echo esc_attr('selected="selected"'); ?>><?php echo esc_html($user_login); ?></option>
                                <?php 
                                } ?>
                            </select>
                            <span class="note">Please select any post author you want to assign for duplicate post. Default: <strong>Current User</strong></span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Duplicate Post Link Text</th>
                        <td>
                            <input type="text" name="post_link_text" placeholder="Duplicate This" value="<?php if(!empty($post_link_text)) echo esc_attr($post_link_text); ?>">
                            <span class="note">Text for duplicate post link. Default: <strong>To current list</strong></span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Duplicate Post Prefix</th>
                        <td>
                            <input type="text" name="post_link_prefix" value="<?php if(!empty($post_link_prefix))  echo esc_attr($post_link_prefix); ?>">
                            <span class="note">Add a prefix to duplicate or clone post e.g. <strong>Copy, </strong><strong>Clone</strong> etc. It will show before title.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Duplicate Post Suffix</th>
                        <td>
                            <input type="text" name="post_link_suffix" value="<?php if(!empty($post_link_suffix)) echo esc_attr($post_link_suffix); ?>">
                            <span class="note">Add a suffix to duplicate or clone post e.g. <strong>Copy, </strong><strong>Clone</strong> etc. It will show after title.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
                <input type="hidden" name="gwdp_wpnonce" value="<?php echo esc_attr(wp_create_nonce('gwdp_nonce')); ?>">
                <input class="button button-primary button-large gwdp_submit" type="submit" name="gwdp_add_form" id="gwdp_submit" value="Save"/>
        </form>        
    </div>
</div>