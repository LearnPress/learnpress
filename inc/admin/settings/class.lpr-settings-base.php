<?php

/**
 * Class LPR_Settings_Base
 */
class LPR_Settings_Base{
    protected $id   = '';
    protected $text = '';
    protected $section  = false;
    protected $tab      = false;

    /**
     * Constructor     
     */    
    function __construct(){

        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
        $tabs = learn_press_settings_tabs_array();
        if( !$current_tab && $tabs ){
            $tab_keys = array_keys($tabs);
            $current_tab = reset($tab_keys);
            $this->tab = array(
                'id'    => $current_tab,
                'text'  => $tabs[$current_tab]
            );
        }else{
            $this->tab = array( 'id' => null, 'text' => null );
        }

        $current_section = !empty( $_REQUEST['section'] ) ? $_REQUEST['section'] : '';
        $sections = $this->get_sections();

        if( $sections ){
            $array_keys = array_keys( $sections );
            if( !$current_section ) $current_section = reset( $array_keys );
            if( !empty( $sections[$current_section] ) ){
                $this->section = array( 'id' => $current_section, 'text' => $sections[$current_section] );
            }else{
                $this->section = array( 'id' => null, 'text' => '' );
            }

        }else{
            $this->section = array( 'id' => null, 'text' => '' );
        }

        add_action('learn_press_sections_' . $this->id,         array($this, 'output_sections'));
        add_action('learn_press_settings_' . $this->id,         array($this, 'output'));
        add_action('learn_press_settings_save_' . $this->id,    array($this, 'save'));
    }

    function output_sections(){
        $current_section = $this->section['id'];
        $sections = $this->get_sections();
        if( $sections ){
            $array_keys = array_keys( $sections );
            echo '<ul class="subsubsub clearfix">';
            foreach( $sections as $name => $text ){
            ?>
                <li>
                    <a href="<?php echo '?page=learn_press_settings&tab=' . $this->id . '&section=' . sanitize_title( $name  ) ;?>" class="<?php echo $current_section == $name ? 'current' : '';?>">
                    <?php echo $text;?>
                    </a>
                    <?php echo ( end( $array_keys ) == $name ? '' : '|' );?>
                </li>
            <?php
            }
            echo '</ul>';
            echo '<span class="clear"></span>';
        }
    }

    function output(){

    }

    function save(){

    }

    function get_sections(){
        return false;
    }
}