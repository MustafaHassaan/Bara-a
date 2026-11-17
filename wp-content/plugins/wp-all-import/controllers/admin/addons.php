<?php
/**
 * Admin Add-ons page
 *
 * @author Maksym Tsypliakov <maksym.tsypliakov@gmail.com>
 */
class PMXI_Admin_Addons extends PMXI_Controller_Admin {

    public static $addons = array('PMWI_Plugin' => 0, 'PMAI_Plugin' => 0, 'PMWITabs_Plugin' => 0, 'PMLI_Plugin' => 0, 'PMLCA_Plugin' => 0, 'PMUI_Plugin' => 0, 'PMTI_Plugin' => 0); // inactive by default



    protected static function set_addons_status(){
        foreach (self::$addons as $class => $active)
            self::$addons[$class] = class_exists($class);

        self::$addons = apply_filters('pmxi_addons', self::$addons);

    }

    public static function get_all_addons(){

        self::set_addons_status();

        return self::$addons;
    }

    public static function get_addon($addon = false){

        self::set_addons_status();

        return ($addon) ? self::$addons[$addon] : false;
    }

    public static function get_active_addons(){

        self::set_addons_status();
        $active_addons = array();
        foreach (self::$addons as $class => $active) if ($active) $active_addons[] = $class;

        return $active_addons;
    }

}
