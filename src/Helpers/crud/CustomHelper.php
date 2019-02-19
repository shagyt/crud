<?php

namespace Shagyt\lvcrud\Helpers\crud;

use DB;
use Log;

use Shagyt\lvcrud\Models\Module;
use Shagyt\lvcrud\Models\Menu;
use Carbon;
/**
 * Class self
 * @package App\Helpers
 *
 * This is  Helper class contains methods required for Admin Panel functionality.
 */
class CustomHelper
{
    /**
     * Gives various names of Module in Object like label, table, model, controller, singular
     *
     * $names = self::generateModuleNames($module_name);
     *
     * @param $module_name module name
     * @param $icon module icon in FontAwesome
     * @return object
     */
    public static function generateModuleNames($module_name, $icon)
    {
        $array = array();
        $module_name = trim($module_name);
        $module_name = str_replace(" ", "_", $module_name);
        
        $array['module'] = ucfirst(str_plural($module_name));
        $array['label'] = ucfirst(str_plural(preg_replace('/[A-Z]/', ' $0', $module_name)));
        $array['table'] = str_plural(ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $module_name)), '_'));
        $array['model'] = ucfirst(str_singular($module_name));
        $array['fa_icon'] = $icon;
        $array['controller'] = $array['module'] . "Controller";
        $array['singular_l'] = strtolower(str_singular($module_name));
        $array['singular_c'] = ucfirst(str_singular($module_name));
        
        return (object)$array;
    }
    
    /**
     * Get list of Database tables excluding  Context tables like
     * backups, la_configs, la_menus, migrations, modules, module_fields, module_field_types
     * password_resets, permissions, permission_role, role_module, role_module_fields, role_user
     *
     * Method currently supports MySQL and SQLite databases
     *
     * You can exclude additional tables by $$remove_tables
     *
     * $tables = self::getDBTables([]);
     *
     * @param array $remove_tables exclude additional tables
     * @return array
     */
    public static function getDBTables($remove_tables = [])
    {
        if(env('DB_CONNECTION') == "sqlite") {
            $tables_sqlite = DB::select('select * from sqlite_master where type="table"');
            $tables = array();
            foreach($tables_sqlite as $table) {
                if($table->tbl_name != 'sqlite_sequence') {
                    $tables[] = $table->tbl_name;
                }
            }
        } else if(env('DB_CONNECTION') == "pgsql") {
            $tables_pgsql = DB::select("SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND table_schema = 'public' ORDER BY table_name;");
            $tables = array();
            foreach($tables_pgsql as $table) {
                $tables[] = $table->table_name;
            }
        } else if(env('DB_CONNECTION') == "mysql") {
            $tables = DB::select('SHOW TABLES');
        } else {
            $tables = DB::select('SHOW TABLES');
        }
        
        $tables_out = array();
        foreach($tables as $table) {
            $table = (Array)$table;
            $tables_out[] = array_values($table)[0];
        }
        if(in_array(-1, $remove_tables)) {
            $remove_tables2 = array();
        } else {
            $remove_tables2 = array(
                'backups',
                'la_configs',
                'la_menus',
                'migrations',
                'modules',
                'module_fields',
                'module_field_types',
                'password_resets',
                'permissions',
                'permission_role',
                'role_module',
                'role_module_fields',
                'role_user'
            );
        }
        $remove_tables = array_merge($remove_tables, $remove_tables2);
        $remove_tables = array_unique($remove_tables);
        $tables_out = array_diff($tables_out, $remove_tables);
        
        $tables_out2 = array();
        foreach($tables_out as $table) {
            $tables_out2[$table] = $table;
        }
        
        return $tables_out2;
    }
    
    /**
     * Get Array of All Modules
     *
     * $modules = self::getModuleNames([]);
     *
     * @param array $remove_modules to exclude certain modules.
     * @return array Array of Modules
     */
    public static function getModuleNames($remove_modules = [])
    {
        $modules = Module::all();
        
        $modules_out = array();
        foreach($modules as $module) {
            $modules_out[] = $module->name;
        }
        $modules_out = array_diff($modules_out, $remove_modules);
        
        $modules_out2 = array();
        foreach($modules_out as $module) {
            $modules_out2[$module] = $module;
        }
        
        return $modules_out2;
    }
    
    /**
     * Method copies folder recursively into another
     *
     * CustomHelper::file_copy("", "");
     *
     * @param $src source folder
     * @param $dst destination folder
     */
    public static function file_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst, 0777, true);
        while(false !== ($file = readdir($dir))) {
            if(($file != '.') && ($file != '..')) {
                if(is_dir($src . '/' . $file)) {
                    self::file_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    // ignore files
                    if(!in_array($file, [".DS_Store"])) {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
        }
        closedir($dir);
    }
    
    /**
     * Method deletes folder and its content
     *
     * CustomHelper::file_delete("");
     *
     * @param $dir directory name
     */
    public static function file_delete($dir)
    {
        if(is_dir($dir)) {
            $objects = scandir($dir);
            foreach($objects as $object) {
                if($object != "." && $object != "..") {
                    if(is_dir($dir . "/" . $object))
                        self::file_delete($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }
    
    /**
     * Generate Random Password
     *
     * $password = self::gen_password();
     *
     * @param int $chars_min minimum characters
     * @param int $chars_max maximum characters
     * @param bool $use_upper_case allowed uppercase characters
     * @param bool $include_numbers includes numbers or not
     * @param bool $include_special_chars include special charactors or not
     * @return string random password according to configuration
     */
    public static function gen_password($chars_min = 6, $chars_max = 8, $use_upper_case = false, $include_numbers = false, $include_special_chars = false)
    {
        $length = rand($chars_min, $chars_max);
        $selection = 'aeuoyibcdfghjklmnpqrstvwxz';
        if($include_numbers) {
            $selection .= "1234567890";
        }
        if($include_special_chars) {
            $selection .= "!@\"#$%&[]{}?|";
        }
        $password = "";
        for($i = 0; $i < $length; $i++) {
            $current_letter = $use_upper_case ? (rand(0, 1) ? strtoupper($selection[(rand() % strlen($selection))]) : $selection[(rand() % strlen($selection))]) : $selection[(rand() % strlen($selection))];
            $password .= $current_letter;
        }
        return $password;
    }
    
    /**
     * Get url of image by using $upload_id
     *
     * CustomHelper::img($upload_id);
     *
     * @param $upload_id upload id of image / file
     * @return string file / image url
     */
    public static function img($upload_id, $size = "", $path = false)
    {
        $upload = \App\Models\Upload::find($upload_id);
        if(isset($size) && $size != "") {
            $size = "?s=".$size;
        }
        if(isset($upload->id)) {
            if(isset($path) && $path) {
                return $upload->path;
            } else {
                return url("files/" . $upload->hash . '/' . $upload->name).$size;
            }
        } else {
            return "";
        }
    }
    
    /**
     * Get Thumbnail image path of Uploaded image
     *
     * CustomHelper::createThumbnail($filepath, $thumbpath, $thumbnail_width, $thumbnail_height);
     *
     * @param $filepath file path
     * @param $thumbpath thumbnail path
     * @param $thumbnail_width thumbnail width
     * @param $thumbnail_height thumbnail height
     * @param bool $background background color - default transparent
     * @return bool/string Returns Thumbnail path
     */
    public static function createThumbnail($filepath, $thumbpath, $thumbnail_width, $thumbnail_height, $background = false)
    {
        list($original_width, $original_height, $original_type) = getimagesize($filepath);
        if($original_width > $original_height) {
            $new_width = $thumbnail_width;
            $new_height = intval($original_height * $new_width / $original_width);
        } else {
            $new_height = $thumbnail_height;
            $new_width = intval($original_width * $new_height / $original_height);
        }
        $dest_x = intval(($thumbnail_width - $new_width) / 2);
        $dest_y = intval(($thumbnail_height - $new_height) / 2);
        if($original_type === 1) {
            $imgt = "ImageGIF";
            $imgcreatefrom = "ImageCreateFromGIF";
        } else if($original_type === 2) {
            $imgt = "ImageJPEG";
            $imgcreatefrom = "ImageCreateFromJPEG";
        } else if($original_type === 3) {
            $imgt = "ImagePNG";
            $imgcreatefrom = "ImageCreateFromPNG";
        } else {
            return false;
        }
        $old_image = $imgcreatefrom($filepath);
        $new_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height); // creates new image, but with a black background
        // figuring out the color for the background
        if(is_array($background) && count($background) === 3) {
            list($red, $green, $blue) = $background;
            $color = imagecolorallocate($new_image, $red, $green, $blue);
            imagefill($new_image, 0, 0, $color);
            // apply transparent background only if is a png image
        } else if($background === 'transparent' && $original_type === 3) {
            imagesavealpha($new_image, TRUE);
            $color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
            imagefill($new_image, 0, 0, $color);
        }
        imagecopyresampled($new_image, $old_image, $dest_x, $dest_y, 0, 0, $new_width, $new_height, $original_width, $original_height);
        $imgt($new_image, $thumbpath);
        return file_exists($thumbpath);
    }
    
    /**
     * Print the menu editor view.
     * This needs to be done recursively
     *
     * CustomHelper::print_menu_editor($menu)
     *
     * @param $menu menu array from database
     * @return string menu editor html string
     */
    public static function print_menu_editor($menu)
    {
        $editing = \Collective\Html\FormFacade::open(['route' => [config('aquaspade.base.route_prefix') . '.la_menus.destroy', $menu->id], 'method' => 'delete', 'style' => 'display:inline']);
        $editing .= '<button class="btn btn-xs btn-danger pull-right"><i class="fa fa-times"></i></button>';
        $editing .= \Collective\Html\FormFacade::close();
        if($menu->type != "module") {
            $info = (object)array();
            $info->id = $menu->id;
            $info->name = $menu->name;
            $info->url = $menu->url;
            $info->type = $menu->type;
            $info->icon = $menu->icon;
            
            $editing .= '<a class="editMenuBtn btn btn-xs btn-success pull-right" info=\'' . json_encode($info) . '\'><i class="fa fa-edit"></i></a>';
        }
        $str = '<li class="dd-item dd3-item" data-id="' . $menu->id . '">
			<div class="dd-handle dd3-handle"></div>
			<div class="dd3-content"><i class="fa ' . $menu->icon . '"></i> ' . $menu->name . ' ' . $editing . '</div>';
        
        $childrens = \App\Models\Menu::where("parent", $menu->id)->orderBy('hierarchy', 'asc')->get();
        
        if(count($childrens) > 0) {
            $str .= '<ol class="dd-list">';
            foreach($childrens as $children) {
                $str .= self::print_menu_editor($children);
            }
            $str .= '</ol>';
        }
        $str .= '</li>';
        return $str;
    }
    
    /**
     * Print the sidebar menu view.
     * This needs to be done recursively
     *
     * CustomHelper::print_menu($menu)
     *
     * @param $menu menu array from database
     * @return string menu in html string
     */
    public static function print_menu(Menu $menu, $active = false)
    {
        $childrens = $menu->childrensMenu;

        $treeview = "";
        $str = "";
        $subviewSign = "";
        if(count($childrens)) {
            $treeview = " class=\"treeview\"";
            $subviewSign = '<i class="fa fa-angle-left pull-right"></i>';
        }
        $active_str = '';
        if($active) {
            $active_str = 'class="active"';
        }
        if(count($childrens)) {
            foreach($childrens as $children) {
                if($children->type == 'custom') {
                    if($menu->link == "javascript:void(0)") {
                        $str = '<li' . $treeview . ' ' . $active_str . '><a href="javascript:void(0)"><i class="fa ' . $menu->icon . '"></i> <span>' . $menu->label . '</span> ' . $subviewSign . '</a>';
                    } else {
                        $str = '<li' . $treeview . ' ' . $active_str . '><a href="' . url(config("aquaspade.base.route_prefix") . '/' . $menu->link) . '"><i class="fa ' . $menu->icon . '"></i> <span>' . $menu->label . '</span> ' . $subviewSign . '</a>';
                    }
                } else {
                    $mkmodule = Module::make($children->name);
                    if(isset($mkmodule) && $mkmodule->module) {
                        $module = $mkmodule->module;
                    } else {
                        $module = collect();
                    }
                    if(isset($module->id) && Module::hasAccess($module->id)) {
                        $str = '<li' . $treeview . ' ' . $active_str . '><a href="' . url(config("aquaspade.base.route_prefix") . '/' . $menu->link) . '"><i class="fa ' . $menu->icon . '"></i> <span>' . $menu->label . '</span> ' . $subviewSign . '</a>';
                    }
                }
            }
        } else {
            
            $str = '<li' . $treeview . ' ' . $active_str . '><a href="' . url(config("aquaspade.base.route_prefix") . '/' . $menu->link) . '"><i class="fa ' . $menu->icon . '"></i> <span>' . $menu->label . '</span> ' . $subviewSign . '</a>';
        }
        
        if(count($childrens)) {
            $str .= '<ul class="treeview-menu">';
            foreach($childrens as $children) {
                if($children->type == 'custom') {
                    $str .= self::print_menu($children);
                } else {
                    $mkmodule = Module::make($children->name);
                    if(isset($mkmodule) && $mkmodule->module) {
                        $module = $mkmodule->module;
                    } else {
                        $module = collect();
                    }
                    if(isset($module->id) && Module::hasAccess($module->id)) {
                        $str .= self::print_menu($children);
                    }
                }
            }
            $str .= '</ul>';
        }
        $str .= '</li>';
        return $str;
    }
    
    /**
     * Print the top navbar menu view.
     * This needs to be done recursively
     *
     * CustomHelper::print_menu_topnav($menu)
     *
     * @param $menu menu array from database
     * @param bool $active is this menu active or not
     * @return string menu in html string
     */
    public static function print_menu_topnav($menu, $active = false)
    {
        $childrens = \App\Models\Menu::where("parent", $menu->id)->orderBy('hierarchy', 'asc')->get();
        
        $treeview = "";
        $treeview2 = "";
        $subviewSign = "";
        if(count($childrens)) {
            $treeview = " class=\"dropdown\"";
            $treeview2 = " class=\"dropdown-toggle\" data-toggle=\"dropdown\"";
            $subviewSign = ' <span class="caret"></span>';
        }
        $active_str = '';
        if($active) {
            $active_str = 'class="active"';
        }
        
        $str = '<li ' . $treeview . '' . $active_str . '><a ' . $treeview2 . ' href="' . url(config("aquaspade.base.route_prefix") . '/' . $menu->url) . '">' . $menu->label . $subviewSign . '</a>';
        
        if(count($childrens)) {
            $str .= '<ul class="dropdown-menu" role="menu">';
            foreach($childrens as $children) {
                $str .= self::print_menu_topnav($children);
            }
            $str .= '</ul>';
        }
        $str .= '</li>';
        return $str;
    }
    
    /**
     * Get laravel version. very important in installation and handling Laravel 5.3 changes.
     *
     * CustomHelper::laravel_ver()
     *
     * @return float|string laravel version
     */
    public static function laravel_ver()
    {
        $var = \App::VERSION();
        
        if(starts_with($var, "5.2")) {
            return 5.2;
        } else if(starts_with($var, "5.3")) {
            return 5.3;
        } else if(substr_count($var, ".") == 3) {
            $var = substr($var, 0, strrpos($var, "."));
            return $var . "-str";
        } else {
            return floatval($var);
        }
    }
    
    /**
     * Get real Module name by replacing underscores within name
     *
     * @param $name Module Name with whitespace filled by underscores
     * @return mixed return Module Name
     */
    public static function real_module_name($name)
    {
        $name = preg_replace('/(?<!\ )[A-Z]/', ' $0', $name);
        $name = preg_replace('/[^A-Za-z0-9\-]/', ' ', $name);
        // $name = str_replace('/[?-_]/', ' ', $name);
        return $name;
    }
    
    /**
     * Get complete line within file by comparing passed substring $str
     *
     * CustomHelper::getLineWithString()
     *
     * @param $fileName file name to be scanned
     * @param $str substring to be checked for line match
     * @return int/string return -1 if failed to find otherwise complete line in string format
     */
    public static function getLineWithString($fileName, $str)
    {
        $lines = file($fileName);
        foreach($lines as $lineNumber => $line) {
            if(strpos($line, $str) !== false) {
                return $line;
            }
        }
        return -1;
    }
    
    /**
     * Get complete line within given file contents by comparing passed substring $str
     *
     * CustomHelper::getLineWithString2()
     *
     * @param $content content to be scanned
     * @param $str substring to be checked for line match
     * @return int/string return -1 if failed to find otherwise complete line in string format
     */
    public static function getLineWithString2($content, $str)
    {
        $lines = explode(PHP_EOL, $content);
        foreach($lines as $lineNumber => $line) {
            if(strpos($line, $str) !== false) {
                return $line;
            }
        }
        return -1;
    }
    
    /**
     * Method sets parameter in ".env" file as well as into php environment.
     *
     * CustomHelper::setenv("CACHE_DRIVER", "array");
     *
     * @param $param parameter name
     * @param $value parameter value
     */
    public static function setenv($param, $value)
    {
        
        $envfile = self::openFile('.env');
        $line = self::getLineWithString('.env', $param . '=');
        $envfile = str_replace($line, $param . "=" . $value . "\n", $envfile);
        file_put_contents('.env', $envfile);
        
        $_ENV[$param] = $value;
        putenv($param . "=" . $value);
    }
    
    /**
     * Get file contents
     *
     * @param $from file path
     * @return string file contents in String
     */
    public static function openFile($from)
    {
        $md = file_get_contents($from);
        return $md;
    }
    
    /**
     * Delete file
     *
     * CustomHelper::deleteFile();
     *
     * @param $file_path file's path to be deleted
     */
    public static function deleteFile($file_path)
    {
        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    /**
     * Get Migration file name by passing matching table name
     *
     * CustomHelper::get_migration_file("students_table");
     *
     * @param $file_name matching table name like 'create_employees_table'
     * @return string returns migration file name if found else blank string
     */
    public static function get_migration_file($file_name)
    {
        $mfiles = scandir(base_path('database/migrations/'));
        foreach($mfiles as $mfile) {
            if(str_contains($mfile, $file_name)) {
                $mgr_file = base_path('database/migrations/' . $mfile);
                if(file_exists($mgr_file)) {
                    return 'database/migrations/' . $mfile;
                }
            }
        }
        return "";
    }
    
    /**
     * Check if passed array is associative
     *
     * @param array $array array to be checked associative or not
     * @return bool true if associative
     */
    public static function is_assoc_array(array $array)
    {
        // Keys of the array
        $keys = array_keys($array);
        
        // If the array keys of the keys match the keys, then the array must
        // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
        return array_keys($keys) !== $keys;
    }
    
    /**
     * arrya splice kay
     *
     * @param array $array array
     * @return array
     */
    public static function array_splice_key(array $old_array, $first_index, $second_index,array $push_array)
    {
        return array_slice($old_array, $first_index, $second_index, true) +
                $push_array +
                array_slice($old_array, $second_index, NULL, true);
    }

    /**
     * date change formate
     * 
     * CustomHelper::date_format($date);
     *
     * @param array date string
     * @return formatedDate
     */
    public static function date_format($date, $type = "show")
    {
        if(isset($date) && $date != "") {
            if($type == "show") {
                return date("d M Y", strtotime($date));
            } else if($type == "field_show") {
                return date("d-m-Y", strtotime($date));
            } else if($type == "field_show_with_time") {
                return date("M d, Y h:i A", strtotime($date));
            } else if($type == "data_save_simpel") {
                return date("Y-m-d", strtotime($date));
            } else if($type == "data_save_simpel_with_time") {
                return date("Y-m-d\TH:i:sO", strtotime($date));
            } else if($type == "data_save") {
                return date("c", strtotime($date));
                // return \Date::createFromFormat('d/m/Y', $date)->format('Y-m-d\TH:i:sO');
            } else if($type == "data_save_with_time") {
                return date("c", strtotime($date));
                // return \Date::createFromFormat('d/m/Y H:i:s', $date)->format('Y-m-d\TH:i:sO');
            } else if($type == "month_save") {
                return \Carbon::parse($date)->format('M Y');
            } else if($type == 'time_with_seconds'){
                return date("H:i:s", strtotime($date));
            } else if($type == 'time'){
                return date("h:i A", strtotime($date));
            }
            return $date;
        } else {
            return $date;
        }
    }
    
    /**
     * columns alphbet of excel column
     *
     * @param array date string
     * @return columns alphbet of excel column
     */
    public static function alphabetRange($count = '26', $end_column = 'ZZ', $first_letters = '') {
        $columns = array();
        $lenth = $count;
        $length = strlen($end_column);
        $letters = range('A', 'Z');

        // Iterate over 26 letters.
        foreach ($letters as $letter) {
            if($lenth <= "0") {
                break;
            }
            // Paste the $first_letters before the next.
            $column = $first_letters . $letter; 
            // Add the column to the final array.
            $columns[] = $column;
            // If it was the end column that was added, return the columns.
            if ($column == $end_column) {
                return $columns;
            }
            $lenth--;
        }
        // Add the column children.
        foreach ($columns as $column) {
            if (!in_array($end_column, $columns) && strlen($column) < $length) {
                $new_columns = self::alphabetRange(($count - count($columns)) ,$end_column, $column);
                // Merge the new columns which were created with the final columns array.
                $columns = array_merge($columns, $new_columns);
            }
        }

        return $columns;
    }

    /**
     * columns alphbet of excel column
     *
     * CustomHelper::getIndianCurrency($number);
     * 
     */
    public static function getIndianCurrency(float $number)
    {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $hundred = null;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(0 => '', 1 => 'One', 2 => 'Two',
            3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety');
        $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
        while( $i < $digits_length ) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
            } else $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
        return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise;
    }
}
