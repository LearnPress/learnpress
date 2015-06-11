<?php
class LPR_Settings{

    protected $_options = false;
    function __construct( $key = false ){
        $settings_keys = array(
            'general', 'pages', 'payment', 'emails'
        );
        $settings_keys = apply_filters( 'learn_press_settings_keys', $settings_keys );

        if( !in_array( $key, $settings_keys ) ){
            if( $settings_keys ) foreach( $settings_keys as $key ) {
                $this->_options[$key] = get_option( '_lpr_settings_' . $key );
            }
        }else{
            $this->_options = get_option( '_lpr_settings_' . $key );
        }
    }

    function set( $name, $value ){
        $this->_set_option( $this->_options, $name, $value );
    }

    private function _set_option( &$obj, $var, $value ){
        $var = (array)explode('.', $var);
        $current_var = array_shift( $var );
        if( is_object( $obj ) ){
            if( isset( $obj->{$current_var} ) ){
                if( count( $var ) ){
                    $this->_set_option( $obj->{$current_var}, join('.', $var ), $value );
                }else{
                    $obj->{$current_var} = $value;
                }
            }else{
                $obj->{$current_var} = $value;
            }
        }else{
            if( isset( $obj[$current_var] ) ){
                if( count( $var ) ){
                    $this->_set_option( $obj[$current_var], join('.', $var ), $value );
                }else{
                    $obj[$current_var] = $value;
                }
            }else{
                $obj[$current_var] = $value;
            }
        }
    }

    function get( $var, $default = null ){
        return $this->_get_option( $this->_options, $var, $default );
    }

    function _get_option( $obj, $var, $default = null ){
        $var = (array)explode('.', $var);
        $current_var = array_shift( $var );
        if( is_object( $obj ) ){
            if( isset( $obj->{$current_var} ) ){
                if( count( $var ) ){
                    return $this->_get_option( $obj->{$current_var}, join('.', $var ), $default );
                }else{
                    return $obj->{$current_var};
                }
            }else{
                return $default;
            }
        }else{
            if( isset( $obj[$current_var] ) ){
                if( count( $var ) ){
                    return $this->_get_option( $obj[$current_var], join('.', $var ), $default );
                }else{
                    return $obj[$current_var];
                }
            }else{
                return $default;
            }
        }
        return $default;
    }

    static function instance( $key = '' ){
        static $instances = array();
        if( empty( $instances[$key] ) ){
            $instances[$key] = new LPR_Settings( $key );
        }
        return $instances[$key];
    }
}

if( !function_exists( 'learn_press_settings' ) ){
    function learn_press_settings( $setting = null, $key = false ){
        $settings = LPR_Settings::instance( $setting );
        if( $key ) return $settings->get( $key );
        return $settings;
    }
}