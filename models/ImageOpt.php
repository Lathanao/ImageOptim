<?php /********************************************************************//**
*          Beautiful Theme for Prestashop
*
*          @author         Lathanao <Lathanao@gmail.com>
*          @copyright      2017 Lathanao
*          @version        1.0
*          @license        Commercial license see README.md
******************************************************************************/

namespace ImageOpt;

class ImageOpt extends \ObjectModel
{
    const TABLE      = 'ImageOpt';
    const PRIMARY    = 'id_imageOpt';
    const LANG_TABLE = null;
    const SHOP_TABLE = null;

    public          $id_image;
    public          $id_product;
    public          $width;
    public          $height;
    public          $weight_before;
    public          $weight_after;
    public          $engine;
    public          $quality;
    public          $md5;
    public          $date_add;
    public          $date_upd;
    public          $path = null;

    public static $definition = array(
        'table'          => self::TABLE,
        'primary'        => self::PRIMARY,
        'multilang'      => false,
        'multishop'      => false,
        'fields'  => array(
          'id_image' =>       array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'db_type' => 'INT(11)'),
          'id_product' =>     array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'db_type' => 'INT(11)'),
          'width' =>          array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'db_type' => 'INT(11)'),
          'height' =>         array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt',  'db_type' => 'INT(11)'),
          'weight_before' =>  array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'db_type' => 'INT(11)'),
          'weight_after' =>   array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'db_type' => 'INT(11)'),
          'engine' =>         array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'db_type' => 'VARCHAR(255)'),
          'quality' =>        array('type' => self::TYPE_INT,    'validate' => 'isUnsignedInt', 'db_type' => 'INT(11)'),
          'md5' =>            array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'db_type' => 'VARCHAR(255)'),
          'date_add' =>       array('type' => self::TYPE_DATE,   'validate' => 'isDate',        'db_type' => 'DATETIME', 'default' => '1970-01-01 00:00:00'),
          'date_upd' =>       array('type' => self::TYPE_DATE,   'validate' => 'isDate',        'db_type' => 'DATETIME', 'default' => '1970-01-01 00:00:00'),
          '$path' =>          array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'db_type' => 'VARCHAR(255)'),
        )
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null) {
        parent::__construct($id, $id_lang, $id_shop);
    }



    public static function getPath(Image $image)
    {

        $this->pathImage = $image->image_dir.$image->getExistingImgPath().'.'.$image->image_format;
        return $this->pathImage;
    }

    public static function createDatabase($className = null)
    {
        $success = true;

        if (empty($className)) {
            $className = get_called_class();
        }

        $definition = static::getDefinition($className);
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.bqSQL($definition['table']).'` (';
        $sql .= '`'.$definition['primary'].'` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,';
        foreach ($definition['fields'] as $fieldName => $field) {
            if ($fieldName === $definition['primary']) {
                continue;
            }
            if (isset($field['lang']) && $field['lang'] || isset($field['shop']) && $field['shop']) {
                continue;
            }
            $sql .= '`'.$fieldName.'` '.$field['db_type'];
            if (isset($field['required'])) {
                $sql .= ' NOT NULL';
            }
            if (isset($field['default'])) {
                $sql .= ' DEFAULT \''.$field['default'].'\'';
            }
            $sql .= ',';
        }
        $sql = trim($sql, ',');
        $sql .= ')';

        try {
            $success &= \Db::getInstance()->execute($sql);
        } catch (PrestaShopDatabaseException $exception) {
            static::dropDatabase($className);

            return false;
        }

        if (isset($definition['multilang']) && $definition['multilang']
            || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.bqSQL($definition['table']).'_lang` (';
            $sql .= '`'.$definition['primary'].'` INT(11) UNSIGNED NOT NULL,';
            foreach ($definition['fields'] as $fieldName => $field) {
                if ($fieldName === $definition['primary'] || !(isset($field['lang']) && $field['lang'])) {
                    continue;
                }
                $sql .= '`'.$fieldName.'` '.$field['db_type'];
                if (isset($field['required'])) {
                    $sql .= ' NOT NULL';
                }
                if (isset($field['default'])) {
                    $sql .= ' DEFAULT \''.$field['default'].'\'';
                }
                $sql .= ',';
            }

            // Lang field
            $sql .= '`id_lang` INT(11) NOT NULL,';

            if (isset($definition['multilang_shop']) && $definition['multilang_shop']) {
                $sql .= '`id_shop` INT(11) NOT NULL,';
            }

            // Primary key
            $sql .= 'PRIMARY KEY (`'.bqSQL($definition['primary']).'`, `id_lang`)';

            $sql .= ')';

            try {
                $success &= \Db::getInstance()->execute($sql);
            } catch (PrestaShopDatabaseException $exception) {
                static::dropDatabase($className);

                return false;
            }
        }

        if (isset($definition['multishop']) && $definition['multishop']
            || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.bqSQL($definition['table']).'_shop` (';
            $sql .= '`'.$definition['primary'].'` INT(11) UNSIGNED NOT NULL,';
            foreach ($definition['fields'] as $fieldName => $field) {
                if ($fieldName === $definition['primary'] || !(isset($field['shop']) && $field['shop'])) {
                    continue;
                }
                $sql .= '`'.$fieldName.'` '.$field['db_type'];
                if (isset($field['required'])) {
                    $sql .= ' NOT NULL';
                }
                if (isset($field['default'])) {
                    $sql .= ' DEFAULT \''.$field['default'].'\'';
                }
                $sql .= ',';
            }

            // Shop field
            $sql .= '`id_shop` INT(11) NOT NULL,';

            // Primary key
            $sql .= 'PRIMARY KEY (`'.bqSQL($definition['primary']).'`, `id_shop`)';

            $sql .= ')';

            try {
                $success &= \Db::getInstance()->execute($sql);
            } catch (PrestaShopDatabaseException $exception) {
                static::dropDatabase($className);

                return false;
            }
        }

        return $success;
    }

    public static function dropDatabase($className = null)
    {
        $success = true;
        if (empty($className)) {
            $className = get_called_class();
        }

        $definition = \ObjectModel::getDefinition($className);

        $success &= \Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.bqSQL($definition['table']).'`');

        if (isset($definition['multilang']) && $definition['multilang']
            || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            $success &= \Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.bqSQL($definition['table']).'_lang`');
        }

        if (isset($definition['multishop']) && $definition['multishop']
            || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            $success &= \Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.bqSQL($definition['table']).'_shop`');
        }

        return $success;
    }
}
